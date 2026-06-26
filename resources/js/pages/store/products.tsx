import { Head, Link, router } from '@inertiajs/react';
import { SlidersHorizontal, X } from 'lucide-react';
import { ProductCard } from '@/components/store/product-card';
import { Button } from '@/components/ui/button';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { cn, colorToHex } from '@/lib/utils';
import { index as productsIndex } from '@/routes/products';
import type { CatalogFilters, FilterOptions, Paginated, Product } from '@/types/store';

type ProductsProps = {
    products: Paginated<Product>;
    filterOptions: FilterOptions;
    filters: CatalogFilters;
};

const SORT_LABELS: Record<string, string> = {
    newest: 'Newest',
    price_asc: 'Price: Low to High',
    price_desc: 'Price: High to Low',
    name: 'Name',
};

function FilterGroup({
    title,
    options,
    activeValue,
    withSwatch = false,
    onToggle,
}: {
    title: string;
    options: string[];
    activeValue: string | null;
    withSwatch?: boolean;
    onToggle: (value: string) => void;
}) {
    return (
        <div>
            <h3 className="mb-3 text-sm font-semibold">{title}</h3>
            <div className="flex flex-wrap gap-2">
                {options.map((option) => {
                    const active = activeValue === option;

                    return (
                        <button
                            key={option}
                            type="button"
                            onClick={() => onToggle(option)}
                            className={cn(
                                'inline-flex items-center gap-1.5 rounded-full border px-3 py-1.5 text-sm transition-all',
                                active
                                    ? 'border-foreground bg-foreground text-background'
                                    : 'border-border text-muted-foreground hover:border-foreground hover:text-foreground',
                            )}
                        >
                            {withSwatch && (
                                <span
                                    className="size-3 rounded-full ring-1 ring-inset ring-black/10 dark:ring-white/15"
                                    style={{ backgroundColor: colorToHex(option) }}
                                />
                            )}
                            {option}
                        </button>
                    );
                })}
            </div>
        </div>
    );
}

export default function Products({ products, filterOptions, filters }: ProductsProps) {
    const navigate = (next: Partial<CatalogFilters>) => {
        const query: Record<string, string | number> = {};
        const merged = { ...filters, ...next };

        (Object.keys(merged) as (keyof CatalogFilters)[]).forEach((key) => {
            const value = merged[key];

            if (value !== null && value !== '' && value !== undefined) {
                query[key] = value;
            }
        });

        router.get(productsIndex().url, query, { preserveScroll: true, preserveState: true });
    };

    const toggle = (key: keyof CatalogFilters, value: string) => {
        navigate({ [key]: filters[key] === value ? null : value } as Partial<CatalogFilters>);
    };

    const hasActiveFilters =
        !!filters.search || !!filters.series || !!filters.storage || !!filters.color;

    return (
        <>
            <Head title="Store" />
            <div className="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
                <div className="mb-10 flex flex-col gap-2">
                    <h1 className="text-4xl font-semibold tracking-tight">Store</h1>
                    <p className="text-muted-foreground">
                        {products.total} product{products.total === 1 ? '' : 's'}
                        {filters.search && <> matching “{filters.search}”</>}
                    </p>
                </div>

                <div className="grid gap-10 lg:grid-cols-[260px_1fr]">
                    {/* Filters */}
                    <aside className="space-y-7 lg:sticky lg:top-24 lg:self-start">
                        <div className="flex items-center justify-between">
                            <span className="flex items-center gap-2 text-sm font-semibold">
                                <SlidersHorizontal className="size-4" /> Filters
                            </span>
                            {hasActiveFilters && (
                                <button
                                    type="button"
                                    onClick={() => router.get(productsIndex().url)}
                                    className="flex items-center gap-1 text-xs text-muted-foreground transition-colors hover:text-foreground"
                                >
                                    <X className="size-3" /> Clear all
                                </button>
                            )}
                        </div>
                        <FilterGroup
                            title="Series"
                            options={filterOptions.series}
                            activeValue={filters.series}
                            onToggle={(value) => toggle('series', value)}
                        />
                        {filterOptions.storage.length > 0 && (
                            <FilterGroup
                                title="Storage"
                                options={filterOptions.storage}
                                activeValue={filters.storage}
                                onToggle={(value) => toggle('storage', value)}
                            />
                        )}
                        <FilterGroup
                            title="Colour"
                            options={filterOptions.color}
                            activeValue={filters.color}
                            withSwatch
                            onToggle={(value) => toggle('color', value)}
                        />
                    </aside>

                    {/* Results */}
                    <div>
                        <div className="mb-6 flex items-center justify-between gap-4">
                            <p className="text-sm text-muted-foreground">
                                Showing {products.from ?? 0}–{products.to ?? 0} of {products.total}
                            </p>
                            <Select value={filters.sort ?? 'newest'} onValueChange={(value) => navigate({ sort: value })}>
                                <SelectTrigger className="w-52 rounded-full">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    {Object.entries(SORT_LABELS).map(([value, label]) => (
                                        <SelectItem key={value} value={value}>
                                            {label}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        </div>

                        {products.data.length === 0 ? (
                            <div className="rounded-3xl border border-dashed border-border py-24 text-center text-muted-foreground">
                                No products match your filters.
                            </div>
                        ) : (
                            <div className="grid grid-cols-2 gap-4 sm:grid-cols-3 sm:gap-5">
                                {products.data.map((product) => (
                                    <ProductCard key={product.id} product={product} />
                                ))}
                            </div>
                        )}

                        {products.last_page > 1 && (
                            <div className="mt-12 flex flex-wrap justify-center gap-1.5">
                                {products.links.map((link, index) => (
                                    <Button
                                        key={index}
                                        asChild={!!link.url}
                                        variant={link.active ? 'default' : 'outline'}
                                        size="sm"
                                        className="rounded-full"
                                        disabled={!link.url}
                                    >
                                        {link.url ? (
                                            <Link href={link.url} preserveScroll dangerouslySetInnerHTML={{ __html: link.label }} />
                                        ) : (
                                            <span dangerouslySetInnerHTML={{ __html: link.label }} />
                                        )}
                                    </Button>
                                ))}
                            </div>
                        )}
                    </div>
                </div>
            </div>
        </>
    );
}
