<template>
    <header class="bg-white border-b border-gray-200 h-16 flex items-center justify-between px-4 md:px-8 sticky top-0 z-40 shrink-0">
        <div class="flex items-center gap-3">
            <button @click="$emit('toggle-sidebar')" class="p-2 -ml-2 text-gray-500 rounded-md hover:bg-gray-100 lg:hidden">
                <Menu class="h-6 w-6" />
            </button>
            <h2 class="text-xl font-semibold text-gray-900 hidden sm:block">Sistema de Control</h2>
            <h2 class="text-xl font-semibold text-gray-900 sm:hidden">NexusLab</h2>
        </div>

        <div class="flex items-center gap-4">
            <div class="relative" ref="userMenuRef">
                <button @click="showUserMenu = !showUserMenu"
                    class="flex items-center gap-3 px-3 py-2 hover:bg-gray-100 rounded-lg transition-colors">
                    <div class="flex items-center gap-3">
                        <div class="h-8 w-8 bg-blue-600 rounded-full flex items-center justify-center">
                            <User class="h-5 w-5 text-white" />
                        </div>
                        <div class="text-left">
                            <p class="text-sm font-medium text-gray-900">{{ authStore.user?.name }}</p>
                            <p class="text-xs text-gray-500 capitalize">{{ authStore.user?.role }}</p>
                        </div>
                    </div>
                    <ChevronDown class="h-4 w-4 text-gray-500" />
                </button>

                <div v-if="showUserMenu"
                    class="absolute right-0 mt-2 w-64 bg-white rounded-lg shadow-lg border border-gray-200 py-2">
                    <div class="px-4 py-3 border-b border-gray-200">
                        <p class="font-medium text-gray-900">{{ authStore.user?.name }}</p>
                        <p class="text-sm text-gray-500">{{ authStore.user?.email }}</p>
                        <span :class="[
                            'inline-block mt-2 px-2 py-1 text-xs font-medium rounded',
                            getRoleBadgeColor(authStore.user?.role || '')
                        ]">
                            {{ authStore.user?.role }}
                        </span>
                    </div>
                    <div class="py-2">
                        <button @click="goToProfile"
                            class="w-full flex items-center gap-3 px-4 py-2 hover:bg-gray-50 text-gray-700 text-left">
                            <User class="h-4 w-4" />
                            <span class="text-sm">Mi Perfil</span>
                        </button>
                        <button @click="handleLogout"
                            class="w-full flex items-center gap-3 px-4 py-2 hover:bg-gray-50 text-red-600 text-left">
                            <LogOut class="h-4 w-4" />
                            <span class="text-sm">Cerrar Sesión</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </header>
</template>

<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue';
import { useRouter } from 'vue-router';
import { Bell, User, LogOut, Settings, ChevronDown, Menu } from '@lucide/vue';
import { useAuthStore } from '@/stores/auth'; // Asegúrate de apuntar a la ruta de tu store

const authStore = useAuthStore();
const router = useRouter();

const showUserMenu = ref(false);
const showNotifications = ref(false);

const userMenuRef = ref<HTMLDivElement | null>(null);
const notificationsRef = ref<HTMLDivElement | null>(null);

// Función para simular el click outside de React
const handleClickOutside = (event: MouseEvent) => {
    const target = event.target as Node;
    if (userMenuRef.value && !userMenuRef.value.contains(target)) {
        showUserMenu.value = false;
    }
    if (notificationsRef.value && !notificationsRef.value.contains(target)) {
        showNotifications.value = false;
    }
};

onMounted(() => {
    document.addEventListener('mousedown', handleClickOutside);
});

onUnmounted(() => {
    document.removeEventListener('mousedown', handleClickOutside);
});

const handleLogout = async () => {
    await authStore.logout();
    router.push('/login');
};

const goToProfile = () => {
    showUserMenu.value = false;
    router.push('/perfil');
};

const getRoleBadgeColor = (role: string) => {
    switch (role?.toLowerCase()) {
        case 'admin':
            return 'bg-purple-100 text-purple-700';
        case 'supervisor':
            return 'bg-blue-100 text-blue-700';
        case 'operador':
            return 'bg-green-100 text-green-700';
        default:
            return 'bg-gray-100 text-gray-700';
    }
};
</script>