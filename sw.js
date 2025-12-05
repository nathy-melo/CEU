// Service Worker para CEU PWA
const CACHE_NAME = 'ceu-v1.0.0';
const urlsToCache = [
  '/CEU/',
  '/CEU/index.php',
  '/CEU/PaginasPublicas/ContainerPublico.php',
  '/CEU/PaginasParticipante/ContainerParticipante.php',
  '/CEU/PaginasOrganizador/ContainerOrganizador.php',
  '/CEU/Imagens/CEU-Logo-1x1.png',
  '/CEU/Imagens/CEU-Logo.png',
];

// Utilidades de cache
async function networkFirst(request) {
  const cache = await caches.open(CACHE_NAME);
  try {
    const fresh = await fetch(request, { cache: 'no-store' });
    cache.put(request, fresh.clone());
    return fresh;
  } catch (_) {
    const cached = await cache.match(request);
    if (cached) return cached;
    return fetch(request);
  }
}

async function cacheFirst(request) {
  const cached = await caches.match(request);
  if (cached) return cached;
  const response = await fetch(request);
  const cache = await caches.open(CACHE_NAME);
  cache.put(request, response.clone());
  return response;
}

// Instalação do Service Worker
self.addEventListener('install', (event) => {
  // Atualiza imediatamente versões antigas
  self.skipWaiting();
  event.waitUntil(
    caches.open(CACHE_NAME)
      .then((cache) => cache.addAll(urlsToCache))
  );
});

// Interceptação de requisições
self.addEventListener('fetch', (event) => {
  const { request } = event;
  if (request.method !== 'GET') return; // não intercepta POST/PUT etc.

  const url = new URL(request.url);

  // Estratégia: CSS sempre network-first (corrige F5 com CSS desatualizado)
  if (request.destination === 'style' || url.pathname.endsWith('.css')) {
    event.respondWith(networkFirst(request));
    return;
  }

  // HTML também network-first para refletir atualizações rápidas do app
  if (request.destination === 'document' || url.pathname.endsWith('.php') || url.pathname.endsWith('.html')) {
    event.respondWith(networkFirst(request));
    return;
  }

  // Imagens, fontes e outros: cache-first
  event.respondWith(cacheFirst(request));
});

// Atualização do Service Worker
self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys().then((cacheNames) => Promise.all([
      // Limpa caches antigos
      ...cacheNames.map((cacheName) => {
        if (cacheName !== CACHE_NAME) {
          return caches.delete(cacheName);
        }
      }),
      self.clients.claim()
    ]))
  );
});