import api from "@/services/api";
import type { Curso, CursoFormData, CursoResponse, GetCursosResponse, MessageResponse } from "../types/curso";
import type { PaginatedResponse } from "@/types/api";
import { type GetSemestresResponse, type Semestre, type SemestreFormData, type SemestreResponse, type SemestreFormResponse } from "@/types/Semestre";




export const cursoService = {
    async getCursos(params?: { search?: string; carrera_id?: number | string; semestre_academico_id?: number | string; page?: number }): Promise<PaginatedResponse<Curso>> {
        const response = await api.get<GetCursosResponse>('/cursos', { params });
        // Retornamos el objeto completo de paginación que genera ->paginate(10)
        return response.data.cursos;
    },

    async crearCurso(payload: CursoFormData): Promise<CursoResponse> {
        const response = await api.post<CursoResponse>('/cursos', payload);
        return response.data;
    },

    async actualizarCurso(id: number | string, payload: CursoFormData): Promise<CursoResponse> {
        const response = await api.put<CursoResponse>(`/cursos/${id}`, payload);
        return response.data;
    },

    async eliminarCurso(id: number | string): Promise<MessageResponse> {
        const response = await api.delete<MessageResponse>(`/cursos/${id}`);
        return response.data;
    }
}

export const semestreService = {
    async getSemestres(params?: { search?: string; page?: number }): Promise<PaginatedResponse<Semestre>> {
        const response = await api.get<GetSemestresResponse>('/semestres');
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
    },

    async getFormData(): Promise<SemestreFormResponse> {
        const response = await api.get<SemestreFormResponse>('/semestres/form');
        return response.data;
    }
}