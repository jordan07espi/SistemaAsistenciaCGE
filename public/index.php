<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="manifest" href="../manifest.json">
    <meta name="theme-color" content="#2563eb">
    <link rel="apple-touch-icon" href="assets/img/icon-192.png">
    <title>Kiosco de Asistencia</title>
    
    <script src="https://cdn.tailwindcss.com"></script>
    
    <script src="https://unpkg.com/html5-qrcode" type="text/javascript"></script>

    <style>
        .scan-line {
            position: absolute;
            width: 100%;
            height: 2px;
            background: #ef4444;
            animation: scan 2s infinite linear;
            top: 0;
        }
        @keyframes scan {
            0% { top: 0; }
            50% { top: 100%; }
            100% { top: 0; }
        }
        #reader {
            width: 100%;
            border-radius: 12px;
            overflow: hidden;
        }
        /* Ocultar elementos sobrantes de la UI por defecto de la librería */
        #reader__dashboard_section_csr button { display: none; } 
    </style>
</head>
<body class="bg-gray-900 text-white h-screen flex flex-col items-center justify-center relative overflow-hidden">

    <div id="setupModal" class="fixed inset-0 bg-black/90 z-50 flex flex-col items-center justify-center p-4 hidden">
        <h2 class="text-2xl font-bold mb-6 text-center">Configuración del Dispositivo</h2>
        <p class="mb-8 text-gray-300 text-center">Selecciona la ubicación física de este escáner:</p>
        
        <div id="listaSedes" class="grid gap-4 w-full max-w-sm">
            <p class="text-center text-gray-500">Cargando ubicaciones...</p>
        </div>
    </div>

    <div class="w-full max-w-md p-4 flex flex-col h-full justify-between">
        
        <div class="text-center mt-4">
            <h1 class="text-xl font-bold tracking-wider">REGISTRO DE ASISTENCIA</h1>
            <p id="lblSede" class="text-sm text-gray-400 font-mono mt-1 font-bold">CARGANDO SEDE...</p>
            
            <div class="flex justify-center items-center gap-3 mt-3">
                <button id="btnResetConfig" class="text-[10px] text-gray-600 hover:text-gray-400 underline uppercase tracking-wide transition">
                    Cambiar Ubicación
                </button>
                
                <span class="text-gray-700 text-[10px]">|</span>
                
                <a href="../admin/login.php" class="text-[10px] text-gray-600 hover:text-gray-400 underline uppercase tracking-wide transition">
                    Acceso Administrador
                </a>
            </div>
        </div>

        <div class="relative w-full aspect-square bg-gray-800 rounded-2xl border-4 border-gray-700 overflow-hidden shadow-2xl my-4">
            <div id="reader" class="w-full h-full object-cover"></div>
            <div class="scan-line z-10"></div>
            
            <div id="statusMessage" class="absolute inset-0 flex flex-col items-center justify-center bg-black/80 hidden z-20 transition-all">
                <div id="statusIcon" class="text-6xl mb-2">✅</div>
                <h2 id="statusTitle" class="text-2xl font-bold text-center">ENTRADA</h2>
                <p id="statusName" class="text-lg text-gray-300 mt-2 text-center px-4">Juan Perez</p>
                <p id="statusTime" class="text-sm font-mono text-gray-400 mt-4">08:00:00</p>
            </div>
        </div>

        <div class="flex justify-center mb-4">
            <button id="btnReloadCam" class="flex items-center gap-2 text-[10px] text-gray-500 hover:text-white bg-gray-800 hover:bg-gray-700 px-3 py-1 rounded-full border border-gray-700 transition">
                <span>🔄</span> RECARGAR CÁMARA
            </button>
        </div>

        <div class="grid grid-cols-2 gap-4 mb-6">
            <button id="btnEntrada" class="bg-gray-800 hover:bg-green-700 border border-gray-600 text-gray-300 hover:text-white py-4 rounded-xl font-semibold transition flex flex-col items-center">
                <span>⬇️ FORZAR</span>
                <span class="text-xs uppercase">Entrada</span>
            </button>
            <button id="btnSalida" class="bg-gray-800 hover:bg-red-700 border border-gray-600 text-gray-300 hover:text-white py-4 rounded-xl font-semibold transition flex flex-col items-center">
                <span>⬆️ FORZAR</span>
                <span class="text-xs uppercase">Salida</span>
            </button>
        </div>
        
        <div id="manualIndicator" class="hidden text-center text-yellow-400 font-bold animate-pulse mb-2">
            ⚠️ MODO MANUAL ACTIVADO: <span id="modoManualTexto">ENTRADA</span>
        </div>

    </div>

    <audio id="soundSuccess" src="https://assets.mixkit.co/active_storage/sfx/2578/2578-preview.m4a"></audio>
    <audio id="soundError" src="https://assets.mixkit.co/active_storage/sfx/2572/2572-preview.m4a"></audio>

    <script src="assets/js/kiosco.js"></script>

    <script>
      if ('serviceWorker' in navigator) {
        window.addEventListener('load', () => {
          navigator.serviceWorker.register('../service-worker.js')
            .then(reg => console.log('Service Worker registrado (Kiosco):', reg.scope))
            .catch(err => console.log('Error SW:', err));
        });
      }
    </script>
</body>
</html>