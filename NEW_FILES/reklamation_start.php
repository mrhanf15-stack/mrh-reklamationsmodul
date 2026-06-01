<?php
/**
 * reklamation_start.php
 * 
 * Sicherer Einstiegspunkt fuer Reklamationen aus der Bestellhistorie.
 * 
 * Sicherheitsmassnahmen:
 * - Nur GET-Requests erlaubt
 * - Kunde muss eingeloggt sein (sonst Redirect zum Login)
 * - Rate-Limiting: max. 5 Aufrufe pro Minute pro Session
 * - CSRF-Schutz: Einmal-Token aus Session (vom Smarty-Plugin generiert)
 * - Input-Sanitizing: oID nur als Integer
 * - Keine sensiblen Daten in Fehlermeldungen oder URLs
 * - Keine direkte Ausgabe (nur Redirects)
 * - Session-Daten mit Timestamp (Ablauf nach 5 Minuten)
 * 
 * URL: /reklamation_start.php?oID=BESTELLNR&token=EINMAL_TOKEN
 * Pfad: ROOT/reklamation_start.php
 */

// ============================================================
// 1. Modified-Umgebung laden
// ============================================================
define('_VALID_XTC', true);
require('includes/application_top.php');

// ============================================================
// 2. Nur GET-Requests erlauben
// ============================================================
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    header('HTTP/1.1 405 Method Not Allowed');
    header('Allow: GET');
    exit;
}

// ============================================================
// 3. Reklamationsseite URL (fuer Redirects)
// ============================================================
$reclamation_url = xtc_href_link('shop_content.php', 'coID=1840', 'SSL');

// ============================================================
// 4. Kunde muss eingeloggt sein
// ============================================================
if (!isset($_SESSION['customer_id']) || (int)$_SESSION['customer_id'] <= 0) {
    // Zum Login weiterleiten
    xtc_redirect(xtc_href_link(FILENAME_LOGIN, '', 'SSL'));
    exit;
}

$customer_id = (int)$_SESSION['customer_id'];

// ============================================================
// 5. Rate-Limiting (Session-basiert, max. 5 Aufrufe/Minute)
// ============================================================
$now = time();
if (!isset($_SESSION['reclamation_rate_limit'])) {
    $_SESSION['reclamation_rate_limit'] = array();
}

// Alte Eintraege (aelter als 60 Sekunden) entfernen
$_SESSION['reclamation_rate_limit'] = array_filter(
    $_SESSION['reclamation_rate_limit'],
    function($timestamp) use ($now) {
        return ($now - $timestamp) < 60;
    }
);

// Pruefen ob Limit erreicht
if (count($_SESSION['reclamation_rate_limit']) >= 5) {
    $_SESSION['reclamation_error'] = 'Zu viele Anfragen. Bitte warten Sie einen Moment.';
    xtc_redirect($reclamation_url);
    exit;
}

// Aktuellen Aufruf registrieren
$_SESSION['reclamation_rate_limit'][] = $now;

// ============================================================
// 6. Input-Sanitizing: oID nur als Integer
// ============================================================
$orders_id = isset($_GET['oID']) ? (int)$_GET['oID'] : 0;

if ($orders_id <= 0) {
    xtc_redirect($reclamation_url);
    exit;
}

// ============================================================
// 7. CSRF-Schutz: Einmal-Token pruefen
//    Token wird vom Smarty-Plugin (function.reclamation_btn.php)
//    in der Session gespeichert und im Link uebergeben.
// ============================================================
$token = isset($_GET['token']) ? trim($_GET['token']) : '';

if (empty($token)
    || !isset($_SESSION['reclamation_tokens'])
    || !is_array($_SESSION['reclamation_tokens'])
    || !in_array($token, $_SESSION['reclamation_tokens'], true)
) {
    // Ungültiger oder fehlender Token
    $_SESSION['reclamation_error'] = 'Ungueltiger Zugriff. Bitte nutzen Sie den Button in Ihrer Bestellhistorie.';
    xtc_redirect($reclamation_url);
    exit;
}

// Token nach Verwendung entfernen (Einmal-Token)
$_SESSION['reclamation_tokens'] = array_diff($_SESSION['reclamation_tokens'], array($token));
// Array-Keys neu indizieren
$_SESSION['reclamation_tokens'] = array_values($_SESSION['reclamation_tokens']);

// ============================================================
// 8. Sprachdatei laden
// ============================================================
$lang_file = DIR_WS_LANGUAGES . $_SESSION['language'] . '/extra/reclamation.php';
if (file_exists($lang_file)) {
    require_once($lang_file);
}

// ============================================================
// 9. Bestellung validieren
// ============================================================

// 9a. Gehoert die Bestellung dem eingeloggten Kunden?
$order_query = xtc_db_query(
    "SELECT o.orders_id, o.orders_status, o.date_purchased, o.customers_email_address
     FROM " . TABLE_ORDERS . " o
     WHERE o.orders_id = '" . (int)$orders_id . "'
       AND o.customers_id = '" . (int)$customer_id . "'"
);
$order = xtc_db_fetch_array($order_query);

if (!$order) {
    // Generische Fehlermeldung (keine Details preisgeben)
    $_SESSION['reclamation_error'] = 'Diese Bestellung konnte nicht zugeordnet werden.';
    xtc_redirect($reclamation_url);
    exit;
}

// 9b. Status pruefen: Muss versendet sein (Status 3)
if ((int)$order['orders_status'] != 3) {
    $_SESSION['reclamation_error'] = 'Eine Reklamation ist nur bei versendeten Bestellungen moeglich.';
    xtc_redirect($reclamation_url);
    exit;
}

// 9c. 60-Tage-Frist pruefen (ab Bestelldatum)
$date_purchased = strtotime($order['date_purchased']);
$days_since_order = (time() - $date_purchased) / 86400;

if ($days_since_order > 60) {
    $text = defined('TEXT_RECLAMATION_EXPIRED')
        ? TEXT_RECLAMATION_EXPIRED
        : 'Die Reklamationsfrist ist leider abgelaufen.';
    $_SESSION['reclamation_error'] = $text;
    xtc_redirect($reclamation_url);
    exit;
}

// 9d. 10-Tage-Wartezeit pruefen (ab Versanddatum)
$shipped_query = xtc_db_query(
    "SELECT date_added FROM " . TABLE_ORDERS_STATUS_HISTORY . "
     WHERE orders_id = '" . (int)$orders_id . "'
       AND orders_status_id = 3
     ORDER BY date_added DESC LIMIT 1"
);
$shipped = xtc_db_fetch_array($shipped_query);

if ($shipped) {
    $date_shipped = strtotime($shipped['date_added']);
    $days_since_shipped = (time() - $date_shipped) / 86400;

    if ($days_since_shipped < 10) {
        $available_date = date('d.m.Y', $date_shipped + (10 * 86400));
        $text = defined('TEXT_RECLAMATION_BTN_TOO_EARLY')
            ? TEXT_RECLAMATION_BTN_TOO_EARLY
            : 'Reklamation erst ab ' . $available_date . ' moeglich.';
        $_SESSION['reclamation_error'] = $text;
        xtc_redirect($reclamation_url);
        exit;
    }
}

// ============================================================
// 10. Alles OK! Session-Daten setzen fuer die Reklamationsseite
// ============================================================
$_SESSION['reclamation_auto'] = array(
    'orders_id'  => (int)$orders_id,
    'email'      => $order['customers_email_address'],
    'validated'  => true,
    'timestamp'  => time()  // Ablauf nach 5 Minuten in reclamation.php
);

// ============================================================
// 11. Weiterleitung zur Reklamationsseite mit auto-Flag
// ============================================================
xtc_redirect(xtc_href_link('shop_content.php', 'coID=1840&auto=1', 'SSL'));
exit;
