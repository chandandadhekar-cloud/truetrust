/* TrueTrust service worker: offline fallback, fast static assets, cached photos. */
const VERSION = 'tt-v1';
const STATIC = VERSION + '-static';
const PHOTOS = VERSION + '-photos';
const OFFLINE = '/offline';

self.addEventListener('install', (event) => {
  event.waitUntil(
    caches.open(STATIC).then((c) => c.addAll([OFFLINE, '/assets/img/icon-192.png', '/assets/img/icon.svg'])).then(() => self.skipWaiting())
  );
});

self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys().then((keys) => Promise.all(keys.filter((k) => !k.startsWith(VERSION)).map((k) => caches.delete(k)))).then(() => self.clients.claim())
  );
});

async function trimPhotos() {
  const cache = await caches.open(PHOTOS);
  const keys = await cache.keys();
  if (keys.length > 250) {
    await Promise.all(keys.slice(0, keys.length - 250).map((k) => cache.delete(k)));
  }
}

self.addEventListener('fetch', (event) => {
  const req = event.request;
  if (req.method !== 'GET') return;
  const url = new URL(req.url);

  // Google Fonts: cache first.
  if (url.hostname === 'fonts.googleapis.com' || url.hostname === 'fonts.gstatic.com') {
    event.respondWith(caches.open(STATIC).then(async (c) => {
      const hit = await c.match(req);
      if (hit) return hit;
      const res = await fetch(req);
      if (res.ok || res.type === 'opaque') c.put(req, res.clone());
      return res;
    }));
    return;
  }
  if (url.origin !== location.origin) return;

  // Never cache API calls or admin pages.
  if (url.pathname.startsWith('/api/') || url.pathname.startsWith('/admin')) return;

  // Photos are immutable per id: cache first.
  if (url.pathname.startsWith('/photo/')) {
    event.respondWith(caches.open(PHOTOS).then(async (c) => {
      const hit = await c.match(req);
      if (hit) return hit;
      const res = await fetch(req);
      if (res.ok) { c.put(req, res.clone()); trimPhotos(); }
      return res;
    }));
    return;
  }

  // Versioned static assets: stale while revalidate.
  if (url.pathname.startsWith('/assets/')) {
    event.respondWith(caches.open(STATIC).then(async (c) => {
      const hit = await c.match(req);
      const net = fetch(req).then((res) => { if (res.ok) c.put(req, res.clone()); return res; }).catch(() => hit);
      return hit || net;
    }));
    return;
  }

  // Pages: network first, offline page as fallback.
  if (req.mode === 'navigate') {
    event.respondWith(fetch(req).catch(() => caches.match(OFFLINE)));
  }
});
