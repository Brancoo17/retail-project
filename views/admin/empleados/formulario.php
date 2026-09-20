<div class="formulario__campo">
    <label class="formulario__label" for="nombre">Nombre Completo del Empleado</label>
    <input 
        type="text" 
        id="nombre" 
        name="empleado[nombre]" 
        class="formulario__input" 
        placeholder="Ej: Martín Rodríguez (Jefe de Cocina)"
        value="<?php echo s($empleado->nombre ?? ''); ?>"
        required
    >
</div>

<div class="grid grid--2">
    <div class="formulario__campo">
        <label class="formulario__label" for="email">Correo Electrónico (Para iniciar sesión)</label>
        <input 
            type="email" 
            id="email" 
            name="empleado[email]" 
            class="formulario__input" 
            placeholder="cocina@ejemplo.com"
            value="<?php echo s($empleado->email ?? ''); ?>"
            required
        >
    </div>

    <div class="formulario__campo">
        <label class="formulario__label" for="telefono">Teléfono / WhatsApp de Contacto</label>
        <input 
            type="tel" 
            id="telefono" 
            name="empleado[telefono]" 
            class="formulario__input" 
            placeholder="Ej: 1155443322"
            value="<?php echo s($empleado->telefono ?? ''); ?>"
            required
        >
    </div>
</div>

<div class="grid grid--2">
    <div class="formulario__campo">
        <label class="formulario__label" for="password">
            <?php echo isset($empleado->id) ? 'Nueva Contraseña (Dejar en blanco para no cambiar)' : 'Contraseña de Acceso'; ?>
        </label>
        <input 
            type="password" 
            id="password" 
            name="empleado[password]" 
            class="formulario__input" 
            placeholder="Mínimo 6 caracteres"
            <?php echo isset($empleado->id) ? '' : 'required minlength="6"'; ?>
        >
    </div>

    <div class="formulario__campo">
        <label class="formulario__label" for="confirmado">Estado de la Cuenta</label>
        <select id="confirmado" name="empleado[confirmado]" class="formulario__input">
            <option value="1" <?php echo (($empleado->confirmado ?? 1) == 1) ? 'selected' : ''; ?>>Activo (Puede iniciar sesión en Cocina y Caja)</option>
            <option value="0" <?php echo (($empleado->confirmado ?? 1) == 0) ? 'selected' : ''; ?>>Inactivo (Acceso suspendido)</option>
        </select>
    </div>
</div>
