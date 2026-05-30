import type { PaginatedResponse } from "./api";

export interface Laboratorio {
    id: number;
    nombre: string;
    pabellon: string;
    piso: string;
    activo: boolean;
}

export interface LaboratorioFormData {
    nombre: string;
    pabellon: string;
    piso: string;
    activo: boolean
}

export interface GetLaboratoriosResponse {
    laboratorios: PaginatedResponse<Laboratorio>;
}

export interface LaboratorioResponse {
    message: string;
    laboratorio: Laboratorio;
}

export interface MessageResponse {
    message: string;
}

