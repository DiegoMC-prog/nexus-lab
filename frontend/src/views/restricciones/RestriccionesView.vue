<script setup lang="ts">
import { ref, onMounted, onUnmounted, watch } from 'vue';
import {
    Shield,
    ShieldAlert,
    Search,
    Plus,
    Edit,
    Trash2,
    Loader2,
    Building2,
    Clock,
    Laptop,
    User,
    Check,
    X,
    Server,
    Sliders,
    AlertTriangle
} from '@lucide/vue';
import { restriccionService } from '@/services/restriccionService';
import { laboratorioService } from '@/services/laboratorioService';
import type { Restriccion, RestriccionFormData, LogAplicacionProhibida } from '@/types/restriccion';
import type { Laboratorio } from '@/types/laboratorio';

// Estado de Pestañas
const activeTab = ref<'politicas' | 'infracciones'>('politicas');

// Carga e Indicadores
const loadingPoliticas = ref(false);
const loadingLogs = ref(false);
const loadingAction = ref(false);
const pollingInterval = ref<any>(null);
const pollingPulse = ref(false);

// Listados y Datos Paginados
const restricciones = ref<Restriccion[]>([]);
const logs = ref<LogAplicacionProhibida[]>([]);
const laboratorios = ref<Laboratorio[]>([]);

// Paginación Políticas
const currentPagePoliticas = ref(1);
const totalPagesPoliticas = ref(1);
const totalPoliticas = ref(0);

// Paginación Logs
const currentPageLogs = ref(1);
const totalPagesLogs = ref(1);
const totalLogs = ref(0);

// Filtros y Búsquedas
const searchPoliticas = ref('');
const filterLabPoliticas = ref('all');
const searchLogs = ref('');

// Estado del Modal
const showModal = ref(false);
const showDeleteModal = ref(false);
const selectedRestriccion = ref<Restriccion | null>(null);
const validationErrors = ref<Record<string, string[]>>({});

// Formulario reactivo
const formData = ref<RestriccionFormData>({
    laboratorio_id: null,
    nombre_aplicacion: '',
    nombre_proceso: '',
    tipo_restriccion: 'bloqueo_total',
    activo: true
});

// Mensajes Flash
const toastMessage = ref('');
const toastType = ref<'success' | 'error'>('success');
const showToast = ref(false);

const triggerToast = (msg: string, type: 'success' | 'error' = 'success') => {
    toastMessage.value = msg;
    toastType.value = type;
    showToast.value = true;
    setTimeout(() => {
        showToast.value = false;
    }, 4000);
};

// Cargar Políticas
const fetchPoliticas = async (page = 1) => {
    loadingPoliticas.value = true;
    try {
        const response = await restriccionService.getRestricciones({
            search: searchPoliticas.value,
            laboratorio_id: filterLabPoliticas.value,
            page
        });
        restricciones.value = response.data;
        currentPagePoliticas.value = response.current_page;
        totalPagesPoliticas.value = response.last_page;
        totalPoliticas.value = response.total;
    } catch (e: any) {
        triggerToast('Error al cargar las políticas de restricción.', 'error');
    } finally {
        loadingPoliticas.value = false;
    }
};

// Cargar Logs (Infracciones)
const fetchLogs = async (page = 1, isSilent = false) => {
    if (!isSilent) loadingLogs.value = true;
    pollingPulse.value = true;
    try {
        const response = await restriccionService.getLogs({
            search: searchLogs.value,
            page
        });
        logs.value = response.data;
        currentPageLogs.value = response.current_page;
        totalPagesLogs.value = response.last_page;
        totalLogs.value = response.total;
    } catch (e: any) {
        if (!isSilent) triggerToast('Error al cargar el historial de infracciones.', 'error');
    } finally {
        if (!isSilent) loadingLogs.value = false;
        setTimeout(() => {
            pollingPulse.value = false;
        }, 1000);
    }
};

// Cargar Laboratorios para el selector
const fetchLaboratorios = async () => {
    try {
        const response = await laboratorioService.getLaboratorios({ page: 1, activo: 'all' });
        laboratorios.value = response.data;
    } catch (e) {
        console.error('Error al cargar laboratorios:', e);
    }
};

// Iniciar/Detener Polling
const startPolling = () => {
    stopPolling();
    pollingInterval.value = setInterval(() => {
        if (activeTab.value === 'infracciones') {
            fetchLogs(currentPageLogs.value, true);
        }
    }, 20000);
};

const stopPolling = () => {
    if (pollingInterval.value) {
        clearInterval(pollingInterval.value);
        pollingInterval.value = null;
    }
};

// Toggle de Estado Activo/Inactivo rápido
const toggleActivo = async (restriccion: Restriccion) => {
    loadingAction.value = true;
    const originalValue = restriccion.activo;
    restriccion.activo = !originalValue; // Optimistic update
    try {
        await restriccionService.actualizarRestriccion(restriccion.id, {
            laboratorio_id: restriccion.laboratorio_id,
            nombre_aplicacion: restriccion.nombre_aplicacion,
            nombre_proceso: restriccion.nombre_proceso,
            tipo_restriccion: restriccion.tipo_restriccion,
            activo: restriccion.activo
        });
        triggerToast(`Restricción de "${restriccion.nombre_aplicacion}" ${restriccion.activo ? 'activada' : 'desactivada'} correctamente.`);
    } catch (e) {
        restriccion.activo = originalValue; // Rollback
        triggerToast('No se pudo actualizar el estado de la restricción.', 'error');
    } finally {
        loadingAction.value = false;
    }
};

// Modales CRUD
const openAddModal = () => {
    selectedRestriccion.value = null;
    formData.value = {
        laboratorio_id: null,
        nombre_aplicacion: '',
        nombre_proceso: '',
        tipo_restriccion: 'bloqueo_total',
        activo: true
    };
    validationErrors.value = {};
    showModal.value = true;
};

const openEditModal = (restriccion: Restriccion) => {
    selectedRestriccion.value = restriccion;
    formData.value = {
        laboratorio_id: restriccion.laboratorio_id,
        nombre_aplicacion: restriccion.nombre_aplicacion,
        nombre_proceso: restriccion.nombre_proceso,
        tipo_restriccion: restriccion.tipo_restriccion,
        activo: restriccion.activo
    };
    validationErrors.value = {};
    showModal.value = true;
};

const openDeleteModal = (restriccion: Restriccion) => {
    selectedRestriccion.value = restriccion;
    showDeleteModal.value = true;
};

const saveRestriccion = async () => {
    loadingAction.value = true;
    validationErrors.value = {};
    try {
        if (selectedRestriccion.value) {
            await restriccionService.actualizarRestriccion(selectedRestriccion.value.id, formData.value);
            triggerToast('Restricción actualizada correctamente.');
        } else {
            await restriccionService.crearRestriccion(formData.value);
            triggerToast('Nueva restricción agregada correctamente.');
        }
        showModal.value = false;
        fetchPoliticas(1);
    } catch (e: any) {
        if (e.response?.status === 422) {
            validationErrors.value = e.response.data.errors;
        } else {
            triggerToast('Error al guardar la restricción.', 'error');
        }
    } finally {
        loadingAction.value = false;
    }
};

const deleteRestriccion = async () => {
    if (!selectedRestriccion.value) return;
    loadingAction.value = true;
    try {
        await restriccionService.eliminarRestriccion(selectedRestriccion.value.id);
        triggerToast('Restricción eliminada permanentemente.');
        showDeleteModal.value = false;
        fetchPoliticas(currentPagePoliticas.value);
    } catch (e) {
        triggerToast('No se pudo eliminar la restricción.', 'error');
    } finally {
        loadingAction.value = false;
    }
};

// Formateadores de fecha
const formatDate = (dateStr: string) => {
    if (!dateStr) return '';
    const date = new Date(dateStr);
    return date.toLocaleString('es-ES', {
        year: 'numeric',
        month: 'short',
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit'
    });
};

// Vigilantes
watch(activeTab, (newTab) => {
    if (newTab === 'infracciones') {
        fetchLogs(1);
        startPolling();
    } else {
        fetchPoliticas(1);
        stopPolling();
    }
});

// React a búsquedas y filtros
watch([searchPoliticas, filterLabPoliticas], () => {
    fetchPoliticas(1);
});

watch(searchLogs, () => {
    fetchLogs(1);
});

onMounted(() => {
    fetchPoliticas(1);
    fetchLaboratorios();
});

onUnmounted(() => {
    stopPolling();
});
</script>

<template>
    <div class="p-6 space-y-6 bg-slate-50 min-h-screen select-none">
        
        <!-- Encabezado Principal Premium del Sistema -->
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 bg-white p-6 rounded-2xl border border-slate-200/80 shadow-xs relative overflow-hidden">
            <div class="absolute right-0 top-0 -mt-6 -mr-6 w-32 h-32 bg-blue-50/50 rounded-full blur-3xl pointer-events-none"></div>
            <div class="flex items-center gap-4 relative z-10">
                <div class="bg-blue-50 text-blue-600 border border-blue-100 p-3.5 rounded-2xl shadow-3xs flex items-center justify-center shrink-0">
                    <Shield class="w-6.5 h-6.5" />
                </div>
                <div>
                    <h1 class="text-2xl font-black text-slate-900 leading-tight">Restricción de Aplicaciones</h1>
                    <p class="text-sm text-slate-500 font-medium mt-1">Controla, restringe y monitorea el software no autorizado en todos los laboratorios.</p>
                </div>
            </div>
            <div class="flex items-center gap-2.5 relative z-10" v-if="activeTab === 'politicas'">
                <button @click="openAddModal"
                    class="inline-flex items-center justify-center bg-blue-600 hover:bg-blue-700 active:bg-blue-800 text-white font-bold py-2.5 px-4 rounded-xl shadow-xs gap-2 transition-all text-xs cursor-pointer">
                    <Plus class="w-4 h-4" />
                    Nueva Restricción
                </button>
            </div>
        </div>

        <!-- Panel de Pestañas con el Diseño del Sistema -->
        <div class="flex border border-slate-200 p-1 bg-slate-100/80 rounded-xl max-w-md shadow-xs">
            <button @click="activeTab = 'politicas'"
                :class="activeTab === 'politicas' ? 'bg-white text-blue-650 font-black border border-slate-200/60 shadow-3xs' : 'text-slate-500 hover:text-slate-800 font-bold'"
                class="flex-1 py-2 px-4 text-xs rounded-lg transition-all duration-200 cursor-pointer flex items-center justify-center gap-2">
                <Sliders class="w-4 h-4" />
                Políticas de Restricción
            </button>
            <button @click="activeTab = 'infracciones'"
                :class="activeTab === 'infracciones' ? 'bg-white text-red-600 font-black border border-slate-200/60 shadow-3xs' : 'text-slate-500 hover:text-slate-800 font-bold'"
                class="flex-1 py-2 px-4 text-xs rounded-lg transition-all duration-200 cursor-pointer flex items-center justify-center gap-2">
                <ShieldAlert class="w-4 h-4" />
                Registro de Infracciones
                <span v-if="pollingPulse" class="relative flex h-2 w-2">
                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-red-400 opacity-75"></span>
                    <span class="relative inline-flex rounded-full h-2 w-2 bg-red-500"></span>
                </span>
            </button>
        </div>

        <!-- Pestaña 1: Políticas de Restricción -->
        <div v-if="activeTab === 'politicas'" class="space-y-4 animate-in fade-in duration-200">
            <!-- Barra de Filtros de Políticas -->
            <div class="flex flex-col md:flex-row gap-4 bg-white border border-slate-200/80 p-4 rounded-xl shadow-xs">
                <!-- Búsqueda -->
                <div class="flex-1 relative">
                    <Search class="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 w-4.5 h-4.5" />
                    <input v-model="searchPoliticas" type="text" placeholder="Buscar por aplicación o ejecutable..."
                        class="w-full pl-10 pr-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:outline-hidden focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 text-slate-800 placeholder:text-slate-400 text-sm shadow-inner-2xs transition-all" />
                </div>
                <!-- Filtrar por Lab -->
                <div class="w-full md:w-64 relative">
                    <Building2 class="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 w-4.5 h-4.5" />
                    <select v-model="filterLabPoliticas"
                        class="w-full pl-10 pr-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:outline-hidden focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 text-slate-800 text-sm shadow-inner-2xs transition-all cursor-pointer appearance-none">
                        <option value="all">Todos los Laboratorios</option>
                        <option value="global">Institucionales (Globales)</option>
                        <option v-for="lab in laboratorios" :key="lab.id" :value="lab.id">
                            {{ lab.nombre }}
                        </option>
                    </select>
                </div>
            </div>

            <!-- Tabla de Políticas -->
            <div class="bg-white border border-slate-200/80 rounded-2xl overflow-hidden shadow-xs">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-slate-50 border-b border-slate-200 text-[11px] font-bold text-slate-500 uppercase tracking-wider">
                                <th class="px-6 py-4">Aplicación</th>
                                <th class="px-6 py-4">Proceso / Ejecutable</th>
                                <th class="px-6 py-4">Laboratorio Asignado</th>
                                <th class="px-6 py-4">Tipo Restricción</th>
                                <th class="px-6 py-4 text-center">Estado</th>
                                <th class="px-6 py-4 text-right">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-slate-700 text-sm">
                            <tr v-if="loadingPoliticas" class="hover:bg-transparent">
                                <td colspan="6" class="px-6 py-12 text-center text-slate-400 font-semibold">
                                    <div class="flex flex-col items-center justify-center gap-3">
                                        <Loader2 class="w-8 h-8 text-blue-600 animate-spin" />
                                        <span>Cargando políticas de restricción...</span>
                                    </div>
                                </td>
                            </tr>
                            <tr v-else-if="restricciones.length === 0" class="hover:bg-transparent">
                                <td colspan="6" class="px-6 py-12 text-center text-slate-400">
                                    <div class="flex flex-col items-center justify-center gap-2">
                                        <Shield class="w-10 h-10 text-slate-300" />
                                        <span class="font-bold text-slate-500 text-sm">No se encontraron restricciones</span>
                                        <span class="text-xs text-slate-400">Prueba ajustando los filtros de búsqueda o agrega una nueva.</span>
                                    </div>
                                </td>
                            </tr>
                            <tr v-else v-for="res in restricciones" :key="res.id" class="hover:bg-slate-50/50 transition-colors">
                                <td class="px-6 py-4">
                                    <span class="font-extrabold text-slate-900">{{ res.nombre_aplicacion }}</span>
                                </td>
                                <td class="px-6 py-4">
                                    <code class="px-2.5 py-1 bg-slate-50 border border-slate-200 rounded-lg text-red-600 text-xs font-mono font-bold">{{ res.nombre_proceso }}</code>
                                </td>
                                <td class="px-6 py-4">
                                    <span v-if="res.laboratorio" class="inline-flex items-center gap-1.5 text-3xs font-extrabold px-2.5 py-1 bg-blue-50 border border-blue-100 rounded-full text-blue-700 uppercase tracking-wider">
                                        <Building2 class="w-3 h-3" />
                                        {{ res.laboratorio.nombre }}
                                    </span>
                                    <span v-else class="inline-flex items-center gap-1.5 text-3xs font-extrabold px-2.5 py-1 bg-violet-50 border border-violet-100 rounded-full text-violet-700 uppercase tracking-wider">
                                        <Server class="w-3 h-3" />
                                        Global (Todos)
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="capitalize text-xs font-semibold px-2.5 py-0.5 rounded-sm bg-blue-50 text-blue-700 border border-blue-100/50">
                                        {{ res.tipo_restriccion.replace('_', ' ') }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <!-- Toggle Estético Premium (Conforme al del Sistema) -->
                                    <button @click="toggleActivo(res)" :disabled="loadingAction"
                                        class="relative inline-flex h-5 w-9 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-hidden disabled:opacity-50"
                                        :class="res.activo ? 'bg-blue-600' : 'bg-slate-250'">
                                        <span class="pointer-events-none inline-block h-4 w-4 transform rounded-full bg-white shadow-sm ring-0 transition duration-200 ease-in-out"
                                            :class="res.activo ? 'translate-x-4' : 'translate-x-0'"></span>
                                    </button>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <button @click="openEditModal(res)"
                                            class="p-2 bg-white hover:bg-slate-50 text-blue-600 border border-slate-200 hover:border-slate-350 rounded-xl shadow-3xs transition-all cursor-pointer">
                                            <Edit class="w-3.5 h-3.5" />
                                        </button>
                                        <button @click="openDeleteModal(res)"
                                            class="p-2 bg-white hover:bg-red-50 text-red-600 border border-slate-200 hover:border-red-200 rounded-xl shadow-3xs transition-all cursor-pointer">
                                            <Trash2 class="w-3.5 h-3.5" />
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Paginación de Políticas -->
                <div v-if="totalPagesPoliticas > 1" class="px-6 py-4 bg-slate-55/30 border-t border-slate-200 flex items-center justify-between">
                    <span class="text-xs font-semibold text-slate-500">Mostrando {{ restricciones.length }} de {{ totalPoliticas }} restricciones</span>
                    <div class="flex items-center gap-1.5">
                        <button @click="fetchPoliticas(currentPagePoliticas - 1)" :disabled="currentPagePoliticas === 1"
                            class="px-3.5 py-1.5 bg-white border border-slate-200 hover:bg-slate-50 rounded-lg text-xs font-bold text-slate-700 shadow-3xs transition disabled:opacity-40 disabled:cursor-not-allowed">
                            Anterior
                        </button>
                        <span class="text-xs font-bold text-slate-655 px-3">Página {{ currentPagePoliticas }} de {{ totalPagesPoliticas }}</span>
                        <button @click="fetchPoliticas(currentPagePoliticas + 1)" :disabled="currentPagePoliticas === totalPagesPoliticas"
                            class="px-3.5 py-1.5 bg-white border border-slate-200 hover:bg-slate-50 rounded-lg text-xs font-bold text-slate-700 shadow-3xs transition disabled:opacity-40 disabled:cursor-not-allowed">
                            Siguiente
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Pestaña 2: Registro de Infracciones (Logs) -->
        <div v-if="activeTab === 'infracciones'" class="space-y-4 animate-in fade-in duration-200">
            <!-- Barra de Filtros de Logs -->
            <div class="flex flex-col md:flex-row gap-4 bg-white border border-slate-200/80 p-4 rounded-xl items-center justify-between shadow-xs">
                <!-- Búsqueda -->
                <div class="w-full md:flex-1 relative">
                    <Search class="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 w-4.5 h-4.5" />
                    <input v-model="searchLogs" type="text" placeholder="Buscar por ejecutable, equipo o usuario..."
                        class="w-full pl-10 pr-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:outline-hidden focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 text-slate-800 placeholder:text-slate-400 text-sm shadow-inner-2xs transition-all" />
                </div>
                
                <!-- Indicador de Polling -->
                <div class="flex items-center gap-2 bg-slate-50 border border-slate-200/60 px-3 py-1.5 rounded-lg">
                    <span class="relative flex h-2 w-2">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
                    </span>
                    <span class="text-xs text-slate-500 font-bold">Auto-refresco activo (20s)</span>
                </div>
            </div>

            <!-- Tabla de Logs -->
            <div class="bg-white border border-slate-200/80 rounded-2xl overflow-hidden shadow-xs">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-slate-55 border-b border-slate-200 text-[11px] font-bold text-slate-500 uppercase tracking-wider">
                                <th class="px-6 py-4">Estación / Equipo</th>
                                <th class="px-6 py-4">Usuario</th>
                                <th class="px-6 py-4">Proceso Detectado</th>
                                <th class="px-6 py-4">Ruta Ejecutable</th>
                                <th class="px-6 py-4">Acción Tomada</th>
                                <th class="px-6 py-4 text-right">Fecha y Hora</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-slate-700 text-sm">
                            <tr v-if="loadingLogs" class="hover:bg-transparent">
                                <td colspan="6" class="px-6 py-12 text-center text-slate-400 font-semibold">
                                    <div class="flex flex-col items-center justify-center gap-3">
                                        <Loader2 class="w-8 h-8 text-red-500 animate-spin" />
                                        <span>Cargando registro de infracciones...</span>
                                    </div>
                                </td>
                            </tr>
                            <tr v-else-if="logs.length === 0" class="hover:bg-transparent">
                                <td colspan="6" class="px-6 py-12 text-center text-slate-400">
                                    <div class="flex flex-col items-center justify-center gap-2">
                                        <ShieldAlert class="w-10 h-10 text-slate-300" />
                                        <span class="font-bold text-slate-500 text-sm">Sin infracciones registradas</span>
                                        <span class="text-xs text-slate-400">No se han detectado procesos no autorizados en los equipos.</span>
                                    </div>
                                </td>
                            </tr>
                            <tr v-else v-for="log in logs" :key="log.id" class="hover:bg-slate-50/50 transition-colors">
                                <td class="px-6 py-4 font-bold text-slate-900">
                                    <span class="inline-flex items-center gap-2 text-xs font-semibold px-2 py-1 bg-slate-50 border border-slate-200 rounded-lg text-slate-700">
                                        <Laptop class="w-3.5 h-3.5 text-blue-500" />
                                        {{ log.estacion?.hostname || `ID: ${log.estacion_id}` }}
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <span v-if="log.usuario" class="inline-flex items-center gap-1.5 text-xs font-semibold px-2.5 py-1 bg-slate-50 border border-slate-200 rounded-lg text-slate-655">
                                        <User class="w-3.5 h-3.5 text-indigo-500" />
                                        {{ log.usuario.name }}
                                    </span>
                                    <span v-else class="text-xs text-slate-400 italic font-medium">Sin sesión iniciada</span>
                                </td>
                                <td class="px-6 py-4">
                                    <code class="px-2 py-1 bg-slate-50 border border-slate-200 rounded-md text-red-650 text-xs font-mono font-bold">{{ log.nombre_proceso }}</code>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="text-xs font-mono text-slate-500 truncate block max-w-xs" :title="log.ruta_ejecutable || ''">
                                        {{ log.ruta_ejecutable || 'N/A' }}
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="inline-flex items-center gap-1 text-xs font-black px-2.5 py-0.5 rounded-full bg-red-50 border border-red-100 text-red-700">
                                        <AlertTriangle class="w-3.5 h-3.5 shrink-0 text-red-500" />
                                        {{ log.accion_tomada }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-right text-xs text-slate-500 font-bold">
                                    <span class="inline-flex items-center gap-1.5">
                                        <Clock class="w-3.5 h-3.5 text-slate-400" />
                                        {{ formatDate(log.created_at) }}
                                    </span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Paginación de Logs -->
                <div v-if="totalPagesLogs > 1" class="px-6 py-4 bg-slate-55/30 border-t border-slate-200 flex items-center justify-between">
                    <span class="text-xs font-semibold text-slate-500">Mostrando {{ logs.length }} de {{ totalLogs }} infracciones</span>
                    <div class="flex items-center gap-1.5">
                        <button @click="fetchLogs(currentPageLogs - 1)" :disabled="currentPageLogs === 1"
                            class="px-3.5 py-1.5 bg-white border border-slate-200 hover:bg-slate-50 rounded-lg text-xs font-bold text-slate-700 shadow-3xs transition disabled:opacity-40 disabled:cursor-not-allowed">
                            Anterior
                        </button>
                        <span class="text-xs font-bold text-slate-655 px-3">Página {{ currentPageLogs }} de {{ totalPagesLogs }}</span>
                        <button @click="fetchLogs(currentPageLogs + 1)" :disabled="currentPageLogs === totalPagesLogs"
                            class="px-3.5 py-1.5 bg-white border border-slate-200 hover:bg-slate-50 rounded-lg text-xs font-bold text-slate-700 shadow-3xs transition disabled:opacity-40 disabled:cursor-not-allowed">
                            Siguiente
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- MODAL CRUD ANIMADO (FONDO CLARO DEL SISTEMA) -->
        <Transition enter-active-class="transition-all duration-300 ease-out"
            enter-from-class="opacity-0 backdrop-blur-none" enter-to-class="opacity-100 backdrop-blur-md"
            leave-active-class="transition-all duration-200 ease-in" leave-from-class="opacity-100 backdrop-blur-md"
            leave-to-class="opacity-0 backdrop-blur-none">
            <div v-if="showModal"
                class="fixed inset-0 bg-slate-900/50 backdrop-blur-xs z-50 flex items-center justify-center p-4">
                <div class="bg-white rounded-2xl border border-slate-100 max-w-md w-full shadow-2xl relative overflow-hidden flex flex-col animate-in fade-in zoom-in-95 duration-150 text-slate-800">
                    <!-- Encabezado Modal -->
                    <div class="px-6 pt-6 pb-4 flex items-start justify-between border-b border-slate-150">
                        <div class="flex items-center gap-3">
                            <div :class="selectedRestriccion ? 'bg-blue-50 text-blue-600 border-blue-100' : 'bg-emerald-50 text-emerald-600 border-emerald-100'"
                                class="p-2.5 rounded-xl shrink-0 border shadow-3xs">
                                <Shield class="w-5 h-5" />
                            </div>
                            <div>
                                <h3 class="text-lg font-black text-slate-900">
                                    {{ selectedRestriccion ? 'Editar Restricción' : 'Nueva Restricción' }}
                                </h3>
                                <p class="text-xs text-slate-500 mt-0.5">
                                    Configura las reglas de bloqueo automático en los laboratorios.
                                </p>
                            </div>
                        </div>
                        <button @click="showModal = false" :disabled="loadingAction"
                            class="text-slate-400 hover:text-slate-600 hover:bg-slate-100 p-1.5 rounded-lg transition cursor-pointer disabled:opacity-30 disabled:cursor-not-allowed">
                            <X class="w-4.5 h-4.5" />
                        </button>
                    </div>

                    <!-- Cuerpo Formulario -->
                    <div class="p-6 space-y-4">
                        <!-- Nombre Aplicación -->
                        <div class="space-y-1.5">
                            <label class="text-slate-655 text-xs font-bold uppercase tracking-wider">Nombre de la Aplicación</label>
                            <input v-model="formData.nombre_aplicacion" type="text" placeholder="Ej. uTorrent, BitTorrent"
                                :disabled="loadingAction"
                                class="w-full px-3.5 py-2.5 bg-slate-55 border border-slate-200 rounded-xl focus:outline-hidden focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 text-slate-800 text-sm shadow-xs transition placeholder:text-slate-400 disabled:opacity-50"
                                :class="{ 'border-red-500 focus:ring-red-500/10': validationErrors?.nombre_aplicacion }" />
                            <p v-if="validationErrors?.nombre_aplicacion" class="text-xs text-red-500 mt-1 block font-semibold">
                                {{ validationErrors.nombre_aplicacion[0] }}
                            </p>
                        </div>

                        <!-- Nombre Proceso -->
                        <div class="space-y-1.5">
                            <label class="text-slate-655 text-xs font-bold uppercase tracking-wider">Proceso a Bloquear (.exe)</label>
                            <input v-model="formData.nombre_proceso" type="text" placeholder="Ej. utorrent.exe, bittorrent.exe"
                                :disabled="loadingAction"
                                class="w-full px-3.5 py-2.5 bg-slate-55 border border-slate-200 rounded-xl focus:outline-hidden focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 text-slate-800 text-sm shadow-xs transition placeholder:text-slate-400 disabled:opacity-50"
                                :class="{ 'border-red-500 focus:ring-red-500/10': validationErrors?.nombre_proceso }" />
                            <p v-if="validationErrors?.nombre_proceso" class="text-xs text-red-500 mt-1 block font-semibold">
                                {{ validationErrors.nombre_proceso[0] }}
                            </p>
                        </div>

                        <!-- Dropdown de Laboratorio -->
                        <div class="space-y-1.5">
                            <label class="text-slate-655 text-xs font-bold uppercase tracking-wider">Ámbito de Aplicación</label>
                            <select v-model="formData.laboratorio_id" :disabled="loadingAction"
                                class="w-full px-3.5 py-2.5 bg-slate-55 border border-slate-200 rounded-xl focus:outline-hidden focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 text-slate-800 text-sm shadow-xs transition cursor-pointer disabled:opacity-50">
                                <option :value="null">Global (Todos los Laboratorios)</option>
                                <option v-for="lab in laboratorios" :key="lab.id" :value="lab.id">
                                    {{ lab.nombre }}
                                </option>
                            </select>
                            <p v-if="validationErrors?.laboratorio_id" class="text-xs text-red-500 mt-1 block font-semibold">
                                {{ validationErrors.laboratorio_id[0] }}
                            </p>
                        </div>

                        <!-- Tipo Restricción y Estado -->
                        <div class="grid grid-cols-2 gap-4">
                            <div class="space-y-1.5">
                                <label class="text-slate-655 text-xs font-bold uppercase tracking-wider">Tipo Bloqueo</label>
                                <select v-model="formData.tipo_restriccion" :disabled="loadingAction"
                                    class="w-full px-3.5 py-2.5 bg-slate-55 border border-slate-200 rounded-xl focus:outline-hidden focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 text-slate-800 text-sm shadow-xs transition cursor-pointer disabled:opacity-50">
                                    <option value="bloqueo_total">Bloqueo Total</option>
                                    <option value="monitoreo_alerta">Solo Monitorear</option>
                                </select>
                            </div>

                            <div class="space-y-1.5">
                                <label class="text-slate-655 text-xs font-bold uppercase tracking-wider">Estado Inicial</label>
                                <select v-model="formData.activo" :disabled="loadingAction"
                                    class="w-full px-3.5 py-2.5 bg-slate-55 border border-slate-200 rounded-xl focus:outline-hidden focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 text-slate-800 text-sm shadow-xs transition cursor-pointer disabled:opacity-50">
                                    <option :value="true">Activo / Bloquear</option>
                                    <option :value="false">Inactivo / Pausado</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Footer Modal -->
                    <div class="px-6 py-4 bg-slate-50 border-t border-slate-150 flex justify-end gap-2.5">
                        <button @click="showModal = false" :disabled="loadingAction"
                            class="px-4 py-2 bg-white border border-slate-200 text-slate-700 hover:bg-slate-50 hover:border-slate-300 rounded-xl shadow-xs transition-all text-sm font-bold cursor-pointer disabled:opacity-50">
                            Cancelar
                        </button>
                        <button @click="saveRestriccion"
                            :disabled="!formData.nombre_aplicacion || !formData.nombre_proceso || loadingAction"
                            class="px-4 py-2 bg-blue-600 hover:bg-blue-700 active:bg-blue-800 disabled:opacity-40 disabled:cursor-not-allowed text-white rounded-xl shadow-xs transition-all text-sm font-bold flex items-center gap-2 cursor-pointer">
                            <Loader2 v-if="loadingAction" class="w-4 h-4 animate-spin" />
                            {{ selectedRestriccion ? 'Guardar Cambios' : 'Crear Restricción' }}
                        </button>
                    </div>
                </div>
            </div>
        </Transition>

        <!-- MODAL CONFIRMACIÓN ELIMINACIÓN (ESTILO CLARO) -->
        <Transition enter-active-class="transition-all duration-300 ease-out"
            enter-from-class="opacity-0 backdrop-blur-none" enter-to-class="opacity-100 backdrop-blur-md"
            leave-active-class="transition-all duration-200 ease-in" leave-from-class="opacity-100 backdrop-blur-md"
            leave-to-class="opacity-0 backdrop-blur-none">
            <div v-if="showDeleteModal"
                class="fixed inset-0 bg-slate-900/50 backdrop-blur-xs z-50 flex items-center justify-center p-4">
                <div class="bg-white rounded-2xl border border-slate-150 max-w-sm w-full shadow-2xl p-6 flex flex-col animate-in fade-in zoom-in-95 duration-150 text-slate-800">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="bg-red-50 text-red-650 border border-red-100 p-2.5 rounded-xl shrink-0">
                            <AlertTriangle class="w-5 h-5 text-red-500" />
                        </div>
                        <div>
                            <h3 class="text-md font-black text-slate-900">¿Eliminar Restricción?</h3>
                            <p class="text-xs text-slate-500 mt-0.5">Esta acción no se puede deshacer.</p>
                        </div>
                    </div>
                    
                    <p class="text-sm text-slate-600 mb-6 leading-relaxed">
                        ¿Estás seguro que deseas eliminar la política de bloqueo para <span class="font-extrabold text-red-600">"{{ selectedRestriccion?.nombre_aplicacion }}"</span>? Los equipos dejarán de aplicar esta restricción.
                    </p>

                    <div class="flex justify-end gap-2.5">
                        <button @click="showDeleteModal = false" :disabled="loadingAction"
                            class="px-4 py-2 bg-white border border-slate-200 text-slate-700 hover:bg-slate-50 hover:border-slate-300 rounded-xl shadow-xs transition-all text-sm font-bold cursor-pointer disabled:opacity-50">
                            Cancelar
                        </button>
                        <button @click="deleteRestriccion" :disabled="loadingAction"
                            class="px-4 py-2 bg-red-600 hover:bg-red-700 active:bg-red-800 disabled:opacity-40 text-white rounded-xl shadow-xs transition-all text-sm font-bold flex items-center gap-2 cursor-pointer">
                            <Loader2 v-if="loadingAction" class="w-4 h-4 animate-spin" />
                            Eliminar
                        </button>
                    </div>
                </div>
            </div>
        </Transition>

        <!-- FLOATING TOAST NOTIFICATION PREMIUM (CLARO) -->
        <Transition enter-active-class="transition-all duration-300 ease-out"
            enter-from-class="translate-y-12 opacity-0" enter-to-class="translate-y-0 opacity-100"
            leave-active-class="transition-all duration-200 ease-in" leave-from-class="translate-y-0 opacity-100"
            leave-to-class="translate-y-12 opacity-0">
            <div v-if="showToast"
                class="fixed bottom-6 right-6 z-50 flex items-center gap-3 px-4 py-3.5 rounded-xl border bg-white shadow-2xl max-w-sm animate-bounce-subtle"
                :class="toastType === 'success' ? 'border-l-4 border-l-emerald-500 border-slate-200 text-emerald-600 shadow-emerald-500/5' : 'border-l-4 border-l-red-500 border-slate-200 text-red-600 shadow-red-500/5'">
                <div class="p-1 rounded-md bg-slate-50 border border-slate-200 shrink-0">
                    <Check v-if="toastType === 'success'" class="w-4.5 h-4.5 text-emerald-600" />
                    <X v-else class="w-4.5 h-4.5 text-red-600" />
                </div>
                <div class="flex-1">
                    <p class="text-3xs text-slate-400 font-bold uppercase tracking-wider">Notificación del Sistema</p>
                    <p class="text-sm font-bold text-slate-800 mt-0.5">{{ toastMessage }}</p>
                </div>
            </div>
        </Transition>

    </div>
</template>

<style scoped>
/* Animaciones de micro-interacciones */
@keyframes bounce-subtle {
    0%, 100% {
        transform: translateY(0);
    }
    50% {
        transform: translateY(-4px);
    }
}

.animate-bounce-subtle {
    animation: bounce-subtle 4s ease-in-out infinite;
}
</style>
