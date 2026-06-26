import { Form, Head } from '@inertiajs/react';
import { Check, Package, Search, Truck } from 'lucide-react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { cn, formatPrice } from '@/lib/utils';
import { lookup as lookupOrder } from '@/routes/orders/track';
import type { Order } from '@/types/store';

const STEPS = [
    { key: 'confirmed', label: 'Confirmed', icon: Check },
    { key: 'processing', label: 'Processing', icon: Package },
    { key: 'shipped', label: 'Shipped', icon: Truck },
    { key: 'delivered', label: 'Delivered', icon: Check },
];

function StatusTimeline({ status }: { status: string }) {
    const current = Math.max(
        0,
        STEPS.findIndex((s) => s.key === status.toLowerCase()),
    );

    return (
        <div className="flex items-center">
            {STEPS.map((step, index) => {
                const done = index <= current;
                const Icon = step.icon;

                return (
                    <div key={step.key} className="flex flex-1 items-center last:flex-none">
                        <div className="flex flex-col items-center gap-2">
                            <span
                                className={cn(
                                    'flex size-10 items-center justify-center rounded-full border-2 transition-colors',
                                    done
                                        ? 'border-primary bg-primary text-primary-foreground'
                                        : 'border-border bg-background text-muted-foreground',
                                )}
                            >
                                <Icon className="size-5" />
                            </span>
                            <span className={cn('text-xs font-medium', done ? 'text-foreground' : 'text-muted-foreground')}>
                                {step.label}
                            </span>
                        </div>
                        {index < STEPS.length - 1 && (
                            <span className={cn('mx-2 h-0.5 flex-1 rounded-full', index < current ? 'bg-primary' : 'bg-border')} />
                        )}
                    </div>
                );
            })}
        </div>
    );
}

export default function Track({ order }: { order: Order | null }) {
    return (
        <>
            <Head title="Track your order" />
            <div className="mx-auto max-w-2xl px-4 py-12 sm:px-6 lg:px-8">
                <div className="mb-8 text-center">
                    <h1 className="text-3xl font-semibold tracking-tight">Track your order</h1>
                    <p className="mt-2 text-muted-foreground">
                        Enter your order number and the email used at checkout.
                    </p>
                </div>

                <Form {...lookupOrder.form()} className="rounded-2xl border border-border/70 bg-card p-6 shadow-sm">
                    {({ processing, errors }) => (
                        <div className="grid gap-4 sm:grid-cols-[1fr_1fr_auto] sm:items-end">
                            <div className="grid gap-2">
                                <Label htmlFor="order_number">Order number</Label>
                                <Input id="order_number" name="order_number" placeholder="APL-20260626-AB12C" required />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="email">Email</Label>
                                <Input id="email" name="email" type="email" placeholder="you@example.com" required />
                            </div>
                            <Button type="submit" size="lg" className="rounded-full sm:mb-0.5" disabled={processing}>
                                {processing ? <Spinner /> : <Search />}
                                Track
                            </Button>
                            <div className="sm:col-span-3">
                                <InputError message={errors.order_number} />
                            </div>
                        </div>
                    )}
                </Form>

                {order && (
                    <div className="mt-8 overflow-hidden rounded-2xl border border-border/70 bg-card">
                        <div className="flex items-center justify-between border-b border-border px-6 py-4">
                            <div>
                                <p className="text-xs uppercase tracking-wide text-muted-foreground">Order</p>
                                <p className="font-semibold">{order.order_number}</p>
                            </div>
                            <span className="rounded-full bg-primary/10 px-3 py-1 text-sm font-medium capitalize text-primary">
                                {order.status}
                            </span>
                        </div>

                        <div className="px-6 py-8">
                            <StatusTimeline status={order.status} />
                        </div>

                        <ul className="divide-y divide-border border-t border-border px-6">
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

                        <div className="border-t border-border px-6 py-4 text-sm text-muted-foreground">
                            Shipping to {order.customer_name}, {order.address}
                        </div>
                    </div>
                )}
            </div>
        </>
    );
}
