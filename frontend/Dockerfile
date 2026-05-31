FROM node:22-alpine AS build

WORKDIR /app

# Copiar archivos de dependencias
COPY package.json package-lock.json* ./

# Instalar dependencias
RUN npm ci

# Copiar el resto del código fuente
COPY . .

# Compilar la aplicación Vue
RUN npm run build

# Etapa 2: Servidor estático con NGINX
FROM nginx:alpine

# Copiar la build desde la etapa anterior a la carpeta pública de NGINX
# Por defecto, Vite exporta a la carpeta 'dist'
COPY --from=build /app/dist /usr/share/nginx/html

# Creamos una configuración de Nginx al vuelo que redirige todo a index.html
RUN echo 'server { \
    listen 80; \
    location / { \
        root /usr/share/nginx/html; \
        index index.html index.htm; \
        try_files $uri $uri/ /index.html; \
    } \
}' > /etc/nginx/conf.d/default.conf
# ─────────────────────────────────────────────────────────────────────────

# Copiar la build desde la etapa anterior a la carpeta pública de NGINX
COPY --from=build /app/dist /usr/share/nginx/html

# Exponer el puerto 80
EXPOSE 80

# Iniciar NGINX
CMD ["nginx", "-g", "daemon off;"]
