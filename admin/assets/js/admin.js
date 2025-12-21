// admin/assets/js/admin.js

// --- VARIABLES GLOBALES ---
let paginaActual = 1;
let debounceTimer; 

// --- INICIALIZACIÓN ---
document.addEventListener('DOMContentLoaded', () => {
    // 1. Cargar tabla inicial
    cargarColaboradores(1);

    // 2. Iniciar Sistema de Notificaciones en Tiempo Real
    iniciarSSE();

    // ==========================================
    //  SOLUCIÓN 1: LÓGICA DEL MENÚ HAMBURGUESA
    // ==========================================
    const mobileMenuBtn = document.getElementById('mobileMenuBtn');
    const closeSidebarBtn = document.getElementById('closeSidebarBtn');
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('mobileOverlay');

    function toggleSidebar() {
        // Alternar clase para mostrar/ocultar sidebar (quita el translate negativo)
        sidebar.classList.toggle('-translate-x-full');
        
        // Manejar el overlay (fondo oscuro)
        if (overlay.classList.contains('hidden')) {
            overlay.classList.remove('hidden');
            setTimeout(() => overlay.classList.remove('opacity-0'), 10); // Transición suave
        } else {
            overlay.classList.add('opacity-0');
            setTimeout(() => overlay.classList.add('hidden'), 300); // Esperar transición
        }
    }

    // Eventos del menú
    if (mobileMenuBtn) mobileMenuBtn.addEventListener('click', toggleSidebar);
    if (closeSidebarBtn) closeSidebarBtn.addEventListener('click', toggleSidebar);
    if (overlay) overlay.addEventListener('click', toggleSidebar);

    // ==========================================
    //  FIN SOLUCIÓN MENÚ
    // ==========================================

    // 3. Evento: Búsqueda Rápida (con Debounce)
    const inputBusqueda = document.getElementById('busquedaColaborador');
    if (inputBusqueda) {
        inputBusqueda.addEventListener('input', () => {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(() => {
                cargarColaboradores(1); 
            }, 300);
        });
    }

    // 4. Evento: Importar Excel
    const formImportar = document.getElementById('formImportar');
    if (formImportar) {
        formImportar.addEventListener('submit', async (e) => {
            e.preventDefault();
            const fileInput = document.getElementById('archivoExcel');
            
            if (!fileInput.files[0]) {
                alert("Por favor selecciona un archivo .xlsx");
                return;
            }

            const formData = new FormData();
            formData.append('excel_file', fileInput.files[0]);

            mostrarEstado('⏳ Procesando archivo...', 'bg-blue-100 text-blue-700');

            try {
                const response = await fetch('../controllers/ImportarColaboradores.php', {
                    method: 'POST',
                    body: formData
                });
                const data = await response.json();

                if (data.status === 'success') {
                    mostrarEstado(`✅ Importación completada. Insertados: ${data.insertados}. Errores: ${data.errores}`, 'bg-green-100 text-green-700');
                    formImportar.reset();
                    cargarColaboradores(1);
                } else {
                    mostrarEstado(`❌ Error: ${data.message}`, 'bg-red-100 text-red-700');
                }
            } catch (error) {
                mostrarEstado('❌ Error de conexión', 'bg-red-100 text-red-700');
            }
        });
    }

    // 5. Evento: Formulario Nuevo Colaborador
    const formCrear = document.getElementById('formNuevoColaborador');
    if (formCrear) {
        formCrear.addEventListener('submit', async (e) => {
            e.preventDefault();
            await procesarFormulario(formCrear, '../controllers/GestionarColaborador.php', cerrarModalColaborador);
        });
    }

    // 6. Evento: Formulario Editar Colaborador
    const formEditar = document.getElementById('formEditarColaborador');
    if (formEditar) {
        formEditar.addEventListener('submit', async (e) => {
            e.preventDefault();
            await procesarFormulario(formEditar, '../controllers/EditarColaborador.php', cerrarModalEditar);
        });
    }

    // 7. Evento Global: Cerrar menús al hacer clic fuera
    window.addEventListener('click', (e) => {
        if (!e.target.closest('[onclick^="toggleDropdown"]')) {
            cerrarTodosLosDropdowns();
        }
    });
});

// ==========================================
//  LÓGICA PRINCIPAL DE LA TABLA (MODIFICADO PARA MÓVIL)
// ==========================================
async function cargarColaboradores(page = 1) {
    paginaActual = page;
    const tbody = document.getElementById('tablaColaboradores');
    const busqueda = document.getElementById('busquedaColaborador')?.value || '';

    if (!tbody) return;

    try {
        const url = `../controllers/ListarColaboradores.php?page=${page}&limit=20&search=${encodeURIComponent(busqueda)}`;
        const response = await fetch(url);
        const res = await response.json();

        tbody.innerHTML = '';

        if (res.status === 'success') {
            const { data, pagination } = res;

            if (data.length === 0) {
                // Ajuste para que el mensaje de "vacío" se vea bien en móvil
                tbody.innerHTML = '<tr><td colspan="5" class="p-8 text-center text-gray-400 italic block md:table-cell">No se encontraron resultados.</td></tr>';
                document.getElementById('paginacionColaboradores').innerHTML = '';
                return;
            }

            data.forEach(col => {
                const esActivo = col.activo == 1;
                
                // Estilos para la fila (Tarjeta en móvil, Fila normal en Desktop)
                // block md:table-row -> Se comporta como bloque (caja) en móvil, fila en PC
                const bgClass = esActivo ? 'bg-white hover:bg-gray-50' : 'bg-gray-100';
                const textClass = esActivo ? 'text-gray-700' : 'text-gray-400';
                
                const estadoBadge = col.estado_actual === 'DENTRO' 
                    ? '<span class="bg-green-100 text-green-700 px-2 py-1 rounded-full text-xs font-bold border border-green-200">DENTRO</span>'
                    : '<span class="bg-gray-100 text-gray-500 px-2 py-1 rounded-full text-xs font-bold border border-gray-200">FUERA</span>';

                const nombreSafe = col.nombre_completo.replace(/'/g, "\\'");
                const origenSafe = col.origen.replace(/'/g, "\\'");

                // Menú de Acciones
                const acciones = `
                    <div class="relative inline-block text-left dropdown-container w-full md:w-auto">
                        <button id="btn-action-${col.id}" onclick="toggleDropdown(event, '${col.id}')" class="w-full md:w-auto justify-center bg-white hover:bg-gray-50 border border-gray-300 text-gray-700 px-3 py-2 md:py-1 rounded-md text-sm md:text-xs font-bold inline-flex items-center gap-1 shadow-sm transition active:bg-gray-100 z-10 relative">
                            Acciones <i class="ph ph-caret-down text-gray-500"></i>
                        </button>
                        
                        <div id="dropdown-${col.id}" class="hidden absolute right-0 w-full md:w-48 bg-white rounded-xl shadow-2xl border border-gray-200 z-50 overflow-hidden ring-1 ring-black ring-opacity-5">
                            <div class="py-1">
                                <button onclick="abrirModalEditar('${col.id}', '${col.cedula}', '${nombreSafe}', '${origenSafe}')" class="w-full text-left flex items-center gap-2 px-4 py-3 text-sm text-gray-700 hover:bg-yellow-50 hover:text-yellow-700 transition border-b border-gray-100">
                                    <i class="ph ph-pencil-simple text-lg text-yellow-600"></i> Editar Datos
                                </button>
                                <a href="../controllers/GenerarCredencial.php?id=${col.id}" target="_blank" class="flex items-center gap-2 px-4 py-3 text-sm text-gray-700 hover:bg-blue-50 hover:text-blue-700 transition border-b border-gray-100">
                                    <i class="ph ph-id-card text-lg text-blue-500"></i> Credencial
                                </a>
                                <button onclick="toggleEstadoColaborador(${col.id}, ${esActivo ? 0 : 1})" class="w-full text-left flex items-center gap-2 px-4 py-3 text-sm ${esActivo ? 'text-red-600 hover:bg-red-50' : 'text-green-600 hover:bg-green-50'} transition">
                                    <i class="ph ${esActivo ? 'ph-prohibit' : 'ph-check-circle'} text-lg"></i>
                                    ${esActivo ? 'Deshabilitar' : 'Habilitar'}
                                </button>
                            </div>
                        </div>
                    </div>
                `;

                // ==========================================
                // SOLUCIÓN 2: RENDERIZADO TIPO TARJETA
                // ==========================================
                /*
                 Explicación de clases:
                 - tr: "block md:table-row" -> En móvil es un bloque (tarjeta), en PC es fila.
                 - td: "block md:table-cell" -> En móvil es bloque.
                 - flex justify-between: Pone la etiqueta (Cédula) a la izquierda y el valor a la derecha en móvil.
                 - border-b: Separa los items dentro de la tarjeta móvil.
                */
                const row = `
                    <tr id="row-${col.id}" class="${bgClass} transition border border-gray-200 md:border-b-gray-100 md:border-x-0 md:border-t-0 rounded-xl md:rounded-none shadow-sm md:shadow-none mb-4 md:mb-0 block md:table-row relative p-4 md:p-0">
                        
                        <td class="p-2 md:p-4 block md:table-cell flex justify-between items-center border-b border-gray-50 md:border-none">
                            <span class="font-bold text-xs text-gray-400 uppercase md:hidden">Cédula:</span>
                            <span class="font-mono font-bold ${textClass}">${col.cedula}</span>
                        </td>

                        <td class="p-2 md:p-4 block md:table-cell flex justify-between items-center border-b border-gray-50 md:border-none">
                            <span class="font-bold text-xs text-gray-400 uppercase md:hidden">Nombre:</span>
                            <span class="font-semibold text-right md:text-left ${textClass}">
                                ${col.nombre_completo} 
                                ${!esActivo ? '<span class="text-[10px] bg-red-100 text-red-600 px-2 py-0.5 rounded-full ml-2 font-bold uppercase">Inactivo</span>' : ''}
                            </span>
                        </td>

                        <td class="p-2 md:p-4 block md:table-cell flex justify-between items-center border-b border-gray-50 md:border-none">
                            <span class="font-bold text-xs text-gray-400 uppercase md:hidden">Origen:</span>
                            <span class="text-xs font-medium uppercase tracking-wide ${textClass}">${col.origen}</span>
                        </td>

                        <td class="p-2 md:p-4 block md:table-cell flex justify-between items-center border-b border-gray-50 md:border-none">
                            <span class="font-bold text-xs text-gray-400 uppercase md:hidden">Estado:</span>
                            ${estadoBadge}
                        </td>

                        <td class="p-2 md:p-4 block md:table-cell text-center mt-2 md:mt-0">
                            ${acciones}
                        </td>
                    </tr>
                `;
                tbody.innerHTML += row;
            });

            renderizarPaginacion('paginacionColaboradores', pagination, cargarColaboradores);
        }
    } catch (error) {
        console.error("Error cargando lista", error);
        tbody.innerHTML = '<tr><td colspan="5" class="p-4 text-center text-red-500 block md:table-cell">Error de conexión con el servidor.</td></tr>';
    }
}

// ==========================================
//  GESTIÓN DE MENÚS DESPLEGABLES (DROPDOWNS)
// ==========================================
window.toggleDropdown = function(event, id) {
    event.stopPropagation(); 

    const menuID = `dropdown-${id}`;
    const menu = document.getElementById(menuID);
    const row = document.getElementById(`row-${id}`);
    const btn = document.getElementById(`btn-action-${id}`);

    if (menu && !menu.classList.contains('hidden')) {
        cerrarTodosLosDropdowns();
        return;
    }

    cerrarTodosLosDropdowns();

    if (menu && row && btn) {
        row.style.zIndex = '50';
        row.style.position = 'relative';

        const rect = btn.getBoundingClientRect();
        const spaceBelow = window.innerHeight - rect.bottom;
        const menuHeight = 200; 

        menu.classList.remove('top-full', 'mt-1', 'origin-top-right', 'bottom-full', 'mb-1', 'origin-bottom-right');

        if (spaceBelow < menuHeight) {
            menu.classList.add('bottom-full', 'mb-1', 'origin-bottom-right');
        } else {
            menu.classList.add('top-full', 'mt-1', 'origin-top-right');
        }

        menu.classList.remove('hidden');
    }
};

function cerrarTodosLosDropdowns() {
    document.querySelectorAll('[id^="dropdown-"]').forEach(el => el.classList.add('hidden'));
    document.querySelectorAll('tr[id^="row-"]').forEach(r => {
        r.style.zIndex = 'auto';
        r.style.position = ''; // Regresar a comportamiento normal
    });
}

// ==========================================
//  PROCESAMIENTO DE FORMULARIOS
// ==========================================
async function procesarFormulario(form, url, callbackCierre) {
    const formData = new FormData(form);
    const data = Object.fromEntries(formData.entries());

    try {
        const response = await fetch(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });
        const res = await response.json();

        if (res.status === 'success') {
            alert('✅ ' + res.message);
            form.reset();
            callbackCierre();
            cargarColaboradores(paginaActual);
        } else {
            alert('❌ ' + res.message);
        }
    } catch (error) {
        console.error(error);
        alert('Error de conexión');
    }
}

// ==========================================
//  CAMBIAR ESTADO (ACTIVO/INACTIVO)
// ==========================================
async function toggleEstadoColaborador(id, nuevoEstado) {
    const accion = nuevoEstado === 1 ? 'habilitar' : 'deshabilitar';
    if (!confirm(`¿Estás seguro de ${accion} a este colaborador?`)) return;

    try {
        const response = await fetch('../controllers/CambiarEstadoColaborador.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: id, activo: nuevoEstado })
        });
        const res = await response.json();

        if (res.status === 'success') {
            mostrarEstado(`✅ ${res.message}`, 'bg-green-100 text-green-700');
            cargarColaboradores(paginaActual); 
        } else {
            alert('Error: ' + res.message);
        }
    } catch (e) {
        alert('Error de conexión');
    }
}

// ==========================================
//  PAGINACIÓN
// ==========================================
function renderizarPaginacion(containerId, pagination, callbackCarga) {
    const container = document.getElementById(containerId);
    if (!container) return;

    let html = '';
    const { total_pages, current_page, total_records } = pagination;

    if (current_page > 1) {
        html += `<button onclick="${callbackCarga.name}(${current_page - 1})" class="px-3 py-1 border rounded hover:bg-gray-200 bg-white shadow-sm transition">«</button>`;
    }

    let startPage = Math.max(1, current_page - 2);
    let endPage = Math.min(total_pages, current_page + 2);

    for (let i = startPage; i <= endPage; i++) {
        const activeClass = i === current_page 
            ? 'bg-blue-600 text-white border-blue-600 shadow-md transform scale-105' 
            : 'bg-white hover:bg-gray-100 text-gray-700 border-gray-300';
        
        html += `<button onclick="${callbackCarga.name}(${i})" class="px-3 py-1 border rounded ${activeClass} transition mx-1">${i}</button>`;
    }

    if (current_page < total_pages) {
        html += `<button onclick="${callbackCarga.name}(${current_page + 1})" class="px-3 py-1 border rounded hover:bg-gray-200 bg-white shadow-sm transition">»</button>`;
    }

    html += `<span class="text-xs text-gray-400 ml-4 font-mono">Total: ${total_records}</span>`;
    container.innerHTML = html;
}

// ==========================================
//  SSE (NOTIFICACIONES REAL-TIME)
// ==========================================
function iniciarSSE() {
    if (!window.EventSource) return;

    const evtSource = new EventSource('../controllers/SSE_Updates.php');

    evtSource.onmessage = function(event) {
        try {
            const data = JSON.parse(event.data);
            mostrarToast(data);
            if (paginaActual === 1) cargarColaboradores(1);
        } catch (e) {
            console.error("SSE Error:", e);
        }
    };
}

function mostrarToast(data) {
    const container = document.getElementById('toastContainer');
    if (!container) return;

    const toast = document.createElement('div');
    const colorClass = data.tipo === 'ENTRADA' ? 'border-green-500 bg-white' : 'border-blue-500 bg-white';
    const icono = data.tipo === 'ENTRADA' ? '👋' : '🏠';
    const textoColor = data.tipo === 'ENTRADA' ? 'text-green-700' : 'text-blue-700';

    toast.className = `flex items-center gap-4 p-4 rounded-lg shadow-2xl border-l-4 ${colorClass} transform transition-all duration-500 translate-x-full opacity-0 min-w-[320px] bg-white bg-opacity-95 backdrop-blur-sm z-50`;
    
    toast.innerHTML = `
        <div class="text-3xl p-2 bg-gray-50 rounded-full shadow-inner">${icono}</div>
        <div class="flex-1">
            <h4 class="font-bold text-sm ${textoColor} uppercase tracking-wider mb-1">${data.tipo} DETECTADA</h4>
            <p class="font-bold text-gray-800 text-lg leading-tight">${data.nombre}</p>
            <div class="flex justify-between items-center mt-2 text-xs text-gray-500 font-mono border-t border-gray-100 pt-1">
                <span>📍 ${data.sede}</span>
                <span>🕒 ${data.hora}</span>
            </div>
        </div>
    `;

    container.appendChild(toast);
    requestAnimationFrame(() => toast.classList.remove('translate-x-full', 'opacity-0'));
    setTimeout(() => {
        toast.classList.add('translate-x-full', 'opacity-0');
        setTimeout(() => toast.remove(), 500);
    }, 5000);
}

// ==========================================
//  UTILS Y MODALES
// ==========================================
function mostrarEstado(msg, classes) {
    const div = document.getElementById('uploadStatus');
    if (div) {
        div.className = `mt-4 p-3 rounded-lg text-sm ${classes} border border-current shadow-sm`;
        div.textContent = msg;
        div.classList.remove('hidden');
        setTimeout(() => div.classList.add('hidden'), 6000);
    }
}

window.abrirModalColaborador = () => document.getElementById('modalNuevoColaborador').classList.remove('hidden');
window.cerrarModalColaborador = () => document.getElementById('modalNuevoColaborador').classList.add('hidden');

window.abrirModalEditar = function(id, cedula, nombre, origen) {
    document.getElementById('editId').value = id;
    document.getElementById('editCedula').value = cedula;
    document.getElementById('editNombre').value = nombre;
    document.getElementById('editOrigen').value = origen;
    document.getElementById('modalEditarColaborador').classList.remove('hidden');
    cerrarTodosLosDropdowns();
}

window.cerrarModalEditar = () => document.getElementById('modalEditarColaborador').classList.add('hidden');

// Exponer funciones necesarias
window.cargarColaboradores = cargarColaboradores;
window.toggleEstadoColaborador = toggleEstadoColaborador;