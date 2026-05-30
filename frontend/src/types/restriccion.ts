import type { PaginatedResponse } from "./api";

export interface Restriccion {
    id: number;
    laboratorio_id: number | null;
    nombre_aplicacion: string;
    nombre_proceso: string;
    tipo_restriccion: string;
    activo: boolean;
    created_at?: string;
    updated_at?: string;
    laboratorio?: {
        id: number;
        nombre: string;
    } | null;
}

export interface RestriccionFormData {
    laboratorio_id: number | null;
    nombre_aplicacion: string;
    nombre_proceso: string;
    tipo_restriccion: string;
    activo: boolean;
}

export interface LogAplicacionProhibida {
    id: number;
    estacion_id: number;
    usuario_id: number | null;
    nombre_proceso: string;
    ruta_ejecutable: string | null;
    accion_tomada: string;
    created_at: string;
    estacion?: {
        id: number;
        hostname: string;
    } | null;
    usuario?: {
        id: number;
        name: string;
    } | null;
}

export interface GetRestriccionesResponse {
    restricciones: PaginatedResponse<Restriccion>;
}

export interface GetLogsResponse {
    logs: PaginatedResponse<LogAplicacionProhibida>;
}

export interface RestriccionResponse {
    message: string;
    restriccion: Restriccion;
}
