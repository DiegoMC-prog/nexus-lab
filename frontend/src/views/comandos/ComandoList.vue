<script setup lang="ts">
import { ref, onMounted, watch } from 'vue';
import { Search, Terminal, Loader2, ShieldCheck, Key } from '@lucide/vue';
import { comandoService } from '@/services/comandoService';

// Componentes del Sistema
import BasePagination from '@/components/BasePagination.vue';

// --- ESTADOS REACTIVOS ---
const comandos = ref<any[]>([]);
const isLoading = ref(true);

// Filtros
const searchTerm = ref('');

// Paginación
const currentPage = ref(1);
const lastPage = ref(1);
const totalComandos = ref(0);

// --- ACCIONES DE DATOS ---
const fetchComandos = async () => {
    isLoading.value = true;
    try {
        const response = await comandoService.getComandos({
            page: currentPage.value,
            search: searchTerm.value || undefined
        });
        comandos.value = response.data || [];
        currentPage.value = response.current_page || 1;
        lastPage.value = response.last_page || 1;
        totalComandos.value = response.total || 0;
    } catch (error) {
        console.error('Error al cargar comandos:', error);
    } finally {
        isLoading.value = false;
    }
};

let debounceTimeout: ReturnType<typeof setTimeout>;
watch(searchTerm, () => {
    clearTimeout(debounceTimeout);
    debounceTimeout = setTimeout(() => {
        currentPage.value = 1;
        fetchComandos();
    }, 450);
});

watch(currentPage, () => {
    fetchComandos();
});

onMounted(() => {
    fetchComandos();
});
</script>

<template>
    <div class="p-6 space-y-6 relative">
        <!-- Loader Overlay -->
        <div v-if="isLoading"
            class="absolute inset-0 bg-white/40 backdrop-blur-xs z-50 flex items-center justify-center min-h-100 rounded-xl transition-all">
            <div class="bg-white p-4 rounded-xl shadow-lg border border-gray-100 flex items-center gap-3">
                <Loader2 class="w-5 h-5 text-blue-600 animate-spin" />
                <span class="text-sm font-medium text-gray-700">Analizando plantillas de comando...</span>
            </div>
        </div>

        <!-- Encabezado -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 flex items-center gap-2">
                    <Terminal class="w-7 h-7 text-blue-600" />
                    Plantillas de Comandos
                </h1>
                <p class="text-sm text-gray-500 mt-1">
                    {{ totalComandos }} comandos del sistema preconfigurados para su ejecución remota en estaciones de trabajo
                </p>
            </div>
        </div>

        <!-- Filtros -->
        <div class="bg-white p-4 rounded-xl border border-gray-200 shadow-sm">
            <div class="relative max-w-md">
                <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" />
                <input v-model="searchTerm" type="text" placeholder="Buscar por nombre, slug o tipo..."
                    class="w-full pl-10 pr-4 py-2 bg-white border border-gray-200 text-gray-900 placeholder:text-gray-400 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm transition-all" />
            </div>
        </div>

        <!-- Tabla -->
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-gray-50 border-b border-gray-200 text-xs font-semibold text-gray-500 uppercase tracking-wider">
                            <th class="px-6 py-4">Nombre del Comando</th>
                            <th class="px-6 py-4">Slug de Ejecución</th>
                            <th class="px-6 py-4">Categoría / Tipo</th>
                            <th class="px-6 py-4">Autenticación Requerida</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 text-sm text-gray-700">
                        <tr v-if="comandos.length === 0 && !isLoading">
                            <td colspan="4" class="px-6 py-12 text-center text-gray-400 italic">
                                No se encontraron plantillas de comandos configuradas.
                            </td>
                        </tr>
                        
                        <tr v-for="cmd in comandos" :key="cmd.id" class="hover:bg-gray-50/70 transition-colors">
                            <td class="px-6 py-4 font-semibold text-gray-900">
                                {{ cmd.nombre }}
                            </td>
                            
                            <td class="px-6 py-4 font-mono text-xs">
                                <span class="bg-slate-100 border border-slate-200 px-2 py-1 rounded text-slate-700 font-bold select-all">
                                    {{ cmd.slug }}
                                </span>
                            </td>

                            <td class="px-6 py-4 text-xs font-medium uppercase">
                                <span :class="[
                                    'px-2 py-0.5 rounded border shadow-3xs',
                                    cmd.tipo === 'sistema' ? 'bg-blue-50 text-blue-700 border-blue-200' :
                                    cmd.tipo === 'energia' ? 'bg-amber-50 text-amber-700 border-amber-200' :
                                    cmd.tipo === 'kiosko' ? 'bg-indigo-50 text-indigo-700 border-indigo-200' :
                                    'bg-slate-50 text-slate-700 border-slate-200'
                                ]">
                                    {{ cmd.tipo }}
                                </span>
                            </td>

                            <td class="px-6 py-4 text-xs">
                                <div class="flex items-center gap-1.5 font-medium"
                                    :class="cmd.require_auth ? 'text-red-600' : 'text-emerald-600'">
                                    <component :is="cmd.require_auth ? Key : ShieldCheck" class="w-4 h-4 shrink-0" />
                                    <span>{{ cmd.require_auth ? 'Contraseña Docente/Admin' : 'Libre Ejecución' }}</span>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Paginación -->
            <div class="px-6 py-4 border-t border-gray-200 bg-gray-50/50">
                <BasePagination v-model="currentPage" :last-page="lastPage" :total="totalComandos" />
            </div>
        </div>
    </div>
</template>
