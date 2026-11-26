<?php
// Dati per il bonifico dell'acconto, letti dal file di configurazione fuori dal sito (così l'IBAN non sta su GitHub).
require __DIR__ . '/lib-brevo.php';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('X-Robots-Tag: noindex');
$c = ri_config();
echo json_encode(empty($c['iban']) ? ['ok' => false] : ['ok' => true, 'iban' => $c['iban'], 'intestatario' => $c['intestatario'] ?? '', 'bic' => $c['bic'] ?? '', 'link' => $c['link_pagamento'] ?? '']);
