import MainLayout from "@/layouts/MainLayout.vue";
import CarreraList from "@/views/carreras/CarreraList.vue";
import Dashboard from "@/views/dashboard/Dashboard.vue";
import LaboratorioList from "@/views/laboratorios/LaboratorioList.vue";
import MateriasList from "@/views/materias/MateriasList.vue";
import GruposList from "@/views/grupos/GruposList.vue";
import RoleList from "@/views/roles/RoleList.vue";
import SemestreList from "@/views/semestres/SemestreList.vue";
import UserList from "@/views/users/UserList.vue";
import HorariosList from "@/views/horarios/HorariosList.vue";
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
            },
            {
                path: '/carreras',
                name: 'Carreras',
                component: CarreraList,
                meta: { title: 'Gestion de carreras' },
            },
            {
                path: '/semestres',
                name: 'Semestres',
                component: SemestreList,
                meta: { title: 'Gestion de Ciclos Academicos' }
            },
            {
                path: '/materias',
                name: 'Materias',
                component: MateriasList,
                meta: { title: 'Gestion de Materias' }
            },
            {
                path: '/grupos',
                name: 'Grupos',
                component: GruposList,
                meta: { title: 'Gestion de Grupos' }
            },
            {
                path: '/horarios',
                name: 'Horarios',
                component: HorariosList,
                meta: { title: 'Gestion de Horarios' }
            },

        ]
    }


]