import { createRouter, createWebHistory } from 'vue-router'
import { routes } from './web'
import { useAuthStore } from '@/stores/auth'

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: routes,
})

router.beforeEach((to) => {
  const authStore = useAuthStore();
  const isLogged = authStore.isAuthenticated;

  const pageTitle = to.meta.title ? `${to.meta.title} | Control de Equipos` : 'Control de Equipos';
  document.title = pageTitle;

  // 1. Protección del Dashboard: Si la ruta requiere autenticación y NO está logueado
  if (to.meta.requiresAuth && !isLogged) {
    return { name: 'login' };
  }

  // 2. Redirección por sesión activa: Si ya está logueado e intenta ir a Login o OTP
  if (isLogged && (to.name === 'login' || to.name === 'verify-otp')) {
    return { name: 'dashboard' };
  }

  // 3. Trampa de seguridad para el OTP: Si NO está logueado e intenta escribir 
  // manualmente '/verify-otp' en la URL sin haber pasado por el formulario de login
  if (to.name === 'verify-otp' && !authStore.requiresOtp) {
    return { name: 'login' };
  }

  // Si no cumple ninguna condición anterior, permite el paso libre
  return true;
})

export default router
