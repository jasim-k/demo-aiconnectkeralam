import { useState } from 'react';
import { cn } from '@/lib/utils';

type ProductImageProps = {
    src: string;
    alt: string;
    className?: string;
};

/**
 * Product image with a graceful gradient fallback when the source fails to load
 * (e.g. the placeholder host is unreachable offline).
 */
export function ProductImage({ src, alt, className }: ProductImageProps) {
    const [failed, setFailed] = useState(false);

    if (failed) {
        return (
            <div
                className={cn(
                    'flex items-center justify-center bg-gradient-to-br from-neutral-100 to-neutral-200 p-6 text-center dark:from-neutral-800 dark:to-neutral-900',
                    className,
                )}
            >
                <span className="text-sm font-medium text-neutral-500 dark:text-neutral-400">{alt}</span>
            </div>
        );
    }

    return (
        <img
            src={src}
            alt={alt}
            loading="lazy"
            onError={() => setFailed(true)}
            className={cn('object-cover', className)}
        />
    );
}
