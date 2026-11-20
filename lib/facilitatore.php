<?php
// Candidatura facilitatore: scheda PDF per noi, email di conferma al candidato e contatto nella lista Brevo dei facilitatori.
// Viene inclusa da send.php, che ha già controllato origine, limite di invii, honeypot ed email.
require_once __DIR__ . '/fpdf/fpdf.php';

function ri_fac_val($k, $max = 4000) {
    $v = trim(str_replace("\0", '', mb_substr((string)($_POST[$k] ?? ''), 0, $max)));
    return preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', '', $v);
}

// FPDF usa la codifica Windows-1252: va bene per l'italiano.
function ri_pdf_txt($s) {
    $s = str_replace(['’', '‘', '“', '”', '–', '—', '…'], ["'", "'", '"', '"', '-', '-', '...'], $s);
    $out = @iconv('UTF-8', 'windows-1252//TRANSLIT//IGNORE', $s);
    return $out === false ? $s : $out;
}

class RiFacPDF extends FPDF {
    public $nome = '';
    function Header() {
        $this->SetFillColor(35, 32, 59);           // Notte
        $this->Rect(0, 0, 210, 34, 'F');
        $this->SetFillColor(182, 73, 43);          // Terracotta
        $this->Rect(0, 34, 210, 1.6, 'F');
        $this->SetFont('Helvetica', 'B', 9);
        $this->SetTextColor(233, 162, 59);         // Zafferano
        $this->SetXY(16, 10);
        $this->Cell(0, 5, ri_pdf_txt('RITIRI ITINERANTI  ·  NUOVA CANDIDATURA FACILITATORE'));
        $this->SetFont('Helvetica', 'B', 20);
        $this->SetTextColor(251, 244, 233);        // Sabbia
        $this->SetXY(16, 17);
        $this->Cell(0, 10, ri_pdf_txt($this->nome));
        $this->SetY(44);
    }
    function Footer() {
        $this->SetY(-14);
        $this->SetFont('Helvetica', '', 8);
        $this->SetTextColor(140, 135, 150);
        $this->Cell(0, 5, ri_pdf_txt('ritiriitineranti.it  ·  Documento interno, contiene dati personali: non inoltrare.  ·  Pagina ' . $this->PageNo()), 0, 0, 'C');
    }
    function Section($title) {
        if ($this->GetY() > 250) $this->AddPage();
        $this->Ln(3);
        $this->SetFont('Helvetica', 'B', 12);
        $this->SetTextColor(182, 73, 43);
        $this->Cell(0, 7, ri_pdf_txt($title), 0, 1);
        $this->SetDrawColor(230, 220, 205);
        $this->Line(16, $this->GetY(), 194, $this->GetY());
        $this->Ln(3);
    }
    function Row($label, $value) {
        if ($value === '') $value = '—';
        $this->SetFont('Helvetica', 'B', 9);
        $this->SetTextColor(120, 112, 130);
        $this->Cell(0, 5, ri_pdf_txt(mb_strtoupper($label)), 0, 1);
        $this->SetFont('Helvetica', '', 11);
        $this->SetTextColor(35, 32, 59);
        $this->MultiCell(0, 5.6, ri_pdf_txt($value === '—' ? '-' : $value));
        $this->Ln(2.5);
    }
    function Chips($label, $csv) {
        $items = array_values(array_filter(array_map('trim', explode(',', $csv))));
        $this->SetFont('Helvetica', 'B', 9);
        $this->SetTextColor(120, 112, 130);
        $this->Cell(0, 5, ri_pdf_txt(mb_strtoupper($label)), 0, 1);
        if (!$items) { $this->SetFont('Helvetica', '', 11); $this->SetTextColor(35, 32, 59); $this->Cell(0, 6, '-', 0, 1); $this->Ln(2); return; }
        $this->SetFont('Helvetica', 'B', 9.5);
        $x = 16; $y = $this->GetY() + 1;
        foreach ($items as $it) {
            $t = ri_pdf_txt($it); $w = $this->GetStringWidth($t) + 8;
            if ($x + $w > 194) { $x = 16; $y += 8.5; }
            $this->SetFillColor(251, 244, 233);
            $this->SetDrawColor(233, 162, 59);
            $this->RoundedRect($x, $y, $w, 6.5, 3.2);
            $this->SetXY($x, $y + 0.4);
            $this->SetTextColor(35, 32, 59);
            $this->Cell($w, 5.8, $t, 0, 0, 'C');
            $x += $w + 2.5;
        }
        $this->SetY($y + 10);
    }
    function RoundedRect($x, $y, $w, $h, $r) {
        $k = $this->k; $hp = $this->h; $MyArc = 4 / 3 * (sqrt(2) - 1);
        $this->_out(sprintf('%.2F %.2F m', ($x + $r) * $k, ($hp - $y) * $k));
        $xc = $x + $w - $r; $yc = $y + $r;
        $this->_out(sprintf('%.2F %.2F l', $xc * $k, ($hp - $y) * $k));
        $this->_Arc($xc + $r * $MyArc, $yc - $r, $xc + $r, $yc - $r * $MyArc, $xc + $r, $yc);
        $xc = $x + $w - $r; $yc = $y + $h - $r;
        $this->_out(sprintf('%.2F %.2F l', ($x + $w) * $k, ($hp - $yc) * $k));
        $this->_Arc($xc + $r, $yc + $r * $MyArc, $xc + $r * $MyArc, $yc + $r, $xc, $yc + $r);
        $xc = $x + $r; $yc = $y + $h - $r;
        $this->_out(sprintf('%.2F %.2F l', $xc * $k, ($hp - ($y + $h)) * $k));
        $this->_Arc($xc - $r * $MyArc, $yc + $r, $xc - $r, $yc + $r * $MyArc, $xc - $r, $yc);
        $xc = $x + $r; $yc = $y + $r;
        $this->_out(sprintf('%.2F %.2F l', $x * $k, ($hp - $yc) * $k));
        $this->_Arc($xc - $r, $yc - $r * $MyArc, $xc - $r * $MyArc, $yc - $r, $xc, $yc - $r);
        $this->_out('B');
    }
    function _Arc($x1, $y1, $x2, $y2, $x3, $y3) {
        $h = $this->h;
        $this->_out(sprintf('%.2F %.2F %.2F %.2F %.2F %.2F c', $x1 * $this->k, ($h - $y1) * $this->k, $x2 * $this->k, ($h - $y2) * $this->k, $x3 * $this->k, ($h - $y3) * $this->k));
    }
}

function ri_fac_pdf($d) {
    $pdf = new RiFacPDF('P', 'mm', 'A4');
    $pdf->nome = $d['nome'];
    $pdf->SetTitle('Candidatura facilitatore - ' . $d['nome'], true);
    $pdf->SetAuthor('Ritiri Itineranti', true);
    $pdf->SetMargins(16, 44, 16);
    $pdf->SetAutoPageBreak(true, 20);
    $pdf->AddPage();

    // Riquadro con i dati di contatto
    $pdf->SetFillColor(251, 244, 233);
    $pdf->Rect(16, 44, 178, 24, 'F');
    $pdf->SetXY(21, 48);
    $pdf->SetFont('Helvetica', '', 10.5);
    $pdf->SetTextColor(35, 32, 59);
    $pdf->Cell(86, 6, ri_pdf_txt('Email: ' . $d['email']));
    $pdf->Cell(0, 6, ri_pdf_txt('Città: ' . ($d['citta'] ?: '-')), 0, 1);
    $pdf->SetX(21);
    $pdf->Cell(86, 6, ri_pdf_txt('Instagram / sito: ' . ($d['instagram'] ?: '-')));
    $pdf->Cell(0, 6, ri_pdf_txt('Ricevuta il ' . $d['data']), 0, 1);
    $pdf->SetX(21);
    $pdf->SetTextColor(120, 112, 130);
    $pdf->Cell(0, 6, ri_pdf_txt('Esperienza con i gruppi: ' . ($d['anni_esperienza'] ?: '-') . ' anni  ·  Community: ' . ($d['community'] ?: '-')), 0, 1);
    $pdf->SetY(74);

    $pdf->Section('La pratica');
    $pdf->Chips('Focus', $d['focus']);
    $pdf->Row('Formazioni e certificazioni', $d['formazioni']);

    $pdf->Section('Il perché');
    $pdf->Row('Perché vuole portare la sua pratica in viaggio', $d['perche']);
    $pdf->Row('Il tema che porterebbe nei nostri viaggi', $d['tema']);

    $pdf->Section('Viaggi e disponibilità');
    $pdf->Chips('Viaggi che vorrebbe guidare', $d['dest']);
    $pdf->Chips('Mesi disponibili', $d['mesi']);
    $pdf->Row('Destinazione proposta per la co-creazione', $d['destinazione_proposta']);

    return $pdf->Output('S');
}

function ri_fac_esc($s) { return nl2br(htmlspecialchars($s ?: '-', ENT_QUOTES, 'UTF-8')); }

function ri_facilitatore($TO, $FROM, $email) {
    $keys = ['nome', 'email', 'citta', 'instagram', 'focus', 'anni_esperienza', 'community', 'formazioni', 'perche', 'tema', 'dest', 'mesi', 'destinazione_proposta'];
    $d = [];
    foreach ($keys as $k) $d[$k] = ri_fac_val($k, $k === 'nome' ? 80 : 4000);
    $d['email'] = $email;
    $d['data'] = date('d/m/Y \a\l\l\e H:i');
    $nome = $d['nome'] ?: $email;
    $first = explode(' ', $nome)[0];

    $pdf = ri_fac_pdf(array_merge($d, ['nome' => $nome]));
    $file = 'Candidatura - ' . preg_replace('/[^\p{L}\p{N} ]/u', '', $nome) . '.pdf';

    // Email per noi: riepilogo leggibile + PDF allegato
    $row = function ($l, $v) { return '<tr><td style="padding:8px 0;color:#7a7286;font-size:12px;text-transform:uppercase;letter-spacing:.04em;vertical-align:top;width:170px">' . $l . '</td><td style="padding:8px 0;color:#23203B;font-size:15px;line-height:1.5">' . ri_fac_esc($v) . '</td></tr>'; };
    $html = '<div style="background:#FBF4E9;padding:24px;font-family:Helvetica,Arial,sans-serif"><div style="max-width:640px;margin:auto;background:#fff;border-radius:16px;overflow:hidden">'
        . '<div style="background:#23203B;padding:22px 26px;border-bottom:4px solid #B6492B"><div style="color:#E9A23B;font-size:11px;font-weight:bold;letter-spacing:.08em">NUOVA CANDIDATURA FACILITATORE</div><div style="color:#FBF4E9;font-size:24px;font-weight:bold;margin-top:6px">' . htmlspecialchars($nome) . '</div></div>'
        . '<div style="padding:18px 26px"><p style="color:#23203B;font-size:15px;margin:0 0 10px">Trovi la scheda completa nel PDF allegato. Qui sotto il riassunto. Per rispondere basta premere Rispondi.</p><table style="width:100%;border-collapse:collapse">'
        . $row('Email', $d['email']) . $row('Città', $d['citta']) . $row('Instagram / sito', $d['instagram']) . $row('Focus', $d['focus'])
        . $row('Esperienza', $d['anni_esperienza'] ? $d['anni_esperienza'] . ' anni' : '') . $row('Community', $d['community'])
        . $row('Perché', $d['perche']) . $row('Tema', $d['tema']) . $row('Viaggi', $d['dest']) . $row('Mesi', $d['mesi'])
        . '</table></div></div></div>';

    $b = 'ri' . bin2hex(random_bytes(8));
    $msg = "--$b\r\nContent-Type: text/html; charset=UTF-8\r\nContent-Transfer-Encoding: base64\r\n\r\n" . chunk_split(base64_encode($html))
        . "--$b\r\nContent-Type: application/pdf; name=\"" . mb_encode_mimeheader($file, 'UTF-8') . "\"\r\nContent-Transfer-Encoding: base64\r\nContent-Disposition: attachment; filename=\"" . mb_encode_mimeheader($file, 'UTF-8') . "\"\r\n\r\n" . chunk_split(base64_encode($pdf)) . "--$b--";
    $headers = ['From: ' . $FROM, 'Reply-To: ' . ($d['nome'] ? mb_encode_mimeheader($d['nome'], 'UTF-8') . ' <' . $email . '>' : $email), 'MIME-Version: 1.0', 'Content-Type: multipart/mixed; boundary="' . $b . '"'];
    $ok = mail($TO, mb_encode_mimeheader('Candidatura facilitatore · ' . $nome, 'UTF-8'), $msg, implode("\r\n", $headers));
    if (!$ok) return false;

    // Contatto nella lista Brevo dei facilitatori (lista n. 11 su Brevo)
    $parts = explode(' ', $nome, 2);
    $c = ri_config();
    $list = (int)($c['facilitatori_list'] ?? 11);
    ri_brevo_upsert($email, ['FIRSTNAME' => $parts[0], 'LASTNAME' => $parts[1] ?? '', 'CITTA' => $d['citta'], 'FOCUS' => $d['focus'], 'INSTAGRAM' => $d['instagram']], $list ?: false);

    // Conferma al candidato
    $conf = '<div style="background:#FBF4E9;padding:24px;font-family:Helvetica,Arial,sans-serif"><div style="max-width:560px;margin:auto;background:#fff;border-radius:16px;padding:28px">'
        . '<img src="https://ritiriitineranti.it/img/logo-email.png" alt="Ritiri Itineranti" width="220" style="display:block;margin-bottom:22px">'
        . '<p style="color:#23203B;font-size:16px;line-height:1.6;margin:0 0 14px">Ciao ' . htmlspecialchars($first) . ',</p>'
        . '<p style="color:#23203B;font-size:16px;line-height:1.6;margin:0 0 14px">grazie per averci raccontato la tua pratica: la tua candidatura per entrare nel network dei facilitatori di Ritiri Itineranti è arrivata. La leggiamo con calma, perché per noi conta capire chi sei e che viaggio potresti portare, e ti scriviamo entro due settimane.</p>'
        . '<p style="color:#23203B;font-size:16px;line-height:1.6;margin:0 0 14px">Se nel frattempo ti viene in mente qualcosa da aggiungere, rispondi pure a questa email.</p>'
        . '<p style="color:#23203B;font-size:16px;line-height:1.6;margin:0">A presto,<br>Fabio e Flavia</p></div></div>';
    $ch = ['From: Ritiri Itineranti <ciao@ritiriitineranti.it>', 'Reply-To: ' . $TO, 'MIME-Version: 1.0', 'Content-Type: text/html; charset=UTF-8'];
    @mail($email, mb_encode_mimeheader('Abbiamo ricevuto la tua candidatura', 'UTF-8'), $conf, implode("\r\n", $ch));
    return true;
}
