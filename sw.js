// Service Worker para CEU PWA
const CACHE_NAME = 'ceu-v1.0.1';
const urlsToCache = [
  '/CEU/',
  '/CEU/index.php',
  '/CEU/styleGlobal.css',
  '/CEU/PaginasPublicas/ContainerPublico.php',
  '/CEU/PaginasParticipante/ContainerParticipante.php',
  '/CEU/PaginasOrganizador/ContainerOrganizador.php',
  '/CEU/Imagens/CEU-Logo-1x1.png',
  '/CEU/Imagens/CEU-Logo.png',
  // Adicione outros recursos críticos aqui
];

// Instalação do Service Worker
self.addEventListener('install', (event) => {
  // Atualiza imediatamente versões antigas
  self.skipWaiting();
  event.waitUntil(
    caches.open(CACHE_NAME)
      .then((cache) => {
        console.log('Cache aberto');
        return cache.addAll(urlsToCache);
      })
  );
});

// Interceptação de requisições
self.addEventListener('fetch', (event) => {
  event.respondWith(
    caches.match(event.request)
      .then((response) => {
        // Retorna do cache se disponível, senão busca na rede
        if (response) {
          return response;
        }
        return fetch(event.request);
      }
    )
  );
});

// Atualização do Service Worker
self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys().then((cacheNames) => {
      return Promise.all([
        // Limpa caches antigos
        ...cacheNames.map((cacheName) => {
          if (cacheName !== CACHE_NAME) {
            return caches.delete(cacheName);
          }
        }),
        // Assume controle imediatamente
        self.clients.claim()
      ]);
    })
  );
});