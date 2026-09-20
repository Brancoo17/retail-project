# DISEÑO-INICIAL.md — retail-project

Sistema de Gestión para Comercios de Comida con Delivery.

Documento de arranque, pensado para pasarse completo al agente en Antigravity y evolucionar hacia un `CONTEXT.md` una vez generado el scaffold, igual que se hizo en `gym-project`.

---

## 1. Qué es el proyecto

Sistema de gestión integral para comercios de comida con delivery (pizzerías, rotiserías, hamburgueserías, etc.). Resuelve tres dolores típicos de estos negocios:

1. **Pedidos desordenados** (WhatsApp, teléfono, mostrador, todo mezclado sin trazabilidad).
2. **Inventario a ciegas** (no saben cuánto insumo consumen por producto vendido, se quedan sin stock sin avisar).
3. **Sin control de caja/facturación** (no hay reporte claro de qué se vendió, cuánto, y a qué costo real).

Se agrega gestión de repartidores propios como cuarto pilar, ya que definiste que el delivery se maneja dentro del sistema.

Sigue el mismo stack, estructura y convenciones que tus proyectos anteriores (*gym-project, AppSalon, Ecommerce, BienesRaices* — GitHub: `Brancoo17`).

Antes de generar el scaffold, el agente debe revisar la estructura de **gym-project** y **AppSalon** en `https://github.com/Brancoo17` (son los dos proyectos más completos y maduros), para partir de las mismas convenciones de carpetas, MVC, ActiveRecord, SCSS/Gulp y JS vanilla en vez de inventar una estructura nueva.

---

## 2. Stack y arquitectura (heredado de gym-project, no negociable)

- **Backend**: PHP 8+ MVC propio en la raíz del proyecto (sin carpeta `app/`).
- **Base de datos**: MySQL. Un único archivo `.sql` como fuente de verdad del modelo.
- **Frontend & Estilos**: SCSS modular (BEM) + Gulp → `public/build/css/app.css`. Sin estilos inline (excepto anchos dinámicos de barras calculados en servidor).
- **JavaScript**: Vanilla, centralizado en `src/js/app.js`, compilado con Gulp a `public/build/js/app.js`, modularizado por vista con guard clauses. Sin `<script>` en las vistas salvo inyección de datos JSON generados por PHP.
- **Enrutamiento**: `Router.php` como despachador + `public/index.php` como Front Controller.
- **Autoload**: Composer PSR-4 (`MVC\`, `Controllers\`, `Model\`, `Classes\`).
- **Capa de datos**: ActiveRecord propio (`models/ActiveRecord.php`), sin ORM externo.
- **Pagos**: Mercado Pago Checkout Pro (mismo patrón que gym-project) + efectivo en mostrador/entrega.
- **Email**: PHPMailer (confirmaciones de pedido, recuperación de clave).
- **Seguridad**: validaciones críticas (stock, precios, estados de pedido) siempre server-side. Aplicar desde el día 1 las lecciones de la auditoría de gym-project: `escape_string()` en queries dinámicas, tokens con `random_bytes(32)`, sesión endurecida, `.htaccess` bloqueando `.env`/`.git`/`sql/`.

## 3. Decisión de arquitectura: multi-tenant "ligero"

Se diseña el modelo de datos con una tabla `comercios` desde el día 1, y **toda tabla relevante lleva `comercio_id`**. Esto permite:

- Operar hoy con un solo comercio sin ninguna complejidad extra.
- Escalar a SaaS más adelante (varios comercios en la misma base) sin migrar el esquema.
- Si algún comercio grande necesita aislamiento total, se lo migra a su propia base de datos el día que haga falta — decisión de infraestructura, no de modelo de datos.

No se implementa aún: login de "super-admin" de la plataforma, planes de suscripción, ni billing del propio SaaS. Eso queda para una fase posterior, igual que en gym-project.

**Sucursales**: por ahora 1 comercio = 1 local. No se crea tabla `sucursales` en esta etapa. Como `comercio_id` ya está en todas las tablas relevantes, el día que un comercio necesite varias sucursales, se agrega una tabla `sucursales` (con su propia dirección, stock e insumos) y una FK `sucursal_id` en `pedidos`/`movimientos_stock`, sin romper el modelo actual.

---

## 4. Modelo de datos (borrador inicial)

| Entidad | Tabla | Propósito y relaciones clave |
|---|---|---|
| **Comercios** | `comercios` | Tenant. Nombre, dirección (+ `lat`/`lng` del local, cargados una vez por el dueño con un pin en un mapa), horario de atención, configuración white-label (logo, colores, WhatsApp, redes), `km_envio_gratis` (configurable por el admin), `costo_envio_por_km`, estado (activo/suspendido). |
| **Usuarios** | `usuarios` | Cuentas con roles (`dueño`, `empleado`, `repartidor`, `cliente`), `comercio_id`. El rol `cliente` es **opcional**: solo existe si el cliente decidió crear cuenta para ver su historial de pedidos. Dueño/empleado/repartidor sí requieren cuenta y pertenecen a un solo comercio. |
| **Categorías** | `categorias` | Categorías del menú por comercio (pizzas, bebidas, postres). |
| **Productos** | `productos` | Nombre, precio, descripción, imagen, categoría, `comercio_id`, activo/disponible. |
| **Variantes/Opciones** | `producto_opciones` | Tamaños, extras, adicionales con precio propio (ej. "Grande +$500", "Extra queso +$200"). |
| **Insumos** | `insumos` | Materia prima (harina, queso, gaseosa) con stock actual, unidad de medida, stock mínimo de alerta, `comercio_id`. |
| **Recetas** | `producto_insumos` | Detalle de cuánto insumo consume cada producto al venderse. Habilita descuento automático de stock. |
| **Movimientos de Stock** | `movimientos_stock` | Historial de entradas (compras) y salidas (ventas, mermas) por insumo, con motivo y usuario responsable. |
| **Pedidos** | `pedidos` | Cabecera: `comercio_id`, `usuario_id` (nullable — null si es invitado), datos de contacto del pedido (`nombre_contacto`, `telefono_contacto`, siempre presentes aunque haya cuenta), dirección de entrega + `lat`/`lng` (pineados por el cliente en el mapa), `distancia_km` (Haversine, calculada al confirmar), `costo_envio` (0 si `distancia_km` ≤ `km_envio_gratis` del comercio, calculado sobre el excedente si la supera), tipo (delivery/retiro en local), estado (`pendiente`, `confirmado`, `en_preparacion`, `en_camino`, `entregado`, `cancelado`), `fecha_confirmado` (para calcular la ventana de cancelación), método de pago, total. |
| **Detalle de Pedido** | `pedido_detalle` | Productos + opciones elegidas + cantidad + subtotal por línea. |
| **Repartidores-Pedidos** | `pedido_repartidor` | Asignación de un repartidor a un pedido, con hora de asignación, hora de entrega y estado de entrega. |
| **Pagos** | `pagos` | Igual patrón que gym-project: método (`mercadopago`, `efectivo`), estado, `mp_preference_id`/`mp_payment_id`, fecha de pago. |
| **Comprobantes** | `comprobantes` | Ticket interno numerado correlativamente por comercio (no fiscal). Vinculado 1 a 1 con `pedidos`. |
| **Direcciones** | `direcciones_cliente` | Direcciones guardadas, asociadas a `usuario_id` (solo aplica a clientes con cuenta) para reutilizar en próximos pedidos. Invitados cargan la dirección directo en el pedido, sin persistirla aparte. |

> **Regla de negocio a validar siempre server-side**: el stock de un insumo nunca puede quedar negativo; si un producto no tiene insumos suficientes, no debe poder confirmarse el pedido (o debe marcarse "sin stock" automáticamente). El stock se descuenta al **confirmar** el pedido (reserva); si el pedido se cancela después, se revierte con un `movimiento_stock` de tipo "cancelación".

> **Cálculo de envío (Haversine)**: tanto el comercio (una sola vez, al configurar el local) como el cliente (en cada pedido) marcan su ubicación con un pin en un mapa (Leaflet + tiles de OpenStreetMap, gratis y sin backend propio) para obtener `lat`/`lng` directamente, sin necesidad de geocodificar direcciones de texto. Con esos dos puntos, `distancia_km` se calcula con la fórmula de Haversine (distancia en línea recta) directamente en PHP, sin depender de ningún servicio externo. Si `distancia_km ≤ comercios.km_envio_gratis` → envío gratis. Si la supera → `costo_envio = (distancia_km - km_envio_gratis) * costo_envio_por_km` (se cobra solo el excedente). Ventaja: no requiere VPS ni Docker, funciona en cualquier hosting con PHP, incluido shared hosting. Limitación conocida y aceptada por ahora: Haversine da distancia en línea recta, no la distancia real de trayecto (no contempla calles, ríos, etc.), así que puede subestimar el costo real de envío en casos puntuales — si eso se vuelve un problema, ahí sí se evalúa sumar OSRM sobre una VPS.

> **Cancelación**: el cliente solo puede cancelar un pedido ya confirmado dentro de una ventana de tiempo configurable (a definir cuántos minutos) contada desde `pedidos.fecha_confirmado`. Pasada esa ventana, solo el comercio (dueño/empleado) puede cancelarlo. Toda cancelación revierte el stock reservado.

---

## 5. Roles y flujos

### Roles
- **Dueño/Admin**: gestiona catálogo, insumos, recetas, repartidores, ve reportes y caja del comercio.
- **Empleado (cocina/caja)**: ve pedidos entrantes, cambia estados (confirmar, preparar, listo), registra pagos en efectivo.
- **Repartidor**: ve pedidos asignados, actualiza estado de entrega (en camino, entregado).
- **Cliente**: navega el menú, arma el pedido, elige dirección y método de pago, sigue el estado de su pedido.

### Flujo de pedido (borrador)
1. Cliente arma el carrito **como invitado (teléfono + dirección) o con cuenta**, marca su ubicación con un pin en el mapa → el sistema calcula `distancia_km` (Haversine) y `costo_envio` (gratis si está dentro del radio configurado por el admin del comercio).
2. Confirma el pedido → se crea `pedido` en estado `pendiente` (con `usuario_id` null si fue como invitado).
3. Se descuenta stock reservado (o se valida disponibilidad) según `producto_insumos`.
4. Pago: Mercado Pago (igual patrón que gym-project, webhook activa el pedido) o efectivo (se marca al entregar).
5. Empleado confirma y pasa a `en_preparacion`.
6. Dueño/empleado asigna repartidor → `pedido_repartidor`, estado pasa a `en_camino`.
7. Repartidor marca `entregado` → se genera `comprobante` y se descuenta stock real (si no se descontó antes).

> **Cancelación**: entre los pasos 2 y 5, el cliente puede cancelar el pedido solo si todavía está dentro de la ventana de tiempo definida (contada desde que se confirmó). Pasada esa ventana, la cancelación queda a criterio del comercio.

---

## 6. Qué NO hacer (heredado de gym-project)

- ❌ No introducir frameworks PHP ni JS (Laravel, Symfony, React, Vue, Tailwind, etc.).
- ❌ No estilos ni JS inline en las vistas.
- ❌ No implementar facturación fiscal (AFIP/ARCA) en esta fase — quedó descartado explícitamente.
- ❌ No construir infraestructura de "super-admin SaaS" (planes, billing de la plataforma) todavía — solo el modelo de datos queda preparado para eso.
- ❌ No reescribir desde cero, no saltar etapas sin confirmación, no asumir decisiones de arquitectura sin consultar — mismas reglas de trabajo que ya usás.

---

## 7. Plan de etapas propuesto (borrador, a ajustar juntos)

| Etapa | Descripción |
|---|---|
| 1 | Arquitectura base, autenticación y roles (dueño, empleado, repartidor, cliente) |
| 2 | Gestión de comercio (configuración white-label) + catálogo (categorías, productos, opciones) |
| 3 | Gestión de insumos, recetas y stock (con alertas de stock mínimo) |
| 4 | Carrito y flujo de pedido del cliente (sin pago aún) |
| 5 | Pagos: Mercado Pago Checkout Pro + efectivo, y comprobante interno |
| 6 | Panel de cocina/caja: gestión de estados de pedido en tiempo real |
| 7 | Módulo de repartidores: asignación y seguimiento de entregas |
| 8 | Reportes: ventas por período, productos más vendidos, consumo de insumos, rendimiento por repartidor |
| 9 | Pulido UX (mobile-first, notificaciones de estado al cliente) y despliegue |

---

## 8. Cómo me gusta trabajar (heredado)

- Paso a paso, cambios pequeños y concretos.
- Resumen tras cada paso: qué se creó/modificó y cómo probarlo.
- Confirmación explícita antes de avanzar.
- Consultar antes de asumir decisiones de arquitectura o de negocio.

---

## 9. Decisiones ya tomadas

- **Repo**: `retail-project`.
- **Cliente**: pedido como invitado (teléfono + dirección) habilitado; cuenta opcional para ver historial.
- **Sucursales**: 1 comercio = 1 local por ahora; modelo preparado para agregar sucursales después (ver sección 3).
- **Envío**: gratis hasta X km (configurable por el admin del comercio); si se supera, el costo se cobra **solo sobre el excedente**.
- **Cálculo de distancia**: fórmula **Haversine** (línea recta) sobre coordenadas cargadas con un pin en un mapa (Leaflet + OpenStreetMap), sin geocodificación de direcciones de texto ni servicios externos (se descartó OSM+OSRM self-hosteado por requerir VPS con Docker en vez de hosting compartido).
- **Stock**: se descuenta al **confirmar** el pedido (reserva), revirtiéndose si se cancela.
- **Cancelación**: el cliente solo puede cancelar dentro de una ventana de tiempo configurable después de confirmado; pasada esa ventana, solo el comercio puede hacerlo.
- **Hosting**: al usar Haversine (sin OSRM/Nominatim), el sistema queda compatible con hosting compartido de Hostinger, sin necesitar forzosamente un plan VPS solo por este feature.

## 10. Preguntas abiertas para la próxima sesión

- ¿Cuántos minutos/horas dura la ventana de cancelación para el cliente?
- ¿El pin de ubicación del cliente en el mapa reemplaza completamente el campo de dirección en texto, o se piden ambos (uno para mostrar en el panel de cocina/repartidor, otro para el cálculo de distancia)?
