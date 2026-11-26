<?php
// Brochure e prenotazioni dalle pagine dei viaggi. Incluso da send.php dopo i controlli di sicurezza.

// Viaggi: nome mostrato nelle email, brochure, lista Brevo per chi chiede la brochure.
// I valori nel file di configurazione (ritiri-config.php, fuori dal sito) hanno la precedenza.
function ri_trips() {
    $c = ri_config();
    return [
        'marocco' => [
            'nome' => 'Desert Way · Marocco',
            'brochure' => $c['brochure_marocco'] ?? 'https://drive.google.com/file/d/14xNDLKVn2aP5OQD7rlzulQ_tZx0IkR1J/view',
            'lista_brochure' => (int)($c['lista_brochure_marocco'] ?? 0),
            'acconto' => 300,
        ],
    ];
}

function ri_mail_box($inner) {
    return '<div style="background:#FBF4E9;padding:24px;font-family:Helvetica,Arial,sans-serif"><div style="max-width:560px;margin:auto;background:#fff;border-radius:16px;padding:28px">'
        . '<img src="https://ritiriitineranti.it/img/logo-email.png" alt="Ritiri Itineranti" width="220" style="display:block;margin-bottom:22px">'
        . $inner . '<p style="color:#23203B;font-size:16px;line-height:1.6;margin:18px 0 0">A presto,<br>Fabio e Flavia</p></div></div>';
}
function ri_p($t) { return '<p style="color:#23203B;font-size:16px;line-height:1.6;margin:0 0 14px">' . $t . '</p>'; }
function ri_btn($href, $label) { return '<p style="margin:22px 0"><a href="' . htmlspecialchars($href) . '" style="background:#B6492B;color:#fff;text-decoration:none;font-weight:bold;padding:14px 26px;border-radius:999px;display:inline-block">' . $label . '</a></p>'; }

function ri_send_html($to, $subject, $html, $replyTo) {
    $h = ['From: Ritiri Itineranti <ciao@ritiriitineranti.it>', 'Reply-To: ' . $replyTo, 'MIME-Version: 1.0', 'Content-Type: text/html; charset=UTF-8'];
    return @mail($to, mb_encode_mimeheader($subject, 'UTF-8'), $html, implode("\r\n", $h));
}

function ri_split_name() {
    $n = clean($_POST['nome'] ?? '', 60); $c = clean($_POST['cognome'] ?? '', 60);
    if ($c === '' && strpos($n, ' ') !== false) { [$n, $c] = preg_split('/\s+/', $n, 2); }
    return [$n, $c];
}

// Brochure: email con il link alla brochure completa e contatto nella lista Brevo del viaggio.
function ri_brochure($email, $TO) {
    $trips = ri_trips(); $k = clean($_POST['viaggio'] ?? 'marocco', 30);
    $t = $trips[$k] ?? $trips['marocco'];
    [$n, $c] = ri_split_name();
    $html = ri_mail_box(
        ri_p('Ciao ' . htmlspecialchars($n ?: 'viaggiatore') . ',')
        . ri_p('ecco la brochure completa del ' . htmlspecialchars($t['nome']) . ': trovi il programma giorno per giorno, dove dormiamo, cosa è incluso, le quote e cosa mettere nello zaino.')
        . ri_btn($t['brochure'], 'Apri la brochure')
        . ri_p('Quando ti senti pronto, dalla pagina del viaggio puoi bloccare il tuo posto con un acconto di ' . $t['acconto'] . ' €. Se prima vuoi parlarne con noi, rispondi pure a questa email e ti scriviamo.')
    );
    ri_send_html($email, 'La brochure del ' . $t['nome'], $html, $TO);
    if ($t['lista_brochure']) ri_brevo_upsert($email, ['FIRSTNAME' => $n, 'LASTNAME' => $c, 'WHATSAPP' => clean($_POST['telefono'] ?? '', 30), 'DESTINAZIONE_INTERESSE' => $t['nome']], $t['lista_brochure']);
}

// Prenotazione: email con i passi e i dati per il bonifico dell'acconto.
// IBAN e intestatario si scrivono nel file di configurazione sul server (chiavi 'iban' e 'intestatario').
function ri_prenotazione($email, $TO) {
    $c = ri_config(); $trips = ri_trips();
    $k = clean($_POST['viaggio'] ?? 'marocco', 30); $t = $trips[$k] ?? $trips['marocco'];
    $partenza = clean($_POST['partenza'] ?? '', 80);
    [$n, $cg] = ri_split_name();
    $causale = 'Acconto ' . $t['nome'] . ' ' . $partenza . ' - ' . trim($n . ' ' . $cg);
    if (!empty($c['iban'])) {
        $bank = '<table style="width:100%;background:#FBF4E9;border-radius:12px;padding:14px 18px;margin:6px 0 18px;font-size:15px;color:#23203B">'
            . '<tr><td style="padding:4px 0;color:#7a7286;width:120px">Importo</td><td><strong>' . $t['acconto'] . ' €</strong></td></tr>'
            . '<tr><td style="padding:4px 0;color:#7a7286">Intestatario</td><td>' . htmlspecialchars($c['intestatario'] ?? '') . '</td></tr>'
            . '<tr><td style="padding:4px 0;color:#7a7286">IBAN</td><td><strong>' . htmlspecialchars($c['iban']) . '</strong></td></tr>'
            . '<tr><td style="padding:4px 0;color:#7a7286">Causale</td><td>' . htmlspecialchars($causale) . '</td></tr></table>';
    } else {
        $bank = ri_p('Entro 24 ore ti mandiamo i dati per il bonifico dell\'acconto di ' . $t['acconto'] . ' €.');
    }
    $html = ri_mail_box(
        ri_p('Ciao ' . htmlspecialchars($n ?: 'viaggiatore') . ',')
        . ri_p('che bello, il tuo posto per il <strong>' . htmlspecialchars($t['nome']) . '</strong> (' . htmlspecialchars($partenza) . ') è prenotato. Per confermarlo manca solo l\'acconto:')
        . $bank
        . ri_p('Appena lo riceviamo ti scriviamo per confermare il posto. Ecco poi come prosegue:')
        . '<ol style="color:#23203B;font-size:15px;line-height:1.6;padding-left:20px;margin:0 0 14px"><li><strong>Conosciamoci</strong>: se ti va, prenoti una chiamata di benvenuto con noi.</li><li><strong>Seconda rata</strong>: due mesi prima della partenza, il 50% di quello che resta.</li><li><strong>Saldo e chiamata di gruppo</strong>: un mese prima, insieme alla chiamata in cui rispondiamo a tutte le domande.</li><li><strong>Si parte.</strong></li></ol>'
        . ri_btn('https://cal.com/ritiri-itineranti/blackfriday?name=' . rawurlencode(trim($n . ' ' . $cg)) . '&email=' . rawurlencode($email), 'Prenota la chiamata di benvenuto')
        . ri_p('Per qualsiasi domanda rispondi pure a questa email.')
    );
    ri_send_html($email, 'Il tuo posto per il ' . $t['nome'], $html, $TO);
    // La lista di chi parte si aggiorna a mano su Brevo quando arriva il bonifico.
}
