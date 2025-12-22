// admin/assets/js/reportes.js

let chartBarras = null;
let chartPie = null;

document.addEventListener('DOMContentLoaded', () => {
    // 1. Cargar gráficos iniciales
    cargarEstadisticas();

    // 2. Escuchar cambios en el filtro de Sede
    const filtroSede = document.getElementById('filtroSedeReporte'); 
    if (filtroSede) {
        filtroSede.addEventListener('change', () => {
            const sede = filtroSede.value;
            cargarEstadisticas(sede);
        });
    }
});

async function cargarEstadisticas(sede = '') {
    try {
        const url = `../controllers/ObtenerEstadisticas.php?sede=${encodeURIComponent(sede)}`;
        const response = await fetch(url);
        const data = await response.json();

        if (data.error) {
            console.error(data.error);
            return;
        }

        // Actualizar SOLO el Total (Los otros ya no existen en el HTML)
        if (data.resumen) {
            const elTotal = document.getElementById('statTotal');
            if(elTotal) elTotal.textContent = data.resumen.total || 0;
        }

        // Renderizar Gráficos
        renderizarGraficoBarras(data.asistencia_semanal);
        renderizarGraficoPie(data.distribucion_estado);

    } catch (error) {
        console.error("Error cargando estadísticas", error);
    }
}

function renderizarGraficoBarras(datos) {
    const ctx = document.getElementById('chartAsistenciaSemanal').getContext('2d');
    
    // Preparar datos
    const etiquetas = datos.map(d => formatearFecha(d.fecha));
    const valores = datos.map(d => d.total);

    if (chartBarras) {
        chartBarras.destroy();
    }

    chartBarras = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: etiquetas,
            datasets: [{
                label: 'Asistencias',
                data: valores,
                backgroundColor: 'rgba(59, 130, 246, 0.7)',
                borderColor: 'rgba(59, 130, 246, 1)',
                borderWidth: 1,
                borderRadius: 6, // Bordes redondeados en las barras
                barPercentage: 0.6 // Barras un poco más delgadas
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: { 
                    beginAtZero: true, 
                    ticks: { stepSize: 1 },
                    grid: { color: '#f3f4f6' } // Líneas de guía sutiles
                },
                x: {
                    grid: { display: false }
                }
            },
            plugins: {
                legend: { display: false } // Ocultamos leyenda porque ya sabemos qué es
            }
        }
    });
}

function renderizarGraficoPie(datos) {
    const ctx = document.getElementById('chartDistribucion').getContext('2d');

    // Mapear datos (DENTRO/ENTRADA vs FUERA/SALIDA)
    let dentro = 0;
    let fuera = 0;

    datos.forEach(d => {
        const estado = d.estado.toUpperCase(); 
        if (estado === 'DENTRO' || estado === 'ENTRADA') dentro += parseInt(d.total);
        else fuera += parseInt(d.total);
    });

    if (chartPie) {
        chartPie.destroy();
    }

    chartPie = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: ['Presentes', 'Fuera'],
            datasets: [{
                data: [dentro, fuera],
                backgroundColor: [
                    'rgba(34, 197, 94, 0.7)', // Verde
                    'rgba(229, 231, 235, 0.7)'  // Gris (para "fuera" se ve mejor)
                ],
                borderColor: [
                    'rgba(34, 197, 94, 1)',
                    'rgba(209, 213, 219, 1)'
                ],
                borderWidth: 2,
                hoverOffset: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'bottom' }
            },
            cutout: '70%' // Hace el anillo más fino
        }
    });
}

function formatearFecha(fechaString) {
    const dias = ['Dom', 'Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb'];
    const fecha = new Date(fechaString + 'T00:00:00'); 
    return `${dias[fecha.getDay()]} ${fecha.getDate()}`;
}