import api from "./api";

export interface LogsTelemetriaResponse {
    id: number;
    estacion_id: number;
    carga_cpu: number;
    uso_ram_mb: number;
    temp_cpu: number;
    uso_disco: number;
    latencia_red: number;
    created_at: string;
}

export const telemetriaService = {
    async getTelemetryLogs(params?: { search?: string; estacion_id?: number | string; page?: number }) {
        const response = await api.get('/logs-telemetria', { params });
        return response.data.logs;
    },

    async eliminarTelemetryLog(id: number | string) {
        const response = await api.delete(`/logs-telemetria/${id}`);
        return response.data;
    }
};
