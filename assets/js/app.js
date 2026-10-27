/* TrueTrust core: API helper, toasts, sheets, badges, safety actions, PWA install. */
(function () {
  'use strict';

  var TT = window.TT = window.TT || {};
  var csrf = (document.querySelector('meta[name="csrf-token"]') || {}).content || '';

  TT.esc = function (s) {
    return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) {
      return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
    });
  };

  /** fetch wrapper: JSON in/out, CSRF header, friendly errors. */
  TT.api = function (url, opts) {
    opts = opts || {};
    var init = {
      method: opts.method || (opts.body ? 'POST' : 'GET'),
      headers: { 'X-CSRF-Token': csrf, 'X-Requested-With': 'fetch', 'Accept': 'application/json' },
      credentials: 'same-origin'
    };
    if (opts.body instanceof FormData) {
      init.body = opts.body;
    } else if (opts.body) {
      init.headers['Content-Type'] = 'application/json';
      init.body = JSON.stringify(opts.body);
    }
    return fetch(url, init).then(function (res) {
      return res.json().catch(function () { return {}; }).then(function (data) {
        if (!res.ok || data.ok === false) {
          var err = new Error(data.error || (res.status === 419 ? 'Your session expired. Refresh the page.' : 'Something went wrong. Please try again.'));
          err.status = res.status;
          if (res.status === 401) { setTimeout(function () { location.href = '/login'; }, 900); }
          throw err;
        }
        return data;
      });
    }, function () {
      throw new Error("You're offline. Check your connection and try again.");
    });
  };

  /* ---------- Icons used from JS ---------- */
  var ICONS = {
    check: '<path d="M20 6 9 17l-5-5"/>',
    alert: '<path d="M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0zM12 9v4M12 17h.01"/>',
    info: '<circle cx="12" cy="12" r="10"/><path d="M12 16v-4M12 8h.01"/>',
    x: '<path d="M18 6 6 18M6 6l12 12"/>',
    heart: '<path d="M20.8 4.6a5.5 5.5 0 0 0-7.8 0L12 5.7l-1-1.1a5.5 5.5 0 0 0-7.8 7.8l1 1.1L12 21l7.8-7.5 1-1.1a5.5 5.5 0 0 0 0-7.8z"/>',
    star: '<path d="m12 2.5 2.9 6 6.6.9-4.8 4.6 1.2 6.5L12 17.4l-5.9 3.1 1.2-6.5L2.5 9.4l6.6-.9z"/>',
    pin: '<path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0z"/><circle cx="12" cy="10" r="3"/>',
    briefcase: '<rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/>',
    cap: '<path d="M22 10 12 5 2 10l10 5 10-5z"/><path d="M6 12v5c3 2 9 2 12 0v-5"/>',
    up: '<path d="m18 15-6-6-6 6"/>',
    zap: '<path d="M13 2 3 14h9l-1 8 10-12h-9z"/>',
    flag: '<path d="M4 15s1-1 4-1 5 2 8 2 4-1 4-1V3s-1 1-4 1-5-2-8-2-4 1-4 1zM4 22v-7"/>',
    block: '<circle cx="12" cy="12" r="10"/><path d="m4.9 4.9 14.2 14.2"/>',
    ruler: '<path d="M12 3v18M8 7l4-4 4 4M8 17l4 4 4-4"/>',
    target: '<circle cx="12" cy="12" r="10"/><circle cx="12" cy="12" r="6"/><circle cx="12" cy="12" r="2"/>',
    sparkle: '<path d="M12 3l1.9 5.1L19 10l-5.1 1.9L12 17l-1.9-5.1L5 10l5.1-1.9z"/>',
    leaf: '<path d="M11 20A7 7 0 0 1 9.8 6.1C15.5 5 17 4.5 19 2c1 2 2 4.2 2 8 0 5.5-4.8 10-10 10z"/><path d="M2 21c0-3 1.9-5.4 5.1-6"/>',
    glass: '<path d="M8 22h8M12 15v7M6 3h12l-1 6a5 5 0 0 1-10 0z"/>',
    smoke: '<path d="M2 16h15v4H2zM19 16v4M22 16v4M18 12c0-2-2-2-2-4s2-2 2-4"/>',
    globe: '<circle cx="12" cy="12" r="10"/><path d="M2 12h20M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/>'
  };
  TT.icon = function (name, size, cls) {
    size = size || 20;
    return '<svg class="icon ' + (cls || '') + '" width="' + size + '" height="' + size + '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' + (ICONS[name] || ICONS.info) + '</svg>';
  };
  TT.verified = function (size) {
    size = size || 20;
    return '<svg class="icon icon-verified" width="' + size + '" height="' + size + '" viewBox="0 0 24 24" aria-label="Verified"><path fill="currentColor" d="M12 1.5l2.6 1.9 3.2-.1 1 3 2.6 1.9-1 3.1 1 3.1-2.6 1.9-1 3-3.2-.1L12 22.5l-2.6-1.9-3.2.1-1-3-2.6-1.9 1-3.1-1-3.1 2.6-1.9 1-3 3.2.1z"/><path d="M8 12.2l2.6 2.6L16.2 9" fill="none" stroke="#fff" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/></svg>';
  };

  /* ---------- Toasts ---------- */
  TT.toast = function (message, type) {
    var box = document.getElementById('toasts');
    if (!box || !message) return;
    var el = document.createElement('div');
    el.className = 'toast is-' + (type || 'info');
    el.innerHTML = TT.icon(type === 'error' ? 'alert' : type === 'success' ? 'check' : 'info', 20) + '<span>' + TT.esc(message) + '</span>';
    box.appendChild(el);
    setTimeout(function () {
      el.classList.add('is-leaving');
      setTimeout(function () { el.remove(); }, 260);
    }, type === 'error' ? 4800 : 3200);
  };

  /* ---------- Bottom sheets ---------- */
  var openSheets = [];
  TT.sheet = function (content, opts) {
    opts = opts || {};
    var backdrop = document.createElement('div');
    backdrop.className = 'sheet-backdrop';
    var sheet = document.createElement('div');
    sheet.className = 'sheet' + (opts.full ? ' sheet-full' : '');
    sheet.setAttribute('role', 'dialog');
    sheet.setAttribute('aria-modal', 'true');
    if (typeof content === 'string' && content.charAt(0) === '#') {
      var tpl = document.querySelector(content);
      sheet.appendChild(tpl.content.cloneNode(true));
    } else if (typeof content === 'string') {
      sheet.innerHTML = content;
    } else {
      sheet.appendChild(content);
    }
    document.body.appendChild(backdrop);
    document.body.appendChild(sheet);
    document.body.style.overflow = 'hidden';

    var api = {
      el: sheet,
      close: function () {
        sheet.classList.remove('is-open');
        backdrop.classList.remove('is-open');
        openSheets = openSheets.filter(function (s) { return s !== api; });
        if (!openSheets.length) document.body.style.overflow = '';
        setTimeout(function () { sheet.remove(); backdrop.remove(); }, 340);
        if (opts.onClose) opts.onClose();
      }
    };
    openSheets.push(api);
    backdrop.addEventListener('click', api.close);
    sheet.addEventListener('click', function (e) {
      if (e.target.closest('[data-sheet-close]')) api.close();
    });
    requestAnimationFrame(function () {
      requestAnimationFrame(function () { sheet.classList.add('is-open'); backdrop.classList.add('is-open'); });
    });
    if (opts.onOpen) opts.onOpen(sheet, api);
    return api;
  };
  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape' && openSheets.length) openSheets[openSheets.length - 1].close();
  });

  TT.confirm = function (message, okLabel, danger) {
    return new Promise(function (resolve) {
      var done = false;
      var s = TT.sheet(
        '<div class="sheet-grip"></div><h2 style="font-size:22px;margin-bottom:18px">' + TT.esc(message) + '</h2>' +
        '<div style="display:grid;gap:10px"><button class="btn ' + (danger === false ? 'btn-primary' : 'btn-danger') + ' btn-block" data-ok>' + TT.esc(okLabel || 'Yes, continue') + '</button>' +
        '<button class="btn btn-ghost btn-block" data-sheet-close>Cancel</button></div>',
        { onClose: function () { if (!done) resolve(false); } }
      );
      s.el.querySelector('[data-ok]').addEventListener('click', function () { done = true; resolve(true); s.close(); });
    });
  };

  /* ---------- Forms ---------- */
  document.addEventListener('submit', function (e) {
    var form = e.target;
    if (form.hasAttribute('data-confirm') && !form._confirmed) {
      e.preventDefault();
      var submitter = e.submitter;
      TT.confirm(form.getAttribute('data-confirm')).then(function (ok) {
        if (!ok) return;
        form._confirmed = true;
        // Keep the clicked button's name/value (e.g. action=ban), which form.submit() would drop.
        if (submitter && submitter.name) {
          var keep = document.createElement('input');
          keep.type = 'hidden';
          keep.name = submitter.name;
          keep.value = submitter.value;
          form.appendChild(keep);
        }
        form.submit();
      });
      return;
    }
    if (form.hasAttribute('data-submit-once')) {
      var btns = form.querySelectorAll('button[type=submit], button:not([type])');
      setTimeout(function () { btns.forEach(function (b) { b.classList.add('is-loading'); }); }, 0);
    }
  }, true);

  // Back buttons: return to the previous in-app page when there is one.
  document.addEventListener('click', function (e) {
    var back = e.target.closest('[data-back]');
    if (back && document.referrer && new URL(document.referrer).origin === location.origin && history.length > 1) {
      e.preventDefault();
      history.back();
    }
    if (e.target.closest('[data-reload]')) location.reload();
  });

  document.addEventListener('change', function (e) {
    var form = e.target.closest('form[data-autosubmit]');
    if (form) form.submit();
  });

  document.addEventListener('click', function (e) {
    var t = e.target.closest('[data-toggle-password]');
    if (!t) return;
    var input = t.parentNode.querySelector('input');
    input.type = input.type === 'password' ? 'text' : 'password';
  });

  /* ---------- Gallery dots (profile pages) ---------- */
  TT.bindGallery = function (root) {
    (root || document).querySelectorAll('[data-gallery]').forEach(function (track) {
      var dots = track.parentNode.querySelectorAll('[data-gallery-dots] i');
      if (!dots.length) return;
      track.addEventListener('scroll', function () {
        var i = Math.round(track.scrollLeft / track.clientWidth);
        dots.forEach(function (d, j) { d.classList.toggle('on', i === j); });
      }, { passive: true });
    });
  };
  TT.bindGallery();

  /* ---------- Match celebration ---------- */
  TT.matchOverlay = function (m) {
    var face = function (src, name) {
      return '<div class="mf">' + (src ? '<img src="' + TT.esc(src) + '" alt="">' : TT.esc((name || '?').charAt(0))) + '</div>';
    };
    var el = document.createElement('div');
    el.className = 'match-overlay';
    el.innerHTML = '<div class="confetti"></div>' +
      '<div class="match-title">It\'s a match</div>' +
      '<p class="match-sub">You and ' + TT.esc(m.name) + ' like each other.</p>' +
      '<div class="match-faces">' + face(m.myPhoto, m.myName) + face(m.photo, m.name) + '</div>' +
      '<a class="btn btn-primary" href="/chat/' + m.id + '">Send ' + TT.esc(m.name) + ' a message</a>' +
      '<button class="btn btn-ghost" data-close>Keep swiping</button>' +
      (TT.push && TT.push.state() === 'off' ? '<button class="btn btn-ghost btn-sm" data-push-enable style="margin-top:4px;opacity:.75">Get notified when ' + TT.esc(m.name) + ' replies</button>' : '');
    document.body.appendChild(el);
    var confetti = el.querySelector('.confetti');
    var colors = ['#E0435E', '#F2A33A', '#FFFFFF', '#FF8095', '#2F7DF6'];
    for (var i = 0; i < 60; i++) {
      var c = document.createElement('i');
      c.style.left = Math.random() * 100 + '%';
      c.style.background = colors[i % colors.length];
      c.style.animationDuration = (2.2 + Math.random() * 2.2) + 's';
      c.style.animationDelay = (Math.random() * .6) + 's';
      c.style.transform = 'rotate(' + (Math.random() * 360) + 'deg)';
      confetti.appendChild(c);
    }
    requestAnimationFrame(function () { requestAnimationFrame(function () { el.classList.add('is-open'); }); });
    if (navigator.vibrate) navigator.vibrate([30, 60, 30]);
    el.querySelector('[data-close]').addEventListener('click', function () {
      el.classList.remove('is-open');
      setTimeout(function () { el.remove(); }, 300);
    });
    TT.refreshBadges();
  };

  /* ---------- Report & block ---------- */
  function targetOf(btn) {
    var holder = btn.closest('[data-profile]');
    return {
      id: btn.getAttribute('data-id') || (holder && holder.getAttribute('data-profile')),
      name: btn.getAttribute('data-name') || (holder && holder.getAttribute('data-name')) || 'this person'
    };
  }
  function afterBlock() {
    document.dispatchEvent(new CustomEvent('tt:removed'));
    if (!document.getElementById('deck')) setTimeout(function () { location.href = '/matches'; }, 900);
  }
  TT.report = function (id, name) {
    openSheets.slice().forEach(function (s) { s.close(); });
    TT.sheet('#tpl-report', {
      onOpen: function (el, s) {
        el.querySelector('[data-report-name]').textContent = name;
        var form = el.querySelector('[data-report-form]');
        form.elements.id.value = id;
        form.addEventListener('submit', function (e) {
          e.preventDefault();
          var btn = form.querySelector('button[type=submit]');
          btn.classList.add('is-loading');
          TT.api('/api/report', { body: { id: id, reason: (form.querySelector('input[name=reason]:checked') || {}).value, details: form.elements.details.value } })
            .then(function (r) { s.close(); TT.toast(r.message, 'success'); afterBlock(); })
            .catch(function (err) { btn.classList.remove('is-loading'); TT.toast(err.message, 'error'); });
        });
      }
    });
  };
  TT.block = function (id, name) {
    TT.confirm('Block ' + name + '? You won\'t see each other again.', 'Block').then(function (ok) {
      if (!ok) return;
      openSheets.slice().forEach(function (s) { s.close(); });
      TT.api('/api/block', { body: { id: id } })
        .then(function (r) { TT.toast(r.message, 'success'); afterBlock(); })
        .catch(function (err) { TT.toast(err.message, 'error'); });
    });
  };
  document.addEventListener('click', function (e) {
    var r = e.target.closest('[data-report]');
    if (r) { var t = targetOf(r); if (t.id) TT.report(t.id, t.name); return; }
    var b = e.target.closest('[data-block]');
    if (b) { var t2 = targetOf(b); if (t2.id) TT.block(t2.id, t2.name); }
  });

  /* ---------- Nav badges ---------- */
  TT.refreshBadges = function () {
    if (!document.querySelector('[data-badge]')) return;
    TT.api('/api/badges').then(function (b) {
      setBadge('badge-likes', b.likes);
      setBadge('badge-chats', b.unread, b.matches > 0);
    }).catch(function () {});
  };
  function setBadge(name, count, dotOnly) {
    document.querySelectorAll('[data-badge="' + name + '"]').forEach(function (el) {
      if (count > 0) { el.textContent = count > 99 ? '99+' : count; el.classList.remove('hidden'); }
      else if (dotOnly) { el.textContent = ''; el.classList.remove('hidden'); }
      else el.classList.add('hidden');
    });
  }
  if (document.querySelector('[data-badge]')) {
    TT.refreshBadges();
    setInterval(function () { if (!document.hidden) TT.refreshBadges(); }, 25000);
    document.addEventListener('visibilitychange', function () { if (!document.hidden) TT.refreshBadges(); });
  }

  /* ---------- Flash ---------- */
  var flash = document.getElementById('flash-toast');
  if (flash) {
    try { var f = JSON.parse(flash.textContent); TT.toast(f.message, f.type); } catch (e) {}
  }

  /* ---------- PWA ---------- */
  if ('serviceWorker' in navigator) {
    window.addEventListener('load', function () { navigator.serviceWorker.register('/sw.js').catch(function () {}); });
  }

  var deferred = null;
  var standalone = window.matchMedia('(display-mode: standalone)').matches || navigator.standalone === true;
  var ua = navigator.userAgent;
  var isIOS = /iphone|ipad|ipod/i.test(ua) || (navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1);
  var store = {
    get: function (k) { try { return localStorage.getItem(k); } catch (e) { return null; } },
    set: function (k, v) { try { localStorage.setItem(k, v); } catch (e) {} }
  };

  window.addEventListener('beforeinstallprompt', function (e) {
    e.preventDefault();
    deferred = e;
    maybePrompt();
  });
  window.addEventListener('appinstalled', function () {
    store.set('tt_installed', '1');
    TT.toast('Installed. Find it on your home screen.', 'success');
  });

  function showInstall(manual) {
    if (standalone) { if (manual) TT.toast("You're already using the app.", 'info'); return; }
    TT.sheet('#tpl-install', {
      onOpen: function (el, s) {
        var native = el.querySelector('[data-install="native"]');
        var ios = el.querySelector('[data-install="ios"]');
        if (!deferred) {
          native.classList.add('hidden');
          if (isIOS) ios.classList.remove('hidden');
          else {
            ios.classList.remove('hidden');
            ios.innerHTML = '<div class="install-steps"><div><b>1</b><span>Open your browser menu (⋮)</span></div><div><b>2</b><span>Tap <strong>Install app</strong> or <strong>Add to Home screen</strong></span></div></div>';
          }
        }
        var go = el.querySelector('[data-install-go]');
        if (go) go.addEventListener('click', function () {
          if (!deferred) return;
          deferred.prompt();
          deferred.userChoice.then(function () { deferred = null; s.close(); });
        });
        el.querySelector('[data-install-later]').addEventListener('click', function () {
          store.set('tt_install_dismissed', String(Date.now()));
          s.close();
        });
      }
    });
  }
  TT.showInstall = showInstall;
  document.addEventListener('click', function (e) {
    if (e.target.closest('[data-install-open]')) showInstall(true);
  });

  /* ---------- Push notifications ---------- */
  var vapidMeta = document.querySelector('meta[name="vapid-key"]');
  var vapid = vapidMeta ? vapidMeta.content : '';
  var pushSupported = !!(vapid && 'serviceWorker' in navigator && 'PushManager' in window && 'Notification' in window);

  function keyBytes(b64) {
    var pad = '='.repeat((4 - b64.length % 4) % 4);
    var raw = atob((b64 + pad).replace(/-/g, '+').replace(/_/g, '/'));
    var out = new Uint8Array(raw.length);
    for (var i = 0; i < raw.length; i++) out[i] = raw.charCodeAt(i);
    return out;
  }
  function syncSubscription(sub) {
    return TT.api('/api/push/subscribe', { body: { endpoint: sub.endpoint } }).then(function () {
      store.set('tt_push_synced', String(Date.now()));
    });
  }
  TT.push = {
    /** 'unsupported' | 'needs-install' | 'denied' | 'on' | 'off' */
    state: function () {
      if (isIOS && !standalone) return 'needs-install';
      if (!pushSupported) return 'unsupported';
      if (Notification.permission === 'denied') return 'denied';
      return Notification.permission === 'granted' ? 'on' : 'off';
    },
    enable: function () {
      var st = TT.push.state();
      if (st === 'needs-install') {
        TT.toast('On iPhone, install the app first, then turn on notifications from inside it.', 'info');
        showInstall(true);
        return Promise.resolve(false);
      }
      if (st === 'unsupported') { TT.toast("This browser can't show notifications.", 'error'); return Promise.resolve(false); }
      if (st === 'denied') { TT.toast('Notifications are blocked. Allow them in your browser settings for this site.', 'error'); return Promise.resolve(false); }
      return Notification.requestPermission().then(function (perm) {
        if (perm !== 'granted') return false;
        return navigator.serviceWorker.ready.then(function (reg) {
          return reg.pushManager.getSubscription().then(function (sub) {
            return sub || reg.pushManager.subscribe({ userVisibleOnly: true, applicationServerKey: keyBytes(vapid) });
          });
        }).then(syncSubscription).then(function () {
          TT.toast("Notifications are on. We'll tell you about new matches and messages.", 'success');
          refreshPushUi();
          return true;
        });
      }).catch(function () {
        TT.toast("Couldn't turn on notifications. Please try again.", 'error');
        return false;
      });
    }
  };
  function refreshPushUi() {
    var st = TT.push.state();
    var dismissed = parseInt(store.get('tt_push_card') || '0', 10);
    document.querySelectorAll('[data-push-card]').forEach(function (el) {
      var show = (st === 'off' || st === 'needs-install') && !(dismissed && Date.now() - dismissed < 7 * 86400000);
      el.classList.toggle('hidden', !show);
    });
    document.querySelectorAll('[data-push-status]').forEach(function (el) {
      el.textContent = { on: 'On', off: 'Off', denied: 'Blocked in browser', unsupported: 'Not supported', 'needs-install': 'Install app first' }[st];
    });
    document.querySelectorAll('[data-push-enable]').forEach(function (el) {
      el.classList.toggle('hidden', st === 'on' || st === 'unsupported');
    });
  }
  document.addEventListener('click', function (e) {
    if (e.target.closest('[data-push-enable]')) { e.preventDefault(); TT.push.enable(); }
    if (e.target.closest('[data-push-dismiss]')) { store.set('tt_push_card', String(Date.now())); refreshPushUi(); }
  });
  refreshPushUi();
  // Keep the server's copy of this device's subscription fresh (endpoints can rotate).
  // Permission already granted: make sure this device has a subscription the server knows about.
  if (pushSupported && Notification.permission === 'granted') {
    var last = parseInt(store.get('tt_push_synced') || '0', 10);
    navigator.serviceWorker.ready.then(function (reg) {
      return reg.pushManager.getSubscription().then(function (sub) {
        if (sub) return Date.now() - last > 86400000 ? syncSubscription(sub) : null;
        return reg.pushManager.subscribe({ userVisibleOnly: true, applicationServerKey: keyBytes(vapid) }).then(syncSubscription);
      });
    }).catch(function () {});
  }

  var promptTimer = null;
  function maybePrompt() {
    if (document.body.getAttribute('data-pwa-prompt') !== '1' || standalone || store.get('tt_installed')) return;
    var dismissed = parseInt(store.get('tt_install_dismissed') || '0', 10);
    if (dismissed && Date.now() - dismissed < 5 * 86400000) return;
    if (!deferred && !isIOS) return;
    if (promptTimer) return;
    promptTimer = setTimeout(function () {
      if (!openSheets.length && !document.querySelector('.match-overlay')) showInstall(false);
    }, 12000);
  }
  var visits = parseInt(store.get('tt_visits') || '0', 10) + 1;
  store.set('tt_visits', String(visits));
  if (isIOS && visits >= 2) maybePrompt();
})();
