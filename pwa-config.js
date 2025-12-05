// Configuração PWA para CEU
(function(){
  // Manifest switching util
  function setManifestHref(href){
    try {
      let link = document.querySelector('link[rel="manifest"]');
      if (!link) {
        link = document.createElement('link');
        link.rel = 'manifest';
        document.head.appendChild(link);
      }
      const prev = link.getAttribute('href');
      if (prev !== href) {
        link.setAttribute('href', href);
      }
    } catch (e) { console.warn('Não foi possível ajustar o manifest:', e); }
  }
  const DEFAULT_MANIFEST = '/CEU/manifest.json';
  const INSTALL_MANIFEST = '/CEU/manifest-install.json';
  let installManifestEnabled = false;
  window.ceuSetInstallManifest = function(enable){
    installManifestEnabled = !!enable;
    setManifestHref(enable ? INSTALL_MANIFEST : DEFAULT_MANIFEST);
  };

  // Registrar Service Worker (o mais cedo possível, sem esperar o load)
  if ('serviceWorker' in navigator) {
    try {
      const setupRegistrationListeners = function(registration){
        try {
          function notifyUpdate() {
            try { window.dispatchEvent(new Event('ceu:update-available')); } catch(_){ }
          }
          if (registration.waiting) notifyUpdate();
          registration.addEventListener('updatefound', function(){
            const installing = registration.installing;
            if (!installing) return;
            installing.addEventListener('statechange', function(){
              if (installing.state === 'installed' && navigator.serviceWorker.controller) {
                notifyUpdate();
              }
            });
          });
          // Checagem periódica (opcional): 1x por hora
          setInterval(function(){ registration.update().catch(()=>{}); }, 60*60*1000);
        } catch(_){ }
      };

      navigator.serviceWorker.getRegistration().then(function(existing){
        if (existing) {
          setupRegistrationListeners(existing);
        } else {
          navigator.serviceWorker.register('/CEU/sw.js')
            .then(function(registration){
              setupRegistrationListeners(registration);
            })
            .catch(function(error){
              console.error('Falha no registro do SW:', error);
            });
        }
      });
    } catch (e) {
      console.error('Falha ao inicializar Service Worker:', e);
    }
  }

  // Prompt de instalação
  let deferredPrompt;
  window.addEventListener('beforeinstallprompt', (e) => {
    // Previne o prompt automático
    e.preventDefault();
    deferredPrompt = e;
    window.deferredPrompt = e; // expoe para testes
    // Notifica telas interessadas que a instalação ficou disponível
    try {
      window.dispatchEvent(new CustomEvent('ceu:install-available'));
    } catch (_) {}
  });

  // Detectar quando app foi instalado
  window.addEventListener('appinstalled', (evt) => {
    // Limpa o prompt guardado
    deferredPrompt = null;
    window.deferredPrompt = null;
  });

  // Ajuste básico de viewport em mobile
  function adjustViewport() {
    if (window.innerWidth <= 768) {
      let viewport = document.querySelector('meta[name=viewport]');
      if (viewport) {
        viewport.setAttribute('content','width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover');
      }
    }
  }
  window.addEventListener('resize', adjustViewport);
  document.addEventListener('DOMContentLoaded', adjustViewport);

  // Helpers globais
  function isStandalone() {
    return (window.matchMedia && window.matchMedia('(display-mode: standalone)').matches) ||
           (window.navigator && window.navigator.standalone === true);
  }
  function isMobileUA() {
    return /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
  }
  // expõe utilidades para outras páginas
  window.ceuIsStandalone = isStandalone;
  window.ceuIsMobile = isMobileUA;
  window.ceuCanInstall = function(){
    return !!window.deferredPrompt && isMobileUA() && !isStandalone();
  };
  window.ceuTriggerInstall = async function(){
    if (!window.deferredPrompt) return { ok:false, reason:'no-prompt' };
    try {
      window.deferredPrompt.prompt();
      const choice = await window.deferredPrompt.userChoice;
      // Após interação, zera o prompt (o browser não permite reutilizar)
      window.deferredPrompt = null;
      return { ok:true, choice };
    } catch (err) {
      return { ok:false, error: String(err) };
    } finally {
      try { window.dispatchEvent(new Event('ceu:install-checked')); } catch(_){}
    }
  };

  // Roteamento: habilitar manifest instalável SOMENTE em Configurações (mobile)
  function isConfigPage(){
    try {
      const params = new URLSearchParams(location.search);
      const pagina = params.get('pagina');
      return pagina === 'configuracoes';
    } catch (_) { return false; }
  }

  function updateInstallManifestForRoute(){
    const shouldEnable = isConfigPage() && isMobileUA() && !isStandalone();
    window.ceuSetInstallManifest(shouldEnable);
  }

  // Dispara quando a rota muda
  const _pushState = history.pushState;
  history.pushState = function(){
    const ret = _pushState.apply(this, arguments);
    try { window.dispatchEvent(new Event('ceu:locationchange')); } catch(_){ }
    return ret;
  };
  window.addEventListener('popstate', () => {
    try { window.dispatchEvent(new Event('ceu:locationchange')); } catch(_){ }
  });
  window.addEventListener('ceu:locationchange', updateInstallManifestForRoute);
  window.addEventListener('load', updateInstallManifestForRoute);
  // Checagens adicionais em caso de carregamentos dinâmicos
  setTimeout(updateInstallManifestForRoute, 300);
  setTimeout(updateInstallManifestForRoute, 1500);
  // Garante que o manifest correto seja aplicado o quanto antes
  try { updateInstallManifestForRoute(); } catch(_){ }
})();