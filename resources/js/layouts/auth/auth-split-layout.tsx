import AppLogoIcon from '@/components/app-logo-icon';
import ThemeToggle from '@/components/theme-toggle';
import { Link } from '@inertiajs/react';
import { MapPinned, RadioTower, WifiOff } from 'lucide-react';

interface AuthLayoutProps {
    children: React.ReactNode;
    title?: string;
    description?: string;
}

const highlights = [
    { icon: MapPinned, title: 'Route-based fares', text: 'Charged on the shortest legal road route, not GPS drift.' },
    { icon: RadioTower, title: 'Live trip ledger', text: 'Every completed trip lands in the ledger as it syncs.' },
    { icon: WifiOff, title: 'Offline-ready meter', text: 'Drivers keep metering even without a signal.' },
];

function Wordmark({ className = '' }: { className?: string }) {
    return (
        <span className={`text-lg font-semibold tracking-tight ${className}`}>
            Para<span className="opacity-80">Meter</span>
        </span>
    );
}

export default function AuthSplitLayout({ children, title, description }: AuthLayoutProps) {
    return (
        <div className="bg-background grid min-h-svh lg:grid-cols-[minmax(0,5fr)_minmax(0,6fr)]">
            {/* Brand panel */}
            <aside className="bg-brand text-brand-foreground relative hidden overflow-hidden lg:flex lg:flex-col lg:justify-between lg:p-12">
                <div
                    aria-hidden="true"
                    className="pointer-events-none absolute inset-0 opacity-60"
                    style={{
                        backgroundImage:
                            'radial-gradient(circle at 15% 10%, rgba(255,255,255,0.14), transparent 40%), radial-gradient(circle at 90% 85%, rgba(255,255,255,0.10), transparent 45%)',
                    }}
                />
                <svg aria-hidden="true" className="pointer-events-none absolute -right-24 -bottom-24 size-[28rem] text-white/10" viewBox="0 0 40 40">
                    <path d="M7 29a14 14 0 1 1 26 0" fill="none" stroke="currentColor" strokeWidth="0.6" strokeLinecap="round" />
                    <path d="M4 32a17 17 0 1 1 32 0" fill="none" stroke="currentColor" strokeWidth="0.3" strokeDasharray="0.6 1.2" />
                </svg>

                <Link
                    href={route('home')}
                    className="relative flex w-fit items-center gap-3 rounded-md focus-visible:ring-2 focus-visible:ring-white focus-visible:outline-hidden"
                >
                    <span className="flex size-10 items-center justify-center rounded-xl bg-white/15 ring-1 ring-white/25">
                        <AppLogoIcon className="size-6 text-white" />
                    </span>
                    <Wordmark />
                </Link>

                <div className="relative max-w-md">
                    <h2 className="text-3xl leading-tight font-semibold tracking-tight text-balance xl:text-4xl">
                        Fair fares for every ride in Tagbilaran City.
                    </h2>
                    <p className="mt-4 text-base text-[#c7d8f2]">The admin console for Parameter, the tricycle and bao-bao fare meter.</p>

                    <ul className="mt-10 space-y-5">
                        {highlights.map(({ icon: Icon, title, text }) => (
                            <li key={title} className="flex gap-4">
                                <span className="flex size-10 shrink-0 items-center justify-center rounded-lg bg-white/10 ring-1 ring-white/20">
                                    <Icon className="size-5" />
                                </span>
                                <div>
                                    <p className="font-medium">{title}</p>
                                    <p className="text-sm text-[#c7d8f2]">{text}</p>
                                </div>
                            </li>
                        ))}
                    </ul>
                </div>

                <p className="relative text-sm text-[#c7d8f2]">© {new Date().getFullYear()} Parameter · Tagbilaran City, Bohol</p>
            </aside>

            {/* Form side */}
            <main className="relative flex flex-col px-4 py-6 sm:px-8">
                <div className="flex items-center justify-between lg:justify-end">
                    <Link href={route('home')} className="text-foreground flex items-center gap-2.5 lg:hidden">
                        <span className="bg-primary text-primary-foreground flex size-9 items-center justify-center rounded-lg">
                            <AppLogoIcon className="size-5" />
                        </span>
                        <Wordmark />
                    </Link>
                    <ThemeToggle />
                </div>

                <div className="flex flex-1 items-center justify-center py-10">
                    <div className="w-full max-w-[400px]">
                        <div className="mb-8 space-y-2">
                            <h1 className="text-foreground text-2xl font-semibold tracking-tight">{title}</h1>
                            {description && <p className="text-muted-foreground text-sm text-pretty">{description}</p>}
                        </div>
                        {children}
                    </div>
                </div>
            </main>
        </div>
    );
}
