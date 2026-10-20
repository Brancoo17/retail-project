<?php
/**
 * Test Suite: Mejoras de visualización de comandas en Cocina (KDS)
 * - Propuesta 1: Badges semánticos para opciones y modificaciones
 * - Propuesta 2: Modal de enfoque con checklist interactivo
 * - Propuesta 3: Barra superior de resumen All-Day
 */

require_once __DIR__ . '/../includes/app.php';

use Classes\KdsHelper;
use Model\Pedido;
use Model\PedidoDetalle;

echo "========================================================\n";
echo "TEST SUITE: MEJORAS VISUALES Y OPERATIVAS KDS COCINA\n";
echo "========================================================\n\n";

$pass = 0;
$fail = 0;

function verificar($condicion, $descripcion) {
    global $pass, $fail;
    if($condicion) {
        echo "  [OK] $descripcion\n";
        $pass++;
    } else {
        echo "  [FAIL] $descripcion\n";
        $fail++;
    }
}

// --------------------------------------------------------------------
// 1. Pruebas de KdsHelper::parsearOpciones()
// --------------------------------------------------------------------
echo "--- 1. Pruebas de KdsHelper::parsearOpciones() ---\n";

// Caso: Exclusión
$opcExclusion = "Sin cebolla";
$parsed = KdsHelper::parsearOpciones($opcExclusion);
verificar(count($parsed) === 1, "Detecta 1 modificación para 'Sin cebolla'");
verificar($parsed[0]['tipo'] === 'exclusion', "Tipo es 'exclusion'");
verificar(str_contains($parsed[0]['clase'], 'comanda-badge--exclusion'), "Clase contiene comanda-badge--exclusion");
verificar($parsed[0]['icono'] === 'fa-solid fa-ban', "Icono es fa-ban");

// Caso: Extras
$opcExtra = "+ Doble Cheddar (+$500,00) | Extra Bacon";
$parsed = KdsHelper::parsearOpciones($opcExtra);
verificar(count($parsed) === 2, "Detecta 2 modificaciones para extras");
verificar($parsed[0]['tipo'] === 'extra' && $parsed[1]['tipo'] === 'extra', "Ambos son de tipo 'extra'");
verificar(str_contains($parsed[0]['clase'], 'comanda-badge--extra'), "Clase contiene comanda-badge--extra");

// Caso: Salsas
$opcSalsas = "Salsas: Barbacoa, Mayonesa Ahumada, Mostaza Dijon";
$parsed = KdsHelper::parsearOpciones($opcSalsas);
verificar(count($parsed) === 1, "Detecta grupo de salsas");
verificar($parsed[0]['tipo'] === 'salsa', "Tipo es 'salsa'");
verificar(str_contains($parsed[0]['clase'], 'comanda-badge--salsa'), "Clase contiene comanda-badge--salsa");
verificar($parsed[0]['icono'] === 'fa-solid fa-bottle-droplet', "Icono es fa-bottle-droplet");

// Caso: Nota de Alerta / Alergia
$opcAlerta = "Notas: Alergia severa al huevo, por favor limpiar la plancha";
$parsed = KdsHelper::parsearOpciones($opcAlerta);
verificar(count($parsed) === 1, "Detecta 1 nota");
verificar($parsed[0]['tipo'] === 'alerta', "Detecta nota crítica de alergia como 'alerta'");
verificar(str_contains($parsed[0]['clase'], 'comanda-badge--alerta'), "Clase contiene comanda-badge--alerta");
verificar($parsed[0]['icono'] === 'fa-solid fa-triangle-exclamation', "Icono es fa-triangle-exclamation");

// Caso: Nota general sin exclusión
$opcNotaGeneral = "Notas: Por favor enviar servilletas adicionales";
$parsed = KdsHelper::parsearOpciones($opcNotaGeneral);
verificar($parsed[0]['tipo'] === 'nota', "Detecta nota general informativa");
verificar(str_contains($parsed[0]['clase'], 'comanda-badge--nota'), "Clase contiene comanda-badge--nota");

// Caso: Cadena combinada compleja
$opcCompleta = "Medallón Simple | Sin tomate | + Extra Panceta | Salsas: Alioli | Notas: Bien cocido";
$parsed = KdsHelper::parsearOpciones($opcCompleta);
verificar(count($parsed) === 5, "Cadena compleja separada en 5 badges");
$tipos = array_column($parsed, 'tipo');
verificar(in_array('variante', $tipos), "Contiene variante estándar");
verificar(in_array('exclusion', $tipos), "Contiene exclusión");
verificar(in_array('extra', $tipos), "Contiene extra");
verificar(in_array('salsa', $tipos), "Contiene salsa");
verificar(in_array('nota', $tipos), "Contiene nota");

// --------------------------------------------------------------------
// 2. Pruebas de KdsHelper::renderBadgesHTML()
// --------------------------------------------------------------------
echo "\n--- 2. Pruebas de KdsHelper::renderBadgesHTML() ---\n";
$html = KdsHelper::renderBadgesHTML("Sin cebolla | + Cheddar");
verificar(str_contains($html, '<div class="comanda-badges">'), "HTML envuelve con comanda-badges");
verificar(str_contains($html, 'comanda-badge--exclusion'), "HTML contiene badge de exclusión");
verificar(str_contains($html, 'comanda-badge--extra'), "HTML contiene badge de extra");
verificar(KdsHelper::renderBadgesHTML('') === '', "Cadena vacía retorna string vacío");

// --------------------------------------------------------------------
// 3. Pruebas de KdsHelper::calcularAllDay()
// --------------------------------------------------------------------
echo "\n--- 3. Pruebas de KdsHelper::calcularAllDay() ---\n";

// Crear pedidos mock con detalles
$mockP1 = new Pedido(['id' => 9001, 'estado' => 'en_preparacion']);
$mockDet1 = new PedidoDetalle(['nombre_producto' => 'Hamburguesa Doble', 'cantidad' => 2, 'opciones_texto' => 'Sin cebolla']);
$mockDet2 = new PedidoDetalle(['nombre_producto' => 'Papas Fritas', 'cantidad' => 1, 'opciones_texto' => '']);
$mockP1->detalles = [$mockDet1, $mockDet2];

$mockP2 = new Pedido(['id' => 9002, 'estado' => 'pendiente']);
$mockDet3 = new PedidoDetalle(['nombre_producto' => 'Hamburguesa Doble', 'cantidad' => 3, 'opciones_texto' => '+ Doble Cheddar']);
$mockDet4 = new PedidoDetalle(['nombre_producto' => 'Pizza Muzzarella', 'cantidad' => 2, 'opciones_texto' => '']);
$mockP2->detalles = [$mockDet3, $mockDet4];

$resumen = KdsHelper::calcularAllDay([$mockP1, $mockP2]);

verificar($resumen['total_platos'] === 8, "Total platos calculados es 8 (2+1+3+2)");
verificar(isset($resumen['items']['Hamburguesa Doble']), "Agrupa Hamburguesa Doble");
verificar($resumen['items']['Hamburguesa Doble']['cantidad'] === 5, "Hamburguesa Doble suma 5 unidades (2 + 3)");
verificar($resumen['items']['Pizza Muzzarella']['cantidad'] === 2, "Pizza Muzzarella suma 2 unidades");
verificar($resumen['items']['Papas Fritas']['cantidad'] === 1, "Papas Fritas suma 1 unidad");

// Verificar orden descendente: primer ítem debe ser Hamburguesa Doble (5)
$primerItem = array_key_first($resumen['items']);
verificar($primerItem === 'Hamburguesa Doble', "Ítems ordenados de mayor a menor volumen (primer item: Hamburguesa Doble)");

// --------------------------------------------------------------------
// 4. Verificación de Integración de Vista y CSS
// --------------------------------------------------------------------
echo "\n--- 4. Verificación de Vista y CSS Compilado ---\n";

$vistaContent = file_get_contents(__DIR__ . '/../views/cocina/index.php');
verificar(str_contains($vistaContent, 'kds-allday'), "Vista contiene contenedor kds-allday");
verificar(str_contains($vistaContent, 'modal-comanda-detalle'), "Vista contiene modal-comanda-detalle");
verificar(str_contains($vistaContent, 'kds-modal-detalle'), "Vista contiene estructura kds-modal-detalle");
verificar(str_contains($vistaContent, 'toggleChecklistItem'), "Vista contiene función toggleChecklistItem");
verificar(str_contains($vistaContent, 'abrirModalDetalleComanda'), "Vista contiene función abrirModalDetalleComanda");
verificar(str_contains($vistaContent, 'renderBadgesHTML'), "Vista utiliza KdsHelper::renderBadgesHTML");

$cssContent = file_get_contents(__DIR__ . '/../public/build/css/app.css');
verificar(str_contains($cssContent, 'comanda-badge--exclusion'), "CSS compilado contiene comanda-badge--exclusion");
verificar(str_contains($cssContent, 'comanda-badge--extra'), "CSS compilado contiene comanda-badge--extra");
verificar(str_contains($cssContent, 'comanda-badge--salsa'), "CSS compilado contiene comanda-badge--salsa");
verificar(str_contains($cssContent, 'kds-allday'), "CSS compilado contiene kds-allday");
verificar(str_contains($cssContent, 'kds-modal-detalle'), "CSS compilado contiene kds-modal-detalle");
verificar(str_contains($cssContent, 'kds-checklist-item--completado'), "CSS compilado contiene kds-checklist-item--completado");

echo "\n========================================================\n";
echo "RESULTADOS: $pass exitosos, $fail fallidos.\n";
echo "========================================================\n";

if($fail > 0) {
    exit(1);
}
