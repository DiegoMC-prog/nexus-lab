import type { PaginatedResponse } from "@/types/api";
import type { GetUsersResponse, User, CreateUserPayload, Role, UserResponse, ShowUserResponse, UpdateUserPayload, MessageResponse } from "@/types/user";
import api from "./api";


export const userService = {
    async getUsers(params?: { search?: string; estado?: string; role?: string; page?: number }): Promise<PaginatedResponse<User>> {
        // Le pasamos el tipo <GetUsersResponse> a Axios
        const response = await api.get<GetUsersResponse>('/users', { params });
        return response.data.users; // Retorna la estructura paginada limpia
    },

    async crearUser(payload: CreateUserPayload): Promise<UserResponse> {
        const response = await api.post<UserResponse>('/users', payload);
        return response.data;
    },

    async showUser(id: number | string): Promise<User> {
        const response = await api.get<ShowUserResponse>(`/users/${id}`);
        return response.data.user;
    },

    async actualizarUser(id: number | string, payload: UpdateUserPayload): Promise<UserResponse> {
        const response = await api.put<UserResponse>(`/users/${id}`, payload);
        return response.data;
    },

    async eliminarUser(id: number | string): Promise<MessageResponse> {
        const response = await api.delete<MessageResponse>(`/users/${id}`);
        return response.data; 
    },

    async cargarRoles(): Promise<Role[]> {
        const response = await api.get<Role[]>('/roles');
        return response.data;
    }
}