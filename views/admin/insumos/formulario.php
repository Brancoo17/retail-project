<div class="formulario__campo">
    <label for="nombre" class="formulario__label">Nombre del Insumo:</label>
    <input 
        type="text" 
        id="nombre" 
        name="insumo[nombre]" 
        class="formulario__input" 
        placeholder="ej. Harina 000, Queso Muzzarella, Salsa de Tomate..." 
        value="<?php echo s($insumo->nombre); ?>"
        required
    >
</div>

<div class="formulario__campo">
    <label for="unidad_medida" class="formulario__label">Unidad de Medida:</label>
    <select id="unidad_medida" name="insumo[unidad_medida]" class="formulario__input" required>
        <?php foreach($unidades as $clave => $etiqueta): ?>
            <option value="<?php echo s($clave); ?>" <?php echo ($insumo->unidad_medida === $clave) ? 'selected' : ''; ?>>
                <?php echo s($etiqueta); ?>
            </option>
        <?php endforeach; ?>
    </select>
    <span class="formulario__ayuda">Define cómo se medirá este ingrediente en las recetas de platos.</span>
</div>

<?php 
    $esUnidad = ($insumo->unidad_medida === 'unidades');
    $valActual = $esUnidad ? (string)round($insumo->stock_actual) : (string)$insumo->stock_actual;
    $valMinimo = $esUnidad ? (string)round($insumo->stock_minimo) : (string)$insumo->stock_minimo;
    $stepVal = $esUnidad ? '1' : 'any';
?>

<div class="grid grid--2">
    <div class="formulario__campo">
        <label for="stock_actual" class="formulario__label">Stock Actual en Depósito:</label>
        <input 
            type="number" 
            step="<?php echo $stepVal; ?>" 
            min="0" 
            id="stock_actual" 
            name="insumo[stock_actual]" 
            class="formulario__input" 
            placeholder="<?php echo $esUnidad ? '0' : '0,000'; ?>" 
            value="<?php echo s($valActual); ?>"
            required
        >
        <span class="formulario__ayuda">Cantidad actual en depósito (enteros para unidades, decimales para peso/volumen).</span>
    </div>

    <div class="formulario__campo">
        <label for="stock_minimo" class="formulario__label">Stock Mínimo (Umbral de Alerta):</label>
        <input 
            type="number" 
            step="<?php echo $stepVal; ?>" 
            min="0" 
            id="stock_minimo" 
            name="insumo[stock_minimo]" 
            class="formulario__input" 
            placeholder="<?php echo $esUnidad ? '10' : '5,000'; ?>" 
            value="<?php echo s($valMinimo); ?>"
            required
        >
        <span class="formulario__ayuda">Nivel para emitir alerta visual si el stock es menor o igual a este valor.</span>
    </div>
</div>
