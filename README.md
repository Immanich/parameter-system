# ParaMeter — web

Tricycle / bao-bao fare meter for Tagbilaran City: Laravel 12 + Inertia + React admin ledger
and the driver API (Sanctum) used by the mobile meter app. See [docs/SPEC.md](docs/SPEC.md).

Backend services run on [Neon](https://neon.com) (declared in `neon.ts`): Postgres, Neon Auth,
Object Storage (ledger CSV exports) and Realtime (live ledger updates).

```bash
composer install && npm install
cp .env.example .env && php artisan key:generate   # first time only
neon login && neon deploy        # provisions neon.ts and writes the Neon variables into .env
php artisan migrate --database=pgsql_direct --seed # seeds admin (ADMIN_EMAIL/ADMIN_PASSWORD) + demo trips outside production
composer dev                     # serve + queue + logs + vite
```

Admin login: `admin@parameter.test` / `password` (change via `.env`). Tests: `php artisan test`.

So the phone can reach the API, serve on your LAN: `php artisan serve --host=0.0.0.0 --port=8000`.
