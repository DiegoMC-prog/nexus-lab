<script setup lang="ts">
import { ref, onMounted, watch } from 'vue';
import { 
    Search, Plus, Edit, Trash2, Sliders, Loader2, 
    BellRing, CheckCircle, XCircle, ShieldAlert 
} from '@lucide/vue';
import { configAlertaService, type ConfigAlertaFormData } from '@/services/configAlertaService';
import { getLaravelValidationErrors } from '@/utils/errorHandler';

// Componentes del Sistema
import BasePagination from '@/components/BasePagination.vue';
import ConfigAlertaModal from './ConfigAlertaModal.vue';
import ConfigAlertaDeleteModal from './ConfigAlertaDeleteModal.vue';
import { useToast } from '@/composables/useToast';

const toast = useToast();

// --- ESTADOS REACTIVOS ---
const configs = ref<any[]>([]);
const isLoading = ref(true);
const isSaving = ref(false);
const validationErrors = ref<Record<string, string[]> | undefined>(undefined);

// Filtros
const searchTerm = ref('');
const filterActivo = ref('all');

// Paginación
const currentPage = ref(1);
const lastPage = ref(1);
const totalConfigs = ref(0);

// Modales
const isFormModalOpen = ref(false);
const isDeleteModalOpen = ref(false);
const selectedConfig = ref<any | null>(null);

// --- ACCIONES DE DATOS ---
const fetchConfigs = async () => {
    isLoading.value = true;
    try {
        const res = await configAlertaService.getConfigAlertas({
            page: currentPage.value,
            search: searchTerm.value || undefined,
            activo: filterActivo.value === 'all' ? undefined : filterActivo.value
        });
        if (res) {
            configs.value = res.data || [];
            currentPage.value = res.current_page || 1;
            lastPage.value = res.last_page || 1;
            totalConfigs.value = res.total || 0;
        } else {
            configs.value = [];
        }
    } catch (error) {
        console.error('Error al cargar configuraciones de alertas:', error);
    } finally {
        isLoading.value = false;
    }
};

let debounceTimeout: ReturnType<typeof setTimeout>;
watch(searchTerm, () => {
    clearTimeout(debounceTimeout);
    debounceTimeout = setTimeout(() => {
        currentPage.value = 1;
        fetchConfigs();
    }, 450);
});

watch(filterActivo, () => {
    currentPage.value = 1;
    fetchConfigs();
});

watch(currentPage, () => {
    fetchConfigs();
});

onMounted(() => {
    fetchConfigs();
});

// Modales triggers
const openCreateModal = () => {
    selectedConfig.value = null;
    validationErrors.value = undefined;
    isFormModalOpen.value = true;
};

const openEditModal = (config: any) => {
    selectedConfig.value = config;
    validationErrors.value = undefined;
    isFormModalOpen.value = true;
};

const openDeleteModal = (config: any) => {
    selectedConfig.value = config;
    isDeleteModalOpen.value = true;
};

const handleSaveConfig = async (formData: ConfigAlertaFormData) => {
    isSaving.value = true;
    validationErrors.value = undefined;
    try {
        if (selectedConfig.value) {
            await configAlertaService.actualizarConfigAlerta(selectedConfig.value.id, formData);
            toast.success('Regla actualizada', 'La regla de monitoreo se ha actualizado correctamente.');
        } else {
            await configAlertaService.crearConfigAlerta(formData);
            toast.success('Regla creada', 'La regla de monitoreo se ha creado correctamente.');
        }
        isFormModalOpen.value = false;
        fetchConfigs();
    } catch (error: any) {
        console.error('Error al guardar configuración de alerta:', error);
        if (error.response && error.response.status === 422) {
            validationErrors.value = getLaravelValidationErrors(error);
            toast.warning('Errores de validación', 'Por favor revisa los campos del formulario.');
        } else {
            toast.error('Error', 'No se pudo guardar la regla de monitoreo.');
        }
    } finally {
        isSaving.value = false;
    }
};

const handleConfirmDelete = async () => {
    if (!selectedConfig.value) return;
    isSaving.value = true;
    try {
        await configAlertaService.eliminarConfigAlerta(selectedConfig.value.id);
        isDeleteModalOpen.value = false;
        toast.success('Regla eliminada', 'La regla de monitoreo ha sido eliminada del sistema.');
        if (configs.value.length === 1 && currentPage.value > 1) {
            currentPage.value--;
        } else {
            fetchConfigs();
        }
    } catch (error) {
        console.error('Error al eliminar configuración de alerta:', error);
        toast.error('Error', 'Hubo un problema al intentar eliminar la regla.');
    } finally {
        isSaving.value = false;
        selectedConfig.value = null;
    }
};

const getFriendlyMetricName = (metrica: string) => {
    if (!metrica) return 'Métrica Desconocida';
    switch (metrica) {
        case 'carga_cpu': return 'Carga de CPU';
        case 'uso_ram_mb': return 'Uso de RAM';
        case 'temp_cpu': return 'Temperatura CPU';
        case 'uso_disco': return 'Uso de Disco';
        case 'latencia_red': return 'Latencia de Red / Ping';
        default: return metrica;
    }
};

const formatThreshold = (metrica: string, value: any) => {
    if (value === undefined || value === null) return 'N/A';
    switch (metrica) {
        case 'carga_cpu': return `${value}%`;
        case 'uso_ram_mb': return Number(value) >= 1024 ? `${(Number(value)/1024).toFixed(1)} GB` : `${value} MB`;
        case 'temp_cpu': return `${value}°C`;
        case 'uso_disco': return `${value}%`;
        case 'latencia_red': return `${value} ms`;
        default: return String(value);
    }
};
</script>

<template>
    <div class="p-6 space-y-6 relative">
        <!-- Loader Overlay -->
        <div v-if="isLoading"
            class="absolute inset-0 bg-white/40 backdrop-blur-xs z-50 flex items-center justify-center min-h-100 rounded-xl transition-all">
            <div class="bg-white p-4 rounded-xl shadow-lg border border-gray-100 flex items-center gap-3">
                <Loader2 class="w-5 h-5 text-blue-600 animate-spin" />
                <span class="text-sm font-medium text-gray-700">Actualizando reglas de monitoreo...</span>
            </div>
        </div>

        <!-- Encabezado -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 flex items-center gap-2">
                    <Sliders class="w-7 h-7 text-blue-600" />
                    Reglas de Alertas
                </h1>
                <p class="text-sm text-gray-500 mt-1">
                    {{ totalConfigs }} reglas de monitoreo y umbrales de advertencia activos para evaluar la salud de la red
                </p>
            </div>
            
            <button @click="openCreateModal"
                class="inline-flex items-center justify-center bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-lg shadow-sm gap-2 transition-colors text-sm cursor-pointer">
                <Plus class="w-4 h-4" />
                Nueva Regla
            </button>
        </div>

        <!-- Filtros -->
        <div class="bg-white p-4 rounded-xl border border-gray-200 shadow-sm">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="relative md:col-span-2">
                    <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" />
                    <input v-model="searchTerm" type="text" placeholder="Buscar por métrica o severidad..."
                        class="w-full pl-10 pr-4 py-2 bg-white border border-gray-200 text-gray-900 placeholder:text-gray-400 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm transition-all" />
                </div>
                
                <select v-model="filterActivo"
                    class="bg-white border border-gray-200 text-gray-900 px-3 py-2 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm cursor-pointer">
                    <option value="all">Todos los estados</option>
                    <option value="true">Monitoreo Activo</option>
                    <option value="false">Desactivadas</option>
                </select>
            </div>
        </div>

        <!-- Tabla -->
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-gray-50 border-b border-gray-200 text-xs font-semibold text-gray-500 uppercase tracking-wider">
                            <th class="px-6 py-4">Métrica Evaluada</th>
                            <th class="px-6 py-4">Fórmula / Regla</th>
                            <th class="px-6 py-4">Severidad Alarma</th>
                            <th class="px-6 py-4">Monitoreo</th>
                            <th class="px-6 py-4 text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 text-sm text-gray-700">
                        <tr v-if="configs.length === 0 && !isLoading">
                            <td colspan="5" class="px-6 py-12 text-center text-gray-400 italic">
                                No se encontraron reglas de alertas configuradas.
                            </td>
                        </tr>
                        
                        <tr v-for="cfg in configs" :key="cfg.id" class="hover:bg-gray-50/70 transition-colors">
                            <!-- Metrica -->
                            <td class="px-6 py-4 font-semibold text-gray-900">
                                {{ getFriendlyMetricName(cfg.metrica) }}
                            </td>
                            
                            <!-- Regla Formula -->
                            <td class="px-6 py-4 font-mono text-xs">
                                <span class="bg-slate-100 border border-slate-200 px-2 py-1 rounded text-slate-700 font-bold select-all">
                                    {{ cfg.metrica }} {{ cfg.operador }} {{ formatThreshold(cfg.metrica, cfg.valor_umbral) }}
                                </span>
                            </td>

                            <!-- Severidad -->
                            <td class="px-6 py-4 text-xs font-semibold uppercase tracking-wider">
                                <span :class="[
                                    'inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full border',
                                    cfg.severidad === 'critica' ? 'bg-red-50 text-red-700 border-red-200' :
                                    cfg.severidad === 'alta' ? 'bg-orange-50 text-orange-700 border-orange-200' :
                                    cfg.severidad === 'media' ? 'bg-amber-50 text-amber-700 border-amber-200' :
                                    'bg-blue-50 text-blue-700 border-blue-200'
                                ]">
                                    <ShieldAlert v-if="cfg.severidad === 'critica' || cfg.severidad === 'alta'" class="w-3 h-3" />
                                    {{ cfg.severidad }}
                                </span>
                            </td>

                            <!-- Activo -->
                            <td class="px-6 py-4 text-xs font-medium">
                                <div class="flex items-center gap-1.5"
                                    :class="cfg.activo ? 'text-emerald-600' : 'text-gray-400'">
                                    <component :is="cfg.activo ? CheckCircle : XCircle" class="w-4 h-4 shrink-0" />
                                    <span>{{ cfg.activo ? 'Activo' : 'Pausado' }}</span>
                                </div>
                            </td>

                            <!-- Acciones -->
                            <td class="px-6 py-4 text-right space-x-1">
                                <button @click="openEditModal(cfg)"
                                    class="inline-flex items-center justify-center p-2 text-blue-600 hover:text-blue-700 hover:bg-blue-50 rounded-lg transition-colors cursor-pointer"
                                    title="Editar Regla">
                                    <Edit class="w-4 h-4" />
                                </button>
                                <button @click="openDeleteModal(cfg)"
                                    class="inline-flex items-center justify-center p-2 text-red-600 hover:text-red-700 hover:bg-red-50 rounded-lg transition-colors cursor-pointer"
                                    title="Eliminar Regla">
                                    <Trash2 class="w-4 h-4" />
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Paginación -->
            <div class="px-6 py-4 border-t border-gray-200 bg-gray-50/50">
                <BasePagination v-model="currentPage" :last-page="lastPage" :total="totalConfigs" />
            </div>
        </div>

        <!-- Modales CRUD -->
        <ConfigAlertaModal :show="isFormModalOpen" :config="selectedConfig" :loading="isSaving"
            :validation-errors="validationErrors" @close="isFormModalOpen = false" @submit="handleSaveConfig" />

        <ConfigAlertaDeleteModal :show="isDeleteModalOpen" :config="selectedConfig" :is-deleting="isSaving"
            @close="isDeleteModalOpen = false" @confirm="handleConfirmDelete" />
    </div>
</template>
