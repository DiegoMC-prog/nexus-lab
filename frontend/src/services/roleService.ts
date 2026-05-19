import type { ApiRole, ApiRolesResponse } from "@/types/role";
import api from "./api";

export const roleService = {
    async getRoles(): Promise<ApiRolesResponse> {
        const response = await api.get<ApiRolesResponse>('/roles');
        return response.data;
    },
    async updateRolePermissions(roleId: string | number, permissions: string[]): Promise<void> {
        // Asumiendo que tu endpoint en Laravel sea /api/roles/{id}/permissions
        await api.put(`/roles/${roleId}/permissions`, { permissions });
    },
    async getAllPermissions(): Promise<string[]> {
        // 1. Tipamos la estructura exacta que configuraste en Laravel
        const response = await api.get<{ permisos: string[] }>('/roles/permisos');

        // 2. Extraemos el array tradicional para que Vue lo auto-categorice sin problemas
        return response.data.permisos;
    },
}