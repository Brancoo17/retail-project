# CONTEXT.md — Guía y Estado del Proyecto Retail MVC

Documento de referencia para agentes de IA y desarrolladores. Diseñado para ser escaneable, conciso y representar fielmente la arquitectura, modelo de datos, estado de avance y convenciones de trabajo.

---

## 1. Qué es el proyecto

Sistema de gestión integral para comercios de comida con delivery (pizzerías, rotiserías, hamburgueserías, etc.) desarrollado en **PHP 8+ con arquitectura MVC propia (sin frameworks externos)**. Sigue el mismo stack, estructura y convenciones utilizadas en los proyectos anteriores (*gym-project, AppSalon, Ecommerce, BienesRaices* — GitHub: `Brancoo17`).

Resuelve tres dolores típicos de estos negocios más la logística de reparto:
1. **Pedidos desordenados**: centralización de pedidos web (invitados o con cuenta) con seguimiento de estados en tiempo real.
2. **Inventario e insumos**: control de materia prima con recetas por producto y descuento automático de stock por venta.
3. **Control de caja y facturación**: registro claro de ventas, costos reales y comprobantes internos.
4. **Delivery propio**: asignación de pedidos a repartidores propios, cálculo de envío automatizado mediante fórmula Haversine y seguimiento de entregas.

---

## 2. Stack y arquitectura (no negociable)

- **Backend**: PHP 8+ MVC propio viviendo en la **raíz del proyecto** (sin carpeta `app/`).
- **Base de Datos**: MySQL. El archivo `sql/retail_mvc.sql` es la **fuente de verdad** única del modelo.
- **Frontend & Estilos**: SCSS (SASS) modular con metodología BEM + Gulp compilando automáticamente a `public/build/css/app.css`. **Todos los estilos deben escribirse obligatoriamente en archivos `.scss` (en `src/scss/`)**. JavaScript Vanilla nativo utilizando `fetch()` para peticiones asíncronas/AJAX.
- **JavaScript & Modularización**: Todo el código JavaScript del frontend se centraliza obligatoriamente en `src/js/app.js` y se compila con Gulp a `public/build/js/app.js`. Organizado con funciones modulares, comentarios delimitando la vista a la que pertenece y cláusulas de guarda (*guard clauses*) para verificar la presencia de elementos en el DOM. Las vistas PHP no deben contener etiquetas `<script>` salvo para inyección de datos dinámicos provistos exclusivamente por PHP en el servidor (ej. arrays JSON inyectados).
- **Enrutamiento**: `Router.php` como despachador y `public/index.php` como Front Controller.
- **Autoload & PSR-4**: Composer configurado con namespaces `MVC\`, `Controllers\`, `Model\`, `Classes\`.
- **Capa de Datos**: Patrón `ActiveRecord` propio en `models/ActiveRecord.php` (`all()`, `find()`, `where()`, `whereAll()`, `SQL()`, `queryArray()`, `guardar()`, `sincronizar()`, `alertas()`) — sin ORMs externos.
- **Bootstrap y Helpers**: `includes/app.php`, `database.php` y `funciones.php` con funciones helper (`s()`, `debuguear()`, `isAuth()`, `isDueno()`, `isEmpleado()`, `isRepartidor()`, `isCliente()`, `isStaff()`).
- **Motor de Vistas**: Renderizado mediante `$router->render('area/vista', [...])` con layouts en `views/layout.php`.
- **Pagos**: Mercado Pago Checkout Pro (mismo patrón que gym-project) + efectivo en mostrador/entrega.
- **Email**: PHPMailer para confirmaciones de pedidos, notificaciones y recuperación de credenciales.
- **Seguridad y Lógica Crítica**: Validaciones sensibles (stock de insumos, precios calculados, costos de envío, estados de pedidos) validadas **siempre en el servidor (server-side)**. Tokens con `random_bytes(32)`, sanitización, sesión endurecida y `.htaccess` bloqueando `.env`, `.git`, `sql/`, `composer.*`.

---

## 3. Qué NO hacer

- ❌ **NO escribir estilos inline** (`style="..."`) en las vistas HTML/PHP. Todo estilo de maquetación, layout, espaciado, colores, tipografía y diseño debe realizarse pura y exclusivamente en archivos `.scss` (SASS) compilados por Gulp. La única excepción admisible son anchos dinámicos de barras calculados en PHP/JS (`style="width: X%;"`).
- ❌ **NO escribir código JavaScript en las vistas HTML/PHP si no requiere código PHP incrustado**. Todo script de interacción, eventos, filtros, modales, mapas y lógica del frontend debe ubicarse en `src/js/app.js` (modularizado por vista con guard clauses) y compilarse con Gulp a `public/build/js/app.js`.
- ❌ **NO** introducir frameworks de PHP (Laravel, Symfony, CodeIgniter, etc.) ni frameworks de JavaScript o CSS utilitarios (React, Vue, Tailwind, etc.).
- ❌ **NO** renombrar carpetas, mover la estructura ni alterar las convenciones establecidas sin previa autorización justificada.
- ❌ **NO** construir infraestructura de super-admin de la plataforma (planes de suscripción SaaS o billing del software) todavía — el modelo queda preparado para ello, pero no se implementa en esta etapa.
- ❌ **NO** implementar facturación fiscal electrónica (AFIP/ARCA) en esta fase (descartado explícitamente; se utilizan comprobantes internos numerados).
- ❌ **NO** saltarse etapas ni encadenar múltiples pasos grandes de golpe sin confirmación y validación previa.

---

## 4. Arquitectura Multi-Tenant "Ligero"

- Se incluye la tabla `comercios` desde el inicio y **toda tabla relevante lleva `comercio_id`**.
- Permite operar en el presente con un solo comercio sin complejidad adicional y habilita el escalado a SaaS multi-inquilino sin alterar el esquema relacional.
- **Sucursales**: 1 comercio = 1 local físico en esta fase. No se crea tabla `sucursales` todavía. Como `comercio_id` ya está presente transversalmente, el día que un comercio requiera sucursales, se agregará la tabla `sucursales` con `sucursal_id` en las tablas operativas sin romper el modelo actual.

---

## 5. Modelo de datos (resumen)

### Entidades Principales

| Entidad | Tabla | Propósito y Relaciones Clave |
|---|---|---|
| **Comercios** | `comercios` | Tenant. Nombre, dirección, `lat`/`lng` del local (cargados una sola vez con pin en mapa por el dueño), teléfono, horario de atención, configuración visual (logo, colores primario/secundario), `km_envio_gratis`, `costo_envio_por_km`, `minutos_cancelacion` (ventana de tiempo para cancelar pedidos), estado (`activo`/`suspendido`). |
| **Usuarios** | `usuarios` | Cuentas con roles (`dueño`, `empleado`, `repartidor`, `cliente`), `comercio_id`, nombre, email, password, teléfono, token y confirmado. El rol `cliente` es opcional (solo si desea historial o guardar direcciones). |
| **Categorías** | `categorias` | Categorías del menú por comercio (ej. Pizzas, Lomitos, Bebidas, Postres), orden y estado activo. |
| **Productos** | `productos` | Catálogo de comidas/bebidas con nombre, descripción, precio base, imagen, `categoria_id`, `comercio_id`, activo/disponible. |
| **Opciones de Producto** | `producto_opciones` | Variantes o agregados con precio adicional (ej. "Grande +$500", "Papas extras +$300", "Sin cebolla +$0"). |
| **Insumos** | `insumos` | Materia prima (harina, muzzarella, envases, latas) con stock actual, unidad de medida (kg, gr, lts, unidad), stock mínimo de alerta y `comercio_id`. |
| **Recetas** | `producto_insumos` | Matriz de consumo de insumos por producto vendido. Permite descontar stock de forma automática. |
| **Movimientos de Stock** | `movimientos_stock` | Trazabilidad de entradas (compras), salidas (ventas, mermas, cancelaciones), cantidad, motivo, fecha y `usuario_id`. |
| **Pedidos** | `pedidos` | Cabecera: `comercio_id`, `usuario_id` (nullable), datos de contacto (`nombre_contacto`, `telefono_contacto`), dirección de texto completa (`direccion_texto`, piso/depto, entrecalles), coordenadas pineadas (`lat`, `lng`), `distancia_km` (Haversine), `costo_envio`, tipo (`delivery`/`retiro`), estado (`pendiente`, `confirmado`, `en_preparacion`, `en_camino`, `entregado`, `cancelado`), `fecha_confirmado`, método de pago, subtotal, total. |
| **Detalle de Pedido** | `pedido_detalle` | Relación de productos, cantidad, precio unitario histórico y opciones seleccionadas en formato serializado/JSON o subtabla. |
| **Repartidores de Pedido** | `pedido_repartidor` | Asignación de pedido a usuario con rol `repartidor`, hora de asignación, hora de entrega y estado. |
| **Pagos** | `pagos` | Método (`mercadopago`, `efectivo`), estado (`pendiente`, `aprobado`, `rechazado`), `mp_preference_id`, `mp_payment_id`, `mp_status`, fecha y monto. |
| **Comprobantes** | `comprobantes` | Ticket interno correlativo por comercio (no fiscal), vinculado 1 a 1 con el pedido. |
| **Direcciones de Cliente** | `direcciones_cliente` | Direcciones guardadas para clientes con cuenta (etiqueta, dirección, lat, lng, observaciones). Invitados usan datos directos en `pedidos`. |

### Reglas de Negocio Críticas Server-Side

1. **Control estricto de stock**: El stock de un insumo nunca puede ser negativo. Al confirmar el pedido se realiza la reserva/descuento de insumos asociados. Si un producto carece de insumos suficientes según su receta, no puede confirmarse.
2. **Cálculo de envío (Haversine)**:
   - Coordenadas `(lat, lng)` del comercio fijadas en la configuración.
   - Coordenadas `(lat, lng)` del cliente marcadas con pin en mapa (Leaflet + OpenStreetMap) + dirección en texto para la entrega.
   - Distancia calculada en línea recta en el backend:
     $$\text{distancia\_km} = \text{haversine}(lat_1, lng_1, lat_2, lng_2)$$
   - Si $\text{distancia\_km} \leq \text{km\_envio\_gratis} \implies \text{costo\_envio} = 0$.
   - Si $\text{distancia\_km} > \text{km\_envio\_gratis} \implies \text{costo\_envio} = (\text{distancia\_km} - \text{km\_envio\_gratis}) \times \text{costo\_envio\_por\_km}$.
3. **Ventana de cancelación**:
   - El cliente solo puede cancelar si el tiempo transcurrido desde `pedidos.fecha_confirmado` es menor o igual a `comercios.minutos_cancelacion` (configurable por el dueño) y el pedido sigue en `confirmado` (antes de entrar a `en_preparacion`).
   - Toda cancelación revierte los insumos reservados mediante un registro en `movimientos_stock` con tipo "cancelación".
   - Fuera de esa ventana, únicamente el personal del comercio (`dueño` o `empleado`) puede cancelar el pedido.

---

## 6. Roles del Sistema

- **Dueño / Administrador**: Acceso global al comercio asignado. Gestiona catálogo (productos, categorías, opciones), insumos, recetas, stock, empleados, repartidores, configuración de local/envíos y reportes de caja/ventas.
- **Empleado (Cocina / Caja)**: Monitorea pedidos entrantes, cambia estados operativos (`confirmado` → `en_preparacion` → `listo`), registra cobros en efectivo en mostrador y asigna repartidores.
- **Repartidor**: Visualiza pedidos asignados a su cuenta, accede a la dirección/mapa de entrega y actualiza estados de transporte (`en_camino` → `entregado`).
- **Cliente**: Acceso público al menú digital del comercio, armado de pedido como invitado o con cuenta, ubicación en mapa, seguimiento de estados del pedido y opción de pago online o en entrega.

---

## 7. Plan de etapas y estado actual

| Etapa | Descripción | Estado |
| :---: | :--- | :---: |
| **1** | **Arquitectura Base, Autenticación y Roles**: MVC propio, conexión DB, ActiveRecord, Gulp/SCSS/JS, login, registro, recuperación de clave y control de acceso por rol (`dueño`, `empleado`, `repartidor`, `cliente`). | 🟢 **Completada** |
| **2** | **Gestión de Comercio y Catálogo**: Configuración del local (coordenadas con pin Leaflet, horarios, logo WebP, costos de envío y cancelación), CRUD de categorías, productos y opciones/variantes. | 🟢 **Completada** |
| **3** | **Insumos, Recetas y Control de Stock**: Gestión de materia prima, recetas por producto, trazabilidad atómica de movimientos y alertas de stock mínimo. | 🟢 **Completada** |
| **4** | **Carrito y Flujo de Pedidos (Invitado y Con Cuenta)**: Navegación del menú, carrito dinámico, selector de ubicación con pin Leaflet + dirección de texto, cálculo Haversine de envío y confirmación server-side. | 🟢 **Completada** |
| **5** | **Pagos y Comprobantes**: Integración Mercado Pago Checkout Pro (preferencias y webhooks) + cobro en efectivo, y generación de comprobantes internos. | 🟢 **Completada** |
| **6** | **Panel Operativo de Cocina y Caja**: Despacho en tiempo real, actualización de estados de preparación y control de caja diario. | 🟢 **Completada** |
| **7** | **Módulo de Repartidores**: Asignación de despachos, vista móvil para repartidores y confirmación de entregas. | 🟢 **Completada** |
| **8** | **Reportes y Métricas de Negocio**: Ventas por período, platos más pedidos, balance de insumos consumidos y rendimiento por repartidor. | 🟢 **Completada** |
| **9** | **Pulido UX, Notificaciones y Despliegue**: Optimización mobile-first, notificaciones para clientes y checklist de producción en hosting compartido. | 🟢 **Completada** |

---

## 8. Convenciones de desarrollo y flujo de trabajo

- **Paso a paso**: Cambios pequeños, concretos y testeados de forma unitaria.
- **Resumen tras cada paso**: Detalle de archivos creados/modificados y comandos o URLs para validarlo.
- **Confirmación explícita**: Validación con el desarrollador antes de avanzar a etapas o pasos subsiguientes.
- **Estricto respeto de convenciones**: Sin frameworks foráneos, sin estilos ni scripts inline en vistas, BEM estricto en SCSS y arquitectura limpia.
