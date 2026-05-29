import api from "./api";

export interface Alerta {
    id: number;
    estacion_id: number;
    estacion?: { id: number; hostname: string; direccion_ip: string };
    config_alerta_id: number;
    configuracion_alerta?: { id: number; metrica: string; severidad: string; operador: string; valor_umbral: number };
    valor_actual: number;
    estado: string;
    resuelto_at?: string;
    created_at: string;
}

export interface AlertaFormData {
    estacion_id: number;
    config_alerta_id: number;
    valor_actual: number;
    estado: string;
    resuelto_at?: string | null;
}

export const alertaService = {
    async getAlertas(params?: { search?: string; estacion_id?: string | number; estado?: string; page?: number }) {
        const response = await api.get('/alertas', { params });
        return response.data.alertas;
    },

    async crearAlerta(payload: AlertaFormData) {
        const response = await api.post('/alertas', payload);
        return response.data;
    },

    async actualizarAlerta(id: number | string, payload: AlertaFormData) {
        const response = await api.put(`/alertas/${id}`, payload);
        return response.data;
    },

    async eliminarAlerta(id: number | string) {
        const response = await api.delete(`/alertas/${id}`);
        return response.data;
    }
};
