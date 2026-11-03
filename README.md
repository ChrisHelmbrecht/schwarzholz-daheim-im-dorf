# Daheim im Dorf – Landingpage

Landingpage zum Flyer „Daheim im Dorf – Wohnen mit Service“ des Feriendorfs Schwarzholz, Viechtach.

Statische Seite (HTML/CSS, keine Build-Tools) plus `kontakt.php` für das Kontaktformular. Gehostet unter daheim.feriendorfschwarzholz.de (Plesk, Git-Deployment aus `main`).

- `index.html` – Seite
- `css/style.css` – Styles
- `img/` – Fotos (web-optimiert, max. 1600 px)
- `kontakt.php` – Formular-Handler, sendet per PHP mail() an info@feriendorf-schwarzholz.de
- `danke.html` – Bestätigungsseite nach dem Absenden

## Anfragen-Log

Jede Formularanfrage wird **vor** dem Mailversand-Status in eine CSV-Datei geschrieben (`daheim-anfragen.csv`), damit nichts verloren geht – auch wenn der Mailversand scheitert.

- Speicherort: Plesk-Ordner `/private` der Domain (außerhalb des Webroots), sonst `data/` (gesperrt)
- Übersicht mit Zählern und CSV-Download: `/admin/` – **muss in Plesk passwortgeschützt sein** (ohne Schutz zeigt die Seite nur einen Hinweis)
- Einstellungen (Empfänger, Absender): `inc/config.php`
