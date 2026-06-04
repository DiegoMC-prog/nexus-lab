<script setup lang="ts">
import { useToast } from '@/composables/useToast';
import { CheckCircle, XCircle, AlertCircle, Info, X } from '@lucide/vue';

const { toasts, removeToast } = useToast();

const getIcon = (type: string) => {
    switch (type) {
        case 'success': return CheckCircle;
        case 'error': return XCircle;
        case 'warning': return AlertCircle;
        case 'info':
        default: return Info;
    }
};

const getColors = (type: string) => {
    switch (type) {
        case 'success': return 'bg-green-50 text-green-800 border-green-200';
        case 'error': return 'bg-red-50 text-red-800 border-red-200';
        case 'warning': return 'bg-yellow-50 text-yellow-800 border-yellow-200';
        case 'info':
        default: return 'bg-blue-50 text-blue-800 border-blue-200';
    }
};

const getIconColor = (type: string) => {
    switch (type) {
        case 'success': return 'text-green-500';
        case 'error': return 'text-red-500';
        case 'warning': return 'text-yellow-500';
        case 'info':
        default: return 'text-blue-500';
    }
};
</script>

<template>
    <div aria-live="assertive" class="fixed inset-0 flex items-start px-4 py-6 pointer-events-none sm:p-6 z-[100] mt-12">
        <div class="flex w-full flex-col items-center space-y-4 sm:items-end">
            <!-- TransitionGroup for smooth animations when toasts are added/removed -->
            <TransitionGroup 
                enter-active-class="transform ease-out duration-300 transition"
                enter-from-class="translate-y-2 opacity-0 sm:translate-y-0 sm:translate-x-2"
                enter-to-class="translate-y-0 opacity-100 sm:translate-x-0"
                leave-active-class="transition ease-in duration-100"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
            >
                <div 
                    v-for="toast in toasts" 
                    :key="toast.id"
                    class="pointer-events-auto w-full max-w-sm overflow-hidden rounded-lg bg-white shadow-lg ring-1 ring-black ring-opacity-5 flex"
                >
                    <div class="p-4 w-full">
                        <div class="flex items-start">
                            <div class="flex-shrink-0">
                                <component 
                                    :is="getIcon(toast.type)" 
                                    class="h-6 w-6" 
                                    :class="getIconColor(toast.type)" 
                                    aria-hidden="true" 
                                />
                            </div>
                            <div class="ml-3 w-0 flex-1 pt-0.5">
                                <p class="text-sm font-medium text-gray-900">{{ toast.title }}</p>
                                <p v-if="toast.message" class="mt-1 text-sm text-gray-500">{{ toast.message }}</p>
                            </div>
                            <div class="ml-4 flex flex-shrink-0">
                                <button 
                                    type="button" 
                                    @click="removeToast(toast.id)"
                                    class="inline-flex rounded-md bg-white text-gray-400 hover:text-gray-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 cursor-pointer"
                                >
                                    <span class="sr-only">Close</span>
                                    <X class="h-5 w-5" aria-hidden="true" />
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </TransitionGroup>
        </div>
    </div>
</template>
