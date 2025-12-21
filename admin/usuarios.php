<?php
// 1. Incluir el Header (Maneja sesión y menú lateral)
require_once 'partials/header.php';

// 2. Seguridad Adicional: Solo SUPERADMIN puede ver esta página
if (!isset($_SESSION['admin_rol']) || $_SESSION['admin_rol'] !== 'SUPERADMIN') {
    echo "<script>window.location.href = 'index.php';</script>";
    exit;
}
?>

<div class="p-8">
    <h1 class="text-2xl font-bold mb-6 text-gray-700 hidden md:block">Gestión de Usuarios Administradores</h1>
    <h2 class="text-xl font-bold mb-6 text-gray-700 md:hidden">Usuarios Admin</h2>

    <div class="bg-transparent md:bg-white md:rounded-xl md:shadow-md md:border md:border-gray-200">
        
        <div class="p-4 border-b border-gray-100 flex justify-between items-center bg-white rounded-xl md:rounded-none shadow-md md:shadow-none mb-4 md:mb-0">
            <div class="flex items-center gap-2">
                <i class="ph ph-shield-check text-xl text-blue-600"></i>
                <h2 class="font-semibold text-gray-700">Listado de Accesos</h2>
            </div>
            <button onclick="abrirModalUsuario()" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-bold flex items-center gap-2 transition shadow-sm">
                <i class="ph ph-user-plus text-lg"></i> 
                <span class="hidden sm:inline">Nuevo Usuario</span>
                <span class="sm:hidden">Nuevo</span>
            </button>
        </div>

        <div class="md:overflow-x-auto">
            <table class="w-full text-left text-sm text-gray-600 block md:table">
                <thead class="bg-gray-50 uppercase text-xs font-semibold text-gray-500 hidden md:table-header-group">
                    <tr>
                        <th class="p-4">Nombre</th>
                        <th class="p-4">Cédula</th>
                        <th class="p-4">Rol</th>
                        <th class="p-4 text-center">Acciones</th>
                    </tr>
                </thead>
                
                <tbody id="tablaUsuarios" class="divide-y divide-gray-100 block md:table-row-group space-y-4 md:space-y-0">
                    <tr><td colspan="4" class="p-8 text-center text-gray-400 block md:table-cell">Cargando usuarios...</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div id="modalNuevoUsuario" class="fixed inset-0 bg-black/50 hidden z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-xl p-6 w-full max-w-md shadow-2xl transform transition-all">
        <h2 class="text-xl font-bold mb-4 text-gray-800 flex items-center gap-2">
            <i class="ph ph-shield-plus text-blue-600"></i> Nuevo Administrador
        </h2>
        <form id="formNuevoUsuario" class="space-y-4">
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Nombre Completo</label>
                <input type="text" name="nombre" class="w-full border border-gray-300 rounded-lg p-2 focus:ring-2 focus:ring-blue-500 outline-none" required placeholder="Ej: Admin Principal">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Cédula</label>
                <input type="text" name="cedula" class="w-full border border-gray-300 rounded-lg p-2 focus:ring-2 focus:ring-blue-500 outline-none" required placeholder="Ej: 1728...">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Contraseña</label>
                <input type="password" name="password" class="w-full border border-gray-300 rounded-lg p-2 focus:ring-2 focus:ring-blue-500 outline-none" required placeholder="******">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Rol de Acceso</label>
                <select name="rol" class="w-full border border-gray-300 rounded-lg p-2 bg-white focus:ring-2 focus:ring-blue-500 outline-none">
                    <option value="ADMIN">Administrador (Limitado)</option>
                    <option value="SUPERADMIN">Super Administrador (Total)</option>
                </select>
            </div>

            <div class="flex justify-end gap-3 mt-6">
                <button type="button" onclick="cerrarModalUsuario()" class="text-gray-500 hover:text-gray-700 font-medium px-3 py-2 rounded-lg hover:bg-gray-100 transition">Cancelar</button>
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg font-bold shadow transition">
                    Guardar Usuario
                </button>
            </div>
        </form>
    </div>
</div>

<div id="modalPassword" class="fixed inset-0 bg-black/50 hidden z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-xl p-6 w-full max-w-md shadow-2xl transform transition-all">
        <h2 class="text-xl font-bold mb-4 text-gray-800 flex items-center gap-2">
            <i class="ph ph-lock-key text-yellow-600"></i> Cambiar Contraseña
        </h2>
        <p class="text-sm text-gray-500 mb-4">
            Actualizando clave para: <span id="lblUsuarioPass" class="font-bold text-gray-700"></span>
        </p>
        
        <form id="formPassword" class="space-y-4">
            <input type="hidden" name="id" id="idUsuarioPass">
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Nueva Contraseña</label>
                <input type="password" name="password" class="w-full border border-gray-300 rounded-lg p-2 focus:ring-2 focus:ring-yellow-500 outline-none" required placeholder="Nueva clave segura">
            </div>

            <div class="flex justify-end gap-3 mt-6">
                <button type="button" onclick="cerrarModalPassword()" class="text-gray-500 hover:text-gray-700 font-medium px-3 py-2 rounded-lg hover:bg-gray-100 transition">Cancelar</button>
                <button type="submit" class="bg-yellow-600 hover:bg-yellow-700 text-white px-4 py-2 rounded-lg font-bold shadow transition">
                    Actualizar Clave
                </button>
            </div>
        </form>
    </div>
</div>

<script src="assets/js/usuarios.js"></script>

<?php 
// 3. Incluir Footer (Cierre de etiquetas y scripts globales)
require_once 'partials/footer.php'; 
?>