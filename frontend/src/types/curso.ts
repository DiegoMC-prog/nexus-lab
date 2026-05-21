import type { PaginatedResponse } from "./api";

export interface Curso {
    id: number;
    carrera_id: number;
    carrera: string;
    semestre_academico_id: number;
    semestre: string
}

export interface CursoFormData {
    carrera_id: number | string;
    semestre_academico_id: number | string;
}

export interface GetCursosResponse {
    cursos: PaginatedResponse<Curso>;
}

export interface CursoResponse {
    message: string;
    curso: Curso;
}

export interface MessageResponse {
    message: string;
}