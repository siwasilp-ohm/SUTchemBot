<?php
/**
 * Import Users from CSV (v1/data/user.csv) → Database
 * 
 * Maps CSV columns:
 *   id, ชื่อ นามสกุล, user, password, email, phone, ศูนย์, ฝ่าย, งาน
 * 
 * Role mapping based on username prefix:
 *   admin* → admin (level 5)
 *   ceo*   → ceo (level 4)
 *   lab*   → lab_manager (level 3)
 *   user*  → user (level 2)
 * 
 * Usage: php import_users.php [--dry-run] [--force]
 */

require_once __DIR__ . '/../includes/config.php';

// ═══ Config ═══
$csvFile = __DIR__ . '/../data/user.csv';
$dryRun = in_array('--dry-run', $argv ?? []);
$force  = in_array('--force', $argv ?? []);

echo "╔══════════════════════════════════════════════════════╗\n";
echo "║   Import Users from CSV → Database                  ║\n";
echo "╚══════════════════════════════════════════════════════╝\n\n";

if ($dryRun) echo "🔍 DRY RUN MODE — No changes will be made\n\n";

// ═══ Connect ═══
try {
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
    $db = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
    ]);
    echo "✅ Database connected: " . DB_NAME . "\n";
} catch (PDOException $e) {
    die("❌ Database connection failed: " . $e->getMessage() . "\n");
}

// ═══ Ensure columns exist ═══
echo "\n📋 Checking schema...\n";
$checkCols = [
    'full_name_th' => "ALTER TABLE users ADD COLUMN full_name_th VARCHAR(255) DEFAULT NULL AFTER last_name",
    'department_id' => "ALTER TABLE users ADD COLUMN department_id INT DEFAULT NULL AFTER department"
];
foreach ($checkCols as $col => $sql) {
    $exists = $db->query("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA='" . DB_NAME . "' AND TABLE_NAME='users' AND COLUMN_NAME='$col'")->fetchColumn();
    if (!$exists) {
        if (!$dryRun) {
            $db->exec($sql);
            echo "   ✅ Added column: $col\n";
        } else {
            echo "   🔍 Would add column: $col\n";
        }
    } else {
        echo "   ✓ Column exists: $col\n";
    }
}

// ═══ Load Roles ═══
$roles = $db->query("SELECT name, id FROM roles")->fetchAll(PDO::FETCH_KEY_PAIR);
echo "\n📋 Roles loaded: " . implode(', ', array_map(fn($n, $id) => "$n(#$id)", array_keys($roles), $roles)) . "\n";

// ═══ Load Departments (level 3 = งาน) ═══
$departments = $db->query("SELECT name, id FROM departments WHERE level = 3")->fetchAll(PDO::FETCH_KEY_PAIR);
echo "📋 Departments (งาน) loaded: " . count($departments) . " items\n";

// Also load level 2 (ฝ่าย) for matching
$divisions = $db->query("SELECT name, id FROM departments WHERE level = 2")->fetchAll(PDO::FETCH_KEY_PAIR);

// ═══ Load Labs ═══
$labs = $db->query("SELECT name, id FROM labs WHERE is_active = 1")->fetchAll(PDO::FETCH_KEY_PAIR);

// ═══ Read CSV ═══
if (!file_exists($csvFile)) {
    die("❌ CSV file not found: $csvFile\n");
}

$handle = fopen($csvFile, 'r');
if (!$handle) die("❌ Cannot open CSV file\n");

// Read header
$header = fgetcsv($handle);
if (!$header) die("❌ Empty CSV file\n");

// Clean BOM from first column
$header[0] = preg_replace('/^\x{FEFF}/u', '', $header[0]);
echo "\n📄 CSV Headers: " . implode(' | ', $header) . "\n";

// Map header indices
$colMap = [
    'id'    => 0,
    'name'  => 1, // ชื่อ นามสกุล
    'user'  => 2,
    'pass'  => 3,
    'email' => 4,
    'phone' => 5,
    'center'=> 6, // ศูนย์
    'div'   => 7, // ฝ่าย
    'unit'  => 8  // งาน
];

// ═══ Password Hash ═══
$defaultHash = password_hash('123', PASSWORD_DEFAULT);

// ═══ Role Mapping ═══
function mapRole(string $username): string {
    if (preg_match('/^admin/i', $username)) return 'admin';
    if (preg_match('/^ceo/i', $username))   return 'ceo';
    if (preg_match('/^lab/i', $username))   return 'lab_manager';
    return 'user';
}

// ═══ Parse Thai name into first/last ═══
function parseThaiName(string $fullName): array {
    $fullName = trim($fullName);
    // Remove titles: นาย, นาง, นางสาว, ดร., ผศ., รศ., ศ.
    $titles = ['นางสาว', 'นาง', 'นาย', 'ดร\.?', 'ผศ\.?', 'รศ\.?', 'ศ\.?'];
    $titlePattern = '/^(' . implode('|', $titles) . ')\s*/u';
    $namePart = preg_replace($titlePattern, '', $fullName);
    
    $parts = preg_split('/\s+/u', trim($namePart), 2);
    $firstName = $parts[0] ?? $fullName;
    $lastName  = $parts[1] ?? '';
    
    return [$firstName, $lastName];
}

// ═══ Find department_id by งาน name ═══
function findDepartmentId(string $unitName, array $departments): ?int {
    // Exact match
    if (isset($departments[$unitName])) return $departments[$unitName];
    
    // Fuzzy match: trim and compare
    $needle = trim($unitName);
    foreach ($departments as $name => $id) {
        if (trim($name) === $needle) return $id;
        // Partial match
        if (mb_strpos($name, $needle) !== false || mb_strpos($needle, $name) !== false) return $id;
    }
    return null;
}

// ═══ Process rows ═══
echo "\n" . str_repeat('─', 100) . "\n";
printf("%-4s %-30s %-10s %-12s %-8s %-6s %s\n", '#', 'ชื่อ-นามสกุล', 'Username', 'Role', 'Dept ID', 'Status', 'Note');
echo str_repeat('─', 100) . "\n";

$inserted = 0;
$updated  = 0;
$skipped  = 0;
$errors   = 0;
$rowNum   = 0;

while (($row = fgetcsv($handle)) !== false) {
    $rowNum++;
    
    // Skip empty rows
    if (empty($row[$colMap['user']])) continue;
    
    $csvId    = trim($row[$colMap['id']]);
    $fullName = trim($row[$colMap['name']]);
    $username = trim($row[$colMap['user']]);
    $password = trim($row[$colMap['pass']]);
    $email    = trim($row[$colMap['email']]);
    $phone    = trim($row[$colMap['phone']]);
    $center   = trim($row[$colMap['center']] ?? '');
    $division = trim($row[$colMap['div']] ?? '');
    $unit     = trim($row[$colMap['unit']] ?? '');
    
    // Parse name
    [$firstName, $lastName] = parseThaiName($fullName);
    
    // Map role
    $roleName = mapRole($username);
    $roleId   = $roles[$roleName] ?? null;
    if (!$roleId) {
        printf("%-4d %-30s %-10s %-12s %-8s %-6s %s\n", $csvId, mb_substr($fullName, 0, 28), $username, $roleName, '-', '❌', "Role not found");
        $errors++;
        continue;
    }
    
    // Find department
    $deptId = null;
    if ($unit) {
        $deptId = findDepartmentId($unit, $departments);
    }
    
    // Generate unique email if needed
    $userEmail = $email;
    if ($email === 'sut@sut.ac.th' || empty($email)) {
        $userEmail = $username . '@sut.ac.th';
    }
    
    // Build department string (ศูนย์ → ฝ่าย → งาน)
    $deptStr = '';
    if ($center && $division) {
        $deptStr = $division;
    } elseif ($division) {
        $deptStr = $division;
    }
    
    // Position from งาน
    $position = $unit ?: null;
    
    // Check if user exists
    $existing = $db->prepare("SELECT id, username FROM users WHERE username = :u");
    $existing->execute([':u' => $username]);
    $existingUser = $existing->fetch();
    
    $status = '';
    $note   = '';
    
    if ($existingUser) {
        if ($force) {
            // Update existing
            if (!$dryRun) {
                $stmt = $db->prepare("
                    UPDATE users SET 
                        first_name = :fn, last_name = :ln, full_name_th = :fnt,
                        email = :em, phone = :ph, department = :dep,
                        position = :pos, department_id = :did,
                        role_id = :rid, organization_id = 1
                    WHERE username = :u
                ");
                $stmt->execute([
                    ':fn'  => $firstName,
                    ':ln'  => $lastName,
                    ':fnt' => $fullName,
                    ':em'  => $userEmail,
                    ':ph'  => $phone,
                    ':dep' => $deptStr,
                    ':pos' => $position,
                    ':did' => $deptId,
                    ':rid' => $roleId,
                    ':u'   => $username
                ]);
            }
            $status = '🔄';
            $note   = "Updated (ID:{$existingUser['id']})";
            $updated++;
        } else {
            $status = '⏭️';
            $note   = "Exists (ID:{$existingUser['id']}), use --force to update";
            $skipped++;
        }
    } else {
        // Insert new user
        if (!$dryRun) {
            $passHash = password_hash($password ?: '123', PASSWORD_DEFAULT);
            $stmt = $db->prepare("
                INSERT INTO users (
                    organization_id, role_id, username, email, password_hash,
                    first_name, last_name, full_name_th, phone,
                    department, position, department_id,
                    theme_preference, language, email_verified, is_active
                ) VALUES (
                    1, :rid, :u, :em, :ph_hash,
                    :fn, :ln, :fnt, :phone,
                    :dep, :pos, :did,
                    'auto', 'th', 1, 1
                )
            ");
            try {
                $stmt->execute([
                    ':rid'     => $roleId,
                    ':u'       => $username,
                    ':em'      => $userEmail,
                    ':ph_hash' => $passHash,
                    ':fn'      => $firstName,
                    ':ln'      => $lastName,
                    ':fnt'     => $fullName,
                    ':phone'   => $phone,
                    ':dep'     => $deptStr,
                    ':pos'     => $position,
                    ':did'     => $deptId
                ]);
                $newId = $db->lastInsertId();
                
                // Create notification settings
                try {
                    $db->prepare("INSERT INTO notification_settings (user_id) VALUES (:uid)")->execute([':uid' => $newId]);
                } catch (Exception $e) { /* ignore if exists */ }
                
                $status = '✅';
                $note   = "Inserted (ID:$newId)";
            } catch (PDOException $e) {
                if (strpos($e->getMessage(), 'Duplicate') !== false) {
                    $status = '⚠️';
                    $note   = "Duplicate email: $userEmail";
                    $skipped++;
                    printf("%-4d %-30s %-10s %-12s %-8s %-6s %s\n", $csvId, mb_substr($fullName, 0, 28), $username, $roleName, $deptId ?? '-', $status, $note);
                    continue;
                }
                throw $e;
            }
        } else {
            $status = '🔍';
            $note   = "Would insert";
        }
        $inserted++;
    }
    
    printf("%-4d %-30s %-10s %-12s %-8s %-6s %s\n", $csvId, mb_substr($fullName, 0, 28), $username, $roleName, $deptId ?? '-', $status, $note);
}

fclose($handle);

echo str_repeat('─', 100) . "\n";
echo "\n╔══════════════════════════════════════╗\n";
echo "║  Summary                            ║\n";
echo "╠══════════════════════════════════════╣\n";
printf("║  ✅ Inserted:  %-20d ║\n", $inserted);
printf("║  🔄 Updated:   %-20d ║\n", $updated);
printf("║  ⏭️  Skipped:  %-20d ║\n", $skipped);
printf("║  ❌ Errors:    %-20d ║\n", $errors);
printf("║  📊 Total:     %-20d ║\n", $rowNum);
echo "╚══════════════════════════════════════╝\n";

if ($dryRun) {
    echo "\n💡 This was a DRY RUN. Run without --dry-run to apply changes.\n";
    echo "   Use --force to update existing users.\n";
}

echo "\n🔑 Default password for all new users: 123\n";
echo "   Users should change their password after first login.\n\n";
