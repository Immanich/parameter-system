import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import { fetchSealedQuery, realtime } from '@/lib/neon';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/react';
import { RealtimeProvider, useLiveQuery } from '@neon/realtime-react';
import { type SealedLiveQuery } from '@neon/realtime/client';
import { FormEventHandler, useEffect, useRef, useState } from 'react';

interface LedgerTrip {
    id: number;
    tricycle_number: string;
    driver_name: string;
    date: string;
    pickup_time: string;
    dropoff_time: string;
    pickup_location: string;
    dropoff_location: string;
    pickup_coords: [number, number];
    dropoff_coords: [number, number];
    distance_km: number;
    gps_distance_km: number | null;
    passenger_count: number;
    fare: number;
    rounded_down: boolean;
    slot: string;
}

interface PageLink {
    url: string | null;
    label: string;
    active: boolean;
}

interface LedgerProps {
    trips: { data: LedgerTrip[]; links: PageLink[]; from: number | null; to: number | null; total: number };
    filters: { from: string; to: string; tricycle: string };
    totals: { trips: number; fare: number; km: number; passengers: number };
}

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Ledger', href: '/ledger' }];

const peso = (n: number) => '₱' + n.toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
const mapUrl = ([lat, lng]: [number, number]) => `https://www.openstreetmap.org/?mlat=${lat}&mlon=${lng}#map=18/${lat}/${lng}`;

export default function Ledger({ trips, filters, totals }: LedgerProps) {
    const [form, setForm] = useState(filters);

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        router.get('/ledger', form, { preserveState: true, replace: true });
    };

    const reset = () => {
        setForm({ from: '', to: '', tricycle: '' });
        router.get('/ledger', {}, { replace: true });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Ledger" />
            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <form onSubmit={submit} className="flex flex-wrap items-end gap-3">
                    <div className="grid gap-1">
                        <Label htmlFor="from">From</Label>
                        <Input id="from" type="date" value={form.from} onChange={(e) => setForm({ ...form, from: e.target.value })} />
                    </div>
                    <div className="grid gap-1">
                        <Label htmlFor="to">To</Label>
                        <Input id="to" type="date" value={form.to} onChange={(e) => setForm({ ...form, to: e.target.value })} />
                    </div>
                    <div className="grid gap-1">
                        <Label htmlFor="tricycle">MTOP / Tricycle No.</Label>
                        <Input
                            id="tricycle"
                            value={form.tricycle}
                            placeholder="Search"
                            onChange={(e) => setForm({ ...form, tricycle: e.target.value })}
                        />
                    </div>
                    <Button type="submit">Filter</Button>
                    <Button type="button" variant="outline" onClick={reset}>
                        Reset
                    </Button>
                    <Button asChild variant="outline">
                        <a href={route('ledger.export', filters)}>Export CSV</a>
                    </Button>
                    <div className="ml-auto self-center">
                        <RealtimeProvider client={realtime}>
                            <LiveUpdates />
                        </RealtimeProvider>
                    </div>
                </form>

                <div className="grid grid-cols-2 gap-3 md:grid-cols-4">
                    <Stat label="Trips" value={totals.trips.toLocaleString()} />
                    <Stat label="Passengers" value={totals.passengers.toLocaleString()} />
                    <Stat label="Distance" value={`${totals.km.toFixed(2)} km`} />
                    <Stat label="Fares" value={peso(totals.fare)} />
                </div>

                <div className="border-sidebar-border/70 dark:border-sidebar-border overflow-x-auto rounded-xl border">
                    <table className="w-full text-sm">
                        <thead className="bg-muted/50 text-left">
                            <tr>
                                <Th>MTOP/Tricycle No.</Th>
                                <Th>Date</Th>
                                <Th>Pickup time</Th>
                                <Th>Drop-off time</Th>
                                <Th>Pickup location</Th>
                                <Th>Drop-off location</Th>
                                <Th className="text-right">Distance (km)</Th>
                                <Th className="text-right">No. of passengers</Th>
                                <Th className="text-right">Fare</Th>
                            </tr>
                        </thead>
                        <tbody>
                            {trips.data.length === 0 && (
                                <tr>
                                    <td colSpan={9} className="text-muted-foreground p-6 text-center">
                                        No trips found.
                                    </td>
                                </tr>
                            )}
                            {trips.data.map((t) => (
                                <tr key={t.id} className="border-t">
                                    <Td>
                                        <div className="font-medium">{t.tricycle_number}</div>
                                        <div className="text-muted-foreground text-xs">{t.driver_name}</div>
                                    </Td>
                                    <Td className="whitespace-nowrap">{t.date}</Td>
                                    <Td className="whitespace-nowrap">{t.pickup_time}</Td>
                                    <Td className="whitespace-nowrap">{t.dropoff_time}</Td>
                                    <Td>
                                        <a href={mapUrl(t.pickup_coords)} target="_blank" rel="noreferrer" className="hover:underline">
                                            {t.pickup_location}
                                        </a>
                                    </Td>
                                    <Td>
                                        <a href={mapUrl(t.dropoff_coords)} target="_blank" rel="noreferrer" className="hover:underline">
                                            {t.dropoff_location}
                                        </a>
                                    </Td>
                                    <Td
                                        className="text-right tabular-nums"
                                        title={t.gps_distance_km != null ? `GPS: ${t.gps_distance_km.toFixed(2)} km` : undefined}
                                    >
                                        {t.distance_km.toFixed(2)}
                                    </Td>
                                    <Td className="text-right tabular-nums">{t.passenger_count}</Td>
                                    <Td className="text-right whitespace-nowrap tabular-nums">
                                        {peso(t.fare)}
                                        {t.rounded_down && <span className="text-muted-foreground ml-1 text-xs">↓</span>}
                                    </Td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>

                {trips.total > 0 && (
                    <div className="flex flex-wrap items-center justify-between gap-2 text-sm">
                        <span className="text-muted-foreground">
                            {trips.from}–{trips.to} of {trips.total}
                        </span>
                        <div className="flex flex-wrap gap-1">
                            {trips.links.map((l, i) =>
                                l.url ? (
                                    <Link
                                        key={i}
                                        href={l.url}
                                        preserveScroll
                                        className={`rounded-md border px-3 py-1 ${l.active ? 'bg-primary text-primary-foreground' : 'hover:bg-muted'}`}
                                        dangerouslySetInnerHTML={{ __html: l.label }}
                                    />
                                ) : (
                                    <span
                                        key={i}
                                        className="text-muted-foreground rounded-md border px-3 py-1 opacity-50"
                                        dangerouslySetInnerHTML={{ __html: l.label }}
                                    />
                                ),
                            )}
                        </div>
                    </div>
                )}
            </div>
        </AppLayout>
    );
}

interface LatestTrip {
    id: number;
    updated_at: string;
}

const sealLatestTrip = () => fetchSealedQuery<LatestTrip>(route('ledger.live'));

/** Reloads the ledger whenever Neon Realtime reports a trip was added or changed (e.g. a driver synced). */
function LiveUpdates() {
    const [sealed, setSealed] = useState<SealedLiveQuery<LatestTrip>>();
    const [failed, setFailed] = useState(false);

    useEffect(() => {
        sealLatestTrip().then(setSealed, () => setFailed(true));
    }, []);

    if (failed) return <LiveBadge live={false} label="Live updates off" />;
    if (!sealed) return <LiveBadge live={false} label="Connecting…" />;
    return <LiveSubscription sealed={sealed} />;
}

function LiveSubscription({ sealed }: { sealed: SealedLiveQuery<LatestTrip> }) {
    const { data, status } = useLiveQuery(sealed, { refreshQuery: sealLatestTrip });
    const latest = data === undefined ? undefined : data[0] ? `${data[0].id}@${String(data[0].updated_at)}` : 'none';
    const seen = useRef<string | undefined>(undefined);

    useEffect(() => {
        if (latest === undefined) return;
        if (seen.current !== undefined && seen.current !== latest) {
            router.reload({ only: ['trips', 'totals'] });
        }
        seen.current = latest;
    }, [latest]);

    if (status === 'error') return <LiveBadge live={false} label="Live updates stopped" />;
    return <LiveBadge live={status === 'live'} label={status === 'live' ? 'Live' : 'Connecting…'} />;
}

function LiveBadge({ live, label }: { live: boolean; label: string }) {
    return (
        <span className="text-muted-foreground inline-flex items-center gap-2 text-xs" data-live={live}>
            <span className={`h-2 w-2 rounded-full ${live ? 'bg-green-500' : 'bg-muted-foreground/40'}`} />
            {label}
        </span>
    );
}

function Stat({ label, value }: { label: string; value: string }) {
    return (
        <div className="border-sidebar-border/70 dark:border-sidebar-border rounded-xl border p-4">
            <div className="text-muted-foreground text-xs">{label}</div>
            <div className="text-xl font-semibold tabular-nums">{value}</div>
        </div>
    );
}

function Th({ children, className = '' }: { children: React.ReactNode; className?: string }) {
    return <th className={`px-3 py-2 font-medium whitespace-nowrap ${className}`}>{children}</th>;
}

function Td({ children, className = '', title }: { children: React.ReactNode; className?: string; title?: string }) {
    return (
        <td className={`px-3 py-2 align-top ${className}`} title={title}>
            {children}
        </td>
    );
}
