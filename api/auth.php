<?php
/**
 * Authentication & User Management API Endpoints
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/database.php';

header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'login':
            if ($method !== 'POST') throw new Exception('Method not allowed');
            
            $data = json_decode(file_get_contents('php://input'), true);
            $result = Auth::login(
                $data['username'] ?? '',
                $data['password'] ?? '',
                $data['remember'] ?? false
            );
            echo json_encode(['success' => true, 'data' => $result]);
            break;
            
        case 'register':
            if ($method !== 'POST') throw new Exception('Method not allowed');
            
            $data = json_decode(file_get_contents('php://input'), true);
            $result = Auth::register($data);
            echo json_encode(['success' => true, 'data' => $result]);
            break;
            
        case 'logout':
            Auth::logout();
            echo json_encode(['success' => true, 'message' => 'Logged out successfully']);
            break;
            
        case 'me':
            $user = Auth::requireAuth();
            echo json_encode(['success' => true, 'data' => $user]);
            break;
            
        case 'refresh':
            $user = Auth::requireAuth();
            $token = Auth::generateToken($user);
            $isSecure = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on';
            setcookie('auth_token', $token, [
                'expires' => time() + (24 * 60 * 60),
                'path' => '/',
                'secure' => $isSecure,
                'httponly' => true,
                'samesite' => 'Lax'
            ]);
            echo json_encode(['success' => true, 'token' => $token]);
            break;

        // ==================== USER MANAGEMENT ====================
        case 'users':
            $user = Auth::requireAuth();
            if (!in_array($user['role_name'], ['admin', 'lab_manager'])) {
                http_response_code(403);
                echo json_encode(['success' => false, 'error' => 'Insufficient permissions']);
                break;
            }
            
            if ($method === 'GET') {
                // List all users
                $sql = "SELECT u.id, u.username, u.email, u.first_name, u.last_name,
                               u.phone, u.department, u.position, u.is_active,
                               u.last_login, u.created_at, u.store_id,
                               r.id as role_id, r.name as role_name, r.display_name as role_display,
                               ls.center_name, ls.division_name, ls.section_name, ls.store_name
                        FROM users u
                        JOIN roles r ON u.role_id = r.id
                        LEFT JOIN lab_stores ls ON u.store_id = ls.id
                        ORDER BY r.level DESC, u.first_name ASC";
                
                // Lab managers can only see users in their lab
                if ($user['role_name'] === 'lab_manager') {
                    $sql = "SELECT u.id, u.username, u.email, u.first_name, u.last_name,
                                   u.phone, u.department, u.position, u.is_active,
                                   u.last_login, u.created_at, u.store_id,
                                   r.id as role_id, r.name as role_name, r.display_name as role_display,
                                   ls.center_name, ls.division_name, ls.section_name, ls.store_name
                            FROM users u
                            JOIN roles r ON u.role_id = r.id
                            LEFT JOIN lab_stores ls ON u.store_id = ls.id
                            WHERE u.department = :dept
                            ORDER BY r.level DESC, u.first_name ASC";
                    $users = Database::fetchAll($sql, [':dept' => $user['department']]);
                } else {
                    $users = Database::fetchAll($sql);
                }
                
                // Remove sensitive fields
                foreach ($users as &$u) {
                    unset($u['password_hash'], $u['api_token']);
                }
                
                echo json_encode(['success' => true, 'data' => $users]);
                
            } elseif ($method === 'POST') {
                // Create new user (admin only)
                if ($user['role_name'] !== 'admin') {
                    throw new Exception('Only admins can create users');
                }
                $data = json_decode(file_get_contents('php://input'), true);
                $result = Auth::register($data);
                echo json_encode(['success' => true, 'data' => $result]);
                
            } else {
                throw new Exception('Method not allowed');
            }
            break;

        case 'users_update':
            $user = Auth::requireAuth();
            if ($method !== 'POST') throw new Exception('Method not allowed');
            if (!in_array($user['role_name'], ['admin', 'lab_manager'])) {
                http_response_code(403);
                echo json_encode(['success' => false, 'error' => 'Insufficient permissions']);
                break;
            }
            
            $data = json_decode(file_get_contents('php://input'), true);
            $userId = (int)($data['user_id'] ?? 0);
            if (!$userId) throw new Exception('User ID is required');
            
            // Get target user
            $target = Database::fetch("SELECT u.*, r.name as role_name, r.level as role_level FROM users u JOIN roles r ON u.role_id = r.id WHERE u.id = :id", [':id' => $userId]);
            if (!$target) throw new Exception('User not found');
            
            // Lab managers can only edit users in their lab and lower roles
            if ($user['role_name'] === 'lab_manager') {
                if ($target['lab_id'] != $user['lab_id']) throw new Exception('Cannot edit users outside your lab');
                if ($target['role_level'] >= 3) throw new Exception('Cannot edit users with equal or higher role');
            }
            
            // Prevent editing self role/status (safety)
            $updateData = [];
            if (isset($data['first_name']) && $data['first_name'] !== '') $updateData['first_name'] = $data['first_name'];
            if (isset($data['last_name']) && $data['last_name'] !== '') $updateData['last_name'] = $data['last_name'];
            if (isset($data['email']) && $data['email'] !== '') {
                // Check email uniqueness
                $existing = Database::fetch("SELECT id FROM users WHERE email = :e AND id != :id", [':e' => $data['email'], ':id' => $userId]);
                if ($existing) throw new Exception('Email already in use');
                $updateData['email'] = $data['email'];
            }
            if (isset($data['phone'])) $updateData['phone'] = $data['phone'];
            if (isset($data['department'])) $updateData['department'] = $data['department'];
            if (isset($data['position'])) $updateData['position'] = $data['position'];
            if (isset($data['store_id'])) $updateData['store_id'] = $data['store_id'] ?: null;
            if (isset($data['lab_id'])) $updateData['lab_id'] = $data['lab_id'] ?: null;
            
            // Role change - admin only
            if (isset($data['role_id']) && $user['role_name'] === 'admin') {
                $newRole = Database::fetch("SELECT id, name FROM roles WHERE id = :id", [':id' => (int)$data['role_id']]);
                if ($newRole) {
                    $updateData['role_id'] = $newRole['id'];
                }
            }
            
            // Status change
            if (isset($data['is_active'])) {
                // Cannot deactivate self
                if ($userId === (int)$user['id']) throw new Exception('Cannot change your own status');
                $updateData['is_active'] = $data['is_active'] ? 1 : 0;
            }
            
            // Password reset
            if (!empty($data['password'])) {
                if (strlen($data['password']) < 6) throw new Exception('Password must be at least 6 characters');
                $updateData['password_hash'] = password_hash($data['password'], PASSWORD_DEFAULT);
            }
            
            if (empty($updateData)) throw new Exception('No data to update');
            
            Database::update('users', $updateData, 'id = :id', [':id' => $userId]);
            
            echo json_encode(['success' => true, 'message' => 'User updated successfully']);
            break;

        case 'users_toggle':
            $user = Auth::requireAuth();
            if ($method !== 'POST') throw new Exception('Method not allowed');
            if ($user['role_name'] !== 'admin') {
                http_response_code(403);
                echo json_encode(['success' => false, 'error' => 'Only admins can toggle user status']);
                break;
            }
            
            $data = json_decode(file_get_contents('php://input'), true);
            $userId = (int)($data['user_id'] ?? 0);
            if (!$userId) throw new Exception('User ID is required');
            if ($userId === (int)$user['id']) throw new Exception('Cannot toggle your own status');
            
            $target = Database::fetch("SELECT id, is_active FROM users WHERE id = :id", [':id' => $userId]);
            if (!$target) throw new Exception('User not found');
            
            $newStatus = $target['is_active'] ? 0 : 1;
            Database::update('users', ['is_active' => $newStatus], 'id = :id', [':id' => $userId]);
            
            echo json_encode(['success' => true, 'data' => ['is_active' => $newStatus]]);
            break;

        case 'users_delete':
            $user = Auth::requireAuth();
            if ($method !== 'POST') throw new Exception('Method not allowed');
            if ($user['role_name'] !== 'admin') {
                http_response_code(403);
                echo json_encode(['success' => false, 'error' => 'Only admins can delete users']);
                break;
            }
            
            $data = json_decode(file_get_contents('php://input'), true);
            $userId = (int)($data['user_id'] ?? 0);
            if (!$userId) throw new Exception('User ID is required');
            if ($userId === (int)$user['id']) throw new Exception('Cannot delete yourself');
            
            $target = Database::fetch("SELECT id, username, is_active FROM users WHERE id = :id", [':id' => $userId]);
            if (!$target) throw new Exception('User not found');
            
            // Only allow deleting inactive users
            if ((int)$target['is_active'] === 1) {
                throw new Exception('ต้องปิดใช้งานผู้ใช้ก่อนลบ / Deactivate the user first');
            }
            
            // Check for related data that would block deletion
            $stockCount = (int)Database::fetch(
                "SELECT COUNT(*) as cnt FROM chemical_stock WHERE owner_user_id = :uid",
                [':uid' => $userId]
            )['cnt'];
            
            $borrowCount = (int)Database::fetch(
                "SELECT COUNT(*) as cnt FROM borrow_requests WHERE requester_id = :uid",
                [':uid' => $userId]
            )['cnt'];
            
            $transferCount = (int)Database::fetch(
                "SELECT COUNT(*) as cnt FROM transfers WHERE from_user_id = :uid OR to_user_id = :uid2",
                [':uid' => $userId, ':uid2' => $userId]
            )['cnt'];
            
            if ($stockCount > 0 || $borrowCount > 0 || $transferCount > 0) {
                $details = [];
                if ($stockCount > 0) $details[] = "สารเคมี {$stockCount} รายการ";
                if ($borrowCount > 0) $details[] = "คำขอยืม {$borrowCount} รายการ";
                if ($transferCount > 0) $details[] = "การโอน {$transferCount} รายการ";
                throw new Exception('ไม่สามารถลบได้ ผู้ใช้มีข้อมูลที่เกี่ยวข้อง: ' . implode(', ', $details));
            }
            
            // Safe to hard delete — clean up cascade-able records first
            try {
                Database::query("DELETE FROM user_sessions WHERE user_id = :uid", [':uid' => $userId]);
                Database::query("DELETE FROM notification_settings WHERE user_id = :uid", [':uid' => $userId]);
                Database::query("DELETE FROM alerts WHERE user_id = :uid", [':uid' => $userId]);
                Database::query("DELETE FROM ai_chat_sessions WHERE user_id = :uid", [':uid' => $userId]);
                Database::query("DELETE FROM ar_sessions WHERE user_id = :uid", [':uid' => $userId]);
                Database::query("DELETE FROM visual_searches WHERE user_id = :uid", [':uid' => $userId]);
                Database::query("DELETE FROM model_requests WHERE requested_by = :uid", [':uid' => $userId]);
                Database::query("DELETE FROM users WHERE id = :uid", [':uid' => $userId]);
                
                echo json_encode(['success' => true, 'message' => "ลบผู้ใช้ {$target['username']} เรียบร้อยแล้ว"]);
            } catch (Exception $delEx) {
                throw new Exception('ลบไม่สำเร็จ: ' . $delEx->getMessage());
            }
            break;

        case 'roles':
            $user = Auth::requireAuth();
            $roles = Database::fetchAll("SELECT id, name, display_name, level FROM roles ORDER BY level DESC");
            echo json_encode(['success' => true, 'data' => $roles]);
            break;

        case 'labs_list':
        case 'org_hierarchy':
            $user = Auth::requireAuth();
            $stores = Database::fetchAll(
                "SELECT id, center_name, division_name, section_name, store_name
                 FROM lab_stores WHERE is_active = 1
                 ORDER BY center_name, division_name, section_name, store_name"
            );
            echo json_encode(['success' => true, 'data' => $stores]);
            break;

        case 'locked_users':
            $user = Auth::requireAuth();
            if ($user['role_name'] !== 'admin') {
                http_response_code(403);
                echo json_encode(['success' => false, 'error' => 'Admin only']);
                break;
            }
            $locked = Database::fetchAll(
                "SELECT id, username, first_name, last_name, full_name_th, login_attempts, locked_until
                 FROM users WHERE locked_until IS NOT NULL AND locked_until > NOW()
                 ORDER BY locked_until DESC"
            );
            echo json_encode(['success' => true, 'data' => $locked]);
            break;

        case 'unlock_user':
            $user = Auth::requireAuth();
            if ($method !== 'POST') throw new Exception('Method not allowed');
            if ($user['role_name'] !== 'admin') {
                http_response_code(403);
                echo json_encode(['success' => false, 'error' => 'Admin only']);
                break;
            }
            $data = json_decode(file_get_contents('php://input'), true);
            $uid = (int)($data['user_id'] ?? 0);
            if (!$uid) throw new Exception('User ID is required');
            Database::update('users', ['login_attempts' => 0, 'locked_until' => null], 'id = :id', [':id' => $uid]);
            echo json_encode(['success' => true, 'message' => 'User unlocked']);
            break;
            
        default:
            throw new Exception('Invalid action');
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
