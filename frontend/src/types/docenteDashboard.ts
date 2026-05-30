export interface DocenteDashboardKpis {
    materias_asignadas: number;
    grupos_a_cargo: number;
    clases_hoy: number;
    laboratorios_reservados: number;
}

export interface CronogramaItem {
    id: number;
    hora_inicio: string;
    hora_fin: string;
    materia: {
        nombre: string;
        codigo: string;
    } | null;
    grupo: {
        nombre: string;
    } | null;
    laboratorio: {
        id: number;
        nombre: string;
        pabellon: string;
        piso: string;
    } | null;
}

export interface DocenteDashboardResponse {
    kpis: DocenteDashboardKpis;
    cronograma_hoy: CronogramaItem[];
}

export interface LiveEstacion {
    id: number;
    uuid: string;
    hostname: string;
    direccion_ip: string;
    so_info: string | null;
    estado: 'Online' | 'Offline' | 'bloqueado';
    estudiante: {
        name: string;
    } | null;
    telemetria: {
        carga_cpu: number;
        uso_ram_mb: number;
    } | null;
}

export interface LiveInfraccion {
    id: number;
    estacion: {
        hostname: string;
    } | null;
    usuario: {
        name: string;
    } | null;
    nombre_proceso: string;
    ruta_ejecutable: string | null;
    accion_tomada: string;
    created_at: string;
}

export interface LiveClaseInfo {
    grupo: {
        id: number;
        nombre: string;
        materia: {
            nombre: string;
            codigo: string;
        } | null;
    } | null;
    laboratorio: {
        id: number;
        nombre: string;
        pabellon: string;
        piso: string;
    };
    hora_inicio: string;
    hora_fin: string;
}

export interface ClaseActivaResponse {
    clase_activa: boolean;
    message?: string;
    grupo?: {
        id: number;
        nombre: string;
        materia: {
            nombre: string;
            codigo: string;
        } | null;
    } | null;
    laboratorio?: {
        id: number;
        nombre: string;
        pabellon: string;
        piso: string;
    };
    hora_inicio?: string;
    hora_fin?: string;
    estaciones?: LiveEstacion[];
    infracciones?: LiveInfraccion[];
}
