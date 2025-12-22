// public/assets/js/kiosco.js

// --- VARIABLES GLOBALES ---
let html5QrCode = null; 
let isScanning = true;
let currentSede = localStorage.getItem('asistencia_sede');
let manualMode = null; 
let SEDES = []; 
let intentosRecarga = 0;

// --- INICIALIZACIÓN ---
document.addEventListener('DOMContentLoaded', async () => {
    
    // 1. Asignar eventos
    document.getElementById('btnEntrada').addEventListener('click', () => toggleModoManual('ENTRADA'));
    document.getElementById('btnSalida').addEventListener('click', () => toggleModoManual('SALIDA'));
    document.getElementById('btnResetConfig').addEventListener('click', borrarConfig);
    
    // NUEVO: Toggle para mostrar/ocultar botones maestros
    document.getElementById('btnToggleMaestros').addEventListener('click', toggleVisibilidadMaestros);

    const btnReload = document.getElementById('btnReloadCam');
    if (btnReload) btnReload.addEventListener('click', recargarCamara);

    // 2. Cargar datos
    await cargarSedesBackend();

    // 3. Verificar estado
    if (!currentSede) {
        document.getElementById('setupModal').classList.remove('hidden');
    } else {
        const existe = SEDES.find(s => s.id === currentSede);
        if (!existe && SEDES.length > 0) {
            alert("La ubicación configurada ya no existe.");
            localStorage.removeItem('asistencia_sede');
            currentSede = null;
            document.getElementById('setupModal').classList.remove('hidden');
        } else {
            iniciarKiosco();
        }
    }
});

// --- NUEVA FUNCIÓN: VISIBILIDAD DE BOTONES MAESTROS ---
function toggleVisibilidadMaestros() {
    const contenedor = document.getElementById('controlesManuales');
    const btnTexto = document.getElementById('btnToggleMaestros');
    
    if (contenedor.classList.contains('hidden')) {
        // MOSTRAR
        contenedor.classList.remove('hidden');
        // Pequeño timeout para permitir que la transición CSS funcione (opacity)
        setTimeout(() => {
            contenedor.classList.remove('opacity-0', '-translate-y-4');
        }, 10);
        btnTexto.textContent = "Ocultar Botones Maestros";
        btnTexto.classList.add('text-red-500');
    } else {
        // OCULTAR
        contenedor.classList.add('opacity-0', '-translate-y-4');
        setTimeout(() => {
            contenedor.classList.add('hidden');
        }, 300); // Esperar a que termine la animación
        btnTexto.textContent = "Botones Maestros";
        btnTexto.classList.remove('text-red-500');
        
        // Opcional: Si ocultan el menú, desactivamos el modo manual por seguridad
        if (manualMode) resetManualMode(); 
    }
}

// --- GESTIÓN DE SEDES ---
async function cargarSedesBackend() {
    try {
        const res = await fetch('../controllers/SedeController.php'); 
        const data = await res.json();
        SEDES = data.map(s => ({ id: s.nombre, label: `🏢 ${s.nombre}`, color: s.color || 'bg-blue-600' }));
        generarBotonesSedes();
    } catch (error) {
        console.error("Error cargando sedes:", error);
        document.getElementById('listaSedes').innerHTML = '<p class="text-red-400 text-center">Error de conexión.</p>';
    }
}

function generarBotonesSedes() {
    const contenedor = document.getElementById('listaSedes');
    contenedor.innerHTML = ''; 
    if (SEDES.length === 0) {
        contenedor.innerHTML = '<p class="text-center text-gray-500">No hay sedes configuradas.</p>';
        return;
    }
    SEDES.forEach(sede => {
        const btn = document.createElement('button');
        btn.className = `${sede.color} hover:opacity-90 p-6 rounded-xl text-xl font-bold transition w-full shadow-lg mb-3 text-white flex justify-center items-center gap-2`;
        btn.innerHTML = sede.label;
        btn.onclick = () => guardarSede(sede.id);
        contenedor.appendChild(btn);
    });
}

function guardarSede(sedeId) {
    localStorage.setItem('asistencia_sede', sedeId);
    currentSede = sedeId;
    document.getElementById('setupModal').classList.add('hidden');
    iniciarKiosco();
}

function borrarConfig() {
    if(confirm("¿Quieres cambiar la ubicación física?")) {
        localStorage.removeItem('asistencia_sede');
        location.reload();
    }
}

function iniciarKiosco() {
    const sedeInfo = SEDES.find(s => s.id === currentSede);
    const nombreMostrar = sedeInfo ? sedeInfo.label.replace('🏢 ', '') : currentSede;
    document.getElementById('lblSede').textContent = `📍 UBICACIÓN: ${nombreMostrar}`;
    iniciarEscanner();
}

// --- CONFIGURACIÓN DEL ESCÁNER ---
function iniciarEscanner() {
    if (html5QrCode) html5QrCode = null; 
    html5QrCode = new Html5Qrcode("reader");
    
    const config = { fps: 10, qrbox: { width: 250, height: 250 } };
    html5QrCode.start({ facingMode: "environment" }, config, onScanSuccess)
    .catch(err => {
        console.error("Error cámara", err);
        mostrarMensaje('📷', 'ERROR CÁMARA', 'Presione Recargar', '', 'text-red-500');
    });
}

async function recargarCamara() {
    intentosRecarga++;
    if (intentosRecarga >= 2) {
        if(confirm("¿Deseas reiniciar el sistema completo (F5)?")) {
            location.reload();
            return;
        }
    }
    mostrarMensaje("🔄", "REINICIANDO...", "Cámara", "", "text-blue-500");
    if (html5QrCode) {
        try {
            await Promise.race([html5QrCode.stop(), new Promise((_, r) => setTimeout(() => r(new Error("Timeout")), 1500))]);
            html5QrCode.clear();
        } catch (e) { console.warn("Stop forzado", e); }
    }
    html5QrCode = null;
    const reader = document.getElementById('reader');
    if(reader) reader.innerHTML = ''; 
    setTimeout(() => { ocultarMensaje(); iniciarEscanner(); }, 800);
}

function onScanSuccess(decodedText, decodedResult) {
    if (!isScanning) return; 
    intentosRecarga = 0;
    isScanning = false; 
    enviarAsistencia(decodedText);
}

// --- COMUNICACIÓN BACKEND ---
async function enviarAsistencia(cedulaQr) {
    mostrarMensaje("⏳", "PROCESANDO...", "Validando datos...", "", "text-white");
    try {
        const payload = { cedula: cedulaQr, sede: currentSede, tipo_manual: manualMode };
        const response = await fetch('../controllers/RegistrarAsistencia.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });
        const data = await response.json();

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
            mostrarMensaje('❌', 'ERROR', data.message || 'No encontrado', '', 'text-red-500');
        }
    } catch (error) {
        playAudio('error');
        mostrarMensaje('🔌', 'ERROR RED', 'Fallo de conexión', '', 'text-red-500');
    }
    setTimeout(() => {
        ocultarMensaje();
        isScanning = true;
    }, 3500);
}

// --- MODOS MANUALES (CORREGIDO ERROR VISUAL) ---
function toggleModoManual(tipo) {
    // Si ya estaba activo, lo desactivamos
    if (manualMode === tipo) {
        resetManualMode();
        return;
    }

    // Activamos nuevo modo
    manualMode = tipo;
    
    // UI Indicadores
    const texto = document.getElementById('modoManualTexto');
    const indicador = document.getElementById('manualIndicator');
    texto.textContent = tipo;
    indicador.classList.remove('hidden');

    const btnEntrada = document.getElementById('btnEntrada');
    const btnSalida = document.getElementById('btnSalida');

    // 1. Limpiar visualmente ambos botones primero (Reset forzado)
    resetEstilosBotones();

    // 2. Aplicar estilos ACTIVOS al seleccionado
    if(tipo === 'ENTRADA') {
        // Quitamos estilos inactivos y ponemos activos
        btnEntrada.classList.remove('bg-gray-800', 'border-gray-600', 'text-gray-400');
        btnEntrada.classList.add('bg-green-600', 'border-green-400', 'text-white', 'ring-4', 'ring-green-500/50', 'shadow-[0_0_20px_rgba(34,197,94,0.5)]');
    } else {
        btnSalida.classList.remove('bg-gray-800', 'border-gray-600', 'text-gray-400');
        btnSalida.classList.add('bg-red-600', 'border-red-400', 'text-white', 'ring-4', 'ring-red-500/50', 'shadow-[0_0_20px_rgba(239,68,68,0.5)]');
    }
}

function resetManualMode() {
    manualMode = null;
    const indicador = document.getElementById('manualIndicator');
    if(indicador) indicador.classList.add('hidden');
    resetEstilosBotones();
}

function resetEstilosBotones() {
    const btnEntrada = document.getElementById('btnEntrada');
    const btnSalida = document.getElementById('btnSalida');

    // Definir las clases "Activas" que queremos eliminar
    const clasesActivasEntrada = ['bg-green-600', 'border-green-400', 'text-white', 'ring-4', 'ring-green-500/50', 'shadow-[0_0_20px_rgba(34,197,94,0.5)]'];
    const clasesActivasSalida = ['bg-red-600', 'border-red-400', 'text-white', 'ring-4', 'ring-red-500/50', 'shadow-[0_0_20px_rgba(239,68,68,0.5)]'];

    // Remover todo rastro de estado activo
    btnEntrada.classList.remove(...clasesActivasEntrada);
    btnSalida.classList.remove(...clasesActivasSalida);

    // Restaurar el estado "Inactivo" (Gris)
    // Usamos 'add' sin miedo, si ya la tiene no pasa nada
    btnEntrada.classList.add('bg-gray-800', 'border-gray-600', 'text-gray-400');
    btnSalida.classList.add('bg-gray-800', 'border-gray-600', 'text-gray-400');
}

// --- UTILS ---
function mostrarMensaje(icon, title, name, time, colorClass) {
    const msgDiv = document.getElementById('statusMessage');
    document.getElementById('statusIcon').textContent = icon;
    const titleEl = document.getElementById('statusTitle');
    titleEl.textContent = title;
    titleEl.className = `text-4xl font-bold text-center ${colorClass}`;
    document.getElementById('statusName').textContent = name;
    document.getElementById('statusTime').textContent = time;
    msgDiv.classList.remove('hidden');
}

function ocultarMensaje() {
    document.getElementById('statusMessage').classList.add('hidden');
}

function playAudio(type) {
    const audio = document.getElementById(type === 'success' ? 'soundSuccess' : 'soundError');
    if(audio) { audio.currentTime = 0; audio.play().catch(e => console.log("Audio block")); }
}