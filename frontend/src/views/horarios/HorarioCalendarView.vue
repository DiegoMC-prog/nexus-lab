<script setup lang="ts">
import { computed } from 'vue';
import { Clock, Building, User, BookOpen, Edit, Trash2 } from '@lucide/vue';
import type { Horario } from '@/types/horario';

// Props recibidos del componente padre
const props = defineProps<{
    horarios: Horario[];
    isDocente: boolean;
}>();

// Eventos emitidos para edición y eliminación
const emit = defineEmits<{
    (e: 'edit', horario: Horario): void;
    (e: 'delete', horario: Horario): void;
}>();

// Agrupar horarios por día de la semana para la vista de Calendario Semanal
const horariosByDay = computed(() => {
    const grouped: Record<number, Horario[]> = { 1: [], 2: [], 3: [], 4: [], 5: [], 6: [], 7: [] };
    
    const sorted = [...props.horarios].sort((a, b) => {
        return (a.hora_inicio || '').localeCompare(b.hora_inicio || '');
    });
    
    sorted.forEach(h => {
        const dia = h.dia_semana || h.dia_sema;
        if (grouped[dia]) {
            grouped[dia].push(h);
        }
    });
    
    return grouped;
});

// Mapeador de nombres de días de la semana
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

// Formatear hora de HH:MM:SS a HH:MM
const formatTime = (timeStr?: string): string => {
    if (!timeStr) return '';
    return timeStr.length > 5 ? timeStr.slice(0, 5) : timeStr;
};
</script>

<template>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-7 gap-4">
        <div v-for="diaNum in [1, 2, 3, 4, 5, 6, 7]" :key="diaNum" 
            class="bg-white rounded-xl border border-gray-200 shadow-2xs flex flex-col min-h-120 overflow-hidden">
            
            <!-- Cabecera del Día con diseño formal y limpio -->
            <div class="px-4 py-3 bg-gray-50 border-b border-gray-200 flex items-center justify-between">
                <span class="text-xs font-extrabold text-gray-700 tracking-wide uppercase">
                    {{ getDiaLabel(diaNum) }}
                </span>
                <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-gray-200/80 text-gray-600">
                    {{ horariosByDay[diaNum]?.length || 0 }}
                </span>
            </div>

            <!-- Lista de Horarios Programados para este día -->
            <div class="p-3 flex-1 space-y-3 overflow-y-auto max-h-130 bg-gray-50/10">
                <div v-if="horariosByDay[diaNum]?.length === 0" 
                    class="h-full flex flex-col items-center justify-center py-10 text-center">
                    <Clock class="w-8 h-8 text-gray-300 stroke-1.5 mb-2" />
                    <span class="text-[11px] font-semibold text-gray-400">Sin clases</span>
                </div>
                
                <div v-else v-for="horario in horariosByDay[diaNum]" :key="horario.id"
                    class="p-3 bg-white rounded-lg border border-gray-200/80 text-slate-800 flex flex-col gap-2 transition-all duration-200 hover:scale-[1.02] hover:border-blue-300 hover:bg-slate-50/50 shadow-2xs hover:shadow-xs relative overflow-hidden group">
                    
                    <!-- Indicador de color lateral izquierdo - Azul elegante y formal para todos los laboratorios -->
                    <div class="absolute left-0 top-0 bottom-0 w-1 bg-blue-600 rounded-l-md"></div>
                    
                    <!-- Franja Horaria -->
                    <div class="flex items-center gap-1.5 font-mono text-[10px] font-bold text-slate-500 uppercase pl-1">
                        <Clock class="w-3.5 h-3.5 text-slate-400 shrink-0" />
                        <span>{{ formatTime(horario.hora_inicio) }} - {{ formatTime(horario.hora_fin) }}</span>
                    </div>

                    <!-- Nombre del Laboratorio -->
                    <div class="flex items-start gap-1.5 text-[11px] font-black text-slate-800 leading-tight pl-1">
                        <Building class="w-3.5 h-3.5 text-slate-500 shrink-0 mt-0.5" />
                        <span class="break-words">{{ horario.nombre_laboratorio }}</span>
                    </div>

                    <!-- Docente -->
                    <div class="flex items-center gap-1.5 text-[10px] text-slate-600 font-medium pl-1">
                        <User class="w-3 h-3 text-slate-400 shrink-0" />
                        <span class="truncate">{{ horario.nombre_docente }}</span>
                    </div>

                    <!-- Grupo Académico (si existe) -->
                    <div v-if="horario.grupo_id" class="flex items-center gap-1.5 text-[10px] text-slate-700 font-bold border-t border-slate-100 pt-1.5 mt-0.5 pl-1">
                        <BookOpen class="w-3 h-3 text-slate-400 shrink-0" />
                        <span class="truncate">Grupo: {{ horario.grupo_id }}</span>
                    </div>
                    
                    <!-- Botón de acciones rápidas al hacer hover (sólo para no-docentes) -->
                    <div v-if="!isDocente" class="absolute right-1.5 top-1.5 hidden group-hover:flex items-center bg-white/95 backdrop-blur-xs rounded-md shadow-sm border border-gray-200 p-0.5 transition-all">
                        <button @click.stop="emit('edit', horario)" class="p-1 hover:bg-gray-100 rounded text-blue-600 cursor-pointer" title="Editar">
                            <Edit class="w-3 h-3" />
                        </button>
                        <button @click.stop="emit('delete', horario)" class="p-1 hover:bg-red-50 rounded text-red-600 cursor-pointer" title="Eliminar">
                            <Trash2 class="w-3 h-3" />
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.backdrop-blur-xs {
    backdrop-filter: blur(2px);
}
</style>
