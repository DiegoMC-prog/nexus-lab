import { ref } from 'vue';

export type ToastType = 'success' | 'error' | 'info' | 'warning';

export interface Toast {
    id: number;
    title: string;
    message?: string;
    type: ToastType;
    duration?: number;
}

const toasts = ref<Toast[]>([]);
let nextId = 0;

export function useToast() {
    const addToast = (toastParams: Omit<Toast, 'id'>) => {
        const id = nextId++;
        const duration = toastParams.duration || 3000;
        
        const toast: Toast = {
            id,
            ...toastParams,
            duration
        };

        toasts.value.push(toast);

        if (duration > 0) {
            setTimeout(() => {
                removeToast(id);
            }, duration);
        }
    };

    const removeToast = (id: number) => {
        const index = toasts.value.findIndex(t => t.id === id);
        if (index !== -1) {
            toasts.value.splice(index, 1);
        }
    };

    const success = (title: string, message?: string) => {
        addToast({ title, message, type: 'success' });
    };

    const error = (title: string, message?: string) => {
        addToast({ title, message, type: 'error', duration: 5000 }); // Errors stay longer
    };

    const info = (title: string, message?: string) => {
        addToast({ title, message, type: 'info' });
    };

    const warning = (title: string, message?: string) => {
        addToast({ title, message, type: 'warning', duration: 4000 });
    };

    return {
        toasts,
        addToast,
        removeToast,
        success,
        error,
        info,
        warning
    };
}
