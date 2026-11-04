import { Head, router, useForm } from '@inertiajs/react';
import { CircleCheck, Eye, EyeOff, LoaderCircle } from 'lucide-react';
import { FormEventHandler, useState } from 'react';

import InputError from '@/components/input-error';
import TextLink from '@/components/text-link';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AuthLayout from '@/layouts/auth-layout';
import { neonAuth } from '@/lib/neon';

type LoginForm = {
    email: string;
    password: string;
    remember: boolean;
};

interface LoginProps {
    status?: string;
    canResetPassword: boolean;
}

export default function Login({ status, canResetPassword }: LoginProps) {
    const { data, setData, post, processing, errors, reset } = useForm<LoginForm>({
        email: '',
        password: '',
        remember: false,
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post(route('login'), {
            onFinish: () => reset('password'),
        });
    };

    return (
        <AuthLayout title="Log in" description="Admin access to the trip ledger">
            <Head title="Log in" />

            {status && <StatusBanner>{status}</StatusBanner>}

            <form className="grid gap-5" onSubmit={submit}>
                <div className="grid gap-2">
                    <Label htmlFor="email">Email address</Label>
                    <Input
                        id="email"
                        type="email"
                        required
                        autoFocus
                        autoComplete="email"
                        className="h-11"
                        value={data.email}
                        onChange={(e) => setData('email', e.target.value)}
                        placeholder="you@example.com"
                        aria-invalid={!!errors.email}
                        aria-describedby={errors.email ? 'email-error' : undefined}
                    />
                    <InputError id="email-error" message={errors.email} />
                </div>

                <div className="grid gap-2">
                    <div className="flex items-center justify-between">
                        <Label htmlFor="password">Password</Label>
                        {/* {canResetPassword && (
                            <TextLink href={route('password.request')} className="text-sm">
                                Forgot password?
                            </TextLink>
                        )} */}
                    </div>
                    <PasswordInput
                        id="password"
                        autoComplete="current-password"
                        value={data.password}
                        onChange={(value) => setData('password', value)}
                        invalid={!!errors.password}
                        describedBy={errors.password ? 'password-error' : undefined}
                    />
                    <InputError id="password-error" message={errors.password} />
                </div>

                <div className="flex items-center gap-3">
                    <Checkbox
                        id="remember"
                        name="remember"
                        checked={data.remember}
                        onCheckedChange={(checked) => setData('remember', checked === true)}
                    />
                    <Label htmlFor="remember" className="text-muted-foreground cursor-pointer font-normal">
                        Keep me signed in
                    </Label>
                </div>

                <Button type="submit" className="mt-1 h-11 w-full" disabled={processing}>
                    {processing && <LoaderCircle className="h-4 w-4 animate-spin" />}
                    {processing ? 'Signing in…' : 'Sign in'}
                </Button>
            </form>

            {/* Neon Auth sign-in is hidden for now. Uncomment to bring back the "or" divider and the Neon Auth option.
            <div className="my-7 flex items-center gap-3" role="separator">
                <span className="bg-border h-px flex-1" />
                <span className="text-muted-foreground text-xs font-medium tracking-wider uppercase">or</span>
                <span className="bg-border h-px flex-1" />
            </div>

            <NeonAuthSignIn />
            */}
        </AuthLayout>
    );
}

function StatusBanner({ children }: { children: React.ReactNode }) {
    return (
        <div
            role="status"
            className="border-success/30 bg-success/10 text-success mb-6 flex items-start gap-2.5 rounded-lg border px-3.5 py-3 text-sm font-medium"
        >
            <CircleCheck className="mt-0.5 size-4 shrink-0" />
            <span>{children}</span>
        </div>
    );
}

function PasswordInput({
    id,
    value,
    onChange,
    autoComplete,
    invalid,
    describedBy,
    minLength,
}: {
    id: string;
    value: string;
    onChange: (value: string) => void;
    autoComplete: string;
    invalid?: boolean;
    describedBy?: string;
    minLength?: number;
}) {
    const [visible, setVisible] = useState(false);

    return (
        <div className="relative">
            <Input
                id={id}
                type={visible ? 'text' : 'password'}
                required
                minLength={minLength}
                autoComplete={autoComplete}
                className="h-11 pr-11"
                value={value}
                onChange={(e) => onChange(e.target.value)}
                placeholder="Enter your password"
                aria-invalid={invalid}
                aria-describedby={describedBy}
            />
            <button
                type="button"
                onClick={() => setVisible((v) => !v)}
                aria-label={visible ? 'Hide password' : 'Show password'}
                aria-pressed={visible}
                className="text-muted-foreground hover:text-foreground focus-visible:ring-ring absolute inset-y-0 right-0 flex w-11 items-center justify-center rounded-r-md focus-visible:ring-2 focus-visible:outline-hidden"
            >
                {visible ? <EyeOff className="size-4" /> : <Eye className="size-4" />}
            </button>
        </div>
    );
}

type NeonMode = 'closed' | 'sign-in' | 'sign-up' | 'verify';

/**
 * Sign in through Neon Auth, then hand its JWT to Laravel, which starts a session
 * for the admin with the same (verified) email.
 * Currently hidden on the login page (see the commented block in <Login />).
 */
// eslint-disable-next-line @typescript-eslint/no-unused-vars
function NeonAuthSignIn() {
    const [mode, setMode] = useState<NeonMode>('closed');
    const [email, setEmail] = useState('');
    const [password, setPassword] = useState('');
    const [otp, setOtp] = useState('');
    const [error, setError] = useState<string>();
    const [notice, setNotice] = useState<string>();
    const [processing, setProcessing] = useState(false);

    const run = async (action: () => Promise<void>) => {
        setError(undefined);
        setNotice(undefined);
        setProcessing(true);
        try {
            await action();
        } catch (e) {
            setError(e instanceof Error ? e.message : 'Something went wrong. Try again.');
        } finally {
            setProcessing(false);
        }
    };

    const signIn = () =>
        run(async () => {
            const signedIn = await neonAuth.signIn.email({ email, password });
            if (signedIn.error) throw new Error(signedIn.error.message ?? 'Invalid email or password.');
            const { data, error } = await neonAuth.token();
            if (error || !data?.token) throw new Error('Could not get a Neon Auth token.');
            await new Promise<void>((resolve) =>
                router.post(
                    route('login.neon'),
                    { token: data.token },
                    {
                        onError: (errors) => {
                            setError(errors.neon ?? 'Sign-in was rejected.');
                            void neonAuth.signOut();
                        },
                        onFinish: () => resolve(),
                    },
                ),
            );
        });

    const signUp = () =>
        run(async () => {
            const { error } = await neonAuth.signUp.email({ email, password, name: email.split('@')[0] });
            if (error) throw new Error(error.message ?? 'Could not create the account.');
            await sendCode();
        });

    const sendCode = async () => {
        const { error } = await neonAuth.emailOtp.sendVerificationOtp({ email, type: 'email-verification' });
        if (error) throw new Error(error.message ?? 'Could not send the verification code.');
        setMode('verify');
        setNotice(`We sent a verification code to ${email}.`);
    };

    const verify = () =>
        run(async () => {
            const { error } = await neonAuth.emailOtp.verifyEmail({ email, otp });
            if (error) throw new Error(error.message ?? 'That code did not work.');
            setOtp('');
            setMode('sign-in');
            setNotice('Email verified. Sign in to continue.');
        });

    if (mode === 'closed') {
        return (
            <Button type="button" variant="outline" className="bg-card h-11 w-full" onClick={() => setMode('sign-in')}>
                Continue with Neon Auth
            </Button>
        );
    }

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        if (mode === 'sign-in') signIn();
        else if (mode === 'sign-up') signUp();
        else verify();
    };

    return (
        <form className="bg-card grid gap-4 rounded-xl border p-5 shadow-xs" onSubmit={submit} aria-label="Neon Auth">
            <div className="text-foreground text-sm font-semibold">
                {mode === 'sign-up' ? 'Create a Neon Auth account' : mode === 'verify' ? 'Verify your email' : 'Sign in with Neon Auth'}
            </div>
            {mode !== 'verify' ? (
                <>
                    <div className="grid gap-2">
                        <Label htmlFor="neon-email">Email address</Label>
                        <Input
                            id="neon-email"
                            type="email"
                            required
                            autoComplete="email"
                            className="h-11"
                            value={email}
                            onChange={(e) => setEmail(e.target.value)}
                        />
                    </div>
                    <div className="grid gap-2">
                        <Label htmlFor="neon-password">Password</Label>
                        <PasswordInput
                            id="neon-password"
                            minLength={8}
                            autoComplete={mode === 'sign-up' ? 'new-password' : 'current-password'}
                            value={password}
                            onChange={setPassword}
                        />
                    </div>
                </>
            ) : (
                <div className="grid gap-2">
                    <Label htmlFor="neon-otp">Verification code</Label>
                    <Input
                        id="neon-otp"
                        inputMode="numeric"
                        required
                        autoComplete="one-time-code"
                        className="h-11 text-center font-mono text-lg tracking-[0.4em]"
                        value={otp}
                        onChange={(e) => setOtp(e.target.value)}
                    />
                </div>
            )}
            <InputError message={error} />
            {notice && (
                <div role="status" className="text-success text-sm">
                    {notice}
                </div>
            )}
            <Button type="submit" variant="secondary" className="h-11" disabled={processing}>
                {processing && <LoaderCircle className="h-4 w-4 animate-spin" />}
                {mode === 'sign-up' ? 'Create account' : mode === 'verify' ? 'Verify email' : 'Sign in with Neon Auth'}
            </Button>
            <div className="text-muted-foreground text-center text-sm">
                {mode === 'sign-in' ? (
                    <button type="button" className="text-link font-medium underline-offset-4 hover:underline" onClick={() => setMode('sign-up')}>
                        New to Neon Auth? Create an account
                    </button>
                ) : (
                    <button type="button" className="text-link font-medium underline-offset-4 hover:underline" onClick={() => setMode('sign-in')}>
                        Back to sign in
                    </button>
                )}
            </div>
        </form>
    );
}
