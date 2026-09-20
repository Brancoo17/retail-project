<div class="formulario__campo">
    <label class="formulario__label" for="nombre">Nombre Completo del Repartidor</label>
    <input 
        type="text" 
        id="nombre" 
        name="repartidor[nombre]" 
        class="formulario__input" 
        placeholder="Ej: Carlos Gómez"
        value="<?php echo s($repartidor->nombre ?? ''); ?>"
        required
    >
</div>

<div class="grid grid--2">
    <div class="formulario__campo">
        <label class="formulario__label" for="email">Correo Electrónico (Para iniciar sesión)</label>
        <input 
            type="email" 
            id="email" 
            name="repartidor[email]" 
            class="formulario__input" 
            placeholder="repartidor@ejemplo.com"
            value="<?php echo s($repartidor->email ?? ''); ?>"
            required
        >
    </div>

    <div class="formulario__campo">
        <label class="formulario__label" for="telefono">Teléfono de Contacto (Celular / WhatsApp)</label>
        <input 
            type="tel" 
            id="telefono" 
            name="repartidor[telefono]" 
            class="formulario__input" 
            placeholder="Ej: 1199887766"
            value="<?php echo s($repartidor->telefono ?? ''); ?>"
            required
        >
    </div>
</div>

<div class="grid grid--2">
    <div class="formulario__campo">
        <label class="formulario__label" for="password">
            <?php echo isset($repartidor->id) ? 'Nueva Contraseña (Dejar en blanco para no cambiar)' : 'Contraseña de Acceso'; ?>
        </label>
        <input 
            type="password" 
            id="password" 
            name="repartidor[password]" 
            class="formulario__input" 
            placeholder="Mínimo 6 caracteres"
            <?php echo isset($repartidor->id) ? '' : 'required minlength="6"'; ?>
        >
    </div>

    <div class="formulario__campo">
        <label class="formulario__label" for="confirmado">Estado del Repartidor</label>
        <select id="confirmado" name="repartidor[confirmado]" class="formulario__input">
            <option value="1" <?php echo (($repartidor->confirmado ?? 1) == 1) ? 'selected' : ''; ?>>Activo (Disponible para despachos)</option>
            <option value="0" <?php echo (($repartidor->confirmado ?? 1) == 0) ? 'selected' : ''; ?>>Inactivo / Pausado</option>
        </select>
    </div>
</div>
