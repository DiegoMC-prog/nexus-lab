<script setup lang="ts">
import { ref, onMounted, onUnmounted, watch, computed } from 'vue';
import { useAuthStore } from '@/stores/auth';
import { dashboardService, type DashboardStatsResponse } from '@/services/dashboardService';
import { alertaService } from '@/services/alertaService';
import DocenteDashboard from './DocenteDashboard.vue';
import {
    Laptop, BookOpen, Users, AlertTriangle, CheckCircle2,
    RefreshCw, Clock, Calendar, MapPin, Cpu, Database,
    Wifi, Thermometer, Loader2, GraduationCap, ArrowRight
} from '@lucide/vue';

// --- ESTADOS REACTIVOS ---
const authStore = useAuthStore();
const isAdmin = computed(() => authStore.user?.role === 'admin');
const isDocente = computed(() => authStore.user?.role === 'docente');
const isEstudiante = computed(() => authStore.user?.role === 'estudiante');

const stats = ref<DashboardStatsResponse | null>(null);
const safeStats = computed(() => {
    return stats.value || {
        estaciones: { total: 0, activas: 0, inactivas: 0, mantenimiento: 0, desconectadas: 0 },
        laboratorios: { total: 0, activos: 0, inactivos: 0, status_list: [] },
        materias: { total: 0 },
        docentes: { total: 0 },
        estudiantes: { total: 0 },
        alertas: { totales: 0, pendientes: 0, resueltas: 0, recientes: [] },
        horarios_hoy: []
    } as DashboardStatsResponse;
});
const isLoading = ref(true);
const isRefreshing = ref(false);
const autoRefresh = ref(true);
const isSavingId = ref<number | null>(null);

let refreshInterval: ReturnType<typeof setInterval> | null = null;

// --- CARGA DE DATOS ---
const loadStats = async (silent = false) => {
    if (!silent) isLoading.value = true;
    else isRefreshing.value = true;

    try {
        const response = await dashboardService.getStats();
        stats.value = response;
    } catch (error) {
        console.error('Error al cargar estadísticas del dashboard:', error);
    } finally {
        isLoading.value = false;
        isRefreshing.value = false;
    }
};

const handleResolveAlerta = async (alerta: any) => {
    isSavingId.value = alerta.id;
    try {
        const payload = {
            estacion_id: alerta.estacion_id,
            config_alerta_id: alerta.config_alerta_id,
            valor_actual: Number(alerta.valor_actual),
            estado: 'resuelto',
            resuelto_at: new Date().toISOString()
        };
        await alertaService.actualizarAlerta(alerta.id, payload);
        // Recargar silenciosamente para refrescar widgets
        await loadStats(true);
    } catch (error) {
        console.error('Error al resolver la alerta desde el dashboard:', error);
    } finally {
        isSavingId.value = null;
    }
};

const setupRefreshInterval = () => {
    if (refreshInterval) {
        clearInterval(refreshInterval);
        refreshInterval = null;
    }

    if (autoRefresh.value) {
        refreshInterval = setInterval(() => {
            loadStats(true);
        }, 15000); // Auto-actualizar cada 15 segundos para dar sensación de tiempo real
    }
};

watch(autoRefresh, () => {
    setupRefreshInterval();
});

onMounted(() => {
    loadStats();
    setupRefreshInterval();
});

onUnmounted(() => {
    if (refreshInterval) {
        clearInterval(refreshInterval);
    }
});

// --- HELPERS ESTILOS Y FORMATO ---
const getGreeting = () => {
    const hour = new Date().getHours();
    if (hour < 12) return '¡Buenos días';
    if (hour < 19) return '¡Buenas tardes';
    return '¡Buenas noches';
};

const getCurrentDateString = () => {
    return new Date().toLocaleDateString('es-ES', {
        weekday: 'long',
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    });
};

const getFriendlyMetricName = (metrica: string | undefined) => {
    if (!metrica) return 'Alerta del Sistema';
    switch (metrica) {
        case 'carga_cpu': return 'Uso excesivo de CPU';
        case 'uso_ram_mb': return 'Desborde de Memoria RAM';
        case 'temp_cpu': return 'Alta Temperatura CPU';
        case 'uso_disco': return 'Límite de Espacio en Disco';
        case 'latencia_red': return 'Latencia Crítica de Red';
        default: return metrica;
    }
};

const formatThreshold = (metrica: string | undefined, value: number | undefined) => {
    if (value === undefined) return '';
    if (!metrica) return value.toString();
    switch (metrica) {
        case 'carga_cpu': return `${value}%`;
        case 'uso_ram_mb': return value >= 1024 ? `${(value / 1024).toFixed(1)} GB` : `${value} MB`;
        case 'temp_cpu': return `${value}°C`;
        case 'uso_disco': return `${value}%`;
        case 'latencia_red': return `${value} ms`;
        default: return value.toString();
    }
};

const getMetricIcon = (metrica: string | undefined) => {
    if (!metrica) return AlertTriangle;
    switch (metrica) {
        case 'carga_cpu': return Cpu;
        case 'uso_ram_mb': return Database;
        case 'temp_cpu': return Thermometer;
        case 'uso_disco': return Database;
        case 'latencia_red': return Wifi;
        default: return AlertTriangle;
    }
};

const getDayName = (dayNum: number) => {
    const days = ['Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo'];
    return days[dayNum - 1] || 'Desconocido';
};
</script>

<template>
    <DocenteDashboard v-if="isDocente" />
    <div v-else class="p-6 space-y-6 bg-slate-50 min-h-screen">
        <!-- Loader inicial de pantalla completa -->
        <div v-if="isLoading && !stats" class="flex flex-col items-center justify-center min-h-[70vh] space-y-4">
            <div class="relative flex items-center justify-center">
                <div class="w-16 h-16 border-4 border-blue-100 border-t-blue-600 rounded-full animate-spin"></div>
                <Laptop class="w-6 h-6 text-blue-600 absolute animate-pulse" />
            </div>
            <p class="text-sm font-semibold text-slate-500 animate-pulse">Sincronizando métricas globales de Nexus
                Lab...</p>
        </div>

        <template v-else-if="stats">
            <!-- Sección de Bienvenida & Control de Refresco -->
            <div
                class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 bg-white p-6 rounded-2xl border border-slate-200/80 shadow-xs relative overflow-hidden">
                <div
                    class="absolute right-0 top-0 -mt-6 -mr-6 w-32 h-32 bg-blue-50/50 rounded-full blur-3xl pointer-events-none">
                </div>
                <div class="space-y-1 z-10">
                    <h1 class="text-2xl font-black text-slate-900 leading-tight flex flex-wrap items-center gap-2">
                        {{ getGreeting() }}, <span class="text-blue-600">{{ authStore.user?.name }}</span>!
                        <span :class="[
                            'inline-flex items-center gap-1.5 px-3 py-0.5 text-xs font-black rounded-full border uppercase tracking-wider',
                            isAdmin ? 'bg-blue-50 text-blue-700 border-blue-100' :
                                isDocente ? 'bg-orange-50 text-orange-700 border-orange-100' :
                                    'bg-rose-50 text-rose-700 border-rose-100'
                        ]">
                            {{ authStore.user?.role }}
                        </span>
                    </h1>
                    <p class="text-sm text-slate-500 font-medium flex items-center gap-1.5">
                        <Calendar class="w-4 h-4 text-slate-400" />
                        {{ getCurrentDateString() }}
                    </p>
                </div>

                <div class="flex items-center gap-4 z-10 shrink-0">
                    <!-- Switch de Auto-refresh -->
                    <label class="inline-flex items-center cursor-pointer select-none">
                        <input type="checkbox" v-model="autoRefresh" class="sr-only peer">
                        <div
                            class="w-9 h-5 bg-slate-250 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:height-4 after:w-4 after:transition-all peer-checked:bg-blue-600 relative">
                        </div>
                        <span class="ml-2 text-xs font-semibold text-slate-600">Auto-refresco (15s)</span>
                    </label>

                    <button @click="loadStats(true)" :disabled="isRefreshing"
                        class="inline-flex items-center justify-center bg-white hover:bg-slate-50 text-slate-700 font-bold py-2.5 px-4 border border-slate-200 rounded-xl shadow-xs gap-2 transition-all text-xs cursor-pointer disabled:opacity-50">
                        <RefreshCw class="w-3.5 h-3.5" :class="{ 'animate-spin': isRefreshing }" />
                        Refrescar
                    </button>
                </div>
            </div>

            <!-- KPI Cards Dinámicos por Rol -->

            <!-- 1. KPI Cards para Admin -->
            <div v-if="isAdmin" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5 gap-5">
                <!-- Estaciones -->
                <div
                    class="group bg-gradient-to-br from-indigo-500 to-indigo-600 text-white rounded-2xl p-5 shadow-sm border border-indigo-100 hover:-translate-y-1 hover:shadow-md transition-all duration-300 relative overflow-hidden">
                    <div class="absolute right-0 bottom-0 translate-x-3 translate-y-3 opacity-15">
                        <Laptop class="w-24 h-24 text-white" />
                    </div>
                    <div class="flex justify-between items-start">
                        <div class="p-2 bg-white/10 rounded-xl">
                            <Laptop class="w-5 h-5 text-white" />
                        </div>
                        <span class="text-xs font-bold bg-white/20 px-2 py-0.5 rounded-full">Workstations</span>
                    </div>
                    <div class="mt-4">
                        <h3 class="text-3xl font-black">{{ safeStats.estaciones.total }}</h3>
                        <p class="text-xs text-indigo-100 font-medium mt-1">Estaciones Registradas</p>
                    </div>
                    <div
                        class="mt-4 pt-3 border-t border-white/10 grid grid-cols-2 gap-y-1.5 text-[10px] font-semibold text-indigo-100">
                        <div class="flex items-center gap-1">
                            <span class="w-1.5 h-1.5 bg-emerald-400 rounded-full animate-ping"></span>
                            <span>{{ safeStats.estaciones.activas }} Activas</span>
                        </div>
                        <div class="flex items-center gap-1">
                            <span class="w-1.5 h-1.5 bg-slate-350 rounded-full"></span>
                            <span>{{ safeStats.estaciones.inactivas }} Off</span>
                        </div>
                        <div class="flex items-center gap-1 col-span-2">
                            <span class="w-1.5 h-1.5 bg-amber-400 rounded-full"></span>
                            <span>{{ safeStats.estaciones.mantenimiento }} Mantenimiento / {{
                                safeStats.estaciones.desconectadas }} Desc.</span>
                        </div>
                    </div>
                </div>

                <!-- Laboratorios -->
                <div
                    class="group bg-gradient-to-br from-teal-500 to-emerald-600 text-white rounded-2xl p-5 shadow-sm border border-teal-100 hover:-translate-y-1 hover:shadow-md transition-all duration-300 relative overflow-hidden">
                    <div class="absolute right-0 bottom-0 translate-x-3 translate-y-3 opacity-15">
                        <Database class="w-24 h-24 text-white" />
                    </div>
                    <div class="flex justify-between items-start">
                        <div class="p-2 bg-white/10 rounded-xl">
                            <Database class="w-5 h-5 text-white" />
                        </div>
                        <span class="text-xs font-bold bg-white/20 px-2 py-0.5 rounded-full">Entornos</span>
                    </div>
                    <div class="mt-4">
                        <h3 class="text-3xl font-black">{{ safeStats.laboratorios.total }}</h3>
                        <p class="text-xs text-teal-50 font-medium mt-1">Laboratorios de Cómputo</p>
                    </div>
                    <div
                        class="mt-4 pt-3 border-t border-white/10 flex items-center gap-4 text-xs font-semibold text-teal-50">
                        <div class="flex items-center gap-1.5">
                            <span class="w-2 h-2 bg-white rounded-full"></span>
                            <span>{{ safeStats.laboratorios.activos }} Activos</span>
                        </div>
                        <div class="flex items-center gap-1.5" v-if="safeStats.laboratorios.inactivos > 0">
                            <span class="w-2 h-2 bg-teal-350 rounded-full"></span>
                            <span>{{ safeStats.laboratorios.inactivos }} Inactivos</span>
                        </div>
                    </div>
                </div>

                <!-- Materias -->
                <div
                    class="group bg-gradient-to-br from-blue-500 to-sky-600 text-white rounded-2xl p-5 shadow-sm border border-blue-100 hover:-translate-y-1 hover:shadow-md transition-all duration-300 relative overflow-hidden">
                    <div class="absolute right-0 bottom-0 translate-x-3 translate-y-3 opacity-15">
                        <BookOpen class="w-24 h-24 text-white" />
                    </div>
                    <div class="flex justify-between items-start">
                        <div class="p-2 bg-white/10 rounded-xl">
                            <BookOpen class="w-5 h-5 text-white" />
                        </div>
                        <span class="text-xs font-bold bg-white/20 px-2 py-0.5 rounded-full">Académico</span>
                    </div>
                    <div class="mt-4">
                        <h3 class="text-3xl font-black">{{ safeStats.materias.total }}</h3>
                        <p class="text-xs text-blue-100 font-medium mt-1">Materias de Pregrado</p>
                    </div>
                    <div
                        class="mt-4 pt-3 border-t border-white/10 text-xs font-semibold text-blue-50 flex items-center gap-1">
                        <CheckCircle2 class="w-3.5 h-3.5" />
                        <span>Curriculum en Operación</span>
                    </div>
                </div>

                <!-- Docentes -->
                <div
                    class="group bg-gradient-to-br from-orange-500 to-amber-600 text-white rounded-2xl p-5 shadow-sm border border-orange-100 hover:-translate-y-1 hover:shadow-md transition-all duration-300 relative overflow-hidden">
                    <div class="absolute right-0 bottom-0 translate-x-3 translate-y-3 opacity-15">
                        <GraduationCap class="w-24 h-24 text-white" />
                    </div>
                    <div class="flex justify-between items-start">
                        <div class="p-2 bg-white/10 rounded-xl">
                            <GraduationCap class="w-5 h-5 text-white" />
                        </div>
                        <span class="text-xs font-bold bg-white/20 px-2 py-0.5 rounded-full">Docentes</span>
                    </div>
                    <div class="mt-4">
                        <h3 class="text-3xl font-black">{{ safeStats.docentes.total }}</h3>
                        <p class="text-xs text-orange-100 font-medium mt-1">Profesores Asignados</p>
                    </div>
                    <div
                        class="mt-4 pt-3 border-t border-white/10 text-xs font-semibold text-orange-50 flex items-center gap-1">
                        <Clock class="w-3.5 h-3.5" />
                        <span>Asistencia de Personal</span>
                    </div>
                </div>

                <!-- Estudiantes -->
                <div
                    class="group bg-gradient-to-br from-rose-500 to-pink-600 text-white rounded-2xl p-5 shadow-sm border border-rose-100 hover:-translate-y-1 hover:shadow-md transition-all duration-300 relative overflow-hidden">
                    <div class="absolute right-0 bottom-0 translate-x-3 translate-y-3 opacity-15">
                        <Users class="w-24 h-24 text-white" />
                    </div>
                    <div class="flex justify-between items-start">
                        <div class="p-2 bg-white/10 rounded-xl">
                            <Users class="w-5 h-5 text-white" />
                        </div>
                        <span class="text-xs font-bold bg-white/20 px-2 py-0.5 rounded-full">Alumnos</span>
                    </div>
                    <div class="mt-4">
                        <h3 class="text-3xl font-black">{{ safeStats.estudiantes.total }}</h3>
                        <p class="text-xs text-rose-100 font-medium mt-1">Estudiantes Registrados</p>
                    </div>
                    <div
                        class="mt-4 pt-3 border-t border-white/10 text-xs font-semibold text-rose-50 flex items-center gap-1">
                        <Users class="w-3.5 h-3.5" />
                        <span>Matrícula General Activa</span>
                    </div>
                </div>
            </div>

            <!-- 2. KPI Cards para Docente -->
            <div v-else-if="isDocente" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
                <!-- Clases de Hoy -->
                <div
                    class="group bg-gradient-to-br from-orange-500 to-amber-600 text-white rounded-2xl p-5 shadow-sm border border-orange-100 hover:-translate-y-1 hover:shadow-md transition-all duration-300 relative overflow-hidden">
                    <div class="absolute right-0 bottom-0 translate-x-3 translate-y-3 opacity-15">
                        <Clock class="w-24 h-24 text-white" />
                    </div>
                    <div class="flex justify-between items-start">
                        <div class="p-2 bg-white/10 rounded-xl">
                            <Clock class="w-5 h-5 text-white" />
                        </div>
                        <span class="text-xs font-bold bg-white/20 px-2 py-0.5 rounded-full">Itinerario</span>
                    </div>
                    <div class="mt-4">
                        <h3 class="text-3xl font-black">{{ safeStats.docente_kpis?.clases_hoy_count ?? 0 }}</h3>
                        <p class="text-xs text-orange-100 font-medium mt-1">Clases Agendadas Hoy</p>
                    </div>
                    <div
                        class="mt-4 pt-3 border-t border-white/10 text-xs font-semibold text-orange-50 flex items-center gap-1">
                        <Calendar class="w-3.5 h-3.5" />
                        <span>Itinerario Diario Activo</span>
                    </div>
                </div>

                <!-- Mis Grupos -->
                <div
                    class="group bg-gradient-to-br from-indigo-500 to-indigo-600 text-white rounded-2xl p-5 shadow-sm border border-indigo-100 hover:-translate-y-1 hover:shadow-md transition-all duration-300 relative overflow-hidden">
                    <div class="absolute right-0 bottom-0 translate-x-3 translate-y-3 opacity-15">
                        <Users class="w-24 h-24 text-white" />
                    </div>
                    <div class="flex justify-between items-start">
                        <div class="p-2 bg-white/10 rounded-xl">
                            <Users class="w-5 h-5 text-white" />
                        </div>
                        <span class="text-xs font-bold bg-white/20 px-2 py-0.5 rounded-full">Alumnado</span>
                    </div>
                    <div class="mt-4">
                        <h3 class="text-3xl font-black">{{ safeStats.docente_kpis?.mis_grupos_count ?? 0 }}</h3>
                        <p class="text-xs text-indigo-100 font-medium mt-1">Grupos Asignados</p>
                    </div>
                    <div
                        class="mt-4 pt-3 border-t border-white/10 text-xs font-semibold text-indigo-50 flex items-center gap-1">
                        <Users class="w-3.5 h-3.5" />
                        <span>Gestión y Control Activo</span>
                    </div>
                </div>

                <!-- Mis Materias -->
                <div
                    class="group bg-gradient-to-br from-blue-500 to-sky-600 text-white rounded-2xl p-5 shadow-sm border border-blue-100 hover:-translate-y-1 hover:shadow-md transition-all duration-300 relative overflow-hidden">
                    <div class="absolute right-0 bottom-0 translate-x-3 translate-y-3 opacity-15">
                        <BookOpen class="w-24 h-24 text-white" />
                    </div>
                    <div class="flex justify-between items-start">
                        <div class="p-2 bg-white/10 rounded-xl">
                            <BookOpen class="w-5 h-5 text-white" />
                        </div>
                        <span class="text-xs font-bold bg-white/20 px-2 py-0.5 rounded-full">Académico</span>
                    </div>
                    <div class="mt-4">
                        <h3 class="text-3xl font-black">{{ safeStats.docente_kpis?.mis_materias_count ?? 0 }}</h3>
                        <p class="text-xs text-blue-100 font-medium mt-1">Mis Asignaturas</p>
                    </div>
                    <div
                        class="mt-4 pt-3 border-t border-white/10 text-xs font-semibold text-blue-50 flex items-center gap-1">
                        <CheckCircle2 class="w-3.5 h-3.5" />
                        <span>Plan Académico Vigente</span>
                    </div>
                </div>

                <!-- Laboratorios Asignados -->
                <div
                    class="group bg-gradient-to-br from-teal-500 to-emerald-600 text-white rounded-2xl p-5 shadow-sm border border-teal-100 hover:-translate-y-1 hover:shadow-md transition-all duration-300 relative overflow-hidden">
                    <div class="absolute right-0 bottom-0 translate-x-3 translate-y-3 opacity-15">
                        <Database class="w-24 h-24 text-white" />
                    </div>
                    <div class="flex justify-between items-start">
                        <div class="p-2 bg-white/10 rounded-xl">
                            <Database class="w-5 h-5 text-white" />
                        </div>
                        <span class="text-xs font-bold bg-white/20 px-2 py-0.5 rounded-full">Laboratorios</span>
                    </div>
                    <div class="mt-4">
                        <h3 class="text-3xl font-black">{{ safeStats.docente_kpis?.laboratorios_asignados_count ?? 0 }}
                        </h3>
                        <p class="text-xs text-teal-100 font-medium mt-1">Laboratorios con Clases</p>
                    </div>
                    <div
                        class="mt-4 pt-3 border-t border-white/10 text-xs font-semibold text-teal-50 flex items-center gap-1">
                        <MapPin class="w-3.5 h-3.5" />
                        <span>Entornos de Aprendizaje</span>
                    </div>
                </div>
            </div>

            <!-- 3. KPI Cards para Estudiante -->
            <div v-else-if="isEstudiante" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
                <!-- Clases de Hoy -->
                <div
                    class="group bg-gradient-to-br from-rose-500 to-pink-600 text-white rounded-2xl p-5 shadow-sm border border-rose-100 hover:-translate-y-1 hover:shadow-md transition-all duration-300 relative overflow-hidden">
                    <div class="absolute right-0 bottom-0 translate-x-3 translate-y-3 opacity-15">
                        <Clock class="w-24 h-24 text-white" />
                    </div>
                    <div class="flex justify-between items-start">
                        <div class="p-2 bg-white/10 rounded-xl">
                            <Clock class="w-5 h-5 text-white" />
                        </div>
                        <span class="text-xs font-bold bg-white/20 px-2 py-0.5 rounded-full">Mi Horario</span>
                    </div>
                    <div class="mt-4">
                        <h3 class="text-3xl font-black">{{ safeStats.estudiante_kpis?.clases_hoy_count ?? 0 }}</h3>
                        <p class="text-xs text-rose-100 font-medium mt-1">Mis Clases de Hoy</p>
                    </div>
                    <div
                        class="mt-4 pt-3 border-t border-white/10 text-xs font-semibold text-rose-50 flex items-center gap-1">
                        <Calendar class="w-3.5 h-3.5" />
                        <span>Mis Horarios del Día</span>
                    </div>
                </div>

                <!-- Mis Grupos -->
                <div
                    class="group bg-gradient-to-br from-blue-500 to-sky-600 text-white rounded-2xl p-5 shadow-sm border border-blue-100 hover:-translate-y-1 hover:shadow-md transition-all duration-300 relative overflow-hidden">
                    <div class="absolute right-0 bottom-0 translate-x-3 translate-y-3 opacity-15">
                        <Users class="w-24 h-24 text-white" />
                    </div>
                    <div class="flex justify-between items-start">
                        <div class="p-2 bg-white/10 rounded-xl">
                            <Users class="w-5 h-5 text-white" />
                        </div>
                        <span class="text-xs font-bold bg-white/20 px-2 py-0.5 rounded-full">Académico</span>
                    </div>
                    <div class="mt-4">
                        <h3 class="text-3xl font-black">{{ safeStats.estudiante_kpis?.mis_grupos_count ?? 0 }}</h3>
                        <p class="text-xs text-blue-100 font-medium mt-1">Mis Grupos Inscritos</p>
                    </div>
                    <div
                        class="mt-4 pt-3 border-t border-white/10 text-xs font-semibold text-blue-50 flex items-center gap-1">
                        <Users class="w-3.5 h-3.5" />
                        <span>Secciones Académicas</span>
                    </div>
                </div>

                <!-- Mis Materias -->
                <div
                    class="group bg-gradient-to-br from-purple-500 to-indigo-650 text-white rounded-2xl p-5 shadow-sm border border-purple-100 hover:-translate-y-1 hover:shadow-md transition-all duration-300 relative overflow-hidden">
                    <div class="absolute right-0 bottom-0 translate-x-3 translate-y-3 opacity-15">
                        <BookOpen class="w-24 h-24 text-white" />
                    </div>
                    <div class="flex justify-between items-start">
                        <div class="p-2 bg-white/10 rounded-xl">
                            <BookOpen class="w-5 h-5 text-white" />
                        </div>
                        <span class="text-xs font-bold bg-white/20 px-2 py-0.5 rounded-full">Curriculum</span>
                    </div>
                    <div class="mt-4">
                        <h3 class="text-3xl font-black">{{ safeStats.estudiante_kpis?.mis_materias_count ?? 0 }}</h3>
                        <p class="text-xs text-purple-100 font-medium mt-1">Asignaturas en Curso</p>
                    </div>
                    <div
                        class="mt-4 pt-3 border-t border-white/10 text-xs font-semibold text-purple-50 flex items-center gap-1">
                        <CheckCircle2 class="w-3.5 h-3.5" />
                        <span>Malla Curricular Activa</span>
                    </div>
                </div>

                <!-- Laboratorios Disponibles -->
                <div
                    class="group bg-gradient-to-br from-emerald-500 to-teal-650 text-white rounded-2xl p-5 shadow-sm border border-emerald-100 hover:-translate-y-1 hover:shadow-md transition-all duration-300 relative overflow-hidden">
                    <div class="absolute right-0 bottom-0 translate-x-3 translate-y-3 opacity-15">
                        <Database class="w-24 h-24 text-white" />
                    </div>
                    <div class="flex justify-between items-start">
                        <div class="p-2 bg-white/10 rounded-xl">
                            <Database class="w-5 h-5 text-white" />
                        </div>
                        <span class="text-xs font-bold bg-white/20 px-2 py-0.5 rounded-full">Entornos</span>
                    </div>
                    <div class="mt-4">
                        <h3 class="text-3xl font-black">{{ safeStats.estudiante_kpis?.laboratorios_activos_count ?? 0 }}
                        </h3>
                        <p class="text-xs text-emerald-100 font-medium mt-1">Laboratorios Operativos</p>
                    </div>
                    <div
                        class="mt-4 pt-3 border-t border-white/10 text-xs font-semibold text-emerald-50 flex items-center gap-1">
                        <Laptop class="w-3.5 h-3.5" />
                        <span>Acceso a Estaciones</span>
                    </div>
                </div>
            </div>

            <!-- Grilla Principal adaptativa por Rol -->

            <!-- Layout 1. Para Administrador (Vista Clásica Completa 70% / 30%) -->
            <div v-if="isAdmin" class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Columna Izquierda: Ocupación de Labs & Horarios (70% o 2/3 de ancho) -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- Salud y Ocupación de los Laboratorios -->
                    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-6">
                        <div class="flex items-center justify-between mb-5">
                            <h2 class="text-lg font-bold text-slate-800 flex items-center gap-2">
                                <Database class="w-5 h-5 text-teal-650" />
                                Estado de Salud de Laboratorios
                            </h2>
                            <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Monitoreo
                                Físico</span>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                            <div v-for="lab in safeStats.laboratorios.status_list" :key="lab.id"
                                class="border border-slate-100 hover:border-slate-200 hover:bg-slate-50/50 rounded-xl p-4 transition-all duration-200 flex flex-col justify-between">
                                <div>
                                    <div class="flex items-center justify-between mb-2">
                                        <h3 class="font-extrabold text-slate-800 text-sm flex items-center gap-1.5">
                                            <span class="w-2.5 h-2.5 rounded-full shadow-3xs"
                                                :class="lab.activo ? 'bg-emerald-500 border-2 border-emerald-100 animate-pulse' : 'bg-rose-400 border-2 border-rose-100'">
                                            </span>
                                            {{ lab.nombre }}
                                        </h3>
                                        <span
                                            class="text-3xs font-semibold px-2 py-0.5 bg-slate-100 text-slate-500 rounded-full border border-slate-150 uppercase tracking-tighter">
                                            {{ lab.pabellon }}
                                        </span>
                                    </div>
                                    <p class="text-xs text-slate-400 flex items-center gap-1 font-medium mb-4">
                                        <MapPin class="w-3.5 h-3.5" />
                                        Piso {{ lab.piso }}
                                    </p>
                                </div>

                                <!-- Barra de Salud / Estaciones en Línea -->
                                <div class="space-y-2">
                                    <div class="flex items-center justify-between text-xs font-semibold text-slate-655">
                                        <span>PC en Línea:</span>
                                        <span class="font-mono text-slate-900">
                                            {{ lab.estaciones_activas_count }} / {{ lab.estaciones_count }}
                                        </span>
                                    </div>
                                    <div
                                        class="w-full bg-slate-100 rounded-full h-2 overflow-hidden flex border border-slate-150/50">
                                        <div class="bg-gradient-to-r from-emerald-400 to-teal-500 h-2 transition-all duration-500"
                                            :style="{ width: lab.estaciones_count > 0 ? `${(lab.estaciones_activas_count / lab.estaciones_count) * 100}%` : '0%' }">
                                        </div>
                                    </div>
                                    <div class="flex justify-between items-center text-[10px] text-slate-400 font-bold">
                                        <span>Operatividad:</span>
                                        <span :class="lab.estaciones_count > 0 && (lab.estaciones_activas_count / lab.estaciones_count) >= 0.8 ? 'text-emerald-600' : 'text-amber-600'">
                                            {{ lab.estaciones_count > 0 
                                                ? ((lab.estaciones_activas_count / lab.estaciones_count) * 100).toFixed(2)
                                                : '0.00' 
                                            }}%
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Horarios / Clases Programadas para Hoy -->
                    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-6">
                        <div class="flex items-center justify-between mb-5">
                            <div>
                                <h2 class="text-lg font-bold text-slate-800 flex items-center gap-2">
                                    <Clock class="w-5 h-5 text-blue-600" />
                                    Cronograma de Clases para Hoy
                                </h2>
                                <p class="text-xs text-slate-400 font-medium mt-0.5">Schedules activos hoy ({{
                                    getDayName(new Date().getDay() || 7) }})</p>
                            </div>
                            <span
                                class="text-xs font-bold text-blue-600 bg-blue-50/80 border border-blue-150 px-2.5 py-1 rounded-lg">Hoy</span>
                        </div>

                        <!-- Lista de Clases -->
                        <div v-if="safeStats.horarios_hoy.length === 0"
                            class="border-2 border-dashed border-slate-150 rounded-2xl p-12 text-center text-slate-400 italic">
                            <Clock class="w-10 h-10 text-slate-250 mx-auto mb-3" />
                            <p class="font-bold text-slate-650 text-sm">Sin clases agendadas hoy</p>
                            <p class="text-xs text-slate-450 mt-1 max-w-xs mx-auto">No hay registros de horarios
                                programados para esta jornada académica.</p>
                        </div>

                        <div v-else class="space-y-4">
                            <div v-for="horario in safeStats.horarios_hoy" :key="horario.id"
                                class="group bg-slate-50 border border-slate-100 hover:border-blue-200 hover:bg-blue-50/10 rounded-xl p-4 transition-all duration-200 flex flex-col md:flex-row md:items-center justify-between gap-4">
                                <div class="flex items-start gap-4">
                                    <!-- Reloj e Icono de Horario -->
                                    <div
                                        class="p-3 bg-white text-blue-600 border border-slate-150 rounded-xl shrink-0 shadow-3xs flex flex-col items-center justify-center w-14 h-14 font-mono font-black text-xs leading-tight">
                                        <span>{{ horario.hora_inicio }}</span>
                                        <span class="text-[9px] font-bold text-slate-400 mt-0.5">{{ horario.hora_fin
                                            }}</span>
                                    </div>

                                    <div class="space-y-1">
                                        <h3
                                            class="font-bold text-slate-900 text-sm group-hover:text-blue-700 transition-colors">
                                            {{ horario.grupo?.materia?.nombre ?? 'Sin Asignatura' }}
                                        </h3>
                                        <div
                                            class="flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-slate-500 font-semibold">
                                            <span
                                                class="font-mono text-3xs bg-slate-200/60 text-slate-600 px-1.5 py-0.5 rounded border border-slate-250 uppercase font-black">
                                                {{ horario.grupo?.materia?.codigo ?? 'COD' }}
                                            </span>
                                            <span class="flex items-center gap-1">
                                                <Users class="w-3.5 h-3.5 text-slate-400" />
                                                Grupo: {{ horario.grupo?.nombre ?? 'A' }}
                                            </span>
                                            <span class="flex items-center gap-1">
                                                <GraduationCap class="w-3.5 h-3.5 text-slate-400" />
                                                Docente: {{ horario.docente?.name ?? 'Sin asignar' }}
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Badge de Laboratorio asignado -->
                                <div class="flex items-center gap-2 shrink-0 md:self-center">
                                    <div
                                        class="bg-white border border-slate-150 rounded-lg py-1.5 px-3 flex items-center gap-1.5 shadow-3xs">
                                        <Database class="w-3.5 h-3.5 text-teal-600" />
                                        <div class="text-left font-bold">
                                            <p class="text-3xs text-slate-400 leading-none uppercase">Laboratorio</p>
                                            <p class="text-xs text-slate-800 font-extrabold leading-tight mt-0.5">
                                                {{ horario.laboratorio?.nombre ?? 'General' }}
                                            </p>
                                        </div>
                                    </div>
                                    <div
                                        class="w-7 h-7 bg-slate-200 hover:bg-blue-100 hover:text-blue-600 rounded-full flex items-center justify-center text-slate-455 transition-colors pointer-events-none">
                                        <ArrowRight class="w-4 h-4" />
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Columna Derecha: Widget de Alertas en Tiempo Real (30% de ancho) -->
                <div class="space-y-6">
                    <div
                        class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-6 flex flex-col h-full justify-between">
                        <div>
                            <div class="flex items-center justify-between mb-5">
                                <h2 class="text-lg font-bold text-slate-800 flex items-center gap-2">
                                    <AlertTriangle class="w-5 h-5 text-red-650" />
                                    Alertas Críticas
                                </h2>
                                <span
                                    class="text-xs font-black text-red-600 bg-red-50 border border-red-150 px-2 py-0.5 rounded-full uppercase animate-pulse">
                                    {{ safeStats.alertas.pendientes }} Pendientes
                                </span>
                            </div>

                            <!-- Listado de Alertas -->
                            <div v-if="safeStats.alertas.recientes.length === 0"
                                class="border-2 border-dashed border-slate-150 rounded-xl p-12 text-center text-slate-400 italic">
                                <CheckCircle2 class="w-10 h-10 text-emerald-200 mx-auto mb-3" />
                                <p class="font-bold text-emerald-700 text-sm">Monitoreo Saludable</p>
                                <p class="text-3xs text-slate-400 mt-1">Todas las estaciones operan dentro de los
                                    umbrales estipulados.</p>
                            </div>

                            <div v-else class="space-y-4">
                                <div v-for="alerta in safeStats.alertas.recientes" :key="alerta.id"
                                    class="border border-slate-200 hover:border-red-300 bg-slate-50/50 rounded-xl p-4 transition-all duration-200 flex flex-col justify-between relative border-l-4 border-l-red-500">
                                    <div class="space-y-3">
                                        <div class="flex items-start justify-between">
                                            <div class="flex items-center gap-2">
                                                <div class="p-1.5 bg-red-50 text-red-600 rounded-lg">
                                                    <component :is="getMetricIcon(alerta.configuracion_alerta?.metrica)"
                                                        class="w-4 h-4" />
                                                </div>
                                                <div>
                                                    <h4 class="font-black text-slate-800 text-xs leading-tight">
                                                        {{ getFriendlyMetricName(alerta.configuracion_alerta?.metrica)
                                                        }}
                                                    </h4>
                                                    <p
                                                        class="text-[9px] text-slate-400 font-mono tracking-tighter uppercase mt-0.5">
                                                        {{ alerta.configuracion_alerta?.metrica }} {{
                                                            alerta.configuracion_alerta?.operador }} {{
                                                            formatThreshold(alerta.configuracion_alerta?.metrica,
                                                                alerta.configuracion_alerta?.valor_umbral) }}
                                                    </p>
                                                </div>
                                            </div>

                                            <span :class="[
                                                'text-[9px] font-black px-1.5 py-0.5 rounded-md border uppercase shadow-3xs',
                                                alerta.configuracion_alerta?.severidad === 'critica' ? 'bg-red-50 text-red-700 border-red-200' :
                                                    alerta.configuracion_alerta?.severidad === 'alta' ? 'bg-orange-50 text-orange-700 border-orange-200' :
                                                        alerta.configuracion_alerta?.severidad === 'media' ? 'bg-amber-50 text-amber-700 border-amber-200' :
                                                            'bg-blue-50 text-blue-700 border-blue-200'
                                            ]">
                                                {{ alerta.configuracion_alerta?.severidad }}
                                            </span>
                                        </div>

                                        <!-- Detalles de la PC -->
                                        <div
                                            class="bg-white border border-slate-150 rounded-lg p-2.5 text-[11px] space-y-1 font-semibold text-slate-500">
                                            <div class="flex items-center justify-between">
                                                <span>Estación:</span>
                                                <span class="text-slate-900 font-extrabold font-mono">{{
                                                    alerta.estacion?.hostname ?? 'N/A' }}</span>
                                            </div>
                                            <div class="flex items-center justify-between">
                                                <span>Dirección IP:</span>
                                                <span class="text-slate-655 font-mono">{{ alerta.estacion?.direccion_ip
                                                    ?? 'N/A' }}</span>
                                            </div>
                                            <div
                                                class="flex items-center justify-between pt-1 border-t border-slate-100">
                                                <span>Registrado:</span>
                                                <span class="text-red-600 font-extrabold font-mono text-xs">
                                                    {{ formatThreshold(alerta.configuracion_alerta?.metrica,
                                                        alerta.valor_actual) }}
                                                </span>
                                            </div>
                                        </div>

                                        <p class="text-[9px] text-slate-400 font-mono">
                                            REPORTADO: {{ new Date(alerta.created_at).toLocaleTimeString() }}
                                        </p>
                                    </div>

                                    <!-- Acción rápida de resolver -->
                                    <div class="pt-3 mt-3 border-t border-slate-100">
                                        <button @click="handleResolveAlerta(alerta)"
                                            :disabled="isSavingId === alerta.id"
                                            class="w-full inline-flex items-center justify-center px-3 py-1.5 bg-emerald-600 hover:bg-emerald-700 active:bg-emerald-800 disabled:opacity-50 text-[10px] font-bold rounded-lg text-white shadow-xs transition-colors cursor-pointer gap-1">
                                            <Loader2 v-if="isSavingId === alerta.id" class="w-3.5 h-3.5 animate-spin" />
                                            <CheckCircle2 v-else class="w-3.5 h-3.5" />
                                            <span>Marcar Resuelta</span>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Botón de acceso completo al modulo de monitoreo -->
                        <div class="mt-6 pt-4 border-t border-slate-150 shrink-0">
                            <router-link to="/monitoring"
                                class="w-full inline-flex items-center justify-center bg-slate-100 hover:bg-blue-50 hover:text-blue-600 border border-slate-200 hover:border-blue-200 text-slate-700 font-bold py-2.5 px-4 rounded-xl transition-all text-xs">
                                Ver Todo el Canal de Alertas
                            </router-link>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Layout 2. Para No-Admin (Docente y Estudiante) -->
            <div v-else :class="isDocente ? 'grid grid-cols-1 lg:grid-cols-2 gap-6' : 'max-w-4xl mx-auto w-full'">
                <!-- Columna Izquierda: Mis Clases / Mi Horario -->
                <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-6 space-y-5">
                    <div class="flex items-center justify-between pb-2 border-b border-slate-100">
                        <div>
                            <h2 class="text-lg font-bold text-slate-800 flex items-center gap-2">
                                <Clock class="w-5 h-5 text-blue-600" />
                                {{ isDocente ? 'Mis Clases para Hoy' : 'Mi Horario de Clases' }}
                            </h2>
                            <p class="text-xs text-slate-400 font-semibold mt-0.5">
                                {{ isDocente ? 'Itinerario de asignaturas a dictar durante esta jornada' : 'Clases programadas para tus materias inscritas' }}
                            </p>
                        </div>
                        <span
                            class="text-xs font-black text-blue-600 bg-blue-50 border border-blue-150 px-2.5 py-1 rounded-lg">Hoy</span>
                    </div>

                    <!-- Lista de Clases -->
                    <div v-if="safeStats.horarios_hoy.length === 0"
                        class="border-2 border-dashed border-slate-150 rounded-2xl p-12 text-center text-slate-400 italic">
                        <Clock class="w-10 h-10 text-slate-250 mx-auto mb-3" />
                        <p class="font-bold text-slate-655 text-sm">Sin clases programadas para hoy</p>
                        <p class="text-xs text-slate-400 mt-1 max-w-xs mx-auto">No tienes registros de horarios
                            agendados para la jornada de hoy.</p>
                    </div>

                    <div v-else class="space-y-4">
                        <div v-for="horario in safeStats.horarios_hoy" :key="horario.id"
                            class="group bg-slate-50 border border-slate-100 hover:border-blue-200 hover:bg-blue-50/10 rounded-xl p-4 transition-all duration-200 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                            <div class="flex items-start gap-4">
                                <!-- Hora de Clases -->
                                <div
                                    class="p-3 bg-white text-blue-600 border border-slate-150 rounded-xl shrink-0 shadow-3xs flex flex-col items-center justify-center w-14 h-14 font-mono font-black text-xs leading-tight">
                                    <span>{{ horario.hora_inicio }}</span>
                                    <span class="text-[9px] font-bold text-slate-400 mt-0.5">{{ horario.hora_fin
                                    }}</span>
                                </div>

                                <div class="space-y-1">
                                    <h3
                                        class="font-bold text-slate-900 text-sm group-hover:text-blue-700 transition-colors">
                                        {{ horario.grupo?.materia?.nombre ?? 'Sin Asignatura' }}
                                    </h3>
                                    <div
                                        class="flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-slate-500 font-semibold">
                                        <span
                                            class="font-mono text-3xs bg-slate-200/60 text-slate-600 px-1.5 py-0.5 rounded border border-slate-250 uppercase font-black">
                                            {{ horario.grupo?.materia?.codigo ?? 'COD' }}
                                        </span>
                                        <span class="flex items-center gap-1">
                                            <Users class="w-3.5 h-3.5 text-slate-400" />
                                            Grupo: {{ horario.grupo?.nombre ?? 'A' }}
                                        </span>
                                        <span v-if="isEstudiante" class="flex items-center gap-1">
                                            <GraduationCap class="w-3.5 h-3.5 text-slate-400" />
                                            Docente: {{ horario.docente?.name ?? 'Sin asignar' }}
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <!-- Laboratorio asignado -->
                            <div class="flex items-center gap-2 shrink-0 sm:self-center">
                                <div
                                    class="bg-white border border-slate-150 rounded-lg py-1.5 px-3 flex items-center gap-1.5 shadow-3xs">
                                    <Database class="w-3.5 h-3.5 text-teal-650" />
                                    <div class="text-left font-bold">
                                        <p class="text-3xs text-slate-400 leading-none uppercase">Laboratorio</p>
                                        <p class="text-xs text-slate-800 font-extrabold leading-tight mt-0.5">
                                            {{ horario.laboratorio?.nombre ?? 'General' }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Columna Derecha: Disponibilidad de Laboratorios (Solo para Docentes) -->
                <div v-if="isDocente" class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-6 space-y-5">
                    <div class="flex items-center justify-between pb-2 border-b border-slate-100">
                        <div>
                            <h2 class="text-lg font-bold text-slate-800 flex items-center gap-2">
                                <Database class="w-5 h-5 text-emerald-600" />
                                {{ isDocente ? 'Estado de Laboratorios Asignados' : 'Laboratorios para Estudio Libre' }}
                            </h2>
                            <p class="text-xs text-slate-400 font-semibold mt-0.5">
                                {{ isDocente ? 'Infraestructura física disponible para sus clases' : 'Espacios académicos activos con equipos disponibles' }}
                            </p>
                        </div>
                        <span
                            class="text-xs font-black text-emerald-700 bg-emerald-50 border border-emerald-150 px-2.5 py-1 rounded-lg">Activos</span>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div v-for="lab in safeStats.laboratorios.status_list" :key="lab.id"
                            class="border border-slate-100 hover:border-slate-250 hover:bg-slate-50/50 rounded-xl p-4 transition-all duration-200 flex flex-col justify-between">
                            <div>
                                <div class="flex items-center justify-between mb-2">
                                    <h3 class="font-extrabold text-slate-800 text-sm flex items-center gap-1.5">
                                        <span
                                            class="w-2.5 h-2.5 rounded-full shadow-3xs bg-emerald-500 border-2 border-emerald-100 animate-pulse"></span>
                                        {{ lab.nombre }}
                                    </h3>
                                    <span
                                        class="text-3xs font-semibold px-2 py-0.5 bg-slate-100 text-slate-500 rounded-full border border-slate-150 uppercase tracking-tighter">
                                        {{ lab.pabellon }}
                                    </span>
                                </div>
                                <p class="text-xs text-slate-400 flex items-center gap-1 font-medium mb-4">
                                    <MapPin class="w-3.5 h-3.5" />
                                    Piso {{ lab.piso }}
                                </p>
                            </div>

                            <!-- Barra de Salud / Estaciones en Línea -->
                            <div class="space-y-2">
                                <div class="flex items-center justify-between text-xs font-semibold text-slate-655">
                                    <span>Equipos en Línea:</span>
                                    <span class="font-mono text-slate-900">
                                        {{ lab.estaciones_activas_count }} / {{ lab.estaciones_count }}
                                    </span>
                                </div>
                                <div
                                    class="w-full bg-slate-100 rounded-full h-2 overflow-hidden flex border border-slate-150/50">
                                    <div class="bg-gradient-to-r from-emerald-400 to-teal-500 h-2 transition-all duration-500"
                                        :style="{ width: lab.estaciones_count > 0 ? `${(lab.estaciones_activas_count / lab.estaciones_count) * 100}%` : '0%' }">
                                    </div>
                                </div>
                                <div class="flex justify-between items-center text-[10px] text-slate-400 font-bold">
                                    <span>Capacidad Libre:</span>
                                    <span class="text-emerald-600 font-black">
                                        {{ lab.estaciones_count > 0 ? Math.round((lab.estaciones_activas_count /
                                            lab.estaciones_count) * 100) : 0 }}%
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </template>
    </div>
</template>