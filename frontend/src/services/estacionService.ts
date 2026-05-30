import api from "./api";

export interface EstacionFormData {
    laboratorio_id?: number | null;
    estudiante_actual_id?: number | null;
    uuid: string;
    hostname: string;
    direccion_mac: string;
    direccion_ip: string;
    so_info: string;
    estado: string;
    version_agente: string;
}

export const estacionService = {
    async vincularEstacion(payload: EstacionFormData) {
        const response = await api.post('/estaciones', payload);
        return response.data;
    },

    async getEstaciones(params?: { search?: string; laboratorio_id?: string; estado?: string; page?: number }) {
        const response = await api.get('/estaciones', { params });
        return response.data.estaciones;
    },

    async eliminarEstacion(id: number | string) {
        const response = await api.delete(`/estaciones/${id}`);
        return response.data;
    }
};
