import { Head, Link } from '@inertiajs/react';
import { CheckCircle2 } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { formatPrice } from '@/lib/utils';
import { index as productsIndex } from '@/routes/products';
import type { Order } from '@/types/store';

export default function OrderSuccess({ order }: { order: Order }) {
    return (
        <>
            <Head title={`Order ${order.order_number}`} />
            <div className="mx-auto max-w-2xl px-4 py-16 sm:px-6 lg:px-8">
                <div className="flex flex-col items-center text-center">
                    <span className="flex size-16 items-center justify-center rounded-full bg-emerald-50 dark:bg-emerald-950">
                        <CheckCircle2 className="size-9 text-emerald-500" />
                    </span>
                    <h1 className="mt-5 text-3xl font-semibold tracking-tight">Order confirmed</h1>
                    <p className="mt-2 text-muted-foreground">
                        Thank you, {order.customer_name}. A confirmation has been sent to {order.email}.
                    </p>
                </div>

                <div className="mt-10 overflow-hidden rounded-3xl border border-border/70 bg-card">
                    <div className="flex items-center justify-between border-b border-border px-6 py-4">
                        <div>
                            <p className="text-xs uppercase tracking-wide text-muted-foreground">Order number</p>
                            <p className="font-semibold">{order.order_number}</p>
                        </div>
                        <span className="rounded-full bg-emerald-50 px-3 py-1 text-sm font-medium capitalize text-emerald-700 dark:bg-emerald-950 dark:text-emerald-400">
                            {order.status}
                        </span>
                    </div>

                    <ul className="divide-y divide-border px-6">
                        {order.items.map((item) => (
                            <li key={item.id} className="flex items-center justify-between py-4">
                                <div>
                                    <p className="font-medium">{item.product_name}</p>
                                    <p className="text-sm text-muted-foreground">
                                        {formatPrice(item.price)} × {item.quantity}
                                    </p>
                                </div>
                                <span className="font-medium">{formatPrice(item.price * item.quantity)}</span>
                            </li>
                        ))}
                    </ul>

                    <div className="flex items-center justify-between border-t border-border bg-muted/40 px-6 py-4">
                        <span className="font-semibold">Total</span>
                        <span className="text-lg font-semibold">{formatPrice(order.total)}</span>
                    </div>
                </div>

                <div className="mt-8 rounded-3xl border border-border/70 p-6 text-sm">
                    <h2 className="font-semibold">Shipping to</h2>
                    <p className="mt-2 text-muted-foreground">{order.customer_name}</p>
                    <p className="text-muted-foreground">{order.address}</p>
                    <p className="text-muted-foreground">{order.phone}</p>
                </div>

                <div className="mt-8 text-center">
                    <Button asChild size="lg" className="rounded-full px-8">
                        <Link href={productsIndex()}>Continue shopping</Link>
                    </Button>
                </div>
            </div>
        </>
    );
}
