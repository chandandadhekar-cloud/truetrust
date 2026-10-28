/* Chat thread: render, poll for new messages, optimistic send, read receipts. */
(function () {
  'use strict';
  var TT = window.TT;
  var root = document.getElementById('chat');
  if (!root) return;

  var matchId = root.getAttribute('data-match');
  var data = JSON.parse(document.getElementById('chat-data').textContent);
  var list = document.getElementById('chat-messages');
  var scroller = document.getElementById('chat-scroll');
  var form = document.querySelector('[data-compose]');
  var ice = document.querySelector('[data-icebreakers]');
  var messages = data.messages;
  var readUpTo = 0;
  var pollDelay = 3000;
  var timer = null;
  var coarse = window.matchMedia('(pointer: coarse)').matches;
  var drawn = {}; // message id -> when it first appeared; newer ones animate in
  var firstDraw = true;
  var ENTER_MS = 320;

  messages.forEach(function (m) { if (m.mine && m.read) readUpTo = Math.max(readUpTo, m.id); });

  function dayLabel(d) {
    var today = new Date();
    var t = new Date(d + 'T00:00:00');
    var diff = Math.round((new Date(today.getFullYear(), today.getMonth(), today.getDate()) - t) / 86400000);
    if (diff === 0) return 'Today';
    if (diff === 1) return 'Yesterday';
    return t.toLocaleDateString(undefined, { day: 'numeric', month: 'short', year: t.getFullYear() === today.getFullYear() ? undefined : 'numeric' });
  }

  function lastId() {
    for (var i = messages.length - 1; i >= 0; i--) if (messages[i].id > 0) return messages[i].id;
    return 0;
  }

  function nearBottom() {
    return scroller.scrollHeight - scroller.scrollTop - scroller.clientHeight < 140;
  }

  function scrollDown(force) {
    if (force || nearBottom()) scroller.scrollTop = scroller.scrollHeight;
  }

  function render() {
    var html = '';
    var day = null;
    var lastMine = null;
    var now = Date.now();
    messages.forEach(function (m) { if (m.mine && m.id > 0) lastMine = m; });
    messages.forEach(function (m, i) {
      if (m.date !== day) { day = m.date; html += '<div class="msg-day">' + dayLabel(day) + '</div>'; }
      var next = messages[i + 1];
      var endOfRun = !next || next.mine !== m.mine || next.date !== m.date;
      var cls = 'msg ' + (m.mine ? 'mine' : 'theirs') + (m.removed ? ' removed' : '') + (m.pending ? ' pending' : '') + (m.failed ? ' failed' : '');
      if (!drawn[m.id]) drawn[m.id] = firstDraw ? -1 : now;
      // Redraws rebuild the list, so resume a running entrance instead of restarting it.
      var age = drawn[m.id] < 0 ? ENTER_MS : now - drawn[m.id];
      var style = age < ENTER_MS ? ' style="animation-delay:-' + Math.round(age) + 'ms"' : '';
      if (style) cls += ' is-new';
      html += '<div class="' + cls + '"' + style + '><div class="bubble">' + (m.removed ? 'This message was removed by moderators' : TT.esc(m.body)) + '</div>';
      if (m.failed) html += '<div class="msg-meta" style="color:var(--danger)">Not sent</div>';
      else if (m === lastMine && m.id <= readUpTo) html += '<div class="msg-meta">Seen</div>';
      else if (endOfRun) html += '<div class="msg-meta">' + (m.pending ? 'Sending…' : m.time) + '</div>';
      html += '</div>';
    });
    list.innerHTML = html;
    firstDraw = false;
    if (ice) ice.classList.toggle('hidden', messages.length > 0);
  }

  function poll() {
    clearTimeout(timer);
    TT.api('/api/chat/' + matchId + '?after=' + lastId())
      .then(function (r) {
        var changed = false;
        r.messages.forEach(function (m) {
          if (!messages.some(function (x) { return x.id === m.id; })) { messages.push(m); changed = true; }
        });
        if (r.readUpTo !== readUpTo) { readUpTo = r.readUpTo; changed = true; }
        if (changed) { var stick = nearBottom(); render(); scrollDown(stick); }
        if (r.ended && form) { form.outerHTML = '<div class="chat-ended">This match has ended.</div>'; form = null; return; }
        pollDelay = document.hidden ? 15000 : 3000;
        timer = setTimeout(poll, pollDelay);
      })
      .catch(function (err) {
        if (err.status === 404) { TT.toast(err.message, 'error'); return; }
        timer = setTimeout(poll, 8000);
      });
  }

  document.addEventListener('visibilitychange', function () { if (!document.hidden) poll(); });

  if (form) {
    var ta = form.querySelector('textarea');
    var btn = form.querySelector('.send-btn');
    var autosize = function () {
      ta.style.height = 'auto';
      ta.style.height = Math.min(140, ta.scrollHeight) + 'px';
      btn.disabled = !ta.value.trim();
    };
    ta.addEventListener('input', autosize);
    ta.addEventListener('keydown', function (e) {
      if (e.key === 'Enter' && !e.shiftKey && !coarse) { e.preventDefault(); form.requestSubmit ? form.requestSubmit() : form.dispatchEvent(new Event('submit', { cancelable: true })); }
    });
    form.addEventListener('submit', function (e) {
      e.preventDefault();
      var body = ta.value.trim();
      if (!body) return;
      var now = new Date();
      var temp = {
        id: -Date.now(), mine: true, body: body, pending: true,
        time: now.toLocaleTimeString([], { hour: 'numeric', minute: '2-digit' }),
        date: now.getFullYear() + '-' + String(now.getMonth() + 1).padStart(2, '0') + '-' + String(now.getDate()).padStart(2, '0')
      };
      messages.push(temp);
      ta.value = '';
      autosize();
      render();
      scrollDown(true);
      if (coarse) ta.blur(); else ta.focus();
      TT.api('/api/chat/' + matchId, { body: { body: body } })
        .then(function (r) {
          var i = messages.indexOf(temp);
          drawn[r.message.id] = drawn[temp.id]; // same bubble as the pending copy
          if (i > -1 && !messages.some(function (x) { return x.id === r.message.id; })) messages[i] = r.message;
          else if (i > -1) messages.splice(i, 1);
          render();
        })
        .catch(function (err) {
          temp.pending = false;
          temp.failed = true;
          render();
          TT.toast(err.message, 'error');
          ta.value = body;
          autosize();
        });
    });
    if (ice) {
      ice.addEventListener('click', function (e) {
        var b = e.target.closest('button');
        if (!b) return;
        ta.value = b.textContent;
        autosize();
        ta.focus();
      });
    }
  }

  document.querySelector('[data-chat-menu]').addEventListener('click', function () {
    TT.sheet('#tpl-chat-menu');
  });

  render();
  scrollDown(true);
  timer = setTimeout(poll, 2500);
})();
