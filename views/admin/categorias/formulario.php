<div class="formulario__campo">
    <label class="formulario__label" for="nombre">Nombre de la Categoría</label>
    <input 
        type="text" 
        id="nombre" 
        name="categoria[nombre]" 
        class="formulario__input" 
        placeholder="Ej: Pizzas Clásicas, Lomitos, Bebidas..."
        value="<?php echo s($categoria->nombre ?? ''); ?>"
        required
    >
</div>

<div class="grid grid--2">
    <div class="formulario__campo">
        <label class="formulario__label" for="orden">Orden de Visualización</label>
        <input 
            type="number" 
            min="0" 
            id="orden" 
            name="categoria[orden]" 
            class="formulario__input" 
            value="<?php echo s((string)($categoria->orden ?? 0)); ?>"
            required
        >
        <span class="formulario__ayuda">Los números menores aparecen primero en el menú (0, 1, 2...).</span>
    </div>

    <div class="formulario__campo">
        <label class="formulario__label" for="activo">Estado</label>
        <select id="activo" name="categoria[activo]" class="formulario__input">
            <option value="1" <?php echo ($categoria->activo == 1) ? 'selected' : ''; ?>>Activa (Visible en el menú)</option>
            <option value="0" <?php echo ($categoria->activo == 0) ? 'selected' : ''; ?>>Inactiva (Oculta)</option>
        </select>
    </div>
</div>

<div class="formulario__campo u-separador-arriba-sm">
    <input type="hidden" name="categoria[permite_aderezos]" value="0">
    <label class="formulario__label" style="display: flex; align-items: center; gap: 0.8rem; cursor: pointer;">
        <input 
            type="checkbox" 
            name="categoria[permite_aderezos]" 
            value="1" 
            <?php echo (!empty($categoria->permite_aderezos) && (int)$categoria->permite_aderezos === 1) ? 'checked' : ''; ?>
        >
        <span><i class="fa-solid fa-bottle-droplet" style="color: #ea580c;"></i> <strong>Habilitar selección de aderezos y salsas</strong> (para sándwiches, hamburguesas, lomitos, etc.)</span>
    </label>
    <span class="formulario__ayuda">Al activarlo, los clientes y el personal podrán elegir aderezos sin cargo al pedir cualquier producto de esta categoría.</span>
</div>
