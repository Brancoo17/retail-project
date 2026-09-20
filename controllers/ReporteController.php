<?php

namespace Controllers;

use MVC\Router;
use Model\Pedido;
use Model\PedidoDetalle;
use Model\MovimientoStock;
use Model\PedidoRepartidor;

class ReporteController {
    /**
     * Panel principal de reportes y métricas de negocio
     */
    public static function index(Router $router): void {
        isDueno();

        $comercioId = 1;
        $periodo = $_GET['periodo'] ?? 'mes_actual';
        $fechaInicio = $_GET['fecha_inicio'] ?? '';
        $fechaFin = $_GET['fecha_fin'] ?? '';

        // Calcular rango de fechas según el filtro seleccionado
        $hoy = date('Y-m-d');
        switch($periodo) {
            case 'hoy':
                $inicio = "{$hoy} 00:00:00";
                $fin = "{$hoy} 23:59:59";
                $nombrePeriodo = 'Hoy (' . date('d/m/Y') . ')';
                break;

            case '7dias':
                $hace7Dias = date('Y-m-d', strtotime('-6 days'));
                $inicio = "{$hace7Dias} 00:00:00";
                $fin = "{$hoy} 23:59:59";
                $nombrePeriodo = 'Últimos 7 días (' . date('d/m', strtotime($hace7Dias)) . ' al ' . date('d/m/Y') . ')';
                break;

            case '30dias':
                $hace30Dias = date('Y-m-d', strtotime('-29 days'));
                $inicio = "{$hace30Dias} 00:00:00";
                $fin = "{$hoy} 23:59:59";
                $nombrePeriodo = 'Últimos 30 días';
                break;

            case 'mes_anterior':
                $primerDiaMesAnt = date('Y-m-01', strtotime('first day of last month'));
                $ultimoDiaMesAnt = date('Y-m-t', strtotime('last day of last month'));
                $inicio = "{$primerDiaMesAnt} 00:00:00";
                $fin = "{$ultimoDiaMesAnt} 23:59:59";
                $nombrePeriodo = 'Mes Anterior (' . date('F Y', strtotime($primerDiaMesAnt)) . ')';
                break;

            case 'personalizado':
                if(!empty($fechaInicio) && !empty($fechaFin)) {
                    $inicio = "{$fechaInicio} 00:00:00";
                    $fin = "{$fechaFin} 23:59:59";
                    $nombrePeriodo = date('d/m/Y', strtotime($fechaInicio)) . ' al ' . date('d/m/Y', strtotime($fechaFin));
                } else {
                    $periodo = 'mes_actual';
                    $inicio = date('Y-m-01 00:00:00');
                    $fin = date('Y-m-t 23:59:59');
                    $nombrePeriodo = 'Este Mes (' . date('m/Y') . ')';
                }
                break;

            case 'mes_actual':
            default:
                $periodo = 'mes_actual';
                $inicio = date('Y-m-01 00:00:00');
                $fin = date('Y-m-t 23:59:59');
                $nombrePeriodo = 'Este Mes (' . date('m/Y') . ')';
                break;
        }

        // Consultas analíticas
        $metricasVentas = Pedido::metricasVentas($inicio, $fin, $comercioId);
        $ventasPorDia = Pedido::ventasPorDia($inicio, $fin, $comercioId);
        $desgloseMetodos = Pedido::desgloseMetodosPago($inicio, $fin, $comercioId);
        $desgloseTipos = Pedido::desgloseTiposEntrega($inicio, $fin, $comercioId);
        $rankingProductos = PedidoDetalle::rankingProductos($inicio, $fin, $comercioId, 10);
        $balanceInsumos = MovimientoStock::balanceInsumosPeriodo($inicio, $fin, $comercioId);
        $metricasRepartidores = PedidoRepartidor::metricasRendimiento($inicio, $fin, $comercioId);

        // Encontrar valor máximo para la escala del gráfico de barras
        $maxVentaDia = 0.0;
        foreach($ventasPorDia as $vd) {
            if($vd['total'] > $maxVentaDia) {
                $maxVentaDia = $vd['total'];
            }
        }

        $router->render('admin/reportes/index', [
            'titulo' => 'Reportes & Estadísticas de Negocio',
            'periodo' => $periodo,
            'nombrePeriodo' => $nombrePeriodo,
            'fechaInicio' => $fechaInicio,
            'fechaFin' => $fechaFin,
            'inicio' => $inicio,
            'fin' => $fin,
            'metricasVentas' => $metricasVentas,
            'ventasPorDia' => $ventasPorDia,
            'maxVentaDia' => $maxVentaDia,
            'desgloseMetodos' => $desgloseMetodos,
            'desgloseTipos' => $desgloseTipos,
            'rankingProductos' => $rankingProductos,
            'balanceInsumos' => $balanceInsumos,
            'metricasRepartidores' => $metricasRepartidores,
            'tipo' => 'admin'
        ]);
    }
}
