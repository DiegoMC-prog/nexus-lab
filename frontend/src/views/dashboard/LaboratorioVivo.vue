<script setup lang="ts">
import { ref, onMounted, onUnmounted, computed } from 'vue';
import { useRouter } from 'vue-router';
import { docenteDashboardService } from '@/services/docenteDashboardService';
import type { LiveEstacion, LiveInfraccion, LiveClaseInfo } from '@/types/docenteDashboard';
import EstacionPantallaModal from './EstacionPantallaModal.vue';
import { 
    Cpu, Database, Clock, Users, Monitor, 
    RefreshCw, ArrowLeft, Activity, ShieldAlert, CheckCircle2,
    Lock, LockOpen, LogOut
} from '@lucide/vue';

// --- ESTADOS REACTIVOS ---
const router = useRouter();
const claseActiva = ref<boolean>(false);
const isLoading = ref(true);
const isPolling = ref(false);
const errorMsg = ref('');

const infoClase = ref<LiveClaseInfo | null>(null);
const estaciones = ref<LiveEstacion[]>([]);
const infracciones = ref<LiveInfraccion[]>([]);

// Control de modal de pantalla de agente
const isScreenModalOpen = ref(false);
const selectedEstacionForScreen = ref<LiveEstacion | null>(null);

// Mapeo de vistas de pantalla de PCs (desktop | start_menu | file_explorer)
const pcScreenViews = ref<Record<number, 'desktop' | 'start_menu' | 'file_explorer'>>({});

// Estado de bloqueo persistente simulado localmente para feedback inmediato de UI
const lockedPCs = ref<Record<number, boolean>>({});

// Acciones sobre estaciones
const openScreenSimulationModal = (pc: LiveEstacion) => {
    selectedEstacionForScreen.value = pc;
    isScreenModalOpen.value = true;
};

const ejecutarAccion = async (pc: LiveEstacion, accion: 'bloquear' | 'desbloquear' | 'cerrar_sesion') => {
    try {
        await docenteDashboardService.ejecutarAccionEstacion(pc.id, accion);
        
        // Simular feedback de interfaz de usuario
        if (accion === 'bloquear') {
            lockedPCs.value[pc.id] = true;
        } else if (accion === 'desbloquear') {
            lockedPCs.value[pc.id] = false;
        } else if (accion === 'cerrar_sesion') {
            // Desconectar estudiante en la UI localmente de inmediato
            pc.estudiante = null;
        }
    } catch (err) {
        console.error(`Error al ejecutar acción remota "${accion}" en la estación ${pc.hostname}:`, err);
    }
};

const getScreenImageForPC = (pcId: number) => {
    const view = pcScreenViews.value[pcId] || 'desktop';
    switch (view) {
        case 'start_menu': return '/sim_start_menu.png';
        case 'file_explorer': return '/sim_file_explorer.png';
        case 'desktop':
        default:
            return '/sim_desktop.png';
    }
};

let pollingInterval: ReturnType<typeof setInterval> | null = null;
let screenTransitionInterval: ReturnType<typeof setInterval> | null = null;

// --- OBTENCION DE DATOS ---
const fetchLiveClassData = async (silent = false) => {
    if (!silent) isLoading.value = true;
    else isPolling.value = true;
    errorMsg.value = '';

    try {
        const res = await docenteDashboardService.getClaseActivaRealTime();
        if (res.clase_activa) {
            claseActiva.value = true;
            infoClase.value = {
                grupo: res.grupo || null,
                laboratorio: res.laboratorio!,
                hora_inicio: res.hora_inicio!,
                hora_fin: res.hora_fin!
            };
            estaciones.value = res.estaciones || [];
            infracciones.value = res.infracciones || [];

            // Inicializar pantallas de PCs y sincronizar estado de bloqueo persistente
            estaciones.value.forEach(pc => {
                // Sincronizar el estado de bloqueo persistente desde el servidor
                if (pc.estado === 'bloqueado') {
                    lockedPCs.value[pc.id] = true;
                } else {
                    lockedPCs.value[pc.id] = false;
                }

                if (pc.estado !== 'Offline' && !pcScreenViews.value[pc.id]) {
                    const views: ('desktop' | 'start_menu' | 'file_explorer')[] = ['desktop', 'start_menu', 'file_explorer'];
                    pcScreenViews.value[pc.id] = views[pc.id % views.length] as 'desktop' | 'start_menu' | 'file_explorer';
                }
            });
        } else {
            claseActiva.value = false;
            infoClase.value = null;
            estaciones.value = [];
            infracciones.value = [];
        }
    } catch (error: any) {
        console.error('Error al recuperar clase en vivo:', error);
        errorMsg.value = 'No se pudo establecer conexión con el servidor para el monitoreo en vivo.';
    } finally {
        isLoading.value = false;
        isPolling.value = false;
    }
};

// --- CICLO DE VIDA (Polling e Intervalos) ---
onMounted(() => {
    fetchLiveClassData();
    
    // Configurar short polling cada 10 segundos
    pollingInterval = setInterval(() => {
        fetchLiveClassData(true);
    }, 10000);

    // Bucle dinámico para transicionar/variar aleatoriamente las pantallas de los estudiantes en vivo
    screenTransitionInterval = setInterval(() => {
        const onlinePCs = estaciones.value.filter(e => e.estado !== 'Offline');
        if (onlinePCs.length > 0) {
            // Modificar la pantalla activa de hasta 3 estaciones simultáneas en cada intervalo
            const count = Math.min(3, onlinePCs.length);
            for (let i = 0; i < count; i++) {
                const pc = onlinePCs[Math.floor(Math.random() * onlinePCs.length)];
                if (!pc) continue;
                const views: ('desktop' | 'start_menu' | 'file_explorer')[] = ['desktop', 'start_menu', 'file_explorer'];
                const current = pcScreenViews.value[pc.id] || 'desktop';
                const next = views.filter(v => v !== current);
                pcScreenViews.value[pc.id] = next[Math.floor(Math.random() * next.length)] as 'desktop' | 'start_menu' | 'file_explorer';
            }
        }
    }, 5000);
});

onUnmounted(() => {
    if (pollingInterval) {
        clearInterval(pollingInterval);
        pollingInterval = null;
    }
    if (screenTransitionInterval) {
        clearInterval(screenTransitionInterval);
        screenTransitionInterval = null;
    }
});

// --- HELPERS ---
const navigateBack = () => {
    router.push('/');
};

// Ordenar estáticamente las estaciones por hostname de forma natural/numérica
const sortedEstaciones = computed(() => {
    return [...estaciones.value].sort((a, b) => 
        a.hostname.localeCompare(b.hostname, undefined, { numeric: true, sensitivity: 'base' })
    );
});

// Estadísticas de la Clase
const activeComputersCount = computed(() => {
    return estaciones.value.filter(e => e.estado !== 'Offline').length;
});

const activeStudentsCount = computed(() => {
    return estaciones.value.filter(e => e.estado !== 'Offline' && e.estudiante).length;
});
</script>

<template>
    <div class="p-6 space-y-6 bg-slate-50 min-h-screen select-none">
        
        <!-- HEADER PRINCIPAL -->
        <div class="flex items-center justify-between border-b border-slate-200 pb-4">
            <div class="flex items-center gap-3">
                <button 
                    @click="navigateBack"
                    class="p-2 bg-white hover:bg-slate-100 text-slate-700 border border-slate-200 rounded-xl shadow-3xs transition-all cursor-pointer"
                >
                    <ArrowLeft class="w-4 h-4" />
                </button>
                <div>
                    <h1 class="text-xl font-black text-slate-900 leading-tight flex items-center gap-2">
                        <Activity class="w-5 h-5 text-blue-600 animate-pulse" />
                        Monitoreo de Clase en Vivo
                    </h1>
                    <p class="text-xs text-slate-400 font-semibold mt-0.5">Control y rendimiento en tiempo real por Short Polling (10s)</p>
                </div>
            </div>
            
            <div class="flex items-center gap-3">
                <div v-if="claseActiva" class="flex items-center gap-1.5 px-3 py-1 text-3xs font-black bg-emerald-50 border border-emerald-100 text-emerald-700 uppercase rounded-full tracking-wider shadow-3xs animate-pulse">
                    <span class="w-1.5 h-1.5 bg-emerald-500 rounded-full"></span>
                    Clase en Curso
                </div>
                <button 
                    @click="fetchLiveClassData(true)"
                    :disabled="isPolling"
                    class="inline-flex items-center justify-center bg-white hover:bg-slate-50 text-slate-700 font-bold py-2 px-3 border border-slate-200 rounded-xl shadow-xs gap-1.5 transition-all text-xs cursor-pointer disabled:opacity-50"
                >
                    <RefreshCw class="w-3.5 h-3.5" :class="{ 'animate-spin': isPolling || isLoading }" />
                    Sincronizar
                </button>
            </div>
        </div>

        <!-- ERROR MESSAGE -->
        <div v-if="errorMsg" class="bg-red-50 border border-red-200 text-red-750 px-4 py-3 rounded-xl text-sm font-semibold flex items-center gap-2">
            <ShieldAlert class="w-4 h-4 text-red-650 shrink-0 animate-bounce" />
            <span>{{ errorMsg }}</span>
        </div>

        <!-- LOADER DE CARGA -->
        <div v-if="isLoading && estaciones.length === 0" class="flex flex-col items-center justify-center min-h-[50vh] space-y-4">
            <div class="relative flex items-center justify-center">
                <div class="w-16 h-16 border-4 border-blue-100 border-t-blue-600 rounded-full animate-spin"></div>
                <Monitor class="w-6 h-6 text-blue-600 absolute animate-pulse" />
            </div>
            <p class="text-sm font-semibold text-slate-500 animate-pulse">Escaneando laboratorios y estaciones activas...</p>
        </div>

        <!-- CASO 1: NO HAY CLASE ACTIVA -->
        <div 
            v-else-if="!claseActiva"
            class="max-w-xl mx-auto text-center bg-white border border-slate-200 shadow-sm rounded-2xl p-10 space-y-5 mt-10"
        >
            <div class="w-16 h-16 bg-slate-100 text-slate-455 rounded-full flex items-center justify-center mx-auto shadow-3xs">
                <Clock class="w-8 h-8" />
            </div>
            <div class="space-y-2">
                <h2 class="text-lg font-black text-slate-800">Sin Clase Activa en este Momento</h2>
                <p class="text-sm text-slate-500 font-medium">
                    El monitoreo de estaciones y bloqueo de software prohibido solo está disponible cuando se encuentra dictando una clase activa en algún laboratorio, según su cronograma de horarios.
                </p>
            </div>
            <div class="pt-4 border-t border-slate-100 flex justify-center">
                <button 
                    @click="navigateBack"
                    class="bg-blue-600 hover:bg-blue-700 text-white font-extrabold py-2 px-5 rounded-xl shadow-md shadow-blue-600/10 text-xs transition-all cursor-pointer"
                >
                    Volver al Dashboard
                </button>
            </div>
        </div>

        <!-- CASO 2: CLASE ACTIVA -->
        <template v-else-if="infoClase">
                    <!-- PANEL INFORMATIVO DE CLASE -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-5 bg-white border border-slate-200/80 shadow-xs rounded-2xl p-5">
                <div class="flex items-center gap-3 min-w-0">
                    <div class="p-3 bg-blue-50 text-blue-600 rounded-xl shadow-3xs shrink-0">
                        <Users class="w-5 h-5" />
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="text-[10px] text-slate-400 font-black uppercase tracking-wider leading-none">Asignatura y Grupo</p>
                        <h4 class="text-sm font-black text-slate-800 mt-1 leading-tight truncate" :title="infoClase.grupo?.materia?.nombre ?? 'Sin Asignatura'">
                            {{ infoClase.grupo?.materia?.nombre ?? 'Sin Asignatura' }}
                        </h4>
                        <p class="text-xs text-slate-500 font-semibold mt-0.5 truncate">
                            Grupo: {{ infoClase.grupo?.nombre ?? 'A' }} 
                            <span class="text-3xs bg-slate-100 px-1 py-0.2 rounded border border-slate-200 font-mono ml-1 font-bold">
                                {{ infoClase.grupo?.materia?.codigo ?? 'COD' }}
                            </span>
                        </p>
                    </div>
                </div>

                <div class="flex items-center gap-3 border-t md:border-t-0 md:border-x border-slate-100 md:px-5 py-3 md:py-0 min-w-0">
                    <div class="p-3 bg-teal-50 text-teal-650 rounded-xl shadow-3xs shrink-0">
                        <Database class="w-5 h-5" />
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="text-[10px] text-slate-400 font-black uppercase tracking-wider leading-none">Entorno Físico</p>
                        <h4 class="text-sm font-black text-slate-800 mt-1 leading-tight truncate" :title="infoClase.laboratorio.nombre">
                            {{ infoClase.laboratorio.nombre }}
                        </h4>
                        <p class="text-xs text-slate-500 font-semibold mt-0.5 truncate">
                            Pabellón {{ infoClase.laboratorio.pabellon }}, Piso {{ infoClase.laboratorio.piso }}
                        </p>
                    </div>
                </div>

                <div class="flex items-center gap-3 border-t md:border-t-0 md:border-l-0 border-slate-100 pt-3 md:pt-0 min-w-0">
                    <div class="p-3 bg-orange-50 text-orange-600 rounded-xl shadow-3xs shrink-0">
                        <Clock class="w-5 h-5" />
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="text-[10px] text-slate-400 font-black uppercase tracking-wider leading-none">Horario de Monitoreo</p>
                        <h4 class="text-sm font-black text-slate-800 mt-1 leading-tight truncate">
                            {{ infoClase.hora_inicio }} - {{ infoClase.hora_fin }}
                        </h4>
                        <p class="text-xs text-slate-550 font-semibold mt-0.5 flex flex-wrap items-center gap-x-2 gap-y-0.5">
                            <span>PCs: <b class="text-slate-800">{{ activeComputersCount }} en línea</b></span>
                            <span class="w-1 h-1 bg-slate-300 rounded-full hidden sm:inline-block"></span>
                            <span>Alumnos: <b class="text-slate-800">{{ activeStudentsCount }} logueados</b></span>
                        </p>
                    </div>
                </div>
            </div>

            <!-- CONTENEDOR GRID Y ALERTA DE RESTRICCION -->
            <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
                
                <!-- GRILLA DE PCS (OCUPA 2/3 DE ANCHO) -->
                <div class="xl:col-span-2 space-y-4">
                    <div class="flex items-center justify-between">
                        <h2 class="text-base font-extrabold text-slate-800 flex items-center gap-2">
                            <Monitor class="w-5 h-5 text-slate-655" />
                            Disposición de Estaciones de Trabajo
                        </h2>
                        <!-- Leyenda -->
                        <div class="flex items-center gap-4 text-3xs font-black uppercase tracking-wider text-slate-500">
                            <div class="flex items-center gap-1">
                                <span class="w-2 h-2 bg-emerald-500 rounded-full shadow-3xs"></span>
                                <span>Activa c/ Sesión</span>
                            </div>
                            <div class="flex items-center gap-1">
                                <span class="w-2 h-2 bg-amber-500 rounded-full shadow-3xs"></span>
                                <span>Activa s/ Sesión</span>
                            </div>
                            <div class="flex items-center gap-1">
                                <span class="w-2 h-2 bg-slate-350 rounded-full"></span>
                                <span>Desconectada</span>
                            </div>
                        </div>
                    </div>

                    <!-- Grid -->
                    <div v-if="estaciones.length === 0" class="bg-white border-2 border-dashed border-slate-200 rounded-2xl p-12 text-center text-slate-400 italic">
                        No hay estaciones asignadas a este laboratorio.
                    </div>
                    <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                        <div 
                            v-for="pc in sortedEstaciones" 
                            :key="pc.id"
                            class="border rounded-2xl p-4 transition-all duration-300 relative overflow-hidden flex flex-col justify-between"
                            :class="[
                                pc.estado === 'Offline' 
                                    ? 'bg-slate-100 border-slate-200/60 opacity-70' 
                                    : pc.estudiante 
                                        ? 'bg-emerald-50/50 hover:bg-emerald-50 border-emerald-200/80 shadow-3xs' 
                                        : 'bg-amber-50/40 hover:bg-amber-50 border-amber-200/80 shadow-3xs'
                            ]"
                        >
                            <!-- Overlay de Estado Bloqueado -->
                            <div 
                                v-if="lockedPCs[pc.id]"
                                class="absolute inset-0 bg-slate-950/75 backdrop-blur-xs flex flex-col items-center justify-center gap-2 text-white z-10 transition-all duration-300"
                            >
                                <Lock class="w-8 h-8 text-amber-400 animate-bounce" />
                                <span class="text-3xs font-black uppercase tracking-widest text-slate-200">Estación Bloqueada</span>
                                <button 
                                    @click="ejecutarAccion(pc, 'desbloquear')"
                                    class="mt-1 bg-amber-500 hover:bg-amber-600 text-slate-950 font-black px-2.5 py-1 rounded-lg text-4xs uppercase tracking-wider transition-colors cursor-pointer"
                                >
                                    Desbloquear
                                </button>
                            </div>

                            <!-- Header de la tarjeta PC -->
                            <div class="flex items-start justify-between mb-2 gap-1.5 min-w-0">
                                <div class="flex items-center gap-2 min-w-0 flex-1">
                                    <div 
                                        class="p-2 rounded-xl shrink-0 shadow-3xs"
                                        :class="[
                                            pc.estado === 'Offline' 
                                                ? 'bg-slate-200 text-slate-400' 
                                                : pc.estudiante 
                                                    ? 'bg-emerald-100 text-emerald-600' 
                                                    : 'bg-amber-100 text-amber-600'
                                        ]"
                                    >
                                        <Monitor class="w-4 h-4" />
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <h4 class="font-extrabold text-slate-800 text-xs leading-none truncate" :title="pc.hostname">{{ pc.hostname }}</h4>
                                        <p class="text-[10px] text-slate-400 font-mono tracking-tighter truncate mt-0.5" :title="pc.direccion_ip">{{ pc.direccion_ip }}</p>
                                    </div>
                                </div>

                                <!-- Indicador de Estado -->
                                <span 
                                    class="w-2 h-2 rounded-full shadow-3xs"
                                    :class="[
                                        pc.estado === 'Offline' 
                                            ? 'bg-slate-350' 
                                            : pc.estudiante 
                                                ? 'bg-emerald-500 animate-pulse' 
                                                : 'bg-amber-500 animate-pulse'
                                    ]"
                                ></span>
                            </div>

                            <!-- Cuerpo: Estudiante Asignado -->
                            <div class="py-2.5 my-1.5 border-y border-slate-200/40 text-xs font-semibold text-slate-500">
                                <p class="text-[9px] text-slate-400 uppercase tracking-widest leading-none">Estudiante</p>
                                <p class="mt-1 truncate font-extrabold text-slate-800 flex items-center gap-1" v-if="pc.estudiante">
                                    <span class="w-1.5 h-1.5 bg-emerald-500 rounded-full shrink-0"></span>
                                    {{ pc.estudiante.name }}
                                </p>
                                <p class="mt-1 text-slate-400 italic flex items-center gap-1 font-medium" v-else-if="pc.estado !== 'Offline'">
                                    <span class="w-1.5 h-1.5 bg-amber-500 rounded-full shrink-0"></span>
                                    PC libre (Sin sesión activa)
                                </p>
                                <p class="mt-1 text-slate-400 italic font-medium" v-else>
                                    Estación apagada
                                </p>
                            </div>

                            <!-- Mini captura de pantalla en vivo -->
                            <div 
                                v-if="pc.estado !== 'Offline'"
                                class="my-2 bg-slate-900 border border-slate-200 rounded-xl overflow-hidden aspect-video relative group cursor-pointer shadow-3xs hover:border-blue-400/80 hover:shadow-2xs transition-all duration-350 shrink-0"
                                @click="openScreenSimulationModal(pc)"
                                title="Clic para ampliar captura del agente"
                            >
                                <img 
                                    :src="getScreenImageForPC(pc.id)" 
                                    class="w-full h-full object-cover select-none pointer-events-none group-hover:scale-103 transition-transform duration-500" 
                                    alt="Miniatura Pantalla"
                                />
                                <div class="absolute inset-0 bg-slate-900/10 group-hover:bg-slate-900/40 transition-colors flex items-center justify-center">
                                    <div class="bg-white/95 backdrop-blur-xs border border-slate-100 rounded-xl p-1.5 shadow-sm opacity-0 group-hover:opacity-100 transition-opacity duration-300">
                                        <Monitor class="w-4 h-4 text-blue-600 animate-pulse" />
                                    </div>
                                    
                                    <!-- Tag de estado en miniatura -->
                                    <span class="absolute bottom-1.5 right-1.5 bg-slate-900/85 text-white font-mono text-[8px] font-black px-1.5 py-0.5 rounded leading-none">
                                        {{ pcScreenViews[pc.id] === 'start_menu' ? 'Inicio' : pcScreenViews[pc.id] === 'file_explorer' ? 'Explorador' : 'Escritorio' }}
                                    </span>
                                </div>
                            </div>

                            <!-- Footer: Rendimiento CPU y RAM -->
                            <div class="space-y-2" v-if="pc.estado !== 'Offline' && pc.telemetria">
                                <div class="flex items-center justify-between text-[10px] font-bold text-slate-655">
                                    <span class="flex items-center gap-1">
                                        <Cpu class="w-3 h-3 text-slate-400" />
                                        CPU:
                                    </span>
                                    <span class="font-mono text-slate-800" :class="{ 'text-red-600': pc.telemetria.carga_cpu >= 80 }">
                                        {{ pc.telemetria.carga_cpu }}%
                                    </span>
                                </div>
                                <div class="w-full bg-slate-200/60 rounded-full h-1 overflow-hidden">
                                    <div 
                                        class="h-1 rounded-full transition-all duration-300"
                                        :class="pc.telemetria.carga_cpu >= 80 ? 'bg-red-500' : 'bg-emerald-500'"
                                        :style="{ width: `${pc.telemetria.carga_cpu}%` }"
                                    ></div>
                                </div>

                                <div class="flex items-center justify-between text-[10px] font-bold text-slate-655">
                                    <span class="flex items-center gap-1">
                                        <Database class="w-3 h-3 text-slate-400" />
                                        RAM:
                                    </span>
                                    <span class="font-mono text-slate-800">
                                        {{ pc.telemetria.uso_ram_mb >= 1024 ? `${(pc.telemetria.uso_ram_mb / 1024).toFixed(1)} GB` : `${pc.telemetria.uso_ram_mb} MB` }}
                                    </span>
                                </div>
                            </div>
                            <div v-else-if="pc.estado !== 'Offline'" class="text-[10px] text-slate-400 italic text-center py-1">
                                Sin telemetría reportada
                            </div>
                            <div v-else class="text-[10px] text-slate-400 italic text-center py-1">
                                No disponible
                            </div>

                            <!-- Acciones Rápidas de Clase -->
                            <div class="mt-4 pt-3 border-t border-slate-200/40 flex items-center justify-between gap-2 shrink-0" v-if="pc.estado !== 'Offline'">
                                <button 
                                    @click="openScreenSimulationModal(pc)"
                                    class="inline-flex items-center justify-center gap-1 bg-blue-50 hover:bg-blue-100 text-blue-700 font-extrabold px-2.5 py-1.5 rounded-xl border border-blue-150 text-[10px] cursor-pointer transition-all duration-200 shadow-3xs flex-1"
                                    title="Ver captura de pantalla en tiempo real del agente"
                                >
                                    <Monitor class="w-3.5 h-3.5" />
                                    Pantalla
                                </button>
                                
                                <div class="flex items-center gap-1.5 shrink-0">
                                    <button 
                                        @click="ejecutarAccion(pc, 'bloquear')"
                                        class="inline-flex items-center justify-center p-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl border border-slate-250/60 cursor-pointer transition-all duration-200"
                                        title="Bloquear estación de trabajo"
                                    >
                                        <Lock class="w-3.5 h-3.5" />
                                    </button>
                                    
                                    <button 
                                        @click="ejecutarAccion(pc, 'cerrar_sesion')"
                                        class="inline-flex items-center justify-center p-2 bg-red-50 hover:bg-red-100 border border-red-200 text-red-700 rounded-xl cursor-pointer transition-all duration-200"
                                        title="Cerrar la sesión de usuario actual"
                                    >
                                        <LogOut class="w-3.5 h-3.5" />
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- HISTORIAL DE INFRACCIONES DE APLICACIONES EN VIVO (1/3 ANCHO) -->
                <div class="space-y-4">
                    <h2 class="text-base font-extrabold text-slate-800 flex items-center gap-2">
                        <ShieldAlert class="w-5 h-5 text-red-600" />
                        Software Prohibido Cerrado
                    </h2>

                    <!-- Lista de Software Cerrado / Violaciones -->
                    <div class="bg-white border border-slate-200/80 shadow-xs rounded-2xl p-4 flex flex-col h-[400px]">
                        <div class="flex-1 overflow-y-auto space-y-3 custom-scrollbar pr-1">
                            <div v-if="infracciones.length === 0" class="flex flex-col items-center justify-center h-full text-center text-slate-400 italic space-y-2">
                                <CheckCircle2 class="w-10 h-10 text-emerald-200 animate-pulse" />
                                <p class="font-extrabold text-emerald-700 text-xs">Clase Libre de Infracciones</p>
                                <p class="text-[10px] text-slate-400 max-w-[200px] leading-tight mt-0.5">Ningún equipo ha intentado abrir procesos prohibidos.</p>
                            </div>

                            <div 
                                v-else
                                v-for="log in infracciones" 
                                :key="log.id"
                                class="border border-red-100 bg-red-50/20 hover:bg-red-50/50 rounded-xl p-3 transition-all relative border-l-4 border-l-red-500"
                            >
                                <div class="flex justify-between items-start gap-1.5 min-w-0">
                                    <div class="min-w-0 flex-1">
                                        <h4 class="font-black text-red-800 text-xs flex items-center gap-1 truncate" :title="log.nombre_proceso">
                                            <ShieldAlert class="w-3.5 h-3.5 text-red-650 shrink-0" />
                                            <span class="truncate">{{ log.nombre_proceso }}</span>
                                        </h4>
                                        <p class="text-[9px] text-slate-400 font-mono tracking-tighter truncate mt-0.5" :title="log.ruta_ejecutable || ''">
                                            {{ log.ruta_ejecutable || 'N/A' }}
                                        </p>
                                    </div>
                                    <span class="text-[9px] font-black bg-red-100 text-red-800 border border-red-200 px-1.5 py-0.2 rounded uppercase shrink-0">
                                        Terminado
                                    </span>
                                </div>

                                <div class="mt-2.5 pt-2 border-t border-slate-100 flex justify-between text-[9px] font-bold text-slate-500 leading-none">
                                    <span>PC: <b class="text-slate-700">{{ log.estacion?.hostname ?? 'N/A' }}</b></span>
                                    <span>Hora: <b class="text-slate-700">{{ log.created_at.split(' ')[1] }}</b></span>
                                </div>
                                <div class="mt-1 text-[9px] font-bold text-slate-500 leading-none" v-if="log.usuario">
                                    Usuario: <b class="text-slate-700">{{ log.usuario.name }}</b>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- TABLA DE DETALLES ADICIONALES (INFRACCIONES) -->
            <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-6" v-if="infracciones.length > 0">
                <h3 class="text-sm font-extrabold text-slate-800 mb-4 flex items-center gap-2">
                    <ShieldAlert class="w-4 h-4 text-red-600" />
                    Detalle Completo de Ejecutables Bloqueados durante esta Clase
                </h3>

                <div class="overflow-x-auto rounded-xl border border-slate-150">
                    <table class="w-full text-left text-xs font-semibold text-slate-655 border-collapse">
                        <thead class="bg-slate-50 text-slate-550 border-b border-slate-150 uppercase tracking-widest text-[10px] font-black">
                            <tr>
                                <th class="p-3">Estación</th>
                                <th class="p-3">Estudiante</th>
                                <th class="p-3">Proceso</th>
                                <th class="p-3">Ruta del Ejecutable</th>
                                <th class="p-3">Acción Tomada</th>
                                <th class="p-3">Registrado a las</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-for="log in infracciones" :key="log.id" class="hover:bg-slate-50/50 transition-colors">
                                <td class="p-3 font-mono font-bold text-slate-800">{{ log.estacion?.hostname ?? 'N/A' }}</td>
                                <td class="p-3 text-slate-900 font-extrabold">{{ log.usuario?.name ?? 'Sin sesión iniciada' }}</td>
                                <td class="p-3 text-red-650 font-extrabold">{{ log.nombre_proceso }}</td>
                                <td class="p-3 font-mono text-[10px] text-slate-400 select-all max-w-xs truncate" :title="log.ruta_ejecutable || ''">
                                    {{ log.ruta_ejecutable || '-' }}
                                </td>
                                <td class="p-3">
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase bg-red-50 text-red-700 border border-red-100">
                                        {{ log.accion_tomada }}
                                    </span>
                                </td>
                                <td class="p-3 font-mono text-slate-500">{{ log.created_at }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </template>

        <!-- Modal de Simulación de Pantallas del Agente -->
        <EstacionPantallaModal 
            v-model="isScreenModalOpen" 
            :estacion="selectedEstacionForScreen" 
            :current-view="selectedEstacionForScreen ? (pcScreenViews[selectedEstacionForScreen.id] || 'desktop') : 'desktop'"
        />
    </div>
</template>

<style scoped>
/* Estilización estética de la barra de scroll integrada */
.custom-scrollbar::-webkit-scrollbar {
    width: 4px;
}

.custom-scrollbar::-webkit-scrollbar-track {
    background: transparent;
}

.custom-scrollbar::-webkit-scrollbar-thumb {
    background: #e2e8f0;
    border-radius: 2px;
}

.custom-scrollbar::-webkit-scrollbar-thumb:hover {
    background: #cbd5e1;
}
</style>
