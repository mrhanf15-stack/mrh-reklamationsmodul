<?php
/* -----------------------------------------------------------------------------------------
   $Id$

   modified eCommerce Shopsoftware
   http://www.modified-shop.org

   Copyright (c) 2009 - 2013 [www.modified-shop.org]
   -----------------------------------------------------------------------------------------
   Released under the GNU General Public License
   
   MRH 2026: Reklamations-Dashboard fuer Mr. Hanf
   Standalone Admin-Seite mit eigenem Bootstrap 5 (wie mrh_batch_packingslip.php)
   ---------------------------------------------------------------------------------------*/

  require('includes/application_top.php');

  // Modul-Check
  if (!defined('MODULE_RECLAMATION_STATUS') || MODULE_RECLAMATION_STATUS != 'true') {
    xtc_redirect(xtc_href_link(FILENAME_DEFAULT));
  }

  // Admin-URL fuer Links
  $admin_url = (defined('HTTPS_SERVER') && HTTPS_SERVER != '') 
    ? HTTPS_SERVER . DIR_WS_ADMIN 
    : HTTP_SERVER . DIR_WS_ADMIN;

  // === Self-Healing: Zoho-Spalten in orders_reclamation ===
  $zoho_cols = array(
    'zoho_ticket_id' => "ALTER TABLE `orders_reclamation` ADD COLUMN `zoho_ticket_id` VARCHAR(50) DEFAULT NULL AFTER `ip_address`",
    'zoho_ticket_nr' => "ALTER TABLE `orders_reclamation` ADD COLUMN `zoho_ticket_nr` VARCHAR(50) DEFAULT NULL AFTER `zoho_ticket_id`",
    'zoho_unread_count' => "ALTER TABLE `orders_reclamation` ADD COLUMN `zoho_unread_count` INT(11) DEFAULT 0 AFTER `zoho_ticket_nr`",
    'zoho_last_thread_id' => "ALTER TABLE `orders_reclamation` ADD COLUMN `zoho_last_thread_id` VARCHAR(50) DEFAULT NULL AFTER `zoho_unread_count`",
    'is_archived' => "ALTER TABLE `orders_reclamation` ADD COLUMN `is_archived` TINYINT(1) DEFAULT 0 AFTER `zoho_last_thread_id`",
  );
  foreach ($zoho_cols as $col => $sql) {
    $check = xtc_db_query("SHOW COLUMNS FROM `orders_reclamation` LIKE '" . $col . "'");
    if (xtc_db_num_rows($check) < 1) {
      xtc_db_query($sql);
    }
  }

  // === AJAX-Endpunkte ===
  if (isset($_GET['ajax'])) {
    header('Content-Type: application/json; charset=utf-8');
    
    switch ($_GET['ajax']) {
      case 'update_status':
        $recl_id = (int)$_POST['reclamation_id'];
        $new_status = xtc_db_prepare_input($_POST['new_status']);
        $comment = xtc_db_prepare_input(mb_substr($_POST['admin_comment'], 0, 1000));
        
        $allowed = array('open', 'in_progress', 'resolved', 'rejected', 'closed');
        if (in_array($new_status, $allowed)) {
          // Pruefen ob vorher schon ein Ticket existiert
          $prev_recl = xtc_db_fetch_array(xtc_db_query("SELECT reclamation_status, zoho_ticket_id, customers_email, customers_name, orders_id FROM " . TABLE_ORDERS_RECLAMATION . " WHERE reclamation_id = '" . $recl_id . "'"));
          
          xtc_db_query("UPDATE " . TABLE_ORDERS_RECLAMATION . " 
                            SET reclamation_status = '" . xtc_db_input($new_status) . "',
                                admin_comment = '" . xtc_db_input($comment) . "',
                                admin_date = NOW()
                          WHERE reclamation_id = '" . $recl_id . "'");
          
          $response = array('success' => true, 'message' => 'Status aktualisiert');
          
          // === Auto-Mail bei Wechsel zu 'in_progress' (Bestaetigungs-Mail direkt an Kunden, KEIN Ticket) ===
          if ($new_status == 'in_progress' && $prev_recl['reclamation_status'] == 'open') {
            $confirm_subject = 'Reklamation Bestellung #' . $prev_recl['orders_id'] . ' - Wir pruefen deinen Fall';
            $confirm_body = 'Hallo ' . $prev_recl['customers_name'] . ",\n\n"
              . "vielen Dank fuer deine Nachricht zu deiner Bestellung #" . $prev_recl['orders_id'] . ".\n\n"
              . "Wir haben deine Reklamation erhalten und unsere Spezialisten pruefen den Fall. "
              . "Du erhaeltst in Kuerze eine ausfuehrliche Antwort von uns.\n\n"
              . "Bitte antworte einfach auf diese E-Mail, falls du weitere Informationen ergaenzen moechtest.\n\n"
              . "Dein Mr. Hanf Team";
            
            // Mail direkt senden (Shop-eigene Mailfunktion)
            $mail_headers = 'From: Mr. Hanf Reklamation <info@mr-hanf.de>' . "\r\n";
            $mail_headers .= 'Reply-To: info@mr-hanf.de' . "\r\n";
            $mail_headers .= 'Content-Type: text/plain; charset=UTF-8' . "\r\n";
            $mail_sent = mail($prev_recl['customers_email'], $confirm_subject, $confirm_body, $mail_headers);
            
            if ($mail_sent) {
              $response['email_sent'] = true;
              $response['message'] = 'Status aktualisiert + Bestaetigungs-Mail gesendet';
            } else {
              $response['email_error'] = 'Mail konnte nicht gesendet werden';
            }
          }
          
          echo json_encode($response);
        } else {
          echo json_encode(array('success' => false, 'message' => 'Ungueltiger Status'));
        }
        exit;
        
      case 'get_detail':
        $recl_id = (int)$_GET['id'];
        $recl = xtc_db_fetch_array(xtc_db_query("SELECT r.*, o.date_purchased, o.payment_method 
                                                    FROM " . TABLE_ORDERS_RECLAMATION . " r
                                                    LEFT JOIN " . TABLE_ORDERS . " o ON r.orders_id = o.orders_id
                                                   WHERE r.reclamation_id = '" . $recl_id . "'"));
        if ($recl) {
          // Produkte laden
          $products = array();
          $p_query = xtc_db_query("SELECT * FROM " . TABLE_ORDERS_RECLAMATION_PRODUCTS . " WHERE reclamation_id = '" . $recl_id . "'");
          while ($p = xtc_db_fetch_array($p_query)) {
            $products[] = $p;
          }
          $recl['products'] = $products;
          
          // Bilder laden
          $images = array();
          $i_query = xtc_db_query("SELECT * FROM " . TABLE_ORDERS_RECLAMATION_IMAGES . " WHERE reclamation_id = '" . $recl_id . "'");
          while ($i = xtc_db_fetch_array($i_query)) {
            $images[] = $i;
          }
          $recl['images'] = $images;
          
          echo json_encode(array('success' => true, 'data' => $recl));
        } else {
          echo json_encode(array('success' => false, 'message' => 'Nicht gefunden'));
        }
        exit;
        
      case 'ai_generate':
        // KI-Textgenerierung via OpenRouter
        $recl_id = (int)$_POST['reclamation_id'];
        $custom_prompt = isset($_POST['custom_prompt']) ? trim($_POST['custom_prompt']) : '';
        
        // Reklamationsdaten laden
        $recl = xtc_db_fetch_array(xtc_db_query("SELECT * FROM " . TABLE_ORDERS_RECLAMATION . " WHERE reclamation_id = '" . $recl_id . "'"));
        if (!$recl) {
          echo json_encode(array('success' => false, 'message' => 'Reklamation nicht gefunden'));
          exit;
        }
        $products = array();
        $p_query = xtc_db_query("SELECT * FROM " . TABLE_ORDERS_RECLAMATION_PRODUCTS . " WHERE reclamation_id = '" . $recl_id . "'");
        while ($p = xtc_db_fetch_array($p_query)) {
          $products[] = $p;
        }
        
        // Kontext fuer KI zusammenbauen
        $product_info = '';
        foreach ($products as $p) {
          $product_info .= '- ' . $p['products_quantity'] . 'x ' . $p['products_name'] . ' (Art.' . $p['products_model'] . '), ';
          $product_info .= 'Grund: ' . $p['reclamation_reason'] . ', Beschreibung: ' . $p['reclamation_description'];
          if ($p['product_category'] == 'seed') {
            $product_info .= ', Keimmethode: ' . $p['seed_germination_method'] . ', Temp: ' . $p['seed_temperature'] . ', Tage: ' . $p['seed_days_waited'];
          }
          $product_info .= "\n";
        }
        
        $system_prompt = "Du bist ein freundlicher Kundenservice-Mitarbeiter von Mr. Hanf (Cannabis Samen Shop). "
          . "Schreibe eine professionelle, empathische Antwort auf eine Kundenreklamation. "
          . "Verwende 'Du' als Anrede. Sei loesungsorientiert. "
          . "Halte die Antwort kurz und praegnant. Unterschreibe mit 'Dein Mr. Hanf Team'.\n\n"
          . "=== BEISPIEL-ANTWORTEN ALS STILVORLAGE ===\n\n"
          . "Beispiel Nichtkeimung ausserhalb der Kulanzzeit:\n"
          . "Ich habe mir deine Bestellung angeschaut. Leider ist es grundsaetzlich so, dass es auf die Keimung keine Garantie gibt - weder von uns noch vom Hersteller. Die Keimung haengt von vielen Faktoren ab. Zu einer Kulanz muss ich dir leider sagen, dass wir diese nur innerhalb von 30 Tagen nach Erhalt anbieten koennen. Nach dieser Frist haben wir keine Moeglichkeit mehr, die Lagerbedingungen auf Kundenseite nachzuvollziehen. Fuer zukuenftige Bestellungen wuerde ich empfehlen, uns innerhalb der 30 Tage zu kontaktieren.\n\n"
          . "Beispiel Nichtkeimung innerhalb Kulanzzeit:\n"
          . "Es tut mir leid zu hoeren, dass nicht alle Samen gekeimt sind. Leider gibt es auf die Keimung grundsaetzlich keine Garantie, da sie von vielen Faktoren abhaengt. Trotzdem moechten wir dir entgegenkommen. Option 1: 25% Kulanz auf die nicht gekeimten Samen als Gutschrift oder Gutschein. Option 2: Ersatzmenge aus unserem hauseigenen Sortiment (nur Versandkosten). Sag uns einfach welche Option dir lieber ist.\n\n"
          . "Beispiel falsche Bestellung komplett:\n"
          . "Es tut mir leid, dass du offenbar falsche Samen erhalten hast. Bitte schick uns ein Foto der gelieferten Samenverpackung sowie ein Bild des durchsichtigen Baggies (mit Bestellnummer). Antworte einfach auf diese E-Mail mit den Bildern, dann schauen wir uns das direkt an.\n\n"
          . "Beispiel falsche Samenmenge:\n"
          . "Bitte entschuldige die Verwechslung. Bitte antworte kurz mit einem Foto der erhaltenen Verpackung, damit wir den Vorgang sauber zuordnen koennen. Dann kuemmern wir uns schnellstmoeglich darum.\n\n"
          . "Beispiel falsches Produkt/Art:\n"
          . "Bitte entschuldige die Verwechslung bei deiner Bestellung. Damit wir das sauber pruefen koennen, schick uns bitte ein Foto der erhaltenen Samenverpackung und am besten auch ein Foto des Etiketts bzw. der Rueckseite der Verpackung.\n\n"
          . "=== ENDE BEISPIELE ===\n"
          . "Passe den Stil und Ton der obigen Beispiele an, aber formuliere die Antwort passend zur konkreten Situation des Kunden. Verwende die konkreten Bestelldaten und Produktnamen.";
        
        $user_message = "Kunde: " . $recl['customers_name'] . "\n"
          . "Bestellung: #" . $recl['orders_id'] . "\n"
          . "Reklamierte Produkte:\n" . $product_info;
        
        if (!empty($custom_prompt)) {
          $user_message .= "\nZusaetzliche Anweisung: " . $custom_prompt;
        }
        
        // OpenRouter API aufrufen - Key aus DB-Konfiguration
        $or_key_q = xtc_db_query("SELECT configuration_value FROM configuration WHERE configuration_key = 'MODULE_RECLAMATION_OPENROUTER_KEY'");
        $or_key_row = xtc_db_fetch_array($or_key_q);
        $openrouter_key = $or_key_row['configuration_value'] ?? '';;
        if (empty($openrouter_key)) {
          echo json_encode(array('success' => false, 'message' => 'OpenRouter API-Key nicht konfiguriert. Bitte in der DB unter MODULE_RECLAMATION_OPENROUTER_KEY eintragen.'));
          exit;
        }
        $ch = curl_init('https://openrouter.ai/api/v1/chat/completions');
        curl_setopt_array($ch, array(
          CURLOPT_RETURNTRANSFER => true,
          CURLOPT_POST => true,
          CURLOPT_HTTPHEADER => array(
            'Content-Type: application/json',
            'Authorization: Bearer ' . $openrouter_key,
          ),
          CURLOPT_POSTFIELDS => json_encode(array(
            'model' => 'anthropic/claude-sonnet-4',
            'messages' => array(
              array('role' => 'system', 'content' => $system_prompt),
              array('role' => 'user', 'content' => $user_message),
            ),
            'max_tokens' => 500,
            'temperature' => 0.7,
          )),
          CURLOPT_TIMEOUT => 30,
        ));
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($http_code == 200) {
          $data = json_decode($response, true);
          $ai_text = isset($data['choices'][0]['message']['content']) ? $data['choices'][0]['message']['content'] : '';
          echo json_encode(array('success' => true, 'text' => $ai_text));
        } else {
          echo json_encode(array('success' => false, 'message' => 'OpenRouter Fehler (HTTP ' . $http_code . '): ' . $response));
        }
        exit;

      case 'zoho_token':
        // HMAC-Token fuer Zoho Desk API generieren
        $secret_q = xtc_db_query("SELECT configuration_value FROM configuration WHERE configuration_key = 'MODULE_ZOHO_DESK_CLIENT_SECRET'");
        $secret_row = xtc_db_fetch_array($secret_q);
        $token = hash_hmac('sha256', date('Y-m-d'), $secret_row['configuration_value'] ?? '');
        echo json_encode(array('success' => true, 'token' => $token));
        exit;

      case 'check_unread':
        // Alle Reklamationen mit Zoho-Ticket pruefen auf neue Antworten
        $tickets_q = xtc_db_query("SELECT reclamation_id, zoho_ticket_id, zoho_last_thread_id FROM " . TABLE_ORDERS_RECLAMATION . " WHERE zoho_ticket_id IS NOT NULL AND zoho_ticket_id != '' AND reclamation_status NOT IN ('closed','resolved','rejected')");
        $unread_map = array();
        
        $secret_q2 = xtc_db_query("SELECT configuration_value FROM configuration WHERE configuration_key = 'MODULE_ZOHO_DESK_CLIENT_SECRET'");
        $secret_row2 = xtc_db_fetch_array($secret_q2);
        $token2 = hash_hmac('sha256', date('Y-m-d'), $secret_row2['configuration_value'] ?? '');
        $catalog_url2 = (defined('HTTPS_CATALOG_SERVER') ? HTTPS_CATALOG_SERVER : HTTP_CATALOG_SERVER) . DIR_WS_CATALOG;
        
        while ($t = xtc_db_fetch_array($tickets_q)) {
          $conv_url = $catalog_url2 . 'zoho_desk_api.php?action=conversations&ticket_id=' . $t['zoho_ticket_id'] . '&token=' . $token2;
          $ch = curl_init($conv_url);
          curl_setopt_array($ch, array(CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 10, CURLOPT_SSL_VERIFYPEER => false));
          $conv_resp = curl_exec($ch);
          curl_close($ch);
          $conv_data = json_decode($conv_resp, true);
          
          if (isset($conv_data['conversations'])) {
            // Zaehle eingehende Nachrichten (direction=in) nach dem letzten bekannten Thread
            $unread = 0;
            $last_known = $t['zoho_last_thread_id'];
            $found_last = empty($last_known); // Wenn kein letzter bekannt, alle zaehlen
            $newest_thread_id = '';
            
            foreach ($conv_data['conversations'] as $thread) {
              if (empty($newest_thread_id)) $newest_thread_id = $thread['id'];
              if (!$found_last && $thread['id'] == $last_known) {
                $found_last = true;
                continue;
              }
              if ($found_last && isset($thread['direction']) && $thread['direction'] == 'in') {
                $unread++;
              }
            }
            
            // Update DB
            if ($unread != (int)$t['zoho_unread_count'] || 0) {
              xtc_db_query("UPDATE " . TABLE_ORDERS_RECLAMATION . " SET zoho_unread_count = '" . (int)$unread . "' WHERE reclamation_id = '" . (int)$t['reclamation_id'] . "'");
            }
            $unread_map[(int)$t['reclamation_id']] = $unread;
          }
        }
        echo json_encode(array('success' => true, 'unread' => $unread_map));
        exit;

      case 'close_ticket':
        $recl_id = (int)$_POST['reclamation_id'];
        $recl_data = xtc_db_fetch_array(xtc_db_query("SELECT zoho_ticket_id FROM " . TABLE_ORDERS_RECLAMATION . " WHERE reclamation_id = '" . $recl_id . "'"));
        
        if (empty($recl_data['zoho_ticket_id'])) {
          echo json_encode(array('success' => false, 'message' => 'Kein Zoho-Ticket vorhanden'));
          exit;
        }
        
        $secret_q3 = xtc_db_query("SELECT configuration_value FROM configuration WHERE configuration_key = 'MODULE_ZOHO_DESK_CLIENT_SECRET'");
        $secret_row3 = xtc_db_fetch_array($secret_q3);
        $token3 = hash_hmac('sha256', date('Y-m-d'), $secret_row3['configuration_value'] ?? '');
        $catalog_url3 = (defined('HTTPS_CATALOG_SERVER') ? HTTPS_CATALOG_SERVER : HTTP_CATALOG_SERVER) . DIR_WS_CATALOG;
        
        $close_url = $catalog_url3 . 'zoho_desk_api.php?action=update_status&token=' . $token3;
        $ch = curl_init($close_url);
        curl_setopt_array($ch, array(
          CURLOPT_RETURNTRANSFER => true,
          CURLOPT_POST => true,
          CURLOPT_POSTFIELDS => http_build_query(array('ticket_id' => $recl_data['zoho_ticket_id'], 'status' => 'Closed')),
          CURLOPT_TIMEOUT => 10,
          CURLOPT_SSL_VERIFYPEER => false,
        ));
        $close_resp = curl_exec($ch);
        curl_close($ch);
        $close_data = json_decode($close_resp, true);
        
        if (isset($close_data['success']) && $close_data['success']) {
          // Auch Reklamation auf closed setzen
          xtc_db_query("UPDATE " . TABLE_ORDERS_RECLAMATION . " SET reclamation_status = 'closed', zoho_unread_count = 0 WHERE reclamation_id = '" . $recl_id . "'");
          echo json_encode(array('success' => true, 'message' => 'Ticket geschlossen'));
        } else {
          echo json_encode(array('success' => false, 'message' => 'Zoho-Fehler: ' . (isset($close_data['error']) ? $close_data['error'] : 'Unbekannt')));
        }
        exit;

      case 'mark_read':
        // Unread-Counter zuruecksetzen und letzten Thread merken
        $recl_id = (int)$_POST['reclamation_id'];
        $last_thread = isset($_POST['last_thread_id']) ? xtc_db_prepare_input($_POST['last_thread_id']) : '';
        xtc_db_query("UPDATE " . TABLE_ORDERS_RECLAMATION . " SET zoho_unread_count = 0, zoho_last_thread_id = '" . xtc_db_input($last_thread) . "' WHERE reclamation_id = '" . $recl_id . "'");
        echo json_encode(array('success' => true));
        exit;

      case 'save_ticket_id':
        $recl_id = (int)$_POST['reclamation_id'];
        $ticket_id = xtc_db_prepare_input($_POST['ticket_id']);
        $ticket_nr = xtc_db_prepare_input(isset($_POST['ticket_nr']) ? $_POST['ticket_nr'] : '');
        xtc_db_query("UPDATE " . TABLE_ORDERS_RECLAMATION . " 
          SET zoho_ticket_id = '" . xtc_db_input($ticket_id) . "',
              zoho_ticket_nr = '" . xtc_db_input($ticket_nr) . "'
          WHERE reclamation_id = '" . $recl_id . "'");
        echo json_encode(array('success' => true));
        exit;

      case 'archive':
        $recl_id = (int)$_POST['reclamation_id'];
        xtc_db_query("UPDATE " . TABLE_ORDERS_RECLAMATION . " SET is_archived = 1 WHERE reclamation_id = '" . $recl_id . "'");
        echo json_encode(array('success' => true, 'message' => 'Reklamation archiviert'));
        exit;

      case 'unarchive':
        $recl_id = (int)$_POST['reclamation_id'];
        xtc_db_query("UPDATE " . TABLE_ORDERS_RECLAMATION . " SET is_archived = 0 WHERE reclamation_id = '" . $recl_id . "'");
        echo json_encode(array('success' => true, 'message' => 'Reklamation wiederhergestellt'));
        exit;

      case 'stats':
        $stats = array();
        $s_query = xtc_db_query("SELECT reclamation_status, COUNT(*) as cnt FROM " . TABLE_ORDERS_RECLAMATION . " WHERE (is_archived = 0 OR is_archived IS NULL) GROUP BY reclamation_status");
        while ($s = xtc_db_fetch_array($s_query)) {
          $stats[$s['reclamation_status']] = (int)$s['cnt'];
        }
        $total = xtc_db_fetch_array(xtc_db_query("SELECT COUNT(*) as cnt FROM " . TABLE_ORDERS_RECLAMATION . " WHERE (is_archived = 0 OR is_archived IS NULL)"));
        $stats['total'] = (int)$total['cnt'];
        $archived_cnt = xtc_db_fetch_array(xtc_db_query("SELECT COUNT(*) as cnt FROM " . TABLE_ORDERS_RECLAMATION . " WHERE is_archived = 1"));
        $stats['archived'] = (int)$archived_cnt['cnt'];
        
        // Samen vs Zubehoer
        $seed_cnt = xtc_db_fetch_array(xtc_db_query("SELECT COUNT(*) as cnt FROM " . TABLE_ORDERS_RECLAMATION_PRODUCTS . " WHERE product_category = 'seed'"));
        $stats['seed_products'] = (int)$seed_cnt['cnt'];
        $default_cnt = xtc_db_fetch_array(xtc_db_query("SELECT COUNT(*) as cnt FROM " . TABLE_ORDERS_RECLAMATION_PRODUCTS . " WHERE product_category = 'default'"));
        $stats['default_products'] = (int)$default_cnt['cnt'];
        
        echo json_encode(array('success' => true, 'data' => $stats));
        exit;
    }
  }

  // === Filter-Parameter ===
  $filter_status = isset($_GET['status']) ? xtc_db_prepare_input($_GET['status']) : '';
  $filter_search = isset($_GET['search']) ? xtc_db_prepare_input($_GET['search']) : '';
  $show_archive = isset($_GET['archive']) && $_GET['archive'] == '1';
  $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
  $per_page = 25;
  $offset = ($page - 1) * $per_page;

  // WHERE-Klausel aufbauen
  $where = " WHERE 1=1 ";
  // Archiv-Filter: Hauptliste = nicht archiviert, Archiv-Tab = nur archivierte
  if ($show_archive) {
    $where .= " AND r.is_archived = 1 ";
  } else {
    $where .= " AND (r.is_archived = 0 OR r.is_archived IS NULL) ";
  }
  if ($filter_status != '') {
    $where .= " AND r.reclamation_status = '" . xtc_db_input($filter_status) . "' ";
  }
  if ($filter_search != '') {
    $where .= " AND (r.orders_id LIKE '%" . xtc_db_input($filter_search) . "%' 
                   OR r.customers_name LIKE '%" . xtc_db_input($filter_search) . "%'
                   OR r.customers_email LIKE '%" . xtc_db_input($filter_search) . "%'
                   OR r.reclamation_id LIKE '%" . xtc_db_input($filter_search) . "%') ";
  }

  // Gesamt-Anzahl
  $count_query = xtc_db_query("SELECT COUNT(*) as cnt FROM " . TABLE_ORDERS_RECLAMATION . " r " . $where);
  $count_row = xtc_db_fetch_array($count_query);
  $total_records = (int)$count_row['cnt'];
  $total_pages = max(1, ceil($total_records / $per_page));

  // Reklamationen laden
  $reclamations = array();
  $list_query = xtc_db_query("SELECT r.*, 
                                     (SELECT COUNT(*) FROM " . TABLE_ORDERS_RECLAMATION_PRODUCTS . " rp WHERE rp.reclamation_id = r.reclamation_id) as product_count,
                                     (SELECT COUNT(*) FROM " . TABLE_ORDERS_RECLAMATION_IMAGES . " ri WHERE ri.reclamation_id = r.reclamation_id) as image_count
                                FROM " . TABLE_ORDERS_RECLAMATION . " r 
                                " . $where . "
                             ORDER BY r.reclamation_date DESC
                                LIMIT " . (int)$offset . ", " . (int)$per_page);
  while ($row = xtc_db_fetch_array($list_query)) {
    $reclamations[] = $row;
  }
  
  // Dashboard-URL fuer Links
  $dashboard_url = 'reclamation_dashboard.php';
?>
<!DOCTYPE html>
<html lang="de">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Reklamations-Dashboard | <?php echo STORE_NAME; ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6/css/all.min.css" rel="stylesheet">
  <style>
    body { background: #f4f6f9; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; margin: 0; }
    
    /* Header */
    .mrh-header { background: linear-gradient(135deg, #c0392b 0%, #922b21 100%); color: #fff; padding: 1.5rem 2rem; border-radius: 0 0 12px 12px; }
    .mrh-header h1 { font-size: 1.5rem; margin: 0; font-weight: 600; }
    .mrh-header .subtitle { opacity: 0.85; font-size: 0.9rem; margin-top: 0.25rem; }
    .mrh-header a { color: #fff; opacity: 0.8; text-decoration: none; font-size: 0.85rem; }
    .mrh-header a:hover { opacity: 1; }
    
    /* Stat Cards */
    .stat-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 1rem; margin-bottom: 1.5rem; }
    .stat-card { border-radius: 10px; padding: 1.25rem 1rem; text-align: center; color: #fff; box-shadow: 0 2px 8px rgba(0,0,0,0.1); transition: transform 0.2s; }
    .stat-card:hover { transform: translateY(-2px); }
    .stat-card h3 { font-size: 2rem; margin: 0; font-weight: 700; }
    .stat-card small { opacity: 0.9; font-size: 0.8rem; font-weight: 500; }
    .bg-total { background: linear-gradient(135deg, #6c757d, #495057); }
    .bg-open { background: linear-gradient(135deg, #f39c12, #e67e22); }
    .bg-in-progress { background: linear-gradient(135deg, #3498db, #2980b9); }
    .bg-resolved { background: linear-gradient(135deg, #27ae60, #1e8449); }
    .bg-rejected { background: linear-gradient(135deg, #e74c3c, #c0392b); }
    .bg-seed { background: linear-gradient(135deg, #8e44ad, #6c3483); }
    
    /* Cards */
    .mrh-card { background: #fff; border-radius: 10px; box-shadow: 0 2px 8px rgba(0,0,0,0.06); margin-bottom: 1.5rem; overflow: hidden; }
    .mrh-card .card-header { background: #f8f9fa; border-bottom: 1px solid #e9ecef; padding: 0.75rem 1.25rem; font-weight: 600; color: #c0392b; display: flex; align-items: center; gap: 0.5rem; }
    .mrh-card .card-body { padding: 1.25rem; }
    
    /* Table */
    .recl-table { width: 100%; font-size: 0.85rem; border-collapse: collapse; }
    .recl-table th { background: #f8f9fa; color: #c0392b; font-weight: 600; padding: 0.6rem 0.75rem; border-bottom: 2px solid #c0392b; text-align: left; white-space: nowrap; }
    .recl-table td { padding: 0.5rem 0.75rem; border-bottom: 1px solid #eee; vertical-align: middle; }
    .recl-table tr:hover { background: #fef9f9; cursor: pointer; }
    
    /* Status Badges */
    .status-badge { display: inline-block; padding: 0.25rem 0.6rem; border-radius: 4px; font-size: 0.75rem; font-weight: 600; }
    .status-open { background: #fff3cd; color: #856404; }
    .status-in_progress { background: #d1ecf1; color: #0c5460; }
    .status-resolved { background: #d4edda; color: #155724; }
    .status-rejected { background: #f8d7da; color: #721c24; }
    .status-closed { background: #e2e3e5; color: #383d41; }
    
    /* Filter */
    .filter-bar { display: flex; gap: 0.75rem; align-items: flex-end; flex-wrap: wrap; }
    .filter-bar label { font-size: 0.8rem; font-weight: 600; color: #555; display: block; margin-bottom: 0.25rem; }
    .filter-bar select, .filter-bar input { padding: 0.4rem 0.75rem; border: 1px solid #ddd; border-radius: 6px; font-size: 0.85rem; }
    .filter-bar select:focus, .filter-bar input:focus { outline: none; border-color: #c0392b; box-shadow: 0 0 0 2px rgba(192,57,43,0.1); }
    
    /* Buttons */
    .btn-mrh { background: #c0392b; color: #fff; border: none; padding: 0.5rem 1rem; border-radius: 6px; font-weight: 600; font-size: 0.85rem; cursor: pointer; transition: all 0.2s; }
    .btn-mrh:hover { background: #922b21; color: #fff; }
    .btn-outline-mrh { background: transparent; color: #c0392b; border: 1px solid #c0392b; padding: 0.5rem 1rem; border-radius: 6px; font-weight: 600; font-size: 0.85rem; cursor: pointer; transition: all 0.2s; text-decoration: none; }
    .btn-outline-mrh:hover { background: #c0392b; color: #fff; }
    
    /* Pagination */
    .pagination-bar { display: flex; justify-content: center; gap: 0.25rem; margin-top: 1rem; }
    .pagination-bar a, .pagination-bar span { display: inline-block; padding: 0.4rem 0.75rem; border-radius: 4px; font-size: 0.85rem; text-decoration: none; color: #555; border: 1px solid #ddd; }
    .pagination-bar a:hover { background: #f8f9fa; }
    .pagination-bar .active { background: #c0392b; color: #fff; border-color: #c0392b; }
    
    /* Modal */
    .mrh-overlay { position: fixed; top: 0; left: 0; right: 0; bottom: 0; width: 100vw; height: 100vh; background: rgba(0,0,0,0.92); z-index: 99999; display: none; }
    .mrh-overlay.show { display: flex; align-items: center; justify-content: center; }
    .mrh-dialog { background: #fff; border-radius: 12px; max-width: 900px; width: 95%; max-height: 85vh; display: flex; flex-direction: column; box-shadow: 0 20px 60px rgba(0,0,0,0.4); position: relative; z-index: 100000; }
    .mrh-dialog .mrh-dialog-head { padding: 1rem 1.5rem; border-bottom: 1px solid #eee; display: flex; justify-content: space-between; align-items: center; flex-shrink: 0; }
    .mrh-dialog .mrh-dialog-head h5 { margin: 0; font-size: 1.1rem; color: #c0392b; }
    .mrh-dialog .mrh-dialog-head .close-btn { background: none; border: none; font-size: 1.5rem; cursor: pointer; color: #999; }
    .mrh-dialog .mrh-dialog-head .close-btn:hover { color: #333; }
    .mrh-dialog .mrh-dialog-body { padding: 1.5rem; overflow-y: auto; flex: 1; }
    
    .content-wrap { max-width: 1400px; margin: 0 auto; padding: 1.5rem 2rem; }
    
    /* Unread Badge */
    .unread-badge { display: inline-flex; align-items: center; gap: 0.25rem; background: #c0392b; color: #fff; padding: 0.15rem 0.5rem; border-radius: 12px; font-size: 0.7rem; font-weight: 700; animation: pulse-badge 2s infinite; }
    .unread-badge i { font-size: 0.65rem; }
    @keyframes pulse-badge { 0%, 100% { transform: scale(1); } 50% { transform: scale(1.1); } }
    .ticket-badge { display: inline-flex; align-items: center; gap: 0.25rem; background: #27ae60; color: #fff; padding: 0.15rem 0.5rem; border-radius: 12px; font-size: 0.7rem; font-weight: 600; }
    
    /* Responsive */
    @media (max-width: 768px) {
      .stat-grid { grid-template-columns: repeat(2, 1fr); }
      .filter-bar { flex-direction: column; }
      .content-wrap { padding: 1rem; }
    }
  </style>
</head>
<body>

  <!-- Header -->
  <div class="mrh-header">
    <div style="display:flex; justify-content:space-between; align-items:center; max-width:1400px; margin:0 auto;">
      <div>
        <h1><i class="fa-solid fa-triangle-exclamation"></i> Reklamations-Dashboard</h1>
        <div class="subtitle">Uebersicht aller Kundenreklamationen &mdash; Mr. Hanf</div>
      </div>
      <a href="<?php echo $admin_url; ?>"><i class="fa-solid fa-arrow-left"></i> Zurueck zum Admin</a>
    </div>
  </div>

  <div class="content-wrap">
    
    <!-- Statistik-Karten -->
    <div class="stat-grid" id="stats-row">
      <div class="stat-card bg-total">
        <h3 id="stat-total"><?php echo $total_records; ?></h3>
        <small>Gesamt</small>
      </div>
      <div class="stat-card bg-open">
        <h3 id="stat-open">-</h3>
        <small>Offen</small>
      </div>
      <div class="stat-card bg-in-progress">
        <h3 id="stat-in_progress">-</h3>
        <small>In Bearbeitung</small>
      </div>
      <div class="stat-card bg-resolved">
        <h3 id="stat-resolved">-</h3>
        <small>Gel&ouml;st</small>
      </div>
      <div class="stat-card bg-rejected">
        <h3 id="stat-rejected">-</h3>
        <small>Abgelehnt</small>
      </div>
      <div class="stat-card bg-seed">
        <h3 id="stat-seed">-</h3>
        <small>Samen-Rekl.</small>
      </div>
    </div>

    <!-- Filter -->
    <div class="mrh-card">
      <div class="card-header"><i class="fa-solid fa-filter"></i> Filter &amp; Suche</div>
      <div class="card-body">
        <form method="get" action="<?php echo $dashboard_url; ?>">
          <div class="filter-bar">
            <div>
              <label>Status</label>
              <select name="status">
                <option value="">Alle</option>
                <option value="open" <?php echo ($filter_status == 'open') ? 'selected' : ''; ?>>Offen</option>
                <option value="in_progress" <?php echo ($filter_status == 'in_progress') ? 'selected' : ''; ?>>In Bearbeitung</option>
                <option value="resolved" <?php echo ($filter_status == 'resolved') ? 'selected' : ''; ?>>Gel&ouml;st</option>
                <option value="rejected" <?php echo ($filter_status == 'rejected') ? 'selected' : ''; ?>>Abgelehnt</option>
                <option value="closed" <?php echo ($filter_status == 'closed') ? 'selected' : ''; ?>>Geschlossen</option>
              </select>
            </div>
            <div style="flex:1; min-width:200px;">
              <label>Suche (Bestell-Nr., Name, E-Mail)</label>
              <input type="text" name="search" value="<?php echo htmlspecialchars($filter_search); ?>" placeholder="Suchbegriff..." style="width:100%;">
            </div>
            <div>
              <button type="submit" class="btn-mrh"><i class="fa-solid fa-search"></i> Filtern</button>
            </div>
            <div>
              <a href="<?php echo $dashboard_url; ?>" class="btn-outline-mrh"><i class="fa-solid fa-rotate-left"></i> Zuruecksetzen</a>
            </div>
          </div>
        </form>
      </div>
    </div>

    <!-- Tab-Navigation: Aktiv / Archiv -->
    <div style="display:flex; gap:0; margin-bottom:-1px; position:relative; z-index:1;">
      <a href="<?php echo $dashboard_url; ?>" class="btn-mrh" style="border-radius:8px 8px 0 0; padding:0.6rem 1.5rem; <?php echo !$show_archive ? 'background:#c0392b;' : 'background:#e9ecef; color:#555; border:1px solid #ddd; border-bottom:none;'; ?>">
        <i class="fa-solid fa-list"></i> Aktiv
      </a>
      <a href="<?php echo $dashboard_url; ?>?archive=1" class="btn-mrh" style="border-radius:8px 8px 0 0; padding:0.6rem 1.5rem; <?php echo $show_archive ? 'background:#c0392b;' : 'background:#e9ecef; color:#555; border:1px solid #ddd; border-bottom:none;'; ?>">
        <i class="fa-solid fa-box-archive"></i> Archiv <span id="archive-count-badge" style="background:rgba(255,255,255,0.3); padding:0.1rem 0.4rem; border-radius:10px; font-size:0.7rem; margin-left:0.25rem;">0</span>
      </a>
    </div>

    <!-- Reklamations-Liste -->
    <div class="mrh-card" style="border-radius:0 10px 10px 10px;">
      <div class="card-header"><i class="fa-solid fa-<?php echo $show_archive ? 'box-archive' : 'list'; ?>"></i> <?php echo $show_archive ? 'Archivierte' : 'Aktive'; ?> Reklamationen (<?php echo $total_records; ?>)</div>
      <div class="card-body" style="padding:0; overflow-x:auto;">
        <table class="recl-table">
          <thead>
            <tr>
              <th style="width:5%">#</th>
              <th style="width:8%">Best.-Nr.</th>
              <th>Kunde</th>
              <th>E-Mail</th>
              <th style="width:12%">Datum</th>
              <th style="width:9%">Status</th>
              <th style="width:6%; text-align:center;">Ticket</th>
              <th style="width:5%; text-align:center;">Prod.</th>
              <th style="width:5%; text-align:center;">Bilder</th>
              <th style="width:7%">Aktion</th>
            </tr>
          </thead>
          <tbody>
            <?php if (count($reclamations) > 0): ?>
              <?php foreach ($reclamations as $recl): ?>
                <?php
                  $status_class = 'status-' . $recl['reclamation_status'];
                  $status_labels = array(
                    'open' => 'Offen',
                    'in_progress' => 'In Bearb.',
                    'resolved' => 'Gel&ouml;st',
                    'rejected' => 'Abgelehnt',
                    'closed' => 'Geschlossen'
                  );
                  $status_label = isset($status_labels[$recl['reclamation_status']]) ? $status_labels[$recl['reclamation_status']] : $recl['reclamation_status'];
                ?>
                <tr onclick="showDetail(<?php echo (int)$recl['reclamation_id']; ?>)">
                  <td><strong><?php echo (int)$recl['reclamation_id']; ?></strong></td>
                  <td><a href="<?php echo $admin_url; ?>orders.php?oID=<?php echo (int)$recl['orders_id']; ?>&action=edit" onclick="event.stopPropagation();" style="color:#c0392b; font-weight:600;"><?php echo (int)$recl['orders_id']; ?></a></td>
                  <td><?php echo htmlspecialchars($recl['customers_name']); ?></td>
                  <td><small><?php echo htmlspecialchars($recl['customers_email']); ?></small></td>
                  <td><small><?php echo date('d.m.Y H:i', strtotime($recl['reclamation_date'])); ?></small></td>
                  <td><span class="status-badge <?php echo $status_class; ?>"><?php echo $status_label; ?></span></td>
                  <td style="text-align:center;">
                    <?php if (!empty($recl['zoho_ticket_id'])): ?>
                      <span class="ticket-badge" title="Ticket #<?php echo htmlspecialchars($recl['zoho_ticket_nr']); ?>"><i class="fa-solid fa-ticket"></i> <?php echo htmlspecialchars($recl['zoho_ticket_nr']); ?></span>
                      <?php if ((int)$recl['zoho_unread_count'] > 0): ?>
                        <span class="unread-badge" title="<?php echo (int)$recl['zoho_unread_count']; ?> neue Antwort(en)"><i class="fa-solid fa-envelope"></i> <?php echo (int)$recl['zoho_unread_count']; ?></span>
                      <?php endif; ?>
                    <?php else: ?>
                      <small style="color:#ccc;">&mdash;</small>
                    <?php endif; ?>
                  </td>
                  <td style="text-align:center;"><?php echo (int)$recl['product_count']; ?></td>
                  <td style="text-align:center;"><?php echo (int)$recl['image_count']; ?></td>
                  <td>
                    <button class="btn-mrh" style="padding:0.3rem 0.6rem; font-size:0.75rem;" onclick="event.stopPropagation(); showDetail(<?php echo (int)$recl['reclamation_id']; ?>);">
                      <i class="fa-solid fa-eye"></i> Details
                    </button>
                    <?php if ($show_archive): ?>
                      <button class="btn-outline-mrh" style="padding:0.3rem 0.6rem; font-size:0.7rem; margin-left:0.25rem;" onclick="event.stopPropagation(); unarchiveRecl(<?php echo (int)$recl['reclamation_id']; ?>);" title="Wiederherstellen">
                        <i class="fa-solid fa-rotate-left"></i>
                      </button>
                    <?php elseif (in_array($recl['reclamation_status'], array('closed', 'resolved', 'rejected'))): ?>
                      <button class="btn-outline-mrh" style="padding:0.3rem 0.6rem; font-size:0.7rem; margin-left:0.25rem;" onclick="event.stopPropagation(); archiveRecl(<?php echo (int)$recl['reclamation_id']; ?>);" title="Archivieren">
                        <i class="fa-solid fa-box-archive"></i>
                      </button>
                    <?php endif; ?>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php else: ?>
              <tr><td colspan="11" style="text-align:center; padding:2rem; color:#999;">Keine Reklamationen gefunden.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Pagination -->
    <?php if ($total_pages > 1): ?>
      <div class="pagination-bar">
        <?php for ($p = 1; $p <= $total_pages; $p++): ?>
          <?php if ($p == $page): ?>
            <span class="active"><?php echo $p; ?></span>
          <?php else: ?>
            <a href="<?php echo $dashboard_url . '?page=' . $p . ($filter_status ? '&status=' . $filter_status : '') . ($filter_search ? '&search=' . urlencode($filter_search) : ''); ?>"><?php echo $p; ?></a>
          <?php endif; ?>
        <?php endfor; ?>
      </div>
    <?php endif; ?>

  </div><!-- /content-wrap -->

  <!-- Detail-Modal (custom, kein Bootstrap JS noetig) -->
  <div class="mrh-overlay" id="detailModal">
    <div class="mrh-dialog">
      <div class="mrh-dialog-head">
        <h5><i class="fa-solid fa-triangle-exclamation"></i> Reklamation #<span id="modal-id"></span></h5>
        <button class="close-btn" onclick="closeModal()">&times;</button>
      </div>
      <div class="mrh-dialog-body" id="modal-body">
        <div style="text-align:center; padding:2rem;"><i class="fa-solid fa-spinner fa-spin fa-2x" style="color:#c0392b;"></i></div>
      </div>
    </div>
  </div>

  <script>
    var ADMIN_URL = '<?php echo $admin_url; ?>';
    var DASHBOARD_URL = '<?php echo $dashboard_url; ?>';
    var CATALOG_URL = '<?php echo HTTP_SERVER . DIR_WS_CATALOG; ?>';

    // Statistiken laden
    fetch(DASHBOARD_URL + '?ajax=stats')
      .then(function(r) { return r.json(); })
      .then(function(d) {
        if (d.success) {
          var s = d.data;
          document.getElementById('stat-total').textContent = s.total || 0;
          document.getElementById('stat-open').textContent = s.open || 0;
          document.getElementById('stat-in_progress').textContent = s.in_progress || 0;
          document.getElementById('stat-resolved').textContent = s.resolved || 0;
          document.getElementById('stat-rejected').textContent = s.rejected || 0;
          document.getElementById('stat-seed').textContent = s.seed_products || 0;
          // Archiv-Zaehler im Tab-Badge aktualisieren
          var archBadge = document.getElementById('archive-count-badge');
          if (archBadge) archBadge.textContent = s.archived || 0;
        }
      });

    // === Archiv-Funktionen ===
    function archiveRecl(reclId) {
      if (!confirm('Reklamation #' + reclId + ' archivieren?')) return;
      var fd = new FormData();
      fd.append('reclamation_id', reclId);
      fetch(DASHBOARD_URL + '?ajax=archive', { method: 'POST', body: fd })
        .then(function(r) { return r.json(); })
        .then(function(d) {
          if (d.success) {
            location.reload();
          } else {
            alert('Fehler: ' + (d.message || 'Unbekannt'));
          }
        });
    }

    function unarchiveRecl(reclId) {
      if (!confirm('Reklamation #' + reclId + ' wiederherstellen?')) return;
      var fd = new FormData();
      fd.append('reclamation_id', reclId);
      fetch(DASHBOARD_URL + '?ajax=unarchive', { method: 'POST', body: fd })
        .then(function(r) { return r.json(); })
        .then(function(d) {
          if (d.success) {
            location.reload();
          } else {
            alert('Fehler: ' + (d.message || 'Unbekannt'));
          }
        });
    }

    // Modal beim Laden an body anhängen (verhindert z-index/overflow Probleme durch Parent-Container)
    (function() {
      var modal = document.getElementById('detailModal');
      if (modal && modal.parentNode !== document.body) {
        document.body.appendChild(modal);
      }
    })();

    // Modal
    function showDetail(id) {
      var modal = document.getElementById('detailModal');
      document.getElementById('modal-id').textContent = id;
      document.getElementById('modal-body').innerHTML = '<div style="text-align:center; padding:2rem;"><i class="fa-solid fa-spinner fa-spin fa-2x" style="color:#c0392b;"></i></div>';
      modal.classList.add('show');
      modal.style.display = 'flex';
      document.body.style.overflow = 'hidden';
      
      fetch(DASHBOARD_URL + '?ajax=get_detail&id=' + id)
        .then(function(r) { return r.json(); })
        .then(function(d) {
          if (d.success) {
            renderDetail(d.data);
          } else {
            document.getElementById('modal-body').innerHTML = '<div style="background:#f8d7da;color:#721c24;padding:1rem;border-radius:6px;">' + d.message + '</div>';
          }
        });
    }

    function closeModal() {
      var modal = document.getElementById('detailModal');
      modal.classList.remove('show');
      modal.style.display = 'none';
      document.body.style.overflow = '';
    }

    // Klick ausserhalb Modal schliesst es
    document.getElementById('detailModal').addEventListener('click', function(e) {
      if (e.target === this) closeModal();
    });

    // ESC schliesst Modal
    document.addEventListener('keydown', function(e) {
      if (e.key === 'Escape') closeModal();
    });

    function renderDetail(data) {
      var html = '';
      
      // Kopf-Infos
      html += '<div style="display:grid; grid-template-columns:1fr 1fr 1fr; gap:1rem; margin-bottom:1rem; padding:1rem; background:#f8f9fa; border-radius:8px;">';
      html += '<div><strong style="color:#666; font-size:0.75rem; display:block;">BESTELL-NR.</strong><a href="' + ADMIN_URL + 'orders.php?oID=' + data.orders_id + '&action=edit" target="_blank" style="color:#c0392b; font-weight:600;">#' + data.orders_id + '</a></div>';
      html += '<div><strong style="color:#666; font-size:0.75rem; display:block;">KUNDE</strong>' + escHtml(data.customers_name) + '</div>';
      html += '<div><strong style="color:#666; font-size:0.75rem; display:block;">E-MAIL</strong>' + escHtml(data.customers_email) + '</div>';
      html += '</div>';
      html += '<div style="display:grid; grid-template-columns:1fr 1fr 1fr; gap:1rem; margin-bottom:1rem; padding:1rem; background:#f8f9fa; border-radius:8px;">';
      html += '<div><strong style="color:#666; font-size:0.75rem; display:block;">DATUM</strong>' + escHtml(data.reclamation_date) + '</div>';
      html += '<div><strong style="color:#666; font-size:0.75rem; display:block;">IP</strong>' + escHtml(data.ip_address) + '</div>';
      html += '<div><strong style="color:#666; font-size:0.75rem; display:block;">STATUS</strong>' + getStatusBadge(data.reclamation_status) + '</div>';
      html += '</div>';
      
      if (data.admin_comment) {
        html += '<div style="background:#fff3cd; padding:0.75rem 1rem; border-radius:6px; margin-bottom:1rem; font-size:0.9rem;"><strong>Admin-Kommentar:</strong> ' + escHtml(data.admin_comment) + '</div>';
      }
      
      // Produkte
      html += '<h6 style="color:#c0392b; margin-top:1.5rem;"><i class="fa-solid fa-box"></i> Reklamierte Produkte</h6>';
      html += '<table style="width:100%; font-size:0.85rem; border-collapse:collapse; margin-bottom:1rem;">';
      html += '<thead><tr style="background:#f8f9fa;"><th style="padding:0.5rem; border-bottom:1px solid #ddd;">Stk.</th><th style="padding:0.5rem; border-bottom:1px solid #ddd;">Produkt</th><th style="padding:0.5rem; border-bottom:1px solid #ddd;">Art.-Nr.</th><th style="padding:0.5rem; border-bottom:1px solid #ddd;">Kategorie</th><th style="padding:0.5rem; border-bottom:1px solid #ddd;">Grund</th><th style="padding:0.5rem; border-bottom:1px solid #ddd;">Beschreibung</th></tr></thead><tbody>';
      
      for (var i = 0; i < data.products.length; i++) {
        var p = data.products[i];
        var catBadge = '';
        if (p.product_category == 'seed') catBadge = '<span class="status-badge" style="background:#fff3cd;color:#856404;">Samen</span>';
        else if (p.product_category == 'plant') catBadge = '<span class="status-badge" style="background:#d4edda;color:#155724;">Pflanze</span>';
        else catBadge = '<span class="status-badge" style="background:#e2e3e5;color:#383d41;">Zubeh.</span>';
        
        html += '<tr>';
        html += '<td style="padding:0.5rem; border-bottom:1px solid #eee;">' + p.products_quantity + 'x</td>';
        html += '<td style="padding:0.5rem; border-bottom:1px solid #eee;">' + escHtml(p.products_name) + '</td>';
        html += '<td style="padding:0.5rem; border-bottom:1px solid #eee;">' + escHtml(p.products_model) + '</td>';
        html += '<td style="padding:0.5rem; border-bottom:1px solid #eee;">' + catBadge + '</td>';
        html += '<td style="padding:0.5rem; border-bottom:1px solid #eee;">' + escHtml(p.reclamation_reason) + '</td>';
        html += '<td style="padding:0.5rem; border-bottom:1px solid #eee;"><small>' + escHtml(p.reclamation_description || '') + '</small></td>';
        html += '</tr>';
        
        // Samen-Details
        if (p.product_category == 'seed' && p.seed_germination_method) {
          html += '<tr style="background:#fffbf0;"><td colspan="6" style="padding:0.5rem; font-size:0.8rem;">';
          html += '<strong>Keimmethode:</strong> ' + escHtml(p.seed_germination_method) + ' | ';
          html += '<strong>Temperatur:</strong> ' + escHtml(p.seed_temperature || '') + ' | ';
          html += '<strong>Tage:</strong> ' + escHtml(p.seed_days_waited || '') + ' | ';
          html += '<strong>Nicht gekeimt:</strong> ' + (p.seed_count_failed || 0) + ' | ';
          html += '<strong>Korrekt gelagert:</strong> ' + (p.seed_stored_correctly == 1 ? 'Ja' : 'Nein');
          if (p.seed_expected_strain) {
            html += ' | <strong>Erwartet:</strong> ' + escHtml(p.seed_expected_strain);
            html += ' | <strong>Erhalten:</strong> ' + escHtml(p.seed_received_strain || '');
          }
          html += '</td></tr>';
        }
      }
      html += '</tbody></table>';
      
      // Bilder
      if (data.images && data.images.length > 0) {
        html += '<h6 style="color:#c0392b; margin-top:1.5rem;"><i class="fa-solid fa-images"></i> Hochgeladene Bilder (' + data.images.length + ')</h6>';
        html += '<div style="display:flex; flex-wrap:wrap; gap:0.5rem;">';
        for (var j = 0; j < data.images.length; j++) {
          var img = data.images[j];
          var imgUrl = CATALOG_URL + img.image_path;
          html += '<a href="' + imgUrl + '" target="_blank"><img src="' + imgUrl + '" style="max-height:120px;border-radius:6px;border:1px solid #dee2e6;" alt="' + escHtml(img.image_original_name) + '"></a>';
        }
        html += '</div>';
      }
      
      // Status-Aenderung
      if (data.reclamation_status == 'open' || data.reclamation_status == 'in_progress') {
        html += '<hr style="margin:1.5rem 0;">';
        html += '<h6 style="color:#c0392b;"><i class="fa-solid fa-pen"></i> Status aendern</h6>';
        html += '<div style="display:grid; grid-template-columns:1fr 2fr 1fr; gap:0.75rem; align-items:start;">';
        html += '<select id="modal-status" style="padding:0.5rem; border:1px solid #ddd; border-radius:6px;">';
        html += '<option value="open"' + (data.reclamation_status == 'open' ? ' selected' : '') + '>Offen</option>';
        html += '<option value="in_progress"' + (data.reclamation_status == 'in_progress' ? ' selected' : '') + '>In Bearbeitung</option>';
        html += '<option value="resolved">Gel&ouml;st</option>';
        html += '<option value="rejected">Abgelehnt</option>';
        html += '<option value="closed">Geschlossen</option>';
        html += '</select>';
        html += '<textarea id="modal-comment" style="padding:0.5rem; border:1px solid #ddd; border-radius:6px; resize:vertical;" rows="2" placeholder="Admin-Kommentar...">' + escHtml(data.admin_comment || '') + '</textarea>';
        html += '<button class="btn-mrh" onclick="updateStatus(' + data.reclamation_id + ')"><i class="fa-solid fa-floppy-disk"></i> Speichern</button>';
        html += '</div>';
      }
      
      // === Zoho Desk Ticket-Bereich ===
      html += '<hr style="margin:1.5rem 0;">';
      
      if (data.zoho_ticket_id) {
        // Ticket existiert bereits → Konversation anzeigen + Antwort senden + Schliessen
        html += '<h6 style="color:#c0392b;"><i class="fa-solid fa-headset"></i> Zoho Ticket #' + escHtml(data.zoho_ticket_nr) + '</h6>';
        html += '<div style="background:#f0f7ff; padding:1rem; border-radius:8px; border:1px solid #d0e3f7;">';
        
        // Konversation-Container (wird per AJAX geladen)
        html += '<div id="ticket-conversations" style="margin-bottom:1rem; max-height:300px; overflow-y:auto; border:1px solid #e0e0e0; border-radius:6px; padding:0.75rem; background:#fff;"><div style="text-align:center; padding:1rem;"><i class="fa-solid fa-spinner fa-spin"></i> Lade Konversation...</div></div>';
        
        // Antwort-Bereich
        html += '<div style="margin-bottom:0.75rem;">';
        html += '<label style="font-size:0.8rem; font-weight:600; color:#555; display:block; margin-bottom:0.25rem;">Antwort verfassen:</label>';
        html += '<div style="display:flex; gap:0.5rem; margin-bottom:0.5rem;">';
        html += '<input type="text" id="ai-prompt" style="flex:1; padding:0.5rem; border:1px solid #ddd; border-radius:6px; font-size:0.85rem;" placeholder="z.B. Nichtkeimung innerhalb Kulanz, falsche Menge, Ersatzlieferung anbieten...">';
        html += '<button class="btn-mrh" style="white-space:nowrap;" onclick="generateAiText(' + data.reclamation_id + ')"><i class="fa-solid fa-wand-magic-sparkles"></i> KI-Text</button>';
        html += '</div>';
        html += '<textarea id="ticket-message" style="width:100%; padding:0.5rem; border:1px solid #ddd; border-radius:6px; font-size:0.85rem; resize:vertical; min-height:100px;" placeholder="Antwort hier eingeben oder per KI generieren..."></textarea>';
        html += '</div>';
        
        // Buttons: Antwort senden + Ticket schliessen
        html += '<div style="display:flex; gap:0.5rem; align-items:center; flex-wrap:wrap;">';
        html += '<button class="btn-mrh" style="background:#27ae60;" onclick="replyToTicket(' + data.reclamation_id + ', \'' + escHtml(data.zoho_ticket_id) + '\', \'' + escHtml(data.customers_email) + '\')"><i class="fa-solid fa-reply"></i> Antwort senden</button>';
        html += '<button class="btn-mrh" style="background:#e74c3c;" onclick="closeTicket(' + data.reclamation_id + ')"><i class="fa-solid fa-xmark"></i> Ticket schliessen</button>';
        html += '</div>';
        
        html += '<div id="ticket-status" style="margin-top:0.75rem; display:none;"></div>';
        html += '</div>';
        
      } else {
        // Kein Ticket → Hinweis + manuelles Erstellen
        html += '<h6 style="color:#c0392b;"><i class="fa-solid fa-headset"></i> Zoho Desk Ticket</h6>';
        html += '<div style="background:#f0f7ff; padding:1rem; border-radius:8px; border:1px solid #d0e3f7;">';
        
        if (data.reclamation_status == 'open') {
          html += '<div style="background:#fff3cd; padding:0.75rem; border-radius:6px; margin-bottom:1rem; font-size:0.85rem;"><i class="fa-solid fa-info-circle"></i> <strong>Hinweis:</strong> Wenn du den Status auf &quot;In Bearbeitung&quot; setzt, erhaelt der Kunde automatisch eine Bestaetigungs-Mail. Ein Zoho-Ticket kannst du unten manuell erstellen.</div>';
        }
        
        // Manuelles Erstellen (falls gewuenscht)
        html += '<div style="margin-bottom:0.75rem;">';
        html += '<label style="font-size:0.8rem; font-weight:600; color:#555; display:block; margin-bottom:0.25rem;">Ticket erstellen (KI-Anweisung optional):</label>';
        html += '<div style="display:flex; gap:0.5rem; margin-bottom:0.5rem;">';
        html += '<input type="text" id="ai-prompt" style="flex:1; padding:0.5rem; border:1px solid #ddd; border-radius:6px; font-size:0.85rem;" placeholder="z.B. Nichtkeimung innerhalb Kulanz, falsche Menge...">';
        html += '<button class="btn-mrh" style="white-space:nowrap;" onclick="generateAiText(' + data.reclamation_id + ')"><i class="fa-solid fa-wand-magic-sparkles"></i> KI-Text</button>';
        html += '</div>';
        html += '</div>';
        
        html += '<div style="margin-bottom:0.75rem;">';
        html += '<label style="font-size:0.8rem; font-weight:600; color:#555; display:block; margin-bottom:0.25rem;">Betreff:</label>';
        html += '<input type="text" id="ticket-subject" style="width:100%; padding:0.5rem; border:1px solid #ddd; border-radius:6px; font-size:0.85rem;" value="Reklamation Bestellung #' + data.orders_id + ' - Mr. Hanf">';
        html += '</div>';
        
        html += '<div style="margin-bottom:0.75rem;">';
        html += '<label style="font-size:0.8rem; font-weight:600; color:#555; display:block; margin-bottom:0.25rem;">Nachricht:</label>';
        html += '<textarea id="ticket-message" style="width:100%; padding:0.5rem; border:1px solid #ddd; border-radius:6px; font-size:0.85rem; resize:vertical; min-height:100px;" placeholder="Text eingeben oder per KI generieren..."></textarea>';
        html += '</div>';
        
        html += '<div style="display:flex; gap:0.5rem; align-items:center;">';
        html += '<div style="flex:1;"><label style="font-size:0.8rem; font-weight:600; color:#555; display:block; margin-bottom:0.25rem;">Empf&auml;nger:</label>';
        html += '<input type="email" id="ticket-email" style="width:100%; padding:0.5rem; border:1px solid #ddd; border-radius:6px; font-size:0.85rem;" value="' + escHtml(data.customers_email) + '"></div>';
        html += '<div style="padding-top:1.2rem;"><button class="btn-mrh" style="background:#27ae60;" onclick="createZohoTicket(' + data.reclamation_id + ')"><i class="fa-solid fa-paper-plane"></i> Ticket erstellen</button></div>';
        html += '</div>';
        
        html += '<div id="ticket-status" style="margin-top:0.75rem; display:none;"></div>';
        html += '</div>';
      }
      
      document.getElementById('modal-body').innerHTML = html;
      
      // Wenn Ticket existiert, Konversation laden
      if (data.zoho_ticket_id) {
        loadConversations(data.zoho_ticket_id, data.reclamation_id);
      }
    }

    function updateStatus(id) {
      var status = document.getElementById('modal-status').value;
      var comment = document.getElementById('modal-comment').value;
      
      var fd = new FormData();
      fd.append('reclamation_id', id);
      fd.append('new_status', status);
      fd.append('admin_comment', comment);
      
      fetch(DASHBOARD_URL + '?ajax=update_status', {
        method: 'POST',
        body: fd
      })
      .then(function(r) { return r.json(); })
      .then(function(d) {
        if (d.success) {
          location.reload();
        } else {
          window.alert('Fehler: ' + d.message);
        }
      });
    }

    function getStatusBadge(status) {
      var map = {
        'open': '<span class="status-badge status-open">Offen</span>',
        'in_progress': '<span class="status-badge status-in_progress">In Bearbeitung</span>',
        'resolved': '<span class="status-badge status-resolved">Gel&ouml;st</span>',
        'rejected': '<span class="status-badge status-rejected">Abgelehnt</span>',
        'closed': '<span class="status-badge status-closed">Geschlossen</span>'
      };
      return map[status] || status;
    }

    // === KI-Textgenerierung ===
    function generateAiText(reclId) {
      var btn = event.target.closest('button');
      var origHtml = btn.innerHTML;
      btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Generiere...';
      btn.disabled = true;
      
      var customPrompt = document.getElementById('ai-prompt').value;
      
      var fd = new FormData();
      fd.append('reclamation_id', reclId);
      fd.append('custom_prompt', customPrompt);
      
      fetch(DASHBOARD_URL + '?ajax=ai_generate', {
        method: 'POST',
        body: fd
      })
      .then(function(r) { return r.json(); })
      .then(function(d) {
        btn.innerHTML = origHtml;
        btn.disabled = false;
        if (d.success) {
          document.getElementById('ticket-message').value = d.text;
        } else {
          showTicketStatus('error', 'KI-Fehler: ' + d.message);
        }
      })
      .catch(function(err) {
        btn.innerHTML = origHtml;
        btn.disabled = false;
        showTicketStatus('error', 'Netzwerkfehler: ' + err.message);
      });
    }
    
    // === Zoho Desk Ticket erstellen + Mail an Kunden senden ===
    function createZohoTicket(reclId) {
      var subject = document.getElementById('ticket-subject').value.trim();
      var message = document.getElementById('ticket-message').value.trim();
      var email = document.getElementById('ticket-email').value.trim();
      
      if (!subject || !message || !email) {
        showTicketStatus('error', 'Bitte Betreff, Nachricht und E-Mail ausfuellen.');
        return;
      }
      
      showTicketStatus('info', '<i class="fa-solid fa-spinner fa-spin"></i> Erstelle Ticket und sende Mail...');
      
      var savedToken = '';
      
      // Zuerst Zoho-Token holen
      fetch(DASHBOARD_URL + '?ajax=zoho_token')
      .then(function(r) { return r.json(); })
      .then(function(tokenData) {
        if (!tokenData.success) {
          showTicketStatus('error', 'Token-Fehler: ' + (tokenData.message || 'Unbekannt'));
          return Promise.reject('token_error');
        }
        savedToken = tokenData.token;
        
        // Ticket bei Zoho erstellen
        var fd = new FormData();
        fd.append('subject', subject);
        fd.append('description', message);
        fd.append('email', email);
        fd.append('contact_name', document.getElementById('ticket-email').getAttribute('data-name') || '');
        
        return fetch(CATALOG_URL + 'zoho_desk_api.php?action=create_ticket&token=' + savedToken, {
          method: 'POST',
          body: fd
        });
      })
      .then(function(r) { if (r) return r.json(); })
      .then(function(d) {
        if (!d) return;
        if (d.success) {
          // Ticket-ID in unserer DB speichern
          var saveFd = new FormData();
          saveFd.append('reclamation_id', reclId);
          saveFd.append('ticket_id', d.ticket_id);
          saveFd.append('ticket_nr', d.ticket_nr || '');
          fetch(DASHBOARD_URL + '?ajax=save_ticket_id', { method: 'POST', body: saveFd });
          
          // Jetzt Reply senden damit Kunde die Mail bekommt
          var replyFd = new FormData();
          replyFd.append('ticket_id', d.ticket_id);
          replyFd.append('to', email);
          replyFd.append('content', '<div>' + message.replace(/\n/g, '<br>') + '</div>');
          
          return fetch(CATALOG_URL + 'zoho_desk_api.php?action=reply&token=' + savedToken, {
            method: 'POST',
            body: replyFd
          }).then(function(r2) { return r2.json(); }).then(function(replyData) {
            var linkHtml = '';
            if (d.web_url) {
              linkHtml = ' <a href="' + d.web_url + '" target="_blank" style="color:#155724; text-decoration:underline;">In Zoho oeffnen</a>';
            }
            if (replyData && replyData.success) {
              showTicketStatus('success', '<i class="fa-solid fa-check-circle"></i> Ticket #' + (d.ticket_nr || d.ticket_id) + ' erstellt + Mail an Kunden gesendet.' + linkHtml);
            } else {
              showTicketStatus('success', '<i class="fa-solid fa-check-circle"></i> Ticket erstellt, aber Mail-Versand fehlgeschlagen: ' + (replyData ? replyData.error : 'Unbekannt') + linkHtml);
            }
          });
        } else {
          showTicketStatus('error', 'Zoho-Fehler: ' + d.error);
        }
      })
      .catch(function(err) {
        if (err !== 'token_error') {
          showTicketStatus('error', 'Netzwerkfehler: ' + err.message);
        }
      });
    }
    
    function showTicketStatus(type, msg) {
      var el = document.getElementById('ticket-status');
      if (!el) return;
      el.style.display = 'block';
      var colors = {
        'success': 'background:#d4edda;color:#155724;border:1px solid #c3e6cb;',
        'error': 'background:#f8d7da;color:#721c24;border:1px solid #f5c6cb;',
        'info': 'background:#d1ecf1;color:#0c5460;border:1px solid #bee5eb;'
      };
      el.innerHTML = '<div style="' + (colors[type] || colors.info) + 'padding:0.75rem;border-radius:6px;font-size:0.85rem;">' + msg + '</div>';
    }

    // === Konversation laden ===
    function loadConversations(ticketId, reclId) {
      fetch(DASHBOARD_URL + '?ajax=zoho_token')
      .then(function(r) { return r.json(); })
      .then(function(tokenData) {
        if (!tokenData.success) return;
        return fetch(CATALOG_URL + 'zoho_desk_api.php?action=conversations&ticket_id=' + ticketId + '&token=' + tokenData.token);
      })
      .then(function(r) { if (r) return r.json(); })
      .then(function(d) {
        if (!d || !d.conversations) {
          document.getElementById('ticket-conversations').innerHTML = '<div style="color:#999; text-align:center; padding:0.5rem;">Keine Nachrichten.</div>';
          return;
        }
        var convHtml = '';
        var lastThreadId = '';
        for (var i = d.conversations.length - 1; i >= 0; i--) {
          var c = d.conversations[i];
          if (i === 0) lastThreadId = c.id;
          var isIn = (c.direction === 'in');
          var bgColor = isIn ? '#fff3cd' : '#d4edda';
          var icon = isIn ? 'fa-user' : 'fa-headset';
          var label = isIn ? 'Kunde' : 'Support';
          var authorName = (c.author && c.author.name) ? c.author.name : label;
          var dateStr = c.createdTime ? new Date(c.createdTime).toLocaleString('de-DE') : '';
          
          convHtml += '<div style="background:' + bgColor + '; padding:0.6rem 0.75rem; border-radius:6px; margin-bottom:0.5rem; font-size:0.82rem;">';
          convHtml += '<div style="display:flex; justify-content:space-between; margin-bottom:0.3rem;"><strong><i class="fa-solid ' + icon + '"></i> ' + escHtml(authorName) + '</strong><small style="color:#888;">' + dateStr + '</small></div>';
          convHtml += '<div>' + (c.content || escHtml(c.summary || '')) + '</div>';
          convHtml += '</div>';
        }
        document.getElementById('ticket-conversations').innerHTML = convHtml || '<div style="color:#999; text-align:center;">Keine Nachrichten.</div>';
        // Scroll nach unten
        var convEl = document.getElementById('ticket-conversations');
        convEl.scrollTop = convEl.scrollHeight;
        
        // Mark as read
        if (lastThreadId) {
          var fd = new FormData();
          fd.append('reclamation_id', reclId);
          fd.append('last_thread_id', lastThreadId);
          fetch(DASHBOARD_URL + '?ajax=mark_read', { method: 'POST', body: fd });
        }
      });
    }
    
    // === Antwort auf Ticket senden ===
    function replyToTicket(reclId, ticketId, email) {
      var message = document.getElementById('ticket-message').value.trim();
      if (!message) {
        showTicketStatus('error', 'Bitte eine Antwort eingeben.');
        return;
      }
      
      showTicketStatus('info', '<i class="fa-solid fa-spinner fa-spin"></i> Sende Antwort...');
      
      fetch(DASHBOARD_URL + '?ajax=zoho_token')
      .then(function(r) { return r.json(); })
      .then(function(tokenData) {
        if (!tokenData.success) {
          showTicketStatus('error', 'Token-Fehler');
          return;
        }
        var fd = new FormData();
        fd.append('token', tokenData.token);
        fd.append('ticket_id', ticketId);
        fd.append('to', email);
        fd.append('content', message);
        
        return fetch(CATALOG_URL + 'zoho_desk_api.php?action=reply&token=' + tokenData.token, {
          method: 'POST',
          body: fd
        });
      })
      .then(function(r) { if (r) return r.json(); })
      .then(function(d) {
        if (!d) return;
        if (d.success) {
          showTicketStatus('success', '<i class="fa-solid fa-check-circle"></i> Antwort gesendet!');
          document.getElementById('ticket-message').value = '';
          // Konversation neu laden
          setTimeout(function() { loadConversations(ticketId, reclId); }, 1500);
        } else {
          showTicketStatus('error', 'Fehler: ' + (d.error || 'Unbekannt'));
        }
      })
      .catch(function(err) {
        showTicketStatus('error', 'Netzwerkfehler: ' + err.message);
      });
    }
    
    // === Ticket schliessen ===
    function closeTicket(reclId) {
      if (!window.confirm('Ticket wirklich schliessen? Der Kunde erhaelt keine weiteren Nachrichten.')) return;
      
      showTicketStatus('info', '<i class="fa-solid fa-spinner fa-spin"></i> Schliesse Ticket...');
      
      var fd = new FormData();
      fd.append('reclamation_id', reclId);
      
      fetch(DASHBOARD_URL + '?ajax=close_ticket', {
        method: 'POST',
        body: fd
      })
      .then(function(r) { return r.json(); })
      .then(function(d) {
        if (d.success) {
          showTicketStatus('success', '<i class="fa-solid fa-check-circle"></i> Ticket geschlossen!');
          setTimeout(function() { location.reload(); }, 1500);
        } else {
          showTicketStatus('error', 'Fehler: ' + d.message);
        }
      });
    }
    
    // === Unread-Check beim Seitenaufruf ===
    fetch(DASHBOARD_URL + '?ajax=check_unread')
      .then(function(r) { return r.json(); })
      .then(function(d) {
        if (d.success && d.unread) {
          // Badges in der Tabelle aktualisieren (fuer dynamische Updates)
          // Die PHP-Seite zeigt bereits die gespeicherten Werte an
        }
      })
      .catch(function() {});

    function escHtml(str) {
      if (!str) return '';
      var div = document.createElement('div');
      div.appendChild(document.createTextNode(str));
      return div.innerHTML;
    }
  </script>

</body>
</html>
