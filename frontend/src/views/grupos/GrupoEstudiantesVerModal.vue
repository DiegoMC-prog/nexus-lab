<script setup lang="ts">
import { ref, watch, computed } from 'vue';
import { X, Search, Users, Loader2, Mail, GraduationCap, AlertCircle } from '@lucide/vue';
import { grupoService } from '@/services/grupoService';
import type { Grupo, GrupoEstudiante } from '@/types/grupo';

const props = defineProps<{
    modelValue: boolean;
    grupo: Grupo | null;
}>();

const emit = defineEmits<{
    (e: 'update:modelValue', value: boolean): void;
}>();

// Estados reactivos
const isLoading = ref(false);
const students = ref<GrupoEstudiante[]>([]);
const searchQuery = ref('');
const errorMessage = ref('');

// Cargar la lista al abrir el modal
watch(() => props.modelValue, async (isOpen) => {
    if (isOpen) {
        searchQuery.value = '';
        errorMessage.value = '';
        students.value = [];

        if (props.grupo) {
            isLoading.value = true;
            try {
                const res = await grupoService.listarEstudiantes(props.grupo.id);
                students.value = res.estudiantes;
            } catch (err: any) {
                console.error('Error al obtener la nómina de estudiantes:', err);
                errorMessage.value = 'No se pudo cargar la lista de estudiantes inscritos. Inténtelo de nuevo más tarde.';
            } finally {
                isLoading.value = false;
            }
        }
    }
});

// Filtrar la nómina cargada en tiempo real
const filteredStudents = computed(() => {
    if (!searchQuery.value.trim()) return students.value;
    const query = searchQuery.value.toLowerCase().trim();
    return students.value.filter(student => 
        student.name.toLowerCase().includes(query) || 
        student.email.toLowerCase().includes(query)
    );
});

const closeModal = () => {
    emit('update:modelValue', false);
};

// Obtener iniciales para el avatar
const getInitials = (name: string): string => {
    if (!name) return 'U';
    const parts = name.trim().split(/\s+/);
    const first = parts[0]?.charAt(0) || '';
    const second = parts[1]?.charAt(0) || '';
    return first && second ? (first + second).toUpperCase() : name.trim().slice(0, 2).toUpperCase();
};

// Generar una clase de fondo suave y color de texto elegante según el nombre (consistente)
const getAvatarStyle = (name: string) => {
    let hash = 0;
    for (let i = 0; i < name.length; i++) {
        hash = name.charCodeAt(i) + ((hash << 5) - hash);
    }
    const colors = [
        { bg: 'bg-blue-50 border-blue-100 text-blue-700', ring: 'focus:ring-blue-400' },
        { bg: 'bg-emerald-50 border-emerald-100 text-emerald-700', ring: 'focus:ring-emerald-400' },
        { bg: 'bg-indigo-50 border-indigo-100 text-indigo-700', ring: 'focus:ring-indigo-400' },
        { bg: 'bg-purple-50 border-purple-100 text-purple-700', ring: 'focus:ring-purple-400' },
        { bg: 'bg-pink-50 border-pink-100 text-pink-700', ring: 'focus:ring-pink-400' },
        { bg: 'bg-rose-50 border-rose-100 text-rose-700', ring: 'focus:ring-rose-400' },
        { bg: 'bg-amber-50 border-amber-100 text-amber-700', ring: 'focus:ring-amber-400' },
        { bg: 'bg-teal-50 border-teal-100 text-teal-700', ring: 'focus:ring-teal-400' },
    ];
    const index = Math.abs(hash) % colors.length;
    return colors[index]!;
};
</script>

<template>
    <Transition name="modal">
        <div v-if="modelValue"
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-xs">
            <div
                class="bg-white rounded-2xl shadow-2xl border border-gray-100 max-w-2xl w-full overflow-hidden transform transition-all scale-100 flex flex-col max-h-[85vh]">

                <!-- Encabezado del Modal -->
                <div class="px-6 py-5 border-b border-gray-100 flex items-center justify-between bg-gradient-to-r from-gray-55/10 to-transparent shrink-0">
                    <div class="flex items-center gap-3">
                        <div class="p-2.5 bg-emerald-50 text-emerald-600 rounded-xl border border-emerald-100">
                            <Users class="w-6 h-6 animate-pulse" />
                        </div>
                        <div>
                            <h2 class="text-xl font-bold text-gray-900 tracking-tight flex items-center gap-2">
                                Estudiantes Inscritos
                            </h2>
                            <p class="text-xs text-gray-500 mt-1 font-medium" v-if="grupo">
                                <span class="bg-gray-100 text-gray-700 px-2 py-0.5 rounded-md font-bold">{{ grupo.nombre }}</span>
                                <span class="mx-1.5">•</span>
                                <span class="text-gray-600">{{ grupo.nombre_materia }}</span>
                                <span class="mx-1.5">•</span>
                                <span class="text-gray-600">Gestión {{ grupo.gestion }}</span>
                            </p>
                        </div>
                    </div>
                    <button @click="closeModal"
                        class="text-gray-400 hover:text-gray-600 p-2 hover:bg-gray-50 rounded-xl transition-all duration-200">
                        <X class="w-5 h-5" />
                    </button>
                </div>

                <!-- Barra de búsqueda e información -->
                <div class="p-6 pb-2 border-b border-gray-50 shrink-0 flex flex-col sm:flex-row gap-3 items-center justify-between bg-gray-50/30">
                    <div class="relative w-full sm:max-w-xs">
                        <Search class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" />
                        <input v-model="searchQuery" type="text" placeholder="Filtrar por nombre o correo..."
                            class="w-full pl-10 pr-8 py-2 bg-white border border-gray-200 text-gray-900 placeholder:text-gray-400 rounded-xl focus:outline-none focus:ring-2 focus:ring-emerald-500/30 focus:border-emerald-500 text-sm transition-all duration-200 shadow-xs" />
                        <button v-if="searchQuery" @click="searchQuery = ''"
                            class="absolute right-2.5 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 p-0.5 rounded-full hover:bg-gray-150 transition-colors">
                            <X class="w-3.5 h-3.5" />
                        </button>
                    </div>

                    <div class="flex items-center gap-2 text-xs font-semibold text-gray-500 shrink-0">
                        <GraduationCap class="w-4 h-4 text-emerald-500" />
                        <span>Total Inscritos:</span>
                        <span class="bg-emerald-50 text-emerald-700 border border-emerald-100 px-2 py-0.5 rounded-md font-mono font-bold text-sm">
                            {{ students.length }} {{ (grupo?.cupo_maximo ?? 0) > 0 ? `/ ${grupo?.cupo_maximo}` : '' }}
                        </span>
                    </div>
                </div>

                <!-- Contenido Principal / Listado -->
                <div class="flex-1 overflow-y-auto p-6 min-h-[250px] bg-gray-50/10">
                    <!-- Estado de carga -->
                    <div v-if="isLoading" class="h-full flex flex-col items-center justify-center py-16">
                        <Loader2 class="w-10 h-10 text-emerald-600 animate-spin" />
                        <span class="text-sm font-medium text-gray-600 mt-4 animate-pulse">Obteniendo nómina oficial...</span>
                    </div>

                    <!-- Mensaje de error -->
                    <div v-else-if="errorMessage" class="p-4 bg-red-50 border border-red-200 text-red-800 rounded-2xl text-sm flex items-start gap-3 shadow-xs">
                        <AlertCircle class="w-5 h-5 text-red-600 shrink-0 mt-0.5" />
                        <div>
                            <span class="font-bold">Error del sistema</span>
                            <p class="mt-1 text-xs text-red-700">{{ errorMessage }}</p>
                        </div>
                    </div>

                    <!-- Estado vacío: Sin alumnos inscritos en absoluto -->
                    <div v-else-if="students.length === 0"
                        class="h-full flex flex-col items-center justify-center text-center py-12">
                        <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mb-4 border border-gray-200 shadow-inner">
                            <Users class="w-8 h-8 text-gray-400" />
                        </div>
                        <p class="text-base font-semibold text-gray-800">No hay estudiantes inscritos</p>
                        <p class="text-xs text-gray-400 mt-1 max-w-sm">Este grupo aún no tiene alumnos vinculados a su nómina académica.</p>
                    </div>

                    <!-- Estado vacío: Búsqueda sin coincidencias -->
                    <div v-else-if="filteredStudents.length === 0"
                        class="h-full flex flex-col items-center justify-center text-center py-12">
                        <div class="w-16 h-16 bg-amber-50 rounded-full flex items-center justify-center mb-4 border border-amber-100 shadow-inner animate-bounce">
                            <Search class="w-8 h-8 text-amber-500" />
                        </div>
                        <p class="text-base font-semibold text-gray-800">Sin coincidencias</p>
                        <p class="text-xs text-gray-400 mt-1">No se encontraron estudiantes que coincidan con "{{ searchQuery }}".</p>
                    </div>

                    <!-- Listado de estudiantes -->
                    <div v-else class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div v-for="student in filteredStudents" :key="student.id"
                            class="flex items-center gap-3.5 p-3.5 bg-white border border-gray-150 rounded-2xl shadow-xs hover:shadow-md hover:border-emerald-200/50 hover:translate-y-[-1px] transition-all duration-200 group">
                            <div :class="['w-11 h-11 rounded-xl border font-extrabold flex items-center justify-center text-sm shrink-0 transition-transform group-hover:scale-105 duration-200', getAvatarStyle(student.name).bg]">
                                {{ getInitials(student.name) }}
                            </div>
                            <div class="min-w-0 flex-1">
                                <p class="text-sm font-semibold text-gray-900 truncate group-hover:text-emerald-700 transition-colors duration-200">{{ student.name }}</p>
                                <div class="flex items-center gap-1.5 mt-0.5 text-gray-500">
                                    <Mail class="w-3.5 h-3.5 shrink-0 text-gray-400" />
                                    <p class="text-xs font-mono truncate select-all">{{ student.email }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Footer del Modal -->
                <div class="px-6 py-4.5 bg-gray-50 border-t border-gray-100 flex justify-end shrink-0">
                    <button type="button" @click="closeModal"
                        class="px-5 py-2.5 bg-white border border-gray-200 text-gray-700 hover:bg-gray-100 hover:text-gray-900 font-bold rounded-xl text-sm transition-all duration-200 shadow-xs focus:ring-2 focus:ring-gray-250">
                        Cerrar Nómina
                    </button>
                </div>

            </div>
        </div>
    </Transition>
</template>

<style scoped>
.backdrop-blur-xs {
    backdrop-filter: blur(2px);
}
.modal-enter-active,
.modal-leave-active {
    transition: opacity 0.25s ease;
}
.modal-enter-from,
.modal-leave-to {
    opacity: 0;
}
</style>
