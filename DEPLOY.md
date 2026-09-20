# Guía de Despliegue en Producción (Deployment Guide)
## Retail Project - Sistema Gastronómico & Delivery en Tiempo Real

Esta guía detalla el procedimiento paso a paso para desplegar la aplicación en servidores de producción, ya sea en **Hosting Compartido (cPanel, Hostinger, DonWeb)** o en un **Servidor Virtual Privado (VPS / Ubuntu / Nginx / Apache)**.

---

## 1. Requisitos del Servidor

- **PHP**: Versión 8.1 o superior (recomendado PHP 8.2).
- **Extensiones PHP obligatorias**:
  - `mysqli` (comunicación con la base de datos MySQL)
  - `curl` (comunicación con APIs externas: Mercado Pago)
  - `mbstring` (manejo de strings UTF-8)
  - `fileinfo` (validación de formatos de imagen WebP/PNG/JPG)
  - `gd` o `imagick` (procesamiento y optimización de imágenes)
  - `openssl` (conexiones seguras y encriptación)
- **Base de Datos**: MySQL 8.0+ o MariaDB 10.4+.
- **Servidor Web**: Apache con `mod_rewrite` y `mod_headers` activos (o Nginx con proxy PHP-FPM).
- **Certificado SSL**: HTTPS activo y válido (imprescindible para el Webhook de Mercado Pago y la geolocalización GPS del navegador).

---

## 2. Preparación de Archivos y Compilación

Antes de subir los archivos al servidor, compila los assets en modo producción:

```bash
# 1. Instalar dependencias de Composer y Node
composer install --no-dev --optimize-autoloader
npm install

# 2. Compilar CSS y JS minificados
npx gulp build
```

---

## 3. Estructura de Despliegue y DocumentRoot

El proyecto sigue la arquitectura de seguridad estándar donde los archivos de lógica (`models`, `controllers`, `views`, `includes`, `vendor`) residen fuera del acceso web público, y únicamente la carpeta `public/` es expuesta al navegador.

### Opción A: Hosting con DocumentRoot Configurable (Recomendado)
- Si tu hosting (o cPanel) permite configurar la carpeta pública del dominio o subdominio:
  - Establece el **DocumentRoot** apuntando directamente a la carpeta `/public` del proyecto.
  - Ejemplo: `/home/usuario/retail-project/public`

### Opción B: Hosting Compartido Estándar (`public_html` fijo)
- Si el hosting no permite cambiar la raíz y todo debe estar dentro de `public_html`:
  1. Sube todo el contenido del repositorio dentro de `public_html`.
  2. El archivo `.htaccess` provisto en la raíz del proyecto redirige automáticamente todas las peticiones hacia `/public` y bloquea el acceso directo a carpetas sensibles:
     - Bloquea `/sql`, `/includes`, `/models`, `/controllers`, `/views`, `/classes`, `/scripts`, `/vendor` y archivos `.env`.

---

## 4. Base de Datos

1. Crea una base de datos en tu panel de control (ej. `cpanel_retail`).
2. Crea un usuario de base de datos con todos los privilegios sobre dicha base de datos.
3. Importa el esquema inicial:
   - **Vía phpMyAdmin**: Pestaña *Importar* → Selecciona el archivo [`sql/retail_mvc.sql`](file:///c:/Users/Branco/Desktop/retail-project/sql/retail_mvc.sql).
   - **Vía SSH**:
     ```bash
     mysql -u tu_usuario -p tu_base_de_datos < sql/retail_mvc.sql
     ```

---

## 5. Configuración del Archivo `.env` en Producción

Copia el archivo `.env.example` como `.env` en la raíz del servidor y ajusta los valores reales:

```ini
# Configuración General
APP_ENV=production
APP_DEBUG=false
APP_URL=https://tudominio.com

# Base de Datos MySQL
DB_HOST=localhost
DB_USER=usuario_mysql_produccion
DB_PASS=PasswordFuerte123!
DB_NAME=base_de_datos_produccion
DB_PORT=3306

# Pasarela de Pagos: Mercado Pago (Credenciales Productivas)
MP_PUBLIC_KEY=APP_USR-xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx
MP_ACCESS_TOKEN=APP_USR-xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx-xxxxxx
MP_WEBHOOK_SECRET=tu_webhook_secret_si_aplica

# Correo Saliente: Servidor SMTP Real (cPanel, Sendgrid, Mailgun, Brevo)
MAIL_HOST=mail.tudominio.com
MAIL_PORT=465
MAIL_USER=pedidos@tudominio.com
MAIL_PASS=PasswordCorreo123!
MAIL_FROM=pedidos@tudominio.com
MAIL_ENCRYPTION=ssl
```

---

## 6. Permisos de Archivos y Carpetas

Asegúrate de que el servidor web tenga permisos de escritura en las carpetas dinámicas:

```bash
# Permisos para subida de fotos de productos y logos
chmod -R 775 public/imagenes

# Proteger archivo de entorno
chmod 600 .env
```

---

## 7. Configuración de Webhook en Mercado Pago

Para que los pagos online con Mercado Pago se confirmen automáticamente en tiempo real:

1. Ingresa al panel de desarrolladores de Mercado Pago ([mercadopago.com/developers](https://www.mercadopago.com/developers)).
2. Dirígete a **Tus integraciones** → Selecciona tu aplicación de producción.
3. En la sección **Notificaciones Webhook**:
   - URL de notificación: `https://tudominio.com/api/webhook/mercadopago`
   - Eventos a escuchar: **Pagos (`payment`)**.
4. Guarda los cambios.

---

## 8. Checklist Pre-Lanzamiento

Antes de abrir el sistema al público general:

- [ ] **Configuración del Local**: En `/admin/configuracion`, fija la ubicación exacta del local con el pin del mapa, define el costo por kilómetro, radio de entrega gratis y minutos de cancelación permitidos.
- [ ] **Horarios de Atención**: Verifica los días y horarios de apertura configurados.
- [ ] **Catálogo y Precios**: Revisa que todos los productos tengan precios actualizados, fotos WebP optimizadas y opciones cargadas.
- [ ] **Stock Inicial**: Carga el stock inicial de materias primas en `/admin/insumos` para que el control atómico de recetas funcione desde el primer pedido.
- [ ] **Equipo de Trabajo**: Da de alta a los empleados de cocina (`rol: empleado`) y cadetes de delivery (`rol: repartidor`).
- [ ] **Caja Inicial**: Realiza la primera apertura de caja con fondo de cambio en `/cocina/caja`.
- [ ] **Prueba de Pago**: Realiza una compra de prueba en producción para validar el flujo completo (Carrito → Pago → Cocina KDS → Despacho → Entrega).
