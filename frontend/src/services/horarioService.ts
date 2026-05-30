import type { PaginatedResponse } from "@/types/api";
import api from "./api";
import type { GetHorariosResponse, Horario, HorarioFormData, HorarioResponse, HorarioFormDataResponse, MessageResponse } from '@/types/horario';

export const horarioService = {
    async getHorarios(params?: {
        search?: string;
        laboratorio_id?: string;
        docente_id?: string;
        grupo_id?: string;
        fecha_inicio?: string;
        fecha_fin?: string;
        page?: string;
    }): Promise<PaginatedResponse<Horario>> {
        const response = await api.get<GetHorariosResponse>('/horarios', { params });
        return response.data.horarios;
    },

    async getFormData(): Promise<HorarioFormDataResponse> {
        const response = await api.get<HorarioFormDataResponse>('/horarios/form-data');
        return response.data;
    },

    async crearHorario(payload: HorarioFormData): Promise<HorarioResponse> {
        const response = await api.post<HorarioResponse>('/horarios', payload);
        return response.data;
    },

    async actualizarHorario(id: number | string, payload: HorarioFormData): Promise<HorarioResponse> {
        const response = await api.put<HorarioResponse>(`/horarios/${id}`, payload);
        return response.data;
    },

    async eliminarHorario(id: number | string): Promise<MessageResponse> {
        const response = await api.delete<MessageResponse>(`/horarios/${id}`);
        return response.data;
    }
}
