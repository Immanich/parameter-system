// Seals a read-only live query for Neon Realtime. The sealing SDK is TypeScript-only,
// so Laravel (App\Services\NeonRealtime) runs this with {"sql", "params"} on stdin
// and reads the sealed query JSON from stdout.
import { createRealtime, rawSql } from '@neon/realtime/server';

const { NEON_REALTIME_SECRET, DATABASE_URL } = process.env;
if (!NEON_REALTIME_SECRET || !DATABASE_URL) {
    console.error('NEON_REALTIME_SECRET and DATABASE_URL must be set');
    process.exit(1);
}

let input = '';
for await (const chunk of process.stdin) input += chunk;
const { sql, params = [] } = JSON.parse(input);

const realtime = createRealtime({
    secret: NEON_REALTIME_SECRET,
    db: new URL(DATABASE_URL).pathname.slice(1),
});

process.stdout.write(JSON.stringify(await realtime.seal({ query: rawSql(sql, params) })));
