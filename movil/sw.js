// Service worker mínimo: solo existe para que el navegador considere la
// app "instalable" (PWA). No cachea datos de negocio (pedidos, precios,
// etc.) para evitar mostrar información desactualizada o filtrar datos
// fuera de sesión.
const CACHE = 'brawmotors-movil-v5';
const SHELL = [
  './index.php',
  './autolavado.php',
  './movil.css?v=5',
  './manifest.json',
  './icons/icon-192.png',
  './icons/icon-512.png',
];

self.addEventListener('install', (e) => {
  e.waitUntil(caches.open(CACHE).then((c) => c.addAll(SHELL)).catch(() => {}));
  self.skipWaiting();
});

self.addEventListener('activate', (e) => {
  e.waitUntil(
    caches.keys().then((keys) =>
      Promise.all(keys.filter((k) => k !== CACHE).map((k) => caches.delete(k)))
    )
  );
  self.clients.claim();
});

// Network-first para todo: la app siempre intenta datos frescos;
// solo cae al cache (el "shell" visual) si no hay red.
self.addEventListener('fetch', (e) => {
  if (e.request.method !== 'GET') return;
  e.respondWith(
    fetch(e.request).catch(() => caches.match(e.request))
  );
});
