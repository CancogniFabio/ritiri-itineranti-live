<?php
// Cal.com chiama questo indirizzo quando qualcuno prenota la chiamata Black Friday.
// Segna il contatto su Brevo come "ha prenotato", così esce dalla sequenza dei promemoria.
// In Cal.com: Impostazioni > Sviluppatori > Webhook > https://ritiriitineranti.it/cal-webhook.php
// evento "Booking created", e come "Secret" la stessa frase di cal_secret nel file di configurazione.
require __DIR__ . '/lib-brevo.php';
header('Content-Type: application/json; charset=utf-8');
$raw = file_get_contents('php://input');
$c = ri_config();
if (!empty($c['cal_secret'])) {
    $sig = $_SERVER['HTTP_X_CAL_SIGNATURE_256'] ?? '';
    if (!hash_equals(hash_hmac('sha256', $raw, $c['cal_secret']), $sig)) {
        http_response_code(401); echo json_encode(['ok' => false]); exit;
    }
}
$d = json_decode($raw, true) ?: [];
$event = $d['triggerEvent'] ?? '';
$att = $d['payload']['attendees'][0] ?? [];
$email = $att['email'] ?? '';
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) { echo json_encode(['ok' => true, 'skip' => 'no email']); exit; }
$start = $d['payload']['startTime'] ?? '';
if ($event === 'BOOKING_CREATED' || $event === 'BOOKING_RESCHEDULED') {
    ri_brevo_upsert($email, ['HA_PRENOTATO' => true, 'DATA_CHIAMATA' => substr($start, 0, 10)]);
} elseif ($event === 'BOOKING_CANCELLED') {
    ri_brevo_upsert($email, ['HA_PRENOTATO' => false]);
}
echo json_encode(['ok' => true]);
