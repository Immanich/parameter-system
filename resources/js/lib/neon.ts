import { createAuthClient } from '@neon/auth';
import { createRealtimeClient, type SealedLiveQuery } from '@neon/realtime/client';

type AuthResult<T> = Promise<{ data: T | null; error: { message?: string; status: number } | null }>;

/**
 * Methods the managed client registers at runtime (jwtClient, emailOTPClient) but that
 * @neon/auth's published types drop under this project's TypeScript setup.
 */
interface NeonAuthPlugins {
    token(): AuthResult<{ token: string }>;
    emailOtp: {
        sendVerificationOtp(input: { email: string; type: 'email-verification' | 'sign-in' | 'forget-password' }): AuthResult<{ success: boolean }>;
        verifyEmail(input: { email: string; otp: string }): AuthResult<unknown>;
    };
}

/** Neon Auth (Managed Better Auth) for this branch. Laravel turns its JWT into a session at `login.neon`. */
const client = createAuthClient(import.meta.env.VITE_NEON_AUTH_URL);
export const neonAuth = client as typeof client & NeonAuthPlugins;

/** One Realtime client per page: subscriptions share its connection. */
export const realtime = createRealtimeClient({ url: import.meta.env.VITE_NEON_REALTIME_URL });

/** POST to a Laravel route that returns a sealed live query (session + CSRF protected). */
export async function fetchSealedQuery<Row>(url: string): Promise<SealedLiveQuery<Row>> {
    const xsrf = document.cookie.match(/(?:^|; )XSRF-TOKEN=([^;]*)/)?.[1];
    const res = await fetch(url, {
        method: 'POST',
        credentials: 'same-origin',
        headers: { Accept: 'application/json', 'X-XSRF-TOKEN': xsrf ? decodeURIComponent(xsrf) : '' },
    });
    if (!res.ok) throw new Error(`Sealing the live query failed (${res.status})`);
    return res.json();
}
