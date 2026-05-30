<script setup lang="ts">
import { ref, onMounted, watch, computed } from 'vue';
import {
    Search, Plus, Edit, Trash2, Building2,
    CheckCircle2, XCircle, Loader2, Network
} from '@lucide/vue'; // Usando tu librería oficial de iconos
import type { Laboratorio, LaboratorioFormData } from '@/types/laboratorio';
import { laboratorioService } from '@/services/laboratorioService';
import { useAuthStore } from '@/stores/auth';

// Componentes modulares
import LaboratorioModal from './LaboratorioModal.vue';
import LaboratorioDeleteModal from './LaboratorioDeleteModal.vue';
import LaboratorioVincularModal from './LaboratorioVincularModal.vue';
import BasePagination from '@/components/BasePagination.vue';
import { getLaravelValidationErrors } from '@/utils/errorHandler';

const authStore = useAuthStore();

// --- ESTADO REACTIVO DE LA API ---
const laboratorios = ref<Laboratorio[]>([]);
const isLoading = ref(true);
const isSaving = ref(false);
const isInitializing = ref(true);
const validationErrors = ref<Record<string, string[]> | undefined>(undefined);

// Filtros (Unificado con tu backend de Laravel)
const searchTerm = ref('');
const filterStatus = ref<'all' | 'true' | 'false'>('all');

// Estado de la Paginación (Variables planas idénticas a Usuarios)
const currentPage = ref(1);
const lastPage = ref(1);
const totalLaboratorios = ref(0);
const perPage = ref(10);

// Control de Modales
const isFormDialogOpen = ref(false);
const isDeleteDialogOpen = ref(false);
const isVincularDialogOpen = ref(false);
const selectedLab = ref<Laboratorio | null>(null);

// --- FUNCIÓN CENTRAL: PETICIÓN AL BACKEND ---
const fetchLaboratorios = async () => {
    isLoading.value = true;
    try {
        const response = await laboratorioService.getLaboratorios({
            page: currentPage.value,
            search: searchTerm.value || undefined,
            // Enviamos el estado tal como lo espera tu condicional de Laravel
            activo: filterStatus.value === 'all' ? undefined : filterStatus.value
        });

        // Seteamos las variables planas desglosando el PaginatedResponse de Laravel
        laboratorios.value = response.data;
        currentPage.value = response.current_page;
        lastPage.value = response.last_page;
        totalLaboratorios.value = response.total;
        perPage.value = response.per_page;
    } catch (error) {
        console.error('Error al cargar los laboratorios desde la API:', error);
    } finally {
        isLoading.value = false;
    }
};

// --- OBSERVADORES (WATCHERS) ---
// Si el usuario escribe o cambia el filtro de estado, reiniciamos siempre a la página 1

let searchTimeout: ReturnType<typeof setTimeout>;

    watch(searchTerm, () => {
    currentPage.value = 1;
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        fetchLaboratorios();
    }, 400);
});

watch(filterStatus, () => {
    currentPage.value = 1; // Reseteamos a la página 1
    fetchLaboratorios();   // Ejecuta al instante sin esperas
});

// Si cambia de página mediante el componente BasePagination, ejecutamos la consulta
watch(currentPage, () => {
    fetchLaboratorios();
});

// Carga inicial optimizada
onMounted(async () => {
    isLoading.value = true;
    isInitializing.value = true;
    try {
        await fetchLaboratorios();
    } finally {
        isLoading.value = false;
        isInitializing.value = false;
    }
});

// --- ACCIONES Y ORQUESTACIÓN ---
const openCreateDialog = () => {
    selectedLab.value = null;
    validationErrors.value = undefined;
    isFormDialogOpen.value = true;
};

const openEditDialog = (lab: Laboratorio) => {
    selectedLab.value = lab;
    validationErrors.value = undefined;
    isFormDialogOpen.value = true;
};

const openDeleteDialog = (lab: Laboratorio) => {
    selectedLab.value = lab;
    isDeleteDialogOpen.value = true;
};

const openVincularDialog = (lab: Laboratorio) => {
    selectedLab.value = lab;
    isVincularDialogOpen.value = true;
};

const handleSaveLaboratorio = async (formData: LaboratorioFormData) => {
    isSaving.value = true;
    validationErrors.value = undefined;
    try {
        if (selectedLab.value?.id) {
            await laboratorioService.actualizarLaboratorio(selectedLab.value.id, formData);
        } else {
            await laboratorioService.crearLaboratorio(formData);
        }
        isFormDialogOpen.value = false;
        selectedLab.value = null;
        await fetchLaboratorios();
    } catch (error: any) {
        console.error('Error al guardar el laboratorio:', error);
        if (error.response && error.response.status === 422) {
            validationErrors.value = getLaravelValidationErrors(error);
        }
    } finally {
        isSaving.value = false;
    }
};

const handleDeleteLaboratorio = async () => {
    if (!selectedLab.value?.id) return;
    try {
        await laboratorioService.eliminarLaboratorio(selectedLab.value.id);
        isDeleteDialogOpen.value = false;
        selectedLab.value = null;

        // Control automático de desborde de páginas tras eliminar un registro
        if (laboratorios.value.length === 1 && currentPage.value > 1) {
            currentPage.value--;
        } else {
            await fetchLaboratorios();
        }
    } catch (error) {
        console.error('Error al eliminar el laboratorio:', error);
    }
};
</script>

<template>
    <div class="p-8 space-y-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-semibold text-gray-900 mb-2">Gestión de Laboratorios</h1>
                <p class="text-gray-600">{{ totalLaboratorios }} laboratorios registrados en el sistema</p>
            </div>

            <div v-if="isInitializing" class="w-32 h-10 bg-gray-200 animate-pulse rounded-md"></div>
            <button v-else-if="authStore.can('laboratorios.crear')" @click="openCreateDialog"
                class="bg-blue-600 hover:bg-blue-700 text-white gap-2 flex items-center px-4 py-2 rounded-md transition-colors text-sm font-medium shadow-sm cursor-pointer">
                <Plus class="w-4 h-4" />
                Nuevo Laboratorio
            </button>
        </div>

        <div class="bg-white border border-gray-200 rounded-lg p-4">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="relative md:col-span-2">
                    <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" />
                    <input v-model="searchTerm" type="text" placeholder="Buscar por nombre, pabellón o piso..."
                        class="pl-10 w-full px-3 py-2 bg-white border border-gray-200 rounded-md text-gray-900 placeholder:text-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm" />
                </div>

                <select v-model="filterStatus"
                    class="bg-white border border-gray-200 text-gray-900 px-3 py-2 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm cursor-pointer">
                    <option value="all">Todos los estados</option>
                    <option value="true">Activos</option>
                    <option value="false">Inactivos</option>
                </select>
            </div>
        </div>

        <div
            class="bg-white border border-gray-200 rounded-lg shadow-sm relative p-6 min-h-75 flex flex-col justify-between">

            <Transition enter-active-class="transition duration-200 ease-out"
                enter-from-class="opacity-0 backdrop-blur-none" enter-to-class="opacity-100 backdrop-blur-xs"
                leave-active-class="transition duration-150 ease-in" leave-from-class="opacity-100 backdrop-blur-xs"
                leave-to-class="opacity-0 backdrop-blur-none">
                <div v-if="isLoading"
                    class="absolute inset-0 bg-white/60 z-10 flex flex-col items-center justify-center rounded-lg">
                    <div class="bg-white p-4 rounded-xl shadow-md border border-gray-100 flex items-center gap-3">
                        <Loader2 class="w-5 h-5 text-blue-600 animate-spin" />
                        <span class="text-sm font-medium text-gray-700">Actualizando información...</span>
                    </div>
                </div>
            </Transition>

            <div v-if="laboratorios.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-6">
                <div v-for="lab in laboratorios" :key="lab.id"
                    class="bg-white border border-gray-200 rounded-lg p-6 hover:border-gray-300 transition-all hover:shadow-md flex flex-col justify-between">
                    <div>
                        <div class="flex items-start justify-between mb-4">
                            <div class="flex items-center gap-3">
                                <div :class="[
                                    'w-12 h-12 rounded-lg flex items-center justify-center border-2 shrink-0',
                                    lab.activo ? 'bg-blue-50 border-blue-500 text-blue-600' : 'bg-gray-50 border-gray-400 text-gray-400'
                                ]">
                                    <Building2 class="w-6 h-6" />
                                </div>
                                <div>
                                    <h3 class="text-lg font-semibold text-gray-900">{{ lab.nombre }}</h3>
                                    <span :class="[
                                        'mt-1 inline-flex items-center gap-1 text-xs font-medium px-2 py-0.5 rounded-full border',
                                        lab.activo ? 'bg-green-50 text-green-700 border-green-200' : 'bg-gray-50 text-gray-700 border-gray-200'
                                    ]">
                                        <CheckCircle2 v-if="lab.activo" class="w-3 h-3" />
                                        <XCircle v-else class="w-3 h-3" />
                                        {{ lab.activo ? 'Activo' : 'Inactivo' }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div class="space-y-2 mb-4 text-sm">
                            <div class="flex items-center justify-between">
                                <span class="text-gray-600">Pabellón:</span>
                                <span class="text-gray-900 font-medium">{{ lab.pabellon }}</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-gray-600">Piso:</span>
                                <span class="text-gray-900 font-medium capitalize">{{ lab.piso }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center gap-2 pt-4 border-t border-gray-100 mt-auto">
                        <button v-if="authStore.can('laboratorios.editar')" @click="openEditDialog(lab)"
                            class="flex-1 inline-flex items-center justify-center px-3 py-1.5 border border-gray-200 text-sm font-medium rounded-md text-blue-600 bg-white hover:bg-blue-50 transition-colors cursor-pointer">
                            <Edit class="w-4 h-4 mr-1" /> Editar
                        </button>
                        <button v-if="authStore.can('laboratorios.editar')" @click="openVincularDialog(lab)"
                            title="Vincular Estaciones"
                            class="inline-flex items-center justify-center p-1.5 border border-gray-200 rounded-md text-emerald-600 bg-white hover:bg-emerald-50 transition-colors cursor-pointer">
                            <Network class="w-4 h-4" />
                        </button>
                        <button v-if="authStore.can('laboratorios.eliminar')" @click="openDeleteDialog(lab)"
                            class="inline-flex items-center justify-center p-1.5 border border-gray-200 rounded-md text-red-600 bg-white hover:bg-red-50 transition-colors cursor-pointer">
                            <Trash2 class="w-4 h-4" />
                        </button>
                    </div>
                </div>
            </div>

            <div v-else-if="!isLoading" class="text-center py-14 text-sm text-gray-400 italic">
                No se encontraron laboratorios que coincidan con los criterios de búsqueda o filtros.
            </div>

            <div class="mt-auto pt-4 border-t border-gray-100">
                <BasePagination v-model="currentPage" :last-page="lastPage" :total="totalLaboratorios" />
            </div>
        </div>

        <LaboratorioModal :show="isFormDialogOpen" :lab="selectedLab" :loading="isSaving"
            :validation-errors="validationErrors" @close="isFormDialogOpen = false" @submit="handleSaveLaboratorio" />

        <LaboratorioDeleteModal :show="isDeleteDialogOpen" :lab="selectedLab" @close="isDeleteDialogOpen = false"
            @confirm="handleDeleteLaboratorio" />

        <LaboratorioVincularModal :show="isVincularDialogOpen" :lab="selectedLab" @close="isVincularDialogOpen = false" />
    </div>
</template>