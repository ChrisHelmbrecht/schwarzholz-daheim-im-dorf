<?php
// Kontaktformular "Daheim im Dorf" – sendet die Anfrage per E-Mail.
$to      = 'info@feriendorf-schwarzholz.de';
$from    = 'noreply@feriendorfschwarzholz.de';   // Absender muss zur Domain passen (Spam-Schutz)
$subject = 'Daheim im Dorf – Anfrage über die Website';
$danke   = 'danke.html';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: index.html#kontakt'); exit; }
if (!empty($_POST['_honey'])) { header('Location: ' . $danke); exit; }   // Spam-Falle

$clean = fn($k) => trim(strip_tags($_POST[$k] ?? ''));
$name  = $clean('Name');
$tel   = $clean('Telefon');
$mail  = $clean('E-Mail');
$msg   = $clean('Nachricht');

if ($name === '' || !filter_var($mail, FILTER_VALIDATE_EMAIL) || empty($_POST['Datenschutz'])) {
    header('Location: index.html?fehler=1#kontakt'); exit;
}

$body  = "Neue Anfrage über daheim.feriendorfschwarzholz.de\n\n";
$body .= "Name:      $name\n";
$body .= "Telefon:   $tel\n";
$body .= "E-Mail:    $mail\n\n";
$body .= "Nachricht:\n$msg\n\n";
$body .= "Datenschutz: zugestimmt\nGesendet: " . date('d.m.Y H:i') . "\n";

$headers  = "From: Daheim im Dorf <$from>\r\n";
$headers .= "Reply-To: $name <$mail>\r\n";
$headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

@mail($to, '=?UTF-8?B?' . base64_encode($subject) . '?=', $body, $headers);
header('Location: ' . $danke);
exit;
