import { Head, Link } from '@inertiajs/react';
import { Package, ShoppingBag } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { cn, formatPrice } from '@/lib/utils';
import { index as productsIndex } from '@/routes/products';
import type { Order } from '@/types/store';

const STATUS_STYLES: Record<string, string> = {
    confirmed: 'bg-blue-50 text-blue-700 dark:bg-blue-950 dark:text-blue-400',
    processing: 'bg-amber-50 text-amber-700 dark:bg-amber-950 dark:text-amber-400',
    shipped: 'bg-violet-50 text-violet-700 dark:bg-violet-950 dark:text-violet-400',
    delivered: 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-400',
};

function formatDate(value: string): string {
    return new Date(value).toLocaleDateString(undefined, {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
}

function OrderCard({ order }: { order: Order }) {
    const itemCount = order.items.reduce((total, item) => total + item.quantity, 0);

    return (
        <div className="overflow-hidden rounded-2xl border border-border/70 bg-card">
            <div className="flex flex-wrap items-center justify-between gap-3 border-b border-border px-6 py-4">
                <div>
                    <p className="text-xs uppercase tracking-wide text-muted-foreground">Order</p>
                    <p className="font-semibold">{order.order_number}</p>
                    <p className="mt-0.5 text-sm text-muted-foreground">Placed {formatDate(order.created_at)}</p>
                </div>
                <span
                    className={cn(
                        'rounded-full px-3 py-1 text-sm font-medium capitalize',
                        STATUS_STYLES[order.status.toLowerCase()] ?? 'bg-muted text-muted-foreground',
                    )}
                >
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
                <span className="text-sm text-muted-foreground">
                    {itemCount} {itemCount === 1 ? 'item' : 'items'}
                </span>
                <span className="text-lg font-semibold">{formatPrice(order.total)}</span>
            </div>
        </div>
    );
}

function EmptyState() {
    return (
        <div className="flex flex-col items-center rounded-2xl border border-dashed border-border/70 px-6 py-16 text-center">
            <span className="flex size-14 items-center justify-center rounded-full bg-muted">
                <ShoppingBag className="size-7 text-muted-foreground" />
            </span>
            <h2 className="mt-5 text-lg font-semibold">No orders yet</h2>
            <p className="mt-1 max-w-sm text-sm text-muted-foreground">
                When you place an order, it will show up here so you can keep track of it.
            </p>
            <Button asChild size="lg" className="mt-6 rounded-full px-8">
                <Link href={productsIndex()}>Start shopping</Link>
            </Button>
        </div>
    );
}

export default function Orders({ orders }: { orders: Order[] }) {
    return (
        <>
            <Head title="My orders" />
            <div className="mx-auto max-w-2xl px-4 py-12 sm:px-6 lg:px-8">
                <div className="mb-8 flex items-center gap-3">
                    <span className="flex size-10 items-center justify-center rounded-full bg-muted">
                        <Package className="size-5" />
                    </span>
                    <div>
                        <h1 className="text-2xl font-semibold tracking-tight">My orders</h1>
                        <p className="text-sm text-muted-foreground">
                            {orders.length > 0
                                ? `${orders.length} ${orders.length === 1 ? 'order' : 'orders'}`
                                : 'Your order history'}
                        </p>
                    </div>
                </div>

                {orders.length > 0 ? (
                    <div className="space-y-5">
                        {orders.map((order) => (
                            <OrderCard key={order.id} order={order} />
                        ))}
                    </div>
                ) : (
                    <EmptyState />
                )}
            </div>
        </>
    );
}
