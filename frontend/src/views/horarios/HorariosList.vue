<script setup lang="ts">
import { ref, watch, onMounted, computed } from 'vue';
import { Calendar, Plus, Edit, Trash2, Loader2, Filter, RefreshCw, List, BookOpen, User, Clock, Building } from '@lucide/vue';
import { horarioService } from '@/services/horarioService';
import type { Horario, HorarioFormData } from '@/types/horario';
import { useAuthStore } from '@/stores/auth';

// Componentes del Sistema
import BasePagination from '@/components/BasePagination.vue';
import HorarioModal from './HorarioModal.vue';
import HorarioDeleteModal from './HorarioDeleteModal.vue';
import HorarioCalendarView from './HorarioCalendarView.vue';

// Auth Store para verificar permisos del usuario
const authStore = useAuthStore();
// isDocente se mantiene para compatibilidad con filtros del backend (query param)
const isDocente = computed(() => authStore.user?.role?.toLowerCase() === 'docente');

// Catálogos cargados de form-data
const laboratorios = ref<{ id: number; nombre: string; }[]>([]);
const docentes = ref<{ id: number; nombre: string; }[]>([]);
const grupos = ref<{ id: number; nombre: string; }[]>([]);

// Estados Reactivos de Filtros
const selectedLabFilter = ref('all');
const selectedDocenteFilter = ref('all');
const selectedGrupoFilter = ref('all');
const filterFechaInicio = ref('');
const filterFechaFin = ref('');

// Estados de Datos
const horarios = ref<Horario[]>([]);
const isLoading = ref(true);
const isSaving = ref(false);

// Errores de Validación del Backend (para mostrar directamente en el Modal)
const validationErrors = ref<Record<string, string[]> | undefined>(undefined);

// Estados de Paginación
const currentPage = ref(1);
const lastPage = ref(1);
const totalHorarios = ref(0);

// Estados de Control de Modales
const isFormModalOpen = ref(false);
const isDeleteModalOpen = ref(false);
const selectedHorario = ref<Horario | null>(null);

// Control de Vista Activa (Datatable / Calendario)
const currentView = ref<'table' | 'calendar'>('table');



// Mapeador de días
const diasSemanasMap: Record<number, string> = {
    1: 'Lunes',
    2: 'Martes',
    3: 'Miércoles',
    4: 'Jueves',
    5: 'Viernes',
    6: 'Sábado',
    7: 'Domingo'
};

const getDiaLabel = (diaNum: number): string => {
    return diasSemanasMap[diaNum] || `Día ${diaNum}`;
};

const formatTime = (timeStr?: string): string => {
    if (!timeStr) return '';
    return timeStr.length > 5 ? timeStr.slice(0, 5) : timeStr;
};

// Carga los catálogos necesarios para los filtros y el formulario
const loadFormCatalogs = async () => {
    try {
        const res = await horarioService.getFormData();
        laboratorios.value = res.laboratorios;
        docentes.value = res.docentes;
        grupos.value = res.grupos;
    } catch (error) {
        console.error('Error al cargar catálogos de horarios:', error);
    }
};

// Carga asíncrona de Horarios
const fetchHorarios = async () => {
    isLoading.value = true;
    await fetchHorariosLogicaInterna();
    isLoading.value = false;
};

const fetchHorariosLogicaInterna = async () => {
    try {
        const params: any = {
            laboratorio_id: selectedLabFilter.value,
            grupo_id: selectedGrupoFilter.value,
            page: currentPage.value.toString()
        };

        // Si es administrador o supervisor, permitimos el filtro de docente.
        // Si es docente, el backend se encarga de inyectar su id de forma forzada.
        if (!isDocente.value) {
            params.docente_id = selectedDocenteFilter.value;
        }

        // Filtro de rango de fechas
        if (filterFechaInicio.value && filterFechaFin.value) {
            params.fecha_inicio = filterFechaInicio.value;
            params.fecha_fin = filterFechaFin.value;
        }

        const res = await horarioService.getHorarios(params);
        horarios.value = res.data;
        lastPage.value = res.last_page;
        totalHorarios.value = res.total;
    } catch (error) {
        console.error('Error al consultar la lista de horarios:', error);
    }
};

// Limpia todos los filtros activos
const resetFilters = () => {
    selectedLabFilter.value = 'all';
    selectedDocenteFilter.value = 'all';
    selectedGrupoFilter.value = 'all';
    filterFechaInicio.value = '';
    filterFechaFin.value = '';
    currentPage.value = 1;
    fetchHorarios();
};

// Watchers de filtros para recarga automática
watch([selectedLabFilter, selectedDocenteFilter, selectedGrupoFilter], () => {
    currentPage.value = 1;
    fetchHorarios();
});

// Watchers de fechas con validación conjunta
watch([filterFechaInicio, filterFechaFin], () => {
    if ((filterFechaInicio.value && filterFechaFin.value) || (!filterFechaInicio.value && !filterFechaFin.value)) {
        currentPage.value = 1;
        fetchHorarios();
    }
});

// Watch directo para el cambio de páginas
watch(currentPage, () => {
    fetchHorarios();
});

// Control de Modales
const openCreateModal = () => {
    selectedHorario.value = null;
    validationErrors.value = undefined;
    isFormModalOpen.value = true;
};

const openEditModal = (horario: Horario) => {
    selectedHorario.value = horario;
    validationErrors.value = undefined;
    isFormModalOpen.value = true;
};

const openDeleteModal = (horario: Horario) => {
    selectedHorario.value = horario;
    isDeleteModalOpen.value = true;
};

// CRUD Event Handlers
const handleSaveHorario = async (formData: HorarioFormData) => {
    isSaving.value = true;
    validationErrors.value = undefined;
    try {
        if (selectedHorario.value) {
            await horarioService.actualizarHorario(selectedHorario.value.id, formData);
        } else {
            await horarioService.crearHorario(formData);
        }
        isFormModalOpen.value = false;
        fetchHorarios();
    } catch (error: any) {
        console.error('Error al guardar el horario:', error);
        // Si hay errores de validación de Laravel (422), los inyectamos en el modal
        if (error.response && error.response.status === 422 && error.response.data.errors) {
            validationErrors.value = error.response.data.errors;
        }
    } finally {
        isSaving.value = false;
    }
};

const handleConfirmDelete = async () => {
    if (!selectedHorario.value) return;
    isSaving.value = true;
    try {
        await horarioService.eliminarHorario(selectedHorario.value.id);
        isDeleteModalOpen.value = false;

        // Regresar de página si borramos el único de una sección
        if (horarios.value.length === 1 && currentPage.value > 1) {
            currentPage.value--;
        } else {
            fetchHorarios();
        }
    } catch (error) {
        console.error('Error al eliminar horario:', error);
    } finally {
        isSaving.value = false;
    }
};

onMounted(async () => {
    isLoading.value = true;
    try {
        await Promise.all([
            loadFormCatalogs(),
            fetchHorariosLogicaInterna()
        ]);
    } catch (error) {
        console.error('Error inicializando el módulo de horarios:', error);
    } finally {
        isLoading.value = false;
    }
});
</script>

<template>
    <div class="p-6 space-y-6 relative">
        <div v-if="isLoading"
            class="absolute inset-0 bg-white/40 backdrop-blur-xs z-50 flex items-center justify-center min-h-100 rounded-xl transition-all">
            <div class="bg-white p-4 rounded-xl shadow-lg border border-gray-100 flex items-center gap-3">
                <Loader2 class="w-5 h-5 text-blue-600 animate-spin" />
                <span class="text-sm font-medium text-gray-700">Cargando planificación de horarios...</span>
            </div>
        </div>

        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 flex items-center gap-2">
                    <Calendar class="w-7 h-7 text-blue-600" />
                    Planificación de Horarios
                </h1>
                <p class="text-sm text-gray-500 mt-1">
                    {{ totalHorarios }} franjas de horarios registradas en laboratorios de cómputo
                </p>
            </div>
            
            <div class="flex items-center gap-3">
                <!-- Selector de Vista (Tabla / Calendario) -->
                <div class="flex bg-gray-100 p-1 rounded-xl border border-gray-200">
                    <button @click="currentView = 'table'" :class="[
                        'px-3 py-1.5 rounded-lg text-xs font-bold flex items-center gap-1.5 transition-all duration-200 cursor-pointer',
                        currentView === 'table' ? 'bg-white text-blue-600 shadow-xs border border-gray-100' : 'text-gray-500 hover:text-gray-700'
                    ]">
                        <List class="w-3.5 h-3.5" />
                        Lista
                    </button>
                    <button @click="currentView = 'calendar'" :class="[
                        'px-3 py-1.5 rounded-lg text-xs font-bold flex items-center gap-1.5 transition-all duration-200 cursor-pointer',
                        currentView === 'calendar' ? 'bg-white text-blue-600 shadow-xs border border-gray-100' : 'text-gray-500 hover:text-gray-700'
                    ]">
                        <Calendar class="w-3.5 h-3.5" />
                        Calendario
                    </button>
                </div>

                <!-- Solo con permiso para crear horarios -->
                <button v-if="authStore.can('horarios.crear')" @click="openCreateModal"
                    class="inline-flex items-center justify-center bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-lg shadow-sm gap-2 transition-colors text-sm cursor-pointer">
                    <Plus class="w-4 h-4" />
                    Nuevo Horario
                </button>
            </div>
        </div>

        <!-- Panel de Filtros Multicriterio -->
        <div class="bg-white p-5 rounded-xl border border-gray-200 shadow-sm space-y-4">
            <div class="flex items-center justify-between border-b border-gray-100 pb-3">
                <div class="flex items-center gap-2 text-sm font-bold text-gray-900">
                    <Filter class="w-4.5 h-4.5 text-blue-600" />
                    <span>Filtros de Búsqueda Avanzada</span>
                </div>
                <button @click="resetFilters" 
                    class="inline-flex items-center gap-1.5 text-xs text-blue-600 hover:text-blue-700 font-semibold transition-colors">
                    <RefreshCw class="w-3.5 h-3.5" />
                    Limpiar Filtros
                </button>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-4">
                <!-- Filtro Laboratorios -->
                <div class="space-y-1">
                    <label class="text-[10px] font-bold uppercase text-gray-400 tracking-wider">Laboratorio</label>
                    <select v-model="selectedLabFilter"
                        class="w-full px-3 py-2 bg-white border border-gray-200 text-gray-900 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 text-xs transition-all">
                        <option value="all">Todos los Laboratorios</option>
                        <option v-for="lab in laboratorios" :key="lab.id" :value="lab.id">
                            {{ lab.nombre }}
                        </option>
                    </select>
                </div>

                <!-- Filtro Docentes (Solo administradores) -->
                <div v-if="!isDocente" class="space-y-1">
                    <label class="text-[10px] font-bold uppercase text-gray-400 tracking-wider">Docente</label>
                    <select v-model="selectedDocenteFilter"
                        class="w-full px-3 py-2 bg-white border border-gray-200 text-gray-900 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 text-xs transition-all">
                        <option value="all">Todos los Docentes</option>
                        <option v-for="doc in docentes" :key="doc.id" :value="doc.id">
                            {{ doc.nombre }}
                        </option>
                    </select>
                </div>

                <!-- Filtro Grupos -->
                <div class="space-y-1">
                    <label class="text-[10px] font-bold uppercase text-gray-400 tracking-wider">Grupo Académico</label>
                    <select v-model="selectedGrupoFilter"
                        class="w-full px-3 py-2 bg-white border border-gray-200 text-gray-900 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 text-xs transition-all">
                        <option value="all">Todos los Grupos</option>
                        <option v-for="grupo in grupos" :key="grupo.id" :value="grupo.id">
                            {{ grupo.nombre }}
                        </option>
                    </select>
                </div>

                <!-- Filtro Fecha Inicio -->
                <div class="space-y-1">
                    <label class="text-[10px] font-bold uppercase text-gray-400 tracking-wider">Desde la Fecha</label>
                    <input v-model="filterFechaInicio" type="date"
                        class="w-full px-3 py-1.5 bg-white border border-gray-200 text-gray-900 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 text-xs transition-all" />
                </div>

                <!-- Filtro Fecha Fin -->
                <div class="space-y-1">
                    <label class="text-[10px] font-bold uppercase text-gray-400 tracking-wider">Hasta la Fecha</label>
                    <input v-model="filterFechaFin" type="date"
                        class="w-full px-3 py-1.5 bg-white border border-gray-200 text-gray-900 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 text-xs transition-all" />
                </div>
            </div>
        </div>

        <!-- Tabla de Datos -->
        <div v-if="currentView === 'table'" class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden animate-fadeIn">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr
                            class="bg-gray-50 border-b border-gray-200 text-xs font-semibold text-gray-500 uppercase tracking-wider">
                            <th class="px-6 py-4">Laboratorio</th>
                            <th class="px-6 py-4">Docente</th>
                            <th class="px-6 py-4">Día de la Semana</th>
                            <th class="px-6 py-4">Horas Reservadas</th>
                            <th class="px-6 py-4">Vigencia del Período</th>
                            <th v-if="authStore.can('horarios.editar') || authStore.can('horarios.eliminar')" class="px-6 py-4 text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 text-sm text-gray-700">
                        <tr v-if="horarios.length === 0 && !isLoading">
                            <td colspan="6" class="px-6 py-10 text-center text-gray-400">
                                No se encontraron registros de planificación de horarios que coincidan.
                            </td>
                        </tr>
                        <tr v-for="horario in horarios" :key="horario.id" class="hover:bg-gray-50/70 transition-colors">
                            <td class="px-6 py-4">
                                <div class="font-semibold text-gray-900">
                                    {{ horario.nombre_laboratorio }}
                                </div>
                            </td>
                            <td class="px-6 py-4 text-gray-600 font-medium">
                                {{ horario.nombre_docente }}
                            </td>
                            <td class="px-6 py-4 text-gray-600">
                                <span class="bg-blue-50 text-blue-700 border border-blue-150 px-2.5 py-1 rounded-lg text-xs font-bold">
                                    {{ getDiaLabel(horario.dia_semana || horario.dia_sema) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 font-mono font-bold text-gray-700 text-xs">
                                {{ formatTime(horario.hora_inicio) }} - {{ formatTime(horario.hora_fin) }}
                            </td>
                            <td class="px-6 py-4 text-gray-500 font-medium text-xs">
                                {{ horario.fecha_inicio }} al {{ horario.fecha_fin }}
                            </td>
                            <td v-if="authStore.can('horarios.editar') || authStore.can('horarios.eliminar')" class="px-6 py-4 text-right space-x-1">
                                <button v-if="authStore.can('horarios.editar')" @click="openEditModal(horario)"
                                    class="inline-flex items-center justify-center p-2 text-blue-600 hover:text-blue-700 hover:bg-blue-50 rounded-lg transition-colors cursor-pointer"
                                    title="Editar Horario">
                                    <Edit class="w-4 h-4" />
                                </button>
                                <button v-if="authStore.can('horarios.eliminar')" @click="openDeleteModal(horario)"
                                    class="inline-flex items-center justify-center p-2 text-red-600 hover:text-red-700 hover:bg-red-50 rounded-lg transition-colors cursor-pointer"
                                    title="Eliminar Horario">
                                    <Trash2 class="w-4 h-4" />
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="px-6 py-4 border-t border-gray-200 bg-gray-50/50">
                <BasePagination v-model="currentPage" :last-page="lastPage" :total="totalHorarios" />
            </div>
        </div>

        <!-- Vista de Calendario Semanal (Sólo Lectura) -->
        <div v-else class="space-y-6 animate-fadeIn">
            <HorarioCalendarView 
                :horarios="horarios" 
                :is-docente="isDocente" 
                @edit="openEditModal" 
                @delete="openDeleteModal" 
            />
            
            <div class="flex justify-center bg-white py-3 rounded-xl border border-gray-200 shadow-sm">
                <BasePagination v-model="currentPage" :last-page="lastPage" :total="totalHorarios" />
            </div>
        </div>

        <HorarioModal v-model="isFormModalOpen" :horario="selectedHorario" :is-saving="isSaving"
            :laboratorios="laboratorios" :docentes="docentes" :grupos="grupos" 
            :validation-errors="validationErrors" @save="handleSaveHorario" />

        <HorarioDeleteModal v-model="isDeleteModalOpen" :horario="selectedHorario" :is-saving="isSaving"
            @confirm="handleConfirmDelete" />
    </div>
</template>

<style scoped>
.backdrop-blur-xs {
    backdrop-filter: blur(2px);
}
</style>
