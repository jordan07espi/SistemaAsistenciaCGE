// admin/assets/js/usuarios.js

document.addEventListener('DOMContentLoaded', () => {
    cargarUsuarios();

    const formCrear = document.getElementById('formNuevoUsuario');
    if (formCrear) {
        formCrear.addEventListener('submit', async (e) => {
            e.preventDefault();
            await enviarFormulario(formCrear, 'crear', cerrarModalUsuario);
        });
    }

    const formPass = document.getElementById('formPassword');
    if (formPass) {
        formPass.addEventListener('submit', async (e) => {
            e.preventDefault();
            await enviarFormulario(formPass, 'cambiar_password', cerrarModalPassword);
        });
    }
});

async function cargarUsuarios() {
    const tbody = document.getElementById('tablaUsuarios');
    // Spinner responsivo
    tbody.innerHTML = '<tr><td colspan="4" class="p-8 text-center text-gray-400 block md:table-cell">⏳ Cargando usuarios...</td></tr>';

    try {
        const response = await fetch('../controllers/AdministrarUsuarios.php?accion=listar');
        const usuarios = await response.json();

        tbody.innerHTML = '';

        if (usuarios.length === 0) {
            tbody.innerHTML = '<tr><td colspan="4" class="p-8 text-center text-gray-500 block md:table-cell">No hay usuarios registrados.</td></tr>';
            return;
        }

        usuarios.forEach(u => {
            // Badge para el Rol
            const badgeRol = u.rol === 'SUPERADMIN' 
                ? '<span class="bg-purple-100 text-purple-700 px-2 py-1 rounded-full text-xs font-bold border border-purple-200">SUPERADMIN</span>'
                : '<span class="bg-blue-100 text-blue-700 px-2 py-1 rounded-full text-xs font-bold border border-blue-200">ADMIN</span>';

            // --- CORRECCIÓN AQUÍ: Se cambiaron las propiedades u.nombre_completo por u.nombre y u.username por u.cedula ---
            const row = `
                <tr class="hover:bg-gray-50 transition block md:table-row bg-white md:bg-transparent shadow-sm md:shadow-none rounded-xl md:rounded-none border border-gray-200 md:border-0 md:border-b md:border-gray-100 p-4 md:p-0">
                    
                    <td class="p-2 md:p-4 block md:table-cell flex justify-between items-center md:border-none border-b border-gray-50 last:border-0">
                        <span class="font-bold text-xs text-gray-400 uppercase md:hidden">Nombre:</span>
                        <span class="font-semibold text-gray-800 text-right md:text-left">${u.nombre}</span>
                    </td>

                    <td class="p-2 md:p-4 block md:table-cell flex justify-between items-center md:border-none border-b border-gray-50 last:border-0">
                        <span class="font-bold text-xs text-gray-400 uppercase md:hidden">Cédula:</span>
                        <span class="font-mono text-sm text-gray-600">${u.cedula}</span>
                    </td>

                    <td class="p-2 md:p-4 block md:table-cell flex justify-between items-center md:border-none border-b border-gray-50 last:border-0">
                        <span class="font-bold text-xs text-gray-400 uppercase md:hidden">Rol:</span>
                        ${badgeRol}
                    </td>

                    <td class="p-2 md:p-4 block md:table-cell flex justify-end md:justify-center items-center gap-2 mt-2 md:mt-0">
                        <button onclick="abrirModalPassword(${u.id}, '${u.nombre}')" class="text-yellow-600 hover:text-yellow-800 bg-yellow-50 hover:bg-yellow-100 p-2 rounded-lg transition" title="Cambiar Contraseña">
                            <i class="ph ph-lock-key text-lg"></i>
                        </button>
                        
                        <button onclick="eliminarUsuario(${u.id})" class="text-red-600 hover:text-red-800 bg-red-50 hover:bg-red-100 p-2 rounded-lg transition" title="Eliminar Usuario">
                            <i class="ph ph-trash text-lg"></i>
                        </button>
                    </td>
                </tr>
            `;
            tbody.innerHTML += row;
        });

    } catch (error) {
        console.error(error);
        tbody.innerHTML = '<tr><td colspan="4" class="p-8 text-center text-red-500 block md:table-cell">Error de conexión.</td></tr>';
    }
}

async function enviarFormulario(form, accion, callbackCierre) {
    const formData = new FormData(form);
    const data = Object.fromEntries(formData.entries());
    data.accion = accion;

    try {
        const response = await fetch('../controllers/AdministrarUsuarios.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify(data)
        });
        const res = await response.json();

        if (res.status === 'success') {
            alert("✅ Operación exitosa");
            form.reset();
            callbackCierre();
            cargarUsuarios();
        } else {
            alert("❌ Error: " + res.message);
        }
    } catch (error) {
        alert("Error de conexión");
    }
}

async function eliminarUsuario(id) {
    if(!confirm("¿Estás seguro de eliminar este usuario? No podrá acceder al sistema.")) return;

    try {
        const response = await fetch('../controllers/AdministrarUsuarios.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({ accion: 'eliminar', id: id })
        });
        const res = await response.json();
        
        if (res.status === 'success') {
            cargarUsuarios();
        } else {
            alert("❌ Error: " + res.message);
        }
    } catch (error) {
        alert("Error de conexión");
    }
}

// MODALES
window.abrirModalUsuario = () => document.getElementById('modalNuevoUsuario').classList.remove('hidden');
window.cerrarModalUsuario = () => document.getElementById('modalNuevoUsuario').classList.add('hidden');

window.abrirModalPassword = (id, nombre) => {
    document.getElementById('idUsuarioPass').value = id;
    document.getElementById('lblUsuarioPass').textContent = nombre;
    document.getElementById('modalPassword').classList.remove('hidden');
};
window.cerrarModalPassword = () => document.getElementById('modalPassword').classList.add('hidden');