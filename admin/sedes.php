<?php require_once 'partials/header.php'; ?>

<div class="p-8">
    <h1 class="text-2xl font-bold mb-6 text-gray-700 hidden md:block">Gestión de Ubicaciones Físicas</h1>
    <h2 class="text-xl font-bold mb-6 text-gray-700 md:hidden">Sedes / Ubicaciones</h2>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        
        <div class="bg-white p-6 rounded-xl shadow-md h-fit border border-gray-200">
            <h2 class="text-lg font-bold mb-4 flex items-center gap-2"><i class="ph ph-plus-circle text-blue-600"></i> Nueva Sede</h2>
            <form id="formSede" class="space-y-4">
                <div><label class="block text-sm font-medium text-gray-700 mb-1">Nombre del Edificio</label><input type="text" name="nombre" class="w-full border rounded-lg p-2 outline-none focus:ring-2 focus:ring-blue-500" placeholder="Ej: Campus Norte" required></div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Color del Botón</label>
                    <select name="color" class="w-full border rounded-lg p-2 bg-white">
                        <option value="bg-blue-600">Azul (Institucional)</option>
                        <option value="bg-purple-600">Morado (Capacitación)</option>
                        <option value="bg-green-600">Verde</option>
                        <option value="bg-red-600">Rojo</option>
                        <option value="bg-orange-600">Naranja</option>
                        <option value="bg-teal-600">Turquesa</option>
                        <option value="bg-slate-800">Negro</option>
                    </select>
                </div>
                <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-lg transition shadow-md">Guardar Ubicación</button>
            </form>
        </div>

        <div class="bg-white p-6 rounded-xl shadow-md lg:col-span-2 border border-gray-200">
            <h2 class="text-lg font-bold mb-4 flex items-center gap-2"><i class="ph ph-list-dashes text-gray-500"></i> Sedes Activas</h2>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm text-gray-600">
                    <thead class="bg-gray-50 uppercase text-xs font-semibold text-gray-500">
                        <tr><th class="p-3">Nombre</th><th class="p-3">Color Visual</th><th class="p-3 text-right">Acción</th></tr>
                    </thead>
                    <tbody id="tablaSedes" class="divide-y divide-gray-100">
                        <tr><td colspan="3" class="p-4 text-center text-gray-400">Cargando...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        cargarSedes();
        document.getElementById('formSede').addEventListener('submit', async (e) => {
            e.preventDefault();
            const formData = new FormData(e.target);
            const data = Object.fromEntries(formData.entries());
            data.accion = 'crear';
            if(confirm(`¿Confirmas crear la sede "${data.nombre}"?`)) { await enviarSolicitud(data); e.target.reset(); }
        });
    });
    async function cargarSedes() {
        const tbody = document.getElementById('tablaSedes');
        try {
            const res = await fetch('../controllers/SedeController.php');
            const sedes = await res.json();
            tbody.innerHTML = '';
            if (sedes.length === 0) { tbody.innerHTML = '<tr><td colspan="3" class="p-4 text-center text-gray-400">No hay sedes registradas.</td></tr>'; return; }
            sedes.forEach(s => {
                const row = `<tr class="hover:bg-gray-50 transition"><td class="p-3 font-semibold text-gray-800">${s.nombre}</td><td class="p-3"><div class="flex items-center gap-2"><span class="w-6 h-6 rounded-full ${s.color} shadow-sm border border-gray-200"></span><span class="text-xs text-gray-400 font-mono">${s.color}</span></div></td><td class="p-3 text-right"><button onclick="eliminarSede(${s.id})" class="text-red-500 hover:text-red-700 font-bold hover:bg-red-50 p-2 rounded transition"><i class="ph ph-trash text-lg"></i></button></td></tr>`;
                tbody.innerHTML += row;
            });
        } catch (error) { tbody.innerHTML = '<tr><td colspan="3" class="p-4 text-center text-red-500">Error al cargar datos.</td></tr>'; }
    }
    async function enviarSolicitud(data) {
        try {
            const res = await fetch('../controllers/SedeController.php', { method: 'POST', headers: {'Content-Type': 'application/json'}, body: JSON.stringify(data) });
            const json = await res.json();
            if(json.status === 'success') { cargarSedes(); } else { alert("❌ Error: " + json.message); }
        } catch (error) { alert("Error de conexión"); }
    }
    async function eliminarSede(id) {
        if(confirm("¿Seguro que deseas eliminar esta sede?")) { await enviarSolicitud({ accion: 'eliminar', id: id }); }
    }
</script>

<?php require_once 'partials/footer.php'; ?>