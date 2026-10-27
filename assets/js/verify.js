/* Selfie verification: submit as soon as a photo is chosen. */
(function () {
  'use strict';
  var input = document.querySelector('[data-selfie-input]');
  if (!input) return;
  input.addEventListener('change', function () {
    if (!input.files.length) return;
    input.closest('label').classList.add('is-loading');
    document.querySelector('[data-selfie-label]').textContent = 'Uploading…';
    input.form.submit();
  });
})();
