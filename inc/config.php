<?php
// Gemeinsame Einstellungen für Kontaktformular und Lead-Übersicht.
date_default_timezone_set('Europe/Berlin');

const LEAD_MAIL_TO   = 'info@feriendorf-schwarzholz.de';
const LEAD_MAIL_FROM = 'noreply@feriendorfschwarzholz.de'; // ggf. an den externen Mailserver anpassen
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
