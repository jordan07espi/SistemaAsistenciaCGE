// admin/assets/js/reportes.js

let paginaActualReporte = 1;
let chartPastelInstance = null;
let chartBarrasInstance = null;

document.addEventListener('DOMContentLoaded', () => {
    buscarReporte(1);
    cargarGraficos();
    cargarSedesEnFiltro();

    const form = document.getElementById('formFiltros');
    if (form) {
        form.addEventListener('submit', (e) => {
            e.preventDefault();
            buscarReporte(1);
        });
    }

    const btnExportar = document.getElementById('btnExportar');
    if (btnExportar) {
        btnExportar.addEventListener('click', () => {
            const inicio = document.getElementById('fechaInicio').value;
            const fin = document.getElementById('fechaFin').value;
            const sede = document.getElementById('sede').value;
            window.open(`../controllers/ExportarAsistencias.php?inicio=${inicio}&fin=${fin}&sede=${sede}`, '_blank');
        });
    }
});

async function buscarReporte(page = 1) {
    paginaActualReporte = page;
    const inicio = document.getElementById('fechaInicio').value;
    const fin = document.getElementById('fechaFin').value;
    const sede = document.getElementById('sede').value;
    const tbody = document.getElementById('tablaReporte');

    // Spinner adaptado
    tbody.innerHTML = '<tr><td colspan="6" class="p-8 text-center block md:table-cell">⏳ Cargando datos...</td></tr>';

    try {
        const response = await fetch('../controllers/ReporteAsistencia.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ fecha_inicio: inicio, fecha_fin: fin, sede: sede, page: page, limit: 10 })
        });

        const res = await response.json();

        if (res.status === 'success') {
            const datos = res.data;
            tbody.innerHTML = '';

            if (datos.length === 0) {
                tbody.innerHTML = '<tr><td colspan="6" class="p-8 text-center text-gray-500 block md:table-cell">No se encontraron registros en este rango.</td></tr>';
                document.getElementById('paginacionReportes').innerHTML = '';
                return;
            }

            datos.forEach(row => {
                const tipoClass = row.tipo === 'ENTRADA' ? 'text-green-600' : 'text-blue-600';
                
                let modoBadge = '';
                if (row.modo_registro === 'QR_AUTO') {
                    modoBadge = '<span class="text-xs text-gray-500 border border-gray-200 px-2 py-1 rounded bg-gray-50">QR Auto</span>';
                } else if (row.modo_registro && row.modo_registro.includes('MANUAL')) {
                    modoBadge = '<span class="text-xs text-yellow-700 bg-yellow-100 border border-yellow-200 px-2 py-1 rounded font-bold">MANUAL</span>';
                } else {
                    modoBadge = '<span class="text-xs text-gray-400">Sistema</span>';
                }

                // --- ESTRUCTURA RESPONSIVE (CARD vs ROW) ---
                const tr = `
                    <tr class="hover:bg-gray-50 border-b border-gray-100 last:border-0 transition block md:table-row bg-white md:bg-transparent shadow-sm md:shadow-none rounded-xl md:rounded-none border border-gray-200 md:border-0 p-4 md:p-0">
                        
                        <td class="p-2 md:p-4 block md:table-cell flex justify-between items-center md:border-none border-b border-gray-50 last:border-0">
                            <span class="font-bold text-xs text-gray-400 uppercase md:hidden">Fecha:</span>
                            <span class="font-mono text-sm text-gray-600">${row.fecha_hora}</span>
                        </td>

                        <td class="p-2 md:p-4 block md:table-cell flex justify-between items-center md:border-none border-b border-gray-50 last:border-0">
                            <span class="font-bold text-xs text-gray-400 uppercase md:hidden">Cédula:</span>
                            <span class="font-mono text-xs font-bold text-gray-500">${row.cedula}</span>
                        </td>

                        <td class="p-2 md:p-4 block md:table-cell flex justify-between items-center md:border-none border-b border-gray-50 last:border-0">
                            <span class="font-bold text-xs text-gray-400 uppercase md:hidden">Nombre:</span>
                            <span class="font-semibold text-gray-800 text-right md:text-left">${row.nombre_completo}</span>
                        </td>

                        <td class="p-2 md:p-4 block md:table-cell flex justify-between items-center md:border-none border-b border-gray-50 last:border-0">
                            <span class="font-bold text-xs text-gray-400 uppercase md:hidden">Tipo:</span>
                            <span class="font-bold ${tipoClass}">${row.tipo}</span>
                        </td>

                        <td class="p-2 md:p-4 block md:table-cell flex justify-between items-center md:border-none border-b border-gray-50 last:border-0">
                            <span class="font-bold text-xs text-gray-400 uppercase md:hidden">Sede:</span>
                            <span class="text-xs text-gray-500 uppercase">${row.sede_registro}</span>
                        </td>

                        <td class="p-2 md:p-4 block md:table-cell flex justify-between items-center">
                            <span class="font-bold text-xs text-gray-400 uppercase md:hidden">Modo:</span>
                            ${modoBadge}
                        </td>
                    </tr>
                `;
                tbody.innerHTML += tr;
            });

            renderizarPaginacionReportes('paginacionReportes', res.pagination, buscarReporte);

        } else {
            tbody.innerHTML = `<tr><td colspan="6" class="p-8 text-center text-red-500 block md:table-cell">Error: ${res.message}</td></tr>`;
        }

    } catch (error) {
        console.error(error);
        tbody.innerHTML = '<tr><td colspan="6" class="p-8 text-center text-red-500 block md:table-cell">Error de conexión.</td></tr>';
    }
}

function renderizarPaginacionReportes(containerId, pagination, callbackCarga) {
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
        const activeClass = i === current_page ? 'bg-blue-600 text-white border-blue-600 shadow-md transform scale-105' : 'bg-white hover:bg-gray-100 text-gray-700 border-gray-300';
        html += `<button onclick="${callbackCarga.name}(${i})" class="px-3 py-1 border rounded ${activeClass} transition mx-1">${i}</button>`;
    }

    if (current_page < total_pages) {
        html += `<button onclick="${callbackCarga.name}(${current_page + 1})" class="px-3 py-1 border rounded hover:bg-gray-200 bg-white shadow-sm transition">»</button>`;
    }

    html += `<span class="text-xs text-gray-400 ml-4 font-mono">Total: ${total_records}</span>`;
    container.innerHTML = html;
}

async function cargarGraficos() {
    if (!document.getElementById('chartEstado') || !document.getElementById('chartSemana')) return;
    try {
        const response = await fetch('../controllers/ObtenerEstadisticas.php');
        const res = await response.json();
        if (res.status === 'success') {
            renderizarPastel(res.pie);
            renderizarBarras(res.bar);
        }
    } catch (error) { console.error("Error gráficos", error); }
}

function renderizarPastel(datos) {
    const ctx = document.getElementById('chartEstado').getContext('2d');
    if (chartPastelInstance) chartPastelInstance.destroy();
    chartPastelInstance = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: datos.labels,
            datasets: [{
                data: datos.data,
                backgroundColor: ['#16a34a', '#94a3b8'],
                borderWidth: 0, hoverOffset: 4
            }]
        },
        options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } } }
    });
}

function renderizarBarras(datos) {
    const ctx = document.getElementById('chartSemana').getContext('2d');
    if (chartBarrasInstance) chartBarrasInstance.destroy();
    chartBarrasInstance = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: datos.labels,
            datasets: [{
                label: 'Asistencias',
                data: datos.data,
                backgroundColor: '#2563eb', borderRadius: 4
            }]
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            scales: { y: { beginAtZero: true, ticks: { precision: 0 } } },
            plugins: { legend: { display: false } }
        }
    });
}

async function cargarSedesEnFiltro() {
    const select = document.getElementById('sede');
    if (!select) return;
    const valorActual = select.value;
    try {
        const res = await fetch('../controllers/SedeController.php');
        const sedes = await res.json();
        select.innerHTML = '<option value="TODAS">TODAS LAS SEDES</option>';
        sedes.forEach(s => {
            const option = document.createElement('option');
            option.value = s.nombre; option.textContent = s.nombre;
            select.appendChild(option);
        });
        if (valorActual) select.value = valorActual;
    } catch (e) { console.error("Error cargando sedes", e); }
}