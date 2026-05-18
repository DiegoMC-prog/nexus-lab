import api from "./api";

import type { LoginPayload, LoginResponse, AuthSuccessResponse, VerifyOtpPayload } from "@/types/auth";

export const authService = {
    async login(payload: LoginPayload): Promise<LoginResponse> {
        const response = await api.post<LoginResponse>('/login', payload);
        return response.data;
    },

    async verifyOtp(payload: VerifyOtpPayload): Promise<AuthSuccessResponse> {
        const response = await api.post<AuthSuccessResponse>('/verify-otp', payload);
        return response.data;
    },

    async sendResetLink(email: string): Promise<any> {
        // Reemplaza '/forgot-password' por el prefijo/ruta real de tu API en Laravel
        const response = await api.post('/forgot-password', { email });
        return response.data;
    },

    async updatePassword(payload: any): Promise<any> {
        const response = await api.post('/reset-password', payload);
        return response.data;
    },

    async validateResetToken(token: string, email: string): Promise<any> {
        const response = await api.post('/validate-reset-token', { token, email });
        return response.data;
    },

    async getPermisos() {
        const response = await api.get(`/user/permisos`);
        return response.data;
    },

    async logout(): Promise<void> {
        await api.post('/logout');
    }
}