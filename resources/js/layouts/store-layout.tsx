import { Link, router, usePage } from '@inertiajs/react';
import { Search, ShoppingCart, User } from 'lucide-react';
import { useState } from 'react';
import type { FormEvent, ReactNode } from 'react';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import { DropdownMenu, DropdownMenuContent, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { Input } from '@/components/ui/input';
import { UserMenuContent } from '@/components/user-menu-content';
import { useInitials } from '@/hooks/use-initials';
import { cn } from '@/lib/utils';
import { home, login } from '@/routes';
import { index as cartIndex } from '@/routes/cart';
import { index as ordersIndex, track as trackOrder } from '@/routes/orders';
import { index as productsIndex } from '@/routes/products';

function CartButton() {
    const { cart } = usePage().props;
    const count = cart?.count ?? 0;

    return (
        <Link
            href={cartIndex()}
            aria-label="View cart"
            className="relative flex size-10 items-center justify-center rounded-full text-foreground transition-colors hover:bg-muted"
        >
            <ShoppingCart className="size-5" />
            {count > 0 && (
                <span className="absolute -right-0.5 -top-0.5 flex size-5 items-center justify-center rounded-full bg-foreground text-[11px] font-semibold text-background">
                    {count > 99 ? '99+' : count}
                </span>
            )}
        </Link>
    );
}

function AccountMenu() {
    const { auth } = usePage().props;
    const getInitials = useInitials();
    const user = auth?.user;

    if (!user) {
        return (
            <Button asChild variant="ghost" size="sm" className="rounded-full">
                <Link href={login()}>
                    <User className="size-4" />
                    <span className="hidden sm:inline">Sign in</span>
                </Link>
            </Button>
        );
    }

    return (
        <DropdownMenu>
            <DropdownMenuTrigger asChild>
                <button
                    type="button"
                    aria-label="Account menu"
                    className="flex items-center rounded-full outline-none ring-offset-background focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                >
                    <Avatar className="size-9 border border-border">
                        <AvatarImage src={user.avatar} alt={user.name} />
                        <AvatarFallback className="bg-muted text-sm font-medium text-foreground">
                            {getInitials(user.name)}
                        </AvatarFallback>
                    </Avatar>
                </button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end" className="w-60">
                <UserMenuContent user={user} />
            </DropdownMenuContent>
        </DropdownMenu>
    );
}

function NavLink({ href, label }: { href: string; label: string }) {
    const { url } = usePage();
    const active = href !== '/' && url.startsWith(href);

    return (
        <Link
            href={href}
            className={cn(
                'group relative py-1 text-sm font-medium transition-colors',
                active ? 'text-foreground' : 'text-muted-foreground hover:text-foreground',
            )}
        >
            {label}
            <span
                className={cn(
                    'absolute -bottom-0.5 left-0 h-0.5 rounded-full bg-foreground transition-all duration-300',
                    active ? 'w-full' : 'w-0 group-hover:w-full',
                )}
            />
        </Link>
    );
}

function StoreHeader() {
    const [search, setSearch] = useState('');

    const submitSearch = (event: FormEvent) => {
        event.preventDefault();
        router.get(productsIndex().url, search.trim() ? { search: search.trim() } : {}, {
            preserveState: true,
        });
    };

    return (
        <header className="sticky top-0 z-40 border-b border-border/60 bg-background/70 backdrop-blur-xl">
            <div className="mx-auto flex h-16 max-w-7xl items-center gap-6 px-4 sm:px-6 lg:px-8">
                <Link href={home()} className="flex shrink-0 items-center" aria-label="AI Connect Kerala — home">
                    <img src="/brand/ai-con-logo.svg" alt="AI Connect Kerala" className="h-7 w-auto" />
                </Link>

                <nav className="hidden items-center gap-7 md:flex">
                    <NavLink href={productsIndex().url} label="Store" />
                    <NavLink href={productsIndex({ query: { series: 'iPhone 17' } }).url} label="iPhone 17" />
                    <NavLink href={productsIndex({ query: { series: 'iPhone 16' } }).url} label="iPhone 16" />
                    <NavLink href={productsIndex({ query: { series: 'Accessories' } }).url} label="Accessories" />
                </nav>

                <form onSubmit={submitSearch} className="relative ml-auto hidden w-full max-w-[220px] sm:block">
                    <Search className="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-muted-foreground" />
                    <Input
                        type="search"
                        value={search}
                        onChange={(event) => setSearch(event.target.value)}
                        placeholder="Search products"
                        className="h-10 rounded-full border-transparent bg-muted pl-9 focus-visible:border-ring focus-visible:bg-background"
                    />
                </form>

                <div className="ml-auto flex items-center gap-1 sm:ml-2">
                    <AccountMenu />
                    <CartButton />
                </div>
            </div>
        </header>
    );
}

function StoreFooter() {
    const series = (s: string) => productsIndex({ query: { series: s } }).url;
    const links = [
        {
            heading: 'Shop',
            items: [
                { label: 'iPhone 17', href: series('iPhone 17') },
                { label: 'iPhone 16', href: series('iPhone 16') },
                { label: 'iPhone 15', href: series('iPhone 15') },
                { label: 'Accessories', href: series('Accessories') },
            ],
        },
        {
            heading: 'Account',
            items: [
                { label: 'My orders', href: ordersIndex().url },
                { label: 'Track order', href: trackOrder().url },
                { label: 'Browse store', href: productsIndex().url },
            ],
        },
    ];

    return (
        <footer className="mt-24 border-t border-border/60 bg-muted/30">
            <div className="mx-auto max-w-7xl px-4 py-14 sm:px-6 lg:px-8">
                <div className="grid gap-10 md:grid-cols-[1.5fr_1fr_1fr]">
                    <div className="max-w-sm">
                        <Link href={home()} aria-label="AI Connect Kerala — home">
                            <img src="/brand/ai-con-logo.svg" alt="AI Connect Kerala" className="h-7 w-auto" />
                        </Link>
                        <p className="mt-4 text-sm leading-relaxed text-muted-foreground">
                            A demo storefront for AI Connect Kerala, built with Laravel, Inertia & MCP.
                        </p>
                    </div>
                    {links.map((group) => (
                        <div key={group.heading}>
                            <h3 className="text-xs font-semibold uppercase tracking-wide text-muted-foreground">
                                {group.heading}
                            </h3>
                            <ul className="mt-4 space-y-3">
                                {group.items.map((item) => (
                                    <li key={item.label}>
                                        <Link
                                            href={item.href}
                                            className="text-sm text-foreground/80 transition-colors hover:text-foreground"
                                        >
                                            {item.label}
                                        </Link>
                                    </li>
                                ))}
                            </ul>
                        </div>
                    ))}
                </div>
                <div className="mt-12 flex flex-col gap-1 border-t border-border/60 pt-6 text-xs text-muted-foreground sm:flex-row sm:items-center sm:justify-between">
                    <span>© {new Date().getFullYear()} AI Connect Kerala · Demo store for the community.</span>
                    <span>Not affiliated with Apple Inc. Product names &amp; imagery © Apple.</span>
                </div>
            </div>
        </footer>
    );
}

export default function StoreLayout({ children }: { children: ReactNode }) {
    return (
        <div className="flex min-h-screen flex-col bg-background text-foreground">
            <StoreHeader />
            <main className="flex-1">{children}</main>
            <StoreFooter />
        </div>
    );
}
