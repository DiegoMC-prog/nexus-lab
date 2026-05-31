import type { PaginatedResponse } from "./api";

export interface Semestre {
    id: number;
    nombre: string;
}

export interface SemestreFormData {
    nombre: string;
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
