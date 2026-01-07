// public/assets/js/kiosco.js

// --- VARIABLES ---
let html5QrCode = null; 
let isScanning = true;
let currentSede = localStorage.getItem('asistencia_sede');
let manualMode = null; 
let SEDES = []; 

// --- INICIO ---
document.addEventListener('DOMContentLoaded', async () => {
    // Event Listeners
    document.getElementById('btnEntrada').addEventListener('click', () => toggleModoManual('ENTRADA'));
    document.getElementById('btnSalida').addEventListener('click', () => toggleModoManual('SALIDA'));
    document.getElementById('btnResetConfig').addEventListener('click', borrarConfig);
    document.getElementById('btnToggleMaestros').addEventListener('click', toggleVisibilidadMaestros);
    const btnReload = document.getElementById('btnReloadCam');
    if (btnReload) btnReload.addEventListener('click', recargarCamara);

    // Iniciar lógica de Sede
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

    // --- NUEVO: INICIAR RELOJ VISUAL ---
    actualizarTextoVisual();
    setInterval(actualizarTextoVisual, 60000); // Revisar cada minuto
});

// --- LÓGICA VISUAL (Horarios) ---
function actualizarTextoVisual() {
    // Si hay modo manual activo, no sobrescribimos con la hora
    if (manualMode) return; 

    const now = new Date();
    const hora = now.getHours();
    
    const container = document.getElementById('estadoVisualContainer');
    const titulo = document.getElementById('estadoVisualTexto');
    const sub = document.getElementById('estadoVisualSub');

    // Limpiar clases de color
    container.classList.remove('bg-green-900/40', 'border-green-500', 'bg-red-900/40', 'border-red-500', 'bg-gray-800/50', 'border-gray-600');
    titulo.classList.remove('text-green-400', 'text-red-400');

    // LÓGICA HORARIA (Según lo que pediste)
    // 07:00 AM - 11:59 AM -> ENTRADA
    // 12:00 PM - Adelante -> SALIDA
    
    if (hora >= 7 && hora < 12) {
        // ENTRADA
        container.classList.add('bg-green-900/40', 'border-green-500');
        titulo.classList.add('text-green-400');
        titulo.textContent = "MARCANDO ENTRADA";
        sub.textContent = "TURNO MATUTINO";
    } else if (hora >= 12) {
        // SALIDA (o reingresos tarde)
        container.classList.add('bg-red-900/40', 'border-red-500');
        titulo.classList.add('text-red-400');
        titulo.textContent = "MARCANDO SALIDA";
        sub.textContent = "TURNO VESPERTINO / SALIDA";
    } else {
        // HORARIO NO DEFINIDO (Madrugada)
        container.classList.add('bg-gray-800/50', 'border-gray-600');
        titulo.textContent = "ESCANEAR QR";
        sub.textContent = "ESPERANDO LECTURA";
    }
}

// --- RESTO DE FUNCIONES (Kiosco Standard) ---

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
    if (html5QrCode) {
        try { html5QrCode.clear(); } catch(e){}
        html5QrCode = null;
    }
    document.getElementById('reader').innerHTML = ''; 

    html5QrCode = new Html5Qrcode("reader");
    const config = { fps: 10, qrbox: { width: 250, height: 250 }, aspectRatio: 1.0 };
    
    html5QrCode.start({ facingMode: "environment" }, config, onScanSuccess)
    .catch(err => {
        console.error("Fallo camara:", err);
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
    if (manualMode === tipo) { 
        resetManualMode(); 
        actualizarTextoVisual(); // Volver al texto automático
        return; 
    }
    
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
    
    // Actualizar visualmente el encabezado grande también
    const container = document.getElementById('estadoVisualContainer');
    const titulo = document.getElementById('estadoVisualTexto');
    const sub = document.getElementById('estadoVisualSub');
    
    container.className = `mb-4 text-center p-3 rounded-xl border-2 transition-all duration-500 bg-${color}-900/40 border-${color}-500`;
    titulo.className = `text-2xl md:text-3xl font-black uppercase tracking-widest text-${color}-400 drop-shadow-lg animate-pulse`;
    titulo.textContent = `FORZANDO ${tipo}`;
    sub.textContent = "MODO MANUAL ACTIVO";
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