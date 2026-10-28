/* Member profile page: like / pass / super like from the full profile. */
(function () {
  'use strict';
  var TT = window.TT;
  var root = document.querySelector('[data-profile]');
  if (!root) return;
  var id = root.getAttribute('data-profile');

  document.querySelectorAll('[data-swipe]').forEach(function (b) {
    b.addEventListener('click', function () {
      var action = b.getAttribute('data-swipe');
      b.classList.add('is-pop');
      document.querySelectorAll('[data-swipe]').forEach(function (x) { x.disabled = true; });
      TT.api('/api/swipe', { body: { id: id, action: action } })
        .then(function (r) {
          if (r.match) {
            TT.matchOverlay(r.match);
            document.querySelector('.pv-actions').innerHTML = '<a href="/chat/' + r.match.id + '" class="btn btn-primary" style="min-width:220px">Send a message</a>';
          } else {
            TT.burst(action);
            TT.toast(action === 'pass' ? 'Passed' : action === 'superlike' ? 'Super Like sent' : 'Like sent', 'success');
            setTimeout(function () { history.length > 1 ? history.back() : location.assign('/likes'); }, 850);
          }
        })
        .catch(function (err) {
          TT.toast(err.message, 'error');
          b.classList.remove('is-pop');
          document.querySelectorAll('[data-swipe]').forEach(function (x) { x.disabled = false; });
        });
    });
  });

  document.addEventListener('tt:removed', function () {
    setTimeout(function () { location.href = '/discover'; }, 900);
  });
})();
