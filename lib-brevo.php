<?php
// Configurazione segreta: sta FUORI dal sito, in /home/essent42/ritiri-config.php (mai su GitHub).
// Contenuto di esempio di quel file:
// <?php return ['brevo_key' => 'xkeysib-...', 'brevo_list' => 3, 'bf_template' => 1, 'cal_secret' => 'una-frase-segreta'];
function ri_config() {
    static $c = null;
    if ($c !== null) return $c;
    $f = dirname(__DIR__, 2) . '/ritiri-config.php';
    $c = is_file($f) ? (array)include $f : [];
    return $c;
}
// Crea o aggiorna un contatto su Brevo. Se la chiave non c'è, non fa nulla.
// Chiamata generica all'API di Brevo. Restituisce il codice HTTP (0 se manca la chiave).
function ri_brevo_call($method, $path, $body = null) {
    $c = ri_config();
    if (empty($c['brevo_key'])) return 0;
    $ch = curl_init('https://api.brevo.com/v3' . $path);
    curl_setopt_array($ch, [
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 8,
        CURLOPT_HTTPHEADER => ['api-key: ' . $c['brevo_key'], 'Content-Type: application/json', 'Accept: application/json'],
    ]);
    if ($body !== null) curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
    curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return $code;
}
// La prima volta crea su Brevo i campi che usiamo, poi lascia un segnaposto accanto al file di configurazione.
function ri_brevo_attrs() {
    $flag = dirname(__DIR__, 2) . '/.ritiri-brevo-attrs-2';
    if (is_file($flag)) return;
    foreach (['PARTENZA' => 'text', 'CODICE_BF' => 'text', 'HA_PRENOTATO' => 'boolean', 'DATA_CHIAMATA' => 'date', 'NEWSLETTER' => 'boolean', 'PROFILO_QUIZ' => 'text'] as $n => $t) {
        ri_brevo_call('POST', '/contacts/attributes/normal/' . $n, ['type' => $t]);
    }
    @file_put_contents($flag, date('c'));
}
// Crea o aggiorna un contatto su Brevo. Con $listId === false non lo aggiunge a nessuna lista.
function ri_brevo_upsert($email, $attrs, $listId = null) {
    $c = ri_config();
    if (empty($c['brevo_key'])) return false;
    ri_brevo_attrs();
    $body = ['email' => $email, 'attributes' => (object)$attrs, 'updateEnabled' => true];
    $list = $listId === false ? null : ($listId ?: ($c['brevo_list'] ?? null));
    if ($list) $body['listIds'] = [(int)$list];
    $code = ri_brevo_call('POST', '/contacts', $body);
    return $code >= 200 && $code < 300;
}
// Invia un modello transazionale di Brevo. Restituisce true se Brevo l'ha accettato.
function ri_brevo_template($templateId, $email, $name, $params) {
    $code = ri_brevo_call('POST', '/smtp/email', ['templateId' => (int)$templateId, 'to' => [array_filter(['email' => $email, 'name' => $name])], 'params' => (object)$params]);
    return $code >= 200 && $code < 300;
}
