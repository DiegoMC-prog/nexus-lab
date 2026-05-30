import axios, { type AxiosInstance } from "axios";
import { useErrorStore } from "@/stores/errorStore";
import { useAuthStore } from "@/stores/auth";
import router from "@/router";

const api: AxiosInstance = axios.create({
    baseURL: import.meta.env.VITE_API_URL,
    headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json'
    }
});

api.interceptors.request.use((config) => {
    const token = localStorage.getItem('token') || sessionStorage.getItem('token');

    if (token && config.headers) {
        config.headers.Authorization = `Bearer ${token}`;
    }
    return config;
})

api.interceptors.response.use(
    (response) => response, // Si la petición sale bien, la deja pasar normal
    (error) => {
        const errorStore = useErrorStore(); // Invocamos el store aquí dentro
        const authStore = useAuthStore();

        if (error.response && error.response.status === 401) {
            errorStore.clearErrors();
            authStore.clearLocalSession(); 
            router.push('/login');          
            return Promise.reject(error);
        }
        // Escenario 1: El servidor está APAGADO (No hay respuesta del host)
        if (!error.response || error.code === 'ERR_NETWORK') {
            errorStore.setApiDown(true);
        }

        // Escenario 2: El servidor responde, pero con un error crítico de Base de Datos (500)
        else if (error.response.status === 500) {
            errorStore.setDatabaseDown(true);
        }

        return Promise.reject(error); // Devuelve el error por si la vista quiere manejar algo específico
    }
);

export default api;