<?php
/**
 * Autoinclude: Lädt Reklamations-Sprachkonstanten global
 * Pfad: includes/extra/application_top/reclamation_lang_loader.php
 * 
 * Damit TEXT_RECLAMATION_BTN_SUBMIT und TEXT_RECLAMATION_BTN_EXPIRED
 * auch auf der Bestellhistorie-Seite verfügbar sind.
 */
if (!defined('TEXT_RECLAMATION_BTN_SUBMIT')) {
  $recl_lang_file = DIR_FS_LANGUAGES . $_SESSION['language'] . '/extra/reclamation.php';
  if (file_exists($recl_lang_file)) {
    require_once($recl_lang_file);
  }
}
