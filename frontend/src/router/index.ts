import { createRouter, createWebHistory } from 'vue-router'
import { routes } from './web'
import { useAuthStore } from '@/stores/auth'

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: routes,
})

/**
 * Devuelve la ruta home correspondiente al rol del usuario autenticado.
 * - admin      → Dashboard general (/)
 * - docente    → Horarios (sus clases)
 * - estudiante → Horarios (su cronograma)
 */
const getHomeRouteForRole = (role: string): string => {
  switch (role.toLowerCase()) {
    case 'docente':
      return '/horarios';
    case 'estudiante':
      return '/horarios';
    case 'admin':
    default:
      return '/';
  }
}

router.beforeEach((to) => {
  const authStore = useAuthStore();
  const isLogged = authStore.isAuthenticated;

  const pageTitle = to.meta.title ? `${to.meta.title} | Control de Equipos` : 'Control de Equipos';
  document.title = pageTitle;

  // 1. Protección: Si la ruta requiere autenticación y NO está logueado
  if (to.meta.requiresAuth && !isLogged) {
    return { name: 'login' };
  }

  // 2. Redirección por sesión activa: Si ya está logueado e intenta ir a Login u OTP
  if (isLogged && (to.name === 'login' || to.name === 'verify-otp')) {
    const role = authStore.user?.role || 'admin';
    const homeRoute = getHomeRouteForRole(role);
    return homeRoute;
  }

  // 3. Trampa de seguridad para el OTP: No permitir acceso manual sin haber pasado por login
  if (to.name === 'verify-otp' && !authStore.requiresOtp) {
    return { name: 'login' };
  }

  // 4. Guard de permisos: Si la ruta tiene un permiso requerido, verificarlo
  if (isLogged && to.meta.permission) {
    const requiredPermission = to.meta.permission as string;
    const hasPermission = authStore.can(requiredPermission);

    if (!hasPermission) {
      // Redirigir a la ruta home del rol en lugar de mostrar un error
      const role = authStore.user?.role || 'admin';
      const homeRoute = getHomeRouteForRole(role);
      // Evitar bucle infinito si ya estamos en la ruta home
      if (to.path !== homeRoute) {
        return homeRoute;
      }
    }
  }

  // Si no cumple ninguna condición anterior, permite el paso libre
  return true;
})

export default router
