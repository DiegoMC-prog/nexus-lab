import type { Carrera, CarreraFormData, CarreraResponse, GetCarrerasResponse, MessageResponse } from "@/types/carrera";
import api from "./api";
import type { PaginatedResponse } from "@/types/api";

export const carreraService = {
    async getCarreras(params?: { search?: string; page?: string }): Promise<PaginatedResponse<Carrera>> {
        const response = await api.get<GetCarrerasResponse>('/carreras', { params });
        return response.data.carreras;
    },

    async crearCarrera(payload: CarreraFormData): Promise<CarreraResponse> {
        const response = await api.post<CarreraResponse>('/carreras', payload);
        return response.data;
    },

    async actualizarCarrera(id: number | string, payload: CarreraFormData): Promise<CarreraResponse> {
        const response = await api.put<CarreraResponse>(`/carreras/${id}`, payload);
        return response.data;
    },

    async eliminarCarrera(id: number | string): Promise<MessageResponse> {
        const response = await api.delete<MessageResponse>(`/carreras/${id}`);
        return response.data;
    }
}