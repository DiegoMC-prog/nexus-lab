# NexusLab - SISTEMA DISTRUIBUIDO PARA LA GESTION Y MONITOREO DE LABORATORIOS INFORMATICOS EN TIEMPO REAL

Este repositorio contiene el código fuente completo de NexusLab, un SISTEMA DISTRUIBUIDO PARA LA GESTION Y MONITOREO DE LABORATORIOS INFORMATICOS EN TIEMPO REAL.

## 1. Diagrama de Arquitectura (Docker)

```text
                                  +-----------------------+
      [ Usuarios / Admin ]        |  Servidor VPS / Local |
               |                  +-----------------------+
               | (HTTP: 80)       |                       |
               | (WS: 8080)       v                       |
+-------------------------------------------------------+ |
| NGINX PROXY (Frontend Container)                      | |
| - Sirve la aplicación Vue (Puerto 80)                 | |
| - Enruta /api/ hacia el Backend (Puerto 8000 interno) | |
+-------------------------------------------------------+ |
               |                                          |
               | (Red Interna: frontend_network)          |
               v                                          |
+-------------------------------------------------------+ |
| BACKEND CONTAINER (Laravel)                           | |
| - API REST principal (Puerto 8000 interno)            | |
| - Validación TOTP y Lógica de Negocio                 | |
+-------------------------------------------------------+ |
               |                                          |
               | (Red Interna: internal_network)          |
               v                                          |
+-------------------------------------------------------+ |
| DATABASE CONTAINER (PostgreSQL 15)                    | |
| - Persistencia de datos (Puerto 5432 interno)         | |
+-------------------------------------------------------+ |
```
*Nota: Los servicios adicionales como Reverb (WebSockets 8080), Queue y Scheduler operan reciclando la imagen del backend y comunicándose por la red interna.*

## 2. Instrucciones para Entorno Local

Para levantar este proyecto en tu propia máquina usando Docker, sigue estos pasos:

```bash
# 1. Clonar el repositorio
git clone https://github.com/DiegoMC-prog/nexus-lab.git
cd nexus-lab

# 2. Copiar variables de entorno
cp .env.example .env

# 3. Levantar todos los servicios
docker compose up --build -d

# 4. Instalar dependencias del backend y migrar la base de datos
docker exec -it nexus-backend composer install
docker exec -it nexus-backend php artisan migrate:fresh --seed
```
*El sistema estará disponible en `http://localhost` y los WebSockets en `ws://localhost:8080`.*

## 3. Usuarios de Prueba (Obligatorios en Producción)

Los siguientes usuarios han sido inyectados mediante los Seeders y están disponibles tanto en el entorno de desarrollo como en producción para pruebas de evaluación. El sistema implementa Autenticación 2FA mediante TOTP (Google Authenticator / Authy).

| Rol | Email | Contraseña | Código 2FA (Secret) | Escanear QR |
|-----|-------|------------|---------------------|-------------|
| Administrador | `admin@prueba.com` | `Admin123!` | **`JBSWY3DPEHPK3PXP`** | <img src="./docs/qr-admin.svg" width="100" /> |
| Usuario regular | `user@prueba.com` | `User123!` | **`KNRW24TMMJQXEZLJ`** | <img src="./docs/qr-user.svg" width="100" /> |

> **Nota para el Inge:** Puede escanear los Códigos QR directamente con su aplicación Authenticator (Google Authenticator, Authy, etc) o introducir los secretos manualmente. No es necesario que los usuarios marquen un equipo de confianza preconfigurado en la base de datos para esta prueba.

## 4. Versiones

| Tag      | Estado         | Cambios                                   |
|----------|----------------|-------------------------------------------|
| v1.2.1   | ✅ Producción  | 2FA (TOTP) + equipo de confianza + despliegue cloud |
| v1.2.0   | ✅ Estable     | primera version de produccion  |
| v1.1.1   | ✅ Estable     | Arreglo de idioma de mensajes    |
| v1.1.0   | ✅ Estable     | Correcion de QA                         |

Para probar una versión específica:
```bash
git fetch --tags
git checkout v1.2.0
docker compose up --build -d
```

## 5. Checklist de Funcionalidades Verificables

- [x] Registro de usuario
- [x] Login con email/contraseña
- [x] Solicitud de código 2FA tras login correcto
- [x] Verificación de código TOTP (Google Authenticator)
- [x] Checkbox "Confiar en este equipo" funcional (Fingerprint/Dispositivos guardados)
- [x] Acceso a rutas protegidas solo con JWT válido
- [x] Diferentes vistas/permisos para ADMIN y USER
- [x] Cierre de sesión (Revocación de tokens)
- [x] Despliegue en la nube accesible por HTTP/HTTPS
