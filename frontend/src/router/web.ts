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
import EstacionList from "@/views/estaciones/EstacionList.vue";
import ComandoList from "@/views/comandos/ComandoList.vue";
import ConfigAlertaList from "@/views/config-alertas/ConfigAlertaList.vue";
import AlertaList from "@/views/alertas/AlertaList.vue";
import LogsComandoList from "@/views/logs-comandos/LogsComandoList.vue";
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
                meta: { title: 'Dashboard', permission: 'dashboard.ver' },
            },
            {
                path: '/users',
                name: 'Usuarios',
                component: UserList,
                meta: { title: 'Gestion de Usuarios', permission: 'usuarios.ver' },
            },
            {
                path: '/roles',
                name: 'Roles',
                component: RoleList,
                meta: { title: 'Gestion de Roles', permission: 'roles.ver' },
            },
            {
                path: '/laboratorios',
                name: 'Laboratorios',
                component: LaboratorioList,
                meta: { title: 'Gestion de Laboratorios', permission: 'laboratorios.ver' },
            },
            {
                path: '/estaciones',
                name: 'Estaciones',
                component: EstacionList,
                meta: { title: 'Gestion de Estaciones', permission: 'estaciones.ver' },
            },
            {
                path: '/carreras',
                name: 'Carreras',
                component: CarreraList,
                meta: { title: 'Gestion de carreras', permission: 'carreras.ver' },
            },
            {
                path: '/semestres',
                name: 'Semestres',
                component: SemestreList,
                meta: { title: 'Gestion de Ciclos Academicos', permission: 'semestres.ver' }
            },
            {
                path: '/materias',
                name: 'Materias',
                component: MateriasList,
                meta: { title: 'Gestion de Materias', permission: 'materias.ver' }
            },
            {
                path: '/grupos',
                name: 'Grupos',
                component: GruposList,
                meta: { title: 'Gestion de Grupos', permission: 'grupos.ver' }
            },
            {
                path: '/horarios',
                name: 'Horarios',
                component: HorariosList,
                meta: { title: 'Gestion de Horarios', permission: 'horarios.ver' }
            },
            {
                path: '/comandos',
                name: 'Comandos',
                component: ComandoList,
                meta: { title: 'Plantillas de Comandos', permission: 'comandos.ver' }
            },
            {
                path: '/config-alertas',
                name: 'ConfigAlertas',
                component: ConfigAlertaList,
                meta: { title: 'Reglas de Alertas', permission: 'alertas.ver' }
            },
            {
                path: '/monitoring',
                name: 'Monitoreo',
                component: AlertaList,
                meta: { title: 'Monitoreo de Alertas', permission: 'monitoreo.ver' }
            },
            {
                path: '/history',
                name: 'Historial',
                component: LogsComandoList,
                meta: { title: 'Historial de Comandos', permission: 'comandos.ver' }
            },
            {
                path: '/restricciones',
                name: 'Restricciones',
                component: () => import('@/views/restricciones/RestriccionesView.vue'),
                meta: { title: 'Restricciones de Aplicaciones', permission: 'manage-restrictions' }
            },
            {
                path: '/docente-dashboard',
                name: 'DocenteDashboard',
                component: () => import('@/views/dashboard/DocenteDashboard.vue'),
                meta: { title: 'Dashboard Docente', permission: 'dashboard.ver' }
            },
            {
                path: '/laboratorio-vivo',
                name: 'LaboratorioVivo',
                component: () => import('@/views/dashboard/LaboratorioVivo.vue'),
                meta: { title: 'Clase en Vivo', permission: 'dashboard.ver' }
            },
            {
                path: '/perfil',
                name: 'Perfil',
                component: () => import('@/views/perfil/Perfil.vue'),
                meta: { title: 'Mi Perfil' }
            },
        ]
    }
]