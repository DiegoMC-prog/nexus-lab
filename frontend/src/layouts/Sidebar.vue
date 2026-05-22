<script setup lang="ts">
import { computed } from 'vue';
import { useAuthStore } from '@/stores/auth';
import {
    LayoutDashboard,
    Activity,
    History,
    Calendar,
    Building2,
    GraduationCap,
    Layers,
    BookOpen,
    Users,
    User,
    Shield,
    Settings,
    MonitorSmartphone
} from '@lucide/vue';

const authStore = useAuthStore();

// Estructura organizada por bloques lógicos de negocio
const navSections = [
    {
        label: 'Monitoreo y Control',
        items: [
            { to: '/', label: 'Dashboard', icon: LayoutDashboard, roles: ['admin', 'supervisor', 'operador'] },
            { to: '/monitoring', label: 'Monitoreo', icon: Activity, roles: ['admin', 'supervisor', 'operador'] },
            { to: '/history', label: 'Historial', icon: History, roles: ['admin', 'supervisor'] },
        ]
    },
    {
        label: 'Gestión de Espacios',
        items: [
            { to: '/horarios', label: 'Horarios', icon: Calendar, roles: ['admin', 'supervisor', 'operador', 'docente'] },
            { to: '/laboratorios', label: 'Laboratorios', icon: Building2, roles: ['admin', 'supervisor'] },
        ]
    },
    {
        label: 'Estructura Académica',
        items: [
            { to: '/carreras', label: 'Carreras', icon: GraduationCap, roles: ['admin'] },
            { to: '/semestres', label: 'Semestres', icon: Layers, roles: ['admin'] },
            { to: '/materias', label: 'Materias', icon: BookOpen, roles: ['admin'] },
            { to: '/grupos', label: 'Grupos', icon: Users, roles: ['admin'] },
        ]
    },
    {
        label: 'Administración',
        items: [
            { to: '/users', label: 'Usuarios', icon: User, roles: ['admin'] },
            { to: '/roles', label: 'Roles', icon: Shield, roles: ['admin'] },
            { to: '/settings', label: 'Configuración', icon: Settings, roles: ['admin', 'supervisor'] }
        ]
    }
];

// Filtramos dinámicamente las secciones y sus ítems basándonos en el rol del usuario
const visibleSections = computed(() => {
    const userRole = authStore.user?.role?.toLowerCase() || '';

    return navSections
        .map(section => ({
            ...section,
            items: section.items.filter(item => item.roles.includes(userRole))
        }))
        .filter(section => section.items.length > 0); // Oculta secciones vacías por completo
});
</script>

<template>
    <aside
        class="w-64 bg-gray-950 text-white flex flex-col h-screen fixed left-0 top-0 border-r border-gray-900 select-none">
        <div class="p-6 border-b border-gray-900">
            <div class="flex items-center gap-3">
                <div class="bg-blue-600 p-2 rounded-xl text-white shadow-lg shadow-blue-600/20">
                    <MonitorSmartphone class="h-5 w-5" />
                </div>
                <div>
                    <h1 class="font-bold text-sm tracking-wide text-gray-100">NexusLab</h1>
                    <p class="text-xs text-gray-500 font-medium">Control de Equipos</p>
                </div>
            </div>
        </div>

        <nav class="flex-1 p-4 overflow-y-auto space-y-6 custom-scrollbar">
            <div v-for="section in visibleSections" :key="section.label" class="space-y-2">
                <h2 class="text-[10px] font-bold text-gray-600 px-3 tracking-widest uppercase">
                    {{ section.label }}
                </h2>

                <ul class="space-y-1">
                    <li v-for="item in section.items" :key="item.to">
                        <router-link :to="item.to" custom v-slot="{ isActive, isExactActive, navigate }">
                            <a @click="navigate" :class="[
                                'flex items-center gap-3 px-3 py-2.5 rounded-lg transition-all duration-200 cursor-pointer text-sm font-medium group',
                                (item.to === '/' ? isExactActive : isActive)
                                    ? 'bg-blue-600 text-white font-semibold shadow-md shadow-blue-600/10'
                                    : 'text-gray-400 hover:bg-gray-900 hover:text-gray-100'
                            ]">
                                <component :is="item.icon" :class="[
                                    'h-4 w-4 shrink-0 transition-colors',
                                    (item.to === '/' ? isExactActive : isActive)
                                        ? 'text-white'
                                        : 'text-gray-500 group-hover:text-gray-300'
                                ]" />
                                <span>{{ item.label }}</span>
                            </a>
                        </router-link>
                    </li>
                </ul>
            </div>
        </nav>

        <div class="p-4 border-t border-gray-900 bg-gray-950/50">
            <div class="flex flex-col gap-0.5 text-[11px] text-gray-600 px-2 font-medium">
                <p>Sistema v1.1.0</p>
                <p>© 2026 NexusLab Manager</p>
            </div>
        </div>
    </aside>
</template>

<style scoped>
/* Estilización estética de la barra de scroll integrada para Tailwind */
.custom-scrollbar::-webkit-scrollbar {
    width: 4px;
}

.custom-scrollbar::-webkit-scrollbar-track {
    background: transparent;
}

.custom-scrollbar::-webkit-scrollbar-thumb {
    background: #1f2937;
    border-radius: 2px;
}

.custom-scrollbar::-webkit-scrollbar-thumb:hover {
    background: #374151;
}
</style>