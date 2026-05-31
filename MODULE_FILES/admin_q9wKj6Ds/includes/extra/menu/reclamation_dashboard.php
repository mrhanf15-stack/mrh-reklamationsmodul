<?php
/* -----------------------------------------------------------------------------------------
   MRH 2026: Reklamationsmodul – Admin-Menu-Eintrag
   ---------------------------------------------------------------------------------------*/

  defined( '_VALID_XTC' ) or die( 'Direct Access to this location is not allowed.' );

  if (defined('MODULE_RECLAMATION_STATUS') && MODULE_RECLAMATION_STATUS == 'true') {
    $lang_code = isset($_SESSION['language_code']) ? $_SESSION['language_code'] : 'de';
    
    switch ($lang_code) {
      case 'en':
        $menu_label = 'Reclamation Dashboard';
        break;
      case 'fr':
        $menu_label = 'Tableau de bord r&eacute;clamations';
        break;
      case 'es':
        $menu_label = 'Panel de reclamaciones';
        break;
      default:
        $menu_label = 'Reklamations-Dashboard';
        break;
    }
    
    $add_contents[BOX_HEADING_CUSTOMERS][] = array(
      'admin_access_name' => 'reclamation_dashboard',
      'filename' => FILENAME_RECLAMATION_DASHBOARD,
      'boxname' => $menu_label,
      'parameters' => '',
    );
  }
