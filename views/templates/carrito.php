<!-- BOTÓN FLOTANTE DEL CARRITO -->
<div id="boton-carrito-flotante" class="boton-carrito-flotante boton-carrito-flotante--oculto" role="button" aria-label="Ver pedido">
    <div class="boton-carrito-flotante__icono">
        <i class="fa-solid fa-bag-shopping"></i>
        <span id="carrito-contador-badge" class="boton-carrito-flotante__badge">0</span>
    </div>
    <span class="boton-carrito-flotante__texto">Ver Pedido</span>
    <span id="carrito-total-flotante" class="boton-carrito-flotante__total">$ 0,00</span>
</div>

<!-- DRAWER / SIDEBAR LATERAL DEL CARRITO -->
<div id="carrito-overlay" class="carrito-overlay"></div>
<aside id="carrito-drawer" class="carrito-drawer" aria-label="Carrito de compras">
    <div class="carrito-drawer__cabecera">
        <h3 class="carrito-drawer__titulo">
            <i class="fa-solid fa-utensils"></i>
            <span>Tu Pedido</span>
        </h3>
        <button id="carrito-cerrar-btn" type="button" class="carrito-drawer__cerrar" aria-label="Cerrar pedido">
            <i class="fa-solid fa-xmark"></i>
        </button>
    </div>

    <div id="carrito-cuerpo" class="carrito-drawer__cuerpo">
        <!-- Renderizado dinámicamente por JavaScript -->
    </div>

    <div class="carrito-drawer__pie">
        <div class="carrito-drawer__subtotal-fila">
            <span>Subtotal:</span>
            <span id="carrito-subtotal-texto">$ 0,00</span>
        </div>
        <a href="/checkout" id="carrito-btn-checkout" class="boton boton--primario carrito-drawer__boton-checkout">
            <span>Continuar al Checkout</span>
            <i class="fa-solid fa-arrow-right"></i>
        </a>
    </div>
</aside>
