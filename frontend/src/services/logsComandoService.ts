import api from "./api";

export interface LogsComando {
    id: number;
    usuario_id: number;
    usuario?: { id: number; name: string; email: string };
    estacion_id: number;
    estacion?: { id: number; hostname: string; direccion_ip: string };
    comando_id: number;
    comando?: { id: number; nombre: string; slug: string };
    origen: string;
    estado: string;
    mensaje_respuesta?: string;
    created_at: string;
}

export const logsComandoService = {
    async getLogsComandos(params?: { 
        search?: string; 
        usuario_id?: string | number; 
        estacion_id?: string | number; 
        comando_id?: string | number; 
        estado?: string;
        page?: number; 
    }) {
        const response = await api.get('/logs-comandos', { params });
        return response.data.logsComando;
    },

    async eliminarLogComando(id: number | string) {
        const response = await api.delete(`/logs-comandos/${id}`);
        return response.data;
    }
};
