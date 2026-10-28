/* Discover: swipe deck with drag gestures, photo tapping, undo and filters. */
(function () {
  'use strict';
  var TT = window.TT;
  var deckEl = document.getElementById('deck');
  if (!deckEl) return;

  var queue = [];
  var seen = [];
  var loading = false;
  var exhausted = false;
  var busy = false;
  var loadingEl = deckEl.querySelector('[data-deck-loading]');
  var emptyEl = deckEl.querySelector('[data-deck-empty]');
  var actions = document.querySelector('[data-deck-actions]');
  var THRESHOLD = 110;

  function load() {
    if (loading || exhausted) return Promise.resolve();
    loading = true;
    var exclude = seen.slice(-60).concat(queue.map(function (c) { return c.id; }));
    return TT.api('/api/deck?exclude=' + exclude.join(','))
      .then(function (r) {
        var fresh = r.cards.filter(function (c) { return exclude.indexOf(c.id) === -1; });
        if (!fresh.length) exhausted = true;
        queue = queue.concat(fresh);
        fresh.forEach(function (c) { c.photos.slice(0, 2).forEach(preload); });
      })
      .catch(function (err) { TT.toast(err.message, 'error'); })
      .then(function () { loading = false; render(); });
  }

  function preload(src) { var i = new Image(); i.src = src; }

  function render() {
    loadingEl.classList.toggle('hidden', !(loading && !queue.length));
    var empty = !queue.length && !loading;
    emptyEl.classList.toggle('hidden', !empty);
    actions.querySelectorAll('[data-act]').forEach(function (b) {
      if (b.getAttribute('data-act') !== 'rewind') b.disabled = !queue.length;
    });

    var existing = Array.prototype.slice.call(deckEl.querySelectorAll('.pcard'));
    var wanted = queue.slice(0, 2);
    existing.forEach(function (el) {
      if (!wanted.some(function (c) { return String(c.id) === el.dataset.id; }) && !el.classList.contains('is-leaving')) el.remove();
    });
    // Draw back-to-front so the top card sits last in the DOM.
    wanted.slice().reverse().forEach(function (card, idx) {
      var isTop = idx === wanted.length - 1;
      var el = deckEl.querySelector('.pcard[data-id="' + card.id + '"]:not(.is-leaving)');
      if (!el) {
        el = build(card);
        deckEl.appendChild(el);
      }
      el.classList.toggle('is-top', isTop);
      el.classList.toggle('is-behind', !isTop);
      if (isTop) { deckEl.appendChild(el); bindDrag(el, card); }
    });
    if (queue.length < 4) load();
  }

  function build(card) {
    var el = document.createElement('div');
    el.className = 'pcard';
    el.dataset.id = card.id;
    var bars = card.photos.length > 1
      ? '<div class="pcard-bars">' + card.photos.map(function (_, i) { return '<i class="' + (i === 0 ? 'on' : '') + '"></i>'; }).join('') + '</div>'
      : '';
    var pills = [];
    if (card.superliked_you) pills.push('<span class="pill pill-gold">' + TT.icon('star', 13) + 'Super Liked you</span>');
    else if (card.liked_you) pills.push('<span class="pill pill-brand">' + TT.icon('heart', 13) + 'Likes you</span>');
    if (card.online) pills.push('<span class="pill pill-live">Active now</span>');
    if (card.ai) pills.push('<span class="pill pill-ai">' + TT.icon('sparkle', 13) + 'AI-generated photos</span>');
    var meta = [];
    if (card.job) meta.push('<span>' + TT.icon('briefcase', 16) + TT.esc(card.job) + '</span>');
    meta.push('<span>' + TT.icon('pin', 16) + TT.esc(card.city) + (card.distance ? ' · ' + TT.esc(card.distance) : '') + '</span>');

    el.innerHTML =
      '<div class="pcard-photo">' + (card.photos[0] ? '<img src="' + TT.esc(card.photos[0]) + '" alt="" draggable="false">' : '') + '</div>' +
      bars +
      '<div class="pcard-tap l" data-tap="-1"></div><div class="pcard-tap r" data-tap="1"></div>' +
      '<div class="stamp stamp-like">LIKE</div><div class="stamp stamp-nope">NOPE</div><div class="stamp stamp-super">SUPER</div>' +
      '<div class="pcard-shade"></div>' +
      '<div class="pcard-info" data-open-profile>' +
        (pills.length ? '<div class="pcard-pills">' + pills.join('') + '</div>' : '') +
        '<div class="pcard-name">' + TT.esc(card.name) + ' <span class="age">' + card.age + '</span>' + (card.verified ? TT.verified(24) : '') + '</div>' +
        '<div class="pcard-meta">' + meta.join('') + '</div>' +
        (card.bio ? '<p class="pcard-bio">' + TT.esc(card.bio) + '</p>' : '') +
      '</div>' +
      '<button class="pcard-more" data-open-profile aria-label="Open profile">' + TT.icon('up', 20) + '</button>';
    el._photo = 0;
    return el;
  }

  function showPhoto(el, card, dir) {
    var n = card.photos.length;
    if (n < 2) { nudge(el, dir); return; }
    var next = el._photo + dir;
    if (next < 0 || next >= n) { nudge(el, dir); return; }
    el._photo = next;
    el.querySelector('.pcard-photo img').src = card.photos[next];
    el.querySelectorAll('.pcard-bars i').forEach(function (b, i) { b.classList.toggle('on', i === next); });
    if (card.photos[next + 1]) preload(card.photos[next + 1]);
  }

  function nudge(el, dir) {
    el.classList.add('is-animating');
    el.style.transform = 'perspective(800px) rotateY(' + (dir * 4) + 'deg)';
    setTimeout(function () { el.style.transform = ''; setTimeout(function () { el.classList.remove('is-animating'); }, 380); }, 120);
  }

  function bindDrag(el, card) {
    if (el._bound) return;
    el._bound = true;
    var startX = 0, startY = 0, dx = 0, dy = 0, dragging = false, moved = false, pid = null;
    var like = el.querySelector('.stamp-like'), nope = el.querySelector('.stamp-nope'), sup = el.querySelector('.stamp-super');

    el.addEventListener('pointerdown', function (e) {
      if (busy || e.button > 0 || e.target.closest('.pcard-more')) return;
      dragging = true; moved = false; pid = e.pointerId;
      startX = e.clientX; startY = e.clientY; dx = dy = 0;
      el.classList.remove('is-animating');
      el.setPointerCapture(pid);
    });
    el.addEventListener('pointermove', function (e) {
      if (!dragging || e.pointerId !== pid) return;
      dx = e.clientX - startX; dy = e.clientY - startY;
      if (Math.abs(dx) > 6 || Math.abs(dy) > 6) moved = true;
      if (!moved) return;
      el.style.transform = 'translate(' + dx + 'px,' + dy + 'px) rotate(' + (dx * 0.06) + 'deg)';
      like.style.opacity = Math.max(0, Math.min(1, dx / THRESHOLD));
      nope.style.opacity = Math.max(0, Math.min(1, -dx / THRESHOLD));
      sup.style.opacity = Math.abs(dx) < 70 ? Math.max(0, Math.min(1, -dy / THRESHOLD)) : 0;
    });
    function end(e) {
      if (!dragging || (e && e.pointerId !== pid)) return;
      dragging = false;
      if (!moved) {
        var t = e && document.elementFromPoint(e.clientX, e.clientY);
        if (t && t.closest('[data-open-profile]')) openProfile(card);
        else if (t && t.closest('[data-tap]')) showPhoto(el, card, parseInt(t.closest('[data-tap]').getAttribute('data-tap'), 10));
        return;
      }
      if (dx > THRESHOLD) decide('like', dx, dy);
      else if (dx < -THRESHOLD) decide('pass', dx, dy);
      else if (dy < -THRESHOLD && Math.abs(dx) < 80) decide('superlike', dx, dy);
      else {
        el.classList.add('is-animating');
        el.style.transform = '';
        [like, nope, sup].forEach(function (s) { s.style.opacity = 0; });
      }
    }
    el.addEventListener('pointerup', end);
    el.addEventListener('pointercancel', end);
    el.addEventListener('click', function (e) {
      if (e.target.closest('.pcard-more')) openProfile(card);
    });
  }

  function decide(action, dx, dy) {
    if (busy || !queue.length) return;
    var card = queue[0];
    var el = deckEl.querySelector('.pcard.is-top');
    busy = true;
    if (el) {
      var stamp = el.querySelector(action === 'like' ? '.stamp-like' : action === 'pass' ? '.stamp-nope' : '.stamp-super');
      stamp.style.opacity = 1;
      var w = window.innerWidth;
      var x = action === 'like' ? w * 1.3 : action === 'pass' ? -w * 1.3 : (dx || 0);
      var y = action === 'superlike' ? -window.innerHeight * 1.2 : (dy || 0) + 60;
      el.classList.add('is-animating', 'is-leaving');
      el.classList.remove('is-top');
      el.style.transform = 'translate(' + x + 'px,' + y + 'px) rotate(' + (action === 'like' ? 24 : action === 'pass' ? -24 : 0) + 'deg)';
      setTimeout(function () { el.remove(); }, 400);
    }
    queue.shift();
    seen.push(card.id);
    if (navigator.vibrate) navigator.vibrate(action === 'superlike' ? 18 : 8);
    render();
    setTimeout(function () { busy = false; }, 220);

    TT.api('/api/swipe', { body: { id: card.id, action: action } })
      .then(function (r) {
        if (r.match) setTimeout(function () { TT.matchOverlay(r.match); }, 250);
      })
      .catch(function (err) {
        TT.toast(err.message, 'error');
        if (err.status === 429 || err.status === 422) {
          seen = seen.filter(function (id) { return id !== card.id; });
          queue.unshift(card);
          render();
        }
      });
  }

  function rewind() {
    TT.api('/api/rewind', { body: {} })
      .then(function (r) {
        seen = seen.filter(function (id) { return id !== r.card.id; });
        queue.unshift(r.card);
        exhausted = false;
        render();
      })
      .catch(function (err) { TT.toast(err.message, 'info'); });
  }

  actions.addEventListener('click', function (e) {
    var b = e.target.closest('[data-act]');
    if (!b) return;
    var act = b.getAttribute('data-act');
    if (act === 'rewind') rewind();
    else decide(act, 0, 0);
  });

  document.addEventListener('keydown', function (e) {
    if (document.querySelector('.sheet.is-open, .match-overlay') || /input|textarea|select/i.test((e.target.tagName || ''))) return;
    if (e.key === 'ArrowLeft') decide('pass', 0, 0);
    else if (e.key === 'ArrowRight') decide('like', 0, 0);
    else if (e.key === 'ArrowUp') decide('superlike', 0, 0);
    else if (e.key === ' ' && queue[0]) { e.preventDefault(); var top = deckEl.querySelector('.pcard.is-top'); if (top) showPhoto(top, queue[0], 1); }
  });

  // Report/block from an open profile removes that card.
  document.addEventListener('tt:removed', function () {
    if (queue.length) { seen.push(queue[0].id); queue.shift(); render(); }
  });

  /* ---------- Full profile sheet ---------- */
  function openProfile(card) {
    var photos = card.photos.map(function (p) { return '<img src="' + TT.esc(p) + '" alt="">'; }).join('');
    var dots = card.photos.length > 1 ? '<div class="pv-dots" data-gallery-dots>' + card.photos.map(function (_, i) { return '<i class="' + (i ? '' : 'on') + '"></i>'; }).join('') + '</div>' : '';
    var meta = [];
    if (card.activity) meta.push('<span' + (card.online ? ' style="color:var(--ok)"' : '') + '>' + TT.icon('zap', 17) + TT.esc(card.activity) + '</span>');
    if (card.job) meta.push('<span>' + TT.icon('briefcase', 17) + TT.esc(card.job) + '</span>');
    if (card.education) meta.push('<span>' + TT.icon('cap', 17) + TT.esc(card.education) + '</span>');
    meta.push('<span>' + TT.icon('pin', 17) + TT.esc(card.city) + (card.distance ? ' · ' + TT.esc(card.distance) : '') + '</span>');
    var html =
      '<button class="sheet-close" data-sheet-close aria-label="Close">' + TT.icon('x', 22) + '</button>' +
      '<div class="profile-view" data-profile="' + card.id + '" data-name="' + TT.esc(card.name) + '">' +
      '<div class="pv-gallery"><div class="pv-track" data-gallery>' + photos + '</div>' + dots + '</div>' +
      '<div class="pv-head"><div class="pv-name">' + TT.esc(card.name) + ' <span class="age">' + card.age + '</span>' + (card.verified ? TT.verified(24) : '') + '</div>' +
      (card.ai ? '<p class="ai-note">' + TT.icon('sparkle', 15) + 'This profile\'s photos are AI-generated.</p>' : '') +
      '<div class="pv-meta">' + meta.join('') + '</div></div>' +
      (card.bio ? '<div class="pv-section"><h3>About</h3><p class="pv-bio">' + TT.esc(card.bio) + '</p></div>' : '') +
      (card.prompts.length ? '<div style="height:14px"></div>' : '') +
      card.prompts.map(function (p) { return '<div class="prompt-card"><div class="q">' + TT.esc(p.q) + '</div><div class="a">' + TT.esc(p.a) + '</div></div>'; }).join('') +
      (card.basics.length ? '<div class="pv-section"><h3>Basics</h3><div class="chips">' + card.basics.map(function (b) { return '<span class="chip">' + TT.icon(b.icon, 16) + TT.esc(b.label) + '</span>'; }).join('') + '</div></div>' : '') +
      (card.interests.length ? '<div class="pv-section"><h3>Interests</h3><div class="chips">' + card.interests.map(function (i) { return '<span class="chip">' + TT.esc(i) + '</span>'; }).join('') + '</div></div>' : '') +
      '<div class="pv-safety"><button type="button" data-report>' + TT.icon('flag', 16) + ' Report ' + TT.esc(card.name) + '</button><button type="button" data-block>' + TT.icon('block', 16) + ' Block</button></div>' +
      '<div style="height:110px"></div></div>' +
      '<div class="pv-actions" style="position:sticky">' +
        '<button class="act act-lg act-pass" data-sheet-act="pass" aria-label="Pass">' + TT.icon('x', 30) + '</button>' +
        '<button class="act act-sm act-super" data-sheet-act="superlike" aria-label="Super Like" style="align-self:center">' + TT.icon('star', 22) + '</button>' +
        '<button class="act act-lg act-like" data-sheet-act="like" aria-label="Like">' + TT.icon('heart', 30) + '</button>' +
      '</div>';
    TT.sheet(html, {
      full: true,
      onOpen: function (el, s) {
        TT.bindGallery(el);
        el.querySelectorAll('[data-sheet-act]').forEach(function (b) {
          b.addEventListener('click', function () {
            s.close();
            setTimeout(function () { decide(b.getAttribute('data-sheet-act'), 0, 0); }, 180);
          });
        });
      }
    });
  }

  /* ---------- Filters ---------- */
  document.addEventListener('click', function (e) {
    if (!e.target.closest('[data-open-filters]')) return;
    TT.sheet('#tpl-filters', {
      onOpen: function (el, s) {
        var form = el.querySelector('[data-filters-form]');
        bindDualRange(el.querySelector('[data-dual-range]'), el.querySelector('[data-age-label]'));
        form.addEventListener('submit', function (ev) {
          ev.preventDefault();
          var fd = new FormData(form);
          var body = {};
          fd.forEach(function (v, k) { body[k] = v; });
          TT.api('/api/filters', { body: body }).then(function () {
            s.close();
            location.reload();
          }).catch(function (err) { TT.toast(err.message, 'error'); });
        });
      }
    });
  });

  function bindDualRange(wrap, label) {
    var inputs = wrap.querySelectorAll('input[type=range]');
    var fill = wrap.querySelector('.range-fill');
    function update(e) {
      var a = parseInt(inputs[0].value, 10), b = parseInt(inputs[1].value, 10);
      if (a > b) {
        if (e && e.target === inputs[0]) { inputs[0].value = b; a = b; } else { inputs[1].value = a; b = a; }
      }
      var min = parseInt(inputs[0].min, 10), max = parseInt(inputs[0].max, 10);
      fill.style.left = ((a - min) / (max - min) * 100) + '%';
      fill.style.right = (100 - (b - min) / (max - min) * 100) + '%';
      label.textContent = a + '–' + (b === max ? max + '+' : b);
    }
    inputs.forEach(function (i) { i.addEventListener('input', update); });
    update();
  }

  emptyEl.querySelector('[data-deck-reload]').addEventListener('click', function () {
    exhausted = false;
    seen = [];
    load();
  });

  load();
})();
