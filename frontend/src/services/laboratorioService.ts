import type { PaginatedResponse } from "@/types/api";
import api from "./api";
import type { GetLaboratoriosResponse, Laboratorio, LaboratorioFormData, LaboratorioResponse, MessageResponse } from "@/types/laboratorio";

export const laboratorioService = {
    async getLaboratorios(params?: { search?: string; activo?: string; page?: number }): Promise<PaginatedResponse<Laboratorio>> {
        const response = await api.get<GetLaboratoriosResponse>('/laboratorios', { params });
        return response.data.laboratorios;
    },

    async crearLaboratorio(payload: LaboratorioFormData): Promise<LaboratorioResponse> {
        const response = await api.post<LaboratorioResponse>('/laboratorios', payload);
        return response.data;
    },

    async actualizarLaboratorio(id: number | string, payload: LaboratorioFormData): Promise<LaboratorioResponse> {
        const response = await api.put<LaboratorioResponse>(`/laboratorios/${id}`, payload);
        return response.data;
    },

    async eliminarLaboratorio(id: number | string): Promise<MessageResponse> {
        const response = await api.delete<MessageResponse>(`/laboratorios/${id}`);
        return response.data;
    }
}