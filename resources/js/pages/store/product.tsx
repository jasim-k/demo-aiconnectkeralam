import { Head, Link, router } from '@inertiajs/react';
import { Check, ChevronLeft, Minus, Plus, RotateCcw, ShoppingBag, Truck } from 'lucide-react';
import { useState } from 'react';
import { ProductImage } from '@/components/store/product-image';
import { Button } from '@/components/ui/button';
import { cn, colorToHex, formatPrice } from '@/lib/utils';
import { add as addToCart } from '@/routes/cart';
import { index as productsIndex, show as showProduct } from '@/routes/products';
import type { Product, ProductVariant } from '@/types/store';

type ProductProps = {
    product: Product;
    storageOptions: string[];
    colorOptions: string[];
    variants: ProductVariant[];
};

/** Resolve the best sibling variant for a chosen attribute, preferring the other current attribute. */
function variantFor(
    product: Product,
    variants: ProductVariant[],
    attr: 'storage' | 'color',
    value: string,
): ProductVariant | undefined {
    const other = attr === 'storage' ? 'color' : 'storage';
    const otherValue = product[other];

    return (
        variants.find((v) => v[attr] === value && v[other] === otherValue) ??
        variants.find((v) => v[attr] === value)
    );
}

function OptionPills({
    product,
    variants,
    attr,
    options,
}: {
    product: Product;
    variants: ProductVariant[];
    attr: 'storage' | 'color';
    options: string[];
}) {
    return (
        <div className="flex flex-wrap gap-2.5">
            {options.map((option) => {
                const active = product[attr] === option;
                const target = variantFor(product, variants, attr, option);
                const disabled = !target;

                return (
                    <Link
                        key={option}
                        href={target ? showProduct(target.id) : '#'}
                        preserveScroll
                        className={cn(
                            'inline-flex items-center gap-2 rounded-xl border px-4 py-2.5 text-sm font-medium transition-all',
                            active
                                ? 'border-foreground bg-foreground/[0.04] ring-1 ring-foreground'
                                : 'border-border text-muted-foreground hover:border-foreground hover:text-foreground',
                            disabled && 'pointer-events-none opacity-40',
                        )}
                    >
                        {attr === 'color' && (
                            <span
                                className="size-4 rounded-full ring-1 ring-inset ring-black/10 dark:ring-white/15"
                                style={{ backgroundColor: colorToHex(option) }}
                            />
                        )}
                        {option}
                    </Link>
                );
            })}
        </div>
    );
}

export default function ProductDetail({ product, storageOptions, colorOptions, variants }: ProductProps) {
    const [quantity, setQuantity] = useState(1);
    const outOfStock = product.stock <= 0;

    const addToCartAction = () => {
        router.post(addToCart().url, { product_id: product.id, quantity }, { preserveScroll: true });
    };

    return (
        <>
            <Head title={product.name} />
            <div className="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
                <Button asChild variant="ghost" size="sm" className="mb-6 -ml-2 text-muted-foreground">
                    <Link href={productsIndex()}>
                        <ChevronLeft /> Back to store
                    </Link>
                </Button>

                <div className="grid gap-10 lg:grid-cols-2 lg:gap-16">
                    {/* Gallery */}
                    <div className="lg:sticky lg:top-24 lg:self-start">
                        <div className="relative aspect-square overflow-hidden rounded-[2rem] border border-border/60 bg-gradient-to-b from-neutral-50 to-neutral-100 dark:from-neutral-900/60 dark:to-neutral-900">
                            {product.is_featured && (
                                <span className="absolute left-4 top-4 z-10 rounded-full bg-foreground/90 px-3 py-1 text-xs font-semibold text-background backdrop-blur">
                                    Featured
                                </span>
                            )}
                            <ProductImage src={product.image} alt={product.name} className="size-full scale-125 object-cover" />
                        </div>
                    </div>

                    {/* Details */}
                    <div>
                        <p className="text-sm font-semibold uppercase tracking-[0.12em] text-muted-foreground">{product.series}</p>
                        <h1 className="mt-2 text-3xl font-semibold tracking-tight sm:text-4xl">{product.name}</h1>
                        <p className="mt-4 text-3xl font-semibold tracking-tight">{formatPrice(product.price)}</p>

                        <p className="mt-6 leading-relaxed text-muted-foreground">{product.description}</p>

                        {storageOptions.length > 1 && (
                            <div className="mt-8">
                                <h2 className="mb-3 text-sm font-semibold">Storage</h2>
                                <OptionPills product={product} variants={variants} attr="storage" options={storageOptions} />
                            </div>
                        )}

                        {colorOptions.length > 1 && (
                            <div className="mt-6">
                                <h2 className="mb-3 text-sm font-semibold">Colour — {product.color}</h2>
                                <OptionPills product={product} variants={variants} attr="color" options={colorOptions} />
                            </div>
                        )}

                        {/* Stock status */}
                        <div className="mt-8">
                            {outOfStock ? (
                                <span className="inline-flex items-center gap-1.5 rounded-full bg-muted px-3 py-1 text-sm font-medium text-muted-foreground">
                                    Sold out
                                </span>
                            ) : (
                                <span className="inline-flex items-center gap-1.5 text-sm font-medium text-emerald-600 dark:text-emerald-400">
                                    <Check className="size-4" /> In stock
                                    {product.stock <= 10 && (
                                        <span className="text-muted-foreground">· only {product.stock} left</span>
                                    )}
                                </span>
                            )}
                        </div>

                        {/* Quantity + add to cart */}
                        <div className="mt-6 flex flex-wrap items-center gap-4">
                            <div className="flex items-center rounded-full border border-border">
                                <Button
                                    variant="ghost"
                                    size="icon"
                                    className="rounded-full"
                                    onClick={() => setQuantity((q) => Math.max(1, q - 1))}
                                    disabled={quantity <= 1 || outOfStock}
                                    aria-label="Decrease quantity"
                                >
                                    <Minus />
                                </Button>
                                <span className="w-10 text-center font-medium tabular-nums">{quantity}</span>
                                <Button
                                    variant="ghost"
                                    size="icon"
                                    className="rounded-full"
                                    onClick={() => setQuantity((q) => Math.min(product.stock, q + 1))}
                                    disabled={quantity >= product.stock || outOfStock}
                                    aria-label="Increase quantity"
                                >
                                    <Plus />
                                </Button>
                            </div>

                            <Button size="lg" onClick={addToCartAction} disabled={outOfStock} className="flex-1 rounded-full sm:flex-none sm:px-10">
                                <ShoppingBag /> Add to cart
                            </Button>
                        </div>

                        {/* Reassurance */}
                        <div className="mt-8 grid grid-cols-2 gap-4 rounded-2xl border border-border/60 bg-muted/30 p-5 text-sm">
                            <div className="flex items-center gap-2.5">
                                <Truck className="size-5 shrink-0 text-muted-foreground" />
                                <span className="font-medium">Free delivery</span>
                            </div>
                            <div className="flex items-center gap-2.5">
                                <RotateCcw className="size-5 shrink-0 text-muted-foreground" />
                                <span className="font-medium">14-day returns</span>
                            </div>
                        </div>

                        <dl className="mt-8 grid grid-cols-2 gap-y-4 border-t border-border pt-6 text-sm">
                            <div>
                                <dt className="text-muted-foreground">Model</dt>
                                <dd className="font-medium">{product.model}</dd>
                            </div>
                            <div>
                                <dt className="text-muted-foreground">SKU</dt>
                                <dd className="font-medium">{product.sku}</dd>
                            </div>
                            {product.storage && (
                                <div>
                                    <dt className="text-muted-foreground">Storage</dt>
                                    <dd className="font-medium">{product.storage}</dd>
                                </div>
                            )}
                            {product.color && (
                                <div>
                                    <dt className="text-muted-foreground">Colour</dt>
                                    <dd className="font-medium">{product.color}</dd>
                                </div>
                            )}
                        </dl>
                    </div>
                </div>
            </div>
        </>
    );
}
