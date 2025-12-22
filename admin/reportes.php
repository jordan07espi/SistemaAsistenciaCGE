<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

require_once 'partials/header.php';
require_once __DIR__ . '/../models/SedeDAO.php';

// Obtener las sedes para el filtro desplegable
$sedeDAO = new SedeDAO();
$sedes = $sedeDAO->listarTodas();
?>

<div class="p-4 md:p-8">
    
    <div class="flex flex-col md:flex-row justify-between items-center mb-6 gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-700">Reportes y Estadísticas</h1>
            <p class="text-sm text-gray-500">Visualiza el rendimiento de la asistencia en tiempo real.</p>
        </div>
        
        <div class="bg-blue-600 text-white px-4 py-2 rounded-lg shadow-lg font-mono text-lg font-bold">
            <?php echo date('d/m/Y'); ?>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        
        <div class="bg-white p-4 rounded-xl shadow-sm border-l-4 border-blue-500 flex flex-col justify-center h-48 relative overflow-hidden">
            <div class="z-10">
                <p class="text-gray-500 text-xs font-bold uppercase tracking-wider">Asistencias Hoy</p>
                <h2 class="text-5xl font-bold text-blue-600 mt-2" id="statTotal">0</h2>
                <p class="text-xs text-gray-400 mt-1">Registros en tiempo real</p>
            </div>
            <div class="absolute -right-4 -bottom-4 text-blue-50 opacity-50">
                <i class="ph ph-users-three text-9xl"></i>
            </div>
        </div>

        <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-200 h-48 flex flex-col">
            <div class="flex justify-between items-center mb-2">
                <h3 class="font-bold text-gray-700 text-sm">Estado Actual</h3>
                <span class="text-[10px] bg-gray-100 text-gray-500 px-2 py-0.5 rounded">Hoy</span>
            </div>
            <div class="flex-1 relative w-full min-h-0">
                <canvas id="chartDistribucion"></canvas>
            </div>
        </div>

        <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-200 h-48 flex flex-col">
            <div class="flex justify-between items-center mb-2">
                <h3 class="font-bold text-gray-700 text-sm">Tendencia (7 días)</h3>
                <span class="text-[10px] bg-gray-100 text-gray-500 px-2 py-0.5 rounded">Histórico</span>
            </div>
            <div class="flex-1 relative w-full min-h-0">
                <canvas id="chartAsistenciaSemanal"></canvas>
            </div>
        </div>

    </div>
    <div class="bg-white rounded-xl shadow-md border border-gray-200 overflow-hidden">
        
        <div class="p-6 border-b border-gray-100 bg-gray-50">
            <h3 class="font-bold text-gray-700 mb-4 text-lg">Reporte Detallado</h3>
            
            <form id="formFiltros" class="grid grid-cols-1 md:grid-cols-12 gap-4 items-end">
                <div class="md:col-span-3">
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Sede</label>
                    <div class="relative">
                        <i class="ph ph-buildings absolute left-3 top-3 text-gray-400"></i>
                        <select id="filtroSedeReporte" name="sede" class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 text-sm">
                            <option value="">TODAS LAS SEDES</option>
                            <?php foreach ($sedes as $s): ?>
                                <option value="<?php echo htmlspecialchars($s['nombre']); ?>">
                                    <?php echo htmlspecialchars($s['nombre']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="md:col-span-3">
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Desde</label>
                    <input type="date" name="fecha_inicio" id="fechaInicio" value="<?php echo date('Y-m-d'); ?>" 
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 text-sm">
                </div>

                <div class="md:col-span-3">
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Hasta</label>
                    <input type="date" name="fecha_fin" id="fechaFin" value="<?php echo date('Y-m-d'); ?>" 
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 text-sm">
                </div>
                
                <div class="md:col-span-3 flex gap-2">
                    <button type="submit" class="flex-1 bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-lg transition text-sm flex justify-center items-center gap-2">
                        <i class="ph ph-magnifying-glass"></i> Buscar
                    </button>
                    
                    <button type="button" id="btnExportar" class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded-lg transition text-sm flex items-center justify-center" title="Descargar Excel">
                        <i class="ph ph-file-xls text-xl"></i>
                    </button>
                </div>
            </form>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-gray-600">
                <thead class="bg-gray-100 uppercase text-xs font-bold text-gray-500">
                    <tr>
                        <th class="p-4 whitespace-nowrap">Fecha / Hora</th>
                        <th class="p-4 whitespace-nowrap">Colaborador</th>
                        <th class="p-4 whitespace-nowrap">Sede</th>
                        <th class="p-4 whitespace-nowrap text-center">Tipo</th>
                        <th class="p-4 whitespace-nowrap text-center">Modo</th>
                        <th class="p-4 whitespace-nowrap text-center">Estado</th>
                    </tr>
                </thead>
                <tbody id="tablaReporte" class="divide-y divide-gray-100">
                    <tr>
                        <td colspan="6" class="p-8 text-center text-gray-400 italic">
                            Cargando datos...
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="assets/js/reportes.js"></script>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('formFiltros');
    cargarReporteTabla(1);

    form.addEventListener('submit', (e) => {
        e.preventDefault();
        cargarReporteTabla(1);
    });

    document.getElementById('btnExportar').addEventListener('click', () => {
        const inicio = document.getElementById('fechaInicio').value;
        const fin = document.getElementById('fechaFin').value;
        const sede = document.getElementById('filtroSedeReporte').value;
        const url = `../controllers/ExportarAsistencias.php?inicio=${inicio}&fin=${fin}&sede=${encodeURIComponent(sede)}`;
        window.location.href = url;
    });
});

async function cargarReporteTabla(page = 1) {
    const tbody = document.getElementById('tablaReporte');
    const inicio = document.getElementById('fechaInicio').value;
    const fin = document.getElementById('fechaFin').value;
    const sede = document.getElementById('filtroSedeReporte').value;

    tbody.innerHTML = '<tr><td colspan="6" class="p-8 text-center">⏳ Cargando datos...</td></tr>';

    try {
        const url = `../controllers/ReporteAsistencia.php?page=${page}&inicio=${inicio}&fin=${fin}&sede=${encodeURIComponent(sede)}`;
        const response = await fetch(url);
        const res = await response.json();

        tbody.innerHTML = '';

        if (res.data && res.data.length > 0) {
            res.data.forEach(row => {
                const tipoBadge = row.tipo === 'ENTRADA' 
                    ? '<span class="bg-green-100 text-green-700 px-2 py-1 rounded text-xs font-bold">ENTRADA</span>'
                    : '<span class="bg-red-100 text-red-700 px-2 py-1 rounded text-xs font-bold">SALIDA</span>';
                
                const modoIcon = row.modo_registro === 'QR_AUTO' 
                    ? '<span title="Automático" class="text-blue-500"><i class="ph ph-robot"></i> Auto</span>' 
                    : '<span title="Manual" class="text-yellow-600"><i class="ph ph-hand-pointing"></i> Manual</span>';

                const html = `
                    <tr class="hover:bg-gray-50 transition">
                        <td class="p-4 font-mono text-sm">${row.fecha_hora}</td>
                        <td class="p-4">
                            <div class="font-bold text-gray-800">${row.nombre_completo}</div>
                            <div class="text-xs text-gray-500">CI: ${row.cedula}</div>
                        </td>
                        <td class="p-4 text-sm">${row.sede_registro}</td>
                        <td class="p-4 text-center">${tipoBadge}</td>
                        <td class="p-4 text-center text-xs font-bold">${modoIcon}</td>
                        <td class="p-4 text-center">
                            <span class="w-2 h-2 rounded-full inline-block ${row.tipo === 'ENTRADA' ? 'bg-green-500' : 'bg-red-500'}"></span>
                        </td>
                    </tr>
                `;
                tbody.innerHTML += html;
            });
        } else {
            tbody.innerHTML = '<tr><td colspan="6" class="p-8 text-center text-gray-500">No se encontraron registros en este rango.</td></tr>';
        }
    } catch (error) {
        console.error(error);
        tbody.innerHTML = '<tr><td colspan="6" class="p-8 text-center text-red-500">Error al cargar reporte.</td></tr>';
    }
}
</script>

<?php require_once 'partials/footer.php'; ?>