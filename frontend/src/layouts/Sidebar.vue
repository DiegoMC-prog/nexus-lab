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
    MonitorSmartphone,
    Laptop,
    Bell,
    Terminal
} from '@lucide/vue';

const authStore = useAuthStore();

// Estructura organizada por bloques lógicos de negocio.
// Cada ítem define el permiso mínimo necesario para ser visible.
const navSections = [
    {
        label: 'Monitoreo y Control',
        items: [
            { to: '/', label: 'Dashboard', icon: LayoutDashboard, permission: 'dashboard.ver' },
            { to: '/monitoring', label: 'Monitoreo', icon: Activity, permission: 'monitoreo.ver' },
            { to: '/history', label: 'Historial', icon: History, permission: 'comandos.ver' },
            { to: '/restricciones', label: 'Restricciones', icon: Shield, permission: 'manage-restrictions' },
            { to: '/config-alertas', label: 'Reglas de Alertas', icon: Bell, permission: 'alertas.ver' },
            { to: '/comandos', label: 'Comandos', icon: Terminal, permission: 'comandos.ver' },
        ]
    },
    {
        label: 'Gestión de Espacios',
        items: [
            { to: '/horarios', label: 'Horarios', icon: Calendar, permission: 'horarios.ver' },
            { to: '/laboratorios', label: 'Laboratorios', icon: Building2, permission: 'laboratorios.ver' },
            { to: '/estaciones', label: 'Estaciones', icon: Laptop, permission: 'estaciones.ver' },
        ]
    },
    {
        label: 'Estructura Académica',
        items: [
            { to: '/carreras', label: 'Carreras', icon: GraduationCap, permission: 'carreras.ver' },
            { to: '/semestres', label: 'Semestres', icon: Layers, permission: 'semestres.ver' },
            { to: '/materias', label: 'Materias', icon: BookOpen, permission: 'materias.ver' },
            { to: '/grupos', label: 'Grupos', icon: Users, permission: 'grupos.ver' },
        ]
    },
    {
        label: 'Administración',
        items: [
            { to: '/users', label: 'Usuarios', icon: User, permission: 'usuarios.ver' },
            { to: '/roles', label: 'Roles', icon: Shield, permission: 'roles.ver' },
        ]
    }
];

// Filtramos dinámicamente las secciones y sus ítems según los permisos reales del usuario
const visibleSections = computed(() => {
    return navSections
        .map(section => ({
            ...section,
            items: section.items.filter(item => authStore.can(item.permission))
        }))
        .filter(section => section.items.length > 0); // Oculta secciones vacías por completo
});
</script>

<template>
    <aside
        class="w-64 bg-gray-950 text-white flex flex-col h-screen border-r border-gray-900 select-none shrink-0">
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
            <!-- Badge del rol activo -->
            <div class="flex items-center gap-2 px-2 mb-3">
                <div class="w-7 h-7 rounded-full bg-gradient-to-br from-blue-600 to-indigo-700 flex items-center justify-center text-white font-bold text-xs shrink-0">
                    {{ authStore.user?.name?.charAt(0)?.toUpperCase() || 'U' }}
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-xs font-semibold text-gray-300 truncate">{{ authStore.user?.name || 'Usuario' }}</p>
                    <p class="text-[10px] text-gray-600 capitalize font-medium">{{ authStore.user?.role || 'Sin rol' }}</p>
                </div>
            </div>
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