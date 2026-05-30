import api from "./api";
import type { DocenteDashboardResponse, ClaseActivaResponse } from "@/types/docenteDashboard";

export const docenteDashboardService = {
    /**
     * Recupera los KPIs y cronograma de clases de hoy para el docente autenticado.
     */
    async getDashboardData(): Promise<DocenteDashboardResponse> {
        const response = await api.get<DocenteDashboardResponse>('/docente/dashboard');
        return response.data;
    },

    /**
     * Consulta el estado de la clase activa en tiempo real (estaciones activas y alertas).
     */
    async getClaseActivaRealTime(): Promise<ClaseActivaResponse> {
        const response = await api.get<ClaseActivaResponse>('/docente/clase-activa');
        return response.data;
    },

    /**
     * Ejecuta una acción sobre una estación de trabajo (bloquear, desbloquear, cerrar_sesion).
     */
    async ejecutarAccionEstacion(estacionId: number, accion: string): Promise<any> {
        const response = await api.post(`/docente/estaciones/${estacionId}/accion`, { accion });
        return response.data;
    }
};
export default docenteDashboardService;
