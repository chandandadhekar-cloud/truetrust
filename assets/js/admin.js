/* Admin helpers: sidebar toggle, select-all checkboxes, bulk forms. */
(function () {
  'use strict';
  var side = document.getElementById('side');
  document.addEventListener('click', function (e) {
    if (e.target.closest('[data-side-toggle]')) side.classList.toggle('is-open');
    else if (side.classList.contains('is-open') && !e.target.closest('#side')) side.classList.remove('is-open');
  });

  document.querySelectorAll('[data-check-all]').forEach(function (all) {
    var scope = document.querySelector(all.getAttribute('data-check-all'));
    var boxes = function () { return scope.querySelectorAll('input[type=checkbox][name="ids[]"]'); };
    var bar = document.querySelector('[data-bulkbar]');
    var sync = function () {
      var n = Array.prototype.filter.call(boxes(), function (b) { return b.checked; }).length;
      if (bar) { bar.classList.toggle('hidden', n === 0); var c = bar.querySelector('[data-bulk-count]'); if (c) c.textContent = n; }
    };
    all.addEventListener('change', function () { boxes().forEach(function (b) { b.checked = all.checked; }); sync(); });
    scope.addEventListener('change', sync);
    sync();
  });

  // Copy to clipboard
  document.addEventListener('click', function (e) {
    var b = e.target.closest('[data-copy]');
    if (!b) return;
    navigator.clipboard && navigator.clipboard.writeText(b.getAttribute('data-copy')).then(function () { window.TT.toast('Copied', 'success'); });
  });
})();
