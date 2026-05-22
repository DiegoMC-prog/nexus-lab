import type { PaginatedResponse } from "./api";

export interface Materia {
    id: number;
    codigo: string;
    nombre: string;
    creditos: number | string;
    semestre_academico_id: number;
    nombre_semestre?: string;
    carrera_id: number;
    nombre_carrera?: string;
}

export interface MateriaFormData {
    carrera_id: number | null;
    semestre_academico_id: number | null;
    codigo: string;
    nombre: string;
    creditos: number | null;
}

export interface GetMateriasResponse {
    materias: PaginatedResponse<Materia>;
}

export interface MateriaResponse {
    message: string;
    materia: Materia;
}

export interface MateriaFormDataResponse {
    semestres: { id: number; nombre: string; }[];
    carreras: { id: number; nombre: string; }[];
}

export interface MessageResponse {
    message: string;
}
