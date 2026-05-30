import api from "./api";

export interface ConfigAlerta {
    id: number;
    metrica: string;
    operador: string;
    valor_umbral: number;
    severidad: string;
    activo: boolean;
    created_at: string;
}

export interface ConfigAlertaFormData {
    metrica: string;
    operador: string;
    valor_umbral: number;
    severidad: string;
    activo: boolean;
}

export const configAlertaService = {
    async getConfigAlertas(params?: { search?: string; activo?: string | boolean; page?: number }) {
        const response = await api.get('/config-alertas', { params });
        return response.data.configAlertas;
    },

    async crearConfigAlerta(payload: ConfigAlertaFormData) {
        const response = await api.post('/config-alertas', payload);
        return response.data;
    },

    async actualizarConfigAlerta(id: number | string, payload: ConfigAlertaFormData) {
        const response = await api.put(`/config-alertas/${id}`, payload);
        return response.data;
    },

    async eliminarConfigAlerta(id: number | string) {
        const response = await api.delete(`/config-alertas/${id}`);
        return response.data;
    }
};
