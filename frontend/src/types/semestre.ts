import type { PaginatedResponse } from "./api";

export interface Semestre {
    id: number;
    nombre: string;
    fecha_inicio: string;
    fecha_fin: string;
    estado: 'activo' | 'cerrado';
}

export interface SemestreFormData {
    nombre: string;
    fecha_inicio: string;
    fecha_fin: string;
}

export interface GetSemestresResponse {
    semestres_academicos: PaginatedResponse<Semestre>;
}

export interface SemestreResponse {
    message: string;
    semestre_academico: Semestre;
}

export interface MessageResponse {
    message: string;
}
