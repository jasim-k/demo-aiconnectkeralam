import { Head, Link } from '@inertiajs/react';
import { ArrowRight, Headphones, RotateCcw, ShieldCheck, Truck } from 'lucide-react';
import { ProductCard } from '@/components/store/product-card';
import { ProductImage } from '@/components/store/product-image';
import { Button } from '@/components/ui/button';
import { index as productsIndex } from '@/routes/products';
import type { Product } from '@/types/store';

type HomeProps = {
    hero: Product | null;
    featured: Product[];
    latest: Product[];
};

const SERIES_TILES = [
    { title: 'iPhone 17', blurb: 'The latest generation', image: '/images/products/iphone-17-pro-cosmic-orange.jpg', series: 'iPhone 17' },
    { title: 'iPhone 16', blurb: 'Built for Apple Intelligence', image: '/images/products/iphone-16-pro-desert-titanium.jpg', series: 'iPhone 16' },
    { title: 'iPhone 15', blurb: 'Titanium. Still brilliant', image: '/images/products/iphone-15-pro-natural-titanium.jpg', series: 'iPhone 15' },
    { title: 'Accessories', blurb: 'AirPods, Watch & more', image: '/images/products/airpods-pro.jpg', series: 'Accessories' },
];

const TRUST = [
    { icon: Truck, label: 'Free delivery', sub: 'On every order' },
    { icon: RotateCcw, label: '14-day returns', sub: 'No questions asked' },
    { icon: ShieldCheck, label: '1-year warranty', sub: 'Apple-backed' },
    { icon: Headphones, label: 'Expert support', sub: '7 days a week' },
];

function SectionHeading({ title, subtitle }: { title: string; subtitle?: string }) {
    return (
        <div className="mb-8 flex items-end justify-between gap-4">
            <div>
                <h2 className="text-2xl font-semibold tracking-tight sm:text-3xl">{title}</h2>
                {subtitle && <p className="mt-1.5 text-muted-foreground">{subtitle}</p>}
            </div>
            <Button asChild variant="ghost" className="hidden shrink-0 sm:inline-flex">
                <Link href={productsIndex()}>
                    View all <ArrowRight />
                </Link>
            </Button>
        </div>
    );
}

export default function Home({ hero, featured, latest }: HomeProps) {
    const heroProduct = hero ?? featured[0];

    return (
        <>
            <Head title="Shop the latest Apple" />

            {/* Hero */}
            <section className="relative overflow-hidden border-b border-border/60 bg-gradient-to-b from-neutral-100 via-background to-background dark:from-neutral-900/60">
                <div className="pointer-events-none absolute -right-32 -top-24 size-[480px] rounded-full bg-blue-400/10 blur-3xl dark:bg-blue-500/10" />
                <div className="mx-auto grid max-w-7xl items-center gap-8 px-4 py-16 sm:px-6 lg:grid-cols-2 lg:py-24 lg:px-8">
                    <div className="text-center lg:text-left">
                        <span className="inline-flex items-center rounded-full border border-border/70 bg-background/60 px-3 py-1 text-xs font-semibold uppercase tracking-[0.12em] text-muted-foreground backdrop-blur">
                            New · iPhone 17 Pro Max
                        </span>
                        <h1 className="mt-5 text-4xl font-semibold leading-[1.05] tracking-tight sm:text-6xl">
                            The latest Apple,
                            <br />
                            delivered to you.
                        </h1>
                        <p className="mx-auto mt-5 max-w-md text-lg text-muted-foreground lg:mx-0">
                            Shop the newest iPhone line-up and accessories. Soft on design, serious on power.
                        </p>
                        <div className="mt-8 flex flex-wrap items-center justify-center gap-3 lg:justify-start">
                            <Button asChild size="lg" className="rounded-full px-7">
                                <Link href={productsIndex()}>Shop the store</Link>
                            </Button>
                            <Button asChild size="lg" variant="outline" className="rounded-full px-7">
                                <Link href={productsIndex({ query: { series: 'iPhone 17' } })}>Explore iPhone 17</Link>
                            </Button>
                        </div>
                    </div>

                    {heroProduct && (
                        <Link href={`/products/${heroProduct.id}`} className="group relative mx-auto w-full max-w-md">
                            <div className="absolute inset-0 -z-10 rounded-[2.5rem] bg-gradient-to-tr from-neutral-200/70 to-transparent blur-2xl dark:from-neutral-800/50" />
                            <div className="aspect-square overflow-hidden rounded-[2.5rem] border border-border/60 bg-white/60 backdrop-blur dark:bg-neutral-900/40">
                                <ProductImage
                                    src={heroProduct.image}
                                    alt={heroProduct.name}
                                    className="size-full scale-[1.15] object-cover transition-transform duration-700 ease-out group-hover:scale-125"
                                />
                            </div>
                        </Link>
                    )}
                </div>
            </section>

            {/* Trust badges */}
            <section className="border-b border-border/60">
                <div className="mx-auto grid max-w-7xl grid-cols-2 gap-px overflow-hidden px-4 sm:px-6 lg:grid-cols-4 lg:px-8">
                    {TRUST.map(({ icon: Icon, label, sub }) => (
                        <div key={label} className="flex items-center gap-3 py-6">
                            <span className="flex size-10 shrink-0 items-center justify-center rounded-full bg-muted text-foreground">
                                <Icon className="size-5" />
                            </span>
                            <div>
                                <p className="text-sm font-semibold leading-tight">{label}</p>
                                <p className="text-xs text-muted-foreground">{sub}</p>
                            </div>
                        </div>
                    ))}
                </div>
            </section>

            <div className="mx-auto max-w-7xl space-y-20 px-4 py-16 sm:px-6 lg:px-8">
                {/* Shop by series */}
                <section>
                    <SectionHeading title="Shop by series" subtitle="Find the line-up that fits you." />
                    <div className="grid grid-cols-2 gap-4 lg:grid-cols-4">
                        {SERIES_TILES.map((tile) => (
                            <Link
                                key={tile.title}
                                href={productsIndex({ query: { series: tile.series } })}
                                className="group relative flex flex-col overflow-hidden rounded-3xl border border-border/70 bg-card transition-all duration-300 hover:-translate-y-1 hover:shadow-lg"
                            >
                                <div className="aspect-[4/5] overflow-hidden bg-neutral-50 dark:bg-neutral-900/60">
                                    <ProductImage
                                        src={tile.image}
                                        alt={tile.title}
                                        className="size-full scale-[1.3] object-cover transition-transform duration-500 group-hover:scale-[1.45]"
                                    />
                                </div>
                                <div className="p-5">
                                    <h3 className="font-semibold tracking-tight">{tile.title}</h3>
                                    <p className="mt-0.5 text-sm text-muted-foreground">{tile.blurb}</p>
                                    <span className="mt-3 inline-flex items-center gap-1 text-sm font-medium text-foreground">
                                        Shop now
                                        <ArrowRight className="size-4 transition-transform group-hover:translate-x-1" />
                                    </span>
                                </div>
                            </Link>
                        ))}
                    </div>
                </section>

                {featured.length > 0 && (
                    <section>
                        <SectionHeading title="Featured" subtitle="Hand-picked highlights from the line-up." />
                        <div className="grid grid-cols-2 gap-4 md:grid-cols-3 lg:grid-cols-4">
                            {featured.map((product) => (
                                <ProductCard key={product.id} product={product} />
                            ))}
                        </div>
                    </section>
                )}

                <section>
                    <SectionHeading title="Latest arrivals" subtitle="The freshest additions to the store." />
                    <div className="grid grid-cols-2 gap-4 md:grid-cols-3 lg:grid-cols-4">
                        {latest.map((product) => (
                            <ProductCard key={product.id} product={product} />
                        ))}
                    </div>
                </section>
            </div>
        </>
    );
}
