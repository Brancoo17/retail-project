<div class="grid grid--2">
    <div class="formulario__campo">
        <label class="formulario__label" for="nombre">Nombre del Producto</label>
        <input 
            type="text" 
            id="nombre" 
            name="producto[nombre]" 
            class="formulario__input" 
            placeholder="Ej: Pizza Muzzarella, Burger Doble Carne..."
            value="<?php echo s($producto->nombre ?? ''); ?>"
            required
        >
    </div>

    <div class="formulario__campo">
        <label class="formulario__label" for="categoria_id">Categoría</label>
        <select id="categoria_id" name="producto[categoria_id]" class="formulario__input" required>
            <option value="">-- Seleccionar Categoría --</option>
            <?php foreach($categorias as $cat): ?>
                <option 
                    value="<?php echo s((string)$cat->id); ?>" 
                    <?php echo ((int)$producto->categoria_id === (int)$cat->id) ? 'selected' : ''; ?>
                >
                    <?php echo s($cat->nombre); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
</div>

<div class="grid grid--2">
    <div class="formulario__campo">
        <label class="formulario__label" for="precio">Precio Base ($)</label>
        <input 
            type="number" 
            step="0.01" 
            min="0" 
            id="precio" 
            name="producto[precio]" 
            class="formulario__input" 
            placeholder="Ej: 8500"
            value="<?php echo s((string)($producto->precio ?? '')); ?>"
            required
        >
    </div>

    <div class="formulario__campo">
        <label class="formulario__label" for="activo">Estado</label>
        <select id="activo" name="producto[activo]" class="formulario__input">
            <option value="1" <?php echo ($producto->activo == 1) ? 'selected' : ''; ?>>Disponible (Visible para pedidos)</option>
            <option value="0" <?php echo ($producto->activo == 0) ? 'selected' : ''; ?>>Pausado / Sin Stock</option>
        </select>
    </div>
</div>

<div class="formulario__campo">
    <label class="formulario__label" for="descripcion">Descripción / Ingredientes</label>
    <textarea 
        id="descripcion" 
        name="producto[descripcion]" 
        class="formulario__input" 
        rows="3"
        placeholder="Ej: Salsa de tomate casera, muzzarella premium, aceitunas verdes y orégano."
    ><?php echo s($producto->descripcion ?? ''); ?></textarea>
</div>

<div class="formulario__campo">
    <label class="formulario__label" for="imagen">Foto del Producto (JPG / PNG / WebP)</label>
    <?php if(!empty($producto->imagen) && file_exists(CARPETA_IMAGENES . $producto->imagen)): ?>
        <div class="producto-preview-contenedor">
            <span class="formulario__ayuda">Imagen actual:</span>
            <img src="/imagenes/<?php echo s($producto->imagen); ?>" alt="Foto Producto" class="producto-preview">
        </div>
    <?php endif; ?>
    <input 
        type="file" 
        id="imagen" 
        name="imagen" 
        class="formulario__input" 
        accept="image/png, image/jpeg, image/webp"
    >
</div>
