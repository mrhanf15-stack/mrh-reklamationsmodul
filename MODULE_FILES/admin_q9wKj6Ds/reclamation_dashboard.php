<?php
/* -----------------------------------------------------------------------------------------
   $Id$

   modified eCommerce Shopsoftware
   http://www.modified-shop.org

   Copyright (c) 2009 - 2013 [www.modified-shop.org]
   -----------------------------------------------------------------------------------------
   Released under the GNU General Public License
   
   MRH 2026: Reklamations-Dashboard fuer Mr. Hanf
   Uebersicht aller Reklamationen mit Filter, Status-Aenderung, Detail-Ansicht
   ---------------------------------------------------------------------------------------*/

  require('includes/application_top.php');

  // Modul-Check
  if (!defined('MODULE_RECLAMATION_STATUS') || MODULE_RECLAMATION_STATUS != 'true') {
    xtc_redirect(xtc_href_link(FILENAME_DEFAULT));
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
          xtc_db_query("UPDATE " . TABLE_ORDERS_RECLAMATION . " 
                            SET reclamation_status = '" . xtc_db_input($new_status) . "',
                                admin_comment = '" . xtc_db_input($comment) . "',
                                admin_date = NOW()
                          WHERE reclamation_id = '" . $recl_id . "'");
          echo json_encode(array('success' => true, 'message' => 'Status aktualisiert'));
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
        
      case 'stats':
        $stats = array();
        $s_query = xtc_db_query("SELECT reclamation_status, COUNT(*) as cnt FROM " . TABLE_ORDERS_RECLAMATION . " GROUP BY reclamation_status");
        while ($s = xtc_db_fetch_array($s_query)) {
          $stats[$s['reclamation_status']] = (int)$s['cnt'];
        }
        $total = xtc_db_fetch_array(xtc_db_query("SELECT COUNT(*) as cnt FROM " . TABLE_ORDERS_RECLAMATION));
        $stats['total'] = (int)$total['cnt'];
        
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
  $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
  $per_page = 25;
  $offset = ($page - 1) * $per_page;

  // WHERE-Klausel aufbauen
  $where = " WHERE 1=1 ";
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
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Reklamations-Dashboard | <?php echo STORE_NAME; ?></title>
  <?php
    require(DIR_WS_INCLUDES . 'head_css.php');
  ?>
  <style>
    .recl-stat-card { border-radius: 8px; padding: 15px; text-align: center; color: #fff; }
    .recl-stat-card h3 { font-size: 2rem; margin: 0; }
    .recl-stat-card small { opacity: 0.8; }
    .bg-open { background: linear-gradient(135deg, #ffc107, #e0a800); color: #000; }
    .bg-in-progress { background: linear-gradient(135deg, #0dcaf0, #0aa2c0); }
    .bg-resolved { background: linear-gradient(135deg, #198754, #146c43); }
    .bg-rejected { background: linear-gradient(135deg, #dc3545, #b02a37); }
    .bg-total { background: linear-gradient(135deg, #6c757d, #565e64); }
    .status-badge { font-size: 0.8rem; padding: 4px 8px; border-radius: 4px; }
    .clickable-row { cursor: pointer; }
    .clickable-row:hover { background-color: #f8f9fa; }
  </style>
</head>
<body>
  <?php require(DIR_WS_INCLUDES . 'header.php'); ?>

  <div class="container-fluid mt-3">
    <h4><span class="fa-solid fa-triangle-exclamation me-2"></span>Reklamations-Dashboard</h4>
    <hr>

    <!-- Statistik-Karten -->
    <div class="row g-3 mb-4" id="stats-row">
      <div class="col-md-2">
        <div class="recl-stat-card bg-total">
          <h3 id="stat-total"><?php echo $total_records; ?></h3>
          <small>Gesamt</small>
        </div>
      </div>
      <div class="col-md-2">
        <div class="recl-stat-card bg-open">
          <h3 id="stat-open">-</h3>
          <small>Offen</small>
        </div>
      </div>
      <div class="col-md-2">
        <div class="recl-stat-card bg-in-progress">
          <h3 id="stat-in_progress">-</h3>
          <small>In Bearbeitung</small>
        </div>
      </div>
      <div class="col-md-2">
        <div class="recl-stat-card bg-resolved">
          <h3 id="stat-resolved">-</h3>
          <small>Gel&ouml;st</small>
        </div>
      </div>
      <div class="col-md-2">
        <div class="recl-stat-card bg-rejected">
          <h3 id="stat-rejected">-</h3>
          <small>Abgelehnt</small>
        </div>
      </div>
      <div class="col-md-2">
        <div class="recl-stat-card" style="background:linear-gradient(135deg,#6610f2,#520dc2);">
          <h3 id="stat-seed">-</h3>
          <small>Samen-Rekl.</small>
        </div>
      </div>
    </div>

    <!-- Filter -->
    <div class="card mb-3">
      <div class="card-body">
        <form method="get" action="<?php echo xtc_href_link(FILENAME_RECLAMATION_DASHBOARD); ?>" class="row g-2 align-items-end">
          <div class="col-md-3">
            <label class="form-label">Status</label>
            <select name="status" class="form-select form-select-sm">
              <option value="">Alle</option>
              <option value="open" <?php echo ($filter_status == 'open') ? 'selected' : ''; ?>>Offen</option>
              <option value="in_progress" <?php echo ($filter_status == 'in_progress') ? 'selected' : ''; ?>>In Bearbeitung</option>
              <option value="resolved" <?php echo ($filter_status == 'resolved') ? 'selected' : ''; ?>>Gel&ouml;st</option>
              <option value="rejected" <?php echo ($filter_status == 'rejected') ? 'selected' : ''; ?>>Abgelehnt</option>
              <option value="closed" <?php echo ($filter_status == 'closed') ? 'selected' : ''; ?>>Geschlossen</option>
            </select>
          </div>
          <div class="col-md-4">
            <label class="form-label">Suche (Bestell-Nr., Name, E-Mail)</label>
            <input type="text" name="search" class="form-control form-control-sm" value="<?php echo htmlspecialchars($filter_search); ?>" placeholder="Suchbegriff...">
          </div>
          <div class="col-md-2">
            <button type="submit" class="btn btn-primary btn-sm w-100"><span class="fa-solid fa-search me-1"></span>Filtern</button>
          </div>
          <div class="col-md-2">
            <a href="<?php echo xtc_href_link(FILENAME_RECLAMATION_DASHBOARD); ?>" class="btn btn-outline-secondary btn-sm w-100"><span class="fa-solid fa-rotate-left me-1"></span>Zur&uuml;cksetzen</a>
          </div>
        </form>
      </div>
    </div>

    <!-- Reklamations-Liste -->
    <div class="card">
      <div class="card-body p-0">
        <table class="table table-hover table-sm mb-0">
          <thead class="table-dark">
            <tr>
              <th style="width:5%">#</th>
              <th style="width:8%">Best.-Nr.</th>
              <th>Kunde</th>
              <th>E-Mail</th>
              <th style="width:12%">Datum</th>
              <th style="width:8%">Status</th>
              <th style="width:5%" class="text-center">Prod.</th>
              <th style="width:5%" class="text-center">Bilder</th>
              <th style="width:5%" class="text-center">IP</th>
              <th style="width:10%">Aktion</th>
            </tr>
          </thead>
          <tbody>
            <?php if (count($reclamations) > 0): ?>
              <?php foreach ($reclamations as $recl): ?>
                <?php
                  $status_class = '';
                  $status_label = '';
                  switch ($recl['reclamation_status']) {
                    case 'open': $status_class = 'bg-warning text-dark'; $status_label = 'Offen'; break;
                    case 'in_progress': $status_class = 'bg-info text-dark'; $status_label = 'In Bearb.'; break;
                    case 'resolved': $status_class = 'bg-success'; $status_label = 'Gel&ouml;st'; break;
                    case 'rejected': $status_class = 'bg-danger'; $status_label = 'Abgelehnt'; break;
                    case 'closed': $status_class = 'bg-secondary'; $status_label = 'Geschlossen'; break;
                  }
                ?>
                <tr class="clickable-row" onclick="showDetail(<?php echo (int)$recl['reclamation_id']; ?>)">
                  <td><?php echo (int)$recl['reclamation_id']; ?></td>
                  <td><a href="<?php echo xtc_href_link(FILENAME_ORDERS, 'oID=' . (int)$recl['orders_id'] . '&action=edit'); ?>" onclick="event.stopPropagation();"><?php echo (int)$recl['orders_id']; ?></a></td>
                  <td><?php echo htmlspecialchars($recl['customers_name']); ?></td>
                  <td><small><?php echo htmlspecialchars($recl['customers_email']); ?></small></td>
                  <td><small><?php echo xtc_datetime_short($recl['reclamation_date']); ?></small></td>
                  <td><span class="badge <?php echo $status_class; ?> status-badge"><?php echo $status_label; ?></span></td>
                  <td class="text-center"><?php echo (int)$recl['product_count']; ?></td>
                  <td class="text-center"><?php echo (int)$recl['image_count']; ?></td>
                  <td class="text-center"><small><?php echo htmlspecialchars(substr($recl['ip_address'], 0, 15)); ?></small></td>
                  <td>
                    <button class="btn btn-outline-primary btn-sm" onclick="event.stopPropagation(); showDetail(<?php echo (int)$recl['reclamation_id']; ?>);">
                      <span class="fa-solid fa-eye"></span>
                    </button>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php else: ?>
              <tr><td colspan="10" class="text-center text-muted py-4">Keine Reklamationen gefunden.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Pagination -->
    <?php if ($total_pages > 1): ?>
      <nav class="mt-3">
        <ul class="pagination pagination-sm justify-content-center">
          <?php for ($p = 1; $p <= $total_pages; $p++): ?>
            <li class="page-item <?php echo ($p == $page) ? 'active' : ''; ?>">
              <a class="page-link" href="<?php echo xtc_href_link(FILENAME_RECLAMATION_DASHBOARD, 'page=' . $p . ($filter_status ? '&status=' . $filter_status : '') . ($filter_search ? '&search=' . urlencode($filter_search) : '')); ?>"><?php echo $p; ?></a>
            </li>
          <?php endfor; ?>
        </ul>
      </nav>
    <?php endif; ?>
  </div>

  <!-- Detail-Modal -->
  <div class="modal fade" id="detailModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title"><span class="fa-solid fa-triangle-exclamation me-2"></span>Reklamation #<span id="modal-id"></span></h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body" id="modal-body">
          <div class="text-center py-5"><span class="fa-solid fa-spinner fa-spin fa-2x"></span></div>
        </div>
      </div>
    </div>
  </div>

  <?php require(DIR_WS_INCLUDES . 'footer.php'); ?>

  <script>
    // Statistiken laden
    fetch('<?php echo xtc_href_link(FILENAME_RECLAMATION_DASHBOARD, 'ajax=stats'); ?>')
      .then(r => r.json())
      .then(d => {
        if (d.success) {
          var s = d.data;
          document.getElementById('stat-total').textContent = s.total || 0;
          document.getElementById('stat-open').textContent = s.open || 0;
          document.getElementById('stat-in_progress').textContent = s.in_progress || 0;
          document.getElementById('stat-resolved').textContent = s.resolved || 0;
          document.getElementById('stat-rejected').textContent = s.rejected || 0;
          document.getElementById('stat-seed').textContent = s.seed_products || 0;
        }
      });

    function showDetail(id) {
      document.getElementById('modal-id').textContent = id;
      document.getElementById('modal-body').innerHTML = '<div class="text-center py-5"><span class="fa-solid fa-spinner fa-spin fa-2x"></span></div>';
      
      var modal = new bootstrap.Modal(document.getElementById('detailModal'));
      modal.show();
      
      fetch('<?php echo xtc_href_link(FILENAME_RECLAMATION_DASHBOARD, 'ajax=get_detail&id='); ?>' + id)
        .then(r => r.json())
        .then(d => {
          if (d.success) {
            renderDetail(d.data);
          } else {
            document.getElementById('modal-body').innerHTML = '<div class="alert alert-danger">' + d.message + '</div>';
          }
        });
    }

    function renderDetail(data) {
      var html = '';
      
      // Kopf-Infos
      html += '<div class="row mb-3">';
      html += '<div class="col-md-4"><strong>Bestell-Nr.:</strong> ' + data.orders_id + '</div>';
      html += '<div class="col-md-4"><strong>Kunde:</strong> ' + escHtml(data.customers_name) + '</div>';
      html += '<div class="col-md-4"><strong>E-Mail:</strong> ' + escHtml(data.customers_email) + '</div>';
      html += '</div>';
      html += '<div class="row mb-3">';
      html += '<div class="col-md-4"><strong>Datum:</strong> ' + data.reclamation_date + '</div>';
      html += '<div class="col-md-4"><strong>IP:</strong> ' + escHtml(data.ip_address) + '</div>';
      html += '<div class="col-md-4"><strong>Status:</strong> ' + getStatusBadge(data.reclamation_status) + '</div>';
      html += '</div>';
      
      if (data.admin_comment) {
        html += '<div class="alert alert-secondary"><strong>Admin-Kommentar:</strong> ' + escHtml(data.admin_comment) + '</div>';
      }
      
      // Produkte
      html += '<h6 class="mt-3"><span class="fa-solid fa-box me-1"></span>Reklamierte Produkte</h6>';
      html += '<table class="table table-sm table-bordered">';
      html += '<thead class="table-light"><tr><th>Stk.</th><th>Produkt</th><th>Art.-Nr.</th><th>Kategorie</th><th>Grund</th><th>Beschreibung</th></tr></thead><tbody>';
      
      for (var i = 0; i < data.products.length; i++) {
        var p = data.products[i];
        var catBadge = '';
        if (p.product_category == 'seed') catBadge = '<span class="badge bg-warning text-dark">Samen</span>';
        else if (p.product_category == 'plant') catBadge = '<span class="badge bg-success">Pflanze</span>';
        else catBadge = '<span class="badge bg-secondary">Zubeh.</span>';
        
        html += '<tr>';
        html += '<td>' + p.products_quantity + 'x</td>';
        html += '<td>' + escHtml(p.products_name) + '</td>';
        html += '<td>' + escHtml(p.products_model) + '</td>';
        html += '<td>' + catBadge + '</td>';
        html += '<td>' + escHtml(p.reclamation_reason) + '</td>';
        html += '<td><small>' + escHtml(p.reclamation_description || '') + '</small></td>';
        html += '</tr>';
        
        // Samen-Details
        if (p.product_category == 'seed' && p.seed_germination_method) {
          html += '<tr class="table-warning"><td colspan="6" class="small">';
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
        html += '<h6 class="mt-3"><span class="fa-solid fa-images me-1"></span>Hochgeladene Bilder (' + data.images.length + ')</h6>';
        html += '<div class="d-flex flex-wrap gap-2">';
        for (var j = 0; j < data.images.length; j++) {
          var img = data.images[j];
          var imgUrl = '<?php echo HTTP_SERVER . DIR_WS_CATALOG; ?>' + img.image_path;
          html += '<a href="' + imgUrl + '" target="_blank"><img src="' + imgUrl + '" style="max-height:120px;border-radius:4px;border:1px solid #dee2e6;" alt="' + escHtml(img.image_original_name) + '"></a>';
        }
        html += '</div>';
      }
      
      // Status-Aenderung
      if (data.reclamation_status == 'open' || data.reclamation_status == 'in_progress') {
        html += '<hr>';
        html += '<h6><span class="fa-solid fa-pen me-1"></span>Status &auml;ndern</h6>';
        html += '<div class="row g-2">';
        html += '<div class="col-md-3"><select id="modal-status" class="form-select form-select-sm">';
        html += '<option value="open"' + (data.reclamation_status == 'open' ? ' selected' : '') + '>Offen</option>';
        html += '<option value="in_progress"' + (data.reclamation_status == 'in_progress' ? ' selected' : '') + '>In Bearbeitung</option>';
        html += '<option value="resolved">Gel&ouml;st</option>';
        html += '<option value="rejected">Abgelehnt</option>';
        html += '<option value="closed">Geschlossen</option>';
        html += '</select></div>';
        html += '<div class="col-md-6"><textarea id="modal-comment" class="form-control form-control-sm" rows="2" placeholder="Admin-Kommentar...">' + escHtml(data.admin_comment || '') + '</textarea></div>';
        html += '<div class="col-md-3"><button class="btn btn-primary btn-sm w-100" onclick="updateStatus(' + data.reclamation_id + ')"><span class="fa-solid fa-floppy-disk me-1"></span>Speichern</button></div>';
        html += '</div>';
      }
      
      document.getElementById('modal-body').innerHTML = html;
    }

    function updateStatus(id) {
      var status = document.getElementById('modal-status').value;
      var comment = document.getElementById('modal-comment').value;
      
      var fd = new FormData();
      fd.append('reclamation_id', id);
      fd.append('new_status', status);
      fd.append('admin_comment', comment);
      
      fetch('<?php echo xtc_href_link(FILENAME_RECLAMATION_DASHBOARD, 'ajax=update_status'); ?>', {
        method: 'POST',
        body: fd
      })
      .then(r => r.json())
      .then(d => {
        if (d.success) {
          location.reload();
        } else {
          alert('Fehler: ' + d.message);
        }
      });
    }

    function getStatusBadge(status) {
      var map = {
        'open': '<span class="badge bg-warning text-dark">Offen</span>',
        'in_progress': '<span class="badge bg-info text-dark">In Bearbeitung</span>',
        'resolved': '<span class="badge bg-success">Gel&ouml;st</span>',
        'rejected': '<span class="badge bg-danger">Abgelehnt</span>',
        'closed': '<span class="badge bg-secondary">Geschlossen</span>'
      };
      return map[status] || status;
    }

    function escHtml(str) {
      if (!str) return '';
      var div = document.createElement('div');
      div.appendChild(document.createTextNode(str));
      return div.innerHTML;
    }
  </script>
</body>
</html>
<?php require(DIR_WS_INCLUDES . 'application_bottom.php'); ?>
