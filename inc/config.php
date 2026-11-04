<?php
// Gemeinsame Einstellungen für Kontaktformular und Lead-Übersicht.
date_default_timezone_set('Europe/Berlin');

const LEAD_MAIL_TO   = 'info@feriendorf-schwarzholz.de';
const LEAD_MAIL_FROM = 'info@dieneue.social'; // Konto des externen Mailservers in Plesk – Absender muss dazu passen
const LEAD_SUBJECT   = 'Daheim im Dorf – Anfrage über die Website';

/**
 * Speicherort der Lead-Datei: bevorzugt außerhalb des Webroots (Plesk-Ordner /private),
 * sonst im Ordner data/ (per .htaccess gesperrt).
 */
function lead_file(): string {
    $candidates = [
        dirname(__DIR__, 2) . '/private',
        dirname(__DIR__) . '/data',
    ];
    foreach ($candidates as $dir) {
        if (is_dir($dir) && is_writable($dir)) {
            return $dir . '/daheim-anfragen.csv';
        }
    }
    return dirname(__DIR__) . '/data/daheim-anfragen.csv';
}

const LEAD_COLUMNS = ['Datum', 'Name', 'Telefon', 'E-Mail', 'Nachricht', 'E-Mail versendet'];

/** Schützt gegen Formel-Injektion beim Öffnen in Excel. */
function csv_safe(string $v): string {
    $v = str_replace(["\r\n", "\r"], "\n", $v);
    return preg_match('/^[=+\-@\t]/', $v) ? "'" . $v : $v;
}

function lead_append(array $row): bool {
    $file = lead_file();
    $new  = !file_exists($file);
    $fh   = @fopen($file, 'a');
    if (!$fh) return false;
    flock($fh, LOCK_EX);
    if ($new) {
        fwrite($fh, "\xEF\xBB\xBF"); // UTF-8 BOM für Excel
        fputcsv($fh, LEAD_COLUMNS, ';');
    }
    fputcsv($fh, array_map('csv_safe', $row), ';');
    fflush($fh);
    flock($fh, LOCK_UN);
    fclose($fh);
    return true;
}

function lead_read(): array {
    $file = lead_file();
    if (!file_exists($file)) return [];
    $fh = fopen($file, 'r');
    $rows = [];
    $first = true;
    while (($r = fgetcsv($fh, 0, ';')) !== false) {
        if ($first) { $first = false; continue; } // Kopfzeile
        if (count($r) < 6) continue;
        $rows[] = $r;
    }
    fclose($fh);
    return $rows;
}

/* ---------- Mailversand ---------- */

/**
 * SMTP-Zugangsdaten liegen NICHT im Repository, sondern in einer Datei auf dem Server:
 *   1. <Webspace>/private/daheim-smtp.php   (bevorzugt, außerhalb des Webroots)
 *   2. inc/smtp.local.php                   (Ausweichort, per .htaccess gesperrt, nicht in Git)
 * Vorlage: inc/smtp.example.php
 */
function smtp_config(): ?array {
    foreach ([dirname(__DIR__, 2) . '/private/daheim-smtp.php', __DIR__ . '/smtp.local.php'] as $f) {
        if (is_file($f)) {
            $cfg = include $f;
            if (is_array($cfg) && !empty($cfg['host'])) { $cfg['_file'] = $f; return $cfg; }
        }
    }
    return null;
}

/**
 * Versendet eine Mail. Rückgabe: [erfolgreich (bool), Fehlertext (string)].
 * $debug: Callback, das SMTP-Protokollzeilen erhält (nur für die Testseite).
 */
function send_mail(string $subject, string $body, string $replyName = '', string $replyMail = '', ?callable $debug = null): array {
    $cfg = smtp_config();

    if ($cfg === null) {
        // Notfall: PHP mail() über den lokalen Mailserver
        $headers  = "From: Daheim im Dorf <" . LEAD_MAIL_FROM . ">\r\n";
        if ($replyMail !== '') {
            $headers .= "Reply-To: " . preg_replace('/[\r\n"<>]+/', ' ', $replyName) . " <$replyMail>\r\n";
        }
        $headers .= "MIME-Version: 1.0\r\nContent-Type: text/plain; charset=UTF-8\r\nContent-Transfer-Encoding: 8bit\r\n";
        $ok = @mail(LEAD_MAIL_TO, '=?UTF-8?B?' . base64_encode($subject) . '?=', $body, $headers, '-f' . LEAD_MAIL_FROM);
        return [$ok, $ok ? '' : 'mail() fehlgeschlagen (kein SMTP konfiguriert)'];
    }

    require_once __DIR__ . '/phpmailer/Exception.php';
    require_once __DIR__ . '/phpmailer/PHPMailer.php';
    require_once __DIR__ . '/phpmailer/SMTP.php';

    $m = new PHPMailer\PHPMailer\PHPMailer(true);
    try {
        $m->isSMTP();
        $m->Host       = $cfg['host'];
        $m->Port       = (int)($cfg['port'] ?? 587);
        $m->SMTPAuth   = true;
        $m->Username   = $cfg['user'];
        $m->Password   = $cfg['pass'];
        $secure        = strtolower($cfg['secure'] ?? 'tls');
        if ($secure === 'none') { $m->SMTPSecure = ''; $m->SMTPAutoTLS = false; }
        else { $m->SMTPSecure = $secure === 'ssl' ? PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS : PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS; }
        $m->Timeout    = 15;
        $m->CharSet    = 'UTF-8';
        if ($debug) { $m->SMTPDebug = 2; $m->Debugoutput = function ($s) use ($debug) { $debug(rtrim($s)); }; }

        $m->setFrom($cfg['from'] ?? $cfg['user'], $cfg['from_name'] ?? 'Daheim im Dorf');
        $m->addAddress($cfg['to'] ?? LEAD_MAIL_TO);
        if ($replyMail !== '') { $m->addReplyTo($replyMail, $replyName); }
        $m->Subject = $subject;
        $m->Body    = $body;
        $m->send();
        return [true, ''];
    } catch (\Throwable $e) {
        return [false, $m->ErrorInfo ?: $e->getMessage()];
    }
}

/** Zugang zu /admin nur mit Plesk-Passwortschutz. */
function require_admin(): void {
    $user = $_SERVER['REMOTE_USER'] ?? $_SERVER['REDIRECT_REMOTE_USER'] ?? $_SERVER['PHP_AUTH_USER'] ?? '';
    if ($user === '') {
        http_response_code(403);
        header('Content-Type: text/plain; charset=UTF-8');
        echo "Zugriff gesperrt.\n\nDieses Verzeichnis ist noch nicht passwortgeschützt.\n"
           . "Bitte in Plesk unter „Passwortgeschützte Verzeichnisse“ den Ordner /admin schützen.";
        exit;
    }
}
