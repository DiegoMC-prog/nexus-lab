import MainLayout from "@/layouts/MainLayout.vue";
import Dashboard from "@/views/dashboard/Dashboard.vue";
import LaboratorioList from "@/views/laboratorios/LaboratorioList.vue";
import RoleList from "@/views/roles/RoleList.vue";
import UserList from "@/views/users/UserList.vue";
import type { RouteRecordRaw } from "vue-router";

export const routes: Array<RouteRecordRaw> = [
    {
        path: '/login',
        name: 'login',
        component: () => import('@/views/login/Login.vue'),
        meta: { title: 'Iniciar Sesion' }
    },
    {
        path: '/verify-otp',
        name: 'verify-otp',
        component: () => import('@/views/login/VerifyOtp.vue'),
        meta: { title: 'Verificar Codigo' }
    },
    {
        path: '/reset-password',
        name: 'reset-password',
        component: () => import('@/views/login/ResetPassword.vue'),
    },
    {
        path: '/forgot-password',
        name: 'forgot-password',
        component: () => import('@/views/login/ForgotPassword.vue'),
        meta: { title: 'Olvido su Contraseña' }
    },
    {
        path: '/',
        component: MainLayout,
        meta: { requiresAuth: true },
        children: [
            {
                path: '',
                name: 'dashboard',
                component: Dashboard,
                meta: { title: 'Dashboard' },
            },
            {
                path: '/users',
                name: 'Usuarios',
                component: UserList,
                meta: { title: 'Gestion de Usuarios' },
            },
            {
                path: '/roles',
                name: 'Roles',
                component: RoleList,
                meta: { title: 'Gestion de Roles' },
            },
            {
                path: '/laboratorios',
                name: 'Laboratorios',
                component: LaboratorioList,
                meta: { title: 'Gestion de Laboratorios' },
            }
        ]
    }


]