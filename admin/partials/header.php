<?php
// admin/partials/header.php
if (session_status() === PHP_SESSION_NONE) session_start();

// Validar sesión
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

// Detectar página actual para activar el menú
$paginaActual = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Control Asistencia</title>
    <link rel="icon" type="image/png" href="../public/assets/img/icon-192.png">
    <link rel="manifest" href="../manifest.json">
    <meta name="theme-color" content="#2563eb">
    <script>
    if ('serviceWorker' in navigator) {
        window.addEventListener('load', () => {
        navigator.serviceWorker.register('../service-worker.js')
            .then(reg => console.log('Service Worker registrado (Admin)'))
            .catch(err => console.log('Error SW:', err));
        });
    }
    </script>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>


</head>
<body class="bg-gray-100 text-gray-800 font-sans">

    <div id="mobileOverlay" class="fixed inset-0 bg-black/50 z-40 hidden md:hidden transition-opacity opacity-0"></div>

    <div class="flex h-screen overflow-hidden">
        
        <aside id="sidebar" class="w-64 bg-slate-900 text-white flex flex-col fixed inset-y-0 left-0 z-50 transform -translate-x-full transition-transform duration-300 ease-in-out md:translate-x-0 md:static md:inset-0 shadow-2xl md:shadow-none">
            
            <div class="p-6 text-center font-bold text-xl border-b border-gray-700 flex justify-between items-center md:block">
                <span>🛡️ DANIELSOFT</span>
                <button id="closeSidebarBtn" class="md:hidden text-gray-400 hover:text-white">
                    <i class="ph ph-x text-2xl"></i>
                </button>
            </div>

            <nav class="flex-1 p-4 space-y-2">
                
                <a href="index.php" class="flex items-center gap-3 p-3 rounded-lg transition <?php echo ($paginaActual == 'index.php') ? 'bg-blue-600 text-white shadow-md' : 'hover:bg-slate-800 text-gray-400 hover:text-white'; ?>">
                    <i class="ph ph-users-three text-xl"></i>
                    <span>Colaboradores</span>
                </a>
                
                <a href="reportes.php" class="flex items-center gap-3 p-3 rounded-lg transition <?php echo ($paginaActual == 'reportes.php') ? 'bg-blue-600 text-white shadow-md' : 'hover:bg-slate-800 text-gray-400 hover:text-white'; ?>">
                    <i class="ph ph-chart-bar text-xl"></i>
                    <span>Reportes</span>
                </a>
                
                <a href="sedes.php" class="flex items-center gap-3 p-3 rounded-lg transition <?php echo ($paginaActual == 'sedes.php') ? 'bg-blue-600 text-white shadow-md' : 'hover:bg-slate-800 text-gray-400 hover:text-white'; ?>">
                    <i class="ph ph-buildings text-xl"></i>
                    <span>Sedes / Ubicaciones</span>
                </a>

                <?php if (isset($_SESSION['admin_rol']) && $_SESSION['admin_rol'] === 'SUPERADMIN'): ?>
                <div class="mt-4 border-t border-gray-700 pt-4">
                    <p class="px-3 text-xs text-gray-400 uppercase font-bold mb-2">Superadmin</p>
                    <a href="usuarios.php" class="flex items-center gap-3 p-3 rounded-lg transition <?php echo ($paginaActual == 'usuarios.php') ? 'bg-yellow-600 text-white shadow-md' : 'hover:bg-slate-800 text-yellow-400 hover:text-yellow-300'; ?>">
                        <i class="ph ph-shield-check text-xl"></i>
                        <span>Usuarios Admin</span>
                    </a>
                </div>
                <?php endif; ?>

            </nav>
            
            <div class="p-4 border-t border-gray-700">
                <div class="mb-4 px-2 text-xs text-gray-500">
                    Hola, <?php echo htmlspecialchars($_SESSION['admin_nombre'] ?? 'Admin'); ?>
                </div>
                <a href="../controllers/Auth.php?action=logout" class="flex items-center gap-2 text-sm text-red-400 hover:text-red-300">
                    <i class="ph ph-sign-out"></i> Cerrar Sesión
                </a>
            </div>
        </aside>

        <main class="flex-1 flex flex-col overflow-y-auto">
            
            <header class="bg-white shadow p-4 flex justify-between items-center md:hidden sticky top-0 z-30">
                <button id="mobileMenuBtn" class="text-gray-600 hover:text-blue-600 focus:outline-none transition">
                    <i class="ph ph-list text-3xl"></i>
                </button>
                
                <h1 class="font-bold text-lg text-gray-700 tracking-wide">PANEL ADMIN</h1>
            </header>