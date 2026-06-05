<script setup lang="ts">
import { ref, watch, onMounted } from 'vue';
import { Search, Plus, Edit, Trash2, Users, Loader2, Filter, GraduationCap } from '@lucide/vue';
import { grupoService } from '@/services/grupoService';
import type { Grupo, GrupoFormData } from '@/types/grupo';
import { useAuthStore } from '@/stores/auth';

// Componentes del Sistema
import BasePagination from '@/components/BasePagination.vue';
import GrupoModal from './GrupoModal.vue';
import GrupoDeleteModal from './GrupoDeleteModal.vue';
import GrupoEstudiantesModal from './GrupoEstudiantesModal.vue';
import GrupoEstudiantesVerModal from './GrupoEstudiantesVerModal.vue';
import { getLaravelValidationErrors } from '@/utils/errorHandler';
import { useToast } from '@/composables/useToast';

const toast = useToast();
const authStore = useAuthStore();

// Catálogos cargados de form-data
const materiasOptions = ref<{ id: number; nombre: string; }[]>([]);

// Estados Reactivos de Datos
const grupos = ref<Grupo[]>([]);
const searchTerm = ref('');
const selectedMateriaFilter = ref('all');
const isLoading = ref(true);
const isSaving = ref(false);
const validationErrors = ref<Record<string, string[]> | undefined>(undefined);

// Estados de Paginación
const currentPage = ref(1);
const lastPage = ref(1);
const totalGrupos = ref(0);

// Estados de Control de Modales
const isFormModalOpen = ref(false);
const isDeleteModalOpen = ref(false);
const isEstudiantesModalOpen = ref(false);
const isVerEstudiantesModalOpen = ref(false);
const selectedGrupo = ref<Grupo | null>(null);
const selectedGrupoForEstudiantes = ref<Grupo | null>(null);
const selectedGrupoForVerEstudiantes = ref<Grupo | null>(null);

// Carga los catálogos necesarios para los filtros y el formulario
const loadFormCatalogs = async () => {
    try {
        const res = await grupoService.getFormData();
        materiasOptions.value = res.materias;
    } catch (error) {
        console.error('Error al cargar catálogo de materias para grupos:', error);
    }
};

// Carga asíncrona desde el Servidor Laravel
const fetchGrupos = async () => {
    isLoading.value = true;
    await fetchGruposLogicaInterna();
    isLoading.value = false;
};

const fetchGruposLogicaInterna = async () => {
    try {
        const res = await grupoService.getGrupos({
            search: searchTerm.value,
            materia_id: selectedMateriaFilter.value,
            page: currentPage.value.toString()
        });
        grupos.value = res.data;
        lastPage.value = res.last_page;
        totalGrupos.value = res.total;
    } catch (error) {
        console.error('Error al consultar el catálogo de grupos:', error);
    }
};

// Antirrebote (Debounce) nativo para el buscador de texto
let debounceTimeout: ReturnType<typeof setTimeout>;
watch(searchTerm, () => {
    clearTimeout(debounceTimeout);
    debounceTimeout = setTimeout(() => {
        currentPage.value = 1;
        fetchGrupos();
    }, 400);
});

// Watchers para recarga en filtros select
watch(selectedMateriaFilter, () => {
    currentPage.value = 1;
    fetchGrupos();
});

// Watch directo para el cambio de páginas en el paginador
watch(currentPage, () => {
    fetchGrupos();
});

// Control de flujos de apertura de modales
const openCreateModal = () => {
    selectedGrupo.value = null;
    validationErrors.value = undefined;
    isFormModalOpen.value = true;
};

const openEditModal = (grupo: Grupo) => {
    selectedGrupo.value = grupo;
    validationErrors.value = undefined;
    isFormModalOpen.value = true;
};

const openDeleteModal = (grupo: Grupo) => {
    selectedGrupo.value = grupo;
    isDeleteModalOpen.value = true;
};

const openEstudiantesModal = (grupo: Grupo) => {
    selectedGrupoForEstudiantes.value = grupo;
    isEstudiantesModalOpen.value = true;
};

const openVerEstudiantesModal = (grupo: Grupo) => {
    selectedGrupoForVerEstudiantes.value = grupo;
    isVerEstudiantesModalOpen.value = true;
};

// Controladores de eventos del CRUD
const handleSaveGrupo = async (formData: GrupoFormData) => {
    isSaving.value = true;
    validationErrors.value = undefined;
    try {
        if (selectedGrupo.value) {
            await grupoService.actualizarGrupo(selectedGrupo.value.id, formData);
            toast.success('Grupo actualizado', 'El grupo ha sido actualizado correctamente.');
        } else {
            await grupoService.crearGrupo(formData);
            toast.success('Grupo creado', 'El grupo ha sido registrado correctamente.');
        }
        isFormModalOpen.value = false; // Solo cierra si finaliza con éxito en la BD
        fetchGrupos();
    } catch (error: any) {
        console.error('Error al guardar el grupo académico:', error);
        if (error.response && error.response.status === 422) {
            validationErrors.value = getLaravelValidationErrors(error);
            toast.warning('Errores de validación', 'Por favor revisa los campos del formulario.');
        } else {
            toast.error('Error', 'No se pudo guardar la información del grupo.');
        }
    } finally {
        isSaving.value = false;
    }
};

const handleConfirmDelete = async () => {
    if (!selectedGrupo.value) return;
    isSaving.value = true;
    try {
        await grupoService.eliminarGrupo(selectedGrupo.value.id);
        isDeleteModalOpen.value = false;
        toast.success('Grupo eliminado', 'El grupo ha sido eliminado correctamente.');

        // Regresar de página automáticamente si borramos el único ítem de una sección remota
        if (grupos.value.length === 1 && currentPage.value > 1) {
            currentPage.value--;
        } else {
            fetchGrupos();
        }
    } catch (error) {
        console.error('Error al intentar eliminar el recurso grupo:', error);
        toast.error('Error', 'Hubo un problema al intentar eliminar el grupo.');
    } finally {
        isSaving.value = false;
    }
};

onMounted(async () => {
    isLoading.value = true;
    try {
        // OPTIMIZACIÓN: Ejecutamos ambas peticiones en paralelo (Promise.all)
        await Promise.all([
            loadFormCatalogs(),
            fetchGruposLogicaInterna()
        ]);
    } catch (error) {
        console.error('Error en la inicialización modular de grupos:', error);
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
                <span class="text-sm font-medium text-gray-700">Cargando grupos académicos...</span>
            </div>
        </div>

        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 flex items-center gap-2">
                    <Users class="w-7 h-7 text-blue-600" />
                    Gestión de Grupos Académicos
                </h1>
                <p class="text-sm text-gray-500 mt-1">
                    {{ totalGrupos }} grupos registrados en las asignaturas vigentes
                </p>
            </div>
            <button v-if="authStore.can('grupos.crear')" @click="openCreateModal"
                class="inline-flex items-center justify-center bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-lg shadow-sm gap-2 transition-colors text-sm">
                <Plus class="w-4 h-4" />
                Nuevo Grupo
            </button>
        </div>

        <!-- Panel de Filtros Multicriterio -->
        <div class="bg-white p-4 rounded-xl border border-gray-200 shadow-sm grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="relative">
                <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" />
                <input v-model="searchTerm" type="text" placeholder="Buscar por nombre de grupo o materia..."
                    class="w-full pl-10 pr-4 py-2 bg-white border border-gray-200 text-gray-900 placeholder:text-gray-400 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm transition-all" />
            </div>

            <div class="relative flex items-center gap-2">
                <Filter class="w-4 h-4 text-gray-400 shrink-0" />
                <select v-model="selectedMateriaFilter"
                    class="w-full px-3 py-2 bg-white border border-gray-200 text-gray-900 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm transition-all">
                    <option value="all">Todas las Materias</option>
                    <option v-for="materia in materiasOptions" :key="materia.id" :value="materia.id">
                        {{ materia.nombre }}
                    </option>
                </select>
            </div>
        </div>

        <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr
                            class="bg-gray-50 border-b border-gray-200 text-xs font-semibold text-gray-500 uppercase tracking-wider">
                            <th class="px-6 py-4">Materia</th>
                            <th class="px-6 py-4">Grupo / Sección</th>
                            <th class="px-6 py-4">Gestión Académica</th>
                            <th class="px-6 py-4">Cupo Máximo</th>
                            <th class="px-6 py-4 text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 text-sm text-gray-700">
                        <tr v-if="grupos.length === 0 && !isLoading">
                            <td colspan="5" class="px-6 py-10 text-center text-gray-400">
                                No se encontraron grupos académicos que coincidan con los criterios.
                            </td>
                        </tr>
                        <tr v-for="grupo in grupos" :key="grupo.id" class="hover:bg-gray-50/70 transition-colors">
                            <td class="px-6 py-4 font-semibold text-gray-900">
                                {{ grupo.nombre_materia || 'No asignada' }}
                            </td>
                            <td class="px-6 py-4 text-gray-600 font-mono font-bold">
                                <span class="bg-blue-50 text-blue-700 border border-blue-150 px-2 py-1 rounded text-xs">
                                    {{ grupo.nombre }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-gray-500 font-medium">
                                {{ grupo.gestion }}
                            </td>
                            <td class="px-6 py-4 font-mono font-bold text-gray-700">
                                {{ grupo.cupo_maximo > 0 ? `${grupo.cupo_maximo} alumnos` : 'Sin límite' }}
                            </td>
                            <td class="px-6 py-4 text-right space-x-1">
                                <button @click="openVerEstudiantesModal(grupo)"
                                    class="inline-flex items-center justify-center p-2 text-emerald-600 hover:text-emerald-700 hover:bg-emerald-50 rounded-lg transition-colors"
                                    title="Ver Estudiantes Inscritos">
                                    <Users class="w-4 h-4" />
                                </button>
                                <button v-if="authStore.can('grupos.editar')" @click="openEstudiantesModal(grupo)"
                                    class="inline-flex items-center justify-center p-2 text-purple-600 hover:text-purple-700 hover:bg-purple-50 rounded-lg transition-colors"
                                    title="Gestionar Estudiantes">
                                    <GraduationCap class="w-4 h-4" />
                                </button>
                                <button v-if="authStore.can('grupos.editar')" @click="openEditModal(grupo)"
                                    class="inline-flex items-center justify-center p-2 text-blue-600 hover:text-blue-700 hover:bg-blue-50 rounded-lg transition-colors"
                                    title="Editar Grupo">
                                    <Edit class="w-4 h-4" />
                                </button>
                                <button v-if="authStore.can('grupos.eliminar')" @click="openDeleteModal(grupo)"
                                    class="inline-flex items-center justify-center p-2 text-red-600 hover:text-red-700 hover:bg-red-50 rounded-lg transition-colors"
                                    title="Eliminar Grupo">
                                    <Trash2 class="w-4 h-4" />
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="px-6 py-4 border-t border-gray-200 bg-gray-50/50">
                <BasePagination v-model="currentPage" :last-page="lastPage" :total="totalGrupos" />
            </div>
        </div>

        <GrupoModal v-model="isFormModalOpen" :grupo="selectedGrupo" :is-saving="isSaving" :materias="materiasOptions"
            :validation-errors="validationErrors" @save="handleSaveGrupo" />

        <GrupoDeleteModal v-model="isDeleteModalOpen" :grupo="selectedGrupo" :is-saving="isSaving"
            @confirm="handleConfirmDelete" />

        <GrupoEstudiantesModal v-model="isEstudiantesModalOpen" :grupo="selectedGrupoForEstudiantes" />

        <GrupoEstudiantesVerModal v-model="isVerEstudiantesModalOpen" :grupo="selectedGrupoForVerEstudiantes" />
    </div>
</template>

<style scoped>
.backdrop-blur-xs {
    backdrop-filter: blur(2px);
}
</style>
