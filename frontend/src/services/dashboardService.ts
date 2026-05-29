import api from "./api";

export interface EstacionesStats {
    total: number;
    activas: number;
    inactivas: number;
    mantenimiento: number;
    desconectadas: number;
}

export interface LaboratoriosStats {
    total: number;
    activos: number;
    inactivos: number;
    status_list: LaboratorioStatus[];
}

export interface LaboratorioStatus {
    id: number;
    nombre: string;
    pabellon: string;
    piso: string;
    activo: boolean;
    estaciones_count: number;
    estaciones_activas_count: number;
}

export interface SimpleStats {
    total: number;
}

export interface AlertaReciente {
    id: number;
    estacion_id: number | null;
    config_alerta_id: number;
    valor_actual: number;
    estado: string;
    created_at: string;
    estacion: {
        id: number;
        hostname: string;
        direccion_ip: string;
    } | null;
    configuracion_alerta: {
        metrica: string;
        operador: string;
        valor_umbral: number;
        severidad: string;
    } | null;
}

export interface AlertasStats {
    totales: number;
    pendientes: number;
    resueltas: number;
    recientes: AlertaReciente[];
}

export interface HorarioHoy {
    id: number;
    dia_semana: number;
    hora_inicio: string;
    hora_fin: string;
    laboratorio: {
        nombre: string;
        pabellon: string;
    } | null;
    docente: {
        name: string;
        email: string;
    } | null;
    grupo: {
        nombre: string;
        materia: {
            nombre: string;
            codigo: string;
        } | null;
    } | null;
}

export interface DocenteKpis {
    mis_materias_count: number;
    mis_grupos_count: number;
    clases_hoy_count: number;
    laboratorios_asignados_count: number;
}

export interface EstudianteKpis {
    mis_materias_count: number;
    mis_grupos_count: number;
    clases_hoy_count: number;
    laboratorios_activos_count: number;
}

export interface DashboardStatsResponse {
    estaciones: EstacionesStats;
    laboratorios: LaboratoriosStats;
    materias: SimpleStats;
    docentes: SimpleStats;
    estudiantes: SimpleStats;
    alertas: AlertasStats;
    horarios_hoy: HorarioHoy[];
    docente_kpis?: DocenteKpis;
    estudiante_kpis?: EstudianteKpis;
}

export const dashboardService = {
    async getStats(): Promise<DashboardStatsResponse> {
        const response = await api.get<DashboardStatsResponse>('/dashboard/stats');
        return response.data;
    }
};
