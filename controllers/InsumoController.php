<?php

namespace Controllers;

use MVC\Router;
use Model\Insumo;
use Model\MovimientoStock;

class InsumoController {

    public static function index(Router $router): void {
        isDueno();

        $resultado = $_GET['resultado'] ?? null;
        if($resultado === '1') {
            Insumo::setAlerta('exito', 'Insumo registrado correctamente');
        } elseif($resultado === '2') {
            Insumo::setAlerta('exito', 'Insumo actualizado correctamente');
        } elseif($resultado === '3') {
            Insumo::setAlerta('exito', 'Insumo eliminado del inventario');
        } elseif($resultado === '4') {
            Insumo::setAlerta('exito', 'Movimiento de stock asentado con éxito');
        } elseif($resultado === 'error_receta') {
            Insumo::setAlerta('error', 'No es posible eliminar este insumo porque forma parte de una o más recetas activas en el catálogo');
        }

        $busqueda = $_GET['s'] ?? null;
        $insumos = Insumo::porComercio(1, $busqueda);
        $estadisticas = Insumo::estadisticas(1);
        $alertas = Insumo::getAlertas();

        $router->render('admin/insumos/index', [
            'titulo' => 'Inventario de Insumos',
            'insumos' => $insumos,
            'estadisticas' => $estadisticas,
            'busqueda' => $busqueda,
            'alertas' => $alertas,
            'tipo' => 'admin'
        ]);
    }

    public static function crear(Router $router): void {
        isDueno();

        $insumo = new Insumo();
        $alertas = [];

        if($_SERVER['REQUEST_METHOD'] === 'POST') {
            $insumo->sincronizar($_POST['insumo'] ?? []);
            $insumo->comercio_id = 1;

            $alertas = $insumo->validar();

            if(empty($alertas)) {
                $resultado = $insumo->crear();

                if($resultado['resultado']) {
                    $insumoId = (int)$resultado['id'];

                    // Si se cargó stock inicial mayor a 0, asentar movimiento de apertura
                    if($insumo->stock_actual > 0) {
                        $usuarioId = $_SESSION['id'] ?? null;
                        MovimientoStock::registrar(
                            1,
                            $insumoId,
                            $usuarioId ? (int)$usuarioId : null,
                            'compra',
                            $insumo->stock_actual,
                            'Stock inicial de apertura al crear insumo'
                        );
                    }

                    header('Location: /admin/insumos?resultado=1');
                    exit;
                } else {
                    Insumo::setAlerta('error', 'Error al crear el insumo');
                    $alertas = Insumo::getAlertas();
                }
            }
        }

        $router->render('admin/insumos/crear', [
            'titulo' => 'Nuevo Insumo',
            'insumo' => $insumo,
            'unidades' => Insumo::UNIDADES_VALIDAS,
            'alertas' => $alertas,
            'tipo' => 'admin'
        ]);
    }

    public static function actualizar(Router $router): void {
        isDueno();

        $id = (int)($_GET['id'] ?? 0);
        $insumo = Insumo::find($id);

        if(!$insumo || (int)$insumo->comercio_id !== 1) {
            header('Location: /admin/insumos');
            exit;
        }

        $alertas = [];

        if($_SERVER['REQUEST_METHOD'] === 'POST') {
            $stockPrevio = $insumo->stock_actual;
            $insumo->sincronizar($_POST['insumo'] ?? []);
            $alertas = $insumo->validar();

            if(empty($alertas)) {
                $resultado = $insumo->actualizar();

                if($resultado) {
                    // Si el usuario modificó manualmente el stock en este formulario, registrar ajuste
                    if(abs($insumo->stock_actual - $stockPrevio) > 0.0001) {
                        $diff = $insumo->stock_actual - $stockPrevio;
                        $usuarioId = $_SESSION['id'] ?? null;
                        
                        // Guardar registro de ajuste
                        $mov = new MovimientoStock([
                            'comercio_id' => 1,
                            'insumo_id' => $insumo->id,
                            'usuario_id' => $usuarioId ? (int)$usuarioId : null,
                            'tipo' => 'ajuste',
                            'cantidad' => abs($diff),
                            'motivo' => 'Ajuste manual directo desde ficha de insumo'
                        ]);
                        $mov->crear();
                    }

                    header('Location: /admin/insumos?resultado=2');
                    exit;
                } else {
                    Insumo::setAlerta('error', 'Error al actualizar el insumo');
                    $alertas = Insumo::getAlertas();
                }
            }
        }

        $router->render('admin/insumos/actualizar', [
            'titulo' => 'Editar Insumo',
            'insumo' => $insumo,
            'unidades' => Insumo::UNIDADES_VALIDAS,
            'alertas' => $alertas,
            'tipo' => 'admin'
        ]);
    }

    public static function eliminar(): void {
        isDueno();

        if($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = (int)($_POST['id'] ?? 0);
            $insumo = Insumo::find($id);

            if($insumo && (int)$insumo->comercio_id === 1) {
                // Verificar si está en uso en alguna receta
                $recetas = $insumo->recetasEnUso();
                if(!empty($recetas)) {
                    header('Location: /admin/insumos?resultado=error_receta');
                    exit;
                }

                $insumo->eliminar();
                header('Location: /admin/insumos?resultado=3');
                exit;
            }
        }

        header('Location: /admin/insumos');
        exit;
    }

    public static function movimientos(Router $router): void {
        isDueno();

        $insumoId = isset($_GET['insumo_id']) && !empty($_GET['insumo_id']) ? (int)$_GET['insumo_id'] : null;
        $tipo = isset($_GET['tipo']) && !empty($_GET['tipo']) ? (string)$_GET['tipo'] : null;

        $movimientos = MovimientoStock::porComercio(1, 150, $insumoId, $tipo);
        $insumos = Insumo::porComercio(1);
        $alertas = MovimientoStock::getAlertas();

        $router->render('admin/insumos/movimientos', [
            'titulo' => 'Historial de Movimientos de Stock',
            'movimientos' => $movimientos,
            'insumos' => $insumos,
            'insumoSeleccionado' => $insumoId,
            'tipoSeleccionado' => $tipo,
            'tipos' => MovimientoStock::TIPOS_VALIDOS,
            'alertas' => $alertas,
            'tipo' => 'admin'
        ]);
    }

    public static function nuevoMovimiento(Router $router): void {
        isDueno();

        $insumos = Insumo::porComercio(1);
        $insumoIdDefecto = (int)($_GET['insumo_id'] ?? 0);
        $alertas = [];

        if($_SERVER['REQUEST_METHOD'] === 'POST') {
            $insumoId = (int)($_POST['insumo_id'] ?? 0);
            $tipo = (string)($_POST['tipo'] ?? '');
            $cantidad = (float)str_replace(',', '.', (string)($_POST['cantidad'] ?? '0.0'));
            $motivo = trim((string)($_POST['motivo'] ?? ''));

            if(empty($insumoId)) {
                $alertas['error'][] = 'Debes seleccionar un insumo de la lista';
            }
            if(!array_key_exists($tipo, MovimientoStock::TIPOS_VALIDOS)) {
                $alertas['error'][] = 'El tipo de movimiento seleccionado no es válido';
            }
            if($cantidad <= 0) {
                $alertas['error'][] = 'La cantidad debe ser mayor a cero';
            }
            if(empty($motivo)) {
                $alertas['error'][] = 'Debes especificar un motivo o detalle del movimiento';
            }

            if(empty($alertas)) {
                $usuarioId = $_SESSION['id'] ?? null;
                $ok = MovimientoStock::registrar(
                    1,
                    $insumoId,
                    $usuarioId ? (int)$usuarioId : null,
                    $tipo,
                    $cantidad,
                    $motivo
                );

                if($ok) {
                    header('Location: /admin/insumos?resultado=4');
                    exit;
                } else {
                    $alertas = MovimientoStock::getAlertas();
                }
            }
        }

        $router->render('admin/insumos/nuevo_movimiento', [
            'titulo' => 'Registrar Movimiento de Stock',
            'insumos' => $insumos,
            'insumoIdDefecto' => $insumoIdDefecto,
            'tipos' => [
                'compra' => 'Compra / Ingreso (+)',
                'merma' => 'Merma / Descarte (-)',
                'ajuste' => 'Ajuste de Inventario (+/-)'
            ],
            'alertas' => $alertas,
            'tipo' => 'admin'
        ]);
    }
}
