/* BLACK MILK — vachette 3D qui regarde depuis le bord de l'écran.
   Position fixe, suit le curseur, clignements + micro-mouvements de tête.
   Réglages: window.BM_MASCOT = { side:'right'|'left', width:'clamp(...)', bottom:'8vh', peek:0.26, z:40 } */
(function () {
  if (window.__bmMascot) return;
  window.__bmMascot = true;

  var CFG = Object.assign({
    side: 'right',
    width: 'clamp(112px, 12.5vw, 208px)',
    bottom: '7vh',
    peek: 0.26,
    z: 40
  }, window.BM_MASCOT || {});

  /* géométrie mesurée sur /img/mascot/mascot-cow.webp (520 × 706) */
  var R = function (p) { var k = 'r_' + p.replace(/[^a-z0-9]+/gi, '_').replace(/^_+|_+$/g, ''); return (window.__resources && window.__resources[k]) || p; };
  var BASE_W = 520, BASE_H = 706;
  var EYES = [
    { src: '/img/mascot/mascot-iris-l.webp', cx: 215.4, cy: 249.7, d: 82.4, lid: 'rgb(123,93,85)', mx: 4.6, my: 3.2, rx: 30, ry: 32 },
    { src: '/img/mascot/mascot-iris-r.webp', cx: 362.1, cy: 185.3, d: 77.2, lid: 'rgb(53,25,20)', mx: 4.6, my: 3.2, rx: 29, ry: 28 }
  ];

  function load(src) {
    return new Promise(function (res, rej) {
      var i = new Image();
      i.onload = function () { res(i); };
      i.onerror = rej;
      i.src = src;
    });
  }

  function build(base, irises) {
    var wrap = document.createElement('div');
    wrap.id = 'bm-mascot';
    wrap.setAttribute('aria-hidden', 'true');
    var offset = (CFG.peek * 100).toFixed(1);
    wrap.style.cssText = [
      'position:fixed',
      CFG.side + ':0',
      'bottom:' + CFG.bottom,
      'width:' + CFG.width,
      'z-index:' + CFG.z,
      'pointer-events:none',
      'will-change:transform',
      'transform:translateX(' + (CFG.side === 'right' ? '' : '-') + offset + '%)',
      'transition:opacity .6s ease',
      'opacity:0'
    ].join(';');

    var cv = document.createElement('canvas');
    var dpr = Math.min(window.devicePixelRatio || 1, 2.5);
    cv.width = Math.round(BASE_W * dpr);
    cv.height = Math.round(BASE_H * dpr);
    cv.style.cssText = 'display:block;width:100%;height:auto;' +
      (CFG.side === 'left' ? 'transform:scaleX(-1);' : '');
    wrap.appendChild(cv);
    document.body.appendChild(wrap);

    var ctx = cv.getContext('2d');
    ctx.scale(dpr, dpr);
    ctx.imageSmoothingQuality = 'high';

    /* état du regard */
    var tx = 0, ty = 0, gx = 0, gy = 0;      // cible / position lissée (−1..1)
    var pointer = false, blink = 0, blinkAt = performance.now() + 2600;
    var touch = !window.matchMedia('(hover: hover)').matches;

    function drawEye(e, i) {
      var pad = e.d / 2 + 8;
      ctx.save();
      ctx.beginPath();
      ctx.rect(e.cx - pad, e.cy - pad, pad * 2, pad * 2);
      ctx.clip();
      ctx.drawImage(base, 0, 0, BASE_W, BASE_H);

      var ox = gx * e.mx, oy = gy * e.my;
      ctx.drawImage(irises[i], e.cx - e.d / 2 + ox, e.cy - e.d / 2 + oy, e.d, e.d);

      if (blink > 0.01) {
        ctx.save();
        ctx.beginPath();
        ctx.ellipse(e.cx, e.cy, e.rx, e.ry, 0, 0, Math.PI * 2);
        ctx.clip();
        ctx.fillStyle = e.lid;
        var h = e.ry * 2.1 * blink;
        ctx.fillRect(e.cx - e.rx, e.cy - e.ry - 2, e.rx * 2, h);
        ctx.restore();
      }
      ctx.restore();
    }

    function frame(now) {
      /* dérive douce sur écran tactile / sans souris */
      if (touch || !pointer) {
        tx = Math.sin(now / 2600) * 0.55;
        ty = Math.sin(now / 3700 + 1.2) * 0.4;
      }
      gx += (tx - gx) * 0.085;
      gy += (ty - gy) * 0.085;

      /* clignement */
      if (now > blinkAt) {
        var p = (now - blinkAt) / 150;
        blink = p < 1 ? p : p < 2 ? 2 - p : 0;
        if (p >= 2) { blink = 0; blinkAt = now + 2400 + Math.random() * 4200; }
      }

      /* micro-mouvement de tête + oreilles (respiration) */
      var sway = Math.sin(now / 3200) * 0.7 + gx * 0.9;
      var rise = Math.sin(now / 2400) * 0.5;
      wrap.style.transform =
        'translateX(' + (CFG.side === 'right' ? '' : '-') + offset + '%) ' +
        'translateY(' + rise.toFixed(2) + 'px) rotate(' + sway.toFixed(2) + 'deg)';

      ctx.clearRect(0, 0, BASE_W, BASE_H);
      ctx.drawImage(base, 0, 0, BASE_W, BASE_H);
      EYES.forEach(drawEye);
      requestAnimationFrame(frame);
    }

    function aim(clientX, clientY) {
      var r = cv.getBoundingClientRect();
      var ex = r.left + r.width * 0.55, ey = r.top + r.height * 0.32;
      tx = Math.max(-1, Math.min(1, (clientX - ex) / (window.innerWidth * 0.55)));
      ty = Math.max(-1, Math.min(1, (clientY - ey) / (window.innerHeight * 0.55)));
      if (CFG.side === 'left') tx = -tx;
      pointer = true;
    }

    window.addEventListener('pointermove', function (ev) {
      if (ev.pointerType === 'touch') return;
      touch = false;
      aim(ev.clientX, ev.clientY);
    }, { passive: true });

    window.addEventListener('pointerleave', function () { pointer = false; }, { passive: true });

    requestAnimationFrame(frame);
    requestAnimationFrame(function () { wrap.style.opacity = '1'; });
  }

  function go() {
    Promise.all([load(R('/img/mascot/mascot-cow.webp'))].concat(EYES.map(function (e) { return load(R(e.src)); })))
      .then(function (imgs) { build(imgs[0], imgs.slice(1)); })
      .catch(function (err) { console.warn('[bm-mascot]', err); });
  }

  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', go, { once: true });
  else go();
})();
