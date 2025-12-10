import { ref } from 'vue';

type Appearance = 'light';

export function updateTheme() {
    if (typeof window === 'undefined') {
        return;
    }
    // Always use light mode
    document.documentElement.classList.remove('dark');
}

export function initializeTheme() {
    if (typeof window === 'undefined') {
        return;
    }
    // Always initialize to light mode
    updateTheme();
}

const appearance = ref<Appearance>('light');

export function useAppearance() {
    return {
        appearance,
        updateAppearance: () => {}, // No-op since we only use light mode
    };
}
