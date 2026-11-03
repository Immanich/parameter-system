<?php

namespace App\Services;

use Firebase\JWT\JWK;
use Firebase\JWT\JWT;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use UnexpectedValueException;

/**
 * Verifies JWTs issued by Neon Auth (Managed Better Auth) against the branch JWKS.
 */
class NeonAuth
{
    /**
     * @return array<string, mixed> The verified claims.
     *
     * @throws UnexpectedValueException When the token is invalid, expired, or not for this branch.
     */
    public function verify(string $token): array
    {
        $origin = $this->origin();

        try {
            $claims = (array) JWT::decode($token, JWK::parseKeySet($this->jwks(), 'EdDSA'));
        } catch (UnexpectedValueException $e) {
            // Keys may have rotated since they were cached: refetch once.
            Cache::forget('neon-auth:jwks');
            $claims = (array) JWT::decode($token, JWK::parseKeySet($this->jwks(), 'EdDSA'));
        }

        if (($claims['iss'] ?? null) !== $origin || ! in_array($origin, (array) ($claims['aud'] ?? []), true)) {
            throw new UnexpectedValueException('Token was not issued for this Neon Auth branch.');
        }

        return $claims;
    }

    /**
     * @return array{keys: list<array<string, mixed>>}
     */
    private function jwks(): array
    {
        return Cache::remember('neon-auth:jwks', now()->addHour(), fn () => Http::timeout(10)
            ->get(config('services.neon.auth_jwks_url'))
            ->throw()
            ->json());
    }

    private function origin(): string
    {
        $url = parse_url(config('services.neon.auth_url'));

        return $url['scheme'].'://'.$url['host'].(isset($url['port']) ? ':'.$url['port'] : '');
    }
}
