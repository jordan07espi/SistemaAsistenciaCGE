<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Control Asistencia</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-900 h-screen flex items-center justify-center">

    <div class="bg-white p-8 rounded-2xl shadow-2xl w-full max-w-sm">
        <div class="text-center mb-8">
            <img src="../public/assets/img/icon-512.png" alt="Logo CGE" class="mx-auto h-24 w-auto mb-4 drop-shadow-md">
            
            <h1 class="text-2xl font-bold text-slate-800">Acceso Administrativo</h1>
            <p class="text-sm text-gray-500">Ingresa tus credenciales</p>
        </div>

        <form id="formLogin" class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Cédula</label>
                <input type="text" id="cedula" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none" placeholder="Ej: 1728..." required>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Contraseña</label>
                <input type="password" id="password" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none" placeholder="••••••••" required>
            </div>

            <div id="msgError" class="text-red-500 text-sm text-center hidden"></div>

            <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 rounded-lg transition">
                Ingresar
            </button>
        </form>

        <div class="mt-6 text-center">
            <a href="../index.php" class="text-sm text-gray-400 hover:text-gray-600">← Volver al Kiosco</a>
        </div>
    </div>

    <script>
        document.getElementById('formLogin').addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const cedula = document.getElementById('cedula').value;
            const password = document.getElementById('password').value;
            const msg = document.getElementById('msgError');
            const btn = e.target.querySelector('button');

            btn.disabled = true;
            btn.textContent = "Verificando...";
            msg.classList.add('hidden');

            try {
                const response = await fetch('../controllers/Auth.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ cedula, password })
                });

                const data = await response.json();

                if (data.status === 'success') {
                    window.location.href = 'index.php'; // Redirigir al Dashboard
                } else {
                    msg.textContent = data.message;
                    msg.classList.remove('hidden');
                    btn.disabled = false;
                    btn.textContent = "Ingresar";
                }
            } catch (error) {
                console.error(error);
                msg.textContent = "Error de conexión";
                msg.classList.remove('hidden');
                btn.disabled = false;
                btn.textContent = "Ingresar";
            }
        });
    </script>
</body>
</html>