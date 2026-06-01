<?php
/**
 * Mr. Hanf Zoho Desk Integration - AJAX Handler (Standalone)
 * 
 * Wird vom Autoinclude per XHR aufgerufen um Tickets und
 * Konversationen von Zoho Desk abzurufen.
 * 
 * Standalone-Version: Lädt configure.php direkt statt application_top.php
 * um Probleme mit dem Reverse-Proxy zu vermeiden.
 *
 * @version 1.4.0
 * 
 * WICHTIG: Diese Datei liegt im Shop-Root (nicht im Admin-Verzeichnis),
 * weil der Reverse-Proxy alle Requests an admin_... blockiert.
 * 
 * Authentifizierung: HMAC-Token basiert auf Client-Secret + Datum.
 * Das Token wird vom Autoinclude serverseitig generiert und per
 * XHR-Header mitgeschickt.
 * 
 * @author  Mr. Hanf
 */

// Output Buffering starten um ungewollte Ausgaben zu verhindern
ob_start();

// Shop configure.php laden für DB-Zugang
define('_VALID_XTC', true);
$shop_dir = dirname(__FILE__) . '/';

if (file_exists($shop_dir . 'includes/configure.php')) {
    require_once($shop_dir . 'includes/configure.php');
} else {
    ob_end_clean();
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => 'configure.php nicht gefunden']);
    exit;
}

// DB-Verbindung herstellen
$db = new mysqli(DB_SERVER, DB_SERVER_USERNAME, DB_SERVER_PASSWORD, DB_DATABASE);
if ($db->connect_error) {
    ob_end_clean();
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => 'Datenbankverbindung fehlgeschlagen']);
    exit;
}
$db->set_charset('utf8');

// Konfiguration aus DB laden
$zoho_config = [];
$config_keys = [
    'MODULE_ZOHO_DESK_STATUS',
    'MODULE_ZOHO_DESK_CLIENT_ID',
    'MODULE_ZOHO_DESK_CLIENT_SECRET',
    'MODULE_ZOHO_DESK_REFRESH_TOKEN',
    'MODULE_ZOHO_DESK_ORG_ID',
    'MODULE_ZOHO_DESK_FROM_EMAIL',
    'MODULE_ZOHO_DESK_CHANNEL_EMAIL',
];
$result = $db->query("SELECT configuration_key, configuration_value FROM configuration WHERE configuration_key IN ('" . implode("','", $config_keys) . "')");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $zoho_config[$row['configuration_key']] = $row['configuration_value'];
    }
}

// Token-basierte Authentifizierung
// Das Token wird im Autoinclude serverseitig generiert: hash_hmac('sha256', date('Y-m-d'), CLIENT_SECRET)
// Gültig für den aktuellen Tag
$auth_token = isset($_GET['token']) ? $_GET['token'] : (isset($_POST['token']) ? $_POST['token'] : '');
$expected_token = hash_hmac('sha256', date('Y-m-d'), $zoho_config['MODULE_ZOHO_DESK_CLIENT_SECRET'] ?? '');

// Auch Token von gestern akzeptieren (für Requests um Mitternacht)
$expected_token_yesterday = hash_hmac('sha256', date('Y-m-d', strtotime('-1 day')), $zoho_config['MODULE_ZOHO_DESK_CLIENT_SECRET'] ?? '');

if (empty($auth_token) || ($auth_token !== $expected_token && $auth_token !== $expected_token_yesterday)) {
    ob_end_clean();
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => 'Nicht autorisiert.']);
    exit;
}

// Modul aktiv?
if (empty($zoho_config['MODULE_ZOHO_DESK_STATUS']) || $zoho_config['MODULE_ZOHO_DESK_STATUS'] != 'True') {
    ob_end_clean();
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => 'Zoho Desk Modul ist deaktiviert']);
    exit;
}

// Prüfen ob alle Credentials vorhanden sind
if (empty($zoho_config['MODULE_ZOHO_DESK_CLIENT_ID']) || 
    empty($zoho_config['MODULE_ZOHO_DESK_CLIENT_SECRET']) || 
    empty($zoho_config['MODULE_ZOHO_DESK_REFRESH_TOKEN']) || 
    empty($zoho_config['MODULE_ZOHO_DESK_ORG_ID'])) {
    ob_end_clean();
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => 'Zoho Desk ist nicht konfiguriert. Bitte Zugangsdaten im Modul eintragen.']);
    exit;
}

// ZohoDeskApi Klasse laden
$class_file = $shop_dir . 'includes/classes/ZohoDeskApi.php';
if (!file_exists($class_file)) {
    ob_end_clean();
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => 'ZohoDeskApi.php nicht gefunden unter: ' . $class_file]);
    exit;
}
require_once($class_file);

// Zoho API initialisieren mit den Werten aus der DB
$zoho = new ZohoDeskApi(
    $zoho_config['MODULE_ZOHO_DESK_CLIENT_ID'],
    $zoho_config['MODULE_ZOHO_DESK_CLIENT_SECRET'],
    $zoho_config['MODULE_ZOHO_DESK_REFRESH_TOKEN'],
    $zoho_config['MODULE_ZOHO_DESK_ORG_ID']
);

// Bisherige Ausgaben verwerfen
ob_end_clean();

header('Content-Type: application/json; charset=utf-8');
// CORS für Admin-Domain erlauben
header('Access-Control-Allow-Origin: *');

$action = isset($_GET['action']) ? $_GET['action'] : '';

switch ($action) {

    // Tickets abrufen
    case 'tickets':
        $email    = isset($_GET['email'])    ? trim($_GET['email'])    : '';
        $order_id = isset($_GET['order_id']) ? trim($_GET['order_id']) : '';

        if (empty($email)) {
            echo json_encode(['error' => 'Keine E-Mail-Adresse angegeben']);
            exit;
        }

        $tickets = $zoho->getTicketsForOrder($email, $order_id);

        // Ticket-Daten aufbereiten
        $ticket_result = [];
        foreach ($tickets as $ticket) {
            $ticket_result[] = [
                'id'           => $ticket['id'],
                'ticketNumber' => $ticket['ticketNumber'],
                'subject'      => $ticket['subject'],
                'status'       => isset($ticket['status']) ? $ticket['status'] : 'Unbekannt',
                'statusType'   => isset($ticket['statusType']) ? $ticket['statusType'] : 'Open',
                'channel'      => isset($ticket['channel']) ? $ticket['channel'] : '',
                'createdTime'  => isset($ticket['createdTime']) ? $ticket['createdTime'] : '',
                'threadCount'  => isset($ticket['threadCount']) ? $ticket['threadCount'] : '0',
                'webUrl'       => $zoho->getTicketUrl($ticket),
                '_match'       => isset($ticket['_match']) ? $ticket['_match'] : 'email',
            ];
        }

        echo json_encode(['tickets' => $ticket_result]);
        break;

    // Konversationen eines Tickets abrufen
    case 'conversations':
        $ticket_id = isset($_GET['ticket_id']) ? trim($_GET['ticket_id']) : '';

        if (empty($ticket_id)) {
            echo json_encode(['error' => 'Keine Ticket-ID angegeben']);
            exit;
        }

        $conversations_raw = $zoho->getConversations($ticket_id);
        
        // Zoho gibt {data: [...]} zurueck - Array extrahieren
        $conversations = [];
        if (isset($conversations_raw['data']) && is_array($conversations_raw['data'])) {
            $conversations = $conversations_raw['data'];
        } elseif (is_array($conversations_raw) && !isset($conversations_raw['error']) && !isset($conversations_raw['data'])) {
            // Fallback: Direkt als Array (falls Zoho-Format sich aendert)
            $conversations = $conversations_raw;
        }

        // Thread-Daten aufbereiten
        $conv_result = [];
        foreach ($conversations as $thread) {
            // Nur echte Threads (keine Kommentare)
            if (!isset($thread['type']) || $thread['type'] !== 'thread') {
                continue;
            }

            // Vollständigen Thread-Inhalt laden
            $thread_detail = $zoho->getThread($ticket_id, $thread['id']);
            $content = '';
            if (isset($thread_detail['content'])) {
                $content = $thread_detail['content'];
            } elseif (isset($thread['summary'])) {
                $content = $thread['summary'];
            }

            $conv_result[] = [
                'id'               => $thread['id'],
                'type'             => 'thread',
                'direction'        => isset($thread['direction']) ? $thread['direction'] : 'in',
                'fromEmailAddress' => isset($thread_detail['fromEmailAddress']) ? $thread_detail['fromEmailAddress'] : '',
                'to'               => isset($thread_detail['to']) ? $thread_detail['to'] : '',
                'createdTime'      => isset($thread['createdTime']) ? $thread['createdTime'] : '',
                'content'          => $content,
                'summary'          => isset($thread['summary']) ? $thread['summary'] : '',
                'author'           => isset($thread['author']) ? [
                    'name'  => isset($thread['author']['name'])  ? $thread['author']['name']  : '',
                    'email' => isset($thread['author']['email']) ? $thread['author']['email'] : '',
                ] : null,
                'hasAttach'        => isset($thread['hasAttach']) ? $thread['hasAttach'] : false,
                'attachmentCount'  => isset($thread['attachmentCount']) ? $thread['attachmentCount'] : '0',
            ];
        }

        echo json_encode(['conversations' => $conv_result]);
        break;

    // Antwort auf ein Ticket senden (mit optionalen Anhängen)
    case 'reply':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['error' => 'Nur POST-Requests erlaubt']);
            exit;
        }

        $ticket_id = isset($_POST['ticket_id']) ? trim($_POST['ticket_id']) : '';
        $to        = isset($_POST['to'])        ? trim($_POST['to'])        : '';
        $content   = isset($_POST['content'])   ? trim($_POST['content'])   : '';

        if (empty($ticket_id) || empty($to) || empty($content)) {
            echo json_encode(['error' => 'Ticket-ID, Empfaenger und Inhalt sind erforderlich']);
            exit;
        }


        // Content muss HTML sein - wenn kein HTML-Tag vorhanden, in div wrappen
        if (strpos($content, '<') === false || strpos($content, '>') === false) {
            $content = '<div>' . nl2br(htmlspecialchars($content, ENT_QUOTES, 'UTF-8')) . '</div>';
        }

        // Anhänge hochladen falls vorhanden
        $attachmentIds = [];
        if (!empty($_FILES['attachments'])) {
            $files = $_FILES['attachments'];
            $file_count = is_array($files['name']) ? count($files['name']) : 1;
            for ($i = 0; $i < $file_count; $i++) {
                $fname = is_array($files['name']) ? $files['name'][$i] : $files['name'];
                $ftmp  = is_array($files['tmp_name']) ? $files['tmp_name'][$i] : $files['tmp_name'];
                $ftype = is_array($files['type']) ? $files['type'][$i] : $files['type'];
                $ferr  = is_array($files['error']) ? $files['error'][$i] : $files['error'];

                if ($ferr === UPLOAD_ERR_OK && !empty($ftmp)) {
                    $upload_result = $zoho->uploadAttachment($ticket_id, $ftmp, $fname, $ftype);
                    if (isset($upload_result['id'])) {
                        $attachmentIds[] = $upload_result['id'];
                    }
                }
            }
        }

        // fromEmailAddress aus Konfiguration - PFLICHTFELD bei Email-Channel!
        $from_email = '';
        if (!empty($zoho_config['MODULE_ZOHO_DESK_FROM_EMAIL'])) {
            $from_email = $zoho_config['MODULE_ZOHO_DESK_FROM_EMAIL'];
        } elseif (!empty($zoho_config['MODULE_ZOHO_DESK_CHANNEL_EMAIL'])) {
            $from_email = $zoho_config['MODULE_ZOHO_DESK_CHANNEL_EMAIL'];
        } else {
            $from_email = 'info@mr-hanf.de'; // Fallback
        }

        $reply_result = $zoho->sendReply($ticket_id, $to, $content, $from_email, $attachmentIds);

        if (isset($reply_result['error'])) {
            echo json_encode(['error' => $reply_result['error']]);
        } else {
            echo json_encode([
                'success' => true,
                'message' => 'Antwort erfolgreich gesendet',
                'thread'  => isset($reply_result['id']) ? $reply_result['id'] : null,
            ]);
        }
        break;

    // Anhänge eines Threads abrufen
    case 'attachments':
        $ticket_id = isset($_GET['ticket_id']) ? trim($_GET['ticket_id']) : '';
        $thread_id = isset($_GET['thread_id']) ? trim($_GET['thread_id']) : '';

        if (empty($ticket_id) || empty($thread_id)) {
            echo json_encode(['error' => 'Ticket-ID und Thread-ID erforderlich']);
            exit;
        }

        $attachments = $zoho->getThreadAttachments($ticket_id, $thread_id);
        echo json_encode(['attachments' => $attachments]);
        break;

    // Ticket-Status ändern
    case 'status':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['error' => 'Nur POST-Requests erlaubt']);
            exit;
        }

        $ticket_id = isset($_POST['ticket_id']) ? trim($_POST['ticket_id']) : '';
        $status    = isset($_POST['status'])    ? trim($_POST['status'])    : '';

        if (empty($ticket_id) || empty($status)) {
            echo json_encode(['error' => 'Ticket-ID und Status erforderlich']);
            exit;
        }

        $status_result = $zoho->updateTicketStatus($ticket_id, $status);

        if (isset($status_result['error'])) {
            echo json_encode(['error' => $status_result['error']]);
        } else {
            echo json_encode([
                'success' => true,
                'message' => 'Status geaendert',
                'status'  => isset($status_result['status']) ? $status_result['status'] : $status,
            ]);
        }
        break;

    // Neues Ticket erstellen (fuer Reklamations-Dashboard)
    case 'create_ticket':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['error' => 'Nur POST-Requests erlaubt']);
            exit;
        }
        $subject       = isset($_POST['subject'])       ? trim($_POST['subject'])       : '';
        $description   = isset($_POST['description'])   ? trim($_POST['description'])   : '';
        $email         = isset($_POST['email'])         ? trim($_POST['email'])         : '';
        $contact_name  = isset($_POST['contact_name'])  ? trim($_POST['contact_name'])  : '';
        $department_id = isset($_POST['department_id']) ? trim($_POST['department_id']) : '';

        if (empty($subject) || empty($description) || empty($email)) {
            echo json_encode(['error' => 'Betreff, Beschreibung und E-Mail sind erforderlich']);
            exit;
        }

        // Description muss HTML sein
        if (strpos($description, '<') === false || strpos($description, '>') === false) {
            $description = '<div>' . nl2br(htmlspecialchars($description, ENT_QUOTES, 'UTF-8')) . '</div>';
        }

        $ticket_data = [
            'subject'     => $subject,
            'description' => $description,
            'status'      => 'Open',
            'priority'    => 'Medium',
            'channel'     => 'Email',
        ];

        // Contact suchen oder erstellen
        if (!empty($email)) {
            $contact = $zoho->searchContactByEmail($email);
            if (!empty($contact) && isset($contact[0]['id'])) {
                $ticket_data['contactId'] = $contact[0]['id'];
            } else {
                // Kein Contact gefunden - lastName ist Pflichtfeld bei Zoho
                $last_name = !empty($contact_name) ? $contact_name : explode('@', $email)[0];
                $ticket_data['contact'] = [
                    'lastName' => $last_name,
                    'email'    => $email,
                ];
            }
        }

        // Department setzen (Pflichtfeld!)
        if (!empty($department_id)) {
            $ticket_data['departmentId'] = $department_id;
        } else {
            $departments = $zoho->getDepartments();
            if (!empty($departments) && isset($departments[0]['id'])) {
                $ticket_data['departmentId'] = $departments[0]['id'];
            }
        }

        $create_result = $zoho->createTicket($ticket_data);
        if (isset($create_result['error'])) {
            echo json_encode(['error' => $create_result['error']]);
        } elseif (isset($create_result['id'])) {
            echo json_encode([
                'success'   => true,
                'ticket_id' => $create_result['id'],
                'ticket_nr' => isset($create_result['ticketNumber']) ? $create_result['ticketNumber'] : '',
                'web_url'   => $zoho->getTicketUrl($create_result),
                'message'   => 'Ticket #' . (isset($create_result['ticketNumber']) ? $create_result['ticketNumber'] : $create_result['id']) . ' erfolgreich erstellt',
            ]);
        } else {
            echo json_encode(['error' => 'Unerwartete Antwort von Zoho: ' . json_encode($create_result)]);
        }
        break;

    // Departments auflisten
    case 'departments':
        $departments = $zoho->getDepartments();
        echo json_encode(['departments' => $departments]);
        break;

    default:
        echo json_encode(['error' => 'Unbekannte Aktion: ' . $action]);
        break;
}

$db->close();
