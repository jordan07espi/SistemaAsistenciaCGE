<?php require_once 'partials/header.php'; ?>

<div class="p-8">
    <h1 class="text-2xl font-bold mb-6 text-gray-700 hidden md:block">Gestión de Colaboradores</h1>
    <h2 class="text-xl font-bold mb-6 text-gray-700 md:hidden">Colaboradores</h2>

    <div class="bg-white rounded-xl shadow-md p-6 mb-8 border border-gray-200">
        <h2 class="text-lg font-semibold mb-4 flex items-center gap-2">
            <i class="ph ph-microsoft-excel-logo text-green-600"></i> Gestión Masiva
        </h2>
        
        <div class="flex flex-col md:flex-row gap-6 justify-between items-end">
            <form id="formImportar" class="flex flex-col md:flex-row gap-4 items-end flex-1 w-full">
                <div class="w-full">
                    <label class="text-xs text-gray-500 mb-1 block">Subir .xlsx (CEDULA, NOMBRE, ORIGEN)</label>
                    <input type="file" id="archivoExcel" accept=".xlsx, .xls" class="block w-full text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-green-50 file:text-green-700 hover:file:bg-green-100"/>
                </div>
                <button type="submit" class="w-full md:w-auto bg-green-600 hover:bg-green-700 text-white py-2 px-6 rounded-lg font-semibold transition flex items-center justify-center gap-2">
                    <i class="ph ph-upload"></i> Subir
                </button>
            </form>

            <div class="border-t md:border-t-0 md:border-l pt-4 md:pt-0 pl-0 md:pl-6 border-gray-200 flex flex-col gap-2 w-full md:w-auto">
                <p class="text-xs text-gray-400 hidden md:block">Descargas:</p>
                <a href="../controllers/ExportarColaboradores.php" target="_blank" class="bg-blue-600 hover:bg-blue-700 text-white py-1.5 px-4 rounded text-sm font-semibold flex items-center gap-2 justify-center w-full">
                    <i class="ph ph-file-xls text-lg"></i> Exportar Lista
                </a>
                <a href="../controllers/GenerarCredencial.php?modo=masivo" target="_blank" class="bg-red-600 hover:bg-red-700 text-white py-1.5 px-4 rounded text-sm font-semibold flex items-center gap-2 justify-center w-full">
                    <i class="ph ph-id-card text-lg"></i> Credenciales PDF
                </a>
            </div>
        </div>
        <div id="uploadStatus" class="mt-4 hidden p-3 rounded-lg text-sm"></div>
    </div>

    <div class="bg-transparent md:bg-white md:rounded-xl md:shadow-md md:border md:border-gray-200">
        <div class="p-4 border-b border-gray-100 flex flex-col md:flex-row justify-between items-center gap-4 bg-white rounded-xl md:rounded-none shadow-md md:shadow-none mb-4 md:mb-0">
            <div class="flex flex-col md:flex-row items-center gap-4 w-full md:w-auto">
                <div class="relative w-full md:w-64">
                    <i class="ph ph-magnifying-glass absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                    <input type="text" id="busquedaColaborador" placeholder="Buscar cédula o nombre..." class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 outline-none transition" autocomplete="off">
                </div>
            </div>
            <div class="flex gap-2 w-full md:w-auto justify-end">
                <button onclick="abrirModalColaborador()" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-bold flex items-center gap-2 transition shadow-sm flex-1 md:flex-none justify-center">
                    <i class="ph ph-plus-circle text-lg"></i> <span>Nuevo</span>
                </button>
                <button onclick="cargarColaboradores(1)" class="text-gray-500 hover:text-blue-600 p-2 rounded hover:bg-gray-100 transition" title="Recargar"><i class="ph ph-arrows-clockwise text-xl"></i></button>
            </div>
        </div>

        <div class="md:overflow-x-auto min-h-[350px]">
            <table class="w-full text-left text-sm text-gray-600 block md:table">
                <thead class="bg-gray-50 uppercase text-xs font-semibold text-gray-500 hidden md:table-header-group">
                    <tr>
                        <th class="p-4">Cédula</th>
                        <th class="p-4">Nombre</th>
                        <th class="p-4">Origen</th>
                        <th class="p-4">Estado</th>
                        <th class="p-4 text-center w-16"></th>
                    </tr>
                </thead>
                <tbody id="tablaColaboradores" class="divide-y divide-gray-100 block md:table-row-group space-y-4 md:space-y-0">
                    <tr><td colspan="5" class="p-4 text-center">Cargando...</td></tr>
                </tbody>
            </table>
        </div>
        <div id="paginacionColaboradores" class="p-4 flex justify-center items-center gap-2 border-t border-gray-100 bg-white md:bg-transparent rounded-b-xl md:rounded-none"></div>
    </div>
</div>

<div id="modalNuevoColaborador" class="fixed inset-0 bg-black/50 hidden z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-xl p-6 w-full max-w-md shadow-2xl">
        <h2 class="text-xl font-bold mb-4 text-gray-800 flex items-center gap-2"><i class="ph ph-user-plus text-blue-600"></i> Registrar Colaborador</h2>
        <form id="formNuevoColaborador" class="space-y-4">
            <div><label class="block text-sm font-medium text-gray-700 mb-1">Cédula / ID</label><input type="text" name="cedula" class="w-full border rounded-lg p-2 focus:ring-2 focus:ring-blue-500 outline-none" required></div>
            <div><label class="block text-sm font-medium text-gray-700 mb-1">Nombre Completo</label><input type="text" name="nombre" class="w-full border rounded-lg p-2 focus:ring-2 focus:ring-blue-500 outline-none" required></div>
            <div><label class="block text-sm font-medium text-gray-700 mb-1">Origen</label><select name="origen" class="w-full border rounded-lg p-2 bg-white"><option value="INSTITUTO">INSTITUTO</option><option value="CAPACITADORA">CAPACITADORA</option></select></div>
            <div class="flex justify-end gap-3 mt-6">
                <button type="button" onclick="cerrarModalColaborador()" class="text-gray-500 hover:text-gray-700">Cancelar</button>
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg font-bold shadow">Guardar</button>
            </div>
        </form>
    </div>
</div>

<div id="modalEditarColaborador" class="fixed inset-0 bg-black/50 hidden z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-xl p-6 w-full max-w-md shadow-2xl">
        <h2 class="text-xl font-bold mb-4 text-gray-800 flex items-center gap-2"><i class="ph ph-pencil-simple text-yellow-600"></i> Editar Colaborador</h2>
        <form id="formEditarColaborador" class="space-y-4">
            <input type="hidden" name="id" id="editId">
            <div><label class="block text-sm font-medium text-gray-700 mb-1">Cédula / ID</label><input type="text" name="cedula" id="editCedula" class="w-full border rounded-lg p-2 focus:ring-2 focus:ring-yellow-500 outline-none" required></div>
            <div><label class="block text-sm font-medium text-gray-700 mb-1">Nombre Completo</label><input type="text" name="nombre" id="editNombre" class="w-full border rounded-lg p-2 focus:ring-2 focus:ring-yellow-500 outline-none" required></div>
            <div><label class="block text-sm font-medium text-gray-700 mb-1">Origen</label><select name="origen" id="editOrigen" class="w-full border rounded-lg p-2 bg-white"><option value="INSTITUTO">INSTITUTO</option><option value="CAPACITADORA">CAPACITADORA</option></select></div>
            <div class="flex justify-end gap-3 mt-6">
                <button type="button" onclick="cerrarModalEditar()" class="text-gray-500 hover:text-gray-700">Cancelar</button>
                <button type="submit" class="bg-yellow-600 hover:bg-yellow-700 text-white px-4 py-2 rounded-lg font-bold shadow">Actualizar</button>
            </div>
        </form>
    </div>
</div>

<?php require_once 'partials/footer.php'; ?>