<?php

namespace Classes;

class KdsHelper {

    /**
     * Parsea la cadena de opciones de un ítem y la desglosa en badges semánticos categorizados
     *
     * @param string|null $opcionesTexto Cadena ej: "Doble Cheddar | Salsas: Barbacoa, Mayonesa | Notas: Sin cebolla"
     * @return array Array de elementos con texto, tipo, icono y clase CSS
     */
    public static function parsearOpciones(?string $opcionesTexto): array {
        if(empty($opcionesTexto) || trim($opcionesTexto) === '') {
            return [];
        }

        // Separar primero por el delimitador estándar " | "
        $partes = array_map('trim', explode('|', $opcionesTexto));
        $badges = [];

        foreach($partes as $parte) {
            if($parte === '') continue;

            $lower = mb_strtolower($parte, 'UTF-8');

            // 1. Caso Salsas y Aderezos
            if(str_starts_with($parte, 'Salsas:') || str_starts_with($parte, 'Salsa:')) {
                $contenidoSalsas = trim(substr($parte, strpos($parte, ':') + 1));
                $badges[] = [
                    'texto' => 'Salsas: ' . $contenidoSalsas,
                    'tipo' => 'salsa',
                    'icono' => 'fa-solid fa-bottle-droplet',
                    'clase' => 'comanda-badge comanda-badge--salsa'
                ];
                continue;
            }

            // 2. Caso Notas especiales del cliente
            if(str_starts_with($parte, 'Notas:') || str_starts_with($parte, 'Nota:')) {
                $contenidoNota = trim(substr($parte, strpos($parte, ':') + 1));
                $notaLower = mb_strtolower($contenidoNota, 'UTF-8');

                // Si la nota contiene una exclusión o alergia crítica
                $esAlerta = (
                    str_contains($notaLower, 'sin') || 
                    str_contains($notaLower, 'no ') || 
                    str_contains($notaLower, 'alergia') || 
                    str_contains($notaLower, 'alérg') || 
                    str_contains($notaLower, 'sacar') ||
                    str_contains($notaLower, 'evitar')
                );

                $badges[] = [
                    'texto' => 'Nota: ' . $contenidoNota,
                    'tipo' => $esAlerta ? 'alerta' : 'nota',
                    'icono' => $esAlerta ? 'fa-solid fa-triangle-exclamation' : 'fa-solid fa-note-sticky',
                    'clase' => $esAlerta ? 'comanda-badge comanda-badge--alerta' : 'comanda-badge comanda-badge--nota'
                ];
                continue;
            }

            // 3. Caso Exclusiones explícitas (ej: "Sin cebolla", "Sin sal", "Sin mayonesa")
            if(
                str_starts_with($lower, 'sin ') || 
                str_starts_with($lower, 'sin-') || 
                str_contains($lower, ' sin ') ||
                str_contains($lower, 'no ') ||
                str_contains($lower, 'sacar ')
            ) {
                $badges[] = [
                    'texto' => $parte,
                    'tipo' => 'exclusion',
                    'icono' => 'fa-solid fa-ban',
                    'clase' => 'comanda-badge comanda-badge--exclusion'
                ];
                continue;
            }

            // 4. Caso Adicionales / Extras (ej: "+ Doble Cheddar", "Extra Bacon", "Doble Medallón")
            if(
                str_starts_with($parte, '+') || 
                str_contains($lower, 'extra') || 
                str_contains($lower, 'doble') || 
                str_contains($lower, 'triple') || 
                str_contains($lower, 'adicional')
            ) {
                $badges[] = [
                    'texto' => $parte,
                    'tipo' => 'extra',
                    'icono' => 'fa-solid fa-plus',
                    'clase' => 'comanda-badge comanda-badge--extra'
                ];
                continue;
            }

            // 5. Caso Variante general o punto de cocción (ej: "Papas Rústicas", "A punto", "Grande")
            $badges[] = [
                'texto' => $parte,
                'tipo' => 'variante',
                'icono' => 'fa-solid fa-tag',
                'clase' => 'comanda-badge comanda-badge--opcion'
            ];
        }

        return $badges;
    }

    /**
     * Renderiza el conjunto de badges en HTML seguro
     */
    public static function renderBadgesHTML(?string $opcionesTexto): string {
        $badges = self::parsearOpciones($opcionesTexto);
        if(empty($badges)) {
            return '';
        }

        $html = '<div class="comanda-badges">';
        foreach($badges as $b) {
            $textoEscapado = htmlspecialchars($b['texto'], ENT_QUOTES, 'UTF-8');
            $html .= sprintf(
                '<span class="%s"><i class="%s"></i> <span>%s</span></span>',
                htmlspecialchars($b['clase'], ENT_QUOTES, 'UTF-8'),
                htmlspecialchars($b['icono'], ENT_QUOTES, 'UTF-8'),
                $textoEscapado
            );
        }
        $html .= '</div>';

        return $html;
    }

    /**
     * Calcula el resumen consolidado de producción (All-Day) para los pedidos activos en cocina
     *
     * @param array $pedidos Array de objetos Model\Pedido
     * @return array [ 'items' => [...], 'total_platos' => int ]
     */
    public static function calcularAllDay(array $pedidos): array {
        $agrupados = [];
        $totalPlatos = 0;

        foreach($pedidos as $ped) {
            $detalles = [];
            if(is_object($ped)) {
                if(isset($ped->detalles) && is_array($ped->detalles)) {
                    $detalles = $ped->detalles;
                } elseif(method_exists($ped, 'detalles')) {
                    $detalles = $ped->detalles();
                }
            } elseif(is_array($ped)) {
                $detalles = $ped['detalles'] ?? [];
            }

            foreach($detalles as $det) {
                $nombre = $det->nombre_producto ?? 'Producto';
                $cant = (int)($det->cantidad ?? 1);
                $totalPlatos += $cant;

                if(!isset($agrupados[$nombre])) {
                    $agrupados[$nombre] = [
                        'nombre' => $nombre,
                        'cantidad' => 0,
                        'detalles_resumen' => []
                    ];
                }

                $agrupados[$nombre]['cantidad'] += $cant;

                if(!empty($det->opciones_texto)) {
                    $agrupados[$nombre]['detalles_resumen'][] = "{$cant}x ({$det->opciones_texto})";
                }
            }
        }

        // Ordenar de mayor a menor volumen de producción
        uasort($agrupados, fn($a, $b) => $b['cantidad'] <=> $a['cantidad']);

        return [
            'items' => $agrupados,
            'total_platos' => $totalPlatos
        ];
    }
}
