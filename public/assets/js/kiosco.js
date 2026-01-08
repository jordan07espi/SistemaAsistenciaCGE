// public/assets/js/kiosco.js

// --- VARIABLES ---
let html5QrCode = null; 
let isScanning = true;
let currentSede = localStorage.getItem('asistencia_sede');
let manualMode = null; 
let SEDES = []; 

// Variables para el temporizador visual
let maestrosTimeout = null; 
let countdownInterval = null; 
let secondsLeft = 15; 

// --- INICIO ---
document.addEventListener('DOMContentLoaded', async () => {
    document.getElementById('btnEntrada').addEventListener('click', () => toggleModoManual('ENTRADA'));
    document.getElementById('btnSalida').addEventListener('click', () => toggleModoManual('SALIDA'));
    document.getElementById('btnResetConfig').addEventListener('click', borrarConfig);
    document.getElementById('btnToggleMaestros').addEventListener('click', toggleVisibilidadMaestros);
    
    const btnReload = document.getElementById('btnReloadCam');
    if (btnReload) btnReload.addEventListener('click', recargarCamara);

    // EVENTO: Tocar pantalla reinicia el tiempo (si está en manual)
    document.addEventListener('click', () => {
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

    // Iniciar Reloj Normal
    actualizarTextoVisual();
    setInterval(actualizarTextoVisual, 60000); 
});

// --- LÓGICA VISUAL (Reloj y Textos) ---

function actualizarTextoVisual() {
    // Si hay modo manual activo, no tocamos nada (manda el timer)
    if (manualMode) return; 

    const now = new Date();
    const hora = now.getHours();
    
    const container = document.getElementById('estadoVisualContainer');
    const titulo = document.getElementById('estadoVisualTexto');
    const sub = document.getElementById('estadoVisualSub');

    // RESET COMPLETO DE ESTILOS (Para limpiar lo que deja el modo manual)
    container.className = "mb-4 text-center p-3 rounded-xl border-2 transition-all duration-500 bg-gray-800/50 border-gray-600";
    titulo.className = "text-2xl md:text-3xl font-black uppercase tracking-widest text-white drop-shadow-md";
    // Restaurar estilo pequeño por defecto para el subtítulo
    sub.className = "text-[10px] font-bold uppercase tracking-wide text-gray-400";

    if (hora >= 7 && hora < 12) {
        // ENTRADA
        container.classList.remove('bg-gray-800/50', 'border-gray-600');
        container.classList.add('bg-green-900/40', 'border-green-500');
        titulo.classList.add('text-green-400');
        titulo.textContent = "MARCANDO ENTRADA";
        sub.textContent = "TURNO MATUTINO";
    } else if (hora >= 12) {
        // SALIDA
        container.classList.remove('bg-gray-800/50', 'border-gray-600');
        container.classList.add('bg-red-900/40', 'border-red-500');
        titulo.classList.add('text-red-400');
        titulo.textContent = "MARCANDO SALIDA";
        sub.textContent = "TURNO VESPERTINO / SALIDA";
    } else {
        // MADRUGADA
        titulo.textContent = "ESCANEAR QR";
        sub.textContent = "ESPERANDO LECTURA";
    }
}

function actualizarTextoConTimer() {
    if (!manualMode) return;

    const titulo = document.getElementById('estadoVisualTexto');
    const sub = document.getElementById('estadoVisualSub');

    // AQUÍ ESTÁ EL CAMBIO: Ponemos el contador en el TÍTULO GIGANTE
    titulo.textContent = `FORZANDO ${manualMode} (${secondsLeft}s)`;
    
    // El subtítulo solo da la instrucción (un poco más visible que antes)
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
        // ABRIR MENÚ
        const now = new Date();
        const hora = now.getHours();
        
        btnEntrada.classList.remove('hidden');
        btnSalida.classList.remove('hidden');

        // INTELIGENCIA
        if (hora >= 7 && hora < 12) {
            btnEntrada.classList.add('hidden'); 
        } else if (hora >= 12) {
            btnSalida.classList.add('hidden'); 
        }

        contenedor.classList.remove('hidden');
        setTimeout(() => contenedor.classList.remove('opacity-0', '-translate-y-4'), 10);
        btnTexto.textContent = "Cancelar";
        btnTexto.classList.add('text-red-400');

        // Autocierre del menú (10s)
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
    if (manualMode === tipo) { 
        resetManualMode(); 
        return; 
    }
    
    manualMode = tipo;
    
    // UI Indicador Inferior
    const txt = document.getElementById('modoManualTexto');
    const ind = document.getElementById('manualIndicator');
    txt.textContent = tipo;
    ind.classList.remove('hidden');

    ocultarMaestros();

    // Actualizar Estilos del Encabezado Gigante
    const color = tipo === 'ENTRADA' ? 'green' : 'red';
    const container = document.getElementById('estadoVisualContainer');
    const titulo = document.getElementById('estadoVisualTexto');
    
    container.className = `mb-4 text-center p-3 rounded-xl border-2 transition-all duration-500 bg-${color}-900/40 border-${color}-500`;
    titulo.className = `text-2xl md:text-3xl font-black uppercase tracking-widest text-${color}-400 drop-shadow-lg animate-pulse`;
    
    // INICIAR CUENTA REGRESIVA
    startCountdown();
}

function startCountdown() {
    if (countdownInterval) clearInterval(countdownInterval);
    
    secondsLeft = 15; 
    actualizarTextoConTimer(); // Mostrar 15s inmediatamente en el título

    countdownInterval = setInterval(() => {
        secondsLeft--;
        actualizarTextoConTimer();

        if (secondsLeft <= 0) {
            clearInterval(countdownInterval);
            resetManualMode(); // Se acabó el tiempo
        }
    }, 1000);
}

function resetManualMode() {
    manualMode = null;
    document.getElementById('manualIndicator').classList.add('hidden');
    
    if (countdownInterval) { clearInterval(countdownInterval); countdownInterval = null; }
    
    actualizarTextoVisual(); // Volver al texto automático normal
}

// --- CONFIGURACIÓN & BACKEND --- (Sin cambios)
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
    if (html5QrCode) { try { html5QrCode.clear(); } catch(e){} html5QrCode = null; }
    document.getElementById('reader').innerHTML = ''; 

    html5QrCode = new Html5Qrcode("reader");
    const config = { fps: 10, qrbox: { width: 250, height: 250 }, aspectRatio: 1.0 };
    
    html5QrCode.start({ facingMode: "environment" }, config, onScanSuccess)
    .catch(err => {
        mostrarMensaje('📷', 'ERROR CÁMARA', 'Presione Recargar', '', 'text-red-500');
    });
}

function recargarCamara() {
    mostrarMensaje("🔄", "REINICIANDO...", "Espere...", "", "text-blue-500");
    setTimeout(() => { window.location.reload(); }, 500);
}

function onScanSuccess(decodedText) {
    if (!isScanning) return; 
    isScanning = false; 
    
    // Detener contador al escanear
    if (countdownInterval) { clearInterval(countdownInterval); }

    enviarAsistencia(decodedText);
}

async function enviarAsistencia(cedulaQr) {
    mostrarMensaje("⏳", "PROCESANDO...", "Validando...", "", "text-white");

    try {
        const res = await fetch('../controllers/RegistrarAsistencia.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ cedula: cedulaQr, sede: currentSede, tipo_manual: manualMode })
        });
        const data = await res.json();

        if (data.status === 'success') {
            playAudio('success');
            const color = data.tipo === 'ENTRADA' ? 'text-green-500' : 'text-blue-500';
            const icono = data.tipo === 'ENTRADA' ? '👋' : '🏠';
            mostrarMensaje(icono, data.tipo, data.colaborador, data.hora, color);
            
            // Éxito: Reset (One-Shot)
            if (manualMode) resetManualMode();

        } else if (data.status === 'warning') {
            playAudio('error');
            mostrarMensaje('⚠️', 'ESPERA', data.message, '', 'text-yellow-400');
            // Warning: Reiniciar contador
            if (manualMode) startCountdown();
        } else {
            playAudio('error');
            mostrarMensaje('❌', 'ERROR', data.message, '', 'text-red-500');
            // Error: Reiniciar contador
            if (manualMode) startCountdown();
        }
    } catch (e) {
        playAudio('error');
        mostrarMensaje('🔌', 'OFFLINE', 'Sin conexión', '', 'text-red-500');
    }

    setTimeout(() => {
        ocultarMensaje();
        isScanning = true;
    }, 3000);
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