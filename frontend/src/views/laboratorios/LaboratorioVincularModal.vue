<script setup lang="ts">
import { ref, watch, onUnmounted } from 'vue';
import {
    X, Loader2, Network, Laptop, CheckCircle2,
    AlertCircle, Play, Sparkles, Activity
} from '@lucide/vue';
import type { Laboratorio } from '@/types/laboratorio';
import { estacionService, type EstacionFormData } from '@/services/estacionService';
import api from '@/services/api';
import { parseLaravelError } from '@/utils/errorHandler';

// 🚀 1. DEFINIMOS LA INTERFAZ ESTRICTA PARA LAS ESTACIONES PENDIENTES
interface EstacionPendiente {
    laboratorio_target_id: number;
    uuid: string;
    hostname: string;
    direccion_mac: string;
    direccion_ip: string;
    so_info: string;
    version_agente: string;
    estado: 'pendiente' | 'bloqueado' | 'activo';
    detectedAt?: string;
    vendor?: string;  // OUI / Fabricante de la NIC
}

// Tipamos los estados posibles del botón de vinculación
type LinkingStatus = 'idle' | 'loading' | 'success' | 'error';

const props = defineProps<{
    show: boolean;
    lab: Laboratorio | null;
}>();

const emit = defineEmits<{
    (e: 'close'): void;
}>();

// --- ESTADO REACTIVO FUERTEMENTE TIPADO ---
const isConnected = ref<boolean>(false);
const detectedStations = ref<EstacionPendiente[]>([]);
const linkingState = ref<Record<string, LinkingStatus>>({});
const linkingErrors = ref<Record<string, string>>({});
const isSimulating = ref<boolean>(false);

// Dejamos que TS infiera el tipo de canal o usamos el fallback de Laravel Echo
let channel: any = null;

// --- SUSCRIPCIÓN AL WEBSOCKET (LARAVEL ECHO) ---
const subscribeToChannel = () => {
    if (!props.lab) return;

    unsubscribeFromChannel();

    try {
        console.log(`[WebSocket] Suscribiéndose al canal: laboratorio.${props.lab.id}`);
        isConnected.value = true;

        channel = window.Echo.channel(`laboratorio.${props.lab.id}`);

        // Escucha el evento EstacionDetectadaEnCanal
        channel.listen('.EstacionDetectadaEnCanal', (data: { payload?: EstacionPendiente } & any) => {
            console.log('[WebSocket] Estación detectada en tiempo real:', data);

            // Evaluamos si viene envuelto en payload o directo (según el Broadcast de Laravel)
            const station: EstacionPendiente = data.payload || data;

            // Evitar duplicaciones síncronas en el array reactivo
            if (station && station.uuid && !detectedStations.value.some(s => s.uuid === station.uuid)) {
                detectedStations.value.unshift({
                    ...station,
                    detectedAt: new Date().toLocaleTimeString()
                });

                // Mapeo inicial del estado del botón por UUID
                linkingState.value[station.uuid] = 'idle';
            }
        });
    } catch (error) {
        console.error('[WebSocket] Error crítico en la pasarela de sockets:', error);
        isConnected.value = false;
    }
};

const unsubscribeFromChannel = () => {
    if (props.lab && window.Echo) {
        console.log(`[WebSocket] Removiendo listener y saliendo del canal: laboratorio.${props.lab.id}`);
        window.Echo.leave(`laboratorio.${props.lab.id}`);
    }
    isConnected.value = false;
};

// --- CONTROL DE ACCIONES DE RED ---
const handleLinkStation = async (station: EstacionPendiente) => {
    const uuid = station.uuid;
    linkingState.value[uuid] = 'loading';

    try {
        const payload: EstacionFormData = {
            laboratorio_id: props.lab?.id ?? null,
            uuid: station.uuid,
            hostname: station.hostname,
            direccion_mac: station.direccion_mac,
            direccion_ip: station.direccion_ip,
            so_info: station.so_info,
            estado: 'bloqueado', // Inicia protegida bajo el modelo Kiosko
            version_agente: station.version_agente
        };

        // Ejecutamos la persistencia formal en PostgreSQL
        await estacionService.vincularEstacion(payload);

        linkingState.value[uuid] = 'success';

        // Transición fluida: removemos de la lista lateral tras 1.5 segundos
        setTimeout(() => {
            detectedStations.value = detectedStations.value.filter(s => s.uuid !== uuid);
            delete linkingState.value[uuid]; // Limpiamos el mapa de estados para liberar memoria
        }, 1500);
    } catch (error) {
        console.error('Fallo en la consolidación de la estación:', error);
        linkingState.value[uuid] = 'error';
        linkingErrors.value[uuid] = parseLaravelError(error);
        setTimeout(() => {
            linkingState.value[uuid] = 'idle';
            delete linkingErrors.value[uuid];
        }, 6000);
    }
};

// --- POOLS DE DATOS REALISTAS PARA EL SIMULADOR ---
const OUI_VENDORS = [
    // Dell
    { prefix: '00:21:70', vendor: 'Dell' },
    { prefix: '18:03:73', vendor: 'Dell' },
    { prefix: 'F8:BC:12', vendor: 'Dell' },
    // HP / Hewlett-Packard
    { prefix: '00:1E:0B', vendor: 'HP' },
    { prefix: '3C:D9:2B', vendor: 'HP' },
    { prefix: 'B4:99:BA', vendor: 'HP' },
    // Lenovo
    { prefix: '00:1A:6B', vendor: 'Lenovo' },
    { prefix: '54:EE:75', vendor: 'Lenovo' },
    { prefix: 'C8:5B:76', vendor: 'Lenovo' },
    // Asus
    { prefix: '00:E0:18', vendor: 'ASUS' },
    { prefix: 'AC:22:0B', vendor: 'ASUS' },
    // Acer
    { prefix: '00:27:0E', vendor: 'Acer' },
    { prefix: 'E4:CE:8F', vendor: 'Acer' },
    // Intel NIC (común en cualquier marca)
    { prefix: '8C:8D:28', vendor: 'Intel' },
    { prefix: '00:1B:21', vendor: 'Intel' },
];

const OS_VARIANTS = [
    'Windows 10 Pro (x64) Build 19045',
    'Windows 10 Education (x64) Build 19045',
    'Windows 11 Pro (x64) Build 22631',
    'Windows 11 Education (x64) Build 22631',
    'Windows 11 Pro (x64) Build 26100',
    'Windows 10 Pro (x64) Build 19041',
    'Windows 11 Home (x64) Build 22631',
];

const AGENT_VERSIONS = [
    'v2.0.5-wpf',
    'v2.1.0-wpf',
    'v2.0.3-wpf',
    'v2.1.2-wpf',
    'v2.2.0-beta',
];

const HOSTNAME_PREFIXES = ['LAB', 'NEXUS', 'PC', 'WS', 'COMP', 'AULA', 'EST'];

const randHex = () => Math.floor(Math.random() * 256).toString(16).padStart(2, '0').toUpperCase();

const generateMac = (oui: string): string => {
    const suffix = `${randHex()}:${randHex()}:${randHex()}`;
    return `${oui}:${suffix}`;
};

const generateHostname = (labId: number, index: number): string => {
    const prefix = HOSTNAME_PREFIXES[Math.floor(Math.random() * HOSTNAME_PREFIXES.length)] ?? 'PC';
    const labNum = String(labId).padStart(2, '0');
    const pcNum = String(index).padStart(2, '0');
    return `${prefix}-L${labNum}-PC${pcNum}`;
};

// Controla el índice de PC para que no repita en la misma sesión
let simulationIndex = 1;

// --- SIMULADOR DE PRUEBAS MASIVAS ---
const simulateDetection = async () => {
    if (!props.lab) return;
    isSimulating.value = true;

    try {
        await api.post('/api/simulador/vinculacion-abierta', {
            laboratorio_id: props.lab.id
        });
    } catch {
        console.warn("[Simulador] Backend offline. Activando generador local realista.");

        // Genera entre 1 y 4 equipos con retardos escalonados naturales
        const count = Math.floor(Math.random() * 3) + 1;
        for (let i = 0; i < count; i++) {
            const delay = i * (Math.floor(Math.random() * 300) + 250); // 250–550 ms entre cada uno
            setTimeout(() => triggerLocalSimulation(), delay);
        }
    } finally {
        isSimulating.value = false;
    }
};

const triggerLocalSimulation = () => {
    if (!props.lab) return;

    const oui = OUI_VENDORS[Math.floor(Math.random() * OUI_VENDORS.length)];
    if (!oui) return;

    const os = OS_VARIANTS[Math.floor(Math.random() * OS_VARIANTS.length)] ?? 'Windows 11 Pro (x64) Build 22631';
    const agentVersion = AGENT_VERSIONS[Math.floor(Math.random() * AGENT_VERSIONS.length)] ?? 'v2.0.5-wpf';
    const subnet = 100 + (props.lab.id ?? 1);
    const host = Math.floor(Math.random() * 150) + 10;
    const pcIndex = simulationIndex++;

    const mockUuid = (typeof crypto !== 'undefined' && crypto.randomUUID)
        ? crypto.randomUUID()
        : `xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx`.replace(/[xy]/g, c => {
            const r = Math.random() * 16 | 0;
            return (c === 'x' ? r : (r & 0x3 | 0x8)).toString(16);
        });

    const mockPayload: EstacionPendiente = {
        laboratorio_target_id: props.lab.id ?? 1,
        uuid: mockUuid,
        hostname: generateHostname(props.lab.id ?? 1, pcIndex),
        direccion_mac: generateMac(oui.prefix),
        direccion_ip: `192.168.${subnet}.${host}`,
        so_info: os,
        version_agente: agentVersion,
        vendor: oui.vendor,
        estado: 'pendiente'
    };

    if (!detectedStations.value.some(s => s.uuid === mockPayload.uuid)) {
        detectedStations.value.unshift({
            ...mockPayload,
            detectedAt: new Date().toLocaleTimeString()
        });
        linkingState.value[mockPayload.uuid] = 'idle';
    }
};

const triggerInvalidSimulation = () => {
    if (!props.lab) return;

    const oui = OUI_VENDORS[Math.floor(Math.random() * OUI_VENDORS.length)];
    if (!oui) return;

    const os = OS_VARIANTS[Math.floor(Math.random() * OS_VARIANTS.length)] ?? 'Windows 11 Pro (x64) Build 22631';
    const agentVersion = AGENT_VERSIONS[Math.floor(Math.random() * AGENT_VERSIONS.length)] ?? 'v2.0.5-wpf';
    const subnet = 100 + (props.lab.id ?? 1);
    const host = Math.floor(Math.random() * 150) + 10;
    const pcIndex = simulationIndex++;

    const mockUuid = (typeof crypto !== 'undefined' && crypto.randomUUID)
        ? crypto.randomUUID()
        : `xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx`.replace(/[xy]/g, c => {
            const r = Math.random() * 16 | 0;
            return (c === 'x' ? r : (r & 0x3 | 0x8)).toString(16);
        });

    // Base con datos inicialmente válidos
    const mockPayload: EstacionPendiente = {
        laboratorio_target_id: props.lab.id ?? 1,
        uuid: mockUuid,
        hostname: generateHostname(props.lab.id ?? 1, pcIndex) + '-ERR',
        direccion_mac: generateMac(oui.prefix),
        direccion_ip: `192.168.${subnet}.${host}`,
        so_info: os,
        version_agente: agentVersion,
        vendor: 'Pirate NIC',
        estado: 'pendiente'
    };

    // 🎲 Selección Aleatoria del Tipo de Error a inyectar
    const errorType = Math.floor(Math.random() * 5);
    switch (errorType) {
        case 0:
            mockPayload.direccion_mac = 'ZZ:YY:XX:WW:VV:UU'; // MAC Inválida
            mockPayload.vendor = 'MAC Error';
            break;
        case 1:
            mockPayload.direccion_ip = '999.888.777.666'; // IP Inválida
            mockPayload.vendor = 'IP Error';
            break;
        case 2:
            mockPayload.hostname = 'PC'; // Hostname muy corto (min:3)
            mockPayload.vendor = 'Hostname Error';
            break;
        case 3:
            mockPayload.uuid = 'formato-invalido-de-uuid'; // UUID Inválido
            mockPayload.vendor = 'UUID Error';
            break;
        case 4:
            mockPayload.so_info = ''; // SO Vacío (required)
            mockPayload.vendor = 'SO Error';
            break;
    }

    if (!detectedStations.value.some(s => s.uuid === mockPayload.uuid)) {
        detectedStations.value.unshift({
            ...mockPayload,
            detectedAt: new Date().toLocaleTimeString()
        });
        linkingState.value[mockPayload.uuid] = 'idle';
    }
};

const simulateInvalidDetection = async () => {
    if (!props.lab) return;
    isSimulating.value = true;
    setTimeout(() => {
        triggerInvalidSimulation();
        isSimulating.value = false;
    }, 400);
};

// --- LISTENERS DEL CICLO DE VIDA ---
watch(() => props.show, (isShown) => {
    if (isShown) {
        detectedStations.value = [];
        subscribeToChannel();
    } else {
        unsubscribeFromChannel();
    }
});

onUnmounted(() => {
    unsubscribeFromChannel();
});
</script>

<template>
    <Transition enter-active-class="transition-all duration-300 ease-out"
        enter-from-class="opacity-0 backdrop-blur-none" enter-to-class="opacity-100 backdrop-blur-md"
        leave-active-class="transition-all duration-200 ease-in" leave-from-class="opacity-100 backdrop-blur-md"
        leave-to-class="opacity-0 backdrop-blur-none">
        <div v-if="show"
            class="fixed inset-0 bg-slate-950/60 backdrop-blur-xs z-50 flex items-center justify-center p-4">
            <div
                class="bg-white rounded-xl border border-gray-150 max-w-2xl w-full shadow-2xl relative overflow-hidden flex flex-col animate-in fade-in zoom-in-95 duration-200 max-h-[90vh]">

                <!-- Encabezado Premium -->
                <div class="px-6 pt-6 pb-4 flex items-start justify-between border-b border-gray-100">
                    <div class="flex items-center gap-3">
                        <div class="bg-blue-50 text-blue-600 p-2.5 rounded-lg shrink-0 border border-blue-100">
                            <Network class="w-5 h-5" />
                        </div>
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900">
                                Vincular Estaciones
                            </h3>
                            <p class="text-xs text-gray-500 mt-0.5">
                                Laboratorio: <span class="font-semibold text-blue-600">{{ lab?.nombre }}</span>
                                (Pabellón {{ lab?.pabellon }}, Piso {{ lab?.piso }})
                            </p>
                        </div>
                    </div>
                    <button @click="emit('close')"
                        class="text-gray-400 hover:text-gray-600 hover:bg-gray-150 p-1.5 rounded-lg transition-colors cursor-pointer">
                        <X class="w-4 h-4" />
                    </button>
                </div>

                <!-- Estado del Canal y Guía -->
                <div class="bg-slate-50 border-b border-gray-100 p-4">
                    <div class="flex items-center justify-between mb-2">
                        <div class="flex items-center gap-2">
                            <!-- Pulse indicador de WebSocket -->
                            <div class="relative flex h-3.5 w-3.5">
                                <span
                                    class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                                <span class="relative inline-flex rounded-full h-3.5 w-3.5"
                                    :class="isConnected ? 'bg-emerald-500' : 'bg-amber-500'"></span>
                            </div>
                            <span class="text-xs font-semibold text-slate-700 uppercase tracking-wider">
                                {{ isConnected ? 'Canal de vinculación activo' : 'Conectando con socket...' }}
                            </span>
                        </div>
                        <span
                            class="text-2xs bg-blue-100 text-blue-800 font-medium px-2 py-0.5 rounded-full border border-blue-200">
                            WebSocket ID: laboratorio.{{ lab?.id }}
                        </span>
                    </div>
                    <p class="text-xs text-gray-600 leading-relaxed">
                        Para vincular una estación, encienda la computadora remota y ejecute el agente de NexusLab. El
                        sistema detectará las credenciales y el hardware de la máquina en tiempo real a través del
                        WebSocket.
                    </p>
                </div>

                <!-- Lista de Estaciones Detectadas -->
                <div class="p-6 overflow-y-auto flex-1 min-h-[300px]">
                    <div v-if="detectedStations.length === 0"
                        class="flex flex-col items-center justify-center py-10 space-y-4 text-center">
                        <div class="relative">
                            <div
                                class="w-16 h-16 rounded-full bg-slate-50 border border-slate-200 flex items-center justify-center text-slate-400">
                                <Laptop class="w-8 h-8 animate-pulse text-blue-500" />
                            </div>
                            <div class="absolute -top-1 -right-1">
                                <Activity class="w-5 h-5 text-emerald-500 animate-bounce" />
                            </div>
                        </div>
                        <div>
                            <p class="font-medium text-gray-800 text-sm">Esperando transmisiones del Agente...</p>
                            <p class="text-xs text-gray-400 max-w-sm mt-1 mx-auto leading-relaxed">
                                Escuchando solicitudes en tiempo real en la red local. Inicie el agente para ver
                                aparecer las estaciones aquí.
                            </p>
                        </div>
                        <div class="flex items-center gap-3">
                            <span class="inline-block w-2 h-2 rounded-full bg-emerald-500 animate-ping"></span>
                            <span class="text-2xs text-gray-400 font-mono select-none">LISTENING FOR
                                CONNECTIONS...</span>
                        </div>
                    </div>

                    <div v-else class="space-y-4">
                        <h4 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-2">
                            Estaciones detectadas ({{ detectedStations.length }})
                        </h4>

                        <div v-for="station in detectedStations" :key="station.uuid"
                            class="bg-white border border-gray-200 hover:border-blue-300 hover:shadow-xs rounded-xl p-4 transition-all flex flex-col gap-3 animate-in slide-in-from-top-4 duration-200">
                            <!-- Info principal -->
                            <div class="flex items-start justify-between gap-3">
                                <div class="space-y-2 min-w-0 flex-1">
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <Laptop class="w-4 h-4 text-slate-700 shrink-0" />
                                        <span class="font-semibold text-gray-900 text-sm">{{ station.hostname }}</span>
                                        <span
                                            class="text-3xs text-gray-400 font-medium px-1.5 py-0.5 bg-slate-100 rounded-md border border-slate-200 whitespace-nowrap">
                                            Detectado {{ station.detectedAt }}
                                        </span>
                                    </div>

                                    <div class="grid grid-cols-2 gap-x-4 gap-y-1 text-2xs text-gray-500">
                                        <div>
                                            <span class="font-medium text-gray-400">IP:</span>
                                            <span class="font-mono text-gray-700"> {{ station.direccion_ip }}</span>
                                        </div>
                                        <div>
                                            <span class="font-medium text-gray-400">Agente:</span>
                                            <span class="text-gray-700"> {{ station.version_agente }}</span>
                                        </div>
                                        <div class="col-span-2 flex items-center gap-1.5 flex-wrap">
                                            <span class="font-medium text-gray-400">MAC:</span>
                                            <span class="font-mono text-gray-700 uppercase">{{ station.direccion_mac }}</span>
                                            <span v-if="station.vendor"
                                                class="text-2xs bg-slate-100 text-slate-500 border border-slate-200 rounded px-1 py-0.5 font-medium shrink-0">
                                                {{ station.vendor }}
                                            </span>
                                        </div>
                                        <div class="col-span-2">
                                            <span class="font-medium text-gray-400">S.O:</span>
                                            <span class="text-gray-700"> {{ station.so_info }}</span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Acción de Vinculación -->
                                <div class="shrink-0">
                                    <button @click="handleLinkStation(station)"
                                        :disabled="linkingState[station.uuid] === 'loading' || linkingState[station.uuid] === 'success'"
                                        :class="[
                                            'px-3 py-2 text-xs font-semibold rounded-lg shadow-sm border transition-all cursor-pointer flex items-center gap-1.5 whitespace-nowrap',
                                            linkingState[station.uuid] === 'success' ? 'bg-emerald-50 border-emerald-200 text-emerald-700 shadow-none' :
                                                linkingState[station.uuid] === 'error' ? 'bg-rose-50 border-rose-200 text-rose-700 shadow-none' :
                                                    'bg-blue-600 hover:bg-blue-700 active:bg-blue-800 border-blue-600 text-white'
                                        ]">
                                        <Loader2 v-if="linkingState[station.uuid] === 'loading'"
                                            class="w-3.5 h-3.5 animate-spin" />
                                        <CheckCircle2 v-else-if="linkingState[station.uuid] === 'success'"
                                            class="w-3.5 h-3.5" />
                                        <AlertCircle v-else-if="linkingState[station.uuid] === 'error'"
                                            class="w-3.5 h-3.5" />
                                        <span>
                                            {{
                                                linkingState[station.uuid] === 'loading' ? 'Vinculando...' :
                                                    linkingState[station.uuid] === 'success' ? 'Vinculado' :
                                                        linkingState[station.uuid] === 'error' ? 'Error' :
                                                            'Vincular'
                                            }}
                                        </span>
                                    </button>
                                </div>
                            </div>
                            
                            <!-- Mensaje de Error de Validación -->
                            <div v-if="linkingState[station.uuid] === 'error' && linkingErrors[station.uuid]" 
                                class="mt-1 p-2.5 bg-rose-50 border border-rose-200 rounded-lg flex items-start gap-2.5 animate-in slide-in-from-top-1 fade-in duration-200">
                                <AlertCircle class="w-4 h-4 text-rose-500 shrink-0 mt-0.5" />
                                <p class="text-xs text-rose-700 font-medium leading-relaxed whitespace-pre-wrap">{{ linkingErrors[station.uuid] }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Footer Premium con Simulador de WebSocket -->
                <div class="px-6 py-4 bg-slate-50 border-t border-gray-150 flex items-center justify-between gap-4">
                    <div class="flex items-center gap-2">
                        <button @click="simulateDetection" :disabled="isSimulating"
                            class="px-3.5 py-1.5 bg-white border border-gray-250 text-gray-600 hover:text-blue-600 hover:border-blue-300 hover:bg-blue-50/50 rounded-lg transition-all text-xs font-medium cursor-pointer flex items-center gap-1.5 shadow-2xs">
                            <Sparkles class="w-3.5 h-3.5 text-amber-500 animate-pulse" />
                            <span>Simular Conexión (Test)</span>
                            <Loader2 v-if="isSimulating" class="w-3 h-3 animate-spin text-blue-600" />
                        </button>
                        
                        <button @click="simulateInvalidDetection" :disabled="isSimulating"
                            class="px-3.5 py-1.5 bg-white border border-gray-250 text-gray-600 hover:text-rose-600 hover:border-rose-300 hover:bg-rose-50/50 rounded-lg transition-all text-xs font-medium cursor-pointer flex items-center gap-1.5 shadow-2xs"
                            title="Genera una estación con datos inválidos para probar la validación">
                            <AlertCircle class="w-3.5 h-3.5 text-rose-500" />
                            <span>Inyectar Error</span>
                        </button>
                    </div>

                    <button @click="emit('close')"
                        class="px-4 py-2 border border-gray-200 text-gray-700 bg-white rounded-lg hover:bg-gray-100 hover:border-gray-300 transition-all text-xs font-medium cursor-pointer">
                        Cerrar Canal
                    </button>
                </div>

            </div>
        </div>
    </Transition>
</template>
