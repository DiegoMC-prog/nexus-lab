import type { PaginatedResponse } from "@/types/api";
import api from "./api";
import type { GetSemestresResponse, Semestre, SemestreFormData, SemestreResponse, MessageResponse } from '@/types/semestre';

export const semestreService = {
    async getSemestres(params?: { search?: string; page?: string }): Promise<PaginatedResponse<Semestre>> {
        const response = await api.get<GetSemestresResponse>('/semestres', { params });
        return response.data.semestres_academicos;
    },

    async crearSemestre(payload: SemestreFormData): Promise<SemestreResponse> {
        const response = await api.post<SemestreResponse>('/semestres', payload);
        return response.data;
    },

    async actualizarSemestre(id: number | string, payload: SemestreFormData): Promise<SemestreResponse> {
        const response = await api.put<SemestreResponse>(`/semestres/${id}`, payload);
        return response.data;
    },

    async eliminarSemestre(id: number | string): Promise<MessageResponse> {
        const response = await api.delete<MessageResponse>(`/semestres/${id}`);
        return response.data;
    }
}
