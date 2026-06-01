<?php
/**
 * Smarty Plugin: {reclamation_btn order_id=12345}
 * 
 * Zeigt den Reklamations-Button nur wenn:
 * - Bestellung Status 3 (Versendet) hat
 * - Mindestens 10 Tage seit Versand vergangen sind
 * - Maximal 60 Tage seit Bestelldatum
 * 
 * Sicherheit:
 * - Generiert einen Einmal-Token (CSRF-Schutz)
 * - Token wird in $_SESSION['reclamation_tokens'][] gespeichert
 * - Link zeigt auf reklamation_start.php (validiert Token + Bestellung)
 * 
 * Pfad: includes/external/smarty/plugins/function.reclamation_btn.php
 */
function smarty_function_reclamation_btn($params, $template) {
  $orders_id = (int)($params['order_id'] ?? 0);
  if ($orders_id <= 0) return '';

  // Bestelldaten holen
  $order_query = xtc_db_query("SELECT orders_status, date_purchased FROM " . TABLE_ORDERS . " WHERE orders_id = '" . $orders_id . "'");
  $order = xtc_db_fetch_array($order_query);
  if (!$order) return '';

  $orders_status = (int)$order['orders_status'];
  $date_purchased = strtotime($order['date_purchased']);
  $now = time();
  $days_since_order = ($now - $date_purchased) / 86400;

  // Regel: Max 60 Tage seit Bestellung -> abgelaufen
  if ($days_since_order > 60 && $orders_status == 3) {
    $text = defined('TEXT_RECLAMATION_BTN_EXPIRED') ? TEXT_RECLAMATION_BTN_EXPIRED : 'Reklamationsfrist abgelaufen (60 Tage)';
    return '<span class="btn btn-sm btn-outline-secondary disabled" style="pointer-events:none; opacity:0.5;">
              <i class="fa-solid fa-ban me-1"></i> ' . $text . '
            </span>';
  }

  // Regel: Status muss 3 (Versendet) sein
  if ($orders_status != 3) {
    return ''; // Kein Button wenn nicht versendet
  }

  // Regel: Mindestens 10 Tage seit Versand
  $shipped_query = xtc_db_query("SELECT date_added FROM " . TABLE_ORDERS_STATUS_HISTORY . " 
                                  WHERE orders_id = '" . $orders_id . "' 
                                    AND orders_status_id = 3 
                                  ORDER BY date_added DESC LIMIT 1");
  $shipped = xtc_db_fetch_array($shipped_query);
  
  if ($shipped) {
    $date_shipped = strtotime($shipped['date_added']);
    $days_since_shipped = ($now - $date_shipped) / 86400;
    
    if ($days_since_shipped < 10) {
      $available_date = date('d.m.Y', $date_shipped + (10 * 86400));
      $text = defined('TEXT_RECLAMATION_BTN_TOO_EARLY') ? TEXT_RECLAMATION_BTN_TOO_EARLY : 'Reklamation ab ' . $available_date . ' m&ouml;glich';
      return '<span class="btn btn-sm btn-outline-warning disabled" style="pointer-events:none; opacity:0.6;">
                <i class="fa-solid fa-clock me-1"></i> ' . $text . '
              </span>';
    }
  }

  // ============================================================
  // Alles OK: CSRF-Token generieren und Button anzeigen
  // ============================================================
  
  // Einmal-Token generieren (32 Zeichen hex)
  $token = bin2hex(random_bytes(16));
  
  // Token in Session speichern (max. 10 Tokens gleichzeitig, aelteste entfernen)
  if (!isset($_SESSION['reclamation_tokens']) || !is_array($_SESSION['reclamation_tokens'])) {
    $_SESSION['reclamation_tokens'] = array();
  }
  // Maximal 10 Tokens behalten (FIFO)
  if (count($_SESSION['reclamation_tokens']) >= 10) {
    array_shift($_SESSION['reclamation_tokens']);
  }
  $_SESSION['reclamation_tokens'][] = $token;

  // Button-Text
  $text = defined('TEXT_RECLAMATION_BTN_SUBMIT') ? TEXT_RECLAMATION_BTN_SUBMIT : 'Reklamation einreichen';
  
  // Link auf reklamation_start.php mit oID und Token
  $base = (defined('HTTPS_SERVER') ? rtrim(HTTPS_SERVER, '/') : 'https://mr-hanf.de');
  $link = $base . '/reklamation_start.php?oID=' . $orders_id . '&amp;token=' . $token;
  
  return '<a href="' . $link . '" class="btn btn-sm btn-outline-danger">
            <i class="fa-solid fa-triangle-exclamation me-1"></i> ' . $text . '
          </a>';
}
