/* Shared form widgets: photo grid uploader, interest chips, counters, city picker. */
(function () {
  'use strict';
  var TT = window.TT;

  /* ---------- Photo grid ---------- */
  var grid = document.querySelector('[data-photo-grid]');
  if (grid) {
    var max = parseInt(grid.getAttribute('data-max'), 10) || 6;
    var photos = Array.prototype.map.call(grid.querySelectorAll('[data-photo-id]'), function (el) {
      return { id: parseInt(el.getAttribute('data-photo-id'), 10), url: el.querySelector('img').src, pending: !!el.querySelector('.ph-tag.pending') };
    });
    var uploading = 0;
    var next = document.querySelector('[data-photos-next]');

    var draw = function () {
      var html = '';
      for (var i = 0; i < max; i++) {
        var p = photos[i];
        if (p) {
          html += '<div class="photo-slot has-photo' + (p.fresh ? ' is-new' : '') + '" data-photo-id="' + p.id + '"><img src="' + TT.esc(p.url) + '" alt="">' +
            '<div class="ph-actions">' + (i > 0 ? '<button type="button" class="ph-btn" data-photo-first title="Make cover photo">' + TT.icon('star', 16) + '</button>' : '') +
            '<button type="button" class="ph-btn" data-photo-delete title="Remove photo"><svg class="icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg></button></div>' +
            (p.pending ? '<span class="ph-tag pending">In review</span>' : i === 0 ? '<span class="ph-tag">Cover</span>' : '') + '</div>';
        } else if (i < photos.length + uploading) {
          html += '<div class="photo-slot is-uploading"></div>';
        } else {
          html += '<label class="photo-slot"><span class="add"><svg class="icon" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="M12 5v14M5 12h14"/></svg></span>' +
            '<input type="file" accept="image/jpeg,image/png,image/webp" multiple data-photo-input aria-label="Add photo"></label>';
        }
      }
      grid.innerHTML = html;
      photos.forEach(function (p) { p.fresh = false; });
      if (next) next.disabled = photos.length === 0 || uploading > 0;
    };

    // Downscale big phone photos before upload: much faster on mobile data.
    var shrink = function (file) {
      if (!window.createImageBitmap || file.size < 900 * 1024) return Promise.resolve(file);
      return createImageBitmap(file, { imageOrientation: 'from-image' }).then(function (bmp) {
        var scale = Math.min(1, 1800 / Math.max(bmp.width, bmp.height));
        var c = document.createElement('canvas');
        c.width = Math.round(bmp.width * scale);
        c.height = Math.round(bmp.height * scale);
        c.getContext('2d').drawImage(bmp, 0, 0, c.width, c.height);
        return new Promise(function (res) {
          c.toBlob(function (b) { res(b ? new File([b], 'photo.jpg', { type: 'image/jpeg' }) : file); }, 'image/jpeg', 0.9);
        });
      }).catch(function () { return file; });
    };

    var upload = function (file) {
      uploading++;
      draw();
      return shrink(file).then(function (f) {
        var fd = new FormData();
        fd.append('photo', f, f.name || 'photo.jpg');
        return TT.api('/api/photos', { body: fd });
      }).then(function (r) {
        photos.push({ id: r.photo.id, url: r.photo.url, pending: r.photo.status === 'pending', fresh: true });
        if (r.photo.status === 'pending') TT.toast('Photo added. It will appear once reviewed.', 'info');
      }).catch(function (err) {
        TT.toast(err.message, 'error');
      }).then(function () { uploading--; draw(); });
    };

    grid.addEventListener('change', function (e) {
      if (!e.target.matches('[data-photo-input]')) return;
      var files = Array.prototype.slice.call(e.target.files, 0, max - photos.length - uploading);
      files.reduce(function (p, f) { return p.then(function () { return upload(f); }); }, Promise.resolve());
    });

    grid.addEventListener('click', function (e) {
      var slot = e.target.closest('[data-photo-id]');
      if (!slot) return;
      var id = parseInt(slot.getAttribute('data-photo-id'), 10);
      if (e.target.closest('[data-photo-delete]')) {
        TT.confirm('Remove this photo?', 'Remove').then(function (ok) {
          if (!ok) return;
          TT.api('/api/photos/' + id + '/delete', { body: {} }).then(function () {
            slot.classList.add('is-removing');
            return new Promise(function (res) { setTimeout(res, 220); });
          }).then(function () {
            photos = photos.filter(function (p) { return p.id !== id; });
            draw();
          }).catch(function (err) { TT.toast(err.message, 'error'); });
        });
      } else if (e.target.closest('[data-photo-first]')) {
        var p = photos.filter(function (x) { return x.id === id; })[0];
        photos = [p].concat(photos.filter(function (x) { return x.id !== id; }));
        draw();
        TT.api('/api/photos/reorder', { body: { ids: photos.map(function (x) { return x.id; }) } }).catch(function (err) { TT.toast(err.message, 'error'); });
      }
    });
    draw();
  }

  /* ---------- Chips with a limit ---------- */
  document.querySelectorAll('[data-chip-limit]').forEach(function (box) {
    var limit = parseInt(box.getAttribute('data-chip-limit'), 10);
    var counter = box.parentNode.querySelector('[data-chip-count]') || document.querySelector('[data-chip-count]');
    var sync = function () {
      var n = box.querySelectorAll('input:checked').length;
      if (counter) counter.textContent = n + '/' + limit;
      box.querySelectorAll('input:not(:checked)').forEach(function (i) {
        i.disabled = n >= limit;
        i.parentNode.style.opacity = n >= limit ? '.45' : '';
      });
    };
    box.addEventListener('change', sync);
    sync();
  });

  /* ---------- Character counters ---------- */
  document.querySelectorAll('[data-counter-for]').forEach(function (c) {
    var ta = document.getElementById(c.getAttribute('data-counter-for'));
    if (!ta) return;
    var max = ta.getAttribute('maxlength');
    var sync = function () { c.textContent = ta.value.length + '/' + max; };
    ta.addEventListener('input', sync);
    sync();
  });

  /* ---------- City picker ---------- */
  var cityList = document.querySelector('[data-city-list]');
  if (cityList) {
    var value = document.querySelector('[data-city-value]');
    var search = document.querySelector('[data-city-search]');
    var nextBtn = document.querySelector('[data-city-next]');
    var opts = Array.prototype.slice.call(cityList.querySelectorAll('[data-city]'));
    var pick = function (el) {
      opts.forEach(function (o) { o.classList.toggle('is-on', o === el); });
      value.value = el.getAttribute('data-city');
      nextBtn.disabled = false;
    };
    cityList.addEventListener('click', function (e) {
      var o = e.target.closest('[data-city]');
      if (o) pick(o);
    });
    cityList.addEventListener('keydown', function (e) {
      if (e.key === 'Enter' && e.target.matches('[data-city]')) { e.preventDefault(); pick(e.target); }
    });
    search.addEventListener('input', function () {
      var q = search.value.trim().toLowerCase();
      opts.forEach(function (o) { o.classList.toggle('hidden', q && o.textContent.toLowerCase().indexOf(q) === -1); });
    });
    var current = cityList.querySelector('.is-on');
    if (current) current.scrollIntoView({ block: 'center' });

    document.querySelector('[data-locate]').addEventListener('click', function () {
      var b = this;
      if (!navigator.geolocation) { TT.toast('Location is not available on this device.', 'error'); return; }
      b.classList.add('is-loading');
      navigator.geolocation.getCurrentPosition(function (pos) {
        var lat = pos.coords.latitude, lng = pos.coords.longitude;
        var best = null, bestD = Infinity;
        opts.forEach(function (o) {
          var dLat = parseFloat(o.getAttribute('data-lat')) - lat, dLng = (parseFloat(o.getAttribute('data-lng')) - lng) * Math.cos(lat * Math.PI / 180);
          var d = dLat * dLat + dLng * dLng;
          if (d < bestD) { bestD = d; best = o; }
        });
        b.classList.remove('is-loading');
        if (best) {
          pick(best);
          best.scrollIntoView({ block: 'center', behavior: 'smooth' });
          TT.toast('Found you near ' + best.getAttribute('data-city'), 'success');
        }
      }, function () {
        b.classList.remove('is-loading');
        TT.toast('We could not get your location. Pick your city instead.', 'error');
      }, { timeout: 10000, maximumAge: 600000 });
    });
  }
})();
