import type { PaginatedResponse } from "./api";

export interface Carrera {
    id: number;
    nombre: string;
    codigo: string;
}

export interface CarreraFormData {
    nombre: string;
    codigo: string;
}

export interface GetCarrerasResponse {
    carreras: PaginatedResponse<Carrera>;
}

export interface CarreraResponse {
    message: string,
    carrera: Carrera,
}

export interface MessageResponse {
    message: string,
}