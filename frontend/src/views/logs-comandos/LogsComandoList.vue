<script setup lang="ts">
import { ref, onMounted, watch } from 'vue';
import {
    Search, Trash2, History, Loader2, RefreshCw,
    Terminal, User, Laptop, Eye, X,
    CheckCircle, XCircle
} from '@lucide/vue';
import { logsComandoService } from '@/services/logsComandoService';

// Componentes del Sistema
import BasePagination from '@/components/BasePagination.vue';

// --- ESTADOS REACTIVOS ---
const logs = ref<any[]>([]);
const isLoading = ref(true);

// Filtros
const searchTerm = ref('');
const filterEstado = ref('all');

// Paginación
const currentPage = ref(1);
const lastPage = ref(1);
const totalLogs = ref(0);

// Visualización de Respuesta Ampliada
const isViewerOpen = ref(false);
const selectedLog = ref<any | null>(null);

// --- ACCIONES DE DATOS ---
const fetchLogs = async () => {
    isLoading.value = true;
    try {
        const response = await logsComandoService.getLogsComandos({
            page: currentPage.value,
            search: searchTerm.value || undefined,
            estado: filterEstado.value === 'all' ? undefined : filterEstado.value
        });
        logs.value = response.data || [];
        currentPage.value = response.current_page || 1;
        lastPage.value = response.last_page || 1;
        totalLogs.value = response.total || 0;
    } catch (error) {
        console.error('Error al cargar logs de comandos:', error);
    } finally {
        isLoading.value = false;
    }
};

let debounceTimeout: ReturnType<typeof setTimeout>;
watch(searchTerm, () => {
    clearTimeout(debounceTimeout);
    debounceTimeout = setTimeout(() => {
        currentPage.value = 1;
        fetchLogs();
    }, 450);
});

watch(filterEstado, () => {
    currentPage.value = 1;
    fetchLogs();
});

watch(currentPage, () => {
    fetchLogs();
});

const handleDeleteLog = async (id: number) => {
    isLoading.value = true;
    try {
        await logsComandoService.eliminarLogComando(id);
        if (logs.value.length === 1 && currentPage.value > 1) {
            currentPage.value--;
        } else {
            await fetchLogs();
        }
    } catch (error) {
        console.error('Error al eliminar log de comando:', error);
    } finally {
        isLoading.value = false;
    }
};

const openViewer = (log: any) => {
    selectedLog.value = log;
    isViewerOpen.value = true;
};

onMounted(() => {
    fetchLogs();
});
</script>

<template>
    <div class="p-6 space-y-6 relative">
        <!-- Loader Overlay -->
        <div v-if="isLoading"
            class="absolute inset-0 bg-white/40 backdrop-blur-xs z-50 flex items-center justify-center min-h-100 rounded-xl transition-all">
            <div class="bg-white p-4 rounded-xl shadow-lg border border-gray-100 flex items-center gap-3">
                <Loader2 class="w-5 h-5 text-blue-600 animate-spin" />
                <span class="text-sm font-medium text-gray-700">Analizando historial de comandos...</span>
            </div>
        </div>

        <!-- Encabezado -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 flex items-center gap-2">
                    <History class="w-7 h-7 text-blue-600" />
                    Historial de Comandos
                </h1>
                <p class="text-sm text-gray-500 mt-1">
                    {{ totalLogs }} transacciones de comandos ejecutadas y registradas en las estaciones de trabajo
                </p>
            </div>

            <button @click="fetchLogs"
                class="inline-flex items-center justify-center bg-white hover:bg-slate-50 text-gray-700 font-medium py-2 px-3 border border-gray-250 rounded-lg shadow-sm gap-1.5 transition-colors text-xs cursor-pointer">
                <RefreshCw class="w-3.5 h-3.5" />
                Actualizar Lista
            </button>
        </div>

        <!-- Filtros -->
        <div class="bg-white p-4 rounded-xl border border-gray-200 shadow-sm">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="relative md:col-span-2">
                    <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" />
                    <input v-model="searchTerm" type="text"
                        placeholder="Buscar por origen, respuesta, usuario o estación..."
                        class="w-full pl-10 pr-4 py-2 bg-white border border-gray-200 text-gray-900 placeholder:text-gray-400 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm transition-all" />
                </div>

                <select v-model="filterEstado"
                    class="bg-white border border-gray-200 text-gray-900 px-3 py-2 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm cursor-pointer">
                    <option value="all">Todos los estados</option>
                    <option value="exitoso">Exitosos</option>
                    <option value="fallido">Fallidos</option>
                    <option value="pendiente">Pendientes de Ejecución</option>
                </select>
            </div>
        </div>

        <!-- Tabla -->
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr
                            class="bg-gray-50 border-b border-gray-200 text-xs font-semibold text-gray-500 uppercase tracking-wider">
                            <th class="px-6 py-4">Usuario Emisor</th>
                            <th class="px-6 py-4">Estación Destino</th>
                            <th class="px-6 py-4">Comando Enviado</th>
                            <th class="px-6 py-4">Origen / Vía</th>
                            <th class="px-6 py-4">Estado Ejecución</th>
                            <th class="px-6 py-4">Fecha y Hora</th>
                            <th class="px-6 py-4 text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 text-sm text-gray-700 font-medium">
                        <tr v-if="logs.length === 0 && !isLoading">
                            <td colspan="7" class="px-6 py-12 text-center text-gray-400 italic">
                                No se encontraron registros de ejecución en el historial.
                            </td>
                        </tr>

                        <tr v-for="log in logs" :key="log.id" class="hover:bg-gray-50/70 transition-colors">
                            <!-- Usuario -->
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-2">
                                    <User class="w-4 h-4 text-slate-400" />
                                    <div>
                                        <p class="text-gray-900 font-semibold">{{ log.usuario?.name || 'Sistema' }}</p>
                                        <p class="text-3xs text-gray-400 font-mono tracking-tight">{{ log.usuario?.email
                                            }}</p>
                                    </div>
                                </div>
                            </td>

                            <!-- Estacion -->
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-2">
                                    <Laptop class="w-4 h-4 text-slate-400" />
                                    <div>
                                        <p class="text-slate-800 font-semibold font-mono">{{ log.estacion?.hostname }}
                                        </p>
                                        <p class="text-3xs text-gray-400 font-mono tracking-tight">{{
                                            log.estacion?.direccion_ip }}</p>
                                    </div>
                                </div>
                            </td>

                            <!-- Comando -->
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-1.5 font-bold font-mono text-xs">
                                    <Terminal class="w-3.5 h-3.5 text-blue-600 shrink-0" />
                                    <span class="bg-blue-50 border border-blue-150 text-blue-700 px-2 py-0.5 rounded">
                                        {{ log.comando?.nombre }}
                                    </span>
                                </div>
                            </td>

                            <!-- Origen / Via -->
                            <td class="px-6 py-4 text-xs font-semibold uppercase tracking-wider text-gray-500">
                                {{ log.origen }}
                            </td>

                            <!-- Estado -->
                            <td class="px-6 py-4">
                                <span :class="[
                                    'inline-flex items-center gap-1 text-[10px] font-bold px-2 py-0.5 rounded-full border uppercase tracking-wider shadow-3xs',
                                    log.estado === 'exitoso' || log.estado === 'success' ? 'bg-emerald-50 text-emerald-700 border-emerald-200' :
                                        log.estado === 'fallido' || log.estado === 'failed' ? 'bg-rose-50 text-rose-700 border-rose-200' :
                                            'bg-indigo-50 text-indigo-700 border-indigo-200'
                                ]">
                                    <component
                                        :is="log.estado === 'exitoso' || log.estado === 'success' ? CheckCircle : XCircle"
                                        class="w-3 h-3 shrink-0" />
                                    {{ log.estado }}
                                </span>
                            </td>

                            <!-- Fecha -->
                            <td class="px-6 py-4 text-xs font-mono text-gray-500">
                                {{ new Date(log.created_at).toLocaleString() }}
                            </td>

                            <!-- Acciones -->
                            <td class="px-6 py-4 text-right space-x-1.5">
                                <button @click="openViewer(log)"
                                    class="inline-flex items-center justify-center p-2 text-blue-600 hover:text-blue-700 hover:bg-blue-50 rounded-lg transition-colors cursor-pointer"
                                    title="Ver Detalles de Respuesta">
                                    <Eye class="w-4 h-4" />
                                </button>
                                <button @click="handleDeleteLog(log.id)"
                                    class="inline-flex items-center justify-center p-2 text-red-600 hover:text-red-700 hover:bg-red-50 rounded-lg transition-colors cursor-pointer"
                                    title="Eliminar Log de Transacción">
                                    <Trash2 class="w-4 h-4" />
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Paginador -->
            <div class="px-6 py-4 border-t border-gray-200 bg-gray-50/50">
                <BasePagination v-model="currentPage" :last-page="lastPage" :total="totalLogs" />
            </div>
        </div>

        <!-- Visor de Respuesta del Agente C# (Slideover/Modal) -->
        <Transition enter-active-class="transition-all duration-300 ease-out"
            enter-from-class="opacity-0 backdrop-blur-none" enter-to-class="opacity-100 backdrop-blur-md"
            leave-active-class="transition-all duration-200 ease-in" leave-from-class="opacity-100 backdrop-blur-md"
            leave-to-class="opacity-0 backdrop-blur-none">
            <div v-if="isViewerOpen"
                class="fixed inset-0 bg-slate-900/50 backdrop-blur-xs z-50 flex items-center justify-center p-4">
                <div
                    class="bg-white rounded-xl border border-gray-100 max-w-lg w-full shadow-2xl relative overflow-hidden flex flex-col animate-in fade-in zoom-in-95 duration-150">

                    <div class="px-6 pt-6 pb-4 flex items-start justify-between border-b border-gray-100">
                        <div class="flex items-center gap-3">
                            <div class="bg-blue-50 text-blue-600 p-2.5 rounded-lg border border-blue-100 shrink-0">
                                <Terminal class="w-5 h-5" />
                            </div>
                            <div>
                                <h3 class="text-sm font-bold text-gray-900">Respuesta del Agente Remoto</h3>
                                <p class="text-xs text-gray-400 mt-0.5 font-mono">ID Transacción: #{{ selectedLog?.id }}
                                </p>
                            </div>
                        </div>
                        <button @click="isViewerOpen = false"
                            class="text-gray-400 hover:text-gray-600 hover:bg-gray-100 p-1.5 rounded-lg transition-colors cursor-pointer">
                            <X class="w-4 h-4" />
                        </button>
                    </div>

                    <div class="p-6 space-y-4">
                        <div class="space-y-1">
                            <h4 class="text-3xs font-bold text-gray-400 uppercase tracking-widest">Ejecución</h4>
                            <p class="text-xs text-gray-800 leading-relaxed font-semibold">
                                El usuario <span class="text-blue-600">{{ selectedLog?.usuario?.name }}</span> envió el
                                comando <span class="font-mono bg-slate-100 px-1 py-0.5 rounded font-bold">{{
                                    selectedLog?.comando?.nombre }}</span> a la máquina <span
                                    class="font-mono font-bold">{{ selectedLog?.estacion?.hostname }}</span>.
                            </p>
                        </div>

                        <div class="space-y-1">
                            <h4 class="text-3xs font-bold text-gray-400 uppercase tracking-widest">Terminal / Sensor de
                                Respuesta</h4>
                            <div
                                class="bg-slate-950 text-slate-100 font-mono text-2xs p-4 rounded-lg overflow-x-auto shadow-inner leading-relaxed select-all">
                                <p v-if="selectedLog?.mensaje_respuesta" class="whitespace-pre-line">{{
                                    selectedLog.mensaje_respuesta }}</p>
                                <p v-else class="text-slate-500 italic">Ningún mensaje de respuesta devuelto por el
                                    agente de NexusLab.</p>
                            </div>
                        </div>
                    </div>

                    <div class="px-6 py-4 bg-gray-50 border-t border-gray-100 flex justify-end">
                        <button @click="isViewerOpen = false"
                            class="px-4 py-2 border border-gray-200 text-gray-700 bg-white rounded-lg hover:bg-gray-100 hover:border-gray-300 transition-all text-xs font-medium cursor-pointer">
                            Cerrar Visor
                        </button>
                    </div>

                </div>
            </div>
        </Transition>
    </div>
</template>
