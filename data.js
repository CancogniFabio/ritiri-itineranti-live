/* Ritiri Itineranti: partenze reali, insegnanti e moduli del sito */
window.RI = {
  email: "ciao@ritiriitineranti.it",
  destinations: {
    marocco: { name: "Desert Way", country: "Marocco", page: "/marocco", img: "img/dunes.jpg", days: "7 giorni, 6 notti", price: 1400, early: 1250, earlyUntil: "31 dicembre 2026", color: "#B6492B", level: 4, levelName: "Avventura" },
    nilo: { name: "La Via del Nilo", country: "Egitto", page: "/nilo", img: "img/felucca.jpg", days: "8 giorni, 7 notti", price: null, color: "#17676A", level: 1, levelName: "Dolce" },
    cappadocia: { name: "Valli di Pietra", country: "Turchia", page: "/cappadocia", img: "img/balloons.jpg", days: "7 giorni, 6 notti", price: null, color: "#9B3F67", level: 3, levelName: "Attivo" }
  },
  teachers: [
    { id: "fabio", name: "Fabio", role: "Breathwork e regolazione del sistema nervoso", color: "#B6492B", initials: "F", base: "Lisbona", ig: "thebreathworkway", site: "https://thebreathworkway.com",
      themes: ["Breathwork", "Lavoro somatico"],
      bio: "Attraverso il respiro accompagna persone e gruppi a ritrovare il contatto con il corpo, con le emozioni e con la propria direzione. Prima di diventare facilitatore di breathwork faceva l'ingegnere strutturale, e oggi lavora in modo trauma-aware sul sistema nervoso e sul corpo emotivo. Ha vissuto tra Hong Kong, l'Australia e il Portogallo, e nei viaggi porta il respiro, il lavoro somatico e, inevitabilmente, la parte più avventurosa." },
    { id: "flavia", name: "Flavia", role: "Yoga, meditazione e Deep Coaching", color: "#17676A", initials: "F", base: "Marocco e Italia", ig: "_the_good_space_",
      themes: ["Yoga", "Meditazione", "Coaching"],
      bio: "Insegna yoga e meditazione ed è Deep Coach, e il suo talento è creare spazi in cui il sistema nervoso si sente al sicuro e ognuno torna ad ascoltare la propria saggezza interiore. Con il progetto The Good Space unisce le persone in modo autentico e vitale. Ha vissuto tra gli Stati Uniti, la Spagna e il Marocco, e nei viaggi guida la pratica del mattino e i cerchi di condivisione." }
  ],
  departures: [
    { id: "ma-apr27", period: "Aprile 2027", dest: "marocco", sort: 202704, date: "21-27 aprile 2027", start: "2027-04-21", end: "2027-04-27", status: "open", teachers: ["fabio", "flavia"] },
    { id: "ma-ott27", period: "Ottobre 2027", dest: "marocco", sort: 202710, date: "21-27 ottobre 2027", start: "2027-10-21", end: "2027-10-27", status: "open", img: "https://innertravels.world/wp-content/uploads/2026/07/DSC2994-1536x1024.jpg", teachers: ["fabio", "flavia"] },
    { id: "ni-nov27", period: "Novembre 2027", dest: "nilo", sort: 202711, date: "Novembre 2027", status: "tba", teachers: ["fabio", "flavia"] },
    { id: "ni-feb28", period: "Febbraio 2028", dest: "nilo", sort: 202802, date: "Febbraio 2028", status: "tba", img: "img/karnak1.jpg", teachers: ["fabio", "flavia"] },
    { id: "ca-2028", period: "2028", dest: "cappadocia", sort: 202809, date: "Nel 2028", status: "soon", teachers: ["fabio", "flavia"] }
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
  var BF = Date.now() < new Date('2026-12-04T23:59:59+01:00').getTime();
  function bfOn(d) { return BF && d.dest === 'marocco' && d.status === 'open'; }
  function bfLink(d) { return '/black-friday?partenza=' + encodeURIComponent(d.date) + '#sconto'; }
  var BFPRICE = '<s class="old-price">1.400 €</s> 1.100 €<small class="eb">con lo sconto Black Friday, fino al 4 dicembre</small>';
  var LABEL = { open: 'Iscrizioni aperte', tba: 'Date esatte in arrivo', soon: 'In preparazione' };
  function status(d) { return '<div class="go ' + (d.status === 'open' ? 'ok' : 'wait') + '"><span class="go-label">' + (d.status === 'open' ? '✓ ' : '') + LABEL[d.status] + '</span></div>'; }
  function guides(d, size) {
    var ts = d.teachers.map(teacher);
    return '<div class="dep-fac"><span class="avatars">' + ts.map(function (t) { return avatar(t, size); }).join('') + '</span><div><strong>con ' + ts.map(function (t) { return t.name; }).join(' e ') + '</strong><span>Breathwork, yoga e meditazione</span></div></div>';
  }
  function depCard(d) {
    var ds = D.destinations[d.dest];
    var bf = bfOn(d);
    return '<a class="dep-card" href="' + (bf ? ds.page : ds.page + '#date') + '" style="--c:' + ds.color + '">' +
      '<div class="dep-img"><img src="' + (d.img || ds.img) + '" alt="' + ds.country + ' · ' + ds.name + '" loading="lazy"><span class="dep-country">' + ds.country + '</span></div>' +
      '<div class="dep-body"><span class="dep-date">' + d.date + '</span><h3>' + ds.country + ' · ' + ds.name + '</h3>' +
      '<span class="dep-trip">' + ds.days + '</span>' + level(ds) + guides(d, 34) +
      status(d) + '<div class="dep-foot"><span class="dep-price">' + (bf ? BFPRICE : price(ds)) + '</span><span class="dep-seats">' + (bf ? 'Scopri il viaggio →' : d.status === 'open' ? 'Sali a bordo →' : 'Avvisami →') + '</span></div></div></a>';
  }
  window.RIrender = { teacher: teacher, eur: eur, avatar: avatar, depCard: depCard, dots: dots };

  // ---------- home: partenze con filtri collegati ----------
  var grid = document.getElementById('dep-grid');
  if (grid) {
    var all = D.departures.slice().sort(function (a, b) { return a.sort - b.sort; });
    function themesOf(d) { var o = []; d.teachers.forEach(function (id) { teacher(id).themes.forEach(function (t) { if (o.indexOf(t) < 0) o.push(t); }); }); return o; }
    var sel = { dest: document.getElementById('f-dest'), period: document.getElementById('f-period'), theme: document.getElementById('f-theme'), fac: document.getElementById('f-fac') };
    var ALL = { dest: 'Tutte', period: 'Qualsiasi', theme: 'Tutte', fac: 'Tutti' };
    var options = { dest: [], period: [], theme: [], fac: [] };
    all.forEach(function (d) {
      if (options.dest.indexOf(d.dest) < 0) options.dest.push(d.dest);
      if (options.period.indexOf(d.period) < 0) options.period.push(d.period);
      themesOf(d).forEach(function (t) { if (options.theme.indexOf(t) < 0) options.theme.push(t); });
      d.teachers.forEach(function (t) { if (options.fac.indexOf(t) < 0) options.fac.push(t); });
    });
    function has(d, k, v) {
      if (k === 'dest') return d.dest === v;
      if (k === 'period') return d.period === v;
      if (k === 'theme') return themesOf(d).indexOf(v) >= 0;
      return d.teachers.indexOf(v) >= 0;
    }
    function label(k, v) { if (k === 'dest') return D.destinations[v].country + ' · ' + D.destinations[v].name; if (k === 'fac') return teacher(v).name; return v; }
    function ok(d, skip) { return Object.keys(sel).every(function (k) { return k === skip || !sel[k] || !sel[k].value || has(d, k, sel[k].value); }); }
    var hint = document.getElementById('search-hint'), count = document.getElementById('dep-count'), reset = document.getElementById('f-reset');
    function run() {
      Object.keys(sel).forEach(function (k) {
        if (!sel[k]) return;
        var cur = sel[k].value, html = '<option value="">' + ALL[k] + '</option>';
        options[k].forEach(function (v) {
          var n = all.filter(function (d) { return ok(d, k) && has(d, k, v); }).length;
          html += '<option value="' + v + '"' + (n ? '' : ' disabled') + (v === cur ? ' selected' : '') + '>' + label(k, v) + (n ? '' : ' · nessuna data') + '</option>';
        });
        sel[k].innerHTML = html;
      });
      var list = all.filter(function (d) { return ok(d, null); });
      grid.innerHTML = list.length ? list.map(depCard).join('') : '<p class="empty">Nessuna partenza con questi filtri. Prova a toglierne uno.</p>';
      if (count) count.textContent = list.length + (list.length === 1 ? ' partenza' : ' partenze');
      var active = Object.keys(sel).some(function (k) { return sel[k] && sel[k].value; });
      if (reset) reset.hidden = !active;
      if (hint) hint.textContent = active ? (list.length === 1 ? 'Abbiamo trovato 1 partenza.' : list.length ? 'Abbiamo trovato ' + list.length + ' partenze.' : 'Nessuna partenza con questi filtri.') : '';
    }
    Object.keys(sel).forEach(function (k) { if (sel[k]) sel[k].addEventListener('change', run); });
    if (reset) reset.addEventListener('click', function () { Object.keys(sel).forEach(function (k) { if (sel[k]) sel[k].value = ''; }); run(); });
    run();
  }

  // ---------- trip page: departures ----------
  var tripDeps = document.getElementById('trip-deps');
  if (tripDeps) {
    var dest = tripDeps.getAttribute('data-dest'), ds = D.destinations[dest];
    tripDeps.innerHTML = D.departures.filter(function (d) { return d.dest === dest; }).sort(function (a, b) { return a.sort - b.sort; }).map(function (d) {
      var open = d.status === 'open';
      return '<article class="dep-tile"><span class="dep-date">' + d.date + '</span>' + guides(d, 44) +
        '<span class="dep-theme">' + ds.days + '</span>' + status(d) +
        '<div class="dep-foot"><span class="dep-price">' + (bfOn(d) ? BFPRICE : price(ds)) + '</span></div>' +
        (open ? '<a class="btn accent" href="/prenota?p=' + d.id + '">Prenota ora</a>' : '<button class="btn accent" type="button" data-book="' + d.id + '">Avvisami quando apre</button>') + '</article>';
    }).join('');
    if (BF && dest === 'marocco') tripDeps.insertAdjacentHTML('beforebegin', '<p class="bf-trip-note"><strong>Black Friday:</strong> fino al 4 dicembre il Desert Way costa 1.100 € invece di 1.400 €. Lasci i tuoi dati e ricevi il codice e la guida completa del viaggio, poi prenoti una consulenza gratuita oppure blocchi direttamente il posto. <a href="/black-friday#sconto">Ottieni lo sconto →</a></p>');
  }

  // ---------- finestre generiche: data-open="id" ----------
  document.addEventListener('click', function (e) {
    var o = e.target.closest('[data-open]');
    if (o) { var m = document.getElementById(o.getAttribute('data-open')); if (m) { e.preventDefault(); m.hidden = false; var i = m.querySelector('input:not([type=hidden]):not([tabindex])'); if (i) i.focus(); } }
    var mm = e.target.closest('.modal');
    if (mm && mm.id !== 'book-modal' && mm.id !== 'brochure-modal' && (e.target === mm || e.target.closest('[data-close]'))) mm.hidden = true;
  });
  document.addEventListener('keydown', function (e) { if (e.key === 'Escape') [].forEach.call(document.querySelectorAll('.modal'), function (m) { m.hidden = true; }); });

  // ---------- brochure modal ----------
  var bmodal = document.getElementById('brochure-modal');
  if (bmodal) {
    document.addEventListener('click', function (e) {
      if (e.target.closest('[data-brochure]')) { e.preventDefault(); bmodal.hidden = false; var i = bmodal.querySelector('input:not([type=hidden]):not([tabindex])'); if (i) i.focus(); }
      if (e.target.closest('#brochure-modal [data-close]') || e.target === bmodal) bmodal.hidden = true;
    });
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape') bmodal.hidden = true; });
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
        eyebrow.textContent = d.status === 'open' ? 'Sali a bordo' : 'Avvisami quando apre';
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
      .then(function (j) {
        form._resp = j;
        form.hidden = true;
        var done = form.parentNode.querySelector('.form-done');
        if (done) {
          done.hidden = false;
          // porta il messaggio di conferma al centro dello schermo (i moduli lunghi lo lascerebbero fuori vista)
          if (!form.closest('.modal')) {
            done.setAttribute('tabindex', '-1');
            setTimeout(function () { done.scrollIntoView({ behavior: 'smooth', block: 'center' }); done.focus({ preventScroll: true }); }, 30);
          }
        }
        document.dispatchEvent(new CustomEvent('ri:sent', { detail: form }));
        if (window._paq) _paq.push(['trackEvent', 'Modulo', 'Inviato', form.getAttribute('data-form') || 'Contatto']);
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

/* Prefisso internazionale: accanto a ogni campo telefono compare la tendina dei paesi (Italia già scelta).
   Il server unisce prefisso e numero e toglie un eventuale prefisso ripetuto nel campo. */
(function () {
  var P = [["Italia", "39"], ["Svizzera", "41"], ["San Marino", "378"], ["Portogallo", "351"], ["Spagna", "34"], ["Francia", "33"], ["Germania", "49"], ["Regno Unito", "44"], ["Austria", "43"], ["──────────", ""], ["Afghanistan", "93"], ["Albania", "355"], ["Algeria", "213"], ["Andorra", "376"], ["Angola", "244"], ["Arabia Saudita", "966"], ["Argentina", "54"], ["Armenia", "374"], ["Australia", "61"], ["Azerbaigian", "994"], ["Bahrein", "973"], ["Bangladesh", "880"], ["Belgio", "32"], ["Bielorussia", "375"], ["Bolivia", "591"], ["Bosnia ed Erzegovina", "387"], ["Brasile", "55"], ["Bulgaria", "359"], ["Cambogia", "855"], ["Camerun", "237"], ["Canada", "1"], ["Capo Verde", "238"], ["Cechia", "420"], ["Cile", "56"], ["Cina", "86"], ["Cipro", "357"], ["Colombia", "57"], ["Corea del Sud", "82"], ["Costa d'Avorio", "225"], ["Costa Rica", "506"], ["Croazia", "385"], ["Cuba", "53"], ["Danimarca", "45"], ["Ecuador", "593"], ["Egitto", "20"], ["El Salvador", "503"], ["Emirati Arabi Uniti", "971"], ["Estonia", "372"], ["Etiopia", "251"], ["Filippine", "63"], ["Finlandia", "358"], ["Georgia", "995"], ["Ghana", "233"], ["Giamaica", "1876"], ["Giappone", "81"], ["Giordania", "962"], ["Grecia", "30"], ["Guatemala", "502"], ["Honduras", "504"], ["Hong Kong", "852"], ["India", "91"], ["Indonesia", "62"], ["Iran", "98"], ["Iraq", "964"], ["Irlanda", "353"], ["Islanda", "354"], ["Israele", "972"], ["Kazakistan", "7"], ["Kenya", "254"], ["Kirghizistan", "996"], ["Kosovo", "383"], ["Kuwait", "965"], ["Lettonia", "371"], ["Libano", "961"], ["Libia", "218"], ["Liechtenstein", "423"], ["Lituania", "370"], ["Lussemburgo", "352"], ["Macedonia del Nord", "389"], ["Madagascar", "261"], ["Malaysia", "60"], ["Maldive", "960"], ["Malta", "356"], ["Marocco", "212"], ["Mauritius", "230"], ["Messico", "52"], ["Moldavia", "373"], ["Monaco", "377"], ["Mongolia", "976"], ["Montenegro", "382"], ["Mozambico", "258"], ["Namibia", "264"], ["Nepal", "977"], ["Nicaragua", "505"], ["Nigeria", "234"], ["Norvegia", "47"], ["Nuova Zelanda", "64"], ["Oman", "968"], ["Paesi Bassi", "31"], ["Pakistan", "92"], ["Panama", "507"], ["Paraguay", "595"], ["Perù", "51"], ["Polonia", "48"], ["Qatar", "974"], ["Repubblica Dominicana", "1809"], ["Romania", "40"], ["Russia", "7"], ["Senegal", "221"], ["Serbia", "381"], ["Singapore", "65"], ["Slovacchia", "421"], ["Slovenia", "386"], ["Sri Lanka", "94"], ["Stati Uniti", "1"], ["Sudafrica", "27"], ["Svezia", "46"], ["Tailandia", "66"], ["Taiwan", "886"], ["Tanzania", "255"], ["Tunisia", "216"], ["Turchia", "90"], ["Ucraina", "380"], ["Uganda", "256"], ["Ungheria", "36"], ["Uruguay", "598"], ["Uzbekistan", "998"], ["Venezuela", "58"], ["Vietnam", "84"]];
  function build(input) {
    if (input.dataset.prefix) return; input.dataset.prefix = '1';
    var sel = document.createElement('select');
    sel.name = 'prefisso'; sel.className = 'tel-prefix'; sel.setAttribute('aria-label', 'Prefisso internazionale');
    P.forEach(function (p) {
      var o = document.createElement('option');
      if (!p[1]) { o.disabled = true; o.textContent = p[0]; }
      else { o.value = p[1]; o.textContent = '+' + p[1] + ' ' + p[0]; }
      sel.appendChild(o);
    });
    sel.value = '39';
    var row = document.createElement('span'); row.className = 'tel-row';
    input.parentNode.insertBefore(row, input);
    row.appendChild(sel); row.appendChild(input);
    input.placeholder = '333 123 4567';
    input.removeAttribute('pattern');
  }
  function init() { document.querySelectorAll('input[type=tel][name=telefono]').forEach(build); }
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init); else init();
})();

/* Menu mobile */
(function () { var b = document.querySelector('.menu-btn'), n = document.getElementById('nav'); if (!b) return; b.addEventListener('click', function () { var o = n.classList.toggle('open'); b.setAttribute('aria-expanded', o); }); n.addEventListener('click', function (e) { if (e.target.tagName === 'A') { n.classList.remove('open'); b.setAttribute('aria-expanded', false); } }); })();

/* Messaggi di errore dei moduli in italiano */
(function () {
  function msg(el) {
    var v = el.validity;
    if (v.valueMissing) return el.type === 'checkbox' ? 'Spunta questa casella per continuare.' : (el.tagName === 'SELECT' ? 'Scegli una voce dall\'elenco.' : 'Compila questo campo.');
    if (v.typeMismatch && el.type === 'email') return 'Inserisci un indirizzo email valido, per esempio nome@email.it.';
    if (v.patternMismatch && el.type === 'tel') return 'Inserisci un numero di cellulare valido, per esempio +39 333 123 4567.';
    if (v.typeMismatch) return 'Controlla il formato di questo campo.';
    if (v.tooShort) return 'Il testo è troppo corto.';
    if (v.patternMismatch) return 'Controlla il formato di questo campo.';
    return '';
  }
  document.addEventListener('invalid', function (e) { var el = e.target; el.setCustomValidity(''); if (!el.validity.valid) el.setCustomValidity(msg(el)); }, true);
  document.addEventListener('input', function (e) { if (e.target.setCustomValidity) e.target.setCustomValidity(''); }, true);
  document.addEventListener('change', function (e) { if (e.target.setCustomValidity) e.target.setCustomValidity(''); }, true);
})();

// Gallerie: ogni foto appare quando entra nello schermo
(function () {
  if (!('IntersectionObserver' in window)) return;
  var figs = document.querySelectorAll('.gallery figure');
  if (!figs.length) return;
  document.documentElement.classList.add('reveal-on');
  var io = new IntersectionObserver(function (es) {
    es.forEach(function (e) { if (e.isIntersecting) { e.target.classList.add('in'); io.unobserve(e.target); } });
  }, { threshold: 0.15, rootMargin: '0px 0px -8% 0px' });
  figs.forEach(function (f, i) { f.style.transitionDelay = (i % 3) * 90 + 'ms'; io.observe(f); });
})();

// Sezioni: ogni blocco entra con l'animazione che gli si addice, sempre lenta e morbida
(function () {
  if (!('IntersectionObserver' in window)) return;
  if (window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
  // [selettore, variante]: rise = sale dal basso, left = entra da sinistra, pop = si ingrandisce appena, fade = solo dissolvenza
  var groups = [
    ['main section:not(.home-hero):not(.page-hero) .eyebrow, main section:not(.home-hero):not(.page-hero) h2, main section:not(.home-hero):not(.page-hero) .lead, .perte-end, .more-link', 'rise'],
    ['.mani-grid > div, .three > div, .how-step, .how > div, .dep-tile, .dep-card, .dest-card, .why > div, .card, .moment, .day', 'rise'],
    ['.trip', 'rise'],
    ['.pt', 'left'],
    ['.disc, .soon-list span', 'pop'],
    ['.lvl-strip, .lvl-strip-list li, .faq details, .first-story p, .quiz-teaser, .propose-band, .join, .partner-box, .soon, .bf-ingr > *, .bf-days > *', 'fade']
  ];
  var els = [];
  groups.forEach(function (g) {
    [].forEach.call(document.querySelectorAll(g[0]), function (e) {
      if (e.classList.contains('fx') || e.closest('.gallery, .site-header, .home-hero, .page-hero, .modal, form')) return;
      e.classList.add('fx', 'fx-' + g[1]); els.push(e);
    });
  });
  if (!els.length) return;
  document.documentElement.classList.add('fx-on');
  var io = new IntersectionObserver(function (es) {
    es.forEach(function (e) { if (e.isIntersecting) { e.target.classList.add('in'); io.unobserve(e.target); } });
  }, { threshold: 0.12, rootMargin: '0px 0px -6% 0px' });
  els.forEach(function (e) {
    var i = e.parentNode ? [].indexOf.call(e.parentNode.children, e) : 0;
    var step = e.classList.contains('fx-left') ? 110 : e.classList.contains('fx-pop') ? 60 : 90;
    e.style.transitionDelay = Math.min(i, 6) * step + 'ms';
    e.addEventListener('transitionend', function () { e.style.transitionDelay = ''; }, { once: true });
    io.observe(e);
  });
})();
