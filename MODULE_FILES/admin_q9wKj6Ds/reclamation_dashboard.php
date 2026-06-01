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
    .modal-backdrop { position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 1000; display: none; }
    .modal-backdrop.show { display: flex; align-items: center; justify-content: center; }
    .modal-box { background: #fff; border-radius: 12px; max-width: 900px; width: 95%; max-height: 90vh; overflow-y: auto; box-shadow: 0 20px 60px rgba(0,0,0,0.3); }
    .modal-box .modal-header { padding: 1rem 1.5rem; border-bottom: 1px solid #eee; display: flex; justify-content: space-between; align-items: center; }
    .modal-box .modal-header h5 { margin: 0; font-size: 1.1rem; color: #c0392b; }
    .modal-box .modal-header .close-btn { background: none; border: none; font-size: 1.5rem; cursor: pointer; color: #999; }
    .modal-box .modal-header .close-btn:hover { color: #333; }
    .modal-box .modal-body { padding: 1.5rem; }
    
    .content-wrap { max-width: 1400px; margin: 0 auto; padding: 1.5rem 2rem; }
    
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

    <!-- Reklamations-Liste -->
    <div class="mrh-card">
      <div class="card-header"><i class="fa-solid fa-list"></i> Reklamationen (<?php echo $total_records; ?>)</div>
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
              <th style="width:5%; text-align:center;">Prod.</th>
              <th style="width:5%; text-align:center;">Bilder</th>
              <th style="width:10%">IP</th>
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
                  <td style="text-align:center;"><?php echo (int)$recl['product_count']; ?></td>
                  <td style="text-align:center;"><?php echo (int)$recl['image_count']; ?></td>
                  <td><small><?php echo htmlspecialchars(substr($recl['ip_address'], 0, 15)); ?></small></td>
                  <td>
                    <button class="btn-mrh" style="padding:0.3rem 0.6rem; font-size:0.75rem;" onclick="event.stopPropagation(); showDetail(<?php echo (int)$recl['reclamation_id']; ?>);">
                      <i class="fa-solid fa-eye"></i> Details
                    </button>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php else: ?>
              <tr><td colspan="10" style="text-align:center; padding:2rem; color:#999;">Keine Reklamationen gefunden.</td></tr>
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
  <div class="modal-backdrop" id="detailModal">
    <div class="modal-box">
      <div class="modal-header">
        <h5><i class="fa-solid fa-triangle-exclamation"></i> Reklamation #<span id="modal-id"></span></h5>
        <button class="close-btn" onclick="closeModal()">&times;</button>
      </div>
      <div class="modal-body" id="modal-body">
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
        }
      });

    // Modal
    function showDetail(id) {
      document.getElementById('modal-id').textContent = id;
      document.getElementById('modal-body').innerHTML = '<div style="text-align:center; padding:2rem;"><i class="fa-solid fa-spinner fa-spin fa-2x" style="color:#c0392b;"></i></div>';
      document.getElementById('detailModal').classList.add('show');
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
      document.getElementById('detailModal').classList.remove('show');
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
      
      document.getElementById('modal-body').innerHTML = html;
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

    function escHtml(str) {
      if (!str) return '';
      var div = document.createElement('div');
      div.appendChild(document.createTextNode(str));
      return div.innerHTML;
    }
  </script>

</body>
</html>
