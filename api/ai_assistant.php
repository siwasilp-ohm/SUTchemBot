<?php
/**
 * AI Assistant API - Chatbot, Search, Predictions
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/database.php';

header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];
$user = Auth::requireAuth();

try {
    switch ($method) {
        case 'GET':
            if (isset($_GET['action']) && $_GET['action'] === 'sessions') {
                $sessions = getChatSessions($user['id']);
                echo json_encode(['success' => true, 'data' => $sessions]);
            } elseif (isset($_GET['session_id'])) {
                $messages = getChatMessages($_GET['session_id'], $user['id']);
                echo json_encode(['success' => true, 'data' => $messages]);
            } elseif (isset($_GET['suggest'])) {
                $suggestions = getSmartSuggestions($user);
                echo json_encode(['success' => true, 'data' => $suggestions]);
            } else {
                echo json_encode(['success' => false, 'error' => 'Invalid action']);
            }
            break;
            
        case 'POST':
            $data = json_decode(file_get_contents('php://input'), true);
            $action = $data['action'] ?? 'chat';
            
            switch ($action) {
                case 'chat':
                    $response = processChatMessage($data, $user);
                    echo json_encode(['success' => true, 'data' => $response]);
                    break;
                case 'search':
                    $results = smartSearch($data['query'], $user);
                    echo json_encode(['success' => true, 'data' => $results]);
                    break;
                case 'visual_search':
                    $results = visualSearch($data, $user);
                    echo json_encode(['success' => true, 'data' => $results]);
                    break;
                case 'predict_usage':
                    $prediction = predictUsage($data['chemical_id'], $data['lab_id'] ?? $user['lab_id']);
                    echo json_encode(['success' => true, 'data' => $prediction]);
                    break;
                default:
                    throw new Exception('Invalid action');
            }
            break;
            
        case 'DELETE':
            if (isset($_GET['session_id'])) {
                deleteChatSession($_GET['session_id'], $user['id']);
                echo json_encode(['success' => true, 'message' => 'Session deleted']);
            }
            break;
            
        default:
            throw new Exception('Method not allowed');
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

function processChatMessage(array $data, array $user): array {
    $message = $data['message'] ?? '';
    $sessionId = $data['session_id'] ?? null;
    
    if (!$sessionId) {
        $sessionId = 'chat_' . time() . '_' . bin2hex(random_bytes(4));
        Database::insert('ai_chat_sessions', [
            'user_id' => $user['id'],
            'session_id' => $sessionId,
            'title' => substr($message, 0, 50)
        ]);
    }
    
    // Store user message
    Database::insert('ai_chat_messages', [
        'session_id' => $sessionId,
        'role' => 'user',
        'content' => $message
    ]);
    
    // Process message and generate response
    $response = generateAIResponse($message, $user);
    
    // Store AI response
    Database::insert('ai_chat_messages', [
        'session_id' => $sessionId,
        'role' => 'assistant',
        'content' => $response['text'],
        'referenced_chemicals' => json_encode($response['chemicals'] ?? []),
        'referenced_containers' => json_encode($response['containers'] ?? [])
    ]);
    
    return [
        'session_id' => $sessionId,
        'response' => $response['text'],
        'chemicals' => $response['chemicals'] ?? [],
        'containers' => $response['containers'] ?? [],
        'actions' => $response['actions'] ?? []
    ];
}

function generateAIResponse(string $message, array $user): array {
    $message = strtolower($message);
    $response = ['text' => '', 'chemicals' => [], 'containers' => [], 'actions' => []];
    
    // Intent detection patterns
    $patterns = [
        'location' => '/where is|find|location|อยู่ที่ไหน|หา/i',
        'sds' => '/sds|safety data sheet|ข้อมูลความปลอดภัย/i',
        'expiry' => '/expire|หมดอายุ|expiring/i',
        'stock' => '/stock|quantity|how many|เหลือเท่าไหร่|จำนวน/i',
        'borrow' => '/borrow|ยืม|request/i',
        'hazard' => '/hazard|dangerous|อันตราย|safety/i',
        'usage' => '/usage|ใช้|consumption/i',
        'reorder' => '/reorder|สั่งซื้อ|purchase/i',
    ];
    
    $intent = null;
    foreach ($patterns as $type => $pattern) {
        if (preg_match($pattern, $message)) {
            $intent = $type;
            break;
        }
    }
    
    // Extract chemical name from message
    $chemicalName = extractChemicalName($message);
    
    switch ($intent) {
        case 'location':
            if ($chemicalName) {
                $chemical = Database::fetch(
                    "SELECT * FROM chemicals WHERE name LIKE :name OR cas_number = :cas LIMIT 1",
                    [':name' => "%$chemicalName%", ':cas' => $chemicalName]
                );
                
                if ($chemical) {
                    $containers = Database::fetchAll(
                        "SELECT cn.*, sl.name as slot, sh.name as shelf, 
                                cab.name as cabinet, r.name as room, b.name as building
                         FROM containers cn
                         LEFT JOIN slots sl ON cn.location_slot_id = sl.id
                         LEFT JOIN shelves sh ON sl.shelf_id = sh.id
                         LEFT JOIN cabinets cab ON sh.cabinet_id = cab.id
                         LEFT JOIN rooms r ON cab.room_id = r.id
                         LEFT JOIN buildings b ON r.building_id = b.id
                         WHERE cn.chemical_id = :chem_id AND cn.status = 'active'",
                        [':chem_id' => $chemical['id']]
                    );
                    
                    $response['text'] = "Found {$chemical['name']} (CAS: {$chemical['cas_number']})\n\n";
                    foreach ($containers as $c) {
                        $response['text'] .= "📍 Location: {$c['building']} > {$c['room']} > {$c['cabinet']} > {$c['shelf']} > {$c['slot']}\n";
                        $response['text'] .= "   Quantity: {$c['current_quantity']} {$c['quantity_unit']}\n\n";
                    }
                    $response['chemicals'] = [$chemical];
                    $response['containers'] = $containers;
                    $response['actions'] = [['type' => 'navigate', 'label' => 'Show on Map']];
                } else {
                    $response['text'] = "Sorry, I couldn't find a chemical matching '{$chemicalName}' in the system.";
                }
            } else {
                $response['text'] = "Please specify which chemical you're looking for. For example: 'Where is HCl?' or 'Find ethanol'";
            }
            break;
            
        case 'sds':
            if ($chemicalName) {
                $chemical = Database::fetch(
                    "SELECT name, cas_number, sds_url, sds_pdf_path, ghs_classifications, 
                            hazard_statements, precautionary_statements, first_aid_measures
                     FROM chemicals WHERE name LIKE :name OR cas_number = :cas LIMIT 1",
                    [':name' => "%$chemicalName%", ':cas' => $chemicalName]
                );
                
                if ($chemical) {
                    $response['text'] = "Safety Information for {$chemical['name']} (CAS: {$chemical['cas_number']}):\n\n";
                    $response['text'] .= "⚠️ Hazards: " . implode(', ', json_decode($chemical['hazard_statements'] ?? '[]', true)) . "\n\n";
                    $response['text'] .= "🛡️ Precautions: " . implode(', ', json_decode($chemical['precautionary_statements'] ?? '[]', true)) . "\n\n";
                    if ($chemical['sds_url']) {
                        $response['text'] .= "📄 Full SDS: {$chemical['sds_url']}";
                    }
                    $response['chemicals'] = [$chemical];
                    $response['actions'] = [['type' => 'view_sds', 'label' => 'View Full SDS']];
                } else {
                    $response['text'] = "Chemical not found. Please check the name or CAS number.";
                }
            } else {
                $response['text'] = "Please specify which chemical's SDS you need.";
            }
            break;
            
        case 'expiry':
            $expiring = Database::fetchAll(
                "SELECT c.name, cn.expiry_date, cn.current_quantity, cn.quantity_unit,
                        DATEDIFF(cn.expiry_date, CURDATE()) as days_left
                 FROM containers cn
                 JOIN chemicals c ON cn.chemical_id = c.id
                 WHERE cn.status = 'active' AND cn.expiry_date IS NOT NULL
                 AND cn.expiry_date <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)
                 AND cn.lab_id = :lab_id
                 ORDER BY cn.expiry_date ASC
                 LIMIT 10",
                [':lab_id' => $user['lab_id']]
            );
            
            if ($expiring) {
                $response['text'] = "⚠️ Chemicals Expiring Soon:\n\n";
                foreach ($expiring as $e) {
                    $response['text'] .= "• {$e['name']}: {$e['days_left']} days left ({$e['current_quantity']} {$e['quantity_unit']})\n";
                }
            } else {
                $response['text'] = "✅ No chemicals expiring in the next 30 days!";
            }
            break;
            
        case 'stock':
            if ($chemicalName) {
                $stock = Database::fetch(
                    "SELECT c.name, SUM(cn.current_quantity) as total, cn.quantity_unit
                     FROM containers cn
                     JOIN chemicals c ON cn.chemical_id = c.id
                     WHERE (c.name LIKE :name OR c.cas_number = :cas) AND cn.status = 'active'
                     GROUP BY c.id",
                    [':name' => "%$chemicalName%", ':cas' => $chemicalName]
                );
                
                if ($stock) {
                    $response['text'] = "📦 Stock for {$stock['name']}: {$stock['total']} {$stock['quantity_unit']}";
                } else {
                    $response['text'] = "No stock found for '{$chemicalName}'";
                }
            } else {
                // Show low stock summary
                $lowStock = Database::fetchAll(
                    "SELECT c.name, AVG(cn.remaining_percentage) as avg_remaining
                     FROM containers cn
                     JOIN chemicals c ON cn.chemical_id = c.id
                     WHERE cn.status = 'active' AND cn.lab_id = :lab_id
                     GROUP BY c.id
                     HAVING avg_remaining <= 25
                     ORDER BY avg_remaining ASC
                     LIMIT 10",
                    [':lab_id' => $user['lab_id']]
                );
                
                $response['text'] = "📉 Low Stock Alert:\n\n";
                foreach ($lowStock as $item) {
                    $response['text'] .= "• {$item['name']}: " . round($item['avg_remaining'], 1) . "% remaining\n";
                }
            }
            break;
            
        case 'borrow':
            $response['text'] = "To borrow a chemical:\n\n";
            $response['text'] .= "1. Search for the chemical you need\n";
            $response['text'] .= "2. Click 'Request to Borrow'\n";
            $response['text'] .= "3. Specify quantity and purpose\n";
            $response['text'] .= "4. Wait for approval from the owner/lab manager\n\n";
            $response['text'] .= "Or tell me which chemical you need and I can help you find it!";
            $response['actions'] = [['type' => 'search_chemical', 'label' => 'Search Chemicals']];
            break;
            
        default:
            // General response
            $response['text'] = "I'm your Chemical Inventory Assistant! I can help you with:\n\n";
            $response['text'] .= "🔍 Find chemicals and their locations\n";
            $response['text'] .= "📄 View Safety Data Sheets (SDS)\n";
            $response['text'] .= "⚠️ Check expiry dates and low stock\n";
            $response['text'] .= "📦 Request to borrow chemicals\n";
            $response['text'] .= "🛡️ Get safety information\n\n";
            $response['text'] .= "What would you like to know?";
    }
    
    return $response;
}

function extractChemicalName(string $message): ?string {
    // Common patterns to extract chemical names
    $patterns = [
        '/(?:find|where is|location of|stock of)\s+(.+)/i',
        '/(.+?)\s+(?:sds|safety|hazard|expiry|stock)/i',
    ];
    
    foreach ($patterns as $pattern) {
        if (preg_match($pattern, $message, $matches)) {
            return trim($matches[1]);
        }
    }
    
    return null;
}

function smartSearch(string $query, array $user): array {
    $results = [
        'chemicals' => [],
        'containers' => [],
        'locations' => []
    ];
    
    // Search chemicals
    $results['chemicals'] = Database::fetchAll(
        "SELECT id, name, cas_number, molecular_formula, physical_state
         FROM chemicals 
         WHERE (name LIKE :q OR cas_number LIKE :q OR synonyms LIKE :q)
         AND is_active = 1
         LIMIT 10",
        [':q' => "%$query%"]
    );
    
    // Search containers
    $results['containers'] = Database::fetchAll(
        "SELECT cn.id, cn.qr_code, c.name as chemical_name, cn.current_quantity, 
                cn.quantity_unit, cn.status, l.name as location
         FROM containers cn
         JOIN chemicals c ON cn.chemical_id = c.id
         LEFT JOIN slots sl ON cn.location_slot_id = sl.id
         LEFT JOIN shelves sh ON sl.shelf_id = sh.id
         LEFT JOIN cabinets cab ON sh.cabinet_id = cab.id
         LEFT JOIN rooms r ON cab.room_id = r.id
         LEFT JOIN buildings b ON r.building_id = b.id
         WHERE (c.name LIKE :q OR cn.qr_code = :exact)
         AND cn.status = 'active'
         LIMIT 10",
        [':q' => "%$query%", ':exact' => $query]
    );
    
    return $results;
}

function visualSearch(array $data, array $user): array {
    // This would integrate with Google Vision API or similar
    // For now, return placeholder
    return [
        'detected_text' => [],
        'detected_chemicals' => [],
        'message' => 'Visual search feature requires image processing API configuration'
    ];
}

function predictUsage(int $chemicalId, int $labId): array {
    // Get historical usage
    $history = Database::fetchAll(
        "SELECT DATE_FORMAT(ch.created_at, '%Y-%m') as month,
                SUM(ABS(ch.quantity_change)) as total_used
         FROM container_history ch
         JOIN containers cn ON ch.container_id = cn.id
         WHERE cn.chemical_id = :chem_id AND cn.lab_id = :lab_id
         AND ch.action_type = 'used'
         AND ch.created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
         GROUP BY month
         ORDER BY month ASC",
        [':chem_id' => $chemicalId, ':lab_id' => $labId]
    );
    
    // Simple linear prediction
    $predictedUsage = 0;
    if (count($history) >= 3) {
        $values = array_column($history, 'total_used');
        $trend = ($values[count($values) - 1] - $values[0]) / count($values);
        $predictedUsage = max(0, end($values) + $trend);
    }
    
    // Get current stock
    $currentStock = Database::fetch(
        "SELECT SUM(current_quantity) as total FROM containers 
         WHERE chemical_id = :chem_id AND lab_id = :lab_id AND status = 'active'",
        [':chem_id' => $chemicalId, ':lab_id' => $labId]
    )['total'] ?? 0;
    
    return [
        'historical_usage' => $history,
        'predicted_next_month' => round($predictedUsage, 2),
        'current_stock' => $currentStock,
        'reorder_recommended' => $currentStock < $predictedUsage * 2,
        'confidence' => count($history) >= 3 ? 'medium' : 'low'
    ];
}

function getChatSessions(int $userId): array {
    return Database::fetchAll(
        "SELECT * FROM ai_chat_sessions WHERE user_id = :user_id ORDER BY updated_at DESC",
        [':user_id' => $userId]
    );
}

function getChatMessages(string $sessionId, int $userId): array {
    // Verify ownership
    $session = Database::fetch(
        "SELECT * FROM ai_chat_sessions WHERE session_id = :session_id AND user_id = :user_id",
        [':session_id' => $sessionId, ':user_id' => $userId]
    );
    
    if (!$session) {
        throw new Exception('Session not found');
    }
    
    return Database::fetchAll(
        "SELECT * FROM ai_chat_messages WHERE session_id = :session_id ORDER BY created_at ASC",
        [':session_id' => $sessionId]
    );
}

function deleteChatSession(string $sessionId, int $userId): void {
    Database::delete(
        'ai_chat_sessions',
        'session_id = :session_id AND user_id = :user_id',
        [':session_id' => $sessionId, ':user_id' => $userId]
    );
}

function getSmartSuggestions(array $user): array {
    $suggestions = [];
    
    // Check for expiring chemicals
    $expiringCount = Database::fetch(
        "SELECT COUNT(*) as count FROM containers 
         WHERE lab_id = :lab_id AND status = 'active' 
         AND expiry_date IS NOT NULL AND expiry_date <= DATE_ADD(CURDATE(), INTERVAL 7 DAY)",
        [':lab_id' => $user['lab_id']]
    )['count'];
    
    if ($expiringCount > 0) {
        $suggestions[] = [
            'type' => 'warning',
            'message' => "{$expiringCount} chemicals expiring this week",
            'action' => 'view_expiring'
        ];
    }
    
    // Check for overdue borrows
    $overdueCount = Database::fetch(
        "SELECT COUNT(*) as count FROM borrow_requests br
         JOIN users u ON br.requester_id = u.id
         WHERE u.lab_id = :lab_id AND br.status IN ('fulfilled', 'partially_returned')
         AND br.expected_return_date < CURDATE()",
        [':lab_id' => $user['lab_id']]
    )['count'];
    
    if ($overdueCount > 0) {
        $suggestions[] = [
            'type' => 'alert',
            'message' => "{$overdueCount} overdue borrow requests",
            'action' => 'view_overdue'
        ];
    }
    
    // Suggest chemicals to reorder
    $lowStock = Database::fetchAll(
        "SELECT c.name, AVG(cn.remaining_percentage) as avg_remaining
         FROM containers cn
         JOIN chemicals c ON cn.chemical_id = c.id
         WHERE cn.lab_id = :lab_id AND cn.status = 'active'
         GROUP BY c.id
         HAVING avg_remaining <= 20
         LIMIT 3",
        [':lab_id' => $user['lab_id']]
    );
    
    foreach ($lowStock as $item) {
        $suggestions[] = [
            'type' => 'info',
            'message' => "{$item['name']} is running low (" . round($item['avg_remaining'], 0) . "%)",
            'action' => 'reorder'
        ];
    }
    
    return $suggestions;
}
