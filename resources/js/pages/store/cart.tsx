import { Head, Link, router, usePage } from '@inertiajs/react';
import { Minus, Plus, ShoppingBag, Trash2 } from 'lucide-react';
import { ProductImage } from '@/components/store/product-image';
import { Button } from '@/components/ui/button';
import { colorToHex, formatPrice } from '@/lib/utils';
import { login } from '@/routes';
import { clear as clearCart, remove as removeFromCart, update as updateCart } from '@/routes/cart';
import { index as checkoutIndex } from '@/routes/checkout';
import { index as productsIndex } from '@/routes/products';
import type { Cart, CartLine } from '@/types/store';

function setQuantity(line: CartLine, quantity: number) {
    router.patch(updateCart().url, { product_id: line.product_id, quantity }, { preserveScroll: true });
}

function CartRow({ line }: { line: CartLine }) {
    return (
        <div className="flex gap-4 py-6">
            <Link
                href={`/products/${line.product_id}`}
                className="size-28 shrink-0 overflow-hidden rounded-2xl border border-border/60 bg-neutral-50 dark:bg-neutral-900/60"
            >
                <ProductImage src={line.image} alt={line.name} className="size-full scale-125 object-cover" />
            </Link>

            <div className="flex flex-1 flex-col">
                <div className="flex items-start justify-between gap-4">
                    <div>
                        <Link href={`/products/${line.product_id}`} className="font-semibold tracking-tight hover:underline">
                            {line.name}
                        </Link>
                        {(line.color || line.storage) && (
                            <p className="mt-1 flex items-center gap-1.5 text-sm text-muted-foreground">
                                {line.color && (
                                    <span
                                        className="size-3 rounded-full ring-1 ring-inset ring-black/10 dark:ring-white/15"
                                        style={{ backgroundColor: colorToHex(line.color) }}
                                    />
                                )}
                                {[line.color, line.storage].filter(Boolean).join(' · ')}
                            </p>
                        )}
                        <p className="mt-1 text-sm text-muted-foreground">{formatPrice(line.unit_price)} each</p>
                    </div>
                    <button
                        type="button"
                        onClick={() => router.delete(removeFromCart().url, { data: { product_id: line.product_id }, preserveScroll: true })}
                        className="text-muted-foreground transition-colors hover:text-destructive"
                        aria-label="Remove item"
                    >
                        <Trash2 className="size-4" />
                    </button>
                </div>

                <div className="mt-auto flex items-center justify-between pt-3">
                    <div className="flex items-center rounded-full border border-border">
                        <Button variant="ghost" size="icon" className="size-9 rounded-full" onClick={() => setQuantity(line, line.quantity - 1)} aria-label="Decrease quantity">
                            <Minus />
                        </Button>
                        <span className="w-9 text-center text-sm font-medium tabular-nums">{line.quantity}</span>
                        <Button
                            variant="ghost"
                            size="icon"
                            className="size-9 rounded-full"
                            onClick={() => setQuantity(line, line.quantity + 1)}
                            disabled={line.quantity >= line.stock}
                            aria-label="Increase quantity"
                        >
                            <Plus />
                        </Button>
                    </div>
                    <span className="font-semibold tracking-tight">{formatPrice(line.subtotal)}</span>
                </div>
            </div>
        </div>
    );
}

export default function CartPage({ cart }: { cart: Cart }) {
    const { auth } = usePage().props;

    if (cart.items.length === 0) {
        return (
            <>
                <Head title="Cart" />
                <div className="mx-auto flex max-w-2xl flex-col items-center px-4 py-28 text-center">
                    <div className="flex size-20 items-center justify-center rounded-full bg-muted">
                        <ShoppingBag className="size-8 text-muted-foreground" />
                    </div>
                    <h1 className="mt-6 text-2xl font-semibold tracking-tight">Your cart is empty</h1>
                    <p className="mt-2 text-muted-foreground">Looks like you haven’t added anything yet.</p>
                    <Button asChild size="lg" className="mt-6 rounded-full px-8">
                        <Link href={productsIndex()}>Continue shopping</Link>
                    </Button>
                </div>
            </>
        );
    }

    return (
        <>
            <Head title="Cart" />
            <div className="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
                <div className="mb-8 flex items-center justify-between">
                    <h1 className="text-4xl font-semibold tracking-tight">Cart</h1>
                    <button
                        type="button"
                        onClick={() => router.delete(clearCart().url, { preserveScroll: true })}
                        className="text-sm text-muted-foreground transition-colors hover:text-destructive"
                    >
                        Clear cart
                    </button>
                </div>

                <div className="grid gap-10 lg:grid-cols-[1fr_380px]">
                    <div className="divide-y divide-border/70">
                        {cart.items.map((line) => (
                            <CartRow key={line.id} line={line} />
                        ))}
                    </div>

                    {/* Summary */}
                    <div className="h-fit rounded-3xl border border-border/70 bg-card p-6 lg:sticky lg:top-24">
                        <h2 className="text-lg font-semibold tracking-tight">Order summary</h2>
                        <dl className="mt-5 space-y-3 text-sm">
                            <div className="flex justify-between">
                                <dt className="text-muted-foreground">Items ({cart.count})</dt>
                                <dd>{formatPrice(cart.total)}</dd>
                            </div>
                            <div className="flex justify-between">
                                <dt className="text-muted-foreground">Shipping</dt>
                                <dd className="text-emerald-600 dark:text-emerald-400">Free</dd>
                            </div>
                        </dl>
                        <div className="mt-5 flex justify-between border-t border-border pt-5 text-base font-semibold">
                            <span>Total</span>
                            <span className="tracking-tight">{formatPrice(cart.total)}</span>
                        </div>
                        {auth.user ? (
                            <Button asChild size="lg" className="mt-6 w-full rounded-full">
                                <Link href={checkoutIndex()}>Checkout</Link>
                            </Button>
                        ) : (
                            <>
                                <Button asChild size="lg" className="mt-6 w-full rounded-full">
                                    <Link href={login()}>Sign in to checkout</Link>
                                </Button>
                                <p className="mt-2 text-center text-xs text-muted-foreground">
                                    You need an account to place an order.
                                </p>
                            </>
                        )}
                        <Button asChild variant="ghost" className="mt-2 w-full rounded-full">
                            <Link href={productsIndex()}>Continue shopping</Link>
                        </Button>
                    </div>
                </div>
            </div>
        </>
    );
}
