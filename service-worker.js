// Archivo: service-worker.js
const CACHE_NAME = 'cge-asistencia-v1';

// Lista de archivos vitales para el Kiosco y el Admin
const ASSETS_TO_CACHE = [
  './',
  './public/index.php',
  './admin/login.php',
  
  // Scripts propios
  './public/assets/js/kiosco.js',
  './admin/assets/js/admin.js',
  './admin/assets/js/usuarios.js',
  './admin/assets/js/reportes.js',

  // Librerías Externas (CDNs)
  'https://cdn.tailwindcss.com',
  'https://unpkg.com/html5-qrcode',
  'https://unpkg.com/@phosphor-icons/web',
  'https://cdn.jsdelivr.net/npm/chart.js',
  
  // Audios (Opcional: Si quieres que suenen offline, descárgalos localmente mejor)
  'https://assets.mixkit.co/active_storage/sfx/2578/2578-preview.m4a',
  'https://assets.mixkit.co/active_storage/sfx/2572/2572-preview.m4a'
];

// 1. INSTALACIÓN
self.addEventListener('install', (e) => {
  e.waitUntil(
    caches.open(CACHE_NAME).then((cache) => {
      return cache.addAll(ASSETS_TO_CACHE);
    })
  );
});

// 2. ACTIVACIÓN (Limpieza de cachés viejas)
self.addEventListener('activate', (e) => {
  e.waitUntil(
    caches.keys().then((keyList) => {
      return Promise.all(
        keyList.map((key) => {
          if (key !== CACHE_NAME) {
            return caches.delete(key);
          }
        })
      );
    })
  );
});

// 3. FETCH (Estrategia: Network First, fallback to Cache)
// Intentamos ir a internet primero (para registrar asistencia real), si falla, usamos caché.
self.addEventListener('fetch', (e) => {
  e.respondWith(
    fetch(e.request)
      .then((res) => {
        return res;
      })
      .catch(() => {
        return caches.match(e.request);
      })
  );
});