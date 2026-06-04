<script setup lang="ts">
import { ref, onMounted, watch, onUnmounted } from 'vue';
import { 
    Search, Trash2, Laptop, Building2, 
    Loader2, AlertTriangle, Monitor, ShieldCheck, 
    RefreshCw, Play, Activity 
} from '@lucide/vue';
import { estacionService } from '@/services/estacionService';
import { laboratorioService } from '@/services/laboratorioService';
import { useAuthStore } from '@/stores/auth';
import type { Laboratorio } from '@/types/laboratorio';

const authStore = useAuthStore();

// Componentes del Sistema
import BasePagination from '@/components/BasePagination.vue';
import EstacionDeleteModal from './EstacionDeleteModal.vue';
import EstacionTelemetriaModal from './EstacionTelemetriaModal.vue';

// --- ESTADOS REACTIVOS ---
const estaciones = ref<any[]>([]);
const laboratorios = ref<Laboratorio[]>([]);
const isLoading = ref(true);
const isDeleting = ref(false);

// Filtros
const searchTerm = ref('');
const filterLaboratorio = ref('all');
const filterEstado = ref('all');

// Paginación
const currentPage = ref(1);
const lastPage = ref(1);
const totalEstaciones = ref(0);

// Control de Modal de Eliminación
const isDeleteModalOpen = ref(false);
const isTelemetryModalOpen = ref(false);
const selectedEstacion = ref<any | null>(null);

// --- MÉTODOS DE CONSULTA ---
const fetchLaboratorios = async () => {
    try {
        const response = await laboratorioService.getLaboratorios({ page: 1 });
        // Intentamos cargar la lista completa o una parte
        laboratorios.value = response.data || [];
    } catch (error) {
        console.error('Error al cargar laboratorios en Estaciones:', error);
    }
};

const fetchEstaciones = async () => {
    isLoading.value = true;
    try {
        const response = await estacionService.getEstaciones({
            page: currentPage.value,
            search: searchTerm.value || undefined,
            laboratorio_id: filterLaboratorio.value === 'all' ? undefined : filterLaboratorio.value,
            estado: filterEstado.value === 'all' ? undefined : filterEstado.value
        });
        
        // El index de Laravel devuelve estaciones paginadas
        estaciones.value = response.data || [];
        currentPage.value = response.current_page || 1;
        lastPage.value = response.last_page || 1;
        totalEstaciones.value = response.total || 0;
    } catch (error) {
        console.error('Error al cargar las estaciones:', error);
    } finally {
        isLoading.value = false;
    }
};

// --- ANTIRREBOTE Y WATCHERS ---
let searchTimeout: ReturnType<typeof setTimeout>;
watch(searchTerm, () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        currentPage.value = 1;
        fetchEstaciones();
    }, 450);
});

watch([filterLaboratorio, filterEstado], () => {
    currentPage.value = 1;
    fetchEstaciones();
});

watch(currentPage, () => {
    fetchEstaciones();
});

// --- ACCIONES ---
const openDeleteModal = (estacion: any) => {
    selectedEstacion.value = estacion;
    isDeleteModalOpen.value = true;
};

const openTelemetryModal = (estacion: any) => {
    selectedEstacion.value = estacion;
    isTelemetryModalOpen.value = true;
};

const handleConfirmDelete = async () => {
    if (!selectedEstacion.value) return;
    isDeleting.value = true;
    try {
        await estacionService.eliminarEstacion(selectedEstacion.value.id);
        isDeleteModalOpen.value = false;
        
        // Ajustar la paginación si se elimina el único elemento de la página
        if (estaciones.value.length === 1 && currentPage.value > 1) {
            currentPage.value--;
        } else {
            await fetchEstaciones();
        }
    } catch (error) {
        console.error('Error al eliminar la estación:', error);
    } finally {
        isDeleting.value = false;
        selectedEstacion.value = null;
    }
};

// Formateo de fecha de última conexión
const formatLastConnection = (dateString: string) => {
    if (!dateString || dateString === 'sin conexion') return 'Nunca';
    try {
        const date = new Date(dateString);
        return date.toLocaleString();
    } catch (e) {
        return dateString;
    }
};

let channel: any = null;

// Carga Inicial
onMounted(async () => {
    await fetchLaboratorios();
    await fetchEstaciones();

    if (window.Echo) {
        channel = window.Echo.channel('estaciones.global');
        channel.listen('.EstacionesActualizadas', () => {
            console.log('[WebSocket] Estaciones actualizadas por el scheduler de fondo. Recargando lista...');
            fetchEstaciones();
        });
    }
});

onUnmounted(() => {
    if (channel && window.Echo) {
        window.Echo.leave('estaciones.global');
    }
});
</script>

<template>
    <div class="p-6 space-y-6 relative">
        <!-- Indicador de Carga Premium -->
        <div v-if="isLoading"
            class="absolute inset-0 bg-white/40 backdrop-blur-xs z-50 flex items-center justify-center min-h-100 rounded-xl transition-all">
            <div class="bg-white p-4 rounded-xl shadow-lg border border-gray-100 flex items-center gap-3">
                <Loader2 class="w-5 h-5 text-blue-600 animate-spin" />
                <span class="text-sm font-medium text-gray-700">Actualizando estaciones...</span>
            </div>
        </div>

        <!-- Encabezado de Vista -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 flex items-center gap-2">
                    <Laptop class="w-7 h-7 text-blue-600" />
                    Gestión de Estaciones
                </h1>
                <p class="text-sm text-gray-500 mt-1">
                    {{ totalEstaciones }} estaciones de trabajo detectadas y administradas en la red
                </p>
            </div>
            
            <button @click="fetchEstaciones"
                class="inline-flex items-center justify-center bg-white hover:bg-slate-50 text-gray-700 font-medium py-2 px-3 border border-gray-250 rounded-lg shadow-sm gap-1.5 transition-colors text-xs cursor-pointer">
                <RefreshCw class="w-3.5 h-3.5" />
                Actualizar Lista
            </button>
        </div>

        <!-- Bloque de Filtros Unificados -->
        <div class="bg-white p-4 rounded-xl border border-gray-200 shadow-sm">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                
                <!-- Buscador de Texto -->
                <div class="relative md:col-span-2">
                    <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" />
                    <input v-model="searchTerm" type="text" placeholder="Buscar por hostname, IP, MAC o S.O..."
                        class="w-full pl-10 pr-4 py-2 bg-white border border-gray-200 text-gray-900 placeholder:text-gray-400 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm transition-all" />
                </div>

                <!-- Filtrado por Laboratorio -->
                <div>
                    <select v-model="filterLaboratorio"
                        class="w-full bg-white border border-gray-200 text-gray-900 px-3 py-2 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm cursor-pointer">
                        <option value="all">Todos los laboratorios</option>
                        <option v-for="lab in laboratorios" :key="lab.id" :value="lab.id">
                            {{ lab.nombre }}
                        </option>
                    </select>
                </div>

                <!-- Filtrado por Estado -->
                <div>
                    <select v-model="filterEstado"
                        class="w-full bg-white border border-gray-200 text-gray-900 px-3 py-2 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm cursor-pointer">
                        <option value="all">Todos los estados</option>
                        <option value="activa">Activa / Disponible</option>
                        <option value="bloqueado">Kiosko / Bloqueado</option>
                        <!-- <option value="pendiente">Pendiente de Aprobación</option> -->
                        <option value="mantenimiento">Mantenimiento</option>
                        <option value="inactiva">Inactiva</option>
                    </select>
                </div>

            </div>
        </div>

        <!-- Tabla Estructurada Premium -->
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-gray-50 border-b border-gray-200 text-xs font-semibold text-gray-500 uppercase tracking-wider">
                            <th class="px-6 py-4">Estación de Trabajo</th>
                            <th class="px-6 py-4">Laboratorio Asignado</th>
                            <th class="px-6 py-4">Credenciales de Red</th>
                            <th class="px-6 py-4">Sistema Operativo</th>
                            <th class="px-6 py-4">Estado</th>
                            <th class="px-6 py-4">Último Reporte</th>
                            <th class="px-6 py-4 text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 text-sm text-gray-700">
                        <tr v-if="estaciones.length === 0 && !isLoading">
                            <td colspan="7" class="px-6 py-12 text-center text-gray-400 italic">
                                No se encontraron estaciones vinculadas que coincidan con la búsqueda.
                            </td>
                        </tr>
                        
                        <tr v-for="estacion in estaciones" :key="estacion.id" class="hover:bg-gray-50/70 transition-colors">
                            <!-- Estación / Hostname -->
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="bg-slate-100 p-2 rounded-lg border border-slate-200 text-slate-600">
                                        <Laptop class="w-4 h-4" />
                                    </div>
                                    <div>
                                        <p class="font-semibold text-gray-900">{{ estacion.hostname }}</p>
                                        <p class="text-3xs text-gray-400 font-mono tracking-tighter">Agente {{ estacion.version_agente }}</p>
                                    </div>
                                </div>
                            </td>

                            <!-- Laboratorio -->
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-2">
                                    <Building2 class="w-4 h-4 text-gray-400" />
                                    <span :class="[
                                        'font-medium text-xs px-2 py-0.5 rounded border capitalize',
                                        estacion.laboratorio_id ? 'bg-blue-50 text-blue-700 border-blue-150' : 'bg-rose-50 text-rose-700 border-rose-150'
                                    ]">
                                        {{ estacion.nombre_laboratorio }}
                                    </span>
                                </div>
                            </td>

                            <!-- Credenciales de Red (IP & MAC) -->
                            <td class="px-6 py-4 font-mono text-2xs space-y-1">
                                <div><span class="text-gray-400">IP:</span> <span class="text-gray-800 font-bold bg-slate-100 px-1 py-0.5 rounded">{{ estacion.direccion_ip }}</span></div>
                                <div><span class="text-gray-400">MAC:</span> <span class="text-gray-600 uppercase">{{ estacion.direccion_mac }}</span></div>
                            </td>

                            <!-- SO -->
                            <td class="px-6 py-4 text-xs text-gray-600">
                                <span class="truncate max-w-[150px] inline-block" :title="estacion.so_info">
                                    {{ estacion.so_info }}
                                </span>
                            </td>

                            <!-- Estado con Badge Premium -->
                            <td class="px-6 py-4">
                                <span :class="[
                                    'inline-flex items-center gap-1 text-[11px] font-semibold px-2 py-0.5 rounded-full border shadow-2xs uppercase tracking-wider',
                                    estacion.estado === 'activa' ? 'bg-emerald-50 text-emerald-700 border-emerald-200' :
                                    estacion.estado === 'pendiente' ? 'bg-indigo-50 text-indigo-700 border-indigo-200' :
                                    estacion.estado === 'bloqueado' ? 'bg-amber-50 text-amber-800 border-amber-250' :
                                    estacion.estado === 'mantenimiento' ? 'bg-orange-50 text-orange-700 border-orange-200' :
                                    'bg-slate-100 text-slate-700 border-slate-350'
                                ]">
                                    <span class="w-1.5 h-1.5 rounded-full shrink-0 animate-pulse" :class="[
                                        estacion.estado === 'activa' ? 'bg-emerald-500' :
                                        estacion.estado === 'pendiente' ? 'bg-indigo-500' :
                                        estacion.estado === 'bloqueado' ? 'bg-amber-500' :
                                        estacion.estado === 'mantenimiento' ? 'bg-orange-500' :
                                        'bg-slate-500'
                                    ]"></span>
                                    {{ 
                                        estacion.estado === 'activa' ? 'Activa' : 
                                        estacion.estado === 'pendiente' ? 'Pendiente' : 
                                        estacion.estado === 'bloqueado' ? 'Kiosko' : 
                                        estacion.estado === 'mantenimiento' ? 'Mantenimiento' : 
                                        'Inactiva'
                                    }}
                                </span>
                            </td>

                            <!-- Reporte de Conexión -->
                            <td class="px-6 py-4 text-xs text-gray-500 font-mono">
                                {{ formatLastConnection(estacion.ultima_conexion) }}
                            </td>

                            <!-- Acciones de Gestión -->
                            <td class="px-6 py-4 text-right space-x-1.5">
                                <button @click="openTelemetryModal(estacion)"
                                    class="inline-flex items-center justify-center p-2 text-blue-650 hover:text-blue-800 hover:bg-blue-50 rounded-lg transition-colors cursor-pointer"
                                    title="Historial de Telemetría">
                                    <Activity class="w-4 h-4" />
                                </button>
                                
                                <button v-if="authStore.can('estaciones.eliminar')" @click="openDeleteModal(estacion)"
                                    class="inline-flex items-center justify-center p-2 text-red-600 hover:text-red-700 hover:bg-red-50 rounded-lg transition-colors cursor-pointer"
                                    title="Desvincular y Eliminar de la Red">
                                    <Trash2 class="w-4 h-4" />
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Paginador Integrado -->
            <div class="px-6 py-4 border-t border-gray-200 bg-gray-50/50">
                <BasePagination v-model="currentPage" :last-page="lastPage" :total="totalEstaciones" />
            </div>
        </div>

        <!-- Modal de Confirmación de Eliminación -->
        <EstacionDeleteModal :show="isDeleteModalOpen" :estacion="selectedEstacion" :is-deleting="isDeleting"
            @close="isDeleteModalOpen = false" @confirm="handleConfirmDelete" />

        <!-- Modal de Historial de Telemetría -->
        <EstacionTelemetriaModal :show="isTelemetryModalOpen" :estacion="selectedEstacion"
            @close="isTelemetryModalOpen = false" />
    </div>
</template>
