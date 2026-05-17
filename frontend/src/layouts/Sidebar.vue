<script setup lang="ts">
import { computed } from 'vue';
import { useAuthStore } from '@/stores/auth'; // Asegúrate de que esta sea la ruta correcta a tu store de Pinia
import {
    LayoutDashboard,
    Activity,
    History,
    Settings,
    MonitorSmartphone,
    Users,
    Shield,
    Building2,
    Calendar
} from '@lucide/vue';

const authStore = useAuthStore();

// 1. Añadimos un array de roles permitidos para cada ruta
const navItems = [
    { to: '/', label: 'Dashboard', icon: LayoutDashboard, roles: ['administrador', 'supervisor', 'operador'] },
    { to: '/monitoring', label: 'Monitoreo', icon: Activity, roles: ['administrador', 'supervisor', 'operador'] },
    { to: '/history', label: 'Historial', icon: History, roles: ['administrador', 'supervisor'] },
    { to: '/laboratorios', label: 'Laboratorios', icon: Building2, roles: ['administrador', 'supervisor'] },
    { to: '/horarios', label: 'Horarios', icon: Calendar, roles: ['administrador', 'supervisor', 'operador'] },
    { to: '/users', label: 'Usuarios', icon: Users, roles: ['administrador'] },
    { to: '/roles', label: 'Roles', icon: Shield, roles: ['administrador'] },
    { to: '/settings', label: 'Configuración', icon: Settings, roles: ['administrador', 'supervisor'] }
];

// 2. Filtramos las opciones dinámicamente según el rol del usuario actual
const visibleNavItems = computed(() => {
    const userRole = authStore.user?.role?.toLowerCase() || '';
    return navItems.filter(item => item.roles.includes(userRole));
});
</script>

<template>
    <aside class="w-64 bg-gray-900 text-white flex flex-col h-screen fixed left-0 top-0">
        <div class="p-6 border-b border-gray-800">
            <div class="flex items-center gap-3">
                <div class="bg-blue-600 p-2 rounded-lg">
                    <MonitorSmartphone class="h-6 w-6" />
                </div>
                <div>
                    <h1 class="font-semibold">Control de Equipos</h1>
                    <p class="text-xs text-gray-400">Laboratorio de Cómputo</p>
                </div>
            </div>
        </div>

        <nav class="flex-1 p-4">
            <ul class="space-y-2">
                <li v-for="item in visibleNavItems" :key="item.to">
                    <router-link :to="item.to" custom v-slot="{ isActive, navigate }">
                        <a @click="navigate" :class="[
                            'flex items-center gap-3 px-4 py-3 rounded-lg transition-colors cursor-pointer',
                            isActive
                                ? 'bg-blue-600 text-white'
                                : 'text-gray-300 hover:bg-gray-800 hover:text-white'
                        ]">
                            <component :is="item.icon" class="h-5 w-5" />
                            <span>{{ item.label }}</span>
                        </a>
                    </router-link>
                </li>
            </ul>
        </nav>

        <div class="p-4 border-t border-gray-800">
            <div class="text-xs text-gray-400">
                <p>Sistema v1.0.0</p>
                <p>© 2026 Lab Manager</p>
            </div>
        </div>
    </aside>
</template>