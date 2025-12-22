<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
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
            background-color: #000;
        }
        /* Forzar que el video ocupe todo el espacio y no se desborde */
        #reader video {
            object-fit: cover;
            width: 100% !important;
            height: 100% !important;
            border-radius: 12px;
        }
    </style>
</head>
<body class="bg-gray-900 text-white h-screen flex flex-col items-center justify-start pt-10 relative overflow-hidden bg-[radial-gradient(ellipse_at_top,_var(--tw-gradient-stops))] from-gray-800 via-gray-900 to-black">

    <div id="setupModal" class="fixed inset-0 bg-black/95 backdrop-blur-sm z-50 flex flex-col items-center justify-center p-4 hidden">
        <h2 class="text-2xl font-bold mb-6 text-center text-white">Configuración del Dispositivo</h2>
        <div id="listaSedes" class="grid gap-4 w-full max-w-sm"></div>
    </div>

    <div class="w-full max-w-md p-4 flex flex-col z-10">
        
        <div class="text-center mb-2">
            <h1 class="text-xl font-bold tracking-wider text-white drop-shadow-md">REGISTRO DE ASISTENCIA</h1>
            <p id="lblSede" class="text-xs text-blue-400 font-mono mt-1 font-bold uppercase tracking-widest">CARGANDO SEDE...</p>
            
            <div class="flex justify-center items-center gap-3 mt-2 flex-wrap opacity-70 hover:opacity-100 transition-opacity">
                <button id="btnResetConfig" class="text-[10px] text-gray-500 hover:text-white underline uppercase tracking-wide">Cambiar Ubicación</button>
                <span class="text-gray-700 text-[10px]">|</span>
                <button id="btnToggleMaestros" class="text-[10px] text-blue-500 hover:text-blue-300 underline uppercase tracking-wide font-bold">Botones Maestros</button>
                <span class="text-gray-700 text-[10px]">|</span>
                <a href="../admin/login.php" class="text-[10px] text-gray-500 hover:text-white underline uppercase tracking-wide">Acceso Admin</a>
            </div>
        </div>

        <div class="relative w-full aspect-square bg-black rounded-3xl border border-gray-700/50 overflow-hidden shadow-2xl shadow-black/50 my-2 backdrop-blur-sm">
            <div id="reader" class="w-full h-full"></div>
            <div class="scan-line z-10 shadow-[0_0_15px_rgba(239,68,68,0.6)]"></div>
            
            <div id="statusMessage" class="absolute inset-0 flex flex-col items-center justify-center bg-black/90 hidden z-20 transition-all backdrop-blur-md">
                <div id="statusIcon" class="text-7xl mb-4 animate-bounce">✅</div>
                <h2 id="statusTitle" class="text-3xl font-black text-center tracking-tight">ENTRADA</h2>
                <p id="statusName" class="text-xl text-gray-200 mt-2 text-center px-6 font-light">Juan Perez</p>
                <div class="mt-6 px-4 py-1 bg-gray-800 rounded-full border border-gray-700">
                    <p id="statusTime" class="text-sm font-mono text-gray-400">08:00:00</p>
                </div>
            </div>
        </div>

        <div class="flex justify-center mb-4">
            <button id="btnReloadCam" class="group relative inline-flex items-center justify-center gap-3 px-6 py-2.5 
                bg-gray-800/40 backdrop-blur-md border border-white/10 
                rounded-full shadow-lg shadow-black/20 
                text-gray-400 font-medium tracking-wide text-[11px] uppercase 
                transition-all duration-300 ease-out 
                hover:bg-gray-700/60 hover:text-white hover:scale-105 active:scale-95">
                <span class="text-base transition-transform duration-700 group-hover:rotate-180 text-blue-500">🔄</span>
                <span>Recargar Cámara</span>
            </button>
        </div>

        <div id="controlesManuales" class="hidden transition-all duration-300 ease-in-out opacity-0 transform -translate-y-4">
            <div class="grid grid-cols-2 gap-4 mb-4 p-4 bg-gray-800/40 rounded-2xl border border-gray-700/50 backdrop-blur-md shadow-xl">
                <button id="btnEntrada" class="bg-gray-800/80 hover:bg-green-900/80 border border-gray-600/50 text-gray-400 hover:text-white py-4 rounded-xl font-semibold transition flex flex-col items-center group">
                    <span class="text-2xl group-hover:scale-110 mb-1">⬇️</span>
                    <span class="text-[10px] uppercase font-bold tracking-widest">Forzar Entrada</span>
                </button>
                <button id="btnSalida" class="bg-gray-800/80 hover:bg-red-900/80 border border-gray-600/50 text-gray-400 hover:text-white py-4 rounded-xl font-semibold transition flex flex-col items-center group">
                    <span class="text-2xl group-hover:scale-110 mb-1">⬆️</span>
                    <span class="text-[10px] uppercase font-bold tracking-widest">Forzar Salida</span>
                </button>
            </div>
        </div>
        
        <div id="manualIndicator" class="hidden text-center text-yellow-400 font-bold animate-pulse mb-4 bg-yellow-400/10 p-3 rounded-xl border border-yellow-400/20 backdrop-blur-sm">
            ⚠️ MODO MANUAL: <span id="modoManualTexto">ENTRADA</span>
        </div>
    </div>

    <audio id="soundSuccess" src="https://assets.mixkit.co/active_storage/sfx/2578/2578-preview.m4a"></audio>
    <audio id="soundError" src="https://assets.mixkit.co/active_storage/sfx/2572/2572-preview.m4a"></audio>

    <script src="assets/js/kiosco.js"></script>
    <script>
      if ('serviceWorker' in navigator) {
        window.addEventListener('load', () => navigator.serviceWorker.register('../service-worker.js'));
      }
    </script>
</body>
</html>