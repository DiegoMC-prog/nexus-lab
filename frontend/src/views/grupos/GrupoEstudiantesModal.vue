<script setup lang="ts">
import { ref, watch, computed } from 'vue';
import { X, Search, Users, Loader2, UserPlus, UserMinus, GraduationCap, CheckCircle2, AlertCircle } from '@lucide/vue';
import { grupoService } from '@/services/grupoService';
import type { Grupo, GrupoEstudiante } from '@/types/grupo';

const props = defineProps<{
    modelValue: boolean;
    grupo: Grupo | null;
}>();

const emit = defineEmits<{
    (e: 'update:modelValue', value: boolean): void;
}>();

// Estados de carga e interfaz
const isLoading = ref(false);
const isSaving = ref(false);
const isSearching = ref(false);
const errorMessage = ref('');
const successMessage = ref('');

// Listas locales
const enrolledStudents = ref<GrupoEstudiante[]>([]);
const searchResults = ref<GrupoEstudiante[]>([]);
const searchQuery = ref('');

// Sincronización al abrir el modal
watch(() => props.modelValue, async (isOpen) => {
    if (isOpen) {
        // Reset de estados
        errorMessage.value = '';
        successMessage.value = '';
        searchQuery.value = '';
        searchResults.value = [];
        enrolledStudents.value = [];

        if (props.grupo) {
            isLoading.value = true;
            try {
                const res = await grupoService.listarEstudiantes(props.grupo.id);
                enrolledStudents.value = res.estudiantes;
            } catch (err: any) {
                console.error('Error al cargar estudiantes del grupo:', err);
                errorMessage.value = 'Ocurrió un error al obtener la lista de estudiantes inscritos.';
            } finally {
                isLoading.value = false;
            }
        }
    }
});

// Buscador con debounce nativo
let searchTimeout: ReturnType<typeof setTimeout>;
watch(searchQuery, (newVal) => {
    clearTimeout(searchTimeout);
    errorMessage.value = '';
    
    if (!newVal.trim()) {
        searchResults.value = [];
        return;
    }

    isSearching.value = true;
    searchTimeout = setTimeout(async () => {
        try {
            const res = await grupoService.searchEstudiante(newVal);
            searchResults.value = res.estudiantes;
        } catch (err: any) {
            console.error('Error al buscar estudiantes:', err);
            errorMessage.value = 'Ocurrió un error en la búsqueda de estudiantes.';
        } finally {
            isSearching.value = false;
        }
    }, 400);
});

// Ayudantes de Lógica de la Vista
const isEnrolled = (studentId: number): boolean => {
    return enrolledStudents.value.some(s => s.id === studentId);
};

const isCupoMaximoAlcanzado = computed(() => {
    return !!(props.grupo && props.grupo.cupo_maximo > 0 && enrolledStudents.value.length >= props.grupo.cupo_maximo);
});

const addStudent = (student: GrupoEstudiante) => {
    if (isEnrolled(student.id)) return;
    
    if (props.grupo && props.grupo.cupo_maximo > 0 && enrolledStudents.value.length >= props.grupo.cupo_maximo) {
        errorMessage.value = `No se puede agregar a ${student.name}. Se ha alcanzado el cupo máximo permitido (${props.grupo.cupo_maximo} estudiantes).`;
        return;
    }
    
    enrolledStudents.value.push(student);
    errorMessage.value = '';
};

const removeStudent = (studentId: number) => {
    enrolledStudents.value = enrolledStudents.value.filter(s => s.id !== studentId);
};

const getInitials = (name: string): string => {
    if (!name) return 'U';
    const parts = name.trim().split(/\s+/);
    const first = parts[0]?.charAt(0) || '';
    const second = parts[1]?.charAt(0) || '';
    if (first && second) {
        return (first + second).toUpperCase();
    }
    return name.trim().slice(0, 2).toUpperCase();
};

// Guardado de la relación en el backend
const handleSave = async () => {
    if (!props.grupo) return;
    
    isSaving.value = true;
    errorMessage.value = '';
    successMessage.value = '';

    try {
        const studentIds = enrolledStudents.value.map(s => s.id);
        await grupoService.actualizarEstudiantesGrupo(props.grupo.id, studentIds);
        successMessage.value = '¡Asignación de estudiantes guardada exitosamente!';
        
        // Espera un momento con el mensaje de éxito antes de cerrar
        setTimeout(() => {
            closeModal();
        }, 1200);
    } catch (err: any) {
        console.error('Error al guardar estudiantes del grupo:', err);
        errorMessage.value = err.response?.data?.message || 'No se pudo guardar la asignación de estudiantes.';
    } finally {
        isSaving.value = false;
    }
};

const closeModal = () => {
    if (!isSaving.value) {
        emit('update:modelValue', false);
    }
};
</script>

<template>
    <Transition name="modal">
        <div v-if="modelValue"
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm">
            <div
                class="bg-white rounded-xl shadow-xl border border-gray-100 max-w-4xl w-full overflow-hidden transform transition-all scale-100 flex flex-col max-h-[90vh]">

                <!-- Encabezado del Modal -->
                <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between bg-white shrink-0">
                    <div>
                        <h2 class="text-lg font-bold text-gray-900 flex items-center gap-2">
                            <GraduationCap class="w-6 h-6 text-purple-600" />
                            <span>Gestionar Estudiantes</span>
                        </h2>
                        <p class="text-xs text-gray-500 mt-0.5" v-if="grupo">
                            Grupo: <span class="font-bold text-gray-700">{{ grupo.nombre }}</span> | 
                            Materia: <span class="font-bold text-gray-700">{{ grupo.nombre_materia }}</span> | 
                            Gestión: <span class="font-bold text-gray-700">{{ grupo.gestion }}</span>
                        </p>
                    </div>
                    <button @click="closeModal" :disabled="isSaving"
                        class="text-gray-400 hover:text-gray-600 p-1.5 hover:bg-gray-50 rounded-lg transition-colors disabled:opacity-50">
                        <X class="w-4 h-4" />
                    </button>
                </div>

                <!-- Spinner de carga inicial para listar estudiantes -->
                <div v-if="isLoading" class="flex-1 flex flex-col items-center justify-center py-20 bg-gray-50/50">
                    <Loader2 class="w-8 h-8 text-purple-600 animate-spin" />
                    <span class="text-sm font-medium text-gray-600 mt-3">Cargando nómina de estudiantes...</span>
                </div>

                <!-- Contenido Principal -->
                <div v-else class="flex-1 overflow-y-auto flex flex-col">
                    
                    <!-- Alerta de cupo excedido -->
                    <div v-if="grupo && (grupo.cupo_maximo ?? 0) > 0 && enrolledStudents.length > (grupo.cupo_maximo ?? 0)"
                        class="mx-6 mt-4 p-3 bg-amber-50 border border-amber-200 text-amber-800 rounded-lg text-xs flex items-start gap-2">
                        <AlertCircle class="w-4 h-4 shrink-0 mt-0.5" />
                        <div>
                            <span class="font-bold">Advertencia de Límite de Cupo:</span> 
                            Se ha superado el cupo máximo para este grupo académico ({{ enrolledStudents.length }} estudiantes en la lista de {{ grupo?.cupo_maximo }} permitidos).
                        </div>
                    </div>

                    <!-- Mensajes de Respuesta de Servidor -->
                    <div class="px-6 mt-4 space-y-2">
                        <div v-if="successMessage" class="p-3 bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-lg text-xs flex items-center gap-2">
                            <CheckCircle2 class="w-4 h-4 text-emerald-600 shrink-0" />
                            <span class="font-medium">{{ successMessage }}</span>
                        </div>
                        <div v-if="errorMessage" class="p-3 bg-red-50 border border-red-200 text-red-800 rounded-lg text-xs flex items-center gap-2">
                            <AlertCircle class="w-4 h-4 text-red-600 shrink-0" />
                            <span class="font-medium">{{ errorMessage }}</span>
                        </div>
                    </div>

                    <!-- Columnas de Selección -->
                    <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-6 flex-1">
                        
                        <!-- Columna Izquierda: Alumnos Inscritos -->
                        <div class="border border-gray-200 rounded-xl overflow-hidden bg-gray-50/20 flex flex-col h-[400px]">
                            <div class="bg-gray-50 px-4 py-3 border-b border-gray-200 flex items-center justify-between shrink-0">
                                <span class="text-xs font-semibold text-gray-700 uppercase tracking-wider flex items-center gap-1.5">
                                    <Users class="w-4 h-4 text-gray-500" />
                                    Estudiantes Inscritos
                                </span>
                                <span class="bg-purple-100 text-purple-800 text-xs font-bold px-2 py-0.5 rounded-full shrink-0">
                                    {{ enrolledStudents.length }} {{ (grupo?.cupo_maximo ?? 0) > 0 ? `/ ${grupo?.cupo_maximo}` : '' }}
                                </span>
                            </div>

                            <div class="flex-1 overflow-y-auto p-3 space-y-2 pr-1">
                                <div v-if="enrolledStudents.length === 0"
                                    class="h-full flex flex-col items-center justify-center text-center p-6 text-gray-400">
                                    <Users class="w-10 h-10 text-gray-300 mb-2 mx-auto" />
                                    <p class="text-sm font-medium">No hay estudiantes en este grupo</p>
                                    <p class="text-xs text-gray-400 mt-1">Busca a la derecha para inscribir nuevos estudiantes.</p>
                                </div>
                                <div v-else v-for="student in enrolledStudents" :key="student.id"
                                    class="flex items-center justify-between p-2.5 bg-white border border-gray-200 rounded-lg shadow-xs hover:border-gray-300 transition-all">
                                    <div class="flex items-center gap-3 min-w-0">
                                        <div class="w-9 h-9 rounded-full bg-purple-50 border border-purple-100 text-purple-600 font-bold flex items-center justify-center text-sm shrink-0">
                                            {{ getInitials(student.name) }}
                                        </div>
                                        <div class="min-w-0">
                                            <p class="text-sm font-medium text-gray-900 truncate">{{ student.name }}</p>
                                            <p class="text-xs text-gray-500 font-mono truncate">{{ student.email }}</p>
                                        </div>
                                    </div>
                                    <button @click="removeStudent(student.id)" :disabled="isSaving"
                                        class="text-red-500 hover:text-red-700 hover:bg-red-50 p-1.5 rounded-lg transition-colors shrink-0 disabled:opacity-50"
                                        title="Retirar del grupo">
                                        <UserMinus class="w-4 h-4" />
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Columna Derecha: Buscador e Incorporación -->
                        <div class="border border-gray-200 rounded-xl overflow-hidden bg-gray-50/20 flex flex-col h-[400px]">
                            <!-- Input Buscador -->
                            <div class="p-3 bg-gray-50 border-b border-gray-200 shrink-0">
                                <div class="relative">
                                    <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" />
                                    <input v-model="searchQuery" type="text" placeholder="Buscar estudiante por nombre..."
                                        :disabled="isSaving"
                                        class="w-full pl-9 pr-9 py-1.5 bg-white border border-gray-200 text-gray-900 placeholder:text-gray-400 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500 text-sm transition-all" />
                                    <Loader2 v-if="isSearching" class="absolute right-3 top-1/2 -translate-y-1/2 w-4 h-4 text-purple-600 animate-spin" />
                                </div>
                            </div>

                            <!-- Resultados -->
                            <div class="flex-1 overflow-y-auto p-3 space-y-2 pr-1">
                                <div v-if="!searchQuery.trim()"
                                    class="h-full flex flex-col items-center justify-center text-center p-6 text-gray-400">
                                    <Search class="w-10 h-10 text-gray-300 mb-2 mx-auto" />
                                    <p class="text-sm font-medium">Búsqueda de Alumnos</p>
                                    <p class="text-xs text-gray-400 mt-1">Escribe el nombre de un alumno para buscarlo en la base de datos de Nexus Lab.</p>
                                </div>
                                <div v-else-if="searchResults.length === 0 && !isSearching"
                                    class="h-full flex flex-col items-center justify-center text-center p-6 text-gray-400">
                                    <AlertCircle class="w-10 h-10 text-gray-300 mb-2 mx-auto" />
                                    <p class="text-sm font-medium">Sin resultados</p>
                                    <p class="text-xs text-gray-400 mt-1">No se encontraron estudiantes para la búsqueda "{{ searchQuery }}".</p>
                                </div>
                                <div v-else v-for="student in searchResults" :key="student.id"
                                    class="flex items-center justify-between p-2.5 bg-white border border-gray-200 rounded-lg shadow-xs hover:border-gray-300 transition-all">
                                    <div class="flex items-center gap-3 min-w-0">
                                        <div class="w-9 h-9 rounded-full bg-blue-50 border border-blue-100 text-blue-600 font-bold flex items-center justify-center text-sm shrink-0">
                                            {{ getInitials(student.name) }}
                                        </div>
                                        <div class="min-w-0">
                                            <p class="text-sm font-medium text-gray-900 truncate">{{ student.name }}</p>
                                            <p class="text-xs text-gray-500 font-mono truncate">{{ student.email }}</p>
                                        </div>
                                    </div>
                                    
                                    <button v-if="isEnrolled(student.id)" disabled
                                        class="inline-flex items-center gap-1 text-xs font-semibold text-gray-400 bg-gray-50 border border-gray-200 px-2 py-1 rounded-lg shrink-0">
                                        <CheckCircle2 class="w-3.5 h-3.5 text-green-500" />
                                        Inscrito
                                    </button>
                                    <button v-else @click="addStudent(student)" :disabled="isSaving || isCupoMaximoAlcanzado"
                                        class="inline-flex items-center gap-1 text-xs font-semibold text-purple-600 hover:text-purple-700 bg-purple-50 hover:bg-purple-100 border border-purple-150 px-2.5 py-1 rounded-lg transition-colors shrink-0 disabled:opacity-50 disabled:hover:bg-purple-50 disabled:hover:text-purple-600"
                                        :class="{ 'opacity-40 cursor-not-allowed': isCupoMaximoAlcanzado }"
                                        :title="isCupoMaximoAlcanzado ? 'Cupo máximo alcanzado' : 'Inscribir estudiante'">
                                        <UserPlus class="w-3.5 h-3.5" />
                                        Inscribir
                                    </button>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>

                <!-- Footer del Modal -->
                <div class="px-6 py-4 bg-gray-50 border-t border-gray-100 flex justify-end gap-3 shrink-0">
                    <button type="button" @click="closeModal" :disabled="isSaving"
                        class="px-4 py-2 border border-gray-200 text-gray-700 hover:bg-gray-50 font-medium rounded-lg text-sm transition-colors disabled:opacity-50">
                        Cancelar
                    </button>
                    <button type="button" @click="handleSave" :disabled="isSaving || isLoading"
                        class="inline-flex items-center justify-center bg-purple-600 hover:bg-purple-700 text-white font-medium py-2 px-4 rounded-lg shadow-sm gap-2 transition-all text-sm disabled:opacity-70 min-w-32">
                        <Loader2 v-if="isSaving" class="w-4 h-4 animate-spin" />
                        <span>{{ isSaving ? 'Guardando...' : 'Guardar Cambios' }}</span>
                    </button>
                </div>

            </div>
        </div>
    </Transition>
</template>

<style scoped>
.modal-enter-active,
.modal-leave-active {
    transition: opacity 0.25s ease;
}

.modal-enter-from,
.modal-leave-to {
    opacity: 0;
}
</style>
