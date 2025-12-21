<?php require_once 'partials/header.php'; ?>

<div class="p-8">
    <h1 class="text-2xl font-bold mb-6 text-gray-700 hidden md:block">Reportes y Estadísticas</h1>
    <h2 class="text-xl font-bold mb-6 text-gray-700 md:hidden">Reportes</h2>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
        <div class="bg-white p-6 rounded-xl shadow-md border border-gray-200">
            <h3 class="font-bold text-gray-700 mb-4 text-center">Estado Actual del Personal</h3>
            <div class="relative h-64"><canvas id="chartEstado"></canvas></div>
        </div>
        <div class="bg-white p-6 rounded-xl shadow-md border border-gray-200">
            <h3 class="font-bold text-gray-700 mb-4 text-center">Asistencias (Últimos 7 días)</h3>
            <div class="relative h-64"><canvas id="chartSemana"></canvas></div>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-md p-6 mb-8 border border-gray-200">
        <h3 class="font-bold text-gray-700 mb-4">Reporte Detallado</h3>
        <form id="formFiltros" class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
            <div><label class="block text-sm font-medium text-gray-700 mb-1">Fecha Inicio</label><input type="date" name="fecha_inicio" id="fechaInicio" class="w-full border rounded-lg p-2 bg-gray-50" value="<?php echo date('Y-m-d'); ?>"></div>
            <div><label class="block text-sm font-medium text-gray-700 mb-1">Fecha Fin</label><input type="date" name="fecha_fin" id="fechaFin" class="w-full border rounded-lg p-2 bg-gray-50" value="<?php echo date('Y-m-d'); ?>"></div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Sede Registro</label>
                <select name="sede" id="sede" class="w-full border rounded-lg p-2 bg-gray-50"><option value="TODAS">TODAS LAS SEDES</option></select>
            </div>
            <div class="flex gap-2">
                <button type="submit" class="flex-1 bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-lg transition flex items-center justify-center gap-2"><i class="ph ph-magnifying-glass"></i> Buscar</button>
                <button type="button" id="btnExportar" class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded-lg transition" title="Descargar Excel"><i class="ph ph-file-xls text-xl"></i></button>
            </div>
        </form>
    </div>

    <div class="bg-transparent md:bg-white md:rounded-xl md:shadow-md md:border md:border-gray-200">
        
        <div class="md:overflow-x-auto min-h-[350px]">
            <table class="w-full text-left text-sm text-gray-600 block md:table">
                <thead class="bg-gray-50 uppercase text-xs font-semibold text-gray-500 hidden md:table-header-group">
                    <tr>
                        <th class="p-4">Fecha/Hora</th>
                        <th class="p-4">Cédula</th>
                        <th class="p-4">Nombre</th>
                        <th class="p-4">Tipo</th>
                        <th class="p-4">Sede Reg.</th>
                        <th class="p-4">Modo</th>
                    </tr>
                </thead>
                <tbody id="tablaReporte" class="divide-y divide-gray-100 block md:table-row-group space-y-4 md:space-y-0">
                    <tr><td colspan="6" class="p-8 text-center text-gray-400">Seleccione fechas y haga clic en Buscar.</td></tr>
                </tbody>
            </table>
        </div>
        
        <div id="paginacionReportes" class="p-4 flex justify-center items-center gap-2 border-t border-gray-100 bg-white md:bg-transparent rounded-b-xl md:rounded-none mt-4 md:mt-0 shadow-sm md:shadow-none"></div>
    </div>
</div>

<script src="assets/js/reportes.js"></script>

<?php require_once 'partials/footer.php'; ?>