<?php
/* -----------------------------------------------------------------------------------------
   $Id$

   modified eCommerce Shopsoftware
   http://www.modified-shop.org

   Copyright (c) 2009 - 2013 [www.modified-shop.org]
   -----------------------------------------------------------------------------------------
   Released under the GNU General Public License
   
   MRH 2026: Reklamationsmodul – Admin Order Info Block
   Zeigt Reklamationen in der Bestelldetail-Ansicht an.
   Admin kann Status aendern, Kommentar hinzufuegen.
   ---------------------------------------------------------------------------------------*/

  if (defined('MODULE_RECLAMATION_STATUS')
      && MODULE_RECLAMATION_STATUS == 'true'
      )
  {
    // MRH: Admin-Aktion verarbeiten (Status aendern)
    if (isset($_POST['mrh_reclamation_action']) && isset($_POST['mrh_reclamation_id'])) {
      $recl_id = (int)$_POST['mrh_reclamation_id'];
      $allowed_statuses = array('open', 'in_progress', 'resolved', 'rejected', 'closed');
      $new_status = isset($_POST['mrh_reclamation_new_status']) ? $_POST['mrh_reclamation_new_status'] : '';
      
      if (in_array($new_status, $allowed_statuses)) {
        $admin_comment = isset($_POST['mrh_reclamation_comment']) ? xtc_db_prepare_input(mb_substr($_POST['mrh_reclamation_comment'], 0, 1000)) : '';
        
        xtc_db_query("UPDATE " . TABLE_ORDERS_RECLAMATION . " 
                          SET reclamation_status = '" . xtc_db_input($new_status) . "',
                              admin_comment = '" . xtc_db_input($admin_comment) . "',
                              admin_date = NOW()
                        WHERE reclamation_id = '" . $recl_id . "'");
        
        $messageStack->add_session(sprintf(TEXT_RECLAMATION_ADMIN_STATUS_UPDATED, $recl_id, $new_status), 'success');
      }
    }

    // Alle Reklamationen fuer diese Bestellung laden
    $orders_reclamation_query = xtc_db_query("SELECT * 
                                               FROM " . TABLE_ORDERS_RECLAMATION . " 
                                              WHERE orders_id = '" . (int)$oID . "' 
                                           ORDER BY reclamation_date DESC");
    
    if (xtc_db_num_rows($orders_reclamation_query) > 0) {
      ?>
      <div id="reclamation_block">
      <div class="heading"><span class="fa-solid fa-triangle-exclamation"></span> <?php echo TABLE_HEADING_RECLAMATION; ?></div>
      <?php
      while ($reclamation = xtc_db_fetch_array($orders_reclamation_query)) {
        $recl_id = (int)$reclamation['reclamation_id'];
        $recl_status = $reclamation['reclamation_status'];
        
        // Status-Badge
        $status_badge = '';
        $border_color = '#6c757d';
        switch ($recl_status) {
          case 'open':
            $status_badge = '<span class="badge bg-warning text-dark">' . TEXT_RECLAMATION_STATUS_OPEN . '</span>';
            $border_color = '#ffc107';
            break;
          case 'in_progress':
            $status_badge = '<span class="badge bg-info text-dark">' . TEXT_RECLAMATION_STATUS_IN_PROGRESS . '</span>';
            $border_color = '#0dcaf0';
            break;
          case 'resolved':
            $status_badge = '<span class="badge bg-success">' . TEXT_RECLAMATION_STATUS_RESOLVED . '</span>';
            $border_color = '#198754';
            break;
          case 'rejected':
            $status_badge = '<span class="badge bg-danger">' . TEXT_RECLAMATION_STATUS_REJECTED . '</span>';
            $border_color = '#dc3545';
            break;
          case 'closed':
            $status_badge = '<span class="badge bg-secondary">' . TEXT_RECLAMATION_STATUS_CLOSED . '</span>';
            $border_color = '#6c757d';
            break;
        }
        ?>
        <div class="card mb-3" style="border-left: 4px solid <?php echo $border_color; ?>;">
          <div class="card-header d-flex justify-content-between align-items-center">
            <strong>Reklamation #<?php echo $recl_id; ?> &ndash; <?php echo xtc_datetime_short($reclamation['reclamation_date']); ?></strong>
            <?php echo $status_badge; ?>
          </div>
          <div class="card-body">
            <?php
            // IP-Adresse anzeigen (Anti-Betrug)
            if (!empty($reclamation['ip_address'])) {
              echo '<p class="small text-muted"><span class="fa-solid fa-globe me-1"></span>IP: ' . htmlspecialchars($reclamation['ip_address']) . '</p>';
            }
            
            // Admin-Kommentar anzeigen (falls vorhanden)
            if (!empty($reclamation['admin_comment'])) {
              echo '<p class="text-muted"><strong>Admin-Kommentar:</strong> ' . htmlspecialchars($reclamation['admin_comment']) . '</p>';
            }
            if (!empty($reclamation['admin_date'])) {
              echo '<p class="text-muted small">Zuletzt bearbeitet: ' . xtc_datetime_short($reclamation['admin_date']) . '</p>';
            }
            ?>
            
            <table cellspacing="0" cellpadding="2" class="table table-sm">
              <tr class="dataTableHeadingRow">
                <td class="dataTableHeadingContent" style="width:5%">Stk.</td>
                <td class="dataTableHeadingContent"><?php echo TABLE_HEADING_PRODUCTS; ?></td>
                <td class="dataTableHeadingContent" style="width:12%"><?php echo TABLE_HEADING_PRODUCTS_MODEL; ?></td>
                <td class="dataTableHeadingContent" style="width:10%">Kategorie</td>
                <td class="dataTableHeadingContent" style="width:15%">Grund</td>
                <td class="dataTableHeadingContent">Beschreibung</td>
              </tr>
              <?php
              $orp_query = xtc_db_query("SELECT * FROM " . TABLE_ORDERS_RECLAMATION_PRODUCTS . " WHERE reclamation_id = '" . $recl_id . "'");
              while ($orp = xtc_db_fetch_array($orp_query)) {
                $cat_label = '';
                $row_class = '';
                
                switch ($orp['product_category']) {
                  case 'seed':
                    $cat_label = '<span class="badge bg-warning text-dark"><span class="fa-solid fa-seedling"></span> Samen</span>';
                    break;
                  case 'plant':
                    $cat_label = '<span class="badge bg-success"><span class="fa-solid fa-leaf"></span> Pflanze</span>';
                    $row_class = ' class="table-danger"';
                    break;
                  default:
                    $cat_label = '<span class="badge bg-secondary">Zubeh&ouml;r</span>';
                    break;
                }
                
                echo '<tr' . $row_class . '>' . PHP_EOL;
                echo '  <td class="dataTableContent" valign="top" align="right">' . $orp['products_quantity'] . '&nbsp;x</td>' . PHP_EOL;
                echo '  <td class="dataTableContent" valign="top">' . htmlspecialchars($orp['products_name']) . '</td>' . PHP_EOL;
                echo '  <td class="dataTableContent" valign="top">' . htmlspecialchars($orp['products_model']) . '</td>' . PHP_EOL;
                echo '  <td class="dataTableContent" valign="top">' . $cat_label . '</td>' . PHP_EOL;
                echo '  <td class="dataTableContent" valign="top">' . htmlspecialchars($orp['reclamation_reason']) . '</td>' . PHP_EOL;
                echo '  <td class="dataTableContent" valign="top">' . nl2br(htmlspecialchars(mb_substr($orp['reclamation_description'], 0, 200))) . '</td>' . PHP_EOL;
                echo '</tr>' . PHP_EOL;
                
                // Samen-Details anzeigen
                if ($orp['product_category'] == 'seed' && !empty($orp['seed_germination_method'])) {
                  echo '<tr class="table-warning">' . PHP_EOL;
                  echo '  <td colspan="6" class="dataTableContent small">' . PHP_EOL;
                  echo '    <span class="fa-solid fa-seedling me-1"></span> ';
                  echo '    <strong>Keimmethode:</strong> ' . htmlspecialchars($orp['seed_germination_method']) . ' | ';
                  echo '    <strong>Temperatur:</strong> ' . htmlspecialchars($orp['seed_temperature']) . ' | ';
                  echo '    <strong>Tage gewartet:</strong> ' . htmlspecialchars($orp['seed_days_waited']) . ' | ';
                  echo '    <strong>Nicht gekeimt:</strong> ' . (int)$orp['seed_count_failed'] . ' St&uuml;ck | ';
                  echo '    <strong>Korrekt gelagert:</strong> ' . ($orp['seed_stored_correctly'] ? 'Ja' : 'Nein');
                  if (!empty($orp['seed_expected_strain'])) {
                    echo ' | <strong>Erwartet:</strong> ' . htmlspecialchars($orp['seed_expected_strain']);
                    echo ' | <strong>Erhalten:</strong> ' . htmlspecialchars($orp['seed_received_strain']);
                  }
                  echo '  </td>' . PHP_EOL;
                  echo '</tr>' . PHP_EOL;
                }
              }
              ?>
            </table>
            
            <?php
            // Bilder anzeigen
            $img_query = xtc_db_query("SELECT * FROM " . TABLE_ORDERS_RECLAMATION_IMAGES . " WHERE reclamation_id = '" . $recl_id . "'");
            if (xtc_db_num_rows($img_query) > 0) {
              echo '<div class="mt-2 mb-3">';
              echo '<strong><span class="fa-solid fa-images me-1"></span> Hochgeladene Bilder:</strong><br>';
              echo '<div class="d-flex flex-wrap gap-2 mt-1">';
              while ($img = xtc_db_fetch_array($img_query)) {
                $img_url = HTTP_SERVER . DIR_WS_CATALOG . $img['image_path'];
                echo '<a href="' . $img_url . '" target="_blank" title="' . htmlspecialchars($img['image_original_name']) . '">';
                echo '<img src="' . $img_url . '" style="max-height:80px;border-radius:4px;border:1px solid #dee2e6;" alt="' . htmlspecialchars($img['image_original_name']) . '">';
                echo '</a>';
              }
              echo '</div>';
              echo '</div>';
            }
            
            // Status-Aenderung (fuer offene/in Bearbeitung)
            if (in_array($recl_status, array('open', 'in_progress'))) {
              ?>
              <form method="post" action="<?php echo xtc_href_link(FILENAME_ORDERS, 'oID=' . (int)$oID . '&action=edit'); ?>" class="mt-3">
                <input type="hidden" name="mrh_reclamation_id" value="<?php echo $recl_id; ?>">
                <input type="hidden" name="mrh_reclamation_action" value="1">
                <div class="row g-2 align-items-end">
                  <div class="col-md-3">
                    <label class="form-label"><strong>Status &auml;ndern:</strong></label>
                    <select name="mrh_reclamation_new_status" class="form-select form-select-sm">
                      <option value="open" <?php echo ($recl_status == 'open') ? 'selected' : ''; ?>>Offen</option>
                      <option value="in_progress" <?php echo ($recl_status == 'in_progress') ? 'selected' : ''; ?>>In Bearbeitung</option>
                      <option value="resolved">Gel&ouml;st</option>
                      <option value="rejected">Abgelehnt</option>
                      <option value="closed">Geschlossen</option>
                    </select>
                  </div>
                  <div class="col-md-6">
                    <label class="form-label"><strong>Admin-Kommentar:</strong></label>
                    <textarea name="mrh_reclamation_comment" class="form-control form-control-sm" rows="2" maxlength="1000" placeholder="Optionaler Kommentar..."><?php echo htmlspecialchars($reclamation['admin_comment']); ?></textarea>
                  </div>
                  <div class="col-md-3">
                    <button type="submit" class="btn btn-primary btn-sm w-100">
                      <span class="fa-solid fa-floppy-disk me-1"></span> Speichern
                    </button>
                  </div>
                </div>
              </form>
              <?php
            }
            ?>
          </div>
        </div>
        <?php
      }
      ?>
      </div>
      <?php
    }
  }
