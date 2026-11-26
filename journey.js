(function () {
  var C = window.JOURNEY;
  var svg = document.getElementById('map');
  var route = document.getElementById('route');
  var trail = document.getElementById('trail');
  var steps = Array.prototype.slice.call(document.querySelectorAll('.step'));
  if (!C || !svg || !route || !steps.length) return;

  var WW = C.world[0], WH = C.world[1];
  var total = route.getTotalLength();
  trail.style.strokeDasharray = '0 ' + (total + 10);

  // Find path lengths for a list of points, in order (so routes that come back work)
  function locate(points) {
    var out = [], from = 0;
    points.forEach(function (t) {
      if (!t) { out.push(total); return; }
      var best = from, bestD = Infinity;
      for (var l = from; l <= total; l += 2) {
        var p = route.getPointAtLength(l), d = (p.x - t[0]) * (p.x - t[0]) + (p.y - t[1]) * (p.y - t[1]);
        if (d < bestD) { bestD = d; best = l; }
        if (bestD < 9 && d > 900) break;
      }
      out.push(best);
      from = Math.min(total, best + 6);
    });
    return out;
  }
  var stopLen = locate(C.stops);
  var modeLen = locate(C.modes.map(function (m) { return m[1]; }));
  modeLen[0] = 0;

  var a1 = document.getElementById('a1'), a2 = document.getElementById('a2');
  var boat = document.getElementById('boat'), balloon = document.getElementById('balloon');
  // Nel deserto si cammina: i dromedari seguono il gruppo portando i bagagli
  var hasCamel = C.modes.some(function (m) { return m[0] === 'camel'; });
  var camels = [];
  if (hasCamel && a1.querySelector('.m-camel')) {
    for (var ci = 0; ci < 2; ci++) {
      var g = a1.querySelector('.m-camel').cloneNode(true);
      // via il cavaliere (testa, corpo, braccio), al suo posto i bagagli
      [].slice.call(g.children).slice(-3).forEach(function (n) { n.parentNode.removeChild(n); });
      g.insertAdjacentHTML('beforeend', '<rect x="-30" y="-98" width="22" height="20" rx="4" fill="#E9A23B"/><rect x="-4" y="-96" width="20" height="18" rx="4" fill="#F2E3C6" stroke="#6B4A30" stroke-width="2"/>');
      g.removeAttribute('class'); g.style.display = 'none';
      var wrap = document.createElementNS('http://www.w3.org/2000/svg', 'g'); wrap.appendChild(g);
      a1.parentNode.insertBefore(wrap, a1);
      camels.push(wrap);
    }
  }
  var markers = Array.prototype.slice.call(svg.querySelectorAll('.stop'));
  var waiting = Array.prototype.slice.call(svg.querySelectorAll('.waiting'));
  var reduce = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  var cur = 0, target = 0, phase = 0, active = -1, follow = false;
  var cam = { x: WW / 2, y: WH / 2, w: WW };

  function modeAt(len) {
    var m = C.modes[0][0];
    for (var i = 0; i < modeLen.length; i++) if (len >= modeLen[i] - 0.5) m = C.modes[i][0];
    return m;
  }

  function update() {
    var vh = window.innerHeight, mid = window.scrollY + vh * 0.55;
    var centers = steps.map(function (s) { var r = s.getBoundingClientRect(); return r.top + window.scrollY + r.height / 2; });
    var p = 0;
    if (mid <= centers[0]) p = 0;
    else if (mid >= centers[centers.length - 1]) p = centers.length - 1;
    else for (var i = 0; i < centers.length - 1; i++) if (mid < centers[i + 1]) { p = i + (mid - centers[i]) / (centers[i + 1] - centers[i]); break; }
    follow = mid > centers[0] - vh * 0.9 && mid < centers[centers.length - 1] + vh * 0.7;
    var k = Math.min(Math.floor(p), stopLen.length - 2), fr = Math.max(0, Math.min(1, p - k));
    target = stopLen[k] + (stopLen[k + 1] - stopLen[k]) * fr;
    var a = Math.round(p);
    if (a !== active) { active = a; steps.forEach(function (s, j) { s.classList.toggle('active', j === a); }); }
  }

  function point(len) { return route.getPointAtLength(Math.max(0, Math.min(total, len))); }
  function dirAt(len) { var a = point(len - 3), b = point(len + 3); return (b.x - a.x) < -0.3 ? -1 : 1; }
  function setLegs(g, sel, fn) { var ls = g.querySelectorAll(sel); for (var i = 0; i < ls.length; i++) ls[i].setAttribute('d', fn(i)); }
  var camelHips = [[-26, -28], [-14, -12], [18, 16], [28, 30]];

  function placeAgent(g, len, mode, sw, bob) {
    if (mode === 'camel') mode = 'walk';
    var p = point(len), d = dirAt(len);
    var walk = g.querySelector('.m-walk'), cml = g.querySelector('.m-camel');
    walk.style.display = mode === 'walk' ? '' : 'none';
    cml.style.display = mode === 'camel' ? '' : 'none';
    var sc = mode === 'camel' ? 0.6 : 0.5;
    g.setAttribute('transform', 'translate(' + p.x.toFixed(1) + ' ' + (p.y - bob).toFixed(1) + ') scale(' + (sc * d) + ' ' + sc + ')');
    if (mode === 'walk') setLegs(walk, '.leg', function (i) { return i === 0 ? 'M17 58 L' + (13 + sw) + ' 93.5' : 'M29 58 L' + (31 - sw) + ' 93.5'; });
    else setLegs(cml, '.cleg', function (i) { var h = camelHips[i], s = (i === 0 || i === 3) ? sw : -sw; return 'M' + h[0] + ' -48 L' + (h[1] + s) + ' 0'; });
  }

  var t0 = performance.now();
  function frame(now) {
    var t = (now - t0) / 1000;
    var delta = target - cur;
    cur = reduce ? target : cur + delta * 0.07;
    var moving = Math.abs(delta) > 0.6 && !reduce;
    var mode = modeAt(cur);
    if (moving) phase += 0.32;

    var single = mode === 'boat' || mode === 'balloon';
    a1.style.display = single ? 'none' : '';
    a2.style.display = single ? 'none' : '';
    if (boat) boat.style.display = mode === 'boat' ? '' : 'none';
    if (balloon) balloon.style.display = mode === 'balloon' ? '' : 'none';

    camels.forEach(function (w) { if (single) w.firstChild.style.display = 'none'; });
    var sw = moving ? Math.sin(phase) * 10 : 0;
    var bob = moving ? Math.abs(Math.sin(phase)) * 3 : 0;
    if (!single) {
      var gap = 42;
      var m2 = modeAt(Math.max(0, cur - gap));
      if (m2 !== 'walk' && m2 !== 'camel') m2 = 'walk';
      placeAgent(a1, cur, mode, sw, bob);
      placeAgent(a2, cur - gap, m2, moving ? -sw : 0, moving ? Math.abs(Math.cos(phase)) * 3 : 0);
      camels.forEach(function (w, i) {
        var len = cur - gap - 62 - i * 64, cm = w.firstChild;
        var show = modeAt(Math.max(0, len)) === 'camel' && len > 0;
        cm.style.display = show ? '' : 'none';
        if (!show) return;
        var p = point(len), d = dirAt(len), csw = moving ? Math.sin(phase * 0.7 + i) * 6 : 0;
        w.setAttribute('transform', 'translate(' + p.x.toFixed(1) + ' ' + p.y.toFixed(1) + ') scale(' + (0.42 * d) + ' 0.42)');
        setLegs(cm, '.cleg', function (k) { var h = camelHips[k], s2 = (k === 0 || k === 3) ? csw : -csw; return 'M' + h[0] + ' -48 L' + (h[1] + s2) + ' 0'; });
      });
    } else {
      var p = point(cur), d = dirAt(cur);
      if (mode === 'boat') {
        boat.setAttribute('transform', 'translate(' + p.x.toFixed(1) + ' ' + p.y.toFixed(1) + ') scale(' + (0.55 * d) + ' 0.55)');
        boat.querySelector('.rock').setAttribute('transform', 'rotate(' + (Math.sin(t * 1.6) * 2.2).toFixed(2) + ')');
        boat.querySelector('.wake').style.opacity = moving ? 0.9 : 0.25;
      } else {
        var lift = 10 + Math.sin(t * 1.3) * 6;
        balloon.setAttribute('transform', 'translate(' + p.x.toFixed(1) + ' ' + p.y.toFixed(1) + ') scale(0.62)');
        balloon.querySelector('.float').setAttribute('transform', 'translate(0 ' + (-lift * 2).toFixed(1) + ')');
      }
    }

    trail.style.strokeDasharray = cur.toFixed(1) + ' ' + (total + 10);
    markers.forEach(function (s, k) { s.classList.toggle('on', stopLen[k] <= cur + 3); s.classList.toggle('current', k === active && follow); });
    waiting.forEach(function (w) { var k = +w.getAttribute('data-hide-from'); w.style.opacity = cur >= modeLen[k] - 2 ? 0 : 1; });

    // camera follows the group, zooms out before and after the journey
    var W = window.innerWidth, H = window.innerHeight, mobile = W < 900;
    var tw, tx, ty;
    if (follow) {
      var fp = point(cur);
      tw = C.zoom * (mobile ? 0.62 : 1);
      var th = tw * H / W;
      tx = fp.x + (mobile ? 0 : tw * 0.2);
      ty = fp.y + (mobile ? th * 0.22 : 0);
    } else {
      tw = Math.max(WW, WH * W / H) * 1.02; tx = WW / 2; ty = WH / 2;
    }
    var thh = tw * H / W;
    var mx = follow ? tw * 0.4 : 40, my = follow ? thh * 0.3 : 40;
    if (tw < WW || follow) tx = Math.max(tw / 2 - mx, Math.min(WW - tw / 2 + mx, tx)); else tx = WW / 2;
    if (thh < WH || follow) ty = Math.max(thh / 2 - my, Math.min(WH - thh / 2 + my, ty)); else ty = WH / 2;
    var e = reduce ? 1 : 0.06;
    cam.x += (tx - cam.x) * e; cam.y += (ty - cam.y) * e; cam.w += (tw - cam.w) * e;
    var ch = cam.w * H / W;
    svg.setAttribute('viewBox', (cam.x - cam.w / 2).toFixed(1) + ' ' + (cam.y - ch / 2).toFixed(1) + ' ' + cam.w.toFixed(1) + ' ' + ch.toFixed(1));

    requestAnimationFrame(frame);
  }

  window.addEventListener('scroll', update, { passive: true });
  window.addEventListener('resize', update);
  update();
  requestAnimationFrame(frame);
})();
