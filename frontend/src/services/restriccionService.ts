import type { PaginatedResponse } from "@/types/api";
import api from "./api";
import type {
    GetRestriccionesResponse,
    GetLogsResponse,
    Restriccion,
    RestriccionFormData,
    RestriccionResponse,
    LogAplicacionProhibida
} from "@/types/restriccion";
import type { MessageResponse } from "@/types/laboratorio";

export const restriccionService = {
    async getRestricciones(params?: { search?: string; laboratorio_id?: string; page?: number }): Promise<PaginatedResponse<Restriccion>> {
        const response = await api.get<GetRestriccionesResponse>('/restricciones-aplicaciones', { params });
        return response.data.restricciones;
    },

    async crearRestriccion(payload: RestriccionFormData): Promise<RestriccionResponse> {
        const response = await api.post<RestriccionResponse>('/restricciones-aplicaciones', payload);
        return response.data;
    },

    async actualizarRestriccion(id: number | string, payload: RestriccionFormData): Promise<RestriccionResponse> {
        const response = await api.put<RestriccionResponse>(`/restricciones-aplicaciones/${id}`, payload);
        return response.data;
    },

    async eliminarRestriccion(id: number | string): Promise<MessageResponse> {
        const response = await api.delete<MessageResponse>(`/restricciones-aplicaciones/${id}`);
        return response.data;
    },

    async getLogs(params?: { search?: string; page?: number }): Promise<PaginatedResponse<LogAplicacionProhibida>> {
        const response = await api.get<GetLogsResponse>('/logs-aplicaciones-prohibidas', { params });
        return response.data.logs;
    }
};
