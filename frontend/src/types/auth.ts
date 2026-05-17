export interface User {
    id: number;
    name: string;
    email: string;
    estado: string;
    role: string;
}

export interface LoginResponse {
    message: string;
    token?: string;         // Viene si el dispositivo es conocido
    requires_otp?: boolean; // Viene si es dispositivo nuevo (Lo añadimos en el controlador antes)
    otp?: string;          // Solo viene en entorno local para debug
    user?: User
}

export interface AuthSuccessResponse {
    message: string;
    token: string;
    user: User; // Aquí podrías hacer otra interfaz para tu User
}

export interface LoginPayload {
    email: string;
    password?: string;
    fingerprint: string;
    remember?: boolean;
}


export interface VerifyOtpPayload {
    email: string;
    otp_code: string;
    fingerprint: string;
}