export interface Grupo {
    id: number;
    materia_id: number;
    nombre: string;
    nombre_grupo: string;
    nombre_materia: string;
    gestion: string;
    cupo_maximo: number;
}

export interface GrupoFormData {
    materia_id: number | null;
    nombre: string;
    gestion: string;
    cupo_maximo: number | null;
}

export interface GetGruposResponse {
    grupos: {
        current_page: number;
        data: Grupo[];
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

export interface GrupoFormDataResponse {
    materias: {
        id: number;
        nombre: string;
    }[];
}

export interface GrupoResponse {
    message: string;
    grupo: {
        id: number;
        materia_id: number;
        nombre_grupo: string;
        gestion: string;
        cupo_maximo: number;
        materia_nombre?: string;
    };
}

export interface MessageResponse {
    message: string;
}

export interface GrupoEstudiante {
    id: number;
    name: string;
    email: string;
}

export interface ListarEstudiantesResponse {
    grupo: {
        id: number;
        nombre: string;
    };
    estudiantes: GrupoEstudiante[];
}

export interface ActualizarEstudiantesGrupoResponse {
    message: string;
    grupo: {
        id: number;
        nombre: string;
    };
    estudiantes: GrupoEstudiante[];
}

export interface SearchEstudianteResponse {
    estudiantes: GrupoEstudiante[];
}

