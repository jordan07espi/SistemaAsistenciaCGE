// public/assets/js/kiosco.js

// --- VARIABLES GLOBALES ---
let html5QrcodeScanner = null;
let isScanning = true;
let currentSede = localStorage.getItem('asistencia_sede');
let manualMode = null; 
let manualTimeout = null;
let SEDES = []; // Se llenará desde la BD

// --- INICIALIZACIÓN ---
document.addEventListener('DOMContentLoaded', async () => {
    
    // 1. Asignar eventos a botones estáticos
    document.getElementById('btnEntrada').addEventListener('click', () => activarModoManual('ENTRADA'));
    document.getElementById('btnSalida').addEventListener('click', () => activarModoManual('SALIDA'));
    document.getElementById('btnResetConfig').addEventListener('click', borrarConfig);

    // 2. Cargar configuración de Sedes desde el Backend
    await cargarSedesBackend();

    // 3. Verificar estado inicial
    if (!currentSede) {
        document.getElementById('setupModal').classList.remove('hidden');
    } else {
        // Validar si la sede guardada aún existe en la nueva lista
        const existe = SEDES.find(s => s.id === currentSede);
        if (!existe && SEDES.length > 0) {
            // Si la sede fue borrada del admin, obligar a re-configurar
            alert("La ubicación configurada ya no existe. Por favor selecciona una nueva.");
            localStorage.removeItem('asistencia_sede');
            currentSede = null;
            document.getElementById('setupModal').classList.remove('hidden');
        } else {
            iniciarKiosco();
        }
    }
});

// --- GESTIÓN DE SEDES ---
async function cargarSedesBackend() {
    try {
        // Petición al controlador que creamos anteriormente
        const res = await fetch('../controllers/SedeController.php'); 
        const data = await res.json();
        
        // Mapeamos los datos de la BD al formato interno
        SEDES = data.map(s => ({
            id: s.nombre, // Usamos el nombre como ID (ej: "Edificio Tulcán")
            label: `🏢 ${s.nombre}`,
            color: s.color || 'bg-blue-600'
        }));

        generarBotonesSedes();

    } catch (error) {
        console.error("Error cargando sedes:", error);
        document.getElementById('listaSedes').innerHTML = 
            '<p class="text-red-400 text-center">Error de conexión. No se pudieron cargar las sedes.</p>';
    }
}

function generarBotonesSedes() {
    const contenedor = document.getElementById('listaSedes');
    contenedor.innerHTML = ''; 

    if (SEDES.length === 0) {
        contenedor.innerHTML = '<p class="text-center text-gray-500">No hay sedes configuradas en el sistema.</p>';
        return;
    }

    SEDES.forEach(sede => {
        const btn = document.createElement('button');
        // Clases de Tailwind dinámicas
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
    if(confirm("¿Quieres cambiar la ubicación física de este dispositivo?")) {
        localStorage.removeItem('asistencia_sede');
        location.reload();
    }
}

function iniciarKiosco() {
    // Buscar nombre bonito para mostrar en pantalla
    const sedeInfo = SEDES.find(s => s.id === currentSede);
    const nombreMostrar = sedeInfo ? sedeInfo.label.replace('🏢 ', '') : currentSede;
    
    document.getElementById('lblSede').textContent = `📍 UBICACIÓN: ${nombreMostrar}`;
    iniciarEscanner();
}

// --- CONFIGURACIÓN DEL ESCÁNER ---
function iniciarEscanner() {
    // Usamos Html5Qrcode (versión core) para mayor control
    const html5QrCode = new Html5Qrcode("reader");
    
    const config = { fps: 10, qrbox: { width: 250, height: 250 } };
    
    // Preferir cámara trasera
    html5QrCode.start({ facingMode: "environment" }, config, onScanSuccess)
    .catch(err => {
        console.error("Error al iniciar cámara", err);
        mostrarMensaje('📷', 'ERROR CÁMARA', 'Verifique permisos/HTTPS', '', 'text-red-500');
    });
}

function onScanSuccess(decodedText, decodedResult) {
    if (!isScanning) return; // Bloqueo temporal para no saturar

    isScanning = false; 
    enviarAsistencia(decodedText);
}

// --- COMUNICACIÓN CON EL BACKEND ---
async function enviarAsistencia(cedulaQr) {
    mostrarMensaje("⏳", "PROCESANDO...", "Validando datos...", "", "text-white");

    try {
        const payload = {
            cedula: cedulaQr,
            sede: currentSede, // Enviamos la sede actual (ej: "Edificio Tulcán")
            tipo_manual: manualMode
        };

        const response = await fetch('../controllers/RegistrarAsistencia.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });

        // Parseo seguro del JSON
        let data;
        try {
            data = await response.json();
        } catch (e) {
            throw new Error("Respuesta inválida del servidor");
        }

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
        console.error(error);
        playAudio('error');
        mostrarMensaje('🔌', 'ERROR RED', 'Fallo de conexión', '', 'text-red-500');
    }

    // Reiniciar estado después de 3.5 segundos
    setTimeout(() => {
        ocultarMensaje();
        resetManualMode(); 
        isScanning = true;
    }, 3500);
}

// --- MODOS MANUALES (FORZAR) ---
function activarModoManual(tipo) {
    manualMode = tipo;
    const texto = document.getElementById('modoManualTexto');
    const indicador = document.getElementById('manualIndicator');
    
    texto.textContent = tipo;
    indicador.classList.remove('hidden');

    // Feedback visual en botones
    const btnEntrada = document.getElementById('btnEntrada');
    const btnSalida = document.getElementById('btnSalida');

    if(tipo === 'ENTRADA') {
        btnEntrada.classList.add('bg-green-800', 'text-white');
        btnSalida.classList.remove('bg-red-800', 'text-white');
    } else {
        btnSalida.classList.add('bg-red-800', 'text-white');
        btnEntrada.classList.remove('bg-green-800', 'text-white');
    }

    // Cancelar manual si no escanea en 10 segundos
    clearTimeout(manualTimeout);
    manualTimeout = setTimeout(resetManualMode, 10000);
}

function resetManualMode() {
    manualMode = null;
    const indicador = document.getElementById('manualIndicator');
    if(indicador) indicador.classList.add('hidden');
    
    document.getElementById('btnEntrada').classList.remove('bg-green-800', 'text-white');
    document.getElementById('btnSalida').classList.remove('bg-red-800', 'text-white');
}

// --- UTILS ---
function mostrarMensaje(icon, title, name, time, colorClass) {
    const msgDiv = document.getElementById('statusMessage');
    document.getElementById('statusIcon').textContent = icon;
    
    const titleEl = document.getElementById('statusTitle');
    titleEl.textContent = title;
    // Resetear clases y aplicar las nuevas
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
    if(audio) {
        audio.currentTime = 0;
        audio.play().catch(e => console.log("Audio bloqueado por navegador (interacción requerida)"));
    }
}