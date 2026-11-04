import AppLogoIcon from '@/components/app-logo-icon';
import ThemeToggle from '@/components/theme-toggle';
import { Link } from '@inertiajs/react';

interface AuthLayoutProps {
    children: React.ReactNode;
    name?: string;
    title?: string;
    description?: string;
}

export default function AuthSimpleLayout({ children, title, description }: AuthLayoutProps) {
    return (
        <div className="bg-background relative flex min-h-svh flex-col items-center justify-center px-4 py-10">
            <ThemeToggle className="absolute top-4 right-4" />

            <div className="w-full max-w-sm">
                <Link href={route('home')} className="mb-6 flex items-center justify-center gap-2.5">
                    <span className="bg-primary text-primary-foreground flex size-9 items-center justify-center rounded-lg">
                        <AppLogoIcon className="size-5" />
                    </span>
                    <span className="text-foreground text-lg font-semibold tracking-tight">Parameter</span>
                </Link>

                <div className="bg-card rounded-xl border p-6 shadow-sm sm:p-8">
                    <div className="mb-6 space-y-1.5 text-center">
                        <h1 className="text-foreground text-xl font-semibold">{title}</h1>
                        {description && <p className="text-muted-foreground text-sm">{description}</p>}
                    </div>
                    {children}
                </div>
            </div>
        </div>
    );
}
