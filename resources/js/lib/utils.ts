import type { InertiaLinkProps } from '@inertiajs/react';
import { clsx } from 'clsx';
import type { ClassValue } from 'clsx';
import { twMerge } from 'tailwind-merge';

export function cn(...inputs: ClassValue[]) {
    return twMerge(clsx(inputs));
}

export function toUrl(url: NonNullable<InertiaLinkProps['href']>): string {
    return typeof url === 'string' ? url : url.url;
}

const inrFormatter = new Intl.NumberFormat('en-IN', {
    style: 'currency',
    currency: 'INR',
    maximumFractionDigits: 0,
});

export function formatPrice(amount: number): string {
    return inrFormatter.format(amount);
}

const COLOR_SWATCHES: Record<string, string> = {
    black: '#1d1d1f',
    blue: '#9fb6cf',
    pink: '#f0d4d4',
    green: '#cdd9c6',
    yellow: '#f2e2b3',
    white: '#f3f3ef',
    ultramarine: '#3a4a8c',
    teal: '#a9c7c5',
    silver: '#e3e4e6',
    sage: '#b6c2ac',
    'sky blue': '#a9c7e0',
    'cosmic orange': '#d8642f',
    'deep blue': '#2b3a5a',
    'natural titanium': '#c2bcb2',
    'blue titanium': '#46506a',
    'black titanium': '#3a3a3c',
    'white titanium': '#eceae5',
    'desert titanium': '#bda58c',
    titanium: '#c2bcb2',
};

/** Best-effort hex swatch for a product colour name, for the colour dot. */
export function colorToHex(color: string | null | undefined): string {
    if (!color) {
        return '#c7c7cc';
    }

    return COLOR_SWATCHES[color.toLowerCase()] ?? '#c7c7cc';
}
