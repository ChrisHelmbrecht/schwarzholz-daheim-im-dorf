<?php
// Kontaktformular "Daheim im Dorf": speichert jede Anfrage zuerst im Log, dann E-Mail an info@.
require __DIR__ . '/inc/config.php';

$danke = 'danke.html';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: index.html#kontakt'); exit; }
if (!empty($_POST['_honey'])) { header('Location: ' . $danke); exit; }   // Spam-Falle

$clean = fn($k) => trim(strip_tags((string)($_POST[$k] ?? '')));
$name  = mb_substr($clean('Name'), 0, 200);
$tel   = mb_substr($clean('Telefon'), 0, 60);
$mail  = mb_substr($clean('E-Mail'), 0, 200);
$msg   = mb_substr($clean('Nachricht'), 0, 5000);

if ($name === '' || !filter_var($mail, FILTER_VALIDATE_EMAIL) || empty($_POST['Datenschutz'])) {
    header('Location: index.html?fehler=1#kontakt'); exit;
}

// Header-Injection verhindern
$name_hdr = preg_replace('/[\r\n"<>]+/', ' ', $name);

$datum = date('d.m.Y H:i');
$body  = "Neue Anfrage über daheim.feriendorfschwarzholz.de\n\n";
$body .= "Name:      $name\n";
$body .= "Telefon:   $tel\n";
$body .= "E-Mail:    $mail\n\n";
$body .= "Nachricht:\n$msg\n\n";
$body .= "Datenschutz: zugestimmt\nGesendet: $datum\n";

$headers  = "From: Daheim im Dorf <" . LEAD_MAIL_FROM . ">\r\n";
$headers .= "Reply-To: $name_hdr <$mail>\r\n";
$headers .= "MIME-Version: 1.0\r\n";
$headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
$headers .= "Content-Transfer-Encoding: 8bit\r\n";

$sent = @mail(
    LEAD_MAIL_TO,
    '=?UTF-8?B?' . base64_encode(LEAD_SUBJECT) . '?=',
    $body,
    $headers,
    '-f' . LEAD_MAIL_FROM
);

// Erst nach dem Versuch protokollieren, damit der Versandstatus mitgeschrieben wird.
// Schlägt der Versand fehl, ist die Anfrage trotzdem im Log.
$logged = lead_append([$datum, $name, $tel, $mail, $msg, $sent ? 'ja' : 'NEIN']);

if (!$logged) {
    error_log('Daheim im Dorf: Lead konnte nicht gespeichert werden (' . lead_file() . ')');
}
if (!$sent) {
    error_log('Daheim im Dorf: mail() fehlgeschlagen für Anfrage von ' . $mail);
}

header('Location: ' . $danke);
exit;
