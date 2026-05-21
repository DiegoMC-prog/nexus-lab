<script setup lang="ts">
import { ref, watch, onMounted } from 'vue';
import { Search, Plus, Edit, Trash2, Calendar, Loader2 } from '@lucide/vue';
import { semestreService } from '@/services/semestreService';
import type { Semestre, SemestreFormData } from '@/types/Semestre';

// Componentes del Sistema
import BasePagination from '@/components/BasePagination.vue';
import SemestreModal from './SemestreModal.vue';
import SemestreDeleteModal from './SemestreDeleteModal.vue';

// Estados Reactivos de Datos
const semestres = ref<Semestre[]>([]);
const searchTerm = ref('');
const isLoading = ref(false);
const isSaving = ref(false);

// Estados de Paginación
const currentPage = ref(1);
const lastPage = ref(1);
const totalSemestres = ref(0);

// Estados de Control de Modales
const isFormModalOpen = ref(false);
const isDeleteModalOpen = ref(false);
const selectedSemestre = ref<Semestre | null>(null);

// Carga asíncrona desde el Servidor Laravel
const fetchSemestres = async () => {
    isLoading.value = true;
    try {
        const res = await semestreService.getSemestres({
            search: searchTerm.value,
            page: currentPage.value.toString()
        });
        semestres.value = res.data;
        lastPage.value = res.last_page;
        totalSemestres.value = res.total;
    } catch (error) {
        console.error('Error al consultar el catálogo de semestres:', error);
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
        fetchSemestres();
    }, 400);
});

// Watch directo para el cambio de páginas en el paginador
watch(currentPage, () => {
    fetchSemestres();
});

// Control de flujos de apertura de modales
const openCreateModal = () => {
    selectedSemestre.value = null;
    isFormModalOpen.value = true;
};

const openEditModal = (semestre: Semestre) => {
    selectedSemestre.value = semestre;
    isFormModalOpen.value = true;
};

const openDeleteModal = (semestre: Semestre) => {
    selectedSemestre.value = semestre;
    isDeleteModalOpen.value = true;
};

// Controladores de eventos del CRUD
const handleSaveSemestre = async (formData: SemestreFormData) => {
    isSaving.value = true;
    try {
        if (selectedSemestre.value) {
            await semestreService.actualizarSemestre(selectedSemestre.value.id, formData);
        } else {
            await semestreService.crearSemestre(formData);
        }
        isFormModalOpen.value = false; // Solo cierra si finaliza con éxito en la BD
        fetchSemestres();
    } catch (error) {
        console.error('Error al guardar el semestre académico:', error);
    } finally {
        isSaving.value = false;
    }
};

const handleConfirmDelete = async () => {
    if (!selectedSemestre.value) return;
    isSaving.value = true;
    try {
        await semestreService.eliminarSemestre(selectedSemestre.value.id);
        isDeleteModalOpen.value = false;

        // Regresar de página automáticamente si borramos el único ítem de una sección remota
        if (semestres.value.length === 1 && currentPage.value > 1) {
            currentPage.value--;
        } else {
            fetchSemestres();
        }
    } catch (error) {
        console.error('Error al intentar eliminar el recurso:', error);
    } finally {
        isSaving.value = false;
    }
};

onMounted(() => {
    fetchSemestres();
});
</script>

<template>
    <div class="p-6 space-y-6 relative">
        <div v-if="isLoading"
            class="absolute inset-0 bg-white/40 backdrop-blur-xs z-50 flex items-center justify-center min-h-100 rounded-xl transition-all">
            <div class="bg-white p-4 rounded-xl shadow-lg border border-gray-100 flex items-center gap-3">
                <Loader2 class="w-5 h-5 text-blue-600 animate-spin" />
                <span class="text-sm font-medium text-gray-700">Cargando semestres...</span>
            </div>
        </div>

        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 flex items-center gap-2">
                    <Calendar class="w-7 h-7 text-blue-600" />
                    Gestión de Semestres Académicos
                </h1>
                <p class="text-sm text-gray-500 mt-1">
                    {{ totalSemestres }} semestres académicos configurados en el sistema
                </p>
            </div>
            <button @click="openCreateModal"
                class="inline-flex items-center justify-center bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-lg shadow-sm gap-2 transition-colors text-sm">
                <Plus class="w-4 h-4" />
                Nuevo Semestre
            </button>
        </div>

        <div class="bg-white p-4 rounded-xl border border-gray-200 shadow-sm">
            <div class="relative max-w-md">
                <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" />
                <input v-model="searchTerm" type="text" placeholder="Buscar por nombre de semestre..."
                    class="w-full pl-10 pr-4 py-2 bg-white border border-gray-200 text-gray-900 placeholder:text-gray-400 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm transition-all" />
            </div>
        </div>

        <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr
                            class="bg-gray-50 border-b border-gray-200 text-xs font-semibold text-gray-500 uppercase tracking-wider">
                            <th class="px-6 py-4">ID</th>
                            <th class="px-6 py-4">Semestre Académico</th>
                            <th class="px-6 py-4 text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 text-sm text-gray-700">
                        <tr v-if="semestres.length === 0 && !isLoading">
                            <td colspan="3" class="px-6 py-10 text-center text-gray-400">
                                No se encontraron semestres académicos que coincidan con la búsqueda.
                            </td>
                        </tr>
                        <tr v-for="semestre in semestres" :key="semestre.id" class="hover:bg-gray-50/70 transition-colors">
                            <td class="px-6 py-4 font-mono text-gray-500">
                                #{{ semestre.id }}
                            </td>
                            <td class="px-6 py-4 font-semibold text-gray-900">
                                {{ semestre.nombre }}
                            </td>
                            <td class="px-6 py-4 text-right space-x-1">
                                <button @click="openEditModal(semestre)"
                                    class="inline-flex items-center justify-center p-2 text-blue-600 hover:text-blue-700 hover:bg-blue-50 rounded-lg transition-colors"
                                    title="Editar Semestre">
                                    <Edit class="w-4 h-4" />
                                </button>
                                <button @click="openDeleteModal(semestre)"
                                    class="inline-flex items-center justify-center p-2 text-red-600 hover:text-red-700 hover:bg-red-50 rounded-lg transition-colors"
                                    title="Eliminar Semestre">
                                    <Trash2 class="w-4 h-4" />
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="px-6 py-4 border-t border-gray-200 bg-gray-50/50">
                <BasePagination v-model="currentPage" :last-page="lastPage" :total="totalSemestres" />
            </div>
        </div>

        <SemestreModal v-model="isFormModalOpen" :semestre="selectedSemestre" :is-saving="isSaving"
            @save="handleSaveSemestre" />

        <SemestreDeleteModal v-model="isDeleteModalOpen" :semestre="selectedSemestre" :is-saving="isSaving"
            @confirm="handleConfirmDelete" />
    </div>
</template>
