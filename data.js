/* Ritiri Itineranti: partenze reali, insegnanti e moduli del sito */
window.RI = {
  email: "ciao@ritiriitineranti.it",
  destinations: {
    marocco: { name: "Desert Way", country: "Marocco", page: "marocco.html", img: "img/dunes.jpg", days: "7 giorni, 6 notti", price: 1400, early: 1200, earlyUntil: "31 dicembre 2026", max: 12, color: "#B6492B", level: 4, levelName: "Avventura" },
    nilo: { name: "La Via del Nilo", country: "Egitto", page: "nilo.html", img: "img/felucca.jpg", days: "8 giorni, 7 notti", price: null, max: 14, color: "#17676A", level: 1, levelName: "Dolce" },
    cappadocia: { name: "Valli di Pietra", country: "Turchia", page: "cappadocia.html", img: "img/balloons.jpg", days: "7 giorni, 6 notti", price: null, max: 12, color: "#9B3F67", level: 3, levelName: "Attivo" }
  },
  teachers: [
    { id: "fabio", name: "Fabio", role: "Breathwork e regolazione del sistema nervoso", color: "#B6492B", initials: "F", base: "Lisbona", ig: "thebreathworkway",
      themes: ["Breathwork", "Lavoro somatico"],
      bio: "Attraverso il respiro accompagna persone e gruppi a ritrovare il contatto con il corpo, con le emozioni e con la propria direzione. Prima di diventare insegnante di breathwork faceva l'ingegnere strutturale, e oggi lavora in modo trauma-aware sul sistema nervoso e sul corpo emotivo. Ha vissuto tra Hong Kong, l'Australia e il Portogallo, e nei viaggi porta il respiro, il lavoro somatico e, inevitabilmente, la parte più avventurosa." },
    { id: "flavia", name: "Flavia", role: "Yoga, meditazione e Deep Coaching", color: "#17676A", initials: "F", base: "Marocco e Italia", ig: "_the_good_space_",
      themes: ["Yoga", "Meditazione", "Coaching"],
      bio: "Insegna yoga e meditazione ed è Deep Coach, e il suo talento è creare spazi in cui il sistema nervoso si sente al sicuro e ognuno torna ad ascoltare la propria saggezza interiore. Con il progetto The Good Space unisce le persone in modo autentico e vitale. Ha vissuto tra gli Stati Uniti, la Spagna e il Marocco, e nei viaggi guida la pratica del mattino e i cerchi di condivisione." }
  ],
  departures: [
    { id: "ma-apr27", dest: "marocco", sort: 202704, date: "21-27 aprile 2027", start: "2027-04-21", end: "2027-04-27", status: "open", teachers: ["fabio", "flavia"] },
    { id: "ma-ott27", dest: "marocco", sort: 202710, date: "21-27 ottobre 2027", start: "2027-10-21", end: "2027-10-27", status: "open", img: "https://innertravels.world/wp-content/uploads/2026/07/DSC2994-1536x1024.jpg", teachers: ["fabio", "flavia"] },
    { id: "ni-nov27", dest: "nilo", sort: 202711, date: "Novembre 2027", status: "tba", teachers: ["fabio", "flavia"] },
    { id: "ni-feb28", dest: "nilo", sort: 202802, date: "Febbraio 2028", status: "tba", img: "img/karnak1.jpg", teachers: ["fabio", "flavia"] },
    { id: "ca-2028", dest: "cappadocia", sort: 202809, date: "Nel 2028", status: "soon", teachers: ["fabio", "flavia"] }
  ]
};

(function () {
  var D = window.RI;
  function teacher(id) { return D.teachers.filter(function (t) { return t.id === id; })[0]; }
  function eur(n) { return String(n).replace(/\B(?=(\d{3})+(?!\d))/g, '.') + ' €'; }
  function price(ds) { if (!ds.price) return 'Quota in arrivo'; if (ds.early) return '<s class="old-price">' + eur(ds.price) + '</s> ' + eur(ds.early) + '<small class="eb">early bird fino al ' + ds.earlyUntil + '</small>'; return eur(ds.price); }
  function avatar(t, size) {
    return '<span class="avatar-sm" style="background:' + t.color + ';width:' + size + 'px;height:' + size + 'px;font-size:' + Math.round(size * 0.4) + 'px" aria-hidden="true">' + t.initials + '</span>';
  }
  function dots(n) { var o = ''; for (var i = 1; i <= 5; i++) o += '<i class="' + (i <= n ? 'on' : '') + '"></i>'; return '<span class="dots" aria-hidden="true">' + o + '</span>'; }
  function level(ds) { return '<span class="lvl">' + dots(ds.level) + '<span>Impegno fisico ' + ds.level + ' su 5</span></span>'; }
  var LABEL = { open: 'Iscrizioni aperte', tba: 'Date esatte in arrivo', soon: 'In preparazione' };
  function status(d) { return '<div class="go ' + (d.status === 'open' ? 'ok' : 'wait') + '"><span class="go-label">' + (d.status === 'open' ? '✓ ' : '') + LABEL[d.status] + '</span></div>'; }
  function guides(d, size) {
    var ts = d.teachers.map(teacher);
    return '<div class="dep-fac"><span class="avatars">' + ts.map(function (t) { return avatar(t, size); }).join('') + '</span><div><strong>con ' + ts.map(function (t) { return t.name; }).join(' e ') + '</strong><span>Breathwork, yoga e meditazione</span></div></div>';
  }
  function depCard(d) {
    var ds = D.destinations[d.dest];
    return '<a class="dep-card" href="' + ds.page + '#date" style="--c:' + ds.color + '">' +
      '<div class="dep-img"><img src="' + (d.img || ds.img) + '" alt="' + ds.country + ' · ' + ds.name + '" loading="lazy"><span class="dep-country">' + ds.country + '</span></div>' +
      '<div class="dep-body"><span class="dep-date">' + d.date + '</span><h3>' + ds.country + ' · ' + ds.name + '</h3>' +
      '<span class="dep-trip">' + ds.days + ' · massimo ' + ds.max + ' persone</span>' + level(ds) + guides(d, 34) +
      status(d) + '<div class="dep-foot"><span class="dep-price">' + price(ds) + '</span><span class="dep-seats">' + (d.status === 'open' ? 'Richiedi un posto →' : 'Avvisami →') + '</span></div></div></a>';
  }
  window.RIrender = { teacher: teacher, eur: eur, avatar: avatar, depCard: depCard, dots: dots };

  // ---------- home: all departures ----------
  var grid = document.getElementById('dep-grid');
  if (grid) {
    var list = D.departures.slice().sort(function (a, b) { return a.sort - b.sort; });
    grid.innerHTML = list.map(depCard).join('');
    var count = document.getElementById('dep-count');
    if (count) count.textContent = list.length + ' partenze';
  }

  // ---------- trip page: departures ----------
  var tripDeps = document.getElementById('trip-deps');
  if (tripDeps) {
    var dest = tripDeps.getAttribute('data-dest'), ds = D.destinations[dest];
    tripDeps.innerHTML = D.departures.filter(function (d) { return d.dest === dest; }).sort(function (a, b) { return a.sort - b.sort; }).map(function (d) {
      var open = d.status === 'open';
      return '<article class="dep-tile"><span class="dep-date">' + d.date + '</span>' + guides(d, 44) +
        '<span class="dep-theme">' + ds.days + ' · massimo ' + ds.max + ' persone</span>' + status(d) +
        '<div class="dep-foot"><span class="dep-price">' + price(ds) + '</span></div>' +
        '<button class="btn accent" type="button" data-book="' + d.id + '">' + (open ? 'Richiedi un posto' : 'Avvisami quando apre') + '</button></article>';
    }).join('');
  }

  // ---------- booking / waitlist modal ----------
  var modal = document.getElementById('book-modal');
  if (modal) {
    var mform = modal.querySelector('form'), title = modal.querySelector('.modal-title'), eyebrow = modal.querySelector('.eyebrow');
    document.addEventListener('click', function (e) {
      var b = e.target.closest('[data-book]');
      if (b) {
        var d = D.departures.filter(function (x) { return x.id === b.getAttribute('data-book'); })[0], dd = D.destinations[d.dest];
        var label = dd.country + ' · ' + dd.name + ' · ' + d.date;
        title.textContent = label;
        eyebrow.textContent = d.status === 'open' ? 'Richiedi un posto' : 'Avvisami quando apre';
        mform.querySelector('[name=partenza]').value = label;
        mform.querySelector('[name=tipo]').value = d.status === 'open' ? 'Richiesta posto' : 'Lista d\'attesa';
        RIforms.reset(mform);
        modal.hidden = false; mform.querySelector('input:not([type=hidden])').focus();
      }
      if (e.target.closest('[data-close]') || e.target === modal) modal.hidden = true;
    });
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape') modal.hidden = true; });
  }

  // ---------- teachers page ----------
  var net = document.getElementById('net-grid');
  if (net) {
    net.innerHTML = D.teachers.map(function (t) {
      return '<article class="fac-card" id="' + t.id + '">' + avatar(t, 76) + '<h3>' + t.name + ' <span class="badge">Fondatore' + (t.id === 'flavia' ? 'ssa' : '') + '</span></h3><span class="fac-role">' + t.role + '</span>' +
        '<p>' + t.bio + '</p><div class="fac-themes">' + t.themes.map(function (x) { return '<span>' + x + '</span>'; }).join('') + '</div>' +
        '<div class="fac-deps"><strong>Prossimi viaggi</strong>' + D.departures.filter(function (d) { return d.teachers.indexOf(t.id) > -1; }).map(function (d) { var ds = D.destinations[d.dest]; return '<a href="' + ds.page + '#date">' + ds.country + ' · ' + d.date + '</a>'; }).join('') +
        '<a href="https://www.instagram.com/' + t.ig + '/" rel="noopener" target="_blank">Instagram @' + t.ig + '</a></div></article>';
    }).join('');
  }
})();

/* Moduli: inviano a send.php (GreenGeeks). Ogni form ha data-form="tipo". */
window.RIforms = (function () {
  var EMAIL = window.RI ? window.RI.email : 'ciao@ritiriitineranti.it';
  function reset(form) {
    form.hidden = false;
    var done = form.parentNode.querySelector('.form-done'); if (done) done.hidden = true;
    var err = form.querySelector('.form-error'); if (err) err.remove();
    var btn = form.querySelector('button[type=submit]'); if (btn) btn.disabled = false;
  }
  function collect(form) {
    var fd = new FormData(form);
    if (!fd.get('tipo')) fd.set('tipo', form.getAttribute('data-form') || 'Contatto');
    fd.set('pagina', location.pathname);
    // checkbox multiple (es. focus) uniti in una riga
    var multi = {};
    form.querySelectorAll('input[type=checkbox][name]').forEach(function (c) { if (c.checked && c.name !== 'privacy') (multi[c.name] = multi[c.name] || []).push(c.value); });
    Object.keys(multi).forEach(function (k) { fd.set(k, multi[k].join(', ')); });
    return fd;
  }
  function submit(e) {
    var form = e.target.closest('form[data-form]'); if (!form) return;
    e.preventDefault();
    var btn = form.querySelector('button[type=submit]'); if (btn) btn.disabled = true;
    var old = form.querySelector('.form-error'); if (old) old.remove();
    fetch('send.php', { method: 'POST', body: collect(form), headers: { 'Accept': 'application/json' } })
      .then(function (r) { return r.json().then(function (j) { if (!r.ok || !j.ok) throw new Error(j.error || 'Errore'); return j; }); })
      .then(function () {
        form.hidden = true;
        var done = form.parentNode.querySelector('.form-done'); if (done) done.hidden = false;
        document.dispatchEvent(new CustomEvent('ri:sent', { detail: form }));
      })
      .catch(function () {
        if (btn) btn.disabled = false;
        var p = document.createElement('p'); p.className = 'form-error note';
        p.innerHTML = 'Non siamo riusciti a inviare il modulo. Scrivici direttamente a <a href="mailto:' + EMAIL + '">' + EMAIL + '</a> e ti rispondiamo noi.';
        form.appendChild(p);
      });
  }
  document.addEventListener('submit', submit);
  return { reset: reset };
})();

/* Menu mobile */
(function () { var b = document.querySelector('.menu-btn'), n = document.getElementById('nav'); if (!b) return; b.addEventListener('click', function () { var o = n.classList.toggle('open'); b.setAttribute('aria-expanded', o); }); n.addEventListener('click', function (e) { if (e.target.tagName === 'A') { n.classList.remove('open'); b.setAttribute('aria-expanded', false); } }); })();
