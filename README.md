# MRH Reklamationsmodul

Reklamationsmodul fuer modified eCommerce (Mr. Hanf) mit Kategorie-Logik (Samen/Pflanzen/Zubehoer), Bildupload, Anti-Betrugs-Fragen und Admin-Dashboard.

## Features

- **3 Produkt-Kategorien**: Samen (Kulanz), Pflanzen (ausgeschlossen), Zubehoer (Gewaehrleistung)
- **Samen-spezifische Fragen**: Keimmethode, Temperatur, Wartezeit, Lagerung
- **Bildupload**: Max. 5 Bilder (JPG, PNG, HEIC, WebP), je max. 10 MB
- **Anti-Betrug**: IP-Logging, Rate-Limiting (max. 3 Reklamationen/Bestellung), Honeypot
- **Admin-Dashboard**: Uebersicht, Filter, Status-Aenderung, Detail-Modal mit AJAX
- **Admin Order Block**: Reklamationen direkt in der Bestelldetail-Ansicht
- **E-Mail-Benachrichtigung**: Admin + Kundenbestaetigung (HTML-Templates)
- **4 Sprachen**: Deutsch, Englisch, Franzoesisch, Spanisch

## Dateistruktur

```
NEW_FILES/
  media/content/reclamation.php              # Controller (Frontend)

MODULE_FILES/
  includes/extra/database_tables/
    reclamation.php                           # DB-Tabellen-Konstanten

  admin_q9wKj6Ds/
    reclamation_dashboard.php                 # Admin-Dashboard
    includes/modules/system/
      reclamation.php                         # DB-Installer
    includes/extra/modules/orders/orders_info_blocks/
      reclamation.php                         # Admin Order Info Block
    includes/extra/filenames/
      reclamation_dashboard.php               # Filename-Konstante
    includes/extra/menu/
      reclamation_dashboard.php               # Admin-Menu-Eintrag

  templates/tpl_mrh_2026/
    module/reclamation.html                   # Smarty Frontend-Template
    mail/german/
      reclamation_mail.html                   # Admin-Mail DE
      reclamation_confirm_mail.html           # Kunden-Mail DE
    mail/english/
      reclamation_mail.html                   # Admin-Mail EN
      reclamation_confirm_mail.html           # Kunden-Mail EN
    mail/french/
      reclamation_mail.html                   # Admin-Mail FR
      reclamation_confirm_mail.html           # Kunden-Mail FR
    mail/spanish/
      reclamation_mail.html                   # Admin-Mail ES
      reclamation_confirm_mail.html           # Kunden-Mail ES

  lang/german/
    extra/reclamation.php                     # Frontend-Sprache DE
    extra/admin/reclamation.php               # Admin-Sprache DE
    modules/system/reclamation.php            # System-Modul-Sprache DE
  lang/english/
    extra/reclamation.php                     # Frontend-Sprache EN
    extra/admin/reclamation.php               # Admin-Sprache EN
    modules/system/reclamation.php            # System-Modul-Sprache EN
  lang/french/
    extra/reclamation.php                     # Frontend-Sprache FR
    extra/admin/reclamation.php               # Admin-Sprache FR
    modules/system/reclamation.php            # System-Modul-Sprache FR
  lang/spanish/
    extra/reclamation.php                     # Frontend-Sprache ES
    extra/admin/reclamation.php               # Admin-Sprache ES
    modules/system/reclamation.php            # System-Modul-Sprache ES
```

## DB-Tabellen

| Tabelle | Beschreibung |
|---------|-------------|
| `orders_reclamation` | Haupttabelle (Status, Datum, IP) |
| `orders_reclamation_products` | Reklamierte Produkte (inkl. Samen-Felder) |
| `orders_reclamation_images` | Hochgeladene Bilder |

## Installation

1. Modul unter **Module > System Module** installieren
2. Konfiguration pruefen (Status, Captcha, E-Mail)
3. Content-Seite verlinken (z.B. `reclamation.php`)
4. Upload-Verzeichnis `images/reclamation/` wird automatisch erstellt

## Konfiguration

| Key | Beschreibung |
|-----|-------------|
| `MODULE_RECLAMATION_STATUS` | Modul aktiv (true/false) |
| `MODULE_RECLAMATION_CAPTCHA` | Captcha aktiv (true/false) |
| `MODULE_RECLAMATION_CONTENT` | Zugeordnete Content-Seite |
| `MODULE_RECLAMATION_EMAIL` | Zusaetzliche E-Mail-Adresse |

## Version

- **v1.00** – Erstversion mit 4 Sprachen (DE, EN, FR, ES)
