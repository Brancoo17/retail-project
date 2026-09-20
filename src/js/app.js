/**
 * retail-mvc - JavaScript Vanilla Centralizado
 * Modularizado por vista con guard clauses y persistencia de carrito en LocalStorage
 */

document.addEventListener('DOMContentLoaded', () => {
    iniciarApp();
});

function iniciarApp() {
    initSmoothScroll();
    initAlertas();
    initMostrarOcultarPassword();
    initColorPickers();
    initMapaComercio();
    initInsumosForm();
    initCarrito();
    initModalProducto();
    initCheckout();
    initSeguimiento();
    initMenuCategorias();
    initBannerPedidoActivo();
    initKDS();
    initSidebar();
    initHeaderMobile();
}

/**
 * Formateador de moneda estilo Argentina (ej. $ 2.500,00)
 */
function formatearMoneda(monto) {
    const valor = Number(monto) || 0;
    return '$ ' + valor.toLocaleString('es-AR', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });
}

/**
 * Scroll suave para enlaces internos
 */
function initSmoothScroll() {
    const enlaces = document.querySelectorAll('a[href^="#"]');
    if (!enlaces.length) return;

    enlaces.forEach(enlace => {
        enlace.addEventListener('click', e => {
            const objetivoId = enlace.getAttribute('href');
            if (objetivoId === '#' || !objetivoId || objetivoId.startsWith('#cat-')) return;

            const objetivo = document.querySelector(objetivoId);
            if (objetivo) {
                e.preventDefault();
                objetivo.scrollIntoView({
                    behavior: 'smooth'
                });
            }
        });
    });
}

/**
 * Cierre automático o manual de alertas
 */
function initAlertas() {
    const alertas = document.querySelectorAll('.alerta:not(.alerta--fija):not(.alerta--persistente):not(.modal-backdrop .alerta)');
    if (!alertas.length) return;

    alertas.forEach(alerta => {
        setTimeout(() => {
            alerta.style.transition = 'opacity 0.5s ease';
            alerta.style.opacity = '0';
            setTimeout(() => alerta.remove(), 500);
        }, 6000);
    });
}

/**
 * Mostrar / Ocultar Contraseña en todos los campos password
 */
function initMostrarOcultarPassword() {
    const passwordInputs = document.querySelectorAll('input[type="password"], input[data-password-toggle="true"]');
    if (!passwordInputs.length) return;

    passwordInputs.forEach(input => {
        // Evitar duplicar si ya fue inicializado
        if (input.dataset.passwordToggleInit === 'true' || (input.parentElement && input.parentElement.classList.contains('campo-password-wrapper'))) {
            return;
        }

        input.dataset.passwordToggleInit = 'true';

        // Crear contenedor relativo
        const wrapper = document.createElement('div');
        wrapper.className = 'campo-password-wrapper';

        // Insertar wrapper en el lugar del input y mover el input adentro
        input.parentNode.insertBefore(wrapper, input);
        wrapper.appendChild(input);

        // Crear botón toggle
        const btnToggle = document.createElement('button');
        btnToggle.type = 'button';
        btnToggle.className = 'btn-toggle-password';
        btnToggle.setAttribute('aria-label', 'Mostrar u ocultar contraseña');
        btnToggle.setAttribute('title', 'Mostrar u ocultar contraseña');
        btnToggle.innerHTML = '<i class="fa-regular fa-eye"></i>';

        btnToggle.addEventListener('click', (e) => {
            e.preventDefault();
            const esPassword = input.type === 'password';
            input.type = esPassword ? 'text' : 'password';
            btnToggle.innerHTML = esPassword 
                ? '<i class="fa-regular fa-eye-slash"></i>' 
                : '<i class="fa-regular fa-eye"></i>';
            btnToggle.classList.toggle('activo', esPassword);
            input.focus();
        });

        wrapper.appendChild(btnToggle);
    });
}

/**
 * Sincroniza visualmente los inputs de color con su código hexadecimal
 */
function initColorPickers() {
    const pickers = document.querySelectorAll('.color-picker');
    if (!pickers.length) return;

    pickers.forEach(picker => {
        const codigo = picker.parentElement.querySelector('.color-picker-codigo');
        if (!codigo) return;

        picker.addEventListener('input', e => {
            codigo.textContent = e.target.value.toUpperCase();
        });
    });
}

/**
 * Inicializa el mapa Leaflet en la vista de configuración del comercio
 */
function initMapaComercio() {
    const mapaContenedor = document.getElementById('mapa-comercio');
    if (!mapaContenedor || typeof L === 'undefined') return;

    const inputLat = document.getElementById('lat');
    const inputLng = document.getElementById('lng');

    const latDefecto = parseFloat(inputLat?.value) || -34.603722;
    const lngDefecto = parseFloat(inputLng?.value) || -58.381592;

    const mapa = L.map('mapa-comercio').setView([latDefecto, lngDefecto], 15);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; OpenStreetMap contributors'
    }).addTo(mapa);

    const marcador = L.marker([latDefecto, lngDefecto], {
        draggable: true
    }).addTo(mapa);

    marcador.bindPopup('<strong>Tu Local</strong><br>Arrastra este pin o haz clic en el mapa').openPopup();

    function actualizarCoordenadas(lat, lng) {
        if (inputLat) inputLat.value = lat.toFixed(7);
        if (inputLng) inputLng.value = lng.toFixed(7);
    }

    marcador.on('dragend', function (e) {
        const posicion = e.target.getLatLng();
        actualizarCoordenadas(posicion.lat, posicion.lng);
    });

    mapa.on('click', function (e) {
        marcador.setLatLng(e.latlng);
        actualizarCoordenadas(e.latlng.lat, e.latlng.lng);
    });
}

/**
 * Adapta dinámicamente los campos numéricos del formulario de insumos según la unidad
 */
function initInsumosForm() {
    const selectUnidad = document.getElementById('unidad_medida');
    if (!selectUnidad) return;

    const inputStockActual = document.getElementById('stock_actual');
    const inputStockMinimo = document.getElementById('stock_minimo');

    function sincronizarCamposSegunUnidad() {
        const esUnidad = selectUnidad.value === 'unidades';
        if (inputStockActual) {
            inputStockActual.step = esUnidad ? '1' : 'any';
            inputStockActual.placeholder = esUnidad ? '0' : '0,000';
            if (esUnidad && inputStockActual.value) {
                inputStockActual.value = Math.round(parseFloat(inputStockActual.value) || 0);
            }
        }
        if (inputStockMinimo) {
            inputStockMinimo.step = esUnidad ? '1' : 'any';
            inputStockMinimo.placeholder = esUnidad ? '10' : '5,000';
            if (esUnidad && inputStockMinimo.value) {
                inputStockMinimo.value = Math.round(parseFloat(inputStockMinimo.value) || 0);
            }
        }
    }

    selectUnidad.addEventListener('change', sincronizarCamposSegunUnidad);
}

// =========================================================================
// MÓDULO: CARRITO DE COMPRAS (LOCALSTORAGE + DRAWER)
// =========================================================================

const CARRITO_STORAGE_KEY = 'retail_carrito_items';

function obtenerCarrito() {
    try {
        const guardado = localStorage.getItem(CARRITO_STORAGE_KEY);
        return guardado ? JSON.parse(guardado) : [];
    } catch (e) {
        console.error('Error al leer carrito:', e);
        return [];
    }
}

function guardarCarrito(items) {
    try {
        localStorage.setItem(CARRITO_STORAGE_KEY, JSON.stringify(items));
    } catch (e) {
        console.error('Error al guardar carrito:', e);
    }
    actualizarUICarrito();
}

function actualizarUICarrito() {
    const items = obtenerCarrito();
    const cantidadTotal = items.reduce((acc, it) => acc + (parseInt(it.cantidad, 10) || 1), 0);
    const subtotal = items.reduce((acc, it) => acc + ((parseFloat(it.precio_unitario) || 0) * (parseInt(it.cantidad, 10) || 1)), 0);

    // 1. Botón flotante
    const botonFlotante = document.getElementById('boton-carrito-flotante');
    const badgeContador = document.getElementById('carrito-contador-badge');
    const totalFlotante = document.getElementById('carrito-total-flotante');

    if (botonFlotante) {
        if (cantidadTotal > 0) {
            botonFlotante.classList.remove('boton-carrito-flotante--oculto');
            if (badgeContador) badgeContador.textContent = cantidadTotal;
            if (totalFlotante) totalFlotante.textContent = formatearMoneda(subtotal);
        } else {
            botonFlotante.classList.add('boton-carrito-flotante--oculto');
        }
    }

    // 2. Drawer lateral
    const drawerCuerpo = document.getElementById('carrito-cuerpo');
    const subtotalTexto = document.getElementById('carrito-subtotal-texto');
    const btnCheckout = document.getElementById('carrito-btn-checkout');

    if (subtotalTexto) {
        subtotalTexto.textContent = formatearMoneda(subtotal);
    }

    if (btnCheckout) {
        if (cantidadTotal === 0) {
            btnCheckout.classList.add('boton--deshabilitado');
            btnCheckout.style.pointerEvents = 'none';
            btnCheckout.style.opacity = '0.5';
        } else {
            btnCheckout.classList.remove('boton--deshabilitado');
            btnCheckout.style.pointerEvents = 'auto';
            btnCheckout.style.opacity = '1';
        }
    }

    if (drawerCuerpo) {
        if (items.length === 0) {
            drawerCuerpo.innerHTML = `
                <div class="carrito-drawer__vacio">
                    <i class="fa-solid fa-cart-shopping"></i>
                    <h4>Tu carrito está vacío</h4>
                    <p>Agregá tus platos favoritos desde el menú para armar tu pedido.</p>
                </div>
            `;
        } else {
            drawerCuerpo.innerHTML = items.map((item, index) => `
                <div class="carrito-item" data-index="${index}">
                    <div class="carrito-item__info">
                        <div class="carrito-item__nombre">${item.nombre}</div>
                        ${item.aderezos && item.aderezos.length > 0 ? `<div class="carrito-item__aderezos" style="font-size: 1.15rem; color: #ea580c; font-weight: 600;">Salsas: ${item.aderezos.join(', ')}</div>` : ''}
                        ${item.opcionesTexto ? `<div class="carrito-item__opciones">${item.opcionesTexto}</div>` : ''}
                        ${item.notas ? `<div class="carrito-item__notas"><em>Nota: ${item.notas}</em></div>` : ''}
                        <div class="carrito-item__controles">
                            <div class="carrito-item__cantidad-ctrl">
                                <button type="button" class="btn-cant-menos" data-index="${index}" aria-label="Disminuir">-</button>
                                <span>${item.cantidad}</span>
                                <button type="button" class="btn-cant-mas" data-index="${index}" aria-label="Aumentar">+</button>
                            </div>
                            <button type="button" class="carrito-item__eliminar btn-eliminar-item" data-index="${index}" title="Eliminar del pedido">
                                <i class="fa-solid fa-trash-can"></i>
                            </button>
                        </div>
                    </div>
                    <div class="carrito-item__total">${formatearMoneda(item.precio_unitario * item.cantidad)}</div>
                </div>
            `).join('');
        }
    }

    // 3. Resumen en Checkout (si estamos en la vista /checkout)
    const checkoutItemsLista = document.getElementById('checkout-items-lista');
    const checkoutSubtotalTexto = document.getElementById('checkout-subtotal-texto');

    if (checkoutItemsLista && checkoutSubtotalTexto) {
        checkoutSubtotalTexto.textContent = formatearMoneda(subtotal);

        if (items.length === 0) {
            checkoutItemsLista.innerHTML = `
                <div class="alerta alerta--advertencia" style="margin-bottom: 1.6rem;">
                    <i class="fa-solid fa-circle-exclamation"></i>
                    <span>Tu carrito está vacío. <a href="/menu" style="text-decoration: underline; font-weight: bold;">Volver a la carta</a> para agregar productos.</span>
                </div>
            `;
        } else {
            checkoutItemsLista.innerHTML = items.map(item => `
                <div class="carrito-item" style="padding: 1rem 0;">
                    <div class="carrito-item__info">
                        <div class="carrito-item__nombre">${item.cantidad}x ${item.nombre}</div>
                        ${item.aderezos && item.aderezos.length > 0 ? `<div class="carrito-item__aderezos" style="font-size: 1.15rem; color: #ea580c; font-weight: 600;">Salsas: ${item.aderezos.join(', ')}</div>` : ''}
                        ${item.opcionesTexto ? `<div class="carrito-item__opciones">${item.opcionesTexto}</div>` : ''}
                        ${item.notas ? `<div class="carrito-item__notas">${item.notas}</div>` : ''}
                    </div>
                    <div class="carrito-item__total">${formatearMoneda(item.precio_unitario * item.cantidad)}</div>
                </div>
            `).join('');
        }

        // Disparar actualización de totales globales en checkout
        if (typeof window.recalcularTotalesCheckout === 'function') {
            window.recalcularTotalesCheckout(subtotal);
        }
    }
}

function abrirDrawerCarrito() {
    const overlay = document.getElementById('carrito-overlay');
    const drawer = document.getElementById('carrito-drawer');
    if (overlay) overlay.classList.add('carrito-overlay--activo');
    if (drawer) drawer.classList.add('carrito-drawer--activo');
}

function cerrarDrawerCarrito() {
    const overlay = document.getElementById('carrito-overlay');
    const drawer = document.getElementById('carrito-drawer');
    if (overlay) overlay.classList.remove('carrito-overlay--activo');
    if (drawer) drawer.classList.remove('carrito-drawer--activo');
}

function initCarrito() {
    const btnFlotante = document.getElementById('boton-carrito-flotante');
    const btnCerrar = document.getElementById('carrito-cerrar-btn');
    const overlay = document.getElementById('carrito-overlay');
    const drawerCuerpo = document.getElementById('carrito-cuerpo');

    if (btnFlotante) {
        btnFlotante.addEventListener('click', abrirDrawerCarrito);
    }

    if (btnCerrar) {
        btnCerrar.addEventListener('click', cerrarDrawerCarrito);
    }

    if (overlay) {
        overlay.addEventListener('click', e => {
            if (e.target === overlay) {
                cerrarDrawerCarrito();
            }
        });
    }

    document.addEventListener('keydown', e => {
        if (e.key === 'Escape') {
            cerrarDrawerCarrito();
        }
    });

    // Delegación de eventos para controles de cantidad y eliminar en el drawer
    if (drawerCuerpo) {
        drawerCuerpo.addEventListener('click', e => {
            const btnMenos = e.target.closest('.btn-cant-menos');
            const btnMas = e.target.closest('.btn-cant-mas');
            const btnEliminar = e.target.closest('.btn-eliminar-item');

            if (!btnMenos && !btnMas && !btnEliminar) return;

            const items = obtenerCarrito();

            if (btnMenos) {
                const index = parseInt(btnMenos.dataset.index, 10);
                if (items[index]) {
                    if (items[index].cantidad > 1) {
                        items[index].cantidad -= 1;
                    } else {
                        items.splice(index, 1);
                    }
                    guardarCarrito(items);
                }
            } else if (btnMas) {
                const index = parseInt(btnMas.dataset.index, 10);
                if (items[index]) {
                    items[index].cantidad += 1;
                    guardarCarrito(items);
                }
            } else if (btnEliminar) {
                const index = parseInt(btnEliminar.dataset.index, 10);
                if (items[index]) {
                    items.splice(index, 1);
                    guardarCarrito(items);
                }
            }
        });
    }

    // Render inicial
    actualizarUICarrito();
}

// =========================================================================
// MÓDULO: MODAL DE PERSONALIZACIÓN DE PRODUCTO
// =========================================================================

function initModalProducto() {
    const overlay = document.getElementById('modal-producto-overlay');
    if (!overlay) return;

    const btnCerrar = document.getElementById('modal-cerrar-btn');
    const imgContenedor = document.getElementById('modal-img-contenedor');
    const imgElement = document.getElementById('modal-producto-img');
    const tituloElement = document.getElementById('modal-producto-titulo');
    const descElement = document.getElementById('modal-producto-desc');
    const opcionesGrupo = document.getElementById('modal-producto-opciones-grupo');
    const opcionesLista = document.getElementById('modal-producto-opciones-lista');
    const inputNotas = document.getElementById('modal-producto-notas');
    const btnMenos = document.getElementById('modal-btn-menos');
    const btnMas = document.getElementById('modal-btn-mas');
    const cantidadDisplay = document.getElementById('modal-cantidad-display');
    const btnAgregar = document.getElementById('modal-btn-agregar');
    const btnAgregarTexto = document.getElementById('modal-btn-agregar-texto');

    let productoActual = null;
    let cantidadActual = 1;

    function calcularPrecioUnitario() {
        if (!productoActual) return 0;
        let total = parseFloat(productoActual.precio) || 0;

        const checkboxes = opcionesLista.querySelectorAll('.modal-opcion-chk:checked');
        checkboxes.forEach(chk => {
            total += parseFloat(chk.dataset.precio) || 0;
        });

        return total;
    }

    function actualizarPrecioBoton() {
        const unitario = calcularPrecioUnitario();
        const subtotal = unitario * cantidadActual;
        btnAgregarTexto.textContent = `Agregar al Pedido · ${formatearMoneda(subtotal)}`;
        cantidadDisplay.textContent = cantidadActual;
    }

    function abrirModal(datos) {
        productoActual = datos;
        cantidadActual = 1;
        if (inputNotas) inputNotas.value = '';

        // Imagen
        if (datos.imagen && imgElement && imgContenedor) {
            imgElement.src = datos.imagen;
            imgElement.alt = datos.nombre;
            imgContenedor.style.display = 'block';
        } else if (imgContenedor) {
            imgContenedor.style.display = 'none';
        }

        // Título y descripción
        if (tituloElement) tituloElement.textContent = datos.nombre;
        if (descElement) descElement.textContent = datos.descripcion || 'Elaborado artesanalmente con ingredientes frescos de primera calidad.';

        // Aderezos y Salsas
        const aderezosGrupo = document.getElementById('modal-producto-aderezos-grupo');
        const aderezosLista = document.getElementById('modal-producto-aderezos-lista');
        if (aderezosLista && aderezosGrupo) {
            const catalogoAderezos = window.catalogoAderezos || [];
            if (datos.permite_aderezos && catalogoAderezos.length > 0) {
                aderezosGrupo.style.display = 'block';
                aderezosLista.innerHTML = catalogoAderezos.map(ad => `
                    <label class="modal-producto__aderezo-chip">
                        <input type="checkbox" class="modal-aderezo-chk" value="${ad.nombre}">
                        <span>${ad.nombre}</span>
                    </label>
                `).join('');
            } else {
                aderezosGrupo.style.display = 'none';
                aderezosLista.innerHTML = '';
            }
        }

        // Opciones
        if (opcionesLista && opcionesGrupo) {
            if (Array.isArray(datos.opciones) && datos.opciones.length > 0) {
                opcionesGrupo.style.display = 'block';
                opcionesLista.innerHTML = datos.opciones.map(opc => `
                    <label class="modal-producto__opcion">
                        <div class="modal-producto__opcion-info">
                            <input 
                                type="checkbox" 
                                class="modal-opcion-chk" 
                                value="${opc.id}" 
                                data-nombre="${opc.nombre}" 
                                data-precio="${opc.precio_adicional}"
                            >
                            <span>${opc.nombre}</span>
                        </div>
                        <span class="modal-producto__opcion-precio">
                            ${parseFloat(opc.precio_adicional) > 0 ? '+ ' + formatearMoneda(opc.precio_adicional) : 'Sin cargo'}
                        </span>
                    </label>
                `).join('');
            } else {
                opcionesGrupo.style.display = 'none';
                opcionesLista.innerHTML = '';
            }
        }

        actualizarPrecioBoton();
        overlay.classList.add('modal-overlay--activo');
        overlay.setAttribute('aria-hidden', 'false');
    }

    function cerrarModal() {
        overlay.classList.remove('modal-overlay--activo');
        overlay.setAttribute('aria-hidden', 'true');
        productoActual = null;
    }

    // Botones de abrir producto en el menú
    const botonesAbrir = document.querySelectorAll('.btn-abrir-producto');
    botonesAbrir.forEach(btn => {
        btn.addEventListener('click', () => {
            try {
                const datos = JSON.parse(btn.dataset.producto);
                abrirModal(datos);
            } catch (e) {
                console.error('Error parseando datos del producto:', e);
            }
        });
    });

    if (btnCerrar) {
        btnCerrar.addEventListener('click', cerrarModal);
    }

    overlay.addEventListener('click', e => {
        if (e.target === overlay) {
            cerrarModal();
        }
    });

    // Cambios en opciones
    if (opcionesLista) {
        opcionesLista.addEventListener('change', e => {
            const chk = e.target.closest('.modal-opcion-chk');
            if (chk) {
                const label = chk.closest('.modal-producto__opcion');
                if (label) {
                    label.classList.toggle('modal-producto__opcion--seleccionada', chk.checked);
                }
                actualizarPrecioBoton();
            }
        });
    }

    // Controles de cantidad
    if (btnMenos) {
        btnMenos.addEventListener('click', () => {
            if (cantidadActual > 1) {
                cantidadActual--;
                actualizarPrecioBoton();
            }
        });
    }

    if (btnMas) {
        btnMas.addEventListener('click', () => {
            cantidadActual++;
            actualizarPrecioBoton();
        });
    }

    // Botón Agregar al Pedido
    if (btnAgregar) {
        btnAgregar.addEventListener('click', () => {
            if (!productoActual) return;

            const unitario = calcularPrecioUnitario();
            const checkboxes = opcionesLista.querySelectorAll('.modal-opcion-chk:checked');
            const opcionesSeleccionadas = Array.from(checkboxes).map(chk => ({
                id: parseInt(chk.value, 10),
                nombre: chk.dataset.nombre,
                precio_adicional: parseFloat(chk.dataset.precio) || 0
            }));

            const opcionesTexto = opcionesSeleccionadas.map(opc => 
                opc.nombre + (opc.precio_adicional > 0 ? ` (+${formatearMoneda(opc.precio_adicional)})` : '')
            ).join(', ');

            const checkboxesAderezos = document.querySelectorAll('.modal-aderezo-chk:checked');
            const aderezosSeleccionados = Array.from(checkboxesAderezos).map(chk => chk.value);

            const notas = inputNotas ? inputNotas.value.trim() : '';

            const item = {
                producto_id: productoActual.id,
                nombre: productoActual.nombre,
                precio_base: productoActual.precio,
                precio_unitario: unitario,
                cantidad: cantidadActual,
                notas: notas,
                aderezos: aderezosSeleccionados,
                opciones: opcionesSeleccionadas.map(o => o.id),
                opcionesTexto: opcionesTexto
            };

            const items = obtenerCarrito();

            // Verificar si existe un ítem exactamente idéntico (mismo producto, mismas opciones, mismas salsas y mismas notas)
            const indexExistente = items.findIndex(it => {
                const mismaId = it.producto_id === item.producto_id;
                const mismasNotas = it.notas === item.notas;
                const mismasOpc = JSON.stringify((it.opciones || []).slice().sort()) === JSON.stringify((item.opciones || []).slice().sort());
                const mismasSalsas = JSON.stringify((it.aderezos || []).slice().sort()) === JSON.stringify((item.aderezos || []).slice().sort());
                return mismaId && mismasNotas && mismasOpc && mismasSalsas;
            });

            if (indexExistente !== -1) {
                items[indexExistente].cantidad += cantidadActual;
            } else {
                items.push(item);
            }

            guardarCarrito(items);
            cerrarModal();
            abrirDrawerCarrito();
        });
    }
}

// =========================================================================
// MÓDULO: CHECKOUT (TABS, MAPA LEAFLET, HAVERSINE Y ENVÍO DEL PEDIDO)
// =========================================================================

function initCheckout() {
    const form = document.getElementById('formulario-checkout');
    if (!form) return;

    const tabDelivery = document.getElementById('tab-delivery');
    const tabRetiro = document.getElementById('tab-retiro');
    const inputTipo = document.getElementById('input-tipo-entrega');
    const seccionDelivery = document.getElementById('seccion-delivery');
    const seccionRetiro = document.getElementById('seccion-retiro');
    const inputDireccion = document.getElementById('direccion_texto');
    const inputLat = document.getElementById('cliente_lat');
    const inputLng = document.getElementById('cliente_lng');
    const kpiDistancia = document.getElementById('kpi-distancia-texto');
    const kpiCosto = document.getElementById('kpi-costo-texto');
    const resumenEnvioTexto = document.getElementById('checkout-envio-texto');
    const resumenTotalTexto = document.getElementById('checkout-total-texto');
    const errorMsg = document.getElementById('checkout-error-msg');
    const btnConfirmar = document.getElementById('btn-confirmar-pedido');
    const selectGuardada = document.getElementById('select-direccion-guardada');

    let costoEnvioActual = 0.00;
    let marcadorCliente = null;

    // Función global para sincronizar totales
    window.recalcularTotalesCheckout = function (subtotalParam) {
        const items = obtenerCarrito();
        const subtotal = (typeof subtotalParam === 'number') 
            ? subtotalParam 
            : items.reduce((acc, it) => acc + (it.precio_unitario * it.cantidad), 0);

        const costoEnvio = (inputTipo.value === 'retiro') ? 0.00 : costoEnvioActual;
        const total = subtotal + costoEnvio;

        if (resumenEnvioTexto) {
            resumenEnvioTexto.textContent = (costoEnvio === 0) ? 'Gratis ($0)' : formatearMoneda(costoEnvio);
        }
        if (resumenTotalTexto) {
            resumenTotalTexto.textContent = formatearMoneda(total);
        }

        if (btnConfirmar) {
            btnConfirmar.disabled = (items.length === 0);
        }
    };

    // 1. Selector Tipo de Entrega (Delivery vs. Retiro)
    if (tabDelivery && tabRetiro) {
        tabDelivery.addEventListener('click', () => {
            tabDelivery.classList.add('checkout-tabs__tab--activo');
            tabDelivery.setAttribute('aria-selected', 'true');
            tabRetiro.classList.remove('checkout-tabs__tab--activo');
            tabRetiro.setAttribute('aria-selected', 'false');
            inputTipo.value = 'delivery';

            if (seccionDelivery) seccionDelivery.style.display = 'block';
            if (seccionRetiro) seccionRetiro.style.display = 'none';
            if (inputDireccion) inputDireccion.setAttribute('required', 'required');

            window.recalcularTotalesCheckout();
        });

        tabRetiro.addEventListener('click', () => {
            tabRetiro.classList.add('checkout-tabs__tab--activo');
            tabRetiro.setAttribute('aria-selected', 'true');
            tabDelivery.classList.remove('checkout-tabs__tab--activo');
            tabDelivery.setAttribute('aria-selected', 'false');
            inputTipo.value = 'retiro';

            if (seccionDelivery) seccionDelivery.style.display = 'none';
            if (seccionRetiro) seccionRetiro.style.display = 'block';
            if (inputDireccion) inputDireccion.removeAttribute('required');

            window.recalcularTotalesCheckout();
        });
    }

    // 2. Selector Método de Pago
    const metodosPago = document.querySelectorAll('.metodos-pago__item');
    metodosPago.forEach(item => {
        item.addEventListener('click', () => {
            metodosPago.forEach(m => m.classList.remove('metodos-pago__item--activo'));
            item.classList.add('metodos-pago__item--activo');
            const radio = item.querySelector('input[type="radio"]');
            if (radio) radio.checked = true;
        });
    });

    // 3. Leaflet Map para Checkout
    const contenedorMapa = document.getElementById('mapa-checkout');
    if (contenedorMapa && typeof L !== 'undefined') {
        const comLat = parseFloat(contenedorMapa.dataset.comercioLat) || -34.603722;
        const comLng = parseFloat(contenedorMapa.dataset.comercioLng) || -58.381592;
        const kmGratis = parseFloat(contenedorMapa.dataset.kmGratis) || 3.0;

        const mapa = L.map('mapa-checkout').setView([comLat, comLng], 14);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '&copy; OpenStreetMap contributors'
        }).addTo(mapa);

        // Marcador del local comercial
        const marcadorComercio = L.marker([comLat, comLng]).addTo(mapa);
        marcadorComercio.bindPopup('<strong>Nuestro Local</strong><br>Punto de partida de tu pedido').openPopup();

        // Radio visual de entrega gratuita
        L.circle([comLat, comLng], {
            radius: kmGratis * 1000,
            color: '#10b981',
            fillColor: '#10b981',
            fillOpacity: 0.1,
            weight: 2
        }).addTo(mapa);

        // Marcador arrastrable del cliente
        // Posición inicial: 500m del local para demostración rápida
        const initLat = comLat + 0.005;
        const initLng = comLng + 0.005;

        marcadorCliente = L.marker([initLat, initLng], { draggable: true }).addTo(mapa);
        marcadorCliente.bindPopup('<strong>Tu Dirección</strong><br>Arrastráme a tu casa').openPopup();

        function actualizarPosicionCliente(lat, lng) {
            if (inputLat) inputLat.value = lat.toFixed(7);
            if (inputLng) inputLng.value = lng.toFixed(7);
            marcadorCliente.setLatLng([lat, lng]);
            calcularEnvioAPI(lat, lng);
        }

        marcadorCliente.on('dragend', function (e) {
            const pos = e.target.getLatLng();
            actualizarPosicionCliente(pos.lat, pos.lng);
        });

        mapa.on('click', function (e) {
            actualizarPosicionCliente(e.latlng.lat, e.latlng.lng);
        });

        // Cálculo inicial con la posición por defecto
        actualizarPosicionCliente(initLat, initLng);
    }

    // 4. Función de cálculo de costo de envío vía API
    async function calcularEnvioAPI(lat, lng) {
        try {
            const respuesta = await fetch('/api/calcular-envio', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ lat, lng })
            });
            const datos = await respuesta.json();

            if (datos.ok) {
                costoEnvioActual = parseFloat(datos.costo_envio) || 0.00;
                if (kpiDistancia) kpiDistancia.textContent = datos.distancia_formateada;
                if (kpiCosto) {
                    kpiCosto.textContent = datos.envio_gratis ? '¡Gratis ($0)!' : datos.costo_formateado;
                }
                window.recalcularTotalesCheckout();
            }
        } catch (e) {
            console.error('Error calculando envío:', e);
        }
    }

    // 5. Selector de Direcciones Guardadas (Cliente con cuenta)
    if (selectGuardada) {
        selectGuardada.addEventListener('change', () => {
            const opcion = selectGuardada.options[selectGuardada.selectedIndex];
            if (!opcion || !opcion.value) return;

            const dirTexto = opcion.dataset.direccion || '';
            const lat = parseFloat(opcion.dataset.lat);
            const lng = parseFloat(opcion.dataset.lng);

            if (inputDireccion) inputDireccion.value = dirTexto;

            if (lat && lng && marcadorCliente) {
                if (inputLat) inputLat.value = lat.toFixed(7);
                if (inputLng) inputLng.value = lng.toFixed(7);
                marcadorCliente.setLatLng([lat, lng]);
                calcularEnvioAPI(lat, lng);
            }
        });
    }

    // 6. Envío del Formulario de Checkout
    form.addEventListener('submit', async e => {
        e.preventDefault();
        if (errorMsg) {
            errorMsg.style.display = 'none';
            errorMsg.textContent = '';
        }

        const items = obtenerCarrito();
        if (items.length === 0) {
            mostrarError('Tu carrito de compras está vacío. Agregá productos antes de confirmar.');
            return;
        }

        const tipo = inputTipo.value;
        const nombreContacto = document.getElementById('nombre_contacto')?.value.trim();
        const telefonoContacto = document.getElementById('telefono_contacto')?.value.trim();

        if (!nombreContacto || !telefonoContacto) {
            mostrarError('Por favor completá tu nombre y teléfono de contacto.');
            return;
        }

        const direccionTexto = inputDireccion?.value.trim();
        const lat = parseFloat(inputLat?.value);
        const lng = parseFloat(inputLng?.value);

        if (tipo === 'delivery') {
            if (!direccionTexto) {
                mostrarError('Por favor ingresá tu dirección para la entrega a domicilio.');
                return;
            }
            if (!lat || !lng) {
                mostrarError('Por favor seleccioná tu ubicación precisa en el mapa para el delivery.');
                return;
            }
        }

        const metodoPago = document.querySelector('input[name="metodo_pago"]:checked')?.value || 'efectivo';

        const payload = {
            nombre_contacto: nombreContacto,
            telefono_contacto: telefonoContacto,
            tipo: tipo,
            metodo_pago: metodoPago,
            direccion_texto: direccionTexto,
            lat: (tipo === 'delivery') ? lat : null,
            lng: (tipo === 'delivery') ? lng : null,
            items: items.map(it => ({
                producto_id: it.producto_id,
                cantidad: it.cantidad,
                opciones: it.opciones || [],
                aderezos: it.aderezos || [],
                notas: it.notas || ''
            }))
        };

        // Estado visual de carga
        if (btnConfirmar) {
            btnConfirmar.disabled = true;
            btnConfirmar.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> <span>Confirmando tu pedido...</span>';
        }

        try {
            const respuesta = await fetch('/pedido/crear', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });

            const resultado = await respuesta.json();

            if (resultado.ok && resultado.redirect) {
                // Guardar pedido activo en localStorage para clientes invitados
                if (resultado.pedido_id) {
                    try {
                        localStorage.setItem('retail_ultimo_pedido', JSON.stringify({
                            id: resultado.pedido_id,
                            fecha: new Date().toISOString()
                        }));
                        sessionStorage.removeItem('banner_pedido_descartado');
                    } catch (e) {
                        console.error('Error guardando retail_ultimo_pedido:', e);
                    }
                }

                // Limpiar carrito local en almacenamiento sin re-renderizar la UI del checkout
                // para evitar el parpadeo de la alerta "Tu carrito está vacío" mientras se redirige
                localStorage.removeItem(CARRITO_STORAGE_KEY);

                if (btnConfirmar) {
                    btnConfirmar.innerHTML = '<i class="fa-solid fa-circle-check"></i> <span>¡Pedido Confirmado! Redirigiendo...</span>';
                }

                window.location.href = resultado.redirect;
            } else {
                mostrarError(resultado.mensaje || 'No se pudo registrar el pedido. Intenta nuevamente.');
                if (btnConfirmar) {
                    btnConfirmar.disabled = false;
                    btnConfirmar.innerHTML = '<i class="fa-solid fa-check"></i> <span>Confirmar y Enviar Pedido</span>';
                }
            }
        } catch (err) {
            console.error('Error al enviar pedido:', err);
            mostrarError('Ocurrió un error de conexión con el servidor. Por favor verificá tu conexión.');
            if (btnConfirmar) {
                btnConfirmar.disabled = false;
                btnConfirmar.innerHTML = '<i class="fa-solid fa-check"></i> <span>Confirmar y Enviar Pedido</span>';
            }
        }
    });

    function mostrarError(texto) {
        if (errorMsg) {
            errorMsg.textContent = texto;
            errorMsg.style.display = 'block';
            errorMsg.scrollIntoView({ behavior: 'smooth', block: 'center' });
        } else {
            alert(texto);
        }
    }

    // Inicializar totales
    window.recalcularTotalesCheckout();
}

// =========================================================================
// MÓDULO: SEGUIMIENTO EN VIVO & CUENTA REGRESIVA DE CANCELACIÓN
// =========================================================================

function initSeguimiento() {
    const contenedor = document.querySelector('.tracker-contenedor');
    if (!contenedor) return;

    // Sincronizar estado del pedido en LocalStorage
    const params = new URLSearchParams(window.location.search);
    const pedidoId = parseInt(params.get('id'), 10);
    if (pedidoId) {
        const esCancelado = !!document.querySelector('.tracker-contenedor .alerta--error');
        const pasosCompletados = document.querySelectorAll('.tracker-paso--completado').length;

        if (esCancelado || pasosCompletados >= 5) {
            localStorage.removeItem('retail_ultimo_pedido');
        } else {
            try {
                localStorage.setItem('retail_ultimo_pedido', JSON.stringify({
                    id: pedidoId,
                    fecha: new Date().toISOString()
                }));
                sessionStorage.removeItem('banner_pedido_descartado');
            } catch (e) {}
        }
    }

    const contador = document.getElementById('contador-segundos');
    if (!contador) return;

    let segundosRestantes = parseInt(contador.dataset.segundos, 10) || 0;

    function renderizarTiempo(seg) {
        if (seg <= 0) return '00:00';
        const mins = Math.floor(seg / 60);
        const secs = seg % 60;
        return `${String(mins).padStart(2, '0')}:${String(secs).padStart(2, '0')}`;
    }

    contador.textContent = renderizarTiempo(segundosRestantes);

    if (segundosRestantes <= 0) {
        desactivarVentana();
        return;
    }

    const intervalo = setInterval(() => {
        segundosRestantes--;
        contador.textContent = renderizarTiempo(segundosRestantes);

        if (segundosRestantes <= 0) {
            clearInterval(intervalo);
            desactivarVentana();
        }
    }, 1000);

    function desactivarVentana() {
        const tarjetaCancelacion = document.querySelector('.cancelacion-card');
        if (tarjetaCancelacion) {
            tarjetaCancelacion.innerHTML = `
                <div class="cancelacion-card__info">
                    <i class="fa-solid fa-fire-burner" style="color: #ea580c;"></i>
                    <div class="cancelacion-card__texto">
                        <strong>Pedido en preparación</strong>
                        <p style="margin: 0.2rem 0 0 0;">La ventana de cancelación ha finalizado y nuestro equipo de cocina ya se encuentra preparando tu pedido.</p>
                    </div>
                </div>
            `;
        }
    }

    // Polling en vivo cada 10s: detecta cambios de estado y avisa con sonido
    if (pedidoId) {
        let estadoActual = null;

        const comprobarEstadoEnVivo = async () => {
            try {
                const res = await fetch(`/api/pedido-estado?id=${pedidoId}`);
                if (!res.ok) return;
                const datos = await res.json();
                if (datos.ok && datos.estado) {
                    if (estadoActual === null) {
                        estadoActual = datos.estado;
                    } else if (estadoActual !== datos.estado) {
                        estadoActual = datos.estado;
                        reproducirCampanaSeguimiento();
                        setTimeout(() => {
                            window.location.reload();
                        }, 900);
                    }
                }
            } catch (e) {}
        };

        comprobarEstadoEnVivo();
        setInterval(comprobarEstadoEnVivo, 10000);
    }
}

/**
 * Tono armónico sutil para avisar al cliente sobre un avance en el pedido
 */
function reproducirCampanaSeguimiento() {
    try {
        const AudioCtx = window.AudioContext || window.webkitAudioContext;
        if (!AudioCtx) return;
        const ctx = new AudioCtx();

        const osc1 = ctx.createOscillator();
        const gain1 = ctx.createGain();
        osc1.type = 'sine';
        osc1.frequency.setValueAtTime(523.25, ctx.currentTime);
        gain1.gain.setValueAtTime(0.2, ctx.currentTime);
        gain1.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.4);
        osc1.connect(gain1);
        gain1.connect(ctx.destination);
        osc1.start(ctx.currentTime);
        osc1.stop(ctx.currentTime + 0.4);

        const osc2 = ctx.createOscillator();
        const gain2 = ctx.createGain();
        osc2.type = 'sine';
        osc2.frequency.setValueAtTime(659.25, ctx.currentTime + 0.15);
        gain2.gain.setValueAtTime(0.25, ctx.currentTime + 0.15);
        gain2.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.65);
        osc2.connect(gain2);
        gain2.connect(ctx.destination);
        osc2.start(ctx.currentTime + 0.15);
        osc2.stop(ctx.currentTime + 0.65);
    } catch (e) {}
}

// =========================================================================
// MÓDULO: NAVEGACIÓN Y FILTRO POR CATEGORÍAS DEL MENÚ
// =========================================================================

function initMenuCategorias() {
    const itemsCategorias = document.querySelectorAll('.menu-categorias__item');
    if (!itemsCategorias.length) return;

    itemsCategorias.forEach(item => {
        item.addEventListener('click', e => {
            e.preventDefault();
            itemsCategorias.forEach(i => i.classList.remove('menu-categorias__item--activo'));
            item.classList.add('menu-categorias__item--activo');

            const categoriaId = item.dataset.categoria;
            const todasLasSecciones = document.querySelectorAll('.menu-seccion');

            if (categoriaId === 'todos') {
                todasLasSecciones.forEach(sec => sec.style.display = 'block');
                window.scrollTo({ top: 0, behavior: 'smooth' });
            } else {
                const seccionObjetivo = document.getElementById(`cat-${categoriaId}`);
                if (seccionObjetivo) {
                    todasLasSecciones.forEach(sec => sec.style.display = 'block');
                    seccionObjetivo.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
            }
        });
    });
}

// =========================================================================
// MÓDULO: BANNER DE PEDIDO ACTIVO PARA CLIENTES INVITADOS
// =========================================================================

function initBannerPedidoActivo() {
    const banner = document.getElementById('banner-pedido-activo');
    if (!banner) return;

    const numSpan = document.getElementById('banner-pedido-num');
    const estadoSpan = document.getElementById('banner-pedido-estado');
    const enlace = document.getElementById('banner-pedido-enlace');
    const btnCerrar = document.getElementById('banner-pedido-cerrar');

    // Si ya estamos en la pantalla de seguimiento, no mostramos el banner de recordatorio
    const rutaActual = window.location.pathname;
    if (rutaActual.startsWith('/pedido/seguimiento')) {
        banner.classList.add('banner-pedido-activo--oculto');
        return;
    }

    // Si el usuario descartó el banner en esta sesión, respetamos su decisión
    if (sessionStorage.getItem('banner_pedido_descartado') === '1') {
        banner.classList.add('banner-pedido-activo--oculto');
        return;
    }

    const guardado = localStorage.getItem('retail_ultimo_pedido');
    if (!guardado) {
        banner.classList.add('banner-pedido-activo--oculto');
        return;
    }

    try {
        const datos = JSON.parse(guardado);
        if (!datos.id) {
            banner.classList.add('banner-pedido-activo--oculto');
            return;
        }

        // Si el pedido tiene más de 24 horas, expira automáticamente
        if (datos.fecha) {
            const fechaPedido = new Date(datos.fecha).getTime();
            const ahora = new Date().getTime();
            if (ahora - fechaPedido > 24 * 60 * 60 * 1000) {
                localStorage.removeItem('retail_ultimo_pedido');
                banner.classList.add('banner-pedido-activo--oculto');
                return;
            }
        }

        // Consultar el estado real en el servidor
        fetch(`/api/pedido-estado?id=${datos.id}`)
            .then(res => res.json())
            .then(data => {
                if (data.ok && data.activo) {
                    if (numSpan) numSpan.textContent = `#${data.id}`;
                    if (estadoSpan) estadoSpan.textContent = data.estado_texto;
                    if (enlace) enlace.href = `/pedido/seguimiento?id=${data.id}`;
                    banner.classList.remove('banner-pedido-activo--oculto');
                } else {
                    // Si ya fue entregado o cancelado, limpiar
                    localStorage.removeItem('retail_ultimo_pedido');
                    banner.classList.add('banner-pedido-activo--oculto');
                }
            })
            .catch(() => {
                // Si falla la red, mostramos el ID guardado por precaución
                if (numSpan) numSpan.textContent = `#${datos.id}`;
                if (enlace) enlace.href = `/pedido/seguimiento?id=${datos.id}`;
                banner.classList.remove('banner-pedido-activo--oculto');
            });

    } catch (e) {
        console.error('Error parseando retail_ultimo_pedido:', e);
        banner.classList.add('banner-pedido-activo--oculto');
    }

    if (btnCerrar) {
        btnCerrar.addEventListener('click', () => {
            banner.classList.add('banner-pedido-activo--oculto');
            sessionStorage.setItem('banner_pedido_descartado', '1');
        });
    }
}

/**
 * Consola Operativa de Cocina y Despacho (KDS)
 */
function initKDS() {
    const reloj = document.getElementById('reloj-texto');
    const btnRefrescar = document.getElementById('kds-btn-refrescar');
    const selectRefresco = document.getElementById('kds-autorefresco');
    const btnAudio = document.getElementById('kds-btn-audio');
    const audioTexto = document.getElementById('audio-estado-texto');

    if (!reloj) return; // Guard clause: solo ejecutar en /cocina

    // 1. Reloj digital en tiempo real
    function actualizarReloj() {
        const ahora = new Date();
        const horas = String(ahora.getHours()).padStart(2, '0');
        const minutos = String(ahora.getMinutes()).padStart(2, '0');
        const segundos = String(ahora.getSeconds()).padStart(2, '0');
        reloj.textContent = `${horas}:${minutos}:${segundos}`;
    }
    actualizarReloj();
    setInterval(actualizarReloj, 1000);

    // 2. Sistema de Audio Sintetizado (Ding-Dong) sin dependencias externas
    let audioHabilitado = localStorage.getItem('kds_audio') !== 'false';
    function actualizarBotonAudio() {
        if (audioTexto) {
            audioTexto.textContent = audioHabilitado ? 'Sonido: ON' : 'Sonido: OFF';
        }
        if (btnAudio) {
            btnAudio.style.opacity = audioHabilitado ? '1' : '0.6';
        }
    }
    actualizarBotonAudio();

    if (btnAudio) {
        btnAudio.addEventListener('click', () => {
            audioHabilitado = !audioHabilitado;
            localStorage.setItem('kds_audio', audioHabilitado ? 'true' : 'false');
            actualizarBotonAudio();
            if (audioHabilitado) {
                reproducirChime();
            }
        });
    }

    function reproducirChime() {
        if (!audioHabilitado) return;
        try {
            const AudioContext = window.AudioContext || window.webkitAudioContext;
            if (!AudioContext) return;
            const ctx = new AudioContext();

            // Primer tono (Ding - E5)
            const osc1 = ctx.createOscillator();
            const gain1 = ctx.createGain();
            osc1.type = 'sine';
            osc1.frequency.setValueAtTime(659.25, ctx.currentTime);
            gain1.gain.setValueAtTime(0.3, ctx.currentTime);
            gain1.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.45);
            osc1.connect(gain1);
            gain1.connect(ctx.destination);
            osc1.start(ctx.currentTime);
            osc1.stop(ctx.currentTime + 0.45);

            // Segundo tono (Dong - A5)
            const osc2 = ctx.createOscillator();
            const gain2 = ctx.createGain();
            osc2.type = 'sine';
            osc2.frequency.setValueAtTime(880, ctx.currentTime + 0.22);
            gain2.gain.setValueAtTime(0.35, ctx.currentTime + 0.22);
            gain2.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.8);
            osc2.connect(gain2);
            gain2.connect(ctx.destination);
            osc2.start(ctx.currentTime + 0.22);
            osc2.stop(ctx.currentTime + 0.8);
        } catch (e) {
            console.error('Error reproduciendo audio KDS:', e);
        }
    }

    // 3. Polling silencioso de pedidos activos
    let ultimoTotalActivos = null;
    let timerRefresco = null;

    async function verificarPedidos() {
        try {
            const res = await fetch('/api/cocina/pedidos');
            if (!res.ok) return;
            const data = await res.json();
            if (data.ok) {
                const totalActual = data.total_activos;
                if (ultimoTotalActivos !== null && totalActual > ultimoTotalActivos) {
                    reproducirChime();
                    setTimeout(() => window.location.reload(), 1200);
                }
                ultimoTotalActivos = totalActual;
            }
        } catch (e) {
            console.error('Error polling KDS:', e);
        }
    }

    verificarPedidos();

    function configurarAutoRefresco() {
        if (timerRefresco) clearInterval(timerRefresco);
        const segundos = parseInt(selectRefresco?.value || '30', 10);
        if (segundos > 0) {
            timerRefresco = setInterval(verificarPedidos, segundos * 1000);
        }
    }

    if (selectRefresco) {
        selectRefresco.addEventListener('change', configurarAutoRefresco);
        configurarAutoRefresco();
    }

    if (btnRefrescar) {
        btnRefrescar.addEventListener('click', () => {
            btnRefrescar.querySelector('i')?.classList.add('fa-spin');
            window.location.reload();
        });
    }
}

/**
 * Control del menú lateral desplegable / colapsable del Panel
 */
function initSidebar() {
    const layout = document.getElementById('panel-layout');
    if (!layout) return;

    const btnColapsar = document.getElementById('btn-colapsar-sidebar');
    const btnToggleTopbar = document.getElementById('panel-topbar-toggle');
    const btnCerrarMovil = document.getElementById('btn-cerrar-sidebar-movil');
    const backdrop = document.getElementById('panel-sidebar-backdrop');

    // Recuperar estado colapsado previo en Desktop
    const colapsadoGuardado = localStorage.getItem('sidebar_colapsado') === 'true';
    if (colapsadoGuardado && window.innerWidth >= 992) {
        layout.classList.add('panel-layout--sidebar-colapsado');
    }

    // Toggle colapso Desktop
    if (btnColapsar) {
        btnColapsar.addEventListener('click', () => {
            layout.classList.toggle('panel-layout--sidebar-colapsado');
            const estaColapsado = layout.classList.contains('panel-layout--sidebar-colapsado');
            localStorage.setItem('sidebar_colapsado', estaColapsado ? 'true' : 'false');
        });
    }

    // Toggle Mobile (abrir drawer)
    if (btnToggleTopbar) {
        btnToggleTopbar.addEventListener('click', () => {
            layout.classList.toggle('panel-layout--sidebar-abierto');
        });
    }

    // Cerrar drawer Mobile
    const cerrarDrawer = () => {
        layout.classList.remove('panel-layout--sidebar-abierto');
    };

    if (btnCerrarMovil) {
        btnCerrarMovil.addEventListener('click', cerrarDrawer);
    }

    if (backdrop) {
        backdrop.addEventListener('click', cerrarDrawer);
    }

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && layout.classList.contains('panel-layout--sidebar-abierto')) {
            cerrarDrawer();
        }
    });
}

/**
 * Menú desplegable para la cabecera pública en dispositivos móviles
 */
function initHeaderMobile() {
    const btnHamburguesa = document.getElementById('header-hamburguesa');
    const nav = document.getElementById('header-navegacion');
    if (!btnHamburguesa || !nav) return;

    btnHamburguesa.addEventListener('click', (e) => {
        e.stopPropagation();
        const estaAbierto = nav.classList.toggle('navegacion--activa');
        btnHamburguesa.setAttribute('aria-expanded', estaAbierto ? 'true' : 'false');

        const icono = btnHamburguesa.querySelector('i');
        if (icono) {
            if (estaAbierto) {
                icono.classList.remove('fa-bars');
                icono.classList.add('fa-xmark');
            } else {
                icono.classList.remove('fa-xmark');
                icono.classList.add('fa-bars');
            }
        }
    });

    // Cerrar al pulsar un enlace de navegación
    nav.querySelectorAll('a').forEach(enlace => {
        enlace.addEventListener('click', () => {
            nav.classList.remove('navegacion--activa');
            btnHamburguesa.setAttribute('aria-expanded', 'false');
            const icono = btnHamburguesa.querySelector('i');
            if (icono) {
                icono.classList.remove('fa-xmark');
                icono.classList.add('fa-bars');
            }
        });
    });

    // Cerrar al hacer clic fuera del menú
    document.addEventListener('click', (e) => {
        if (!nav.contains(e.target) && !btnHamburguesa.contains(e.target)) {
            if (nav.classList.contains('navegacion--activa')) {
                nav.classList.remove('navegacion--activa');
                btnHamburguesa.setAttribute('aria-expanded', 'false');
                const icono = btnHamburguesa.querySelector('i');
                if (icono) {
                    icono.classList.remove('fa-xmark');
                    icono.classList.add('fa-bars');
                }
            }
        }
    });

    // Cerrar con Escape
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && nav.classList.contains('navegacion--activa')) {
            nav.classList.remove('navegacion--activa');
            btnHamburguesa.setAttribute('aria-expanded', 'false');
            const icono = btnHamburguesa.querySelector('i');
            if (icono) {
                icono.classList.remove('fa-xmark');
                icono.classList.add('fa-bars');
            }
        }
    });
}


