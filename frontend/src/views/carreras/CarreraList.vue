<script setup lang="ts">
import { ref, watch, onMounted } from 'vue';
import { Search, Plus, Edit, Trash2, GraduationCap, Loader2 } from '@lucide/vue';
import { carreraService } from '@/services/carreraService';
import type { Carrera, CarreraFormData } from '@/types/carrera';
import { useAuthStore } from '@/stores/auth';

// Componentes del Sistema
import BasePagination from '@/components/BasePagination.vue';
import CarreraModal from './CarreraModal.vue';
import CarreraDeleteModal from './CarreraDeleteModal.vue';
import { getLaravelValidationErrors } from '@/utils/errorHandler';
import { useToast } from '@/composables/useToast';

const toast = useToast();
const authStore = useAuthStore();

// Estados Reactivos de Datos
const carreras = ref<Carrera[]>([]);
const searchTerm = ref('');
const isLoading = ref(false);
const isSaving = ref(false);
const validationErrors = ref<Record<string, string[]> | undefined>(undefined);

// Estados de Paginación
const currentPage = ref(1);
const lastPage = ref(1);
const totalCarreras = ref(0);

// Estados de Control de Modales
const isFormModalOpen = ref(false);
const isDeleteModalOpen = ref(false);
const selectedCarrera = ref<Carrera | null>(null);

// Carga asíncrona desde el Servidor Laravel
const fetchCarreras = async () => {
    isLoading.value = true;
    try {
        const res = await carreraService.getCarreras({
            search: searchTerm.value,
            page: currentPage.value.toString()
        });
        carreras.value = res.data;
        lastPage.value = res.last_page;
        totalCarreras.value = res.total;
    } catch (error) {
        console.error('Error al consultar el catálogo de carreras:', error);
    } finally {
        isLoading.value = false;
    }
};

// Antirrebote (Debounce) nativo para el buscador de texto
let debounceTimeout: ReturnType<typeof setTimeout>;
watch(searchTerm, () => {
    clearTimeout(debounceTimeout);
    debounceTimeout = setTimeout(() => {
        currentPage.value = 1; // Resetea la sección al escribir
        fetchCarreras();
    }, 400);
});

// Watch directo para el cambio de páginas en el paginador
watch(currentPage, () => {
    fetchCarreras();
});

// Control de flujos de apertura de modales
const openCreateModal = () => {
    selectedCarrera.value = null;
    validationErrors.value = undefined;
    isFormModalOpen.value = true;
};

const openEditModal = (carrera: Carrera) => {
    selectedCarrera.value = carrera;
    validationErrors.value = undefined;
    isFormModalOpen.value = true;
};

const openDeleteModal = (carrera: Carrera) => {
    selectedCarrera.value = carrera;
    isDeleteModalOpen.value = true;
};

// Controladores de eventos del CRUD (Resolución asíncrona segura)
const handleSaveCarrera = async (formData: CarreraFormData) => {
    isSaving.value = true;
    validationErrors.value = undefined;
    try {
        if (selectedCarrera.value) {
            await carreraService.actualizarCarrera(selectedCarrera.value.id, formData);
            toast.success('Carrera actualizada', 'Los datos de la carrera se han actualizado correctamente.');
        } else {
            await carreraService.crearCarrera(formData);
            toast.success('Carrera creada', 'La carrera se ha registrado correctamente.');
        }
        isFormModalOpen.value = false; // Solo cierra si finaliza con éxito en la BD
        fetchCarreras();
    } catch (error: any) {
        console.error('Error al guardar la carrera:', error);
        if (error.response && error.response.status === 422) {
            validationErrors.value = getLaravelValidationErrors(error);
            toast.warning('Errores de validación', 'Por favor revisa los campos del formulario.');
        } else {
            toast.error('Error', 'No se pudo guardar la información de la carrera.');
        }
    } finally {
        isSaving.value = false;
    }
};

const handleConfirmDelete = async () => {
    if (!selectedCarrera.value) return;
    isSaving.value = true;
    try {
        await carreraService.eliminarCarrera(selectedCarrera.value.id);
        isDeleteModalOpen.value = false;
        toast.success('Carrera eliminada', 'La carrera ha sido eliminada correctamente del sistema.');

        // Regresar de página automáticamente si borramos el único ítem de una sección remota
        if (carreras.value.length === 1 && currentPage.value > 1) {
            currentPage.value--;
        } else {
            fetchCarreras();
        }
    } catch (error) {
        console.error('Error al intentar eliminar el recurso:', error);
        toast.error('Error', 'Hubo un problema al intentar eliminar la carrera.');
    } finally {
        isSaving.value = false;
    }
};

onMounted(() => {
    fetchCarreras();
});
</script>

<template>
    <div class="p-4 md:p-6 space-y-4 md:space-y-6 relative">
        <div v-if="isLoading"
            class="absolute inset-0 bg-white/40 backdrop-blur-xs z-50 flex items-center justify-center min-h-100 rounded-xl transition-all">
            <div class="bg-white p-4 rounded-xl shadow-lg border border-gray-100 flex items-center gap-3">
                <Loader2 class="w-5 h-5 text-blue-600 animate-spin" />
                <span class="text-sm font-medium text-gray-700">Cargando carreras...</span>
            </div>
        </div>

        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 flex items-center gap-2">
                    <GraduationCap class="w-7 h-7 text-blue-600" />
                    Gestión de Carreras
                </h1>
                <p class="text-sm text-gray-500 mt-1">
                    {{ totalCarreras }} carreras configuradas en el sistema universitario
                </p>
            </div>
            <button v-if="authStore.can('carreras.crear')" @click="openCreateModal"
                class="inline-flex items-center justify-center bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-lg shadow-sm gap-2 transition-colors text-sm w-full sm:w-auto">
                <Plus class="w-4 h-4" />
                Nueva Carrera
            </button>
        </div>

        <div class="bg-white p-4 rounded-xl border border-gray-200 shadow-sm">
            <div class="relative max-w-md">
                <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" />
                <input v-model="searchTerm" type="text" placeholder="Buscar por nombre o código de carrera..."
                    class="w-full pl-10 pr-4 py-2 bg-white border border-gray-200 text-gray-900 placeholder:text-gray-400 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm transition-all" />
            </div>
        </div>

        <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr
                            class="bg-gray-50 border-b border-gray-200 text-xs font-semibold text-gray-500 uppercase tracking-wider">
                            <th class="px-6 py-4">Código</th>
                            <th class="px-6 py-4">Nombre de la Carrera</th>
                            <th class="px-6 py-4 text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 text-sm text-gray-700">
                        <tr v-if="carreras.length === 0 && !isLoading">
                            <td colspan="3" class="px-6 py-10 text-center text-gray-400">
                                No se encontraron carreras que coincidan con la búsqueda.
                            </td>
                        </tr>
                        <tr v-for="carrera in carreras" :key="carrera.id" class="hover:bg-gray-50/70 transition-colors">
                            <td class="px-6 py-4 font-mono font-bold text-gray-600">
                                <span class="bg-gray-100 px-2 py-1 rounded text-xs border border-gray-200">
                                    {{ carrera.codigo }}
                                </span>
                            </td>
                            <td class="px-6 py-4 font-medium text-gray-900">
                                {{ carrera.nombre }}
                            </td>
                            <td class="px-6 py-4 text-right space-x-1">
                                <button v-if="authStore.can('carreras.editar')" @click="openEditModal(carrera)"
                                    class="inline-flex items-center justify-center p-2 text-blue-600 hover:text-blue-700 hover:bg-blue-50 rounded-lg transition-colors"
                                    title="Editar Carrera">
                                    <Edit class="w-4 h-4" />
                                </button>
                                <button v-if="authStore.can('carreras.eliminar')" @click="openDeleteModal(carrera)"
                                    class="inline-flex items-center justify-center p-2 text-red-600 hover:text-red-700 hover:bg-red-50 rounded-lg transition-colors"
                                    title="Eliminar Carrera">
                                    <Trash2 class="w-4 h-4" />
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="px-6 py-4 border-t border-gray-200 bg-gray-50/50">
                <BasePagination v-model="currentPage" :last-page="lastPage" :total="totalCarreras" />
            </div>
        </div>

        <CarreraModal v-model="isFormModalOpen" :carrera="selectedCarrera" :is-saving="isSaving"
            :validation-errors="validationErrors" @save="handleSaveCarrera" />

        <CarreraDeleteModal v-model="isDeleteModalOpen" :carrera="selectedCarrera" :is-saving="isSaving"
            @confirm="handleConfirmDelete" />
    </div>
</template>
