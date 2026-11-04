<?php
// Testseite für den Mailversand – zeigt das SMTP-Protokoll. Nur mit Plesk-Passwortschutz erreichbar.
require dirname(__DIR__) . '/inc/config.php';
require_admin();

$h   = fn($s) => htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
$cfg = smtp_config();
$log = [];
$result = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $body = "Testmail vom Kontaktformular daheim.feriendorfschwarzholz.de\n\nGesendet: " . date('d.m.Y H:i:s')
          . "\nDiese Nachricht kann gelöscht werden.";
    $result = send_mail('Daheim im Dorf – TESTMAIL Formular', $body, '', '', function ($line) use (&$log) {
        // Passwort/Login-Daten nicht anzeigen
        if (preg_match('/^CLIENT -> SERVER: [A-Za-z0-9+\/=]{8,}$/', $line)) { $line = 'CLIENT -> SERVER: ••••••'; }
        $log[] = $line;
    });
}
?><!doctype html>
<html lang="de"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow"><title>Mailtest – Daheim im Dorf</title>
<link rel="stylesheet" href="../css/style.css?v=5">
<style>body{font-size:16px}.wrap{padding:32px 24px 48px}pre{background:#111;color:#cfe;padding:16px;overflow:auto;font-size:13px;line-height:1.45;white-space:pre-wrap}
.ok{background:#e6f4e6;color:#1f5a1f;padding:14px 18px;font-weight:500}.err{background:#fdecec;color:#8a1f1f;padding:14px 18px;font-weight:500}
dl{display:grid;grid-template-columns:140px 1fr;gap:6px 12px;margin:16px 0}dt{font-weight:500;color:var(--g)}</style>
</head><body><div class="wrap">
<div class="kicker">Daheim im Dorf</div>
<h2>Mailversand testen</h2>
<?php if ($cfg): ?>
 <dl>
  <dt>Methode</dt><dd>SMTP</dd>
  <dt>Server</dt><dd><?= $h($cfg['host']) ?>:<?= $h($cfg['port'] ?? 587) ?> (<?= $h($cfg['secure'] ?? 'tls') ?>)</dd>
  <dt>Login</dt><dd><?= $h($cfg['user']) ?></dd>
  <dt>Absender</dt><dd><?= $h($cfg['from'] ?? $cfg['user']) ?></dd>
  <dt>Empfänger</dt><dd><?= $h($cfg['to'] ?? LEAD_MAIL_TO) ?></dd>
  <dt>Konfiguration</dt><dd><?= $h($cfg['_file']) ?></dd>
 </dl>
<?php else: ?>
 <p class="err">Keine SMTP-Zugangsdaten gefunden. Es wird PHP mail() verwendet (unzuverlässig).<br>
 Lege die Datei <code><?= $h(dirname(__DIR__, 2) . '/private/daheim-smtp.php') ?></code> an (Vorlage: inc/smtp.example.php).</p>
<?php endif; ?>
<form method="post"><button class="btn" type="submit">Testmail an <?= $h($cfg['to'] ?? LEAD_MAIL_TO) ?> senden</button></form>
<?php if ($result !== null): ?>
 <p style="margin-top:20px" class="<?= $result[0] ? 'ok' : 'err' ?>">
  <?= $result[0] ? 'Versendet. Bitte Posteingang (und Spam) von ' . $h($cfg['to'] ?? LEAD_MAIL_TO) . ' prüfen.' : 'Fehler: ' . $h($result[1]) ?>
 </p>
 <?php if ($log): ?><h3 style="margin-top:20px">SMTP-Protokoll</h3><pre><?= $h(implode("\n", $log)) ?></pre><?php endif; ?>
<?php endif; ?>
<p style="margin-top:24px"><a href="./">← Zur Anfragen-Übersicht</a></p>
</div></body></html>
