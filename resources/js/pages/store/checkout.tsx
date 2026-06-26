import { Form, Head, Link } from '@inertiajs/react';
import { ChevronLeft } from 'lucide-react';
import InputError from '@/components/input-error';
import { ProductImage } from '@/components/store/product-image';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { formatPrice } from '@/lib/utils';
import { index as cartIndex } from '@/routes/cart';
import { store as checkoutStore } from '@/routes/checkout';
import type { Cart } from '@/types/store';

type CheckoutProps = {
    cart: Cart;
    customer: { name: string; email: string; phone: string | null; address: string | null };
};

export default function Checkout({ cart, customer }: CheckoutProps) {
    return (
        <>
            <Head title="Checkout" />
            <div className="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
                <Button asChild variant="ghost" size="sm" className="mb-6 -ml-2 text-muted-foreground">
                    <Link href={cartIndex()}>
                        <ChevronLeft /> Back to cart
                    </Link>
                </Button>

                <h1 className="mb-8 text-4xl font-semibold tracking-tight">Checkout</h1>

                <div className="grid gap-10 lg:grid-cols-[1fr_400px]">
                    {/* Customer details */}
                    <Form {...checkoutStore.form()} className="space-y-6">
                        {({ processing, errors }) => (
                            <>
                                <div className="rounded-3xl border border-border/70 bg-card p-6 sm:p-8">
                                    <h2 className="mb-4 text-lg font-semibold">Shipping details</h2>
                                    <div className="grid gap-4">
                                        <div className="grid gap-2">
                                            <Label htmlFor="customer_name">Full name</Label>
                                            <Input id="customer_name" name="customer_name" defaultValue={customer.name} required autoFocus autoComplete="name" />
                                            <InputError message={errors.customer_name} />
                                        </div>
                                        <div className="grid gap-4 sm:grid-cols-2">
                                            <div className="grid gap-2">
                                                <Label htmlFor="email">Email</Label>
                                                <Input id="email" name="email" type="email" defaultValue={customer.email} required autoComplete="email" />
                                                <InputError message={errors.email} />
                                            </div>
                                            <div className="grid gap-2">
                                                <Label htmlFor="phone">Phone</Label>
                                                <Input id="phone" name="phone" type="tel" defaultValue={customer.phone ?? ''} required autoComplete="tel" />
                                                <InputError message={errors.phone} />
                                            </div>
                                        </div>
                                        <div className="grid gap-2">
                                            <Label htmlFor="address">Shipping address</Label>
                                            <textarea
                                                id="address"
                                                name="address"
                                                defaultValue={customer.address ?? ''}
                                                required
                                                rows={3}
                                                className="flex w-full rounded-xl border border-input bg-transparent px-3 py-2 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                                            />
                                            <InputError message={errors.address} />
                                        </div>
                                    </div>
                                </div>

                                <InputError message={errors.cart} />

                                <Button type="submit" size="lg" className="w-full rounded-full" disabled={processing}>
                                    {processing && <Spinner />}
                                    Place order · {formatPrice(cart.total)}
                                </Button>
                            </>
                        )}
                    </Form>

                    {/* Order summary */}
                    <div className="h-fit rounded-3xl border border-border/70 bg-card p-6 lg:sticky lg:top-24">
                        <h2 className="text-lg font-semibold">Your order</h2>
                        <ul className="mt-4 divide-y divide-border">
                            {cart.items.map((line) => (
                                <li key={line.id} className="flex items-center gap-3 py-3">
                                    <div className="size-14 shrink-0 overflow-hidden rounded-lg border border-border bg-neutral-50 dark:bg-neutral-900">
                                        <ProductImage src={line.image} alt={line.name} className="size-full scale-125 object-cover" />
                                    </div>
                                    <div className="flex-1">
                                        <p className="text-sm font-medium leading-tight">{line.name}</p>
                                        <p className="text-xs text-muted-foreground">Qty {line.quantity}</p>
                                    </div>
                                    <span className="text-sm font-medium">{formatPrice(line.subtotal)}</span>
                                </li>
                            ))}
                        </ul>
                        <div className="mt-4 space-y-2 border-t border-border pt-4 text-sm">
                            <div className="flex justify-between">
                                <span className="text-muted-foreground">Subtotal</span>
                                <span>{formatPrice(cart.total)}</span>
                            </div>
                            <div className="flex justify-between">
                                <span className="text-muted-foreground">Shipping</span>
                                <span className="text-emerald-600 dark:text-emerald-400">Free</span>
                            </div>
                            <div className="flex justify-between border-t border-border pt-2 text-base font-semibold">
                                <span>Total</span>
                                <span>{formatPrice(cart.total)}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </>
    );
}
