<?php
// Gestore unico dei moduli del sito Ritiri Itineranti.
// Gira su GreenGeeks (PHP) e invia ogni richiesta a ciao@ritiriitineranti.it.
// Sull'anteprima di GitHub Pages il PHP non funziona: lì i moduli mostrano l'indirizzo email.
require __DIR__ . '/lib-brevo.php';
header('Content-Type: application/json; charset=utf-8');

$TO   = 'ciao@ritiriitineranti.it';
$FROM = 'Sito Ritiri Itineranti <no-reply@ritiriitineranti.it>';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Metodo non consentito.']);
    exit;
}

// Solo dal nostro sito: blocca invii automatici da altri domini.
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if ($origin !== '' && !preg_match('#^https://(www\.)?ritiriitineranti\.it$#', $origin)) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'Richiesta non valida.']);
    exit;
}

// Limite di invii: al massimo 6 moduli ogni 10 minuti dallo stesso indirizzo IP, contro spam e abusi.
// I contatori stanno fuori dal sito e contengono solo un'impronta dell'IP, mai l'IP in chiaro.
$rlDir = dirname(__DIR__, 2) . '/.ritiri-ratelimit';
if (!is_dir($rlDir)) @mkdir($rlDir, 0700, true);
$rlFile = $rlDir . '/' . hash('sha256', ($_SERVER['REMOTE_ADDR'] ?? '') . date('Y-m-d')) . '.json';
$hits = is_file($rlFile) ? (array)json_decode((string)@file_get_contents($rlFile), true) : [];
$hits = array_values(array_filter($hits, fn($t) => $t > time() - 600));
if (count($hits) >= 6) {
    http_response_code(429);
    echo json_encode(['ok' => false, 'error' => 'Troppi invii in poco tempo. Riprova tra qualche minuto o scrivici a ' . $TO . '.']);
    exit;
}
$hits[] = time();
@file_put_contents($rlFile, json_encode($hits), LOCK_EX);
foreach ((array)@glob($rlDir . '/*.json') as $old) { if (is_file($old) && filemtime($old) < time() - 86400) @unlink($old); }

// Honeypot: i visitatori veri non compilano mai questo campo
if (!empty($_POST['website'])) {
    echo json_encode(['ok' => true]);
    exit;
}

function clean($v, $max) {
    $v = trim((string)$v);
    $v = str_replace(["\r", "\n", "\0"], ' ', $v);
    return mb_substr($v, 0, $max);
}

$email = clean($_POST['email'] ?? '', 160);
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'error' => 'Controlla l\'indirizzo email e riprova.']);
    exit;
}

$tipo = clean($_POST['tipo'] ?? 'Contatto', 60);

// Telefono: unisce il prefisso scelto nella tendina al numero e lo porta nel formato +39XXXXXXXXXX,
// che è quello che Brevo accetta per WhatsApp. Da qui in poi tutto il codice usa il numero pulito.
if (isset($_POST['telefono']) && trim((string)$_POST['telefono']) !== '') {
    $_POST['telefono'] = ri_phone((string)($_POST['prefisso'] ?? '39'), (string)$_POST['telefono']);
}
unset($_POST['prefisso']);
if (stripos($tipo, 'Black Friday') === 0 && time() > strtotime('2026-12-04 23:59:59 Europe/Rome')) {
    http_response_code(410);
    echo json_encode(['ok' => false, 'error' => 'L\'offerta Black Friday si è chiusa il 4 dicembre. Scrivici a ciao@ritiriitineranti.it.']);
    exit;
}
if (stripos($tipo, 'Black Friday') === 0 && strlen(preg_replace('/\D/', '', $_POST['telefono'] ?? '')) < 8) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'error' => 'Inserisci il tuo numero di cellulare, così possiamo chiamarti.']);
    exit;
}
$skip = ['website'];
$lines = [];
foreach ($_POST as $k => $v) {
    if (in_array($k, $skip, true) || is_array($v)) continue;
    $label = ucfirst(str_replace('_', ' ', clean($k, 40)));
    $val = trim(str_replace("\0", '', mb_substr((string)$v, 0, 4000)));
    if ($val === '') continue;
    $lines[] = $label . ': ' . $val;
}
$lines[] = 'Data: ' . date('d/m/Y H:i');

$subject = mb_encode_mimeheader('Sito Ritiri Itineranti · ' . $tipo, 'UTF-8');
$body    = implode("\n", $lines) . "\n";
$headers = [
    'From: ' . $FROM,
    'Reply-To: ' . $email,
    'MIME-Version: 1.0',
    'Content-Type: text/plain; charset=UTF-8',
];

// Candidatura facilitatore: email con scheda PDF, conferma al candidato e lista Brevo dei facilitatori.
if ($tipo === 'Candidatura facilitatore') {
    require __DIR__ . '/lib/facilitatore.php';
    if (ri_facilitatore($TO, $FROM, $email)) {
        echo json_encode(['ok' => true]);
    } else {
        http_response_code(500);
        echo json_encode(['ok' => false, 'error' => 'Invio non riuscito. Scrivici a ' . $TO . '.']);
    }
    exit;
}

if (mail($TO, $subject, $body, implode("\r\n", $headers))) {
    if (stripos($tipo, 'Black Friday') === 0) ri_black_friday($email, $FROM);
    if (stripos($tipo, 'Quiz') === 0) ri_quiz($email);
    if ($tipo === 'Iscrizione community' && !empty($_POST['consenso_email'])) ri_brevo_upsert($email, ['FIRSTNAME' => clean($_POST['nome'] ?? '', 60), 'LASTNAME' => clean($_POST['cognome'] ?? '', 60), 'WHATSAPP' => clean($_POST['telefono'] ?? '', 30), 'NEWSLETTER' => true], ri_community_list() ?: false);
    if (stripos($tipo, 'Brochure') === 0) { require_once __DIR__ . '/lib/viaggio.php'; ri_brochure($email, $TO); }
    if (stripos($tipo, 'Prenotazione') === 0) { require_once __DIR__ . '/lib/viaggio.php'; echo json_encode(['ok' => true] + (array)ri_prenotazione($email, $TO)); exit; }
    if (stripos($tipo, 'Avvisami') === 0) ri_avvisami($email, trim(substr($tipo, strpos($tipo, '·') !== false ? strpos($tipo, '·') + 2 : 8)));
    echo json_encode(['ok' => true]);
} else {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Invio non riuscito. Scrivici a ' . $TO . '.']);
}


// Avvisami: chi vuole sapere quando aprono le date di un viaggio entra nella lista generale su Brevo.
function ri_avvisami($email, $dest) {
    $nome = clean($_POST['nome'] ?? '', 80);
    $parts = preg_split('/\s+/', trim($nome), 2);
    $tel = clean($_POST['telefono'] ?? '', 40);
    $c = ri_config();
    $attrs = ['FIRSTNAME' => $parts[0], 'LASTNAME' => $parts[1] ?? '', 'DESTINAZIONE_INTERESSE' => $dest, 'NEWSLETTER' => true];
    if ($tel !== '') $attrs['WHATSAPP'] = $tel;
    ri_brevo_upsert($email, $attrs, ri_community_list() ?: false);
    // Email di conferma con la foto della destinazione (modello Brevo "Avvisami · Conferma iscrizione").
    $D = [
        'nilo'       => ['la Via del Nilo', 'il Nilo', '/nilo', 'avvisami-egitto.jpg', 'Le statue colossali del tempio di Abu Simbel', '⛵'],
        'cappadocia' => ['la Cappadocia', 'la Cappadocia', '/cappadocia', 'avvisami-cappadocia-mongolfiere.jpg', "Mongolfiere all'alba sopra le valli della Cappadocia", '🎈'],
    ];
    $k = strtolower(trim($dest));
    if (isset($D[$k])) {
        [$viaggio, $dst, $path, $img, $alt, $emoji] = $D[$k];
        ri_brevo_template($c['avvisami_template'] ?? 13, $email, $nome, [
            'NOME' => $parts[0], 'VIAGGIO' => $viaggio, 'DEST' => $dst, 'EMOJI' => $emoji,
            'LINK' => 'https://ritiriitineranti.it' . $path,
            'IMG' => 'https://ritiriitineranti.it/img/email/' . $img, 'ALT' => $alt,
        ]);
    }
}

// Quiz: email con il profilo e contatto nella lista Quiz Viaggiatore su Brevo.
function ri_quiz($email) {
    $P = [
        'Esploratore' => ["L'Esploratore", "Hai bisogno di spazio, fatica buona e cieli enormi. Il silenzio lo trovi camminando, e le pratiche ti arrivano meglio dopo una giornata sulle dune.", 'Marocco · Desert Way', 'Impegno fisico 4 su 5', 'https://ritiriitineranti.it/marocco'],
        'Contemplativo' => ['Il Contemplativo', "Ti ricarichi rallentando. Ti serve un ritmo lento, comodità la sera e tanto tempo per ascoltarti, mentre il paesaggio scorre da solo.", 'Egitto · La Via del Nilo', 'Impegno fisico 1 su 5', 'https://ritiriitineranti.it/nilo'],
        'Cercatore' => ['Il Cercatore', "Sei curioso di tutto, fuori e dentro di te. Ti piacciono le storie dei posti, le persone che ci vivono e le domande che restano anche dopo.", 'Turchia · Valli di Pietra', 'Impegno fisico 3 su 5 · in arrivo nel 2028', 'https://ritiriitineranti.it/cappadocia'],
        'Primo Passo' => ['Il Primo Passo', "Vorresti prenderti del tempo da anni, ma una settimana lontano ti sembra tanto. Va benissimo così, e per iniziare ti consigliamo il viaggio più dolce che abbiamo, sul Nilo, dove la sera c'è sempre un letto comodo.", 'Egitto · La Via del Nilo', 'Il nostro viaggio più dolce, impegno fisico 1 su 5', 'https://ritiriitineranti.it/nilo'],
    ];
    $k = clean($_POST['profilo'] ?? '', 40);
    if (!isset($P[$k])) $k = 'Primo Passo';
    [$titolo, $desc, $viaggio, $impegno, $link] = $P[$k];
    $nome = clean($_POST['nome'] ?? '', 80);
    $first = trim(explode(' ', $nome)[0]);
    $c = ri_config();
    ri_brevo_template($c['quiz_template'] ?? 8, $email, $nome, ['NOME' => $first, 'PROFILO' => $titolo, 'DESCRIZIONE' => $desc, 'VIAGGIO' => $viaggio, 'IMPEGNO' => $impegno, 'LINK' => $link]);
    ri_brevo_upsert($email, ['FIRSTNAME' => $first, 'PROFILO_QUIZ' => $k, 'NEWSLETTER' => !empty($_POST['consenso_email'])], empty($_POST['consenso_email']) ? [$c['quiz_list'] ?? 8] : [$c['quiz_list'] ?? 8, ri_community_list()]);
}

// Black Friday: email con il codice a chi compila il modulo, e contatto su Brevo per i promemoria.
function ri_black_friday($email, $from) {
    $CODE = 'SAHARA300';
    $nome = clean($_POST['nome'] ?? '', 80);
    $first = trim(explode(' ', $nome)[0]);
    $dep  = clean($_POST['partenza'] ?? '', 60);
    $link = 'https://cal.com/ritiri-itineranti/blackfriday?name=' . rawurlencode($nome) . '&email=' . rawurlencode($email) . '&notes=' . rawurlencode('Codice ' . $CODE . ' · partenza: ' . $dep);
    $txt = "Ciao " . ($first ?: '') . ",\n\n"
         . "ecco il tuo codice Black Friday per il Desert Way: " . $CODE . "\n"
         . "Vale 300 € di sconto, quindi il viaggio ti costa 1.100 € invece di 1.400 €.\n\n"
         . "Qui trovi la guida completa del Desert Way:\nhttps://drive.google.com/file/d/128SIWOEsKJvmga7XMrIk9_DZYlDyMDYD/view?usp=sharing\n\n"
         . "Se vuoi conoscerci prima di decidere, prenota una consulenza gratuita di 20 minuti:\n" . $link . "\n\n"
         . "Se hai già deciso, rispondi a questa email e ti mandiamo i dati per bloccare il posto.\n\n"
         . "Il codice vale per chi lo ha richiesto entro il 4 dicembre 2026, anche se la consulenza cade qualche giorno dopo.\n\n"
         . "A presto,\nFabio e Flavia\nRitiri Itineranti\n";
    $parts = explode(' ', $nome, 2);
    // Prima salviamo il contatto, così l'email può usare i suoi dati (nome, email).
    $saved = ri_brevo_upsert($email, ['FIRSTNAME' => $parts[0], 'LASTNAME' => $parts[1] ?? '', 'WHATSAPP' => clean($_POST['telefono'] ?? '', 30), 'PARTENZA' => $dep, 'CODICE_BF' => $CODE, 'HA_PRENOTATO' => false, 'NEWSLETTER' => !empty($_POST['consenso'])], empty($_POST['consenso']) ? false : [ri_config()['brevo_list'] ?? 4, ri_community_list()]);
    // L'email con il codice (BF00) parte dal workflow di Brevo appena il contatto entra nella lista Black Friday.
    // Se il salvataggio non riesce, mandiamo comunque il codice con una email semplice.
    if (!$saved || empty($_POST['consenso'])) {
        $h = ['From: Fabio e Flavia · Ritiri Itineranti <ciao@ritiriitineranti.it>', 'Reply-To: ciao@ritiriitineranti.it', 'MIME-Version: 1.0', 'Content-Type: text/plain; charset=UTF-8'];
        @mail($email, mb_encode_mimeheader('Il tuo codice Black Friday: ' . $CODE, 'UTF-8'), $txt, implode("\r\n", $h));
    }
    // Il consenso (obbligatorio nel modulo) fa entrare il contatto nella lista Black Friday e nella community.
}


// Normalizza un numero di telefono: "+39" e "347 123 4567" diventano "+393471234567".
// Se il numero contiene già un prefisso (+, 00 o lo stesso prefisso della tendina) non lo ripete.
function ri_phone($prefix, $raw) {
    $prefix = preg_replace('/\D/', '', $prefix) ?: '39';
    $raw = trim($raw);
    $hasPlus = strpos($raw, '+') === 0;
    $d = preg_replace('/\D/', '', $raw);
    if ($d === '') return '';
    if ($hasPlus) return '+' . $d;
    if (strpos($d, '00') === 0) return '+' . substr($d, 2);
    // Prefisso già scritto nel numero (es. 393471234567 con Italia scelta): lo teniamo una volta sola.
    if (strpos($d, $prefix) === 0 && strlen($d) >= strlen($prefix) + 9) return '+' . $d;
    // Fuori dall'Italia lo 0 iniziale è il prefisso nazionale e va tolto (es. Regno Unito 07... diventa +447...).
    if ($prefix !== '39' && $prefix !== '378' && strpos($d, '0') === 0) $d = substr($d, 1);
    return '+' . $prefix . $d;
}
