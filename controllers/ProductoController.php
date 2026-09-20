<?php

namespace Controllers;

use MVC\Router;
use Model\Producto;
use Model\Categoria;
use Model\ProductoOpcion;
use Model\Insumo;
use Model\ProductoInsumo;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager as Image;

class ProductoController {

    public static function index(Router $router): void {
        isDueno();

        $resultado = $_GET['resultado'] ?? null;
        if($resultado === '1') {
            Producto::setAlerta('exito', 'Producto creado con éxito');
        } elseif($resultado === '2') {
            Producto::setAlerta('exito', 'Producto actualizado con éxito');
        } elseif($resultado === '3') {
            Producto::setAlerta('exito', 'Producto eliminado correctamente');
        }

        $alertas = Producto::getAlertas();
        $productos = Producto::porComercio(1);
        $categorias = Categoria::porComercio(1);

        $router->render('admin/productos/index', [
            'titulo' => 'Catálogo de Productos',
            'productos' => $productos,
            'categorias' => $categorias,
            'alertas' => $alertas,
            'tipo' => 'admin'
        ]);
    }

    public static function crear(Router $router): void {
        isDueno();

        $producto = new Producto();
        $categorias = Categoria::porComercio(1, true);
        $alertas = [];

        if($_SERVER['REQUEST_METHOD'] === 'POST') {
            $producto->sincronizar($_POST['producto'] ?? []);
            $producto->comercio_id = 1;

            $imgProducto = null;
            $nombreImagen = '';

            try {
                if(!empty($_FILES['imagen']['tmp_name']) && is_uploaded_file($_FILES['imagen']['tmp_name'])) {
                    $manager = new Image(new Driver());
                    $contenido = file_get_contents($_FILES['imagen']['tmp_name']);
                    $imgProducto = $manager->decode($contenido)->scaleDown(800, 800);
                    $nombreImagen = 'prod_' . md5(uniqid((string)rand(), true)) . '.webp';
                }
            } catch (\Throwable $e) {
                Producto::setAlerta('error', 'Error al procesar la imagen: ' . $e->getMessage());
            }

            $alertas = array_merge(Producto::getAlertas(), $producto->validar());

            if(empty($alertas)) {
                if(!is_dir(CARPETA_IMAGENES)) {
                    mkdir(CARPETA_IMAGENES, 0777, true);
                }

                if($imgProducto && $nombreImagen) {
                    $imgProducto->save(CARPETA_IMAGENES . $nombreImagen);
                    $producto->setImagen($nombreImagen);
                }

                $resultado = $producto->crear();

                if($resultado['resultado']) {
                    header("Location: /admin/productos/actualizar?id={$resultado['id']}&resultado=1");
                    exit;
                } else {
                    Producto::setAlerta('error', 'Error al guardar el producto');
                    $alertas = Producto::getAlertas();
                }
            }
        }

        $router->render('admin/productos/crear', [
            'titulo' => 'Nuevo Producto',
            'producto' => $producto,
            'categorias' => $categorias,
            'alertas' => $alertas,
            'tipo' => 'admin'
        ]);
    }

    public static function actualizar(Router $router): void {
        isDueno();

        $id = (int)($_GET['id'] ?? 0);
        $producto = Producto::find($id);

        if(!$producto || $producto->comercio_id !== 1) {
            header('Location: /admin/productos');
            exit;
        }

        $categorias = Categoria::porComercio(1);
        $opciones = $producto->opciones();
        $receta = ProductoInsumo::porProducto((int)$producto->id);
        $insumos = Insumo::porComercio(1);
        $resultado = $_GET['resultado'] ?? null;

        if($resultado === '1') {
            Producto::setAlerta('exito', '¡Producto creado! Ahora puedes configurar variantes y la receta de insumos.');
        } elseif($resultado === '2') {
            Producto::setAlerta('exito', 'Producto actualizado correctamente');
        } elseif($resultado === 'opcion_creada') {
            Producto::setAlerta('exito', 'Opción o adicional agregado con éxito');
        } elseif($resultado === 'opcion_eliminada') {
            Producto::setAlerta('exito', 'Opción eliminada');
        } elseif($resultado === 'receta_agregada') {
            Producto::setAlerta('exito', 'Insumo agregado a la receta del producto');
        } elseif($resultado === 'receta_eliminada') {
            Producto::setAlerta('exito', 'Insumo eliminado de la receta');
        } elseif($resultado === 'receta_error') {
            Producto::setAlerta('error', 'Error al procesar la receta: datos incompletos o insumo ya existente');
        }

        $alertas = Producto::getAlertas();

        if($_SERVER['REQUEST_METHOD'] === 'POST') {
            $producto->sincronizar($_POST['producto'] ?? []);

            $imgProducto = null;
            $nombreImagen = '';

            try {
                if(!empty($_FILES['imagen']['tmp_name']) && is_uploaded_file($_FILES['imagen']['tmp_name'])) {
                    $manager = new Image(new Driver());
                    $contenido = file_get_contents($_FILES['imagen']['tmp_name']);
                    $imgProducto = $manager->decode($contenido)->scaleDown(800, 800);
                    $nombreImagen = 'prod_' . md5(uniqid((string)rand(), true)) . '.webp';
                }
            } catch (\Throwable $e) {
                Producto::setAlerta('error', 'Error al procesar la imagen: ' . $e->getMessage());
            }

            $alertas = array_merge(Producto::getAlertas(), $producto->validar());

            if(empty($alertas)) {
                if(!is_dir(CARPETA_IMAGENES)) {
                    mkdir(CARPETA_IMAGENES, 0777, true);
                }

                if($imgProducto && $nombreImagen) {
                    $imgProducto->save(CARPETA_IMAGENES . $nombreImagen);
                    $producto->setImagen($nombreImagen);
                }

                $resultadoUpdate = $producto->actualizar();

                if($resultadoUpdate) {
                    header("Location: /admin/productos/actualizar?id={$producto->id}&resultado=2");
                    exit;
                } else {
                    Producto::setAlerta('error', 'Error al actualizar el producto');
                    $alertas = Producto::getAlertas();
                }
            }
        }

        $router->render('admin/productos/actualizar', [
            'titulo' => 'Editar Producto',
            'producto' => $producto,
            'categorias' => $categorias,
            'opciones' => $opciones,
            'receta' => $receta,
            'insumos' => $insumos,
            'alertas' => $alertas,
            'tipo' => 'admin'
        ]);
    }

    public static function eliminar(): void {
        isDueno();

        if($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = (int)($_POST['id'] ?? 0);
            $producto = Producto::find($id);

            if($producto && $producto->comercio_id === 1) {
                $producto->eliminar();
            }
        }

        header('Location: /admin/productos?resultado=3');
        exit;
    }

    public static function crearOpcion(): void {
        isDueno();

        if($_SERVER['REQUEST_METHOD'] === 'POST') {
            $productoId = (int)($_POST['producto_id'] ?? 0);
            $producto = Producto::find($productoId);

            if($producto && $producto->comercio_id === 1) {
                $opcion = new ProductoOpcion($_POST['opcion'] ?? []);
                $opcion->producto_id = $productoId;
                $alertas = $opcion->validar();

                if(empty($alertas)) {
                    $opcion->crear();
                    header("Location: /admin/productos/actualizar?id={$productoId}&resultado=opcion_creada");
                    exit;
                }
            }
        }

        header('Location: /admin/productos');
        exit;
    }

    public static function eliminarOpcion(): void {
        isDueno();

        if($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = (int)($_POST['id'] ?? 0);
            $opcion = ProductoOpcion::find($id);

            if($opcion) {
                $producto = Producto::find($opcion->producto_id);
                if($producto && $producto->comercio_id === 1) {
                    $productoId = $opcion->producto_id;
                    $opcion->eliminar();
                    header("Location: /admin/productos/actualizar?id={$productoId}&resultado=opcion_eliminada");
                    exit;
                }
            }
        }

        header('Location: /admin/productos');
        exit;
    }

    public static function agregarReceta(): void {
        isDueno();

        if($_SERVER['REQUEST_METHOD'] === 'POST') {
            $productoId = (int)($_POST['producto_id'] ?? 0);
            $insumoId = (int)($_POST['insumo_id'] ?? 0);
            $cantidadConsumo = (float)str_replace(',', '.', (string)($_POST['cantidad_consumo'] ?? '0.0'));

            $producto = Producto::find($productoId);
            $insumo = Insumo::find($insumoId);

            if($producto && $producto->comercio_id === 1 && $insumo && $insumo->comercio_id === 1 && $cantidadConsumo > 0) {
                // Si el insumo ya está en la receta, actualizamos la cantidad
                if(ProductoInsumo::existeEnReceta($productoId, $insumoId)) {
                    $existente = ProductoInsumo::where('producto_id', (string)$productoId);
                    // Buscar coincidencia exacta por insumo_id
                    $items = ProductoInsumo::consultarSQL("SELECT * FROM producto_insumos WHERE producto_id = {$productoId} AND insumo_id = {$insumoId} LIMIT 1");
                    if(!empty($items)) {
                        $linea = $items[0];
                        $linea->cantidad_consumo = $cantidadConsumo;
                        $linea->actualizar();
                        header("Location: /admin/productos/actualizar?id={$productoId}&resultado=receta_agregada");
                        exit;
                    }
                }

                $recetaItem = new ProductoInsumo([
                    'producto_id' => $productoId,
                    'insumo_id' => $insumoId,
                    'cantidad_consumo' => $cantidadConsumo
                ]);

                $alertas = $recetaItem->validar();
                if(empty($alertas)) {
                    $recetaItem->crear();
                    header("Location: /admin/productos/actualizar?id={$productoId}&resultado=receta_agregada");
                    exit;
                }
            }

            header("Location: /admin/productos/actualizar?id={$productoId}&resultado=receta_error");
            exit;
        }

        header('Location: /admin/productos');
        exit;
    }

    public static function eliminarReceta(): void {
        isDueno();

        if($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = (int)($_POST['id'] ?? 0);
            $item = ProductoInsumo::find($id);

            if($item) {
                $producto = Producto::find($item->producto_id);
                if($producto && $producto->comercio_id === 1) {
                    $productoId = $item->producto_id;
                    $item->eliminar();
                    header("Location: /admin/productos/actualizar?id={$productoId}&resultado=receta_eliminada");
                    exit;
                }
            }
        }

        header('Location: /admin/productos');
        exit;
    }
}
