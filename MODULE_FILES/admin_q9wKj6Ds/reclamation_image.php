<?php
/* -----------------------------------------------------------------------------------------
   MRH 2026: Reklamationsbilder-Proxy (Admin-only)
   
   Stellt Bilder aus /images/reclamation/ nur fuer eingeloggte Admins bereit.
   Das Verzeichnis ist per .htaccess gesperrt, dieser Proxy liest direkt vom Dateisystem.
   
   Aufruf: reclamation_image.php?file=images/reclamation/123/foto.jpg
   ---------------------------------------------------------------------------------------*/

  require('includes/application_top.php');

  // Admin-Session pruefen
  if (!isset($_SESSION['customer_id']) || !isset($_SESSION['customers_status'])) {
    header('HTTP/1.1 403 Forbidden');
    exit('Zugriff verweigert');
  }

  // Datei-Parameter pruefen
  $file = isset($_GET['file']) ? trim($_GET['file']) : '';
  if (empty($file)) {
    header('HTTP/1.1 400 Bad Request');
    exit('Kein Dateiparameter');
  }

  // Sicherheit: Nur Dateien aus images/reclamation/ erlauben
  // Path-Traversal verhindern
  $file = str_replace('..', '', $file);
  $file = str_replace("\0", '', $file);
  
  // Sicherstellen dass der Pfad mit images/reclamation/ beginnt
  if (strpos($file, 'images/reclamation/') !== 0) {
    header('HTTP/1.1 403 Forbidden');
    exit('Ungueltiger Pfad');
  }

  // Vollstaendigen Dateipfad zusammenbauen
  $full_path = DIR_FS_CATALOG . $file;

  // Pruefen ob Datei existiert
  if (!file_exists($full_path) || !is_file($full_path)) {
    header('HTTP/1.1 404 Not Found');
    exit('Datei nicht gefunden');
  }

  // Content-Type anhand der Dateiendung bestimmen
  $ext = strtolower(pathinfo($full_path, PATHINFO_EXTENSION));
  $mime_types = array(
    'jpg'  => 'image/jpeg',
    'jpeg' => 'image/jpeg',
    'png'  => 'image/png',
    'gif'  => 'image/gif',
    'webp' => 'image/webp',
    'bmp'  => 'image/bmp',
  );

  $content_type = isset($mime_types[$ext]) ? $mime_types[$ext] : 'application/octet-stream';

  // Nur Bildformate erlauben
  if (!isset($mime_types[$ext])) {
    header('HTTP/1.1 403 Forbidden');
    exit('Nur Bilddateien erlaubt');
  }

  // Bild ausliefern
  header('Content-Type: ' . $content_type);
  header('Content-Length: ' . filesize($full_path));
  header('Cache-Control: private, max-age=3600');
  header('Content-Disposition: inline; filename="' . basename($full_path) . '"');
  
  readfile($full_path);
  exit;
