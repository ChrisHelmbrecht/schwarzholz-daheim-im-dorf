<?php
// Lead-Übersicht. Dieses Verzeichnis MUSS in Plesk passwortgeschützt sein
// (Websites & Domains → Passwortgeschützte Verzeichnisse → /admin).
require dirname(__DIR__) . '/inc/config.php';

$user = $_SERVER['REMOTE_USER'] ?? $_SERVER['REDIRECT_REMOTE_USER'] ?? $_SERVER['PHP_AUTH_USER'] ?? '';
if ($user === '') {
    http_response_code(403);
    header('Content-Type: text/plain; charset=UTF-8');
    echo "Zugriff gesperrt.\n\nDieses Verzeichnis ist noch nicht passwortgeschützt.\n"
       . "Bitte in Plesk unter „Passwortgeschützte Verzeichnisse“ den Ordner /admin schützen.";
    exit;
}

$rows = array_reverse(lead_read()); // neueste zuerst

if (isset($_GET['csv'])) {
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="daheim-anfragen-' . date('Y-m-d') . '.csv"');
    readfile(lead_file());
    exit;
}

$total = count($rows);
$month = date('m.Y');
$thisMonth = count(array_filter($rows, fn($r) => substr($r[0], 3, 7) === $month));
$failed = count(array_filter($rows, fn($r) => $r[5] !== 'ja'));
$byMonth = [];
foreach ($rows as $r) { $k = substr($r[0], 3, 7); $byMonth[$k] = ($byMonth[$k] ?? 0) + 1; }

$h = fn($s) => htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
?><!doctype html>
<html lang="de"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title>Anfragen – Daheim im Dorf</title>
<link rel="stylesheet" href="../css/style.css?v=5">
<style>
 body{font-size:16px} .wrap{padding-top:32px;padding-bottom:48px}
 .stats{display:flex;gap:16px;flex-wrap:wrap;margin:20px 0 28px}
 .stat{background:var(--gl);padding:18px 22px;border-top:4px solid var(--sun);min-width:160px}
 .stat b{display:block;font-size:2rem;color:var(--g);font-weight:600;line-height:1.1}
 .stat.warn{border-top-color:#c0392b} .stat.warn b{color:#c0392b}
 table.leads{width:100%;border-collapse:collapse;font-size:.95rem}
 table.leads th{text-align:left;font-weight:600;color:var(--g);border-bottom:2px solid var(--g);padding:8px 10px 8px 0}
 table.leads td{padding:10px 10px 10px 0;border-bottom:1px solid var(--line);vertical-align:top;text-align:left;white-space:normal;font-weight:300}
 table.leads td.msg{max-width:420px;white-space:pre-wrap}
 .no{color:#c0392b;font-weight:600}
 .scroll{overflow-x:auto}
</style></head><body>
<div class="wrap">
 <div class="kicker">Daheim im Dorf</div>
 <h2>Anfragen über die Website</h2>
 <div class="stats">
  <div class="stat"><b><?= $total ?></b>gesamt</div>
  <div class="stat"><b><?= $thisMonth ?></b>im <?= $h(date('m/Y')) ?></div>
  <?php if ($failed): ?><div class="stat warn"><b><?= $failed ?></b>E-Mail nicht versendet</div><?php endif; ?>
 </div>
 <?php if ($byMonth): ?>
 <p class="note">Pro Monat: <?php foreach ($byMonth as $m => $n) echo $h($m) . ': <strong>' . $n . '</strong> &nbsp; '; ?></p>
 <?php endif; ?>
 <p><a class="btn outline" href="?csv=1">Als CSV herunterladen (Excel)</a></p>
 <?php if (!$rows): ?>
  <p>Noch keine Anfragen.</p>
 <?php else: ?>
 <div class="scroll"><table class="leads">
  <tr><th>Datum</th><th>Name</th><th>Telefon</th><th>E-Mail</th><th>Nachricht</th><th>Mail</th></tr>
  <?php foreach ($rows as $r): ?>
  <tr>
   <td><?= $h($r[0]) ?></td>
   <td><?= $h(ltrim($r[1], "'")) ?></td>
   <td><?= $h(ltrim($r[2], "'")) ?></td>
   <td><a href="mailto:<?= $h($r[3]) ?>"><?= $h($r[3]) ?></a></td>
   <td class="msg"><?= $h(ltrim($r[4], "'")) ?></td>
   <td class="<?= $r[5] === 'ja' ? '' : 'no' ?>"><?= $h($r[5]) ?></td>
  </tr>
  <?php endforeach; ?>
 </table></div>
 <?php endif; ?>
 <p class="note" style="margin-top:24px">Gespeichert in: <?= $h(lead_file()) ?></p>
</div>
</body></html>
