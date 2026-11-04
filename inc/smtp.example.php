<?php
// VORLAGE – Zugangsdaten für den externen Mailserver.
// Kopie anlegen als:  <Webspace>/private/daheim-smtp.php
// (in Plesk: Dateien → Ordner "private" neben "httpdocs"), Werte eintragen. Diese Datei NICHT ins Git.
return [
    'host'      => 'smtp.dein-mailserver.de', // SMTP-Server des externen Anbieters
    'port'      => 587,                       // 587 mit 'tls'  oder  465 mit 'ssl'
    'secure'    => 'tls',                     // 'tls' (STARTTLS) oder 'ssl'
    'user'      => 'postfach@deine-domain.de',// Login des Postfachs
    'pass'      => 'PASSWORT',
    'from'      => 'postfach@deine-domain.de',// Absender – muss zu diesem Postfach gehören
    'from_name' => 'Daheim im Dorf',
    // 'to'     => 'info@feriendorf-schwarzholz.de', // optional, Standard siehe inc/config.php
];
