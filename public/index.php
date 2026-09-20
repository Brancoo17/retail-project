<?php

require_once __DIR__ . '/../includes/app.php';

use MVC\Router;
use Controllers\PaginasController;
use Controllers\LoginController;
use Controllers\AdminController;
use Controllers\CocinaController;
use Controllers\RepartidorController;
use Controllers\ClienteController;
use Controllers\ConfiguracionController;
use Controllers\CategoriaController;
use Controllers\ProductoController;
use Controllers\InsumoController;
use Controllers\PedidoController;
use Controllers\PagoController;
use Controllers\ComprobanteController;
use Controllers\AderezoController;
use Controllers\RepartidorAdminController;
use Controllers\ReporteController;
use Controllers\EmpleadoAdminController;
use Controllers\CuentaController;

$router = new Router();

// ==========================================
// PÁGINAS PÚBLICAS & FLUJO DE COMPRA (ETAPAS 4 Y 5)
// ==========================================
$router->get('/', [PaginasController::class, 'index']);
$router->get('/menu', [PedidoController::class, 'menu']);
$router->get('/checkout', [PedidoController::class, 'checkout']);
$router->post('/pedido/crear', [PedidoController::class, 'crear']);
$router->get('/pedido/seguimiento', [PedidoController::class, 'seguimiento']);
$router->post('/pedido/cancelar', [PedidoController::class, 'cancelar']);
$router->post('/api/calcular-envio', [PedidoController::class, 'calcularEnvio']);
$router->get('/api/pedido-estado', [PedidoController::class, 'estado']);

// Pagos y Mercado Pago Checkout Pro (Etapa 5)
$router->post('/api/webhook/mercadopago', [PagoController::class, 'webhook']);
$router->get('/pago/exitoso', [PagoController::class, 'exitoso']);
$router->get('/pago/fallido', [PagoController::class, 'fallido']);
$router->get('/pago/pendiente', [PagoController::class, 'pendiente']);
$router->post('/pago/simular', [PagoController::class, 'simular']);
$router->post('/pago/cobrar-efectivo', [PagoController::class, 'registrarCobroEfectivo']);

// Comprobantes Internos (Etapa 5)
$router->get('/comprobante', [ComprobanteController::class, 'ver']);

// ==========================================
// AUTENTICACIÓN Y CUENTAS
// ==========================================
$router->get('/login', [LoginController::class, 'login']);
$router->post('/login', [LoginController::class, 'login']);
$router->get('/logout', [LoginController::class, 'logout']);

$router->get('/crear-cuenta', [LoginController::class, 'crear']);
$router->post('/crear-cuenta', [LoginController::class, 'crear']);
$router->get('/mensaje', [LoginController::class, 'mensaje']);
$router->get('/confirmar-cuenta', [LoginController::class, 'confirmar']);

$router->get('/olvide', [LoginController::class, 'olvide']);
$router->post('/olvide', [LoginController::class, 'olvide']);
$router->get('/recuperar', [LoginController::class, 'recuperar']);
$router->post('/recuperar', [LoginController::class, 'recuperar']);

// ==========================================
// PANELES Y CONTROL DE ACCESO POR ROL
// ==========================================
$router->get('/admin', [AdminController::class, 'index']);
$router->get('/admin/configuracion', [ConfiguracionController::class, 'index']);
$router->post('/admin/configuracion', [ConfiguracionController::class, 'guardar']);

// Categorías del Menú (dueño)
$router->get('/admin/categorias', [CategoriaController::class, 'index']);
$router->get('/admin/categorias/crear', [CategoriaController::class, 'crear']);
$router->post('/admin/categorias/crear', [CategoriaController::class, 'crear']);
$router->get('/admin/categorias/actualizar', [CategoriaController::class, 'actualizar']);
$router->post('/admin/categorias/actualizar', [CategoriaController::class, 'actualizar']);
$router->post('/admin/categorias/eliminar', [CategoriaController::class, 'eliminar']);

// Salsas y Aderezos del Menú (dueño)
$router->get('/admin/aderezos', [AderezoController::class, 'index']);
$router->post('/admin/aderezos/crear', [AderezoController::class, 'crear']);
$router->post('/admin/aderezos/actualizar', [AderezoController::class, 'actualizar']);
$router->post('/admin/aderezos/eliminar', [AderezoController::class, 'eliminar']);

// Catálogo de Productos (dueño)
$router->get('/admin/productos', [ProductoController::class, 'index']);
$router->get('/admin/productos/crear', [ProductoController::class, 'crear']);
$router->post('/admin/productos/crear', [ProductoController::class, 'crear']);
$router->get('/admin/productos/actualizar', [ProductoController::class, 'actualizar']);
$router->post('/admin/productos/actualizar', [ProductoController::class, 'actualizar']);
$router->post('/admin/productos/eliminar', [ProductoController::class, 'eliminar']);

// Opciones / Variantes de Productos
$router->post('/admin/productos/opciones/crear', [ProductoController::class, 'crearOpcion']);
$router->post('/admin/productos/opciones/eliminar', [ProductoController::class, 'eliminarOpcion']);

// Receta / Consumo de Insumos por Producto
$router->post('/admin/productos/receta/agregar', [ProductoController::class, 'agregarReceta']);
$router->post('/admin/productos/receta/eliminar', [ProductoController::class, 'eliminarReceta']);

// Insumos y Materia Prima (dueño)
$router->get('/admin/insumos', [InsumoController::class, 'index']);
$router->get('/admin/insumos/crear', [InsumoController::class, 'crear']);
$router->post('/admin/insumos/crear', [InsumoController::class, 'crear']);
$router->get('/admin/insumos/actualizar', [InsumoController::class, 'actualizar']);
$router->post('/admin/insumos/actualizar', [InsumoController::class, 'actualizar']);
$router->post('/admin/insumos/eliminar', [InsumoController::class, 'eliminar']);

// Trazabilidad y Movimientos de Stock
$router->get('/admin/insumos/movimientos', [InsumoController::class, 'movimientos']);
$router->get('/admin/insumos/movimientos/crear', [InsumoController::class, 'nuevoMovimiento']);
$router->post('/admin/insumos/movimientos/crear', [InsumoController::class, 'nuevoMovimiento']);

// Consola Operativa de Cocina, Despacho y Caja
$router->get('/cocina', [CocinaController::class, 'index']);
$router->post('/cocina/pedido-presencial', [CocinaController::class, 'crearPedidoPresencial']);
$router->post('/cocina/cambiar-estado', [CocinaController::class, 'cambiarEstado']);
$router->post('/cocina/cobrar-retiro', [CocinaController::class, 'cobrarRetiro']);
$router->post('/cocina/rendir-repartidor', [CocinaController::class, 'rendirRepartidor']);
$router->post('/cocina/asignar-repartidor', [CocinaController::class, 'asignarRepartidor']);
$router->post('/cocina/cancelar-pedido', [CocinaController::class, 'cancelarPedido']);
$router->get('/api/cocina/pedidos', [CocinaController::class, 'apiPedidos']);
$router->get('/cocina/caja', [CocinaController::class, 'caja']);
$router->post('/cocina/caja/abrir', [CocinaController::class, 'abrirCaja']);
$router->post('/cocina/caja/cerrar', [CocinaController::class, 'cerrarCaja']);

// Gestión de Repartidores (dueño)
$router->get('/admin/repartidores', [RepartidorAdminController::class, 'index']);
$router->get('/admin/repartidores/crear', [RepartidorAdminController::class, 'crear']);
$router->post('/admin/repartidores/crear', [RepartidorAdminController::class, 'crear']);
$router->get('/admin/repartidores/actualizar', [RepartidorAdminController::class, 'actualizar']);
$router->post('/admin/repartidores/actualizar', [RepartidorAdminController::class, 'actualizar']);
$router->post('/admin/repartidores/eliminar', [RepartidorAdminController::class, 'eliminar']);

// Gestión de Personal de Cocina & Caja (dueño)
$router->get('/admin/empleados', [EmpleadoAdminController::class, 'index']);
$router->get('/admin/empleados/crear', [EmpleadoAdminController::class, 'crear']);
$router->post('/admin/empleados/crear', [EmpleadoAdminController::class, 'crear']);
$router->get('/admin/empleados/actualizar', [EmpleadoAdminController::class, 'actualizar']);
$router->post('/admin/empleados/actualizar', [EmpleadoAdminController::class, 'actualizar']);
$router->post('/admin/empleados/eliminar', [EmpleadoAdminController::class, 'eliminar']);

// Reportes & Analítica de Negocio (Etapa 8)
$router->get('/admin/reportes', [ReporteController::class, 'index']);

// Consola de Repartidor (móvil)
$router->get('/repartidor', [RepartidorController::class, 'index']);
$router->post('/repartidor/confirmar-entrega', [RepartidorController::class, 'confirmarEntrega']);

$router->get('/cliente', [ClienteController::class, 'index']);

// Mi Cuenta & Configuración de Perfil (todos los roles)
$router->get('/cuenta', [CuentaController::class, 'index']);
$router->post('/cuenta', [CuentaController::class, 'index']);

$router->comprobarRutas();
