import api from "./api";

export interface Perfil {
    id: number;
    user_id: number;
    telefono: string;
    created_at: string;
    usuario?: {
        id: number;
        name: string;
        email: string;
        role: string;
    };
}

export interface PerfilPayload {
    name: string;
    email: string;
    telefono: string;
    password?: string;
    password_confirmation?: string;
}

export const perfilService = {
    async getPerfil(): Promise<Perfil> {
        const response = await api.get('/perfil');
        return response.data.perfil;
    },

    async updatePerfil(payload: PerfilPayload) {
        const response = await api.put('/perfil', payload);
        return response.data;
    }
};
