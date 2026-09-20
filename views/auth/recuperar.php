<div class="auth">
    <div class="auth__tarjeta">
        <header class="auth__encabezado">
            <div class="auth__icono">
                <i class="fa-solid fa-shield-halved"></i>
            </div>
            <h1 class="auth__titulo">Nueva Contraseña</h1>
            <p class="auth__descripcion">Ingresa tu nueva contraseña para acceder al sistema</p>
        </header>

        <?php include_once __DIR__ . '/../templates/alertas.php'; ?>

        <?php if(!$error): ?>
            <form class="formulario" method="POST">
                <div class="formulario__campo">
                    <label class="formulario__label" for="password">Nueva Contraseña</label>
                    <input 
                        type="password" 
                        id="password" 
                        name="password" 
                        class="formulario__input" 
                        placeholder="Mínimo 6 caracteres"
                        required
                    >
                </div>

                <button type="submit" class="boton boton--primario boton--bloque">
                    <i class="fa-solid fa-lock"></i>
                    <span>Guardar Nueva Contraseña</span>
                </button>
            </form>
        <?php else: ?>
            <nav class="formulario__acciones">
                <a href="/login" class="boton boton--secundario boton--bloque">Volver al Inicio de Sesión</a>
            </nav>
        <?php endif; ?>
    </div>
</div>
