import type { PaginatedResponse } from "@/types/api";
import api from "./api";
import type { GetGruposResponse, Grupo, GrupoFormData, GrupoResponse, GrupoFormDataResponse, MessageResponse } from '@/types/grupo';

export const grupoService = {
    async getGrupos(params?: { search?: string; page?: string; materia_id?: string }): Promise<PaginatedResponse<Grupo>> {
        const response = await api.get<GetGruposResponse>('/grupos', { params });
        return response.data.grupos;
    },

    async getFormData(): Promise<GrupoFormDataResponse> {
        const response = await api.get<GrupoFormDataResponse>('/grupos/form-data');
        return response.data;
    },

    async crearGrupo(payload: GrupoFormData): Promise<GrupoResponse> {
        const response = await api.post<GrupoResponse>('/grupos', payload);
        return response.data;
    },

    async actualizarGrupo(id: number | string, payload: GrupoFormData): Promise<GrupoResponse> {
        const response = await api.put<GrupoResponse>(`/grupos/${id}`, payload);
        return response.data;
    },

    async eliminarGrupo(id: number | string): Promise<MessageResponse> {
        const response = await api.delete<MessageResponse>(`/grupos/${id}`);
        return response.data;
    }
}
