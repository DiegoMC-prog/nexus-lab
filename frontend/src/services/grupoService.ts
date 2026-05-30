import type { PaginatedResponse } from "@/types/api";
import api from "./api";
import type { GetGruposResponse, Grupo, GrupoFormData, GrupoResponse, GrupoFormDataResponse, MessageResponse, ListarEstudiantesResponse, ActualizarEstudiantesGrupoResponse, SearchEstudianteResponse } from '@/types/grupo';

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
    },

    async listarEstudiantes(grupoId: number | string): Promise<ListarEstudiantesResponse> {
        const response = await api.get<ListarEstudiantesResponse>(`/grupos/${grupoId}/estudiantes`);
        return response.data;
    },

    async actualizarEstudiantesGrupo(grupoId: number | string, usersId: number[]): Promise<ActualizarEstudiantesGrupoResponse> {
        const response = await api.put<ActualizarEstudiantesGrupoResponse>(`/grupos/${grupoId}/estudiantes`, { users_id: usersId });
        return response.data;
    },

    async searchEstudiante(search: string): Promise<SearchEstudianteResponse> {
        const response = await api.get<SearchEstudianteResponse>('/grupos/estudiantes/search', { params: { search } });
        return response.data;
    }
}
