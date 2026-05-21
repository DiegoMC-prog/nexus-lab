import type { PaginatedResponse } from "./api";
import type { Carrera } from "./carrera";

export interface Semestre {
    id: number;
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

export interface CarreraFormData {
    id: number | string;
    nombre: string;
}

export interface SemestreFormData {
    id?: number | string;
    nombre: string;
}

export interface SemestreFormResponse {
    carreras: Carrera[];
    semestres: Semestre[];
}

