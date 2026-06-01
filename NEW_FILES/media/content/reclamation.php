<?php
/* -----------------------------------------------------------------------------------------
   $Id$

   modified eCommerce Shopsoftware
   http://www.modified-shop.org

   Copyright (c) 2009 - 2013 [www.modified-shop.org]
   -----------------------------------------------------------------------------------------
   Released under the GNU General Public License
   
   MRH 2026: Reklamationsmodul fuer Mr. Hanf
   - Kategorie-Erkennung (Samen / Pflanzen / Zubehoer)
   - Pflanzen (581964) von Reklamation ausgeschlossen (ausgegraut)
   - Samen (581210) nur auf Kulanzbasis, Pflicht-Bildupload, Anti-Betrugs-Fragen
   - Zubehoer: 2-Jahres-Gewaehrleistung (ABGB)
   - Bildupload: max 5 Bilder, JPG/PNG/HEIC/WebP, max 10MB
   - Rate-Limiting: max 3 Reklamationen pro Bestellung
   - IP-Logging, Bildrechte-Checkbox
   ---------------------------------------------------------------------------------------*/
  
  if (defined('MODULE_RECLAMATION_STATUS')
      && MODULE_RECLAMATION_STATUS == 'true'
      )
  {
    defined('DISPLAY_PRIVACY_CHECK') or define('DISPLAY_PRIVACY_CHECK', 'true');
  
    // MRH: Kategorie-IDs fuer Reklamations-Regeln
    defined('RECLAMATION_CATEGORY_SEEDS')  or define('RECLAMATION_CATEGORY_SEEDS',  581210);
    defined('RECLAMATION_CATEGORY_PLANTS') or define('RECLAMATION_CATEGORY_PLANTS', 581964);

    // MRH: Upload-Konfiguration
    defined('RECLAMATION_MAX_IMAGES')    or define('RECLAMATION_MAX_IMAGES', 5);
    defined('RECLAMATION_MAX_FILE_SIZE') or define('RECLAMATION_MAX_FILE_SIZE', 10485760); // 10 MB
    defined('RECLAMATION_IMAGE_DIR')     or define('RECLAMATION_IMAGE_DIR', DIR_FS_CATALOG . 'images/reclamation/');

    // include needed functions
    require_once (DIR_FS_INC.'xtc_validate_email.inc.php');
    require_once (DIR_FS_INC.'parse_multi_language_value.inc.php');
    require_once (DIR_FS_INC.'secure_form.inc.php');
    require_once (DIR_FS_INC.'xtc_date_long.inc.php');
  
    // include needed classes
    require_once(DIR_WS_CLASSES.'modified_captcha.php');
    
    $mod_captcha = $_mod_captcha_class::getInstance();
      
    // captcha
    $use_captcha = array();
    if (defined('MODULE_RECLAMATION_CAPTCHA') 
        && MODULE_RECLAMATION_CAPTCHA == 'true'
        )
    {
      $use_captcha = array('reclamation');
    }
    defined('MODULE_CAPTCHA_CODE_LENGTH') or define('MODULE_CAPTCHA_CODE_LENGTH', 6);
    defined('MODULE_CAPTCHA_LOGGED_IN') or define('MODULE_CAPTCHA_LOGGED_IN', 'True');
    
    $action = isset($_GET['action']) && $_GET['action'] != '' ? $_GET['action'] : '';
    $privacy = isset($_POST['privacy']) && $_POST['privacy'] == 'privacy' ? true : false;
    
    if (!isset($smarty) || !is_object($smarty)) {
      $smarty = new Smarty();
    }
    
    if (!isset($main) || !is_object($main)) {
      $main = new main();
    }
    
    $error = false;

    // =========================================================================
    // MRH: Hilfsfunktion – Prueft ob ein Produkt zu einer bestimmten
    //       Elternkategorie (inkl. Unterkategorien) gehoert
    // =========================================================================
    if (!function_exists('mrh_reclamation_is_category')) {
      function mrh_reclamation_is_category($products_id, $parent_category_id) {
        $cat_query = xtc_db_query("SELECT ptc.categories_id
                                     FROM products_to_categories ptc
                                    WHERE ptc.products_id = '" . (int)$products_id . "'");
        while ($cat = xtc_db_fetch_array($cat_query)) {
          $check_id = (int)$cat['categories_id'];
          $max_depth = 20;
          while ($check_id > 0 && $max_depth-- > 0) {
            if ($check_id == (int)$parent_category_id) {
              return true;
            }
            $parent_query = xtc_db_query("SELECT parent_id 
                                            FROM categories 
                                           WHERE categories_id = '" . (int)$check_id . "'");
            $parent = xtc_db_fetch_array($parent_query);
            $check_id = ($parent) ? (int)$parent['parent_id'] : 0;
          }
        }
        return false;
      }
    }

    // =========================================================================
    // MRH: Hilfsfunktion – Bestimmt den Reklamations-Typ eines Produkts
    //       Rueckgabe: 'plant' | 'seed' | 'default'
    // =========================================================================
    if (!function_exists('mrh_reclamation_product_type')) {
      function mrh_reclamation_product_type($products_id) {
        if (mrh_reclamation_is_category($products_id, RECLAMATION_CATEGORY_PLANTS)) {
          return 'plant';
        }
        if (mrh_reclamation_is_category($products_id, RECLAMATION_CATEGORY_SEEDS)) {
          return 'seed';
        }
        return 'default';
      }
    }

    // =========================================================================
    // MRH: Hilfsfunktion – Bildupload verarbeiten
    // =========================================================================
    if (!function_exists('mrh_reclamation_handle_uploads')) {
      function mrh_reclamation_handle_uploads($orders_id, $reclamation_id) {
        $uploaded_images = array();
        $allowed_types = array('image/jpeg', 'image/png', 'image/heic', 'image/heif', 'image/webp');
        $allowed_extensions = array('jpg', 'jpeg', 'png', 'heic', 'heif', 'webp');
        
        if (!isset($_FILES['reclamation_images']) || empty($_FILES['reclamation_images']['name'][0])) {
          return $uploaded_images;
        }
        
        $upload_dir = RECLAMATION_IMAGE_DIR . (int)$orders_id . '/';
        if (!is_dir($upload_dir)) {
          @mkdir($upload_dir, 0755, true);
        }
        
        $file_count = min(count($_FILES['reclamation_images']['name']), RECLAMATION_MAX_IMAGES);
        
        for ($i = 0; $i < $file_count; $i++) {
          if ($_FILES['reclamation_images']['error'][$i] !== UPLOAD_ERR_OK) {
            continue;
          }
          
          $tmp_name = $_FILES['reclamation_images']['tmp_name'][$i];
          $original_name = $_FILES['reclamation_images']['name'][$i];
          $file_size = $_FILES['reclamation_images']['size'][$i];
          
          // Groessencheck
          if ($file_size > RECLAMATION_MAX_FILE_SIZE) {
            continue;
          }
          
          // MIME-Type pruefen
          $finfo = new finfo(FILEINFO_MIME_TYPE);
          $mime_type = $finfo->file($tmp_name);
          if (!in_array($mime_type, $allowed_types)) {
            continue;
          }
          
          // Extension pruefen
          $ext = strtolower(pathinfo($original_name, PATHINFO_EXTENSION));
          if (!in_array($ext, $allowed_extensions)) {
            continue;
          }
          
          // Dateiname generieren
          $timestamp = time();
          $new_filename = 'rekl_' . (int)$orders_id . '_' . $timestamp . '_' . ($i + 1) . '.jpg';
          $dest_path = $upload_dir . $new_filename;
          
          // HEIC/HEIF zu JPG konvertieren (falls Imagick verfuegbar)
          if (in_array($ext, array('heic', 'heif'))) {
            if (class_exists('Imagick')) {
              try {
                $imagick = new Imagick($tmp_name);
                $imagick->setImageFormat('jpeg');
                $imagick->setImageCompressionQuality(85);
                $imagick->writeImage($dest_path);
                $imagick->destroy();
                $mime_type = 'image/jpeg';
              } catch (Exception $e) {
                // Fallback: Datei direkt kopieren
                if (!move_uploaded_file($tmp_name, $dest_path)) {
                  continue;
                }
              }
            } else {
              // Ohne Imagick: Datei direkt speichern
              $new_filename = 'rekl_' . (int)$orders_id . '_' . $timestamp . '_' . ($i + 1) . '.' . $ext;
              $dest_path = $upload_dir . $new_filename;
              if (!move_uploaded_file($tmp_name, $dest_path)) {
                continue;
              }
            }
          } elseif ($ext == 'webp') {
            // WebP zu JPG konvertieren fuer E-Mail-Kompatibilitaet
            if (function_exists('imagecreatefromwebp')) {
              $img = @imagecreatefromwebp($tmp_name);
              if ($img) {
                imagejpeg($img, $dest_path, 85);
                imagedestroy($img);
                $mime_type = 'image/jpeg';
              } else {
                $new_filename = 'rekl_' . (int)$orders_id . '_' . $timestamp . '_' . ($i + 1) . '.webp';
                $dest_path = $upload_dir . $new_filename;
                if (!move_uploaded_file($tmp_name, $dest_path)) {
                  continue;
                }
              }
            } else {
              $new_filename = 'rekl_' . (int)$orders_id . '_' . $timestamp . '_' . ($i + 1) . '.webp';
              $dest_path = $upload_dir . $new_filename;
              if (!move_uploaded_file($tmp_name, $dest_path)) {
                continue;
              }
            }
          } elseif ($ext == 'png') {
            // PNG zu JPG konvertieren fuer E-Mail-Kompatibilitaet
            if (function_exists('imagecreatefrompng')) {
              $img = @imagecreatefrompng($tmp_name);
              if ($img) {
                $bg = imagecreatetruecolor(imagesx($img), imagesy($img));
                imagefill($bg, 0, 0, imagecolorallocate($bg, 255, 255, 255));
                imagecopy($bg, $img, 0, 0, 0, 0, imagesx($img), imagesy($img));
                imagejpeg($bg, $dest_path, 85);
                imagedestroy($img);
                imagedestroy($bg);
                $mime_type = 'image/jpeg';
              } else {
                if (!move_uploaded_file($tmp_name, $dest_path)) {
                  continue;
                }
              }
            } else {
              if (!move_uploaded_file($tmp_name, $dest_path)) {
                continue;
              }
            }
          } else {
            // JPG/JPEG direkt verschieben
            if (!move_uploaded_file($tmp_name, $dest_path)) {
              continue;
            }
          }
          
          // In DB speichern
          $image_data = array(
            'reclamation_id' => (int)$reclamation_id,
            'reclamation_product_id' => 0,
            'image_path' => 'images/reclamation/' . (int)$orders_id . '/' . $new_filename,
            'image_original_name' => xtc_db_input($original_name),
            'image_size' => (int)$file_size,
            'image_type' => xtc_db_input($mime_type),
            'upload_date' => 'now()',
          );
          xtc_db_perform(TABLE_ORDERS_RECLAMATION_IMAGES, $image_data);
          
          $uploaded_images[] = array(
            'path' => $dest_path,
            'filename' => $new_filename,
            'original_name' => $original_name,
            'mime_type' => $mime_type,
          );
        }
        
        return $uploaded_images;
      }
    }
    
    switch ($action) {
      case 'auth':
        $valid_params = array(
          'orders_id',
          'email_address',
        );
    
        // prepare variables
        foreach ($_POST as $key => $value) {
          if ((!isset(${$key}) || !is_object(${$key})) && in_array($key , $valid_params)) {
            ${$key} = xtc_db_prepare_input($value);
          }
        }

        if (!isset($email_address) || !xtc_validate_email(trim($email_address))) {
          $messageStack->add('reclamation', ENTRY_EMAIL_ADDRESS_ERROR);
          $error = true;
        }
  
        if (!isset($orders_id) || strlen($orders_id) < 1) {
          $messageStack->add('reclamation', ENTRY_RECLAMATION_ORDERS_ID_ERROR);
          $error = true;
        }
        
        if (in_array('reclamation', $use_captcha) && (!isset($_SESSION['customer_id']) || MODULE_CAPTCHA_LOGGED_IN == 'True')) {
          if ($mod_captcha->validate((isset($_POST['vvcode'])) ? $_POST['vvcode'] : '') !== true) {
            $messageStack->add('reclamation', strip_tags(ERROR_VVCODE, '<b><strong>'));
            $error = true;
          }
        }

        if (DISPLAY_PRIVACY_CHECK == 'true' && empty($privacy)) {
          $messageStack->add('reclamation', ENTRY_PRIVACY_ERROR);
          $error = true;
        }
        
        if (check_secure_form($_POST) === false) {
          $messageStack->add('reclamation', ENTRY_TOKEN_ERROR);
          $error = true;
        }
        
        if ($error === false) {
          // E-Mail + Bestellnummer validieren (kein Name noetig)
          $orders_query = xtc_db_query("SELECT *
                                          FROM ".TABLE_ORDERS."
                                         WHERE orders_id = '".(int)$orders_id."'
                                           AND customers_email_address = '".xtc_db_input($email_address)."'");
          if (xtc_db_num_rows($orders_query) < 1) {          
            $messageStack->add('reclamation', TEXT_RECLAMATION_ORDER_NOT_FOUND);
          } else {
            // Rate-Limiting: Max 3 Reklamationen pro Bestellung
            $rate_check = xtc_db_query("SELECT COUNT(*) as cnt FROM " . TABLE_ORDERS_RECLAMATION . " WHERE orders_id = '" . (int)$orders_id . "'");
            $rate_row = xtc_db_fetch_array($rate_check);
            if ((int)$rate_row['cnt'] >= 3) {
              $messageStack->add('reclamation', TEXT_RECLAMATION_RATE_LIMIT);
            } else {
              // Versandstatus pruefen: Bestellung muss jemals versendet worden sein
              $shipped_query = xtc_db_query("SELECT COUNT(*) as cnt FROM orders_status_history WHERE orders_id = '".(int)$orders_id."' AND orders_status_id = 3");
              $shipped_row = xtc_db_fetch_array($shipped_query);
              if ((int)$shipped_row['cnt'] < 1) {
                $messageStack->add('reclamation', TEXT_RECLAMATION_NOT_SHIPPED);
              } else {
                $_SESSION['reclamation'][(int)$orders_id] = array(
                  'valid' => true,
                  'success' => false,
                  'orders_id' => $orders_id,
                  'email_address' => $email_address,
                  'orders' => xtc_db_fetch_array($orders_query),
                );
                session_write_close();
                xtc_redirect(xtc_href_link(basename($PHP_SELF), xtc_get_all_get_params(array('action', 'oID')).'action=products&oID='.(int)$orders_id, 'SSL'));
              }
            }
          }
        }
        break;

      case 'submit':
        if (isset($_REQUEST['oID'])
            && isset($_SESSION['reclamation'][(int)$_REQUEST['oID']])
            && $_SESSION['reclamation'][(int)$_REQUEST['oID']]['valid'] === true
            )
        {
          $reclamation_array = $_SESSION['reclamation'][(int)$_REQUEST['oID']];
          
          $orders_id = (int)$reclamation_array['orders_id'];
          $orders = $reclamation_array['orders'];

          // Bildrechte-Checkbox pruefen
          if (!isset($_POST['image_rights']) || $_POST['image_rights'] != '1') {
            // Nur pruefen wenn Bilder hochgeladen werden
            if (isset($_FILES['reclamation_images']) && !empty($_FILES['reclamation_images']['name'][0])) {
              $error = true;
              $messageStack->add('reclamation', TEXT_RECLAMATION_IMAGE_RIGHTS_ERROR);
            }
          }

          // Produkte sammeln
          $sql_products = array();
          if (isset($_POST['reclaim_product']) && is_array($_POST['reclaim_product'])) {
            foreach ($_POST['reclaim_product'] as $op_id => $selected) {
              if ($selected != '1') continue;
              
              $pid_query = xtc_db_query("SELECT products_id, products_name, products_model, products_quantity 
                                           FROM ".TABLE_ORDERS_PRODUCTS."
                                          WHERE orders_products_id = '".(int)$op_id."'
                                            AND orders_id = '".(int)$orders_id."'");
              $pid_row = xtc_db_fetch_array($pid_query);
              if (!$pid_row) continue;
              
              $ptype = mrh_reclamation_product_type((int)$pid_row['products_id']);
              
              // Pflanzen duerfen nicht reklamiert werden
              if ($ptype == 'plant') {
                continue;
              }
              
              // Zubehoer: 2-Jahres-Frist pruefen
              if ($ptype == 'default') {
                $order_date = strtotime($orders['date_purchased']);
                $two_years_ago = strtotime('-2 years');
                if ($order_date < $two_years_ago) {
                  continue; // Gewaehrleistungsfrist abgelaufen
                }
              }
              
              // Reklamationsgrund fuer dieses Produkt
              $reason_key = 'reason_' . (int)$op_id;
              $reason = isset($_POST[$reason_key]) ? xtc_db_prepare_input($_POST[$reason_key]) : '';
              
              // Beschreibung
              $desc_key = 'description_' . (int)$op_id;
              $description = isset($_POST[$desc_key]) ? xtc_db_prepare_input(mb_substr($_POST[$desc_key], 0, 2000)) : '';
              
              // Reklamierte Menge
              $qty_key = 'reclaim_qty_' . (int)$op_id;
              $qty = isset($_POST[$qty_key]) ? max(1, (int)$_POST[$qty_key]) : 1;
              $qty = min($qty, (int)$pid_row['products_quantity']);
              
              $product_data = array(
                'orders_products_id' => (int)$op_id,
                'products_id' => (int)$pid_row['products_id'],
                'products_name' => $pid_row['products_name'],
                'products_model' => $pid_row['products_model'],
                'products_quantity' => $qty,
                'product_category' => $ptype,
                'reclamation_reason' => $reason,
                'reclamation_description' => $description,
              );
              
              // Samen-spezifische Felder
              if ($ptype == 'seed') {
                $product_data['seed_germination_method'] = isset($_POST['seed_germ_method_' . (int)$op_id]) ? xtc_db_prepare_input($_POST['seed_germ_method_' . (int)$op_id]) : '';
                $product_data['seed_temperature'] = isset($_POST['seed_temp_' . (int)$op_id]) ? xtc_db_prepare_input($_POST['seed_temp_' . (int)$op_id]) : '';
                $product_data['seed_days_waited'] = isset($_POST['seed_days_' . (int)$op_id]) ? xtc_db_prepare_input($_POST['seed_days_' . (int)$op_id]) : '';
                $product_data['seed_count_failed'] = isset($_POST['seed_count_' . (int)$op_id]) ? (int)$_POST['seed_count_' . (int)$op_id] : 0;
                $product_data['seed_stored_correctly'] = isset($_POST['seed_stored_' . (int)$op_id]) ? 1 : 0;
                $product_data['seed_expected_strain'] = isset($_POST['seed_expected_' . (int)$op_id]) ? xtc_db_prepare_input($_POST['seed_expected_' . (int)$op_id]) : '';
                $product_data['seed_received_strain'] = isset($_POST['seed_received_' . (int)$op_id]) ? xtc_db_prepare_input($_POST['seed_received_' . (int)$op_id]) : '';
                
                // Samen: Bildupload Pflicht (wird spaeter geprueft)
                if ($reason != 'other') {
                  $product_data['image_required'] = true;
                }
              }
              
              $sql_products[] = $product_data;
            }
          }
          
          if (count($sql_products) < 1) {
            $error = true;
            $messageStack->add('reclamation', TEXT_RECLAMATION_NO_PRODUCTS_SELECTED);
          }
          
          // Samen-Bildpflicht pruefen
          if (!$error) {
            $has_seed_with_image_required = false;
            foreach ($sql_products as $sp) {
              if (isset($sp['image_required']) && $sp['image_required']) {
                $has_seed_with_image_required = true;
                break;
              }
            }
            if ($has_seed_with_image_required) {
              if (!isset($_FILES['reclamation_images']) || empty($_FILES['reclamation_images']['name'][0])) {
                $error = true;
                $messageStack->add('reclamation', TEXT_RECLAMATION_SEED_IMAGE_REQUIRED);
              }
            }
          }

          if ($error === true) {
            break;
          }

          // === Reklamation in DB speichern ===
          $sql_data_array = array(
            'orders_id' => $orders_id,
            'customers_name' => xtc_db_input($orders['customers_name']),
            'customers_email' => xtc_db_input($orders['customers_email_address']),
            'reclamation_date' => 'now()',
            'reclamation_status' => 'open',
            'ip_address' => xtc_db_input($_SERVER['REMOTE_ADDR']),
          );
          xtc_db_perform(TABLE_ORDERS_RECLAMATION, $sql_data_array);
          
          $reclamation_id = xtc_db_insert_id();
          $_SESSION['reclamation'][(int)$_REQUEST['oID']]['reclamation_id'] = $reclamation_id;

          // Produkte speichern
          foreach ($sql_products as $sp) {
            $sp_data = array(
              'reclamation_id' => $reclamation_id,
              'products_id' => $sp['products_id'],
              'products_name' => xtc_db_input($sp['products_name']),
              'products_model' => xtc_db_input($sp['products_model']),
              'products_quantity' => $sp['products_quantity'],
              'product_category' => $sp['product_category'],
              'reclamation_reason' => xtc_db_input($sp['reclamation_reason']),
              'reclamation_description' => xtc_db_input($sp['reclamation_description']),
            );
            // Samen-Felder
            if ($sp['product_category'] == 'seed') {
              $sp_data['seed_germination_method'] = isset($sp['seed_germination_method']) ? xtc_db_input($sp['seed_germination_method']) : '';
              $sp_data['seed_temperature'] = isset($sp['seed_temperature']) ? xtc_db_input($sp['seed_temperature']) : '';
              $sp_data['seed_days_waited'] = isset($sp['seed_days_waited']) ? xtc_db_input($sp['seed_days_waited']) : '';
              $sp_data['seed_count_failed'] = isset($sp['seed_count_failed']) ? (int)$sp['seed_count_failed'] : 0;
              $sp_data['seed_stored_correctly'] = isset($sp['seed_stored_correctly']) ? (int)$sp['seed_stored_correctly'] : 0;
              $sp_data['seed_expected_strain'] = isset($sp['seed_expected_strain']) ? xtc_db_input($sp['seed_expected_strain']) : '';
              $sp_data['seed_received_strain'] = isset($sp['seed_received_strain']) ? xtc_db_input($sp['seed_received_strain']) : '';
            }
            xtc_db_perform(TABLE_ORDERS_RECLAMATION_PRODUCTS, $sp_data);
          }

          // === Bildupload verarbeiten ===
          $uploaded_images = mrh_reclamation_handle_uploads($orders_id, $reclamation_id);
          $_SESSION['reclamation'][(int)$_REQUEST['oID']]['uploaded_images'] = $uploaded_images;

          // === E-Mail an Shop senden ===
          $smarty->assign('language', $_SESSION['language']);
          $smarty->assign('tpl_path', HTTP_SERVER.DIR_WS_CATALOG.'templates/'.CURRENT_TEMPLATE.'/');    
          $smarty->assign('logo_path', HTTP_SERVER.DIR_WS_CATALOG.'templates/'.CURRENT_TEMPLATE.'/img/');

          $smarty->assign('NAME', $orders['customers_name']);
          $smarty->assign('EMAIL', $orders['customers_email_address']);
          $smarty->assign('ORDERS_ID', $orders['orders_id']);
          $smarty->assign('RECLAMATION_ID', $reclamation_id);
          $smarty->assign('RECLAMATION_DATE', date('d.m.Y H:i'));
          $smarty->assign('PRODUCTS', $sql_products);
          $smarty->assign('IP_ADDRESS', $_SERVER['REMOTE_ADDR']);

          $html_mail = $smarty->fetch(CURRENT_TEMPLATE.'/mail/'.$_SESSION['language'].'/reclamation_mail.html');
          $txt_mail = $smarty->fetch(CURRENT_TEMPLATE.'/mail/'.$_SESSION['language'].'/reclamation_mail.txt');

          $reclamation_subject = str_replace('{$nr}', $orders_id, EMAIL_RECLAMATION_SUBJECT);

          // E-Mail-Empfaenger aus Konfiguration
          $reclamation_email = (defined('MODULE_RECLAMATION_EMAIL') && MODULE_RECLAMATION_EMAIL != '') 
                               ? MODULE_RECLAMATION_EMAIL 
                               : EMAIL_BILLING_ADDRESS;

          // Bilder als Anhaenge vorbereiten
          $attachments_string = '';
          if (!empty($uploaded_images)) {
            $attachment_paths = array();
            foreach ($uploaded_images as $img) {
              $attachment_paths[] = $img['path'];
            }
            $attachments_string = implode(';', $attachment_paths);
          }

          xtc_php_mail(EMAIL_BILLING_ADDRESS,
                       EMAIL_BILLING_NAME,
                       $reclamation_email,
                       STORE_NAME,
                       EMAIL_BILLING_FORWARDING_STRING,
                       trim($orders['customers_email_address']),
                       $orders['customers_name'],
                       $attachments_string,
                       '',
                       $reclamation_subject,
                       $html_mail,
                       $txt_mail,
                       4
                       );

          // === Bestaetigungs-E-Mail an Kunden ===
          $html_confirm = $smarty->fetch(CURRENT_TEMPLATE.'/mail/'.$_SESSION['language'].'/reclamation_confirm.html');
          $txt_confirm = $smarty->fetch(CURRENT_TEMPLATE.'/mail/'.$_SESSION['language'].'/reclamation_confirm.txt');

          $confirm_subject = str_replace('{$nr}', $orders_id, EMAIL_RECLAMATION_CONFIRM_SUBJECT);

          if (SEND_EMAILS == 'true') {
            xtc_php_mail(EMAIL_BILLING_ADDRESS,
                         EMAIL_BILLING_NAME,
                         trim($orders['customers_email_address']),
                         $orders['customers_name'],
                         '',
                         EMAIL_BILLING_REPLY_ADDRESS,
                         EMAIL_BILLING_REPLY_ADDRESS_NAME,
                         '',
                         '',
                         $confirm_subject,
                         $html_confirm,
                         $txt_confirm,
                         2
                         );
          }
                          
          // === Kommentar in Bestellhistorie ===
          $recl_comment_text = 'Reklamation #' . $reclamation_id . ' eingereicht am ' . date('d.m.Y H:i') . ' Uhr';
          $product_names = array();
          foreach ($sql_products as $sp) {
            $product_names[] = $sp['products_name'] . ' (' . $sp['reclamation_reason'] . ')';
          }
          if (!empty($product_names)) {
            $recl_comment_text .= ' | Produkte: ' . implode(', ', $product_names);
          }
          xtc_db_query("INSERT INTO orders_status_history 
                        (orders_id, orders_status_id, date_added, customer_notified, comments) 
                        VALUES (
                          '" . (int)$orders_id . "',
                          (SELECT orders_status FROM orders WHERE orders_id = '" . (int)$orders_id . "'),
                          NOW(),
                          0,
                          '" . xtc_db_input($recl_comment_text) . "'
                        )");

          $messageStack->add_session('reclamation', TEXT_RECLAMATION_SUCCESS_MSG, 'success');
          xtc_redirect(xtc_href_link(basename($PHP_SELF), xtc_get_all_get_params(array('action', 'key')).'action=success&oID='.(int)$orders_id, 'SSL'));
        } else {
          $messageStack->add_session('reclamation', ENTRY_TOKEN_ERROR);
          xtc_redirect(xtc_href_link(basename($PHP_SELF), xtc_get_all_get_params(array('action', 'key'))));
        }
        break;
      
      case 'success':
        if (isset($_REQUEST['oID'])
            && isset($_SESSION['reclamation'][(int)$_REQUEST['oID']])
            && $_SESSION['reclamation'][(int)$_REQUEST['oID']]['valid'] === true
            )
        {
          $_SESSION['reclamation'][(int)$_REQUEST['oID']]['success'] = true;
        } else {
          $messageStack->add_session('reclamation', ENTRY_TOKEN_ERROR);
          xtc_redirect(xtc_href_link(basename($PHP_SELF), 'coID='.(int)$_GET['coID'], 'SSL'));
        }
        break;
        
      default:
        $_SESSION['reclamation'] = array();
        
    }
      
    $smarty->assign('RECLAMATION_HEADING', ((!empty($shop_content_data['content_heading'])) ? $shop_content_data['content_heading'] : $shop_content_data['content_title']));
    $smarty->assign('RECLAMATION_CONTENT', $shop_content_data['content_text']);
  
    if (isset($_REQUEST['oID'])
        && isset($_SESSION['reclamation'][(int)$_REQUEST['oID']])
        )
    {
      $reclamation_array = $_SESSION['reclamation'][(int)$_REQUEST['oID']];

      $orders_id = (int)$reclamation_array['orders_id'];
      $orders = $reclamation_array['orders'];
      
      if (isset($reclamation_array['success']) && $reclamation_array['success'] === true) {
        // === Erfolgsseite ===
        $reclamation_id = $reclamation_array['reclamation_id'];
        
        // Produkte aus DB laden
        $recl_products_array = array();
        $recl_products_query = xtc_db_query("SELECT * FROM " . TABLE_ORDERS_RECLAMATION_PRODUCTS . " 
                                              WHERE reclamation_id = '" . (int)$reclamation_id . "'");
        while ($rp = xtc_db_fetch_array($recl_products_query)) {
          $recl_products_array[] = array(
            'PRODUCTS_NAME' => $rp['products_name'],
            'PRODUCTS_MODEL' => $rp['products_model'],
            'PRODUCTS_QUANTITY' => $rp['products_quantity'],
            'PRODUCT_CATEGORY' => $rp['product_category'],
            'RECLAMATION_REASON' => $rp['reclamation_reason'],
            'RECLAMATION_DESCRIPTION' => $rp['reclamation_description'],
          );
        }
        $smarty->assign('RECL_PRODUCTS', $recl_products_array);
        
        // Bilder laden
        $recl_images_array = array();
        $recl_images_query = xtc_db_query("SELECT * FROM " . TABLE_ORDERS_RECLAMATION_IMAGES . " 
                                            WHERE reclamation_id = '" . (int)$reclamation_id . "'");
        while ($ri = xtc_db_fetch_array($recl_images_query)) {
          $recl_images_array[] = array(
            'IMAGE_PATH' => HTTP_SERVER . DIR_WS_CATALOG . $ri['image_path'],
            'IMAGE_NAME' => $ri['image_original_name'],
          );
        }
        $smarty->assign('RECL_IMAGES', $recl_images_array);
        $smarty->assign('RECLAMATION_ID', $reclamation_id);

        $smarty->assign('BUTTON_CONTINUE', '<a href="'.xtc_href_link(basename($PHP_SELF), 'coID='.(int)$_GET['coID'], 'SSL').'">'.xtc_image_button('button_continue.gif', IMAGE_BUTTON_CONTINUE).'</a>');
        $smarty->assign('FORM_TYPE', 'success');
        
      } elseif ($reclamation_array['valid'] === true) {
        // === Produktauswahl-Seite ===
        $smarty->assign('FORM_ACTION', xtc_draw_form('reclamation', xtc_href_link(basename($PHP_SELF), xtc_get_all_get_params(array('action', 'key')).'action=submit', 'SSL'), 'post', 'enctype="multipart/form-data"').secure_form('reclamation'));
        
        $smarty->assign('RECLAMATION_INFO', sprintf(TEXT_RECLAMATION_INFO, $orders_id, xtc_date_long($orders['date_purchased'])));
        
        // Bestelldatum fuer 2-Jahres-Frist
        $order_date = strtotime($orders['date_purchased']);
        $two_years_ago = strtotime('-2 years');
        
        $orders_products_array = array();
        $orders_products_query = xtc_db_query("SELECT op.*,
                                                       o.currency,
                                                       o.customers_status
                                                  FROM ".TABLE_ORDERS." o
                                                  JOIN ".TABLE_ORDERS_PRODUCTS." op
                                                       ON o.orders_id = op.orders_id 
                                                 WHERE o.orders_id = '".(int)$orders_id."'
                                              GROUP BY op.orders_products_id");
        while ($op = xtc_db_fetch_array($orders_products_query)) {
          $xtPrice = new xtcPrice($op['currency'], $op['customers_status']);
          $ptype = mrh_reclamation_product_type((int)$op['products_id']);
  
          $is_blocked = ($ptype == 'plant') ? 1 : 0;
          $is_seed = ($ptype == 'seed') ? 1 : 0;
          $is_expired = ($ptype == 'default' && $order_date < $two_years_ago) ? 1 : 0;
          
          $attributes_array = array();
          $attributes_query = xtc_db_query("SELECT *
                                              FROM ".TABLE_ORDERS_PRODUCTS_ATTRIBUTES."
                                             WHERE orders_id = '".(int)$orders_id."'
                                               AND orders_products_id = '".$op['orders_products_id']."'");
          if (xtc_db_num_rows($attributes_query) > 0) {
            while ($attr = xtc_db_fetch_array($attributes_query)) {
              $attributes_array[] = array(
                'OPTIONS_NAME' => $attr['products_options'],
                'VALUES_NAME' => $attr['products_options_values'],
              );
            }
          }

          $orders_products_array[] = array(
            'PRODUCTS_NAME' => $op['products_name'],
            'PRODUCTS_MODEL' => $op['products_model'],
            'PRODUCTS_PRICE' => $xtPrice->xtcFormat($op['products_price'], true),
            'PRODUCTS_QUANTITY' => (int)$op['products_quantity'],
            'PRODUCTS_ATTRIBUTES' => $attributes_array,
            'PRODUCT_TYPE' => $ptype,
            'PRODUCT_BLOCKED' => $is_blocked,
            'PRODUCT_IS_SEED' => $is_seed,
            'PRODUCT_EXPIRED' => $is_expired,
            'ORDERS_PRODUCTS_ID' => $op['orders_products_id'],
          );
        }
  
        $smarty->assign('PRODUCTS', $orders_products_array);
  
        if (count($orders_products_array) > 0) {
          $smarty->assign('BUTTON_SUBMIT', xtc_image_submit('button_send.gif', IMAGE_BUTTON_RECLAMATION));
        }
        $smarty->assign('BUTTON_BACK', '<a href="'.xtc_href_link(basename($PHP_SELF), 'coID='.(int)$_GET['coID'], 'SSL').'">'.xtc_image_button('button_back.gif', IMAGE_BUTTON_BACK).'</a>');
        $smarty->assign('FORM_END', '</form>');
        $smarty->assign('FORM_TYPE', 'products');    
      }
    } else {    
      // === Authentifizierungs-Formular ===
      $smarty->assign('FORM_ACTION', xtc_draw_form('reclamation', xtc_href_link(basename($PHP_SELF), xtc_get_all_get_params(array('action')).'action=auth', 'SSL')).secure_form('reclamation'));
      if (in_array('reclamation', $use_captcha) && (!isset($_SESSION['customer_id']) || MODULE_CAPTCHA_LOGGED_IN == 'True')) {
        $smarty->assign('VVIMG', $mod_captcha->get_image_code());
        $smarty->assign('INPUT_CODE', $mod_captcha->get_input_code());
      }
      if (DISPLAY_PRIVACY_CHECK == 'true') {
        $smarty->assign('PRIVACY_CHECKBOX', xtc_draw_checkbox_field('privacy', 'privacy', $privacy, 'id="privacy"'));
      }
      $smarty->assign('PRIVACY_LINK', $main->getContentLink(2, MORE_INFO, $request_type));      
      $smarty->assign('INPUT_EMAIL', xtc_draw_input_fieldNote(array('name' => 'email_address', 'text' => (xtc_not_null(ENTRY_EMAIL_ADDRESS_TEXT) ? '<span class="inputRequirement">'.ENTRY_EMAIL_ADDRESS_TEXT.'</span>' : '')), '', 'autocomplete="email"'));
      $smarty->assign('INPUT_ORDER', xtc_draw_input_fieldNote(array('name' => 'orders_id', 'text' => (xtc_not_null(ENTRY_ORDERS_ID_TEXT) ? '<span class="inputRequirement">'.ENTRY_ORDERS_ID_TEXT.'</span>' : ''))));
      $smarty->assign('BUTTON_SUBMIT', xtc_image_submit('button_continue.gif', IMAGE_BUTTON_CONTINUE));
      $smarty->assign('FORM_END', '</form>');
      $smarty->assign('FORM_TYPE', 'auth');
    }
  
    if ($messageStack->size('reclamation') > 0) {
      $smarty->assign('error_message', $messageStack->output('reclamation'));
    }
    if ($messageStack->size('reclamation', 'success') > 0) {
      $smarty->assign('success_message', $messageStack->output('reclamation', 'success'));
    }
  
    $smarty->assign('language', $_SESSION['language']);
    $smarty->caching = 0;
    $smarty->display(CURRENT_TEMPLATE.'/module/reclamation.html');
    $display_mode = 'contactus';
    
    // clear variables
    $smarty->clear_assign('BUTTON_CONTINUE');
    $smarty->clear_assign('CONTENT_HEADING');
    $content_body = '';
    unset($email);
    
    // disable cache
    $disable_smarty_cache = true;
  }
