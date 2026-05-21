<script setup lang="ts">
import { ref, watch, onMounted, computed } from 'vue';
import { Search, Plus, Edit, Trash2, BookOpen, Layers, Loader2 } from '@lucide/vue';
import { cursoService, semestreService } from '@/services/academicService';
// import type { Curso, CursoFormData, Semestre, SemestreFormData } from '@/types/academic';
import type { Curso, CursoFormData } from '@/types/curso';
import type { Semestre, SemestreFormData } from '@/types/Semestre';

// Componentes del Sistema Reutilizables
import BasePagination from '@/components/BasePagination.vue';
import CursoModal from './CursoModal.vue';
import SemestreModal from './SemestreModal.vue';
import CursoDeleteModal from './CursoDeleteModal.vue';
import SemestreDeleteModal from './SemestreDeleteModal.vue';

// Control de Pestañas Activas (Tabs)
const activeTab = ref<'cursos' | 'semestres'>('cursos');

// Estados Reactivos globales de Carga y Guardado
const isLoading = ref(false);
const isSaving = ref(false);
const searchTerm = ref('');

// Estados Reactivos de Datos
const cursos = ref<Curso[]>([]);
const semestres = ref<Semestre[]>([]);

// Catálogos para Selects de Filtros Avanzados (Pestaña Cursos)
const carreraIdFiltro = ref('');
const semestreIdFiltro = ref('');
const listaCarrerasOpciones = ref<any[]>([]); // Se alimenta de carreraService si es necesario
const listaSemestresOpciones = ref<Semestre[]>([]);

// Estados Compartidos de Paginación Dinámica
const currentPage = ref(1);
const lastPage = ref(1);
const totalRecords = ref(0);

// Estados de Control de Modales Seleccionados
const isCursoModalOpen = ref(false);
const isSemestreModalOpen = ref(false);
const isCursoDeleteOpen = ref(false);
const isSemestreDeleteOpen = ref(false);

const selectedCurso = ref<Curso | null>(null);
const selectedSemestre = ref<Semestre | null>(null);

// 📡 Orquestador de peticiones asíncronas según la pestaña activa
const fetchTabData = async () => {
    isLoading.value = true;
    try {
        if (activeTab.value === 'cursos') {
            const res = await cursoService.getCursos({
                search: searchTerm.value,
                carrera_id: carreraIdFiltro.value,
                semestre_academico_id: semestreIdFiltro.value,
                page: currentPage.value
            });
            cursos.value = res.data;
            lastPage.value = res.last_page;
            totalRecords.value = res.total;
        } else {
            const res = await semestreService.getSemestres({
                search: searchTerm.value,
                page: currentPage.value
            });
            semestres.value = res.data;
            lastPage.value = res.last_page;
            totalRecords.value = res.total;
        }
    } catch (error) {
        console.error(`Error al consultar datos de ${activeTab.value}:`, error);
    } finally {
        isLoading.value = false;
    }
};

// Carga asíncrona de los semestres del catálogo base para los selectores de filtros
const fetchFilterOptions = async () => {
    try {
        const res = await semestreService.getSemestres();
        listaSemestresOpciones.value = res.data || res;
    } catch (error) {
        console.error('Error cargando catálogos de filtros:', error);
    }
};

// ⏱️ Antirrebote (Debounce) nativo para proteger PostgreSQL
let debounceTimeout: ReturnType<typeof setTimeout>;
watch(searchTerm, () => {
    clearTimeout(debounceTimeout);
    debounceTimeout = setTimeout(() => {
        currentPage.value = 1;
        fetchTabData();
    }, 400);
});

// Watchers inmediatos para cambios de paginación y filtros selects
watch(currentPage, () => {
    fetchTabData();
});

watch([carreraIdFiltro, semestreIdFiltro], () => {
    if (activeTab.value === 'cursos') {
        currentPage.value = 1;
        fetchTabData();
    }
});

// 🔄 Manejador de cambio de pestaña limpio
const switchTab = (tab: 'cursos' | 'semestres') => {
    activeTab.value = tab;
    searchTerm.value = '';
    carreraIdFiltro.value = '';
    semestreIdFiltro.value = '';
    currentPage.value = 1;
    fetchTabData();
};

// ---------- FLUJO DE MODALES: CURSOS ----------
const openCreateCurso = () => {
    selectedCurso.value = null;
    isCursoModalOpen.value = true;
};

const openEditCurso = (curso: Curso) => {
    selectedCurso.value = curso;
    isCursoModalOpen.value = true;
};

const openDeleteCurso = (curso: Curso) => {
    selectedCurso.value = curso;
    isCursoDeleteOpen.value = true;
};

const handleSaveCurso = async (formData: CursoFormData) => {
    isSaving.value = true;
    try {
        if (selectedCurso.value) {
            await cursoService.actualizarCurso(selectedCurso.value.id, formData);
        } else {
            await cursoService.crearCurso(formData);
        }
        isCursoModalOpen.value = false;
        fetchTabData();
    } catch (error) {
        console.error('Error al guardar el curso:', error);
    } finally {
        isSaving.value = false;
    }
};

const handleConfirmDeleteCurso = async () => {
    if (!selectedCurso.value) return;
    isSaving.value = true;
    try {
        await cursoService.eliminarCurso(selectedCurso.value.id);
        isCursoDeleteOpen.value = false;
        if (cursos.value.length === 1 && currentPage.value > 1) {
            currentPage.value--;
        } else {
            fetchTabData();
        }
    } catch (error) {
        console.error('Error al eliminar curso:', error);
    } finally {
        isSaving.value = false;
    }
};

// ---------- FLUJO DE MODALES: CATÁLOGO SEMESTRES ----------
const openCreateSemestre = () => {
    selectedSemestre.value = null;
    isSemestreModalOpen.value = true;
};

const openEditSemestre = (semestre: Semestre) => {
    selectedSemestre.value = semestre;
    isSemestreModalOpen.value = true;
};

const openDeleteSemestre = (semestre: Semestre) => {
    selectedSemestre.value = semestre;
    isSemestreDeleteOpen.value = true;
};

const handleSaveSemestre = async (formData: SemestreFormData) => {
    isSaving.value = true;
    try {
        if (selectedSemestre.value) {
            await semestreService.actualizarSemestre(selectedSemestre.value.id, formData);
        } else {
            await semestreService.crearSemestre(formData);
        }
        isSemestreModalOpen.value = false;
        fetchTabData();
        fetchFilterOptions(); // Refresca los selects locales de filtros
    } catch (error) {
        console.error('Error al guardar el semestre base:', error);
    } finally {
        isSaving.value = false;
    }
};

const handleConfirmDeleteSemestre = async () => {
    if (!selectedSemestre.value) return;
    isSaving.value = true;
    try {
        await semestreService.eliminarSemestre(selectedSemestre.value.id);
        isSemestreDeleteOpen.value = false;
        if (semestres.value.length === 1 && currentPage.value > 1) {
            currentPage.value--;
        } else {
            fetchTabData();
        }
        fetchFilterOptions();
    } catch (error) {
        console.error('Error al eliminar el semestre catálogo:', error);
    } finally {
        isSaving.value = false;
    }
};

onMounted(() => {
    fetchTabData();
    fetchFilterOptions();
});
</script>

<template>
    <div class="p-6 space-y-6 relative">
        <div v-if="isLoading"
            class="absolute inset-0 bg-white/40 backdrop-blur-xs z-50 flex items-center justify-center min-h-100 rounded-xl transition-all">
            <div class="bg-white p-4 rounded-xl shadow-lg border border-gray-100 flex items-center gap-3">
                <Loader2 class="w-5 h-5 text-blue-600 animate-spin" />
                <span class="text-sm font-medium text-gray-700">Sincronizando datos académicos...</span>
            </div>
        </div>

        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 flex items-center gap-2">
                    <BookOpen v-if="activeTab === 'cursos'" class="w-7 h-7 text-blue-600" />
                    <Layers v-else class="w-7 h-7 text-emerald-600" />
                    Configuración Académica
                </h1>
                <p class="text-sm text-gray-500 mt-1">
                    <span v-if="activeTab === 'cursos'">{{ totalRecords }} asignaciones de cursos activos por
                        carrera</span>
                    <span v-else>{{ totalRecords }} semestres base configurados en el catálogo central</span>
                </p>
            </div>

            <button @click="activeTab === 'cursos' ? openCreateCurso() : openCreateSemestre()"
                class="inline-flex items-center justify-center font-medium py-2 px-4 rounded-lg shadow-sm gap-2 transition-colors text-sm text-white"
                :class="activeTab === 'cursos' ? 'bg-blue-600 hover:bg-blue-700' : 'bg-emerald-600 hover:bg-emerald-700'">
                <Plus class="w-4 h-4" />
                {{ activeTab === 'cursos' ? 'Asignar Curso' : 'Nuevo Semestre Base' }}
            </button>
        </div>

        <div class="flex border-b border-gray-200 bg-gray-50/60 p-1 rounded-xl w-fit gap-1">
            <button @click="switchTab('cursos')" :class="[
                'px-4 py-1.5 rounded-lg text-xs font-semibold tracking-wide uppercase transition-all duration-200',
                activeTab === 'cursos'
                    ? 'bg-white text-blue-600 shadow-xs ring-1 ring-black/5'
                    : 'text-gray-500 hover:text-gray-900'
            ]">
                Cursos por Carrera
            </button>
            <button @click="switchTab('semestres')" :class="[
                'px-4 py-1.5 rounded-lg text-xs font-semibold tracking-wide uppercase transition-all duration-200',
                activeTab === 'semestres'
                    ? 'bg-white text-emerald-600 shadow-xs ring-1 ring-black/5'
                    : 'text-gray-500 hover:text-gray-900'
            ]">
                Catálogo de Semestres
            </button>
        </div>

        <div
            class="bg-white p-4 rounded-xl border border-gray-200 shadow-sm flex flex-col md:flex-row gap-4 justify-between items-center">
            <div class="relative w-full max-w-md">
                <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" />
                <input v-model="searchTerm" type="text"
                    :placeholder="activeTab === 'cursos' ? 'Buscar carrera o semestre...' : 'Buscar semestre base...'"
                    class="w-full pl-10 pr-4 py-2 bg-white border border-gray-200 text-gray-900 placeholder:text-gray-400 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm transition-all" />
            </div>

            <div v-if="activeTab === 'cursos'" class="flex items-center gap-3 w-full md:w-auto">
                <select v-model="semestreIdFiltro"
                    class="bg-white border border-gray-200 text-gray-700 text-xs rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 outline-none">
                    <option value="">Filtrar por Semestre</option>
                    <option v-for="opcion in listaSemestresOpciones" :key="opcion.id" :value="opcion.id">
                        {{ opcion.nombre }}
                    </option>
                </select>
            </div>
        </div>

        <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
            <div class="overflow-x-auto">

                <table v-if="activeTab === 'cursos'" class="w-full text-left border-collapse">
                    <thead>
                        <tr
                            class="bg-gray-50 border-b border-gray-200 text-xs font-semibold text-gray-500 uppercase tracking-wider">
                            <th class="px-6 py-4">ID</th>
                            <th class="px-6 py-4">Carrera / Programa</th>
                            <th class="px-6 py-4">Semestre Académico</th>
                            <th class="px-6 py-4 text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 text-sm text-gray-700">
                        <tr v-if="cursos.length === 0 && !isLoading">
                            <td colspan="4" class="px-6 py-10 text-center text-gray-400">
                                No se encontraron asignaciones de cursos activos.
                            </td>
                        </tr>
                        <tr v-for="curso in cursos" :key="curso.id" class="hover:bg-gray-50/70 transition-colors">
                            <td class="px-6 py-4 font-mono text-xs text-gray-400">#{{ curso.id }}</td>
                            <td class="px-6 py-4 font-medium text-gray-900">{{ curso.carrera }}</td>
                            <td class="px-6 py-4">
                                <span
                                    class="bg-blue-50 text-blue-700 px-2.5 py-1 rounded-md text-xs font-semibold border border-blue-100">
                                    {{ curso.semestre }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right space-x-1">
                                <button @click="openEditCurso(curso)"
                                    class="p-2 text-blue-600 hover:bg-blue-50 rounded-lg transition-colors">
                                    <Edit class="w-4 h-4" />
                                </button>
                                <button @click="openDeleteCurso(curso)"
                                    class="p-2 text-red-600 hover:bg-red-50 rounded-lg transition-colors">
                                    <Trash2 class="w-4 h-4" />
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <table v-else class="w-full text-left border-collapse">
                    <thead>
                        <tr
                            class="bg-gray-50 border-b border-gray-200 text-xs font-semibold text-gray-500 uppercase tracking-wider">
                            <th class="px-6 py-4">ID</th>
                            <th class="px-6 py-4">Nombre del Semestre Base</th>
                            <th class="px-6 py-4 text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 text-sm text-gray-700">
                        <tr v-if="semestres.length === 0 && !isLoading">
                            <td colspan="3" class="px-6 py-10 text-center text-gray-400">
                                El catálogo maestro de semestres se encuentra vacío.
                            </td>
                        </tr>
                        <tr v-for="sem in semestres" :key="sem.id" class="hover:bg-gray-50/70 transition-colors">
                            <td class="px-6 py-4 font-mono text-xs text-gray-400">#{{ sem.id }}</td>
                            <td class="px-6 py-4 font-medium text-gray-900">{{ sem.nombre }}</td>
                            <td class="px-6 py-4 text-right space-x-1">
                                <button @click="openEditSemestre(sem)"
                                    class="p-2 text-emerald-600 hover:bg-emerald-50 rounded-lg transition-colors">
                                    <Edit class="w-4 h-4" />
                                </button>
                                <button @click="openDeleteSemestre(sem)"
                                    class="p-2 text-red-600 hover:bg-red-50 rounded-lg transition-colors">
                                    <Trash2 class="w-4 h-4" />
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>

            </div>

            <div class="px-6 py-4 border-t border-gray-200 bg-gray-50/50">
                <BasePagination v-model="currentPage" :last-page="lastPage" :total="totalRecords" />
            </div>
        </div>

        <CursoModal v-model="isCursoModalOpen" :curso="selectedCurso" :is-saving="isSaving" @save="handleSaveCurso" />
        <CursoDeleteModal v-model="isCursoDeleteOpen" :curso="selectedCurso" :is-saving="isSaving"
            @confirm="handleConfirmDeleteCurso" />

        <SemestreModal v-model="isSemestreModalOpen" :semestre="selectedSemestre" :is-saving="isSaving"
            @save="handleSaveSemestre" />
        <SemestreDeleteModal v-model="isSemestreDeleteOpen" :semestre="selectedSemestre" :is-saving="isSaving"
            @confirm="handleConfirmDeleteSemestre" />
    </div>
</template>