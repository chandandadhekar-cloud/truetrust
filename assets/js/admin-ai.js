/* Admin: runs the AI photo queue one image at a time while the page is open. */
(function () {
  'use strict';

  var root = document.querySelector('[data-ai-runner]');
  if (!root || !root.querySelector('[data-ai-start]')) return;

  var startBtn = root.querySelector('[data-ai-start]');
  var stopBtn = root.querySelector('[data-ai-stop]');
  var msg = root.querySelector('[data-ai-msg]');
  var bar = root.querySelector('[data-ai-bar]');
  var strip = root.querySelector('[data-ai-strip]');
  var running = false;
  var total = 0;

  function show(r) {
    var c = r.counts || {};
    var waiting = (c.queued || 0) + (c.running || 0);
    root.querySelector('[data-ai-count="queued"]').textContent = waiting;
    root.querySelector('[data-ai-count="done"]').textContent = c.done || 0;
    root.querySelector('[data-ai-count="failed"]').textContent = c.failed || 0;
    if (typeof r.spent === 'number') root.querySelector('[data-ai-spent]').textContent = r.spent.toFixed(2);
    total = Math.max(total, waiting);
    bar.style.width = total ? Math.round((1 - waiting / total) * 100) + '%' : '0%';
    return waiting;
  }

  function setRunning(on) {
    running = on;
    startBtn.classList.toggle('hidden', on);
    stopBtn.classList.toggle('hidden', !on);
  }

  function step() {
    if (!running) return;
    msg.textContent = 'Generating… this takes 20–60 seconds per photo.';
    window.TT.api('/admin/seed/ai/run', { body: {} }).then(function (r) {
      var waiting = show(r);
      msg.textContent = r.message;
      if (r.photo) {
        var a = document.createElement('a');
        a.href = '/admin/users/' + r.photo.user;
        a.title = r.photo.name;
        a.innerHTML = '<img src="' + r.photo.url + '" alt="">';
        strip.insertBefore(a, strip.firstChild);
      }
      if (r.state === 'stopped' || r.state === 'idle' || waiting === 0) {
        setRunning(false);
        if (r.state === 'stopped') window.TT.toast(r.message, 'error');
        else if (waiting === 0) window.TT.toast('All queued photos are done.', 'success');
        return;
      }
      setTimeout(step, r.state === 'busy' ? 5000 : 600);
    }).catch(function () {
      // A gateway timeout doesn't stop the server-side job; check progress and carry on.
      msg.textContent = 'Still working on the last photo…';
      setTimeout(function () {
        window.TT.api('/admin/seed/ai/status').then(function (r) { show(r); step(); }).catch(function () { setTimeout(step, 8000); });
      }, 8000);
    });
  }

  startBtn.addEventListener('click', function () { total = 0; setRunning(true); step(); });
  stopBtn.addEventListener('click', function () {
    setRunning(false);
    msg.textContent = 'Paused. The photo in progress will still finish.';
  });
})();
