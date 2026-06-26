import { useSyncExternalStore } from 'react';

export type ResolvedAppearance = 'light' | 'dark';
export type Appearance = ResolvedAppearance | 'system';

export type UseAppearanceReturn = {
    readonly appearance: Appearance;
    readonly resolvedAppearance: ResolvedAppearance;
    readonly updateAppearance: (mode: Appearance) => void;
};

/**
 * This application is light-only. We keep the appearance API surface stable so
 * consumers (e.g. the toast theme) keep working, but the theme is locked to
 * light and dark mode is never applied.
 */
const applyLight = (): void => {
    if (typeof document === 'undefined') {
        return;
    }

    document.documentElement.classList.remove('dark');
    document.documentElement.style.colorScheme = 'light';
};

const subscribe = (): (() => void) => () => {};
const getSnapshot = (): Appearance => 'light';

export function initializeTheme(): void {
    applyLight();
}

export function useAppearance(): UseAppearanceReturn {
    const appearance = useSyncExternalStore(subscribe, getSnapshot, getSnapshot);

    return {
        appearance,
        resolvedAppearance: 'light',
        updateAppearance: applyLight,
    } as const;
}
