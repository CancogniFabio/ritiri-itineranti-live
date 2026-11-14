<?php
// Configurazione segreta: sta FUORI dal sito, in /home/essent42/ritiri-config.php (mai su GitHub).
// Contenuto di esempio di quel file:
// <?php return ['brevo_key' => 'xkeysib-...', 'brevo_list' => 3, 'cal_secret' => 'una-frase-segreta'];
function ri_config() {
    static $c = null;
    if ($c !== null) return $c;
    $f = dirname(__DIR__, 2) . '/ritiri-config.php';
    $c = is_file($f) ? (array)include $f : [];
    return $c;
}
// Crea o aggiorna un contatto su Brevo. Se la chiave non c'è, non fa nulla.
function ri_brevo_upsert($email, $attrs, $listId = null) {
    $c = ri_config();
    if (empty($c['brevo_key'])) return false;
    $body = ['email' => $email, 'attributes' => (object)$attrs, 'updateEnabled' => true];
    $list = $listId ?: ($c['brevo_list'] ?? null);
    if ($list) $body['listIds'] = [(int)$list];
    $ch = curl_init('https://api.brevo.com/v3/contacts');
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 8,
        CURLOPT_HTTPHEADER => ['api-key: ' . $c['brevo_key'], 'Content-Type: application/json', 'Accept: application/json'],
        CURLOPT_POSTFIELDS => json_encode($body),
    ]);
    curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return $code >= 200 && $code < 300;
}
