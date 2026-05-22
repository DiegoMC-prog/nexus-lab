<script setup lang="ts">
import { ref, watch, onMounted } from 'vue';
import { Search, Plus, Edit, Trash2, BookOpen, Loader2, Filter } from '@lucide/vue';
import { materiaService } from '@/services/materiaService';
import type { Materia, MateriaFormData } from '@/types/materia';

// Componentes del Sistema
import BasePagination from '@/components/BasePagination.vue';
import MateriaModal from './MateriaModal.vue';
import MateriaDeleteModal from './MateriaDeleteModal.vue';

// Catálogos cargados de form-data
const carreras = ref<{ id: number; nombre: string; }[]>([]);
const semestres = ref<{ id: number; nombre: string; }[]>([]);

// Estados Reactivos de Datos
const materias = ref<Materia[]>([]);
const searchTerm = ref('');
const selectedCarreraFilter = ref('all');
const selectedSemestreFilter = ref('all');
const isLoading = ref(true);
const isSaving = ref(false);

// Estados de Paginación
const currentPage = ref(1);
const lastPage = ref(1);
const totalMaterias = ref(0);

// Estados de Control de Modales
const isFormModalOpen = ref(false);
const isDeleteModalOpen = ref(false);
const selectedMateria = ref<Materia | null>(null);

// Carga los catálogos necesarios para los filtros y el formulario
const loadFormCatalogs = async () => {
    try {
        const res = await materiaService.getFormData();
        carreras.value = res.carreras;
        semestres.value = res.semestres;
    } catch (error) {
        console.error('Error al cargar catálogos de materias:', error);
    }
};

// Carga asíncrona desde el Servidor Laravel
const fetchMaterias = async () => {
    isLoading.value = true;
    await fetchMateriasLógicaInterna();
    isLoading.value = false;
};

const fetchMateriasLógicaInterna = async () => {
    try {
        const res = await materiaService.getMaterias({
            search: searchTerm.value,
            carrera_id: selectedCarreraFilter.value,
            semestre_academico_id: selectedSemestreFilter.value,
            page: currentPage.value.toString()
        });
        materias.value = res.data;
        lastPage.value = res.last_page;
        totalMaterias.value = res.total;
    } catch (error) {
        console.error('Error al consultar el catálogo de materias:', error);
    }
};

// Antirrebote (Debounce) nativo para el buscador de texto
let debounceTimeout: ReturnType<typeof setTimeout>;
watch(searchTerm, () => {
    clearTimeout(debounceTimeout);
    debounceTimeout = setTimeout(() => {
        currentPage.value = 1;
        fetchMaterias();
    }, 400);
});

// Watchers para recarga en filtros select
watch([selectedCarreraFilter, selectedSemestreFilter], () => {
    currentPage.value = 1;
    fetchMaterias();
});

// Watch directo para el cambio de páginas en el paginador
watch(currentPage, () => {
    fetchMaterias();
});

// Control de flujos de apertura de modales
const openCreateModal = () => {
    selectedMateria.value = null;
    isFormModalOpen.value = true;
};

const openEditModal = (materia: Materia) => {
    selectedMateria.value = materia;
    isFormModalOpen.value = true;
};

const openDeleteModal = (materia: Materia) => {
    selectedMateria.value = materia;
    isDeleteModalOpen.value = true;
};

// Controladores de eventos del CRUD
const handleSaveMateria = async (formData: MateriaFormData) => {
    isSaving.value = true;
    try {
        if (selectedMateria.value) {
            await materiaService.actualizarMateria(selectedMateria.value.id, formData);
        } else {
            await materiaService.crearMateria(formData);
        }
        isFormModalOpen.value = false; // Solo cierra si finaliza con éxito en la BD
        fetchMaterias();
    } catch (error) {
        console.error('Error al guardar la materia:', error);
    } finally {
        isSaving.value = false;
    }
};

const handleConfirmDelete = async () => {
    if (!selectedMateria.value) return;
    isSaving.value = true;
    try {
        await materiaService.eliminarMateria(selectedMateria.value.id);
        isDeleteModalOpen.value = false;

        // Regresar de página automáticamente si borramos el único ítem de una sección remota
        if (materias.value.length === 1 && currentPage.value > 1) {
            currentPage.value--;
        } else {
            fetchMaterias();
        }
    } catch (error) {
        console.error('Error al intentar eliminar el recurso:', error);
    } finally {
        isSaving.value = false;
    }
};

onMounted(async () => {
    isLoading.value = true;

    try {
        // OPTIMIZACIÓN: Ejecutamos ambas peticiones en paralelo (Promise.all)
        // Esto reduce a la mitad el tiempo de espera inicial en el frontend
        await Promise.all([
            loadFormCatalogs(),
            fetchMateriasLógicaInterna() // Separamos la lógica para no resetear el spinner
        ]);
    } catch (error) {
        console.error('Error en la inicialización modular:', error);
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
                <span class="text-sm font-medium text-gray-700">Cargando materias...</span>
            </div>
        </div>

        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 flex items-center gap-2">
                    <BookOpen class="w-7 h-7 text-blue-600" />
                    Gestión de Materias Curriculares
                </h1>
                <p class="text-sm text-gray-500 mt-1">
                    {{ totalMaterias }} materias registradas en los diferentes programas académicos
                </p>
            </div>
            <button @click="openCreateModal"
                class="inline-flex items-center justify-center bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-lg shadow-sm gap-2 transition-colors text-sm">
                <Plus class="w-4 h-4" />
                Nueva Materia
            </button>
        </div>

        <!-- Panel de Filtros Multicriterio -->
        <div class="bg-white p-4 rounded-xl border border-gray-200 shadow-sm grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="relative">
                <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" />
                <input v-model="searchTerm" type="text" placeholder="Buscar por nombre o código de materia..."
                    class="w-full pl-10 pr-4 py-2 bg-white border border-gray-200 text-gray-900 placeholder:text-gray-400 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm transition-all" />
            </div>

            <div class="relative flex items-center gap-2">
                <Filter class="w-4 h-4 text-gray-400 shrink-0" />
                <select v-model="selectedCarreraFilter"
                    class="w-full px-3 py-2 bg-white border border-gray-200 text-gray-900 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm transition-all">
                    <option value="all">Todas las Carreras</option>
                    <option v-for="carrera in carreras" :key="carrera.id" :value="carrera.id">
                        {{ carrera.nombre }}
                    </option>
                </select>
            </div>

            <div class="relative flex items-center gap-2">
                <Filter class="w-4 h-4 text-gray-400 shrink-0" />
                <select v-model="selectedSemestreFilter"
                    class="w-full px-3 py-2 bg-white border border-gray-200 text-gray-900 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm transition-all">
                    <option value="all">Todos los Semestres</option>
                    <option v-for="semestre in semestres" :key="semestre.id" :value="semestre.id">
                        {{ semestre.nombre }}
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
                            <th class="px-6 py-4">Código</th>
                            <th class="px-6 py-4">Nombre de la Materia</th>
                            <th class="px-6 py-4">Carrera</th>
                            <th class="px-6 py-4">Ciclo Académico</th>
                            <th class="px-6 py-4">Créditos</th>
                            <th class="px-6 py-4 text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 text-sm text-gray-700">
                        <tr v-if="materias.length === 0 && !isLoading">
                            <td colspan="6" class="px-6 py-10 text-center text-gray-400">
                                No se encontraron materias que coincidan con los criterios de búsqueda.
                            </td>
                        </tr>
                        <tr v-for="materia in materias" :key="materia.id" class="hover:bg-gray-50/70 transition-colors">
                            <td class="px-6 py-4 font-mono font-bold text-gray-600">
                                <span class="bg-gray-100 px-2 py-1 rounded text-xs border border-gray-200">
                                    {{ materia.codigo }}
                                </span>
                            </td>
                            <td class="px-6 py-4 font-semibold text-gray-900">
                                {{ materia.nombre }}
                            </td>
                            <td class="px-6 py-4 text-gray-600">
                                {{ materia.nombre_carrera || 'No asignada' }}
                            </td>
                            <td class="px-6 py-4 text-gray-500 font-medium">
                                <span
                                    class="bg-blue-50 text-blue-700 border border-blue-150 px-2 py-0.5 rounded text-xs">
                                    {{ materia.nombre_semestre || 'No asignado' }}
                                </span>
                            </td>
                            <td class="px-6 py-4 font-mono font-bold text-gray-700">
                                {{ materia.creditos }}
                            </td>
                            <td class="px-6 py-4 text-right space-x-1">
                                <button @click="openEditModal(materia)"
                                    class="inline-flex items-center justify-center p-2 text-blue-600 hover:text-blue-700 hover:bg-blue-50 rounded-lg transition-colors"
                                    title="Editar Materia">
                                    <Edit class="w-4 h-4" />
                                </button>
                                <button @click="openDeleteModal(materia)"
                                    class="inline-flex items-center justify-center p-2 text-red-600 hover:text-red-700 hover:bg-red-50 rounded-lg transition-colors"
                                    title="Eliminar Materia">
                                    <Trash2 class="w-4 h-4" />
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="px-6 py-4 border-t border-gray-200 bg-gray-50/50">
                <BasePagination v-model="currentPage" :last-page="lastPage" :total="totalMaterias" />
            </div>
        </div>

        <MateriaModal v-model="isFormModalOpen" :materia="selectedMateria" :is-saving="isSaving" :carreras="carreras"
            :semestres="semestres" @save="handleSaveMateria" />

        <MateriaDeleteModal v-model="isDeleteModalOpen" :materia="selectedMateria" :is-saving="isSaving"
            @confirm="handleConfirmDelete" />
    </div>
</template>
