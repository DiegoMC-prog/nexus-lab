import type { PaginatedResponse } from './api';

export type UserStatus = 'activo' | 'inactivo' | 'bloqueado';
export type UserRole = 'admin' | 'docente' | 'estudiante' | 'mantenimiento';

export interface User {
    id: number;
    name: string;
    email: string;
    estado: string;
    role: string; // El string plano que aplanamos en Laravel
}

export interface Role {
    id: number;
    name: string;
}

export interface CreateUserPayload {
    name: string,
    email: string,
    role: number,
    estado: string
}

export interface UpdateUserPayload {
    name: string;
    email: string;
    role: number; // ID del rol que espera tu Request de Laravel
    estado: string;
}

export interface UserResponse {
    message: string;
    user: User;
}


export interface ShowUserResponse {
    user: User;
}

export interface MessageResponse {
    message: string;
}

export interface GetUsersResponse {
    users: PaginatedResponse<User>;
}
