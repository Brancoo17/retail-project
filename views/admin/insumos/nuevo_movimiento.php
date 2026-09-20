<div class="contenedor seccion">
    <div class="admin-encabezado">
        <a href="/admin/insumos" class="boton boton--secundario boton--pequeno">
            <i class="fa-solid fa-arrow-left"></i>
            <span>Volver a Insumos</span>
        </a>
        <h1 class="seccion__titulo">Registrar Movimiento de Stock</h1>
        <p class="seccion__subtitulo">Asienta compras de mercadería, descartes por merma o ajustes de inventario con trazabilidad total.</p>
    </div>

    <?php include_once __DIR__ . '/../../templates/alertas.php'; ?>

    <form class="formulario formulario--admin" method="POST">
        <fieldset class="admin-tarjeta">
            <legend class="admin-tarjeta__titulo">
                <i class="fa-solid fa-right-left"></i>
                <span>Detalle de la Operación</span>
            </legend>

            <div class="formulario__campo">
                <label for="insumo_id" class="formulario__label">Insumo a Modificar:</label>
                <select id="insumo_id" name="insumo_id" class="formulario__input" required>
                    <option value="">-- Selecciona un Insumo --</option>
                    <?php foreach($insumos as $ins): ?>
                        <option 
                            value="<?php echo (int)$ins->id; ?>" 
                            <?php echo ($insumoIdDefecto === (int)$ins->id) ? 'selected' : ''; ?>
                        >
                            <?php echo s($ins->nombre); ?> (Stock actual: <?php echo s($ins->stockActualFormateado()); ?> <?php echo s($ins->unidad_medida); ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="grid grid--2">
                <div class="formulario__campo">
                    <label for="tipo" class="formulario__label">Tipo de Movimiento:</label>
                    <select id="tipo" name="tipo" class="formulario__input" required>
                        <?php foreach($tipos as $clave => $etiqueta): ?>
                            <option value="<?php echo s($clave); ?>"><?php echo s($etiqueta); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <span class="formulario__ayuda">Compra suma stock, Merma resta stock, Ajuste corrige diferencias.</span>
                </div>

                <div class="formulario__campo">
                    <label for="cantidad" class="formulario__label">Cantidad:</label>
                    <input 
                        type="number" 
                        step="any" 
                        min="0.001" 
                        id="cantidad" 
                        name="cantidad" 
                        class="formulario__input" 
                        placeholder="ej. 5,000 o 10" 
                        required
                    >
                    <span class="formulario__ayuda">Cantidad a ingresar o descontar según la unidad del insumo.</span>
                </div>
            </div>

            <div class="formulario__campo">
                <label for="motivo" class="formulario__label">Motivo o Justificación:</label>
                <input 
                    type="text" 
                    id="motivo" 
                    name="motivo" 
                    class="formulario__input" 
                    placeholder="ej. Factura Distribuidora #1024, Bolsa dañada en transporte, Recuento de fin de mes..." 
                    required
                >
                <span class="formulario__ayuda">Este texto quedará asentado en el historial de auditoría del comercio.</span>
            </div>
        </fieldset>

        <div class="formulario__acciones-admin">
            <button type="submit" class="boton boton--primario">
                <i class="fa-solid fa-floppy-disk"></i>
                <span>Asentar Movimiento</span>
            </button>
        </div>
    </form>
</div>
