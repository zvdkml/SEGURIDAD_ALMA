# Proyecto: SEGURIDAD ALMA - Plugin de Seguridad para WordPress

## Objetivo
Crear un plugin que escanee la seguridad del sitio WordPress, detecte vulnerabilidades y muestre los resultados en un dashboard visual dentro del panel de administración.

## Funciones Principales

### 1. Escaneo de Seguridad
- Analizar el estado de seguridad del sitio.
- Calcular un Security Score (0-100).
- Mostrar nivel de seguridad: Alto, Medio o Bajo.

### 2. Detección de Vulnerabilidades
- WordPress desactualizado.
- Plugins/Temas desactualizados o vulnerables.
- XML-RPC activado.
- Modo debug activo.
- Archivos sensibles expuestos (wp-config.php, readme.html, license.txt).
- Permisos inseguros (ej. 777).
- Usuarios administradores inseguros.
- Demasiados intentos de login.
- Falta de HTTPS.
- Headers de seguridad faltantes.
- Directorio listado habilitado.
- Plugins abandonados.

### 3. Dashboard Visual Moderno (Tipo SaaS)
- Security Score grande.
- Barra de progreso.
- Cards con estado del sistema (Verde, Amarillo, Rojo).
- Gráficos con Chart.js.
- Sección de problemas críticos.
- Tabla completa de vulnerabilidades.
- Historial de escaneos.

### 4. Integración
- Conexión con dashboard externo mediante API.

## Diseño Visual
- Tailwind CSS o Bootstrap.
- Iconos de alerta.
- Chart.js para estadísticas.
