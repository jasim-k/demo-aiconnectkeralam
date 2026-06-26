import { Link } from '@inertiajs/react';
import type { ReactNode } from 'react';
import { home } from '@/routes';

export default function AuthLayout({
    title = '',
    description = '',
    children,
}: {
    title?: string;
    description?: string;
    children: ReactNode;
}) {
    return (
        <div className="flex min-h-svh flex-col items-center justify-center bg-muted/40 p-6">
            <div className="w-full max-w-sm">
                <div className="mb-8 flex justify-center">
                    <Link href={home()} aria-label="AI Connect Kerala — home">
                        <img src="/brand/ai-con-logo.svg" alt="AI Connect Kerala" className="h-9 w-auto" />
                    </Link>
                </div>

                <div className="rounded-2xl border border-border bg-card p-8 shadow-sm">
                    <div className="mb-6 space-y-1.5 text-center">
                        <h1 className="text-xl font-semibold tracking-tight">{title}</h1>
                        {description && <p className="text-sm text-muted-foreground">{description}</p>}
                    </div>

                    {children}
                </div>
            </div>
        </div>
    );
}
