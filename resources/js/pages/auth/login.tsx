import { Head, router, useForm } from '@inertiajs/react';
import { LoaderCircle } from 'lucide-react';
import { FormEventHandler, useState } from 'react';

import InputError from '@/components/input-error';
import TextLink from '@/components/text-link';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AuthLayout from '@/layouts/auth-layout';
import { neonAuth } from '@/lib/neon';

interface LoginForm {
    email: string;
    password: string;
    remember: boolean;
}

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
        <AuthLayout title="Log in to your account" description="Enter your email and password below to log in">
            <Head title="Log in" />

            <form className="flex flex-col gap-6" onSubmit={submit}>
                <div className="grid gap-6">
                    <div className="grid gap-2">
                        <Label htmlFor="email">Email address</Label>
                        <Input
                            id="email"
                            type="email"
                            required
                            autoFocus
                            tabIndex={1}
                            autoComplete="email"
                            value={data.email}
                            onChange={(e) => setData('email', e.target.value)}
                            placeholder="email@example.com"
                        />
                        <InputError message={errors.email} />
                    </div>

                    <div className="grid gap-2">
                        <div className="flex items-center">
                            <Label htmlFor="password">Password</Label>
                            {canResetPassword && (
                                <TextLink href={route('password.request')} className="ml-auto text-sm" tabIndex={5}>
                                    Forgot password?
                                </TextLink>
                            )}
                        </div>
                        <Input
                            id="password"
                            type="password"
                            required
                            tabIndex={2}
                            autoComplete="current-password"
                            value={data.password}
                            onChange={(e) => setData('password', e.target.value)}
                            placeholder="Password"
                        />
                        <InputError message={errors.password} />
                    </div>

                    <div className="flex items-center space-x-3">
                        <Checkbox id="remember" name="remember" tabIndex={3} />
                        <Label htmlFor="remember">Remember me</Label>
                    </div>

                    <Button type="submit" className="mt-4 w-full" tabIndex={4} disabled={processing}>
                        {processing && <LoaderCircle className="h-4 w-4 animate-spin" />}
                        Log in
                    </Button>
                </div>
            </form>

            <NeonAuthSignIn />

            {status && <div className="mb-4 text-center text-sm font-medium text-green-600">{status}</div>}
        </AuthLayout>
    );
}

type NeonMode = 'closed' | 'sign-in' | 'sign-up' | 'verify';

/**
 * Sign in through Neon Auth, then hand its JWT to Laravel, which starts a session
 * for the admin with the same (verified) email.
 */
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
            <Button type="button" variant="outline" className="w-full" onClick={() => setMode('sign-in')}>
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
        <form className="grid gap-4 border-t pt-6" onSubmit={submit} aria-label="Neon Auth">
            <div className="text-sm font-medium">
                {mode === 'sign-up' ? 'Create a Neon Auth account' : mode === 'verify' ? 'Verify your email' : 'Sign in with Neon Auth'}
            </div>
            {mode !== 'verify' ? (
                <>
                    <div className="grid gap-2">
                        <Label htmlFor="neon-email">Email address</Label>
                        <Input id="neon-email" type="email" required autoComplete="email" value={email} onChange={(e) => setEmail(e.target.value)} />
                    </div>
                    <div className="grid gap-2">
                        <Label htmlFor="neon-password">Password</Label>
                        <Input
                            id="neon-password"
                            type="password"
                            required
                            minLength={8}
                            autoComplete={mode === 'sign-up' ? 'new-password' : 'current-password'}
                            value={password}
                            onChange={(e) => setPassword(e.target.value)}
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
                        value={otp}
                        onChange={(e) => setOtp(e.target.value)}
                    />
                </div>
            )}
            <InputError message={error} />
            {notice && <div className="text-sm text-green-600">{notice}</div>}
            <Button type="submit" variant="secondary" disabled={processing}>
                {processing && <LoaderCircle className="h-4 w-4 animate-spin" />}
                {mode === 'sign-up' ? 'Create account' : mode === 'verify' ? 'Verify email' : 'Sign in with Neon Auth'}
            </Button>
            <div className="text-muted-foreground text-center text-sm">
                {mode === 'sign-in' ? (
                    <button type="button" className="underline" onClick={() => setMode('sign-up')}>
                        New to Neon Auth? Create an account
                    </button>
                ) : (
                    <button type="button" className="underline" onClick={() => setMode('sign-in')}>
                        Back to sign in
                    </button>
                )}
            </div>
        </form>
    );
}
