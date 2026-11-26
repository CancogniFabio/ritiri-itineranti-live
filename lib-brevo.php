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
    $GLOBALS['ri_brevo_last'] = (string)curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return $code;
}
// La prima volta crea su Brevo i campi che usiamo, poi lascia un segnaposto accanto al file di configurazione.
function ri_brevo_attrs() {
    $flag = dirname(__DIR__, 2) . '/.ritiri-brevo-attrs-6';
    if (is_file($flag)) return;
    foreach (['ACCONTO_PAGATO' => 'text', 'PARTENZA' => 'text', 'CODICE_BF' => 'text', 'HA_PRENOTATO' => 'boolean', 'DATA_CHIAMATA' => 'date', 'NEWSLETTER' => 'boolean', 'PROFILO_QUIZ' => 'text', 'DESTINAZIONE_INTERESSE' => 'text', 'CITTA' => 'text', 'FOCUS' => 'text', 'INSTAGRAM' => 'text', 'TELEFONO' => 'text'] as $n => $t) {
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
    $ids = array_values(array_filter(array_map('intval', is_array($list) ? $list : [$list])));
    if ($ids) $body['listIds'] = $ids;
    $code = ri_brevo_call('POST', '/contacts', $body);
    if ($code >= 200 && $code < 300) return true;
    $first = $GLOBALS['ri_brevo_last'] ?? '';
    // Brevo rifiuta l'intero contatto se il numero WhatsApp non gli piace (formato sbagliato o già usato da
    // un altro contatto). In quel caso salviamo comunque il contatto, con il numero in un campo di testo.
    $a = (array)$attrs;
    if (!empty($a['WHATSAPP'])) {
        $a['TELEFONO'] = $a['WHATSAPP'];
        unset($a['WHATSAPP']);
        $body['attributes'] = (object)$a;
        $code = ri_brevo_call('POST', '/contacts', $body);
        $ok = $code >= 200 && $code < 300;
        ri_brevo_alert($email, $ok ? 'Contatto salvato senza WhatsApp: Brevo non ha accettato il numero ' . $a['TELEFONO'] . '. Il numero è nel campo TELEFONO.' : 'Contatto NON salvato su Brevo.', $first . ($ok ? '' : "\n" . ($GLOBALS['ri_brevo_last'] ?? '')));
        return $ok;
    }
    ri_brevo_alert($email, 'Contatto NON salvato su Brevo.', $first);
    return false;
}
// Avvisa via email e lascia una riga di log fuori dal sito quando Brevo rifiuta un contatto.
function ri_brevo_alert($email, $what, $detail) {
    $line = date('c') . ' | ' . $email . ' | ' . $what . ' | ' . str_replace(["\r", "\n"], ' ', mb_substr((string)$detail, 0, 500)) . "\n";
    @file_put_contents(dirname(__DIR__, 2) . '/ritiri-brevo-errori.log', $line, FILE_APPEND | LOCK_EX);
    $h = ['From: Sito Ritiri Itineranti <no-reply@ritiriitineranti.it>', 'MIME-Version: 1.0', 'Content-Type: text/plain; charset=UTF-8'];
    @mail('ciao@ritiriitineranti.it', mb_encode_mimeheader('Brevo: controlla il contatto ' . $email, 'UTF-8'), $what . "\n\nEmail: " . $email . "\nRisposta di Brevo: " . $detail . "\n", implode("\r\n", $h));
}
// Invia un modello transazionale di Brevo. Restituisce true se Brevo l'ha accettato.
function ri_brevo_template($templateId, $email, $name, $params) {
    $code = ri_brevo_call('POST', '/smtp/email', ['templateId' => (int)$templateId, 'to' => [array_filter(['email' => $email, 'name' => $name])], 'params' => (object)$params]);
    return $code >= 200 && $code < 300;
}

// Trova l'id di una lista Brevo dal nome (prima che contiene il testo dato), e lo ricorda in un file accanto alla configurazione.
function ri_brevo_list_by_name($needle) {
    $cache = dirname(__DIR__, 2) . '/.ritiri-brevo-list-' . preg_replace('/\W/', '', $needle);
    if (is_file($cache)) return (int)file_get_contents($cache);
    $c = ri_config();
    if (empty($c['brevo_key'])) return 0;
    $ch = curl_init('https://api.brevo.com/v3/contacts/lists?limit=50');
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 8, CURLOPT_HTTPHEADER => ['api-key: ' . $c['brevo_key'], 'Accept: application/json']]);
    $r = json_decode((string)curl_exec($ch), true); curl_close($ch);
    foreach (($r['lists'] ?? []) as $l) {
        if (stripos($l['name'], $needle) !== false) { @file_put_contents($cache, (string)$l['id']); return (int)$l['id']; }
    }
    return 0;
}

// La lista generale della community: tutti i contatti che hanno detto sì alle novità.
function ri_community_list() {
    $c = ri_config();
    return (int)($c['community_list'] ?? ri_brevo_list_by_name('community'));
}
