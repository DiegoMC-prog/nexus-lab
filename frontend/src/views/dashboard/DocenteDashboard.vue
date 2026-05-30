<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '@/stores/auth';
import { docenteDashboardService } from '@/services/docenteDashboardService';
import type { CronogramaItem } from '@/types/docenteDashboard';
import { 
    BookOpen, Users, Clock, Database, Calendar, 
    MapPin, ArrowRight, Activity, RefreshCw, Loader2 
} from '@lucide/vue';

// --- ESTADOS REACTIVOS ---
const authStore = useAuthStore();
const router = useRouter();

const kpis = ref({
    materias_asignadas: 0,
    grupos_a_cargo: 0,
    clases_hoy: 0,
    laboratorios_reservados: 0
});

const cronogramaHoy = ref<CronogramaItem[]>([]);
const isLoading = ref(true);
const isRefreshing = ref(false);
const errorMsg = ref('');

// --- CARGA DE DATOS ---
const loadDashboardData = async (silent = false) => {
    if (!silent) isLoading.value = true;
    else isRefreshing.value = true;
    errorMsg.value = '';

    try {
        const res = await docenteDashboardService.getDashboardData();
        kpis.value = res.kpis;
        cronogramaHoy.value = res.cronograma_hoy;
    } catch (error: any) {
        console.error('Error al cargar dashboard del docente:', error);
        errorMsg.value = 'No se pudo sincronizar la información del docente. Inténtelo de nuevo.';
    } finally {
        isLoading.value = false;
        isRefreshing.value = false;
    }
};

onMounted(() => {
    loadDashboardData();
});

// --- HELPERS ---
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

const getDayName = () => {
    const days = ['Domingo', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'];
    return days[new Date().getDay()];
};

const navigateToMonitoring = () => {
    router.push('/laboratorio-vivo');
};
</script>

<template>
    <div class="p-6 space-y-6 bg-slate-50 min-h-screen">
        <!-- Loader inicial -->
        <div v-if="isLoading && kpis.materias_asignadas === 0" class="flex flex-col items-center justify-center min-h-[70vh] space-y-4">
            <div class="relative flex items-center justify-center">
                <div class="w-16 h-16 border-4 border-blue-100 border-t-blue-600 rounded-full animate-spin"></div>
                <Activity class="w-6 h-6 text-blue-600 absolute animate-pulse" />
            </div>
            <p class="text-sm font-semibold text-slate-500 animate-pulse">Sincronizando información académica del docente...</p>
        </div>

        <template v-else>
            <!-- Cabecera de Bienvenida y Acción de Refresco -->
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 bg-white p-6 rounded-2xl border border-slate-200/80 shadow-xs relative overflow-hidden">
                <div class="absolute right-0 top-0 -mt-6 -mr-6 w-32 h-32 bg-blue-50/50 rounded-full blur-3xl pointer-events-none"></div>
                
                <div class="space-y-1 z-10">
                    <h1 class="text-2xl font-black text-slate-900 leading-tight flex flex-wrap items-center gap-2">
                        {{ getGreeting() }}, <span class="text-blue-600">{{ authStore.user?.name }}</span>!
                        <span class="inline-flex items-center gap-1.5 px-3 py-0.5 text-xs font-black rounded-full border border-orange-100 bg-orange-50 text-orange-700 uppercase tracking-wider">
                            Docente
                        </span>
                    </h1>
                    <p class="text-sm text-slate-500 font-medium flex items-center gap-1.5">
                        <Calendar class="w-4 h-4 text-slate-400" />
                        {{ getCurrentDateString() }}
                    </p>
                </div>
                
                <div class="flex items-center gap-3 z-10 shrink-0">
                    <button 
                        @click="navigateToMonitoring"
                        class="inline-flex items-center justify-center bg-blue-600 hover:bg-blue-700 text-white font-extrabold py-2.5 px-5 rounded-xl shadow-md shadow-blue-600/10 gap-2 transition-all text-xs cursor-pointer"
                    >
                        <Activity class="w-4 h-4 animate-pulse" />
                        Monitorear Clase en Vivo
                    </button>
                    
                    <button 
                        @click="loadDashboardData(true)" 
                        :disabled="isRefreshing"
                        class="inline-flex items-center justify-center bg-white hover:bg-slate-50 text-slate-700 font-bold py-2.5 px-4 border border-slate-200 rounded-xl shadow-xs gap-2 transition-all text-xs cursor-pointer disabled:opacity-50"
                    >
                        <RefreshCw class="w-3.5 h-3.5" :class="{ 'animate-spin': isRefreshing }" />
                        Refrescar
                    </button>
                </div>
            </div>

            <!-- Error Banner -->
            <div v-if="errorMsg" class="bg-red-50 border border-red-200 text-red-750 px-4 py-3 rounded-xl text-sm font-semibold">
                {{ errorMsg }}
            </div>

            <!-- KPI Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
                <!-- Materias Asignadas -->
                <div class="group bg-gradient-to-br from-blue-500 to-indigo-600 text-white rounded-2xl p-5 shadow-sm hover:-translate-y-1 hover:shadow-md transition-all duration-300 relative overflow-hidden">
                    <div class="absolute right-0 bottom-0 translate-x-3 translate-y-3 opacity-15">
                        <BookOpen class="w-24 h-24 text-white" />
                    </div>
                    <div class="flex justify-between items-start">
                        <div class="p-2 bg-white/10 rounded-xl">
                            <BookOpen class="w-5 h-5 text-white" />
                        </div>
                        <span class="text-xs font-bold bg-white/20 px-2 py-0.5 rounded-full">Materias</span>
                    </div>
                    <div class="mt-4">
                        <h3 class="text-3xl font-black">{{ kpis.materias_asignadas }}</h3>
                        <p class="text-xs text-blue-100 font-medium mt-1">Materias Asignadas</p>
                    </div>
                    <div class="mt-4 pt-3 border-t border-white/10 text-xs font-semibold text-blue-50 flex items-center gap-1.5">
                        <Activity class="w-3.5 h-3.5" />
                        <span>Plan Curricular Vigente</span>
                    </div>
                </div>

                <!-- Grupos a Cargo -->
                <div class="group bg-gradient-to-br from-teal-500 to-emerald-600 text-white rounded-2xl p-5 shadow-sm hover:-translate-y-1 hover:shadow-md transition-all duration-300 relative overflow-hidden">
                    <div class="absolute right-0 bottom-0 translate-x-3 translate-y-3 opacity-15">
                        <Users class="w-24 h-24 text-white" />
                    </div>
                    <div class="flex justify-between items-start">
                        <div class="p-2 bg-white/10 rounded-xl">
                            <Users class="w-5 h-5 text-white" />
                        </div>
                        <span class="text-xs font-bold bg-white/20 px-2 py-0.5 rounded-full">Grupos</span>
                    </div>
                    <div class="mt-4">
                        <h3 class="text-3xl font-black">{{ kpis.grupos_a_cargo }}</h3>
                        <p class="text-xs text-teal-150 font-medium mt-1">Grupos a Cargo</p>
                    </div>
                    <div class="mt-4 pt-3 border-t border-white/10 text-xs font-semibold text-teal-50 flex items-center gap-1.5">
                        <Users class="w-3.5 h-3.5" />
                        <span>Estudiantes Inscritos</span>
                    </div>
                </div>

                <!-- Clases de Hoy -->
                <div class="group bg-gradient-to-br from-orange-500 to-amber-600 text-white rounded-2xl p-5 shadow-sm hover:-translate-y-1 hover:shadow-md transition-all duration-300 relative overflow-hidden">
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
                        <h3 class="text-3xl font-black">{{ kpis.clases_hoy }}</h3>
                        <p class="text-xs text-orange-100 font-medium mt-1">Clases de Hoy</p>
                    </div>
                    <div class="mt-4 pt-3 border-t border-white/10 text-xs font-semibold text-orange-50 flex items-center gap-1.5">
                        <Calendar class="w-3.5 h-3.5" />
                        <span>{{ getDayName() }}</span>
                    </div>
                </div>

                <!-- Laboratorios Reservados -->
                <div class="group bg-gradient-to-br from-indigo-500 to-purple-600 text-white rounded-2xl p-5 shadow-sm hover:-translate-y-1 hover:shadow-md transition-all duration-300 relative overflow-hidden">
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
                        <h3 class="text-3xl font-black">{{ kpis.laboratorios_reservados }}</h3>
                        <p class="text-xs text-indigo-100 font-medium mt-1">Laboratorios Reservados</p>
                    </div>
                    <div class="mt-4 pt-3 border-t border-white/10 text-xs font-semibold text-indigo-50 flex items-center gap-1.5">
                        <MapPin class="w-3.5 h-3.5" />
                        <span>Ubicaciones Físicas</span>
                    </div>
                </div>
            </div>

            <!-- Sección de Cronograma Diario -->
            <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-6">
                <div class="flex items-center justify-between mb-6">
                    <div>
                        <h2 class="text-lg font-bold text-slate-800 flex items-center gap-2">
                            <Clock class="w-5 h-5 text-blue-600" />
                            Mi Cronograma de Hoy
                        </h2>
                        <p class="text-xs text-slate-400 font-medium mt-0.5">Clases programadas para la fecha actual ordenadas cronológicamente.</p>
                    </div>
                    <span class="text-xs font-bold text-blue-600 bg-blue-50 border border-blue-150 px-2.5 py-1 rounded-lg">Hoy</span>
                </div>

                <!-- Lista de clases -->
                <div v-if="cronogramaHoy.length === 0" 
                    class="border-2 border-dashed border-slate-150 rounded-2xl p-12 text-center text-slate-400 italic"
                >
                    <Clock class="w-10 h-10 text-slate-250 mx-auto mb-3" />
                    <p class="font-bold text-slate-650 text-sm">Sin clases agendadas para hoy</p>
                    <p class="text-xs text-slate-450 mt-1 max-w-xs mx-auto">No tiene clases programadas en ningún laboratorio para la jornada actual.</p>
                </div>

                <div v-else class="space-y-4">
                    <div 
                        v-for="clase in cronogramaHoy" 
                        :key="clase.id"
                        class="group bg-slate-50 border border-slate-100 hover:border-blue-200 hover:bg-blue-50/10 rounded-xl p-4 transition-all duration-200 flex flex-col md:flex-row md:items-center justify-between gap-4"
                    >
                        <div class="flex items-start gap-4">
                            <!-- Reloj e Icono de Horario -->
                            <div class="p-3 bg-white text-blue-600 border border-slate-150 rounded-xl shrink-0 shadow-3xs flex flex-col items-center justify-center w-14 h-14 font-mono font-black text-xs leading-tight">
                                <span>{{ clase.hora_inicio }}</span>
                                <span class="text-[9px] font-bold text-slate-400 mt-0.5">{{ clase.hora_fin }}</span>
                            </div>
                            
                            <div class="space-y-1">
                                <h3 class="font-bold text-slate-900 text-sm group-hover:text-blue-700 transition-colors">
                                    {{ clase.materia?.nombre ?? 'Sin Asignatura' }}
                                </h3>
                                <div class="flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-slate-500 font-semibold">
                                    <span class="font-mono text-3xs bg-slate-200/60 text-slate-600 px-1.5 py-0.5 rounded border border-slate-250 uppercase font-black">
                                        {{ clase.materia?.codigo ?? 'COD' }}
                                    </span>
                                    <span class="flex items-center gap-1">
                                        <Users class="w-3.5 h-3.5 text-slate-400" />
                                        Grupo: {{ clase.grupo?.nombre ?? 'A' }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Ubicación y Enlace de Monitoreo -->
                        <div class="flex items-center gap-3 shrink-0 md:self-center">
                            <div class="bg-white border border-slate-150 rounded-lg py-1.5 px-3 flex items-center gap-1.5 shadow-3xs">
                                <Database class="w-3.5 h-3.5 text-teal-600" />
                                <div class="text-left font-bold">
                                    <p class="text-3xs text-slate-400 leading-none uppercase">Ubicación</p>
                                    <p class="text-xs text-slate-800 font-extrabold leading-tight mt-0.5">
                                        {{ clase.laboratorio?.nombre ?? 'N/A' }} 
                                        <span class="text-3xs text-slate-400 font-medium font-sans">
                                            (Pab. {{ clase.laboratorio?.pabellon ?? '-' }}, Piso {{ clase.laboratorio?.piso ?? '-' }})
                                        </span>
                                    </p>
                                </div>
                            </div>
                            
                            <button 
                                @click="navigateToMonitoring"
                                class="w-8 h-8 bg-slate-100 group-hover:bg-blue-600 group-hover:text-white rounded-full flex items-center justify-center text-slate-455 transition-colors cursor-pointer"
                            >
                                <ArrowRight class="w-4 h-4" />
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </template>
    </div>
</template>
