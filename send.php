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
$skip = ['website', 'privacy'];
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

if (mail($TO, $subject, $body, implode("\r\n", $headers))) {
    if (stripos($tipo, 'Black Friday') === 0) ri_black_friday($email, $FROM);
    echo json_encode(['ok' => true]);
} else {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Invio non riuscito. Scrivici a ' . $TO . '.']);
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
         . "Il prossimo passo è una chiamata di 20 minuti con noi, in cui ci conosciamo e rispondiamo alle tue domande. Puoi prenotarla qui:\n" . $link . "\n\n"
         . "Il codice vale per chi lo ha richiesto entro il 4 dicembre 2026, anche se la chiamata cade qualche giorno dopo.\n\n"
         . "A presto,\nFabio e Flavia\nRitiri Itineranti\n";
    $h = ['From: Fabio e Flavia · Ritiri Itineranti <ciao@ritiriitineranti.it>', 'Reply-To: ciao@ritiriitineranti.it', 'MIME-Version: 1.0', 'Content-Type: text/plain; charset=UTF-8'];
    @mail($email, mb_encode_mimeheader('Il tuo codice Black Friday: ' . $CODE, 'UTF-8'), $txt, implode("\r\n", $h));
    if (!empty($_POST['marketing'])) {
        $parts = explode(' ', $nome, 2);
        ri_brevo_upsert($email, ['NOME' => $parts[0], 'COGNOME' => $parts[1] ?? '', 'WHATSAPP' => clean($_POST['telefono'] ?? '', 30), 'PARTENZA' => $dep, 'CODICE_BF' => $CODE, 'HA_PRENOTATO' => false]);
    }
}
