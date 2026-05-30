<script setup lang="ts">
import { ref, watch, computed } from 'vue';
import { X, Monitor, Calendar, Shield, Cpu, Database, Play } from '@lucide/vue';
import type { LiveEstacion } from '@/types/docenteDashboard';

const props = withDefaults(
    defineProps<{
        modelValue: boolean;
        estacion: LiveEstacion | null;
        currentView?: 'desktop' | 'start_menu' | 'file_explorer';
    }>(),
    {
        currentView: 'desktop'
    }
);

const emit = defineEmits<{
    (e: 'update:modelValue', value: boolean): void;
}>();

// Vista seleccionada: 'live' | 'desktop' | 'start_menu' | 'file_explorer'
const activeTab = ref<'live' | 'desktop' | 'start_menu' | 'file_explorer'>('live');

// Reset de la pestaña por defecto al abrir
watch(() => props.modelValue, (isOpen) => {
    if (isOpen) {
        activeTab.value = 'live';
    }
});

const closeModal = () => {
    emit('update:modelValue', false);
};

// Determinar qué vista de pantalla mostrar
const activeScreenView = computed(() => {
    if (activeTab.value === 'live') {
        return props.currentView || 'desktop';
    }
    return activeTab.value || 'desktop';
});

// Mapeo de imágenes simuladas
const getScreenImage = () => {
    switch (activeScreenView.value) {
        case 'start_menu':
            return '/sim_start_menu.png';
        case 'file_explorer':
            return '/sim_file_explorer.png';
        case 'desktop':
        default:
            return '/sim_desktop.png';
    }
};

// Iniciales del alumno
const getInitials = (name: string): string => {
    if (!name) return 'U';
    const parts = name.trim().split(/\s+/);
    const first = parts[0]?.charAt(0) || '';
    const second = parts[1]?.charAt(0) || '';
    return first && second ? (first + second).toUpperCase() : name.trim().slice(0, 2).toUpperCase();
};
</script>

<template>
    <Transition name="modal">
        <div v-if="modelValue"
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-xs">
            <div
                class="bg-white rounded-2xl shadow-2xl border border-slate-200 max-w-5xl w-full overflow-hidden transform transition-all scale-100 flex flex-col max-h-[92vh] text-slate-800">

                <!-- CABECERA DEL MODAL DE MONITOREO -->
                <div class="px-6 py-4.5 border-b border-slate-100 flex items-center justify-between shrink-0 bg-white">
                    <div class="flex items-center gap-3">
                        <div class="p-2.5 bg-purple-50 text-purple-600 rounded-xl border border-purple-100 shrink-0">
                            <Monitor class="w-5.5 h-5.5 animate-pulse" />
                        </div>
                        <div class="min-w-0">
                            <h2 class="text-base font-black tracking-tight flex items-center gap-2 text-slate-900 leading-none">
                                Captura de Pantalla en Tiempo Real
                                <span class="bg-emerald-50 text-emerald-700 border border-emerald-250 text-[9px] font-black px-2 py-0.5 rounded-full uppercase tracking-wider">
                                    Agente Activo
                                </span>
                            </h2>
                            <p class="text-3xs text-slate-500 font-semibold mt-1.5" v-if="estacion">
                                Estación: <span class="text-slate-800 font-mono font-bold">{{ estacion.hostname }}</span>
                                <span class="mx-1.5 text-slate-300">•</span>
                                IP: <span class="text-slate-800 font-mono font-bold">{{ estacion.direccion_ip }}</span>
                                <span class="mx-1.5 text-slate-300">•</span>
                                SO: <span class="text-slate-700 font-bold">{{ estacion.so_info || 'Windows 11 Pro' }}</span>
                            </p>
                        </div>
                    </div>
                    <button @click="closeModal"
                        class="text-slate-400 hover:text-slate-600 p-2 hover:bg-slate-50 rounded-xl transition-all duration-200 cursor-pointer">
                        <X class="w-5 h-5" />
                    </button>
                </div>
 
                <!-- CONTENEDOR PRINCIPAL -->
                <div class="flex-1 overflow-y-auto p-6 bg-slate-50/50 flex flex-col lg:flex-row gap-6 min-h-[350px]">
                    
                    <!-- MARCO DE MONITOR (SIMULADO EN OSCURO PARA RESALTAR LA PANTALLA) -->
                    <div class="flex-1 flex flex-col justify-center items-center">
                        <div class="w-full max-w-3xl bg-slate-900 p-2 rounded-2xl border border-slate-950 shadow-2xl relative">
                            <!-- Bisel superior con cámara simulada -->
                            <div class="absolute left-1/2 -translate-x-1/2 top-1 w-12 h-1 bg-slate-950 rounded-full opacity-60"></div>
                            
                            <!-- Pantalla del agente -->
                            <div class="w-full aspect-[16/9] rounded-xl overflow-hidden bg-slate-950 border border-slate-950 relative group">
                                <img 
                                    :src="getScreenImage()" 
                                    alt="Pantalla de agente simulada"
                                    class="w-full h-full object-cover select-none pointer-events-none transition-transform duration-500"
                                />
                                
                                <!-- Indicador de captura activa -->
                                <span class="absolute top-3 left-3 bg-slate-900/80 backdrop-blur-xs text-white border border-slate-700/50 font-mono text-[8px] font-black px-2 py-0.5 rounded uppercase tracking-widest leading-none flex items-center gap-1.5 shadow-md">
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                    Monitoreo En Vivo
                                </span>
                            </div>
                        </div>
 
                        <!-- Soporte físico simulado -->
                        <div class="w-20 h-4 bg-slate-800 border-x border-slate-750 mt-0.5 rounded-b-xs shadow-md"></div>
                        <div class="w-36 h-2 bg-slate-900 border border-slate-950 rounded-full shadow-lg"></div>
                    </div>

                    <!-- PANEL DE METADATOS Y SESIÓN DEL ALUMNO (1/3 ANCHO) -->
                    <div class="w-full lg:w-80 bg-white border border-slate-200 rounded-2xl p-5 flex flex-col justify-between space-y-5 shadow-3xs">
                        <div class="space-y-4">
                            <div>
                                <h3 class="text-3xs font-black uppercase tracking-widest text-slate-400">Sesión de Alumno</h3>
                                
                                <div class="mt-3 flex items-center gap-3.5 bg-slate-50 p-3 rounded-xl border border-slate-200/60 shadow-inner">
                                    <div class="w-10 h-10 rounded-full bg-purple-50 border border-purple-100 text-purple-600 font-extrabold flex items-center justify-center text-sm uppercase shrink-0">
                                        {{ getInitials(estacion?.estudiante ? estacion.estudiante.name : 'PC') }}
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <p class="text-xs font-black text-slate-800 truncate leading-tight">
                                            {{ estacion?.estudiante ? estacion.estudiante.name : 'Sesión Libre' }}
                                        </p>
                                        <p class="text-[9px] text-slate-450 font-bold truncate mt-1">
                                            {{ estudianteInfo = (estacion?.estudiante ? 'Estudiante Registrado' : 'Sin credenciales') }}
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <!-- RENDIMIENTO EN VIVO -->
                            <div class="space-y-3.5 pt-3.5 border-t border-slate-100" v-if="estacion?.telemetria">
                                <h3 class="text-3xs font-black uppercase tracking-widest text-slate-400">Hardware Remoto</h3>
                                
                                <div class="space-y-3">
                                    <!-- CPU -->
                                    <div class="space-y-1">
                                        <div class="flex justify-between text-3xs font-extrabold text-slate-500">
                                            <span class="flex items-center gap-1.5">
                                                <Cpu class="w-3.5 h-3.5 text-purple-500" />
                                                Carga de CPU
                                            </span>
                                            <span class="font-mono text-slate-800 font-bold">{{ estacion.telemetria.carga_cpu }}%</span>
                                        </div>
                                        <div class="w-full bg-slate-100 rounded-full h-1 overflow-hidden">
                                            <div 
                                                class="bg-purple-600 h-1 rounded-full transition-all duration-300"
                                                :style="{ width: `${estacion.telemetria.carga_cpu}%` }"
                                            ></div>
                                        </div>
                                    </div>

                                    <!-- RAM -->
                                    <div class="space-y-1">
                                        <div class="flex justify-between text-3xs font-extrabold text-slate-500">
                                            <span class="flex items-center gap-1.5">
                                                <Database class="w-3.5 h-3.5 text-purple-500" />
                                                Uso de RAM
                                            </span>
                                            <span class="font-mono text-slate-800 font-bold">
                                                {{ (estacion.telemetria.uso_ram_mb / 1024).toFixed(1) }} GB / 8.0 GB
                                            </span>
                                        </div>
                                        <div class="w-full bg-slate-100 rounded-full h-1 overflow-hidden">
                                            <div 
                                                class="bg-purple-600 h-1 rounded-full transition-all duration-300"
                                                :style="{ width: `${(estacion.telemetria.uso_ram_mb / 8192) * 100}%` }"
                                            ></div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- DETALLES DE AGENTE -->
                            <div class="pt-3.5 border-t border-slate-100 space-y-2 text-3xs font-semibold text-slate-500">
                                <h3 class="text-3xs font-black uppercase tracking-widest text-slate-400 mb-2.5">Agente de Ciberseguridad</h3>
                                <div class="flex justify-between">
                                    <span>Versión Nexus:</span>
                                    <span class="font-mono text-slate-750 font-bold">v2.1.0-wpf</span>
                                </div>
                                <div class="flex justify-between">
                                    <span>Estado Canal:</span>
                                    <span class="text-emerald-600 font-bold flex items-center gap-1">
                                        <span class="w-1.5 h-1.5 bg-emerald-500 rounded-full animate-pulse"></span>
                                        Transmitiendo
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div class="pt-4 border-t border-slate-100 bg-white shrink-0 text-slate-450 text-[9px] font-bold leading-normal flex items-start gap-2">
                            <Shield class="w-4 h-4 text-purple-600 shrink-0 mt-0.5" />
                            <span>Esta vista de monitoreo en tiempo real respeta las políticas internas de ciberseguridad académica de Nexus Lab.</span>
                        </div>
                    </div>
                </div>

                <!-- PIE DEL MODAL -->
                <div class="px-6 py-4 bg-slate-50 border-t border-slate-100 flex justify-end shrink-0">
                    <button type="button" @click="closeModal"
                        class="px-5 py-2.5 bg-white border border-slate-200 text-slate-750 hover:bg-slate-100 hover:text-slate-900 font-bold rounded-xl text-sm transition-all duration-200 shadow-xs focus:ring-2 focus:ring-slate-250 cursor-pointer">
                        Cerrar Monitoreo
                    </button>
                </div>

            </div>
        </div>
    </Transition>
</template>

<style scoped>
.backdrop-blur-xs {
    backdrop-filter: blur(2px);
}
.modal-enter-active,
.modal-leave-active {
    transition: opacity 0.25s ease;
}
.modal-enter-from,
.modal-leave-to {
    opacity: 0;
}
.custom-scrollbar::-webkit-scrollbar {
    height: 4px;
    width: 4px;
}
.custom-scrollbar::-webkit-scrollbar-track {
    background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
    background: rgba(148, 163, 184, 0.15);
    border-radius: 9999px;
}
.custom-scrollbar::-webkit-scrollbar-thumb:hover {
    background: rgba(148, 163, 184, 0.3);
}
.bg-slate-205 {
    background-color: #f1f5f9;
}
</style>
