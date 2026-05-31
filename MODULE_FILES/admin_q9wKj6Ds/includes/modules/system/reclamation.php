<?php
/* -----------------------------------------------------------------------------------------
   $Id$

   modified eCommerce Shopsoftware
   http://www.modified-shop.org

   Copyright (c) 2009 - 2013 [www.modified-shop.org]
   -----------------------------------------------------------------------------------------
   Released under the GNU General Public License
   
   MRH 2026: Reklamationsmodul fuer Mr. Hanf – DB-Installer
   - Haupttabelle orders_reclamation
   - Produkttabelle orders_reclamation_products (inkl. Samen-Felder)
   - Bildtabelle orders_reclamation_images
   - Admin-Access-Spalte fuer reclamation_dashboard
   ---------------------------------------------------------------------------------------*/

  defined( '_VALID_XTC' ) or die( 'Direct Access to this location is not allowed.' );
  
  class reclamation {
  
    var $code;
    var $title;
    var $description;
    var $sort_order;
    var $enabled;
    var $_check;
    var $version;
  
    function __construct() {
      $this->version = '1.00';
      $this->code = 'reclamation';
      $this->title = MODULE_RECLAMATION_TEXT_TITLE;
      $this->description = MODULE_RECLAMATION_TEXT_DESCRIPTION;
      $this->enabled = ((defined('MODULE_RECLAMATION_STATUS') && MODULE_RECLAMATION_STATUS == 'true') ? true : false);
    }
    
    function process($file) {
    }
    
    function display() {
      return array('text' => '<br /><div align="center">' . xtc_button(BUTTON_SAVE) .
                             xtc_button_link(BUTTON_CANCEL, xtc_href_link(FILENAME_MODULE_EXPORT, 'set=' . $_GET['set'] . '&module=reclamation')) . "</div>");
    }
    
    function check() {
      if (!isset($this->_check)) {
        if (defined('MODULE_RECLAMATION_STATUS')) {
          $this->_check = true;
        } else {
          $check_query = xtc_db_query("SELECT configuration_value 
                                         FROM " . TABLE_CONFIGURATION . " 
                                        WHERE configuration_key = 'MODULE_RECLAMATION_STATUS'");
          $this->_check = xtc_db_num_rows($check_query);
        }
      }
      return $this->_check;
    }
    
    function install() {
      // Konfigurationseintraege
      xtc_db_query("INSERT INTO " . TABLE_CONFIGURATION . " (configuration_key, configuration_value, configuration_group_id, sort_order, set_function, date_added) VALUES ('MODULE_RECLAMATION_STATUS', 'true',  '6', '1', 'xtc_cfg_select_option(array(\'true\', \'false\'), ', now())");  
      xtc_db_query("INSERT INTO " . TABLE_CONFIGURATION . " (configuration_key, configuration_value, configuration_group_id, sort_order, set_function, date_added) VALUES ('MODULE_RECLAMATION_CAPTCHA', 'false',  '6', '2', 'xtc_cfg_select_option(array(\'true\', \'false\'), ', now())");  
      xtc_db_query("INSERT INTO " . TABLE_CONFIGURATION . " (configuration_key, configuration_value, configuration_group_id, sort_order, set_function, use_function, date_added) VALUES ('MODULE_RECLAMATION_CONTENT', '',  '6', '3', 'xtc_cfg_select_content_module(', 'xtc_cfg_display_content', now())");
      xtc_db_query("INSERT INTO " . TABLE_CONFIGURATION . " (configuration_key, configuration_value, configuration_group_id, sort_order, date_added) VALUES ('MODULE_RECLAMATION_EMAIL', '',  '6', '4', now())");

      // Haupttabelle: orders_reclamation
      xtc_db_query("CREATE TABLE IF NOT EXISTS `orders_reclamation` (
                     `reclamation_id` int(11) NOT NULL AUTO_INCREMENT,
                     `orders_id` int(11) NOT NULL,
                     `customers_name` varchar(255) NOT NULL DEFAULT '',
                     `customers_email` varchar(255) NOT NULL DEFAULT '',
                     `reclamation_date` datetime NOT NULL,
                     `reclamation_status` enum('open','in_progress','resolved','rejected','closed') NOT NULL DEFAULT 'open',
                     `admin_comment` text,
                     `admin_date` datetime DEFAULT NULL,
                     `ip_address` varchar(45) NOT NULL DEFAULT '',
                     PRIMARY KEY (`reclamation_id`),
                     KEY `idx_orders_id` (`orders_id`),
                     KEY `idx_reclamation_status` (`reclamation_status`),
                     KEY `idx_reclamation_date` (`reclamation_date`)
                     ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

      // Produkttabelle: orders_reclamation_products
      xtc_db_query("CREATE TABLE IF NOT EXISTS `orders_reclamation_products` (
                     `reclamation_product_id` int(11) NOT NULL AUTO_INCREMENT,
                     `reclamation_id` int(11) NOT NULL,
                     `products_id` int(11) NOT NULL,
                     `products_name` varchar(255) NOT NULL DEFAULT '',
                     `products_model` varchar(128) NOT NULL DEFAULT '',
                     `products_quantity` int(11) NOT NULL DEFAULT 1,
                     `product_category` enum('seed','plant','default') NOT NULL DEFAULT 'default',
                     `reclamation_reason` varchar(100) NOT NULL DEFAULT '',
                     `reclamation_description` text,
                     `seed_germination_method` varchar(50) DEFAULT NULL,
                     `seed_temperature` varchar(20) DEFAULT NULL,
                     `seed_days_waited` varchar(20) DEFAULT NULL,
                     `seed_count_failed` int(11) DEFAULT 0,
                     `seed_stored_correctly` tinyint(1) DEFAULT NULL,
                     `seed_expected_strain` varchar(255) DEFAULT NULL,
                     `seed_received_strain` varchar(255) DEFAULT NULL,
                     PRIMARY KEY (`reclamation_product_id`),
                     KEY `idx_reclamation_id` (`reclamation_id`),
                     KEY `idx_products_id` (`products_id`)
                     ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

      // Bildtabelle: orders_reclamation_images
      xtc_db_query("CREATE TABLE IF NOT EXISTS `orders_reclamation_images` (
                     `image_id` int(11) NOT NULL AUTO_INCREMENT,
                     `reclamation_id` int(11) NOT NULL,
                     `reclamation_product_id` int(11) DEFAULT 0,
                     `image_path` varchar(500) NOT NULL DEFAULT '',
                     `image_original_name` varchar(255) NOT NULL DEFAULT '',
                     `image_size` int(11) DEFAULT 0,
                     `image_type` varchar(50) DEFAULT '',
                     `upload_date` datetime NOT NULL,
                     PRIMARY KEY (`image_id`),
                     KEY `idx_reclamation_id` (`reclamation_id`)
                     ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

      // Admin-Access Spalte hinzufuegen
      $this->_addAdminAccess();

      // Upload-Verzeichnis erstellen
      $upload_dir = DIR_FS_CATALOG . 'images/reclamation/';
      if (!is_dir($upload_dir)) {
        @mkdir($upload_dir, 0755, true);
      }

      // Upgrade bestehender Tabellen
      $this->_mrh_upgrade_columns();
    }

    /**
     * MRH 2026: Upgrade-Funktion – Fuegt neue Spalten hinzu falls sie noch nicht existieren
     */
    function _mrh_upgrade_columns() {
      // orders_reclamation: Neue Spalten pruefen
      $cols_or = array(
        'ip_address' => "ALTER TABLE `orders_reclamation` ADD COLUMN `ip_address` varchar(45) NOT NULL DEFAULT '' AFTER `admin_date`",
      );
      foreach ($cols_or as $col => $sql) {
        $check = xtc_db_query("SHOW COLUMNS FROM `orders_reclamation` LIKE '" . $col . "'");
        if (xtc_db_num_rows($check) < 1) {
          xtc_db_query($sql);
        }
      }

      // orders_reclamation_products: Samen-Felder pruefen
      $cols_orp = array(
        'seed_germination_method' => "ALTER TABLE `orders_reclamation_products` ADD COLUMN `seed_germination_method` varchar(50) DEFAULT NULL AFTER `reclamation_description`",
        'seed_temperature' => "ALTER TABLE `orders_reclamation_products` ADD COLUMN `seed_temperature` varchar(20) DEFAULT NULL AFTER `seed_germination_method`",
        'seed_days_waited' => "ALTER TABLE `orders_reclamation_products` ADD COLUMN `seed_days_waited` varchar(20) DEFAULT NULL AFTER `seed_temperature`",
        'seed_count_failed' => "ALTER TABLE `orders_reclamation_products` ADD COLUMN `seed_count_failed` int(11) DEFAULT 0 AFTER `seed_days_waited`",
        'seed_stored_correctly' => "ALTER TABLE `orders_reclamation_products` ADD COLUMN `seed_stored_correctly` tinyint(1) DEFAULT NULL AFTER `seed_count_failed`",
        'seed_expected_strain' => "ALTER TABLE `orders_reclamation_products` ADD COLUMN `seed_expected_strain` varchar(255) DEFAULT NULL AFTER `seed_stored_correctly`",
        'seed_received_strain' => "ALTER TABLE `orders_reclamation_products` ADD COLUMN `seed_received_strain` varchar(255) DEFAULT NULL AFTER `seed_expected_strain`",
      );
      foreach ($cols_orp as $col => $sql) {
        $check = xtc_db_query("SHOW COLUMNS FROM `orders_reclamation_products` LIKE '" . $col . "'");
        if (xtc_db_num_rows($check) < 1) {
          xtc_db_query($sql);
        }
      }
    }

    /**
     * Admin-Access Spalte hinzufuegen
     */
    function _addAdminAccess() {
      $check = xtc_db_query("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'admin_access' AND COLUMN_NAME = 'reclamation_dashboard'");
      if (!xtc_db_fetch_array($check)) {
        xtc_db_query("ALTER TABLE admin_access ADD COLUMN reclamation_dashboard INT(1) NOT NULL DEFAULT 1");
      }
    }

    /**
     * Admin-Access Spalte entfernen
     */
    function _removeAdminAccess() {
      $check = xtc_db_query("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'admin_access' AND COLUMN_NAME = 'reclamation_dashboard'");
      if (xtc_db_fetch_array($check)) {
        xtc_db_query("ALTER TABLE admin_access DROP COLUMN reclamation_dashboard");
      }
    }
    
    function remove() {
      xtc_db_query("DELETE FROM " . TABLE_CONFIGURATION . " WHERE configuration_key in ('" . implode("', '", $this->keys()) . "')");
      $this->_removeAdminAccess();
      // Tabellen NICHT loeschen (Datensicherheit)
    }
    
    function keys() {
      $key = array(
        'MODULE_RECLAMATION_STATUS',
        'MODULE_RECLAMATION_CAPTCHA',
        'MODULE_RECLAMATION_CONTENT',
        'MODULE_RECLAMATION_EMAIL',
      );
  
      return $key;
    }
    
  }
