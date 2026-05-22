export interface Horario {
    id: number;
    laboratorio_id: number;
    nombre_laboratorio: string;
    docente_id: number;
    nombre_docente: string;
    grupo_id?: number;
    dia_semana: number; // 1-7
    dia_sema: number;   // Backend fallback alias
    hora_inicio: string;
    hora_fin: string;
    fecha_inicio: string;
    fecha_fin: string;
}

export interface HorarioFormData {
    laboratorio_id: number | null;
    docente_id: number | null;
    grupo_id: number | null;
    dia_semana: number;
    hora_inicio: string;
    hora_fin: string;
    fecha_inicio: string;
    fecha_fin: string;
}

export interface GetHorariosResponse {
    horarios: {
        current_page: number;
        data: Horario[];
        first_page_url: string;
        from: number;
        last_page: number;
        last_page_url: string;
        links: any[];
        next_page_url: string | null;
        path: string;
        per_page: number;
        prev_page_url: string | null;
        to: number;
        total: number;
    };
}

export interface HorarioFormDataResponse {
    laboratorios: { id: number; nombre: string; }[];
    docentes: { id: number; nombre: string; }[];
    grupos: { id: number; nombre: string; }[];
}

export interface HorarioResponse {
    message: string;
    horario: Horario;
}

export interface MessageResponse {
    message: string;
}
