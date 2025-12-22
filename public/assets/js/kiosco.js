// public/assets/js/kiosco.js

// --- VARIABLES ---
let html5QrCode = null; 
let isScanning = true;
let currentSede = localStorage.getItem('asistencia_sede');
let manualMode = null; 
let SEDES = []; 
let lastReloadTime = 0; // Para controlar doble clic de pánico

// --- INICIO ---
document.addEventListener('DOMContentLoaded', async () => {
    document.getElementById('btnEntrada').addEventListener('click', () => toggleModoManual('ENTRADA'));
    document.getElementById('btnSalida').addEventListener('click', () => toggleModoManual('SALIDA'));
    document.getElementById('btnResetConfig').addEventListener('click', borrarConfig);
    document.getElementById('btnToggleMaestros').addEventListener('click', toggleVisibilidadMaestros);
    
    // Botón Recarga
    const btnReload = document.getElementById('btnReloadCam');
    if (btnReload) btnReload.addEventListener('click', recargarCamara);

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
});

function toggleVisibilidadMaestros() {
    const contenedor = document.getElementById('controlesManuales');
    const btnTexto = document.getElementById('btnToggleMaestros');
    
    if (contenedor.classList.contains('hidden')) {
        contenedor.classList.remove('hidden');
        setTimeout(() => contenedor.classList.remove('opacity-0', '-translate-y-4'), 10);
        btnTexto.textContent = "Ocultar Controles";
        btnTexto.classList.add('text-red-400');
    } else {
        contenedor.classList.add('opacity-0', '-translate-y-4');
        setTimeout(() => contenedor.classList.add('hidden'), 300);
        btnTexto.textContent = "Botones Maestros";
        btnTexto.classList.remove('text-red-400');
    }
}

// --- LOGICA SEDES (Sin cambios mayores) ---
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
        b.className = `${s.color} p-4 rounded-xl text-lg font-bold text-white w-full shadow-lg`;
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
        location.reload();
    }
}

function iniciarKiosco() {
    const s = SEDES.find(x => x.id === currentSede);
    document.getElementById('lblSede').textContent = s ? s.label : currentSede;
    iniciarEscanner();
}

// --- CÁMARA ROBUSTA ---
function iniciarEscanner() {
    // 1. Limpieza Nuclear previa
    if (html5QrCode) {
        try { html5QrCode.stop(); } catch(e){}
        html5QrCode = null;
    }
    document.getElementById('reader').innerHTML = ''; // Elimina videos zombies

    // 2. Nueva Instancia
    html5QrCode = new Html5Qrcode("reader");
    
    // Configuración ajustada para rendimiento
    const config = { 
        fps: 10, 
        qrbox: { width: 250, height: 250 },
        aspectRatio: 1.0, 
        disableFlip: false 
    };
    
    html5QrCode.start({ facingMode: "environment" }, config, onScanSuccess)
    .catch(err => {
        console.error("Error start camera", err);
        mostrarMensaje('📷', 'ERROR CÁMARA', 'Presione Recargar', '', 'text-red-500');
    });
}

async function recargarCamara() {
    const ahora = Date.now();
    
    // Si presiona 2 veces en menos de 3 segundos -> RECARGA TOTAL DE PÁGINA
    if (ahora - lastReloadTime < 3000) {
        if(confirm("¿La cámara sigue trabada? Forzar reinicio de página?")) {
            window.location.reload(true);
            return;
        }
    }
    lastReloadTime = ahora;

    mostrarMensaje("🔄", "REINICIANDO...", "Limpiando sensor...", "", "text-blue-500");

    // 1. Intentar detener suavemente
    if (html5QrCode) {
        try {
            await html5QrCode.stop();
            html5QrCode.clear();
        } catch (e) { console.warn("Stop falló, forzando limpieza DOM"); }
    }

    // 2. DESTRUCCIÓN TOTAL DEL DOM DEL LECTOR
    // Esto quita cualquier etiqueta <video> pegada que causa el congelamiento
    const readerDiv = document.getElementById('reader');
    readerDiv.innerHTML = ''; 
    html5QrCode = null;

    // 3. Pequeña pausa para que el navegador libere el recurso
    setTimeout(() => {
        ocultarMensaje();
        iniciarEscanner();
    }, 1000);
}

function onScanSuccess(decodedText) {
    if (!isScanning) return; 
    isScanning = false; 
    enviarAsistencia(decodedText);
}

// --- ENVÍO DATOS ---
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
        } else if (data.status === 'warning') {
            playAudio('error');
            mostrarMensaje('⚠️', 'ESPERA', data.message, '', 'text-yellow-400');
        } else {
            playAudio('error');
            mostrarMensaje('❌', 'ERROR', data.message, '', 'text-red-500');
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

// --- MODALES Y UI ---
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

function toggleModoManual(tipo) {
    if (manualMode === tipo) { resetManualMode(); return; }
    
    manualMode = tipo;
    const txt = document.getElementById('modoManualTexto');
    const ind = document.getElementById('manualIndicator');
    txt.textContent = tipo;
    ind.classList.remove('hidden');

    resetEstilosBotones();
    const btn = tipo === 'ENTRADA' ? document.getElementById('btnEntrada') : document.getElementById('btnSalida');
    const color = tipo === 'ENTRADA' ? 'green' : 'red';
    
    btn.classList.remove('bg-gray-800', 'text-gray-400');
    btn.classList.add(`bg-${color}-600`, `border-${color}-400`, 'text-white', 'ring-4', `ring-${color}-500/50`);
}

function resetManualMode() {
    manualMode = null;
    document.getElementById('manualIndicator').classList.add('hidden');
    resetEstilosBotones();
}

function resetEstilosBotones() {
    ['btnEntrada', 'btnSalida'].forEach(id => {
        const b = document.getElementById(id);
        b.className = "bg-gray-800/80 hover:bg-gray-700 border border-gray-600/50 text-gray-400 hover:text-white py-4 rounded-xl font-semibold transition flex flex-col items-center group backdrop-blur-sm";
    });
}

function playAudio(type) {
    const a = document.getElementById(type === 'success' ? 'soundSuccess' : 'soundError');
    if(a) { a.currentTime = 0; a.play().catch(e=>{}); }
}