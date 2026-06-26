import { Link, router } from '@inertiajs/react';
import { Check, Plus } from 'lucide-react';
import { useState } from 'react';
import { ProductImage } from '@/components/store/product-image';
import { cn, colorToHex, formatPrice } from '@/lib/utils';
import { add as addToCart } from '@/routes/cart';
import { show as showProduct } from '@/routes/products';
import type { Product } from '@/types/store';

export function ProductCard({ product }: { product: Product }) {
    const outOfStock = product.stock <= 0;
    const [added, setAdded] = useState(false);

    const handleAdd = () => {
        router.post(
            addToCart().url,
            { product_id: product.id, quantity: 1 },
            {
                preserveScroll: true,
                onSuccess: () => {
                    setAdded(true);
                    window.setTimeout(() => setAdded(false), 1500);
                },
            },
        );
    };

    return (
        <div className="group relative flex flex-col overflow-hidden rounded-3xl border border-border/70 bg-card transition-all duration-300 hover:-translate-y-1 hover:border-border hover:shadow-[0_20px_40px_-20px_rgba(0,0,0,0.25)]">
            <Link
                href={showProduct(product.id)}
                className="relative block aspect-square overflow-hidden bg-neutral-50 dark:bg-neutral-900/60"
            >
                <ProductImage
                    src={product.image}
                    alt={product.name}
                    className="size-full scale-[1.35] object-cover transition-transform duration-500 ease-out group-hover:scale-150"
                />
                {product.is_featured && (
                    <span className="absolute left-3 top-3 rounded-full bg-foreground/90 px-2.5 py-1 text-[11px] font-semibold text-background backdrop-blur">
                        Featured
                    </span>
                )}
                {outOfStock && (
                    <span className="absolute right-3 top-3 rounded-full bg-muted px-2.5 py-1 text-[11px] font-medium text-muted-foreground">
                        Sold out
                    </span>
                )}
            </Link>

            <div className="flex flex-1 flex-col gap-3 p-5">
                <div className="flex-1">
                    <p className="text-[11px] font-semibold uppercase tracking-[0.12em] text-muted-foreground">{product.series}</p>
                    <Link href={showProduct(product.id)} className="mt-1.5 block">
                        <h3 className="font-semibold leading-snug tracking-tight text-foreground transition-colors group-hover:text-foreground">
                            {product.name}
                        </h3>
                    </Link>
                    {product.color && (
                        <div className="mt-2 flex items-center gap-1.5">
                            <span
                                className="size-3 rounded-full ring-1 ring-inset ring-black/10 dark:ring-white/15"
                                style={{ backgroundColor: colorToHex(product.color) }}
                            />
                            <span className="text-xs text-muted-foreground">{product.color}</span>
                        </div>
                    )}
                </div>

                <div className="flex items-end justify-between gap-2">
                    <div>
                        <p className="text-[11px] text-muted-foreground">From</p>
                        <span className="text-lg font-semibold tracking-tight text-foreground">{formatPrice(product.price)}</span>
                    </div>
                    <button
                        type="button"
                        onClick={handleAdd}
                        disabled={outOfStock}
                        aria-label={`Add ${product.name} to cart`}
                        className={cn(
                            'flex size-10 items-center justify-center rounded-full transition-all duration-200 active:scale-90 disabled:cursor-not-allowed disabled:opacity-40',
                            added
                                ? 'bg-emerald-500 text-white'
                                : 'bg-primary text-primary-foreground hover:scale-105',
                        )}
                    >
                        {added ? <Check className="size-5" /> : <Plus className="size-5" />}
                    </button>
                </div>
            </div>
        </div>
    );
}
