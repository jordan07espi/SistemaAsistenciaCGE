// public/assets/js/kiosco.js

// --- VARIABLES ---
let html5QrCode = null; 
let isScanning = true;
let currentSede = localStorage.getItem('asistencia_sede');
let manualMode = null; 
let SEDES = []; 

// Variables Timer Visual
let maestrosTimeout = null; 
let countdownInterval = null; 
let secondsLeft = 15; 

// Variables de Estabilidad (Watchdog)
let lastScanTime = Date.now(); // Última vez que el sistema estuvo vivo
let watchdogInterval = null;
let isProcessing = false; // Semáforo para no interrumpir envíos

// --- INICIO ---
document.addEventListener('DOMContentLoaded', async () => {
    // Listeners
    document.getElementById('btnEntrada').addEventListener('click', () => toggleModoManual('ENTRADA'));
    document.getElementById('btnSalida').addEventListener('click', () => toggleModoManual('SALIDA'));
    document.getElementById('btnResetConfig').addEventListener('click', borrarConfig);
    document.getElementById('btnToggleMaestros').addEventListener('click', toggleVisibilidadMaestros);
    
    const btnReload = document.getElementById('btnReloadCam');
    if (btnReload) btnReload.addEventListener('click', recargarCamara);

    // Click en pantalla reinicia timer manual
    document.addEventListener('click', () => {
        lastScanTime = Date.now(); // Actividad detectada
        if (manualMode && secondsLeft < 15) {
            secondsLeft = 15; 
            actualizarTextoConTimer(); 
        }
    });

    await cargarSedesBackend();

    if (!currentSede) {
        document.getElementById('setupModal').classList.remove('hidden');
    } else {
        const existe = SEDES.find(s => s.id === currentSede);
        if (!existe && SEDES.length > 0) {
            localStorage.removeItem('asistencia_sede');
            currentSede = null;
            document.getElementById('setupModal').classList.remove('hidden');
        } else {
            iniciarKiosco();
        }
    }

    // Iniciar Reloj Visual
    actualizarTextoVisual();
    setInterval(actualizarTextoVisual, 60000); 

    // INICIAR EL PERRO GUARDIÁN (WATCHDOG)
    iniciarWatchdog();
});

// --- SISTEMA DE AUTO-REPARACIÓN (WATCHDOG) ---
function iniciarWatchdog() {
    // Revisa cada 30 segundos si la cámara sigue viva
    if (watchdogInterval) clearInterval(watchdogInterval);
    
    watchdogInterval = setInterval(() => {
        // Si estamos procesando una asistencia, no molestar
        if (isProcessing) return;

        // Si el lector existe pero el video está pausado o congelado, reiniciar
        const reader = document.getElementById('reader');
        // Simple verificación: Si han pasado 5 minutos sin actividad, refrescar preventivamente
        // O si detectamos un error de estado en html5QrCode
        if (html5QrCode && !html5QrCode.isScanning && !manualMode) {
             console.log("Watchdog: Cámara parece detenida. Reiniciando...");
             recargarCamara(false); // Reinicio silencioso
        }
    }, 30000); 
}

// --- LÓGICA VISUAL ---
function actualizarTextoVisual() {
    if (manualMode) return; 

    const now = new Date();
    const hora = now.getHours();
    
    const container = document.getElementById('estadoVisualContainer');
    const titulo = document.getElementById('estadoVisualTexto');
    const sub = document.getElementById('estadoVisualSub');

    // Reset estilos
    container.className = "mb-4 text-center p-3 rounded-xl border-2 transition-all duration-500 bg-gray-800/50 border-gray-600";
    titulo.className = "text-2xl md:text-3xl font-black uppercase tracking-widest text-white drop-shadow-md";
    sub.className = "text-[10px] font-bold uppercase tracking-wide text-gray-400";

    if (hora >= 7 && hora < 12) {
        container.classList.remove('bg-gray-800/50', 'border-gray-600');
        container.classList.add('bg-green-900/40', 'border-green-500');
        titulo.classList.add('text-green-400');
        titulo.textContent = "MARCANDO ENTRADA";
        sub.textContent = "TURNO MATUTINO";
    } else if (hora >= 12) {
        container.classList.remove('bg-gray-800/50', 'border-gray-600');
        container.classList.add('bg-red-900/40', 'border-red-500');
        titulo.classList.add('text-red-400');
        titulo.textContent = "MARCANDO SALIDA";
        sub.textContent = "TURNO VESPERTINO / SALIDA";
    } else {
        titulo.textContent = "ESCANEAR QR";
        sub.textContent = "ESPERANDO LECTURA";
    }
}

function actualizarTextoConTimer() {
    if (!manualMode) return;
    const titulo = document.getElementById('estadoVisualTexto');
    const sub = document.getElementById('estadoVisualSub');
    titulo.textContent = `FORZANDO ${manualMode} (${secondsLeft}s)`;
    sub.textContent = "TOCA LA PANTALLA PARA EXTENDER TIEMPO";
    sub.className = "text-xs font-bold uppercase tracking-wide text-white animate-pulse";
}

// --- LÓGICA BOTONES MAESTROS ---
function toggleVisibilidadMaestros() {
    const contenedor = document.getElementById('controlesManuales');
    const btnTexto = document.getElementById('btnToggleMaestros');
    const btnEntrada = document.getElementById('btnEntrada');
    const btnSalida = document.getElementById('btnSalida');

    if (maestrosTimeout) { clearTimeout(maestrosTimeout); maestrosTimeout = null; }

    if (contenedor.classList.contains('hidden')) {
        const now = new Date();
        const hora = now.getHours();
        
        btnEntrada.classList.remove('hidden');
        btnSalida.classList.remove('hidden');

        if (hora >= 7 && hora < 12) {
            btnEntrada.classList.add('hidden'); 
        } else if (hora >= 12) {
            btnSalida.classList.add('hidden'); 
        }

        contenedor.classList.remove('hidden');
        setTimeout(() => contenedor.classList.remove('opacity-0', '-translate-y-4'), 10);
        btnTexto.textContent = "Cancelar";
        btnTexto.classList.add('text-red-400');

        maestrosTimeout = setTimeout(() => { ocultarMaestros(); }, 10000);
    } else {
        ocultarMaestros();
    }
}

function ocultarMaestros() {
    const contenedor = document.getElementById('controlesManuales');
    const btnTexto = document.getElementById('btnToggleMaestros');
    if (!contenedor) return;

    contenedor.classList.add('opacity-0', '-translate-y-4');
    setTimeout(() => contenedor.classList.add('hidden'), 300);
    btnTexto.textContent = "Botones Maestros";
    btnTexto.classList.remove('text-red-400');
    if (maestrosTimeout) { clearTimeout(maestrosTimeout); maestrosTimeout = null; }
}

function toggleModoManual(tipo) {
    if (manualMode === tipo) { resetManualMode(); return; }
    
    manualMode = tipo;
    const txt = document.getElementById('modoManualTexto');
    const ind = document.getElementById('manualIndicator');
    txt.textContent = tipo;
    ind.classList.remove('hidden');

    ocultarMaestros();

    const color = tipo === 'ENTRADA' ? 'green' : 'red';
    const container = document.getElementById('estadoVisualContainer');
    const titulo = document.getElementById('estadoVisualTexto');
    
    container.className = `mb-4 text-center p-3 rounded-xl border-2 transition-all duration-500 bg-${color}-900/40 border-${color}-500`;
    titulo.className = `text-2xl md:text-3xl font-black uppercase tracking-widest text-${color}-400 drop-shadow-lg animate-pulse`;
    
    startCountdown();
}

function startCountdown() {
    if (countdownInterval) clearInterval(countdownInterval);
    secondsLeft = 15; 
    actualizarTextoConTimer(); 

    countdownInterval = setInterval(() => {
        secondsLeft--;
        actualizarTextoConTimer();
        if (secondsLeft <= 0) {
            clearInterval(countdownInterval);
            resetManualMode(); 
        }
    }, 1000);
}

function resetManualMode() {
    manualMode = null;
    document.getElementById('manualIndicator').classList.add('hidden');
    if (countdownInterval) { clearInterval(countdownInterval); countdownInterval = null; }
    actualizarTextoVisual(); 
}

// --- CONFIGURACIÓN & BACKEND --- 
async function cargarSedesBackend() {
    try {
        const res = await fetch('../controllers/SedeController.php'); 
        const data = await res.json();
        SEDES = data.map(s => ({ id: s.nombre, label: `🏢 ${s.nombre}`, color: s.color || 'bg-blue-600' }));
        generarBotonesSedes();
    } catch (e) { console.error(e); }
}

function generarBotonesSedes() {
    const c = document.getElementById('listaSedes');
    c.innerHTML = '';
    SEDES.forEach(s => {
        const b = document.createElement('button');
        b.className = `${s.color} p-4 rounded-xl text-lg font-bold text-white w-full shadow-lg mb-2`;
        b.textContent = s.label;
        b.onclick = () => guardarSede(s.id);
        c.appendChild(b);
    });
}

function guardarSede(id) {
    localStorage.setItem('asistencia_sede', id);
    currentSede = id;
    document.getElementById('setupModal').classList.add('hidden');
    iniciarKiosco();
}

function borrarConfig() {
    if(confirm("¿Cambiar ubicación?")) {
        localStorage.removeItem('asistencia_sede');
        window.location.reload();
    }
}

function iniciarKiosco() {
    const s = SEDES.find(x => x.id === currentSede);
    document.getElementById('lblSede').textContent = s ? s.label : currentSede;
    iniciarEscanner();
}

function iniciarEscanner() {
    // Limpieza profunda antes de iniciar
    if (html5QrCode) { 
        try { html5QrCode.stop().then(() => html5QrCode.clear()); } catch(e){} 
        html5QrCode = null;
    }
    document.getElementById('reader').innerHTML = ''; 

    html5QrCode = new Html5Qrcode("reader");
    const config = { fps: 10, qrbox: { width: 250, height: 250 }, aspectRatio: 1.0 };
    
    html5QrCode.start({ facingMode: "environment" }, config, onScanSuccess)
    .catch(err => {
        // Si falla el inicio, intentar de nuevo en 3 segundos automáticamente
        console.error("Fallo camara, reintentando...", err);
        setTimeout(() => recargarCamara(false), 3000);
    });
}

function recargarCamara(mostrarMensajeUser = true) {
    if(mostrarMensajeUser) mostrarMensaje("🔄", "REINICIANDO...", "Espere...", "", "text-blue-500");
    // Forzar recarga completa de página para limpiar memoria del navegador
    setTimeout(() => { window.location.reload(); }, 500);
}

// --- PROCESAMIENTO BLINDADO ---
function onScanSuccess(decodedText) {
    if (!isScanning || isProcessing) return; 
    
    isScanning = false; 
    isProcessing = true; // Bloquear nuevos escaneos
    
    // 1. PAUSAR CÁMARA (Para liberar CPU/Memoria durante el envío)
    if(html5QrCode) {
        html5QrCode.pause(); 
    }

    if (countdownInterval) { clearInterval(countdownInterval); }

    enviarAsistencia(decodedText);
}

async function enviarAsistencia(cedulaQr) {
    mostrarMensaje("⏳", "PROCESANDO...", "Conectando...", "", "text-white");

    // Timeout Controller: Si tarda más de 8 segundos, abortar.
    const controller = new AbortController();
    const timeoutId = setTimeout(() => controller.abort(), 8000); 

    try {
        const res = await fetch('../controllers/RegistrarAsistencia.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ cedula: cedulaQr, sede: currentSede, tipo_manual: manualMode }),
            signal: controller.signal // Vincular timeout
        });
        clearTimeout(timeoutId); // Limpiar timeout si respondió a tiempo

        const data = await res.json();

        if (data.status === 'success') {
            playAudio('success');
            const color = data.tipo === 'ENTRADA' ? 'text-green-500' : 'text-blue-500';
            const icono = data.tipo === 'ENTRADA' ? '👋' : '🏠';
            mostrarMensaje(icono, data.tipo, data.colaborador, data.hora, color);
            if (manualMode) resetManualMode();

        } else if (data.status === 'warning') {
            playAudio('error');
            mostrarMensaje('⚠️', 'ESPERA', data.message, '', 'text-yellow-400');
            if (manualMode) startCountdown();
        } else {
            playAudio('error');
            mostrarMensaje('❌', 'ERROR', data.message, '', 'text-red-500');
            if (manualMode) startCountdown();
        }

    } catch (e) {
        playAudio('error');
        // Mensaje específico para timeout o falta de red
        if (e.name === 'AbortError') {
            mostrarMensaje('🐢', 'LENTITUD', 'Red inestable', '', 'text-orange-500');
        } else {
            mostrarMensaje('🔌', 'OFFLINE', 'Revise conexión', '', 'text-red-500');
        }
    }

    // FINALIZAR PROCESO Y REANUDAR
    setTimeout(() => {
        ocultarMensaje();
        isScanning = true;
        isProcessing = false;
        
        // 2. REANUDAR CÁMARA (Solo ahora volvemos a consumir recursos)
        if(html5QrCode) {
            try { html5QrCode.resume(); } catch(err) { 
                console.log("Error reanudando, reiniciando...", err);
                recargarCamara(false); // Si falla al reanudar, recarga forzosa
            }
        }
    }, 2500); // Dar tiempo a leer el mensaje
}

function mostrarMensaje(icon, title, name, time, colorClass) {
    const m = document.getElementById('statusMessage');
    document.getElementById('statusIcon').textContent = icon;
    const t = document.getElementById('statusTitle');
    t.textContent = title;
    t.className = `text-4xl font-bold text-center ${colorClass}`;
    document.getElementById('statusName').textContent = name;
    document.getElementById('statusTime').textContent = time;
    m.classList.remove('hidden');
}

function ocultarMensaje() {
    document.getElementById('statusMessage').classList.add('hidden');
}

function playAudio(type) {
    const a = document.getElementById(type === 'success' ? 'soundSuccess' : 'soundError');
    if(a) { a.currentTime = 0; a.play().catch(e=>{}); }
}