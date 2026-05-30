import api from "./api";

export interface Comando {
    id: number;
    nombre: string;
    slug: string;
    tipo: string;
    require_auth: boolean;
    created_at: string;
}

export interface ComandoFormData {
    nombre: string;
    slug: string;
    tipo: string;
    require_auth: boolean;
}

export const comandoService = {
    async getComandos(params?: { search?: string; page?: number }) {
        const response = await api.get('/comandos', { params });
        return response.data.comandos;
    },

    async crearComando(payload: ComandoFormData) {
        const response = await api.post('/comandos', payload);
        return response.data;
    },

    async actualizarComando(id: number | string, payload: ComandoFormData) {
        const response = await api.put(`/comandos/${id}`, payload);
        return response.data;
    },

    async eliminarComando(id: number | string) {
        const response = await api.delete(`/comandos/${id}`);
        return response.data;
    }
};
