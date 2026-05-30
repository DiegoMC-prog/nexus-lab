/// <reference types="vite/client" />

import type Echo from "laravel-echo";

declare global {
    interface Window {
        Pusher: any;
        Echo: Echo<'reverb'>; // 💡 Usamos import dinámico para no romper el entorno global
    }
}

export { };