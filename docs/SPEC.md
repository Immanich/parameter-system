# ParaMeter — Spec

Fare meter for tricycles / bao-bao in **Tagbilaran City** (single city). Two apps:

- `web/` — Laravel 12 + Inertia + React. Admin-only web UI (trip ledger) and the mobile API (Sanctum tokens).
- `mobile/` — Expo React Native app used by drivers as the meter. Works offline.

## Data model (server)

### users (admins)
Laravel default `users` table. No public registration; one admin is seeded
(`ADMIN_EMAIL` / `ADMIN_PASSWORD` env, defaults `admin@parameter.test` / `password`).

### drivers
| column | type | notes |
|---|---|---|
| id | bigint | |
| first_name | string | required |
| middle_name | string null | |
| last_name | string | required |
| tricycle_number | string unique | MTOP / tricycle body number |
| contact_number | string unique | |
| email | string null unique | optional, not verified |
| password | string | hashed |
| timestamps | | |

### trips
| column | type | notes |
|---|---|---|
| id | bigint | |
| uuid | uuid unique | generated on the phone; idempotency key |
| driver_id | fk drivers | |
| slot | char(1) | A–E, the meter slot used |
| passenger_count | tinyint | 1–5 |
| pickup_lat / pickup_lng | decimal(10,7) | |
| dropoff_lat / dropoff_lng | decimal(10,7) | |
| pickup_street / dropoff_street | string null | street name (reverse geocoded / nearest graph way) |
| pickup_at / dropoff_at | datetime | |
| route_distance_km | decimal(8,3) | **charged** distance (shortest legal road route) |
| gps_distance_km | decimal(8,3) null | GPS distance actually travelled, **logged only** |
| fare | decimal(8,2) | total fare for all passengers |
| rounded_down | bool | fare was rounded down to whole peso |
| timestamps | | `created_at` = time received by server |

## Fare rules

```
per_passenger = max(15, 15 × route_km)
fare          = round(per_passenger × passenger_count, 1)
if round_down setting on: fare = floor(fare), rounded_down = true
```

- Base/minimum fare ₱15 per passenger; ₱15 per km after that.
- `route_km` is the shortest **legal** road route between the pickup and drop-off pins
  (A* over a bundled Tagbilaran road graph from OpenStreetMap, respecting `oneway`).
  A block-list hook lets specific ways be excluded (closures, no-tricycle roads).
- If the graph is missing or no route is found, fall back to straight-line (haversine) distance.
- GPS distance travelled is recorded for audit only and never affects the fare.
  Points with accuracy worse than 25 m, or moves under 5 m, are ignored.

## API (`/api`, JSON)

| method | path | auth | body | response |
|---|---|---|---|---|
| POST | `/drivers/register` | – | first_name, middle_name?, last_name, tricycle_number, contact_number, email?, password, device_name? | 201 `{token, driver}` |
| POST | `/drivers/login` | – | login (tricycle_number or contact_number), password, device_name? | 200 `{token, driver}` / 422 |
| POST | `/drivers/logout` | token | – | 204 |
| GET | `/drivers/me` | token | – | `{driver}` |
| POST | `/trips` | token | `{trips: [Trip…]}` (1–100) | 200 `{accepted: [uuid…], rejected: [{uuid, errors}]}` |

- No email/phone verification on register.
- `POST /trips` is **batch and idempotent** on `uuid`: re-sending a trip that already
  exists for the same driver is reported as accepted and not duplicated. Each trip is
  validated independently, so one bad trip does not block the rest of the batch.
- Trip payload keys match the trip columns above (except `id`, `driver_id`).

## Meter flow (mobile)

Buttons: `A B C D E` (slots), `1 2 3 4 5` (passenger count), `UNDO`, `CONFIRM`.
Each slot is an independent passenger group: `idle → traveling → fare_shown → (confirm) idle`.

1. **1st press of a letter** (slot idle): pickup. Pin GPS + time, slot → *traveling*,
   passenger count = 1. Slot becomes the *selected* slot.
2. **Number** (optional, while selected slot is traveling): set passenger count.
3. **2nd press of the same letter** (slot traveling): drop-off. Pin GPS + time,
   compute route distance and fare, slot → *fare_shown*, fare displayed.
4. **UNDO**
   - on a *fare_shown* slot: discard drop-off, slot back to *traveling*.
   - on a *traveling* slot: clear the slot (trip cancelled).
5. **CONFIRM** (on a *fare_shown* slot): save trip to local SQLite, try to upload,
   clear slot.

UNDO/CONFIRM/numbers act on the selected slot (the last letter pressed).
While a slot is traveling, its GPS distance is accumulated in the background (logging only).

## Offline / sync

- Trips are written to on-device SQLite first (`synced = 0`).
- Upload immediately if online; unsent trips are retried when connectivity returns,
  on app foreground, and on a timer. Server idempotency makes retries safe.

## Admin web

- Login only (no registration).
- **Ledger** page: MTOP/Tricycle No., Date, Pickup time, Drop-off time, Pickup location,
  Drop-off location, Distance (km), No. of passengers, Fare. Filter by date range and
  tricycle number; paginated; newest first.

## Road graph

`mobile/scripts/build-graph.mjs` turns an OSM extract (`.osm` XML, or Overpass JSON) of
Tagbilaran into `mobile/assets/graph/tagbilaran.json`:

```
{ "nodes": [[lat, lng], …], "segments": [[a, b, meters, wayIdx, oneway], …], "ways": [{id, name}] }
```

Only drivable highway types are kept (no footways/paths/steps/tracks, no `access=no|private`).
`oneway=yes/1/true` and roundabouts are directed a→b (`oneway=1`); `oneway=-1` is stored reversed.
Pins are snapped to the nearest road segment (≤ 250 m), and the street name of that segment is
used as the pickup/drop-off location (works offline). Blocked roads: list OSM way ids in
`mobile/assets/graph/blocked-ways.json` (or call `router.setBlockedWays()`).

## Offline map

`mobile/scripts/build-tiles.mjs` turns the same OSM extract into vector tiles (z11–16) stored in
`mobile/assets/map/tagbilaran.mbtiles` (MBTiles, i.e. SQLite) with layers `roads`, `buildings`,
`landuse`, `water`, `waterway`, `coastline`, `boundary`, `places`. The app copies it (and the
label glyphs) to the documents directory on first run and renders it with MapLibre using a local
style (`src/map/style.ts`). No network or tile server is needed. The meter screen map shows the
driver position, pickup pins of active slots, and the selected slot's drop-off and charged route.
Attribution "© OpenStreetMap contributors" is always visible.
