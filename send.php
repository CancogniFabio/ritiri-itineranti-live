<?php
// Gestore unico dei moduli del sito Ritiri Itineranti.
// Gira su GreenGeeks (PHP) e invia ogni richiesta a ciao@ritiriitineranti.it.
// Sull'anteprima di GitHub Pages il PHP non funziona: lì i moduli mostrano l'indirizzo email.
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
    echo json_encode(['ok' => true]);
} else {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Invio non riuscito. Scrivici a ' . $TO . '.']);
}
