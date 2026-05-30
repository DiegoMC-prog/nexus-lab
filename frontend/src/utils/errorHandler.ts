import axios from 'axios';

export const parseLaravelError = (error: unknown): string => {
    // 1. Verificamos si realmente es un error de Axios
    if (!axios.isAxiosError(error)) {
        return 'Ocurrió un error inesperado fuera de la red.';
    }

    // 2. Si el servidor no respondió (Error de conexión / Servidor apagado)
    if (!error.response) {
        return 'No se pudo conectar con el servidor. Revisa tu conexión.';
    }

    const status = error.response.status;
    const data = error.response.data;

    // 3. Si es un error de validación de Laravel (422)
    if (status === 422 && data?.errors) {
        // Juntamos todos los mensajes de validación en una sola cadena
        return Object.values(data.errors)
            .flat() // Aplana los arrays de mensajes individuales
            .join(' \n'); // Los separa con un salto de línea
    }

    // 4. Si es una excepción común de Laravel (401, 500, etc.) que trae "message"
    if (data?.message) {
        return data.message;
    }

    // 5. Fallback por si la API responde algo raro
    return `Error del servidor (${status}): Inténtelo más tarde.`;
};

export const getLaravelValidationErrors = (error: any): Record<string, string[]> => {
    if (error && error.response?.status === 422 && error.response.data?.errors) {
        return error.response.data.errors;
    }
    return {};
};