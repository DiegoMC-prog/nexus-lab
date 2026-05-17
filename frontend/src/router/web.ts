import type { RouteRecordRaw } from "vue-router";

export const routes: Array<RouteRecordRaw> = [
    {
        path: '/login',
        name: 'login',
        component: () => import('@/views/login/Login.vue'),
    },
    {
        path: '/verify-otp',
        name: 'verify-otp',
        component: () => import('@/views/login/VerifyOtp.vue'),
    },
    {
        path: '/reset-password',
        name: 'reset-password',
        component: () => import('@/views/login/ResetPassword.vue'),
    },
    {
        path: '/forgot-password',
        name: 'forgot-password',
        component: () => import('@/views/login/ForgotPassword.vue'), // 👈 Verifica que el archivo se llame así en tu carpeta
    },
    {
        path: '/dashboard',
        name: 'dashboard',
        component: () => import('@/views/dashboard/Dashboard.vue'),
        meta: { requiresAuth: true },
    },
    {
        path: '/users',
        name: 'Usuarios',
        component: () => import('@/views/users/UserList.vue'),
        meta: { requiresAuth: true },
    },

]