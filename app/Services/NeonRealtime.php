<?php

namespace App\Services;

use Illuminate\Support\Facades\Process;
use RuntimeException;

/**
 * Seals live queries for Neon Realtime. A sealed query is a short-lived bearer
 * capability: only hand it to an authorized user, and keep it out of URLs and logs.
 */
class NeonRealtime
{
    /**
     * @param  string  $sql  A single read-only, deterministic SELECT with $1-style placeholders.
     * @param  list<string|int|float|bool|null>  $params
     * @return array<string, mixed>
     */
    public function seal(string $sql, array $params = []): array
    {
        $result = Process::path(base_path())
            ->env([
                'NEON_REALTIME_SECRET' => config('services.neon.realtime_secret'),
                'DATABASE_URL' => config('database.connections.pgsql.url'),
            ])
            ->input(json_encode(['sql' => $sql, 'params' => $params]))
            ->timeout(15)
            ->run(['node', 'scripts/realtime-seal.mjs']);

        if ($result->failed()) {
            throw new RuntimeException('Sealing the live query failed: '.trim($result->errorOutput()));
        }

        return json_decode($result->output(), true, flags: JSON_THROW_ON_ERROR);
    }
}
