import type { PaginatedResponse } from "@/types/api";
import api from "./api";
import type { GetMateriasResponse, Materia, MateriaFormData, MateriaResponse, MateriaFormDataResponse, MessageResponse } from '@/types/materia';

export const materiaService = {
    async getMaterias(params?: { search?: string; page?: string; semestre_academico_id?: string; carrera_id?: string }): Promise<PaginatedResponse<Materia>> {
        const response = await api.get<GetMateriasResponse>('/materias', { params });
        return response.data.materias;
    },

    async getFormData(): Promise<MateriaFormDataResponse> {
        const response = await api.get<MateriaFormDataResponse>('/materias/form-data');
        return response.data;
    },

    async crearMateria(payload: MateriaFormData): Promise<MateriaResponse> {
        const response = await api.post<MateriaResponse>('/materias', payload);
        return response.data;
    },

    async actualizarMateria(id: number | string, payload: MateriaFormData): Promise<MateriaResponse> {
        const response = await api.put<MateriaResponse>(`/materias/${id}`, payload);
        return response.data;
    },

    async eliminarMateria(id: number | string): Promise<MessageResponse> {
        const response = await api.delete<MessageResponse>(`/materias/${id}`);
        return response.data;
    }
}
