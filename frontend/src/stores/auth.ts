import { defineStore } from "pinia";
import { authService } from "@/services/authService";
import type { LoginPayload, User, VerifyOtpPayload } from "@/types/auth";

export const useAuthStore = defineStore('auth', {
    state: () => ({
        token: (localStorage.getItem('token') || sessionStorage.getItem('token')) as string | null,
        user: JSON.parse(localStorage.getItem('user') || sessionStorage.getItem('user') || 'null') as User | null,
        requiresOtp: false,
        remember: false,
        loginEmail: '',
    }),

    getters: {
        isAuthenticated: (state) => !!state.token,
        userRole: (state) => state.user?.role || 'guest',
        can: (state) => (permiso: string): boolean => {
            return state.user?.permisos?.includes(permiso) || false;
        }
    },

    actions: {
        async login(payload: LoginPayload) {
            this.requiresOtp = false;
            this.remember = !!payload.remember;
            this.loginEmail = payload.email;

            const data = await authService.login(payload);

            if (data.token && data.user) {
                this.setSession(data.token, data.user);
                return true;
            }

            if (data.requires_otp) {
                this.requiresOtp = true;
                return false;
            }
        },

        async verifyOtp(payload: VerifyOtpPayload) {
            const data = await authService.verifyOtp(payload);

            if (data.token && data.user) {
                this.setSession(data.token, data.user);
                this.requiresOtp = false;
                return true;
            }
            return false;
        },

        async resetPassword(email: string): Promise<boolean> {
            try {
                this.loginEmail = email; // Respaldamos el email en el estado

                // Le pega a tu endpoint de Laravel para enviar el correo
                await authService.sendResetLink(email);

                return true;
            } catch (error) {
                console.error("Error al solicitar el enlace de recuperación:", error);
                return false;
            }
        },

        async updatePassword(payload: any): Promise<boolean> {
            try {
                // Enlazamos al servicio de Axios pasándole email, token, password y password_confirmation
                await authService.updatePassword(payload);
                return true;
            } catch (error) {
                console.error("Error al actualizar la contraseña en el servidor:", error);
                return false;
            }
        },

        async checkResetToken(token: string, email: string): Promise<boolean> {
            try {
                await authService.validateResetToken(token, email);
                return true;
            } catch (error) {
                return false;
            }
        },

        async refreshPermisos() {
            try {
                if (!this.user?.id) return;

                const data = await authService.getPermisos();

                this.user.permisos = data;

                const storage = localStorage.getItem('token') ? localStorage : sessionStorage;
                storage.setItem('user', JSON.stringify(this.user));
            } catch (error) {
                console.error("Error al sincronizar los permisos con el servidor:", error);
            }
        },

        async logout() {
            try {
                if (this.token) {
                    await authService.logout();
                }
            } catch (error) {
                // Capturamos el 401 para que no rompa el código de Vue
                console.warn("La API devolvió un error al cerrar sesión, pero limpiamos localmente.", error);
            } finally {
                // Esto se ejecuta SÍ O SÍ, borrando los tokens del navegador
                this.clearLocalSession();
            }
        },

        // Dentro de las actions en authStore.ts
        refreshUser(userData: User) {
            this.user = userData; 
            const storage = localStorage.getItem('token') ? localStorage : sessionStorage;
            storage.setItem('user', JSON.stringify(this.user));
        },

        setSession(newToken: string, userData: User) {
            this.token = newToken;
            this.user = userData;

            const storage = this.remember ? localStorage : sessionStorage;

            storage.setItem('token', newToken);
            storage.setItem('user', JSON.stringify(userData));
        },

        clearLocalSession() {
            this.token = null;
            this.user = null;
            this.requiresOtp = false;
            this.remember = false;

            localStorage.removeItem('token');
            localStorage.removeItem('user');
            sessionStorage.removeItem('token');
            sessionStorage.removeItem('user');
        }

    }
})