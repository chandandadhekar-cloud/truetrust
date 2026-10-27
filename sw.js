/* TrueTrust service worker: offline fallback, fast static assets, cached photos. */
const VERSION = 'tt-v3';
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

/* ---------- Push notifications ---------- */
self.addEventListener('push', (event) => {
  event.waitUntil((async () => {
    let items = [];
    try {
      const res = await fetch('/api/push/pending', { credentials: 'include', headers: { 'Accept': 'application/json' } });
      if (res.ok) items = (await res.json()).items || [];
    } catch (e) { /* offline or signed out: fall through to a generic notice */ }

    const base = { icon: '/assets/img/icon-192.png', badge: '/assets/img/badge-96.png' };
    if (!items.length) {
      return self.registration.showNotification('TrueTrust', { ...base, body: 'You have new activity.', tag: 'activity', data: { url: '/matches' } });
    }
    // One notification per destination (e.g. per chat), newest wins.
    const seen = new Set();
    const shown = [];
    for (const n of items) {
      if (seen.has(n.url) || shown.length >= 3) continue;
      seen.add(n.url);
      shown.push(self.registration.showNotification(n.title, { ...base, body: n.body, tag: n.url, renotify: true, data: { url: n.url } }));
    }
    return Promise.all(shown);
  })());
});

self.addEventListener('notificationclick', (event) => {
  event.notification.close();
  const url = new URL((event.notification.data && event.notification.data.url) || '/discover', self.location.origin).href;
  event.waitUntil((async () => {
    const wins = await self.clients.matchAll({ type: 'window', includeUncontrolled: true });
    for (const w of wins) {
      if (new URL(w.url).origin === self.location.origin) {
        await w.focus();
        return w.navigate(url);
      }
    }
    return self.clients.openWindow(url);
  })());
});
