/* Black Milk — formulaire « Créatrices » (sans dépendance).
   Envoi : renseigner l’URL de réception dans data-endpoint="" ou action="" du <form id="bmc-form">.
   Tant que les deux sont vides, le formulaire affiche l’écran « Merci » sans rien envoyer (mode démo). */
(function () {
  var form = document.getElementById('bmc-form');
  if (!form) return;
  var step = 1, LAST = 4;
  var panels = form.querySelectorAll('[data-step]');
  var ladder = form.querySelectorAll('.bmc-ladder li');
  var next = document.getElementById('bmc-next'), back = document.getElementById('bmc-back');
  var counter = document.getElementById('bmc-counter');
  var LABELS = { next: 'Continuer', send: 'Envoyer ma candidature', sending: 'Envoi…' };
  var MSG = {
    valueMissing: 'Merci de remplir ce champ.', typeMismatch: 'Le format ne semble pas correct.',
    patternMismatch: 'Le format ne semble pas correct.', tooShort: 'Encore quelques caractères, s’il vous plaît.',
    consent: 'Merci d’accepter le traitement de vos données pour envoyer votre candidature.',
    group: 'Merci de choisir au moins une option.'
  };

  function panel(n) { return form.querySelector('[data-step="' + n + '"]'); }
  function isMinor() { var r = form.querySelector('input[name="majeure"]:checked'); return r && r.value === 'Non'; }

  function clearErrors(p) {
    p.querySelectorAll('.bmc-error').forEach(function (e) { e.remove(); });
    p.querySelectorAll('.is-invalid').forEach(function (e) { e.classList.remove('is-invalid'); });
  }
  function addError(el, text) {
    var holder = el.closest('.bmc-field, .bmc-q, .bmc-check') || el.parentNode;
    holder.classList.add('is-invalid');
    var s = document.createElement('span'); s.className = 'bmc-error'; s.textContent = text;
    holder.appendChild(s);
  }
  function validate(n) {
    var p = panel(n), ok = true, first = null;
    clearErrors(p);
    p.querySelectorAll('input:not([type=radio]):not([type=checkbox]):not([type=file]), textarea').forEach(function (el) {
      if (!el.checkValidity()) {
        var v = el.validity, m = v.valueMissing ? MSG.valueMissing : v.tooShort ? MSG.tooShort : v.typeMismatch ? MSG.typeMismatch : MSG.patternMismatch;
        addError(el, m); ok = false; first = first || el;
      }
    });
    p.querySelectorAll('.bmc-q[data-min]').forEach(function (g) {
      if (!g.querySelector('input:checked')) { addError(g.querySelector('input'), MSG.group); ok = false; first = first || g.querySelector('input'); }
    });
    var c = p.querySelector('input[name="consentement_rgpd"]');
    if (c && !c.checked) { addError(c, MSG.consent); ok = false; first = first || c; }
    if (first) first.focus();
    return ok;
  }

  function render() {
    panels.forEach(function (p) { p.hidden = +p.getAttribute('data-step') !== step; });
    ladder.forEach(function (li, i) { li.classList.toggle('is-done', i < step); });
    form.querySelector('.bmc-ladder').hidden = step > LAST;
    counter.textContent = step > LAST ? 'Candidature envoyée' : 'Étape ' + step + ' sur 4';
    back.hidden = !(step > 1 && step <= LAST);
    next.hidden = step > LAST;
    next.textContent = step === LAST ? LABELS.send : LABELS.next;
    document.getElementById('bmc-minor').hidden = !(step === 1 && isMinor());
    next.disabled = step === 1 && isMinor();
  }
  function go(n) {
    step = n; render();
    var top = form.getBoundingClientRect().top + window.pageYOffset - 24;
    if (window.pageYOffset > top) window.scrollTo({ top: top, behavior: 'smooth' });
  }

  function submit() {
    var endpoint = form.getAttribute('data-endpoint') || form.getAttribute('action');
    var done = function () {
      var fn = form.querySelector('input[name="prenom"]').value.trim();
      if (fn) document.getElementById('bmc-name').textContent = fn;
      go(LAST + 1); panel(LAST + 1).focus();
    };
    if (form.querySelector('input[name="website"]').value) return done(); // robot
    if (!endpoint) return done(); // mode démo
    next.disabled = true; next.textContent = LABELS.sending;
    document.getElementById('bmc-send-error').hidden = true;
    fetch(endpoint, { method: 'POST', body: new FormData(form), headers: { 'Accept': 'application/json' } })
      .then(function (r) { if (!r.ok) throw new Error(r.status); done(); })
      .catch(function () { document.getElementById('bmc-send-error').hidden = false; next.disabled = false; next.textContent = LABELS.send; });
  }

  next.addEventListener('click', function () {
    if (!validate(step)) return;
    if (step < LAST) go(step + 1); else submit();
  });
  back.addEventListener('click', function () { go(step - 1); });
  form.addEventListener('change', function (e) { if (e.target.name === 'majeure') render(); });
  form.addEventListener('submit', function (e) { e.preventDefault(); next.click(); });

  var motiv = form.querySelector('textarea[name="motivation"]'), cnt = document.getElementById('bmc-count');
  motiv.addEventListener('input', function () { cnt.textContent = motiv.value.length + ' / 600'; });
  var files = form.querySelector('input[type=file]'), fl = document.getElementById('bmc-files');
  files.addEventListener('change', function () {
    var bad = Array.prototype.some.call(files.files, function (f) { return f.size > 10 * 1024 * 1024; });
    if (files.files.length > 3 || bad) { files.value = ''; fl.textContent = '3 fichiers maximum, 10 Mo chacun.'; return; }
    fl.textContent = Array.prototype.map.call(files.files, function (f) { return f.name; }).join(', ');
  });
  render();
})();
