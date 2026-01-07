<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

require_once 'partials/header.php';
require_once __DIR__ . '/../models/SedeDAO.php';

// Obtener las sedes para el filtro
$sedeDAO = new SedeDAO();
$sedes = $sedeDAO->listarTodas();
?>

<div class="p-4 md:p-8">
    
    <div class="flex flex-col md:flex-row justify-between items-center mb-6 gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-700">Reportes y Estadísticas</h1>
            <p class="text-sm text-gray-500">Visualiza el rendimiento de la asistencia.</p>
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
            </div>
            <div class="absolute -right-4 -bottom-4 text-blue-50 opacity-50">
                <i class="ph ph-users-three text-9xl"></i>
            </div>
        </div>

        <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-200 h-48 relative w-full">
            <h3 class="font-bold text-gray-700 text-sm mb-2">Estado Actual</h3>
            <div class="h-32 w-full"><canvas id="chartDistribucion"></canvas></div>
        </div>

        <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-200 h-48 relative w-full">
            <h3 class="font-bold text-gray-700 text-sm mb-2">Tendencia (7 días)</h3>
            <div class="h-32 w-full"><canvas id="chartAsistenciaSemanal"></canvas></div>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-md border border-gray-200 overflow-hidden">
        
        <div class="p-6 border-b border-gray-100 bg-gray-50">
            <h3 class="font-bold text-gray-700 mb-4 text-lg">Reporte Detallado</h3>
            
            <form id="formFiltros" class="grid grid-cols-1 md:grid-cols-12 gap-4 items-end">
                <div class="md:col-span-3">
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Sede</label>
                    <select id="filtroSedeReporte" name="sede" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 text-sm bg-white">
                        <option value="">TODAS LAS SEDES</option>
                        <?php foreach ($sedes as $s): ?>
                            <option value="<?php echo htmlspecialchars($s['nombre']); ?>">
                                <?php echo htmlspecialchars($s['nombre']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
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
        
        <div id="paginacionContainer" class="p-4 bg-gray-50 border-t border-gray-200 flex flex-col items-end">
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="assets/js/reportes.js"></script>

<?php require_once 'partials/footer.php'; ?>