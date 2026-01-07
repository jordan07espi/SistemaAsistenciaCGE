// admin/assets/js/reportes.js

// --- VARIABLES GLOBALES ---
let chartBarras = null;
let chartPie = null;
let paginaActual = 1; // Variable global para rastrear página

// --- INICIALIZACIÓN ---
document.addEventListener('DOMContentLoaded', () => {
    
    // 1. Cargar gráficos y tabla inicial
    cargarEstadisticas();
    cargarReporteTabla(1);

    // 2. Filtro Sede (Gráficos)
    const filtroSede = document.getElementById('filtroSedeReporte'); 
    if (filtroSede) {
        filtroSede.addEventListener('change', () => {
            const sede = filtroSede.value;
            cargarEstadisticas(sede);
        });
    }

    // 3. Filtro Tabla (Formulario)
    const formFiltros = document.getElementById('formFiltros');
    if (formFiltros) {
        formFiltros.addEventListener('submit', (e) => {
            e.preventDefault();
            cargarReporteTabla(1); // Al filtrar, volver siempre a la página 1
        });
    }

    // 4. Exportar Excel
    const btnExportar = document.getElementById('btnExportar');
    if (btnExportar) {
        btnExportar.addEventListener('click', () => {
            const inicio = document.getElementById('fechaInicio').value;
            const fin = document.getElementById('fechaFin').value;
            const sede = document.getElementById('filtroSedeReporte').value;
            const url = `../controllers/ExportarAsistencias.php?inicio=${inicio}&fin=${fin}&sede=${encodeURIComponent(sede)}`;
            window.location.href = url;
        });
    }
});

// ==========================================
//  LÓGICA DE TABLA Y PAGINACIÓN
// ==========================================
async function cargarReporteTabla(page = 1) {
    paginaActual = page;
    const tbody = document.getElementById('tablaReporte');
    
    // Obtener valores actuales de los filtros
    const inicio = document.getElementById('fechaInicio').value;
    const fin = document.getElementById('fechaFin').value;
    const sede = document.getElementById('filtroSedeReporte').value;

    tbody.innerHTML = '<tr><td colspan="6" class="p-8 text-center text-gray-500 animate-pulse">⏳ Cargando registros...</td></tr>';

    try {
        const url = `../controllers/ReporteAsistencia.php?page=${page}&inicio=${inicio}&fin=${fin}&sede=${encodeURIComponent(sede)}`;
        const response = await fetch(url);
        const res = await response.json();

        tbody.innerHTML = '';

        if (res.status === 'success' && res.data && res.data.length > 0) {
            
            res.data.forEach(row => {
                const tipoBadge = row.tipo === 'ENTRADA' 
                    ? '<span class="bg-green-100 text-green-700 px-2 py-1 rounded text-xs font-bold border border-green-200">ENTRADA</span>'
                    : '<span class="bg-red-100 text-red-700 px-2 py-1 rounded text-xs font-bold border border-red-200">SALIDA</span>';
                
                const modoIcon = row.modo_registro === 'QR_AUTO' 
                    ? '<span title="Automático" class="text-blue-500"><i class="ph ph-robot"></i> Auto</span>' 
                    : '<span title="Manual" class="text-yellow-600 font-bold"><i class="ph ph-hand-pointing"></i> Manual</span>';

                const html = `
                    <tr class="hover:bg-gray-50 transition border-b border-gray-100 last:border-0">
                        <td class="p-4 font-mono text-sm text-gray-600">${row.fecha_hora}</td>
                        <td class="p-4">
                            <div class="font-bold text-gray-800">${row.nombre_completo}</div>
                            <div class="text-xs text-gray-400 font-mono">CI: ${row.cedula}</div>
                        </td>
                        <td class="p-4 text-xs font-bold text-gray-500 uppercase">${row.sede_registro}</td>
                        <td class="p-4 text-center">${tipoBadge}</td>
                        <td class="p-4 text-center text-xs">${modoIcon}</td>
                        <td class="p-4 text-center">
                            <span class="w-2 h-2 rounded-full inline-block ${row.tipo === 'ENTRADA' ? 'bg-green-500' : 'bg-red-500'}"></span>
                        </td>
                    </tr>
                `;
                tbody.innerHTML += html;
            });

            // Usamos la nueva lógica de paginación numerada
            renderizarPaginacion('paginacionContainer', res.pagination, cargarReporteTabla);

        } else {
            tbody.innerHTML = '<tr><td colspan="6" class="p-12 text-center text-gray-400 italic bg-gray-50 rounded-lg border border-dashed border-gray-200">No se encontraron registros en este rango.</td></tr>';
            document.getElementById('paginacionContainer').innerHTML = '';
        }

    } catch (error) {
        console.error("Error tabla:", error);
        tbody.innerHTML = '<tr><td colspan="6" class="p-8 text-center text-red-500 font-bold">Error de conexión con el servidor.</td></tr>';
    }
}

// ==========================================
//  NUEVA LÓGICA DE PAGINACIÓN (Igual a admin.js)
// ==========================================
function renderizarPaginacion(containerId, pagination, callbackCarga) {
    const container = document.getElementById(containerId);
    if (!container) return;

    let html = '';
    // Aseguramos que sean números enteros
    const current_page = parseInt(pagination.current_page);
    const total_pages = parseInt(pagination.total_pages);
    const total_records = pagination.total_records;

    // Contenedor flexible
    html += '<div class="flex items-center justify-end gap-1">';

    // Botón ANTERIOR
    if (current_page > 1) {
        html += `<button onclick="${callbackCarga.name}(${current_page - 1})" class="px-3 py-1 border rounded hover:bg-gray-200 bg-white shadow-sm transition">«</button>`;
    }

    // Lógica de rango de páginas (Muestra actual +/- 2 páginas)
    let startPage = Math.max(1, current_page - 2);
    let endPage = Math.min(total_pages, current_page + 2);

    for (let i = startPage; i <= endPage; i++) {
        const activeClass = i === current_page 
            ? 'bg-blue-600 text-white border-blue-600 shadow-md transform scale-105' 
            : 'bg-white hover:bg-gray-100 text-gray-700 border-gray-300';
        
        html += `<button onclick="${callbackCarga.name}(${i})" class="px-3 py-1 border rounded ${activeClass} transition mx-1 font-mono text-sm">${i}</button>`;
    }

    // Botón SIGUIENTE
    if (current_page < total_pages) {
        html += `<button onclick="${callbackCarga.name}(${current_page + 1})" class="px-3 py-1 border rounded hover:bg-gray-200 bg-white shadow-sm transition">»</button>`;
    }

    html += '</div>';
    
    // Texto de total de registros
    html += `<div class="text-xs text-gray-400 mt-2 text-right font-mono">Total: ${total_records} registros | Pág ${current_page} de ${total_pages}</div>`;

    container.innerHTML = html;
}

// ==========================================
//  GRÁFICOS (Chart.js)
// ==========================================
async function cargarEstadisticas(sede = '') {
    try {
        const url = `../controllers/ObtenerEstadisticas.php?sede=${encodeURIComponent(sede)}`;
        const response = await fetch(url);
        const data = await response.json();

        if (data.error) return;

        if (data.resumen) {
            const elTotal = document.getElementById('statTotal');
            if (elTotal) animateValue(elTotal, 0, data.resumen.total || 0, 800);
        }

        renderizarGraficoBarras(data.asistencia_semanal);
        renderizarGraficoPie(data.distribucion_estado);

    } catch (error) { 
        console.error("Stats Error:", error); 
    }
}

function renderizarGraficoBarras(datos) {
    const canvas = document.getElementById('chartAsistenciaSemanal');
    if (!canvas) return;
    const ctx = canvas.getContext('2d');
    
    const etiquetas = datos.map(d => formatearFecha(d.fecha));
    const valores = datos.map(d => d.total);

    if (chartBarras) chartBarras.destroy();

    chartBarras = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: etiquetas,
            datasets: [{
                label: 'Asistencias',
                data: valores,
                backgroundColor: 'rgba(59, 130, 246, 0.7)',
                borderColor: 'rgba(59, 130, 246, 1)',
                borderWidth: 1,
                borderRadius: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: { 
                y: { beginAtZero: true, ticks: { stepSize: 1 }, grid: { color: '#f3f4f6' } }, 
                x: { grid: { display: false } } 
            },
            plugins: { legend: { display: false } }
        }
    });
}

function renderizarGraficoPie(datos) {
    const canvas = document.getElementById('chartDistribucion');
    if (!canvas) return;
    const ctx = canvas.getContext('2d');

    let dentro = 0, fuera = 0;
    datos.forEach(d => {
        const estado = d.estado.toUpperCase(); 
        if (estado === 'DENTRO' || estado === 'ENTRADA') dentro += parseInt(d.total);
        else fuera += parseInt(d.total);
    });

    if (chartPie) chartPie.destroy();

    chartPie = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: ['Presentes', 'Fuera'],
            datasets: [{
                data: [dentro, fuera],
                backgroundColor: ['rgba(34, 197, 94, 0.7)', 'rgba(229, 231, 235, 0.7)'],
                borderColor: ['rgba(34, 197, 94, 1)', 'rgba(209, 213, 219, 1)'],
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { 
                legend: { position: 'bottom', labels: { usePointStyle: true, boxWidth: 6 } } 
            },
            cutout: '75%'
        }
    });
}

// --- UTILS ---
function formatearFecha(f) {
    const dias = ['Dom', 'Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb'];
    const d = new Date(f + 'T00:00:00'); 
    return `${dias[d.getDay()]} ${d.getDate()}`;
}

function animateValue(obj, start, end, duration) {
    let startTimestamp = null;
    const step = (timestamp) => {
        if (!startTimestamp) startTimestamp = timestamp;
        const progress = Math.min((timestamp - startTimestamp) / duration, 1);
        obj.innerHTML = Math.floor(progress * (end - start) + start);
        if (progress < 1) window.requestAnimationFrame(step);
    };
    window.requestAnimationFrame(step);
}

// Exponer la función al contexto global para que el HTML onclick="" pueda verla
window.cargarReporteTabla = cargarReporteTabla;