<?php
/**
 * Complete SUT data importer
 *
 * Source files:
 * - data/1.ตัวอย่างการจัดเก็บข้อมูลแบบเดิม.csv
 * - data/2.ชื่อสาร CAS No. ชื่อผู้ผลิต ข้อมูลสารเคมี ที่ระบบต้องรู้จัก.csv
 * - data/4.อาคารจัดเก็บสาร.csv
 * - data/5.ชื่อห้อง หมายเลข ที่เก็บสาร.csv
 * - data/6.สารเคมีที่มีอยู่ในคลังฯ.csv
 * - data/7.คลัง.csv
 * - data/user.csv
 */

require_once __DIR__ . '/../includes/database.php';

$pdo = Database::getInstance();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

function csvRows(string $path, int $skipRows = 0): Generator {
    $f = fopen($path, 'r');
    if (!$f) {
        throw new RuntimeException("Cannot open CSV: {$path}");
    }

    $bom = fread($f, 3);
    if ($bom !== "\xEF\xBB\xBF") {
        rewind($f);
    }

    $lineNo = 0;
    while (($row = fgetcsv($f)) !== false) {
        $lineNo++;
        if ($lineNo <= $skipRows) {
            continue;
        }
        yield $row;
    }
    fclose($f);
}

function parseDecimal(?string $value): ?float {
    if ($value === null) return null;
    $clean = trim(str_replace(',', '', $value));
    return is_numeric($clean) ? (float)$clean : null;
}

function parseDateTimeTH(?string $value): ?string {
    if (!$value) return null;
    $value = trim($value);
    if ($value === '') return null;

    $formats = ['j/n/Y H:i', 'j/n/Y G:i', 'Y-m-d H:i:s', 'Y-m-d'];
    foreach ($formats as $fmt) {
        $dt = DateTime::createFromFormat($fmt, $value);
        if ($dt instanceof DateTime) {
            return $dt->format('Y-m-d H:i:s');
        }
    }
    return null;
}

function parseDateTH(?string $value): ?string {
    if (!$value) return null;
    $value = trim($value);
    if ($value === '') return null;

    $formats = ['j/n/Y', 'Y-m-d'];
    foreach ($formats as $fmt) {
        $dt = DateTime::createFromFormat($fmt, $value);
        if ($dt instanceof DateTime) {
            return $dt->format('Y-m-d');
        }
    }
    return null;
}

function splitQuantityWithUnit(?string $value): array {
    $text = trim((string)$value);
    if ($text === '') return [null, null];
    if (preg_match('/([0-9.,]+)\s*(.+)$/u', $text, $m)) {
        return [parseDecimal($m[1]), trim($m[2]) ?: null];
    }
    return [parseDecimal($text), null];
}

echo "=== SUT Complete Data Import ===\n";

$pdo->beginTransaction();
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS chemical_stock (
        id INT AUTO_INCREMENT PRIMARY KEY,
        bottle_code VARCHAR(80) NOT NULL,
        chemical_name VARCHAR(500) NOT NULL,
        cas_no VARCHAR(120) DEFAULT NULL,
        grade VARCHAR(255) DEFAULT NULL,
        package_size DECIMAL(14,4) DEFAULT NULL,
        remaining_qty DECIMAL(14,4) DEFAULT NULL,
        unit VARCHAR(50) DEFAULT NULL,
        owner_name VARCHAR(255) DEFAULT NULL,
        owner_user_id INT DEFAULT NULL,
        added_at DATETIME DEFAULT NULL,
        storage_location VARCHAR(255) DEFAULT NULL,
        building_name VARCHAR(255) DEFAULT NULL,
        room_name VARCHAR(255) DEFAULT NULL,
        room_code VARCHAR(100) DEFAULT NULL,
        cabinet_name VARCHAR(255) DEFAULT NULL,
        shelf_name VARCHAR(255) DEFAULT NULL,
        slot_name VARCHAR(255) DEFAULT NULL,
        manufacturer_name VARCHAR(255) DEFAULT NULL,
        supplier_name VARCHAR(255) DEFAULT NULL,
        invoice_number VARCHAR(120) DEFAULT NULL,
        price DECIMAL(14,2) DEFAULT NULL,
        vat_percent DECIMAL(6,2) DEFAULT NULL,
        notes TEXT,
        funding_source VARCHAR(255) DEFAULT NULL,
        funding_subsource VARCHAR(255) DEFAULT NULL,
        center_name VARCHAR(255) DEFAULT NULL,
        division_name VARCHAR(255) DEFAULT NULL,
        section_name VARCHAR(255) DEFAULT NULL,
        project_name VARCHAR(255) DEFAULT NULL,
        store_name VARCHAR(255) DEFAULT NULL,
        store_affiliation VARCHAR(500) DEFAULT NULL,
        un_class_text VARCHAR(500) DEFAULT NULL,
        ghs_hazard_text VARCHAR(500) DEFAULT NULL,
        expiry_date DATE DEFAULT NULL,
        chemical_id INT DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY uk_bottle_code (bottle_code),
        INDEX idx_owner (owner_user_id),
        INDEX idx_chemical (chemical_id),
        INDEX idx_cas (cas_no)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $pdo->exec("CREATE TABLE IF NOT EXISTS manufacturers (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        UNIQUE KEY uk_mfr_name (name)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $pdo->exec("CREATE TABLE IF NOT EXISTS suppliers (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        UNIQUE KEY uk_supplier_name (name)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $chemicalMap = [];
    foreach ($pdo->query("SELECT id, cas_number FROM chemicals WHERE cas_number IS NOT NULL AND cas_number <> ''") as $r) {
        $chemicalMap[trim((string)$r['cas_number'])] = (int)$r['id'];
    }

    // Import organization store hierarchy (file 7)
    $pdo->exec("CREATE TABLE IF NOT EXISTS lab_stores (
        id INT AUTO_INCREMENT PRIMARY KEY,
        center_name VARCHAR(255) NOT NULL,
        division_name VARCHAR(255) NOT NULL,
        section_name VARCHAR(255) NOT NULL,
        store_name VARCHAR(255) NOT NULL,
        bottle_count INT DEFAULT 0,
        chemical_count INT DEFAULT 0,
        total_weight_kg DECIMAL(12,4) DEFAULT 0,
        is_active TINYINT(1) DEFAULT 1,
        UNIQUE KEY uk_store_name (center_name, division_name, section_name, store_name)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $upsertStore = $pdo->prepare("INSERT INTO lab_stores(center_name, division_name, section_name, store_name, bottle_count, chemical_count, total_weight_kg, is_active)
                                  VALUES(:center,:division,:section,:store,:bottle_count,:chemical_count,:total_weight,1)
                                  ON DUPLICATE KEY UPDATE bottle_count=VALUES(bottle_count), chemical_count=VALUES(chemical_count), total_weight_kg=VALUES(total_weight_kg), is_active=1");

    foreach (csvRows(__DIR__ . '/../data/7.คลัง.csv', 3) as $row) {
        $center = trim($row[0] ?? '');
        $division = trim($row[1] ?? '');
        $section = trim($row[2] ?? '');
        $store = trim($row[3] ?? '');
        if ($center === '' || $division === '' || $section === '' || $store === '') continue;

        $upsertStore->execute([
            ':center' => $center,
            ':division' => $division,
            ':section' => $section,
            ':store' => $store,
            ':bottle_count' => (int)parseDecimal($row[4] ?? '0'),
            ':chemical_count' => (int)parseDecimal($row[5] ?? '0'),
            ':total_weight' => parseDecimal($row[6] ?? '0') ?? 0,
        ]);
    }

    // Import buildings & rooms (file 4 + 5)
    $pdo->exec("CREATE TABLE IF NOT EXISTS buildings (
        id INT PRIMARY KEY,
        organization_id INT DEFAULT 1,
        name VARCHAR(255) NOT NULL,
        name_en VARCHAR(255),
        code VARCHAR(50),
        shortname VARCHAR(50)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    $pdo->exec("CREATE TABLE IF NOT EXISTS rooms (
        id INT PRIMARY KEY,
        building_id INT NOT NULL,
        name VARCHAR(255) NOT NULL,
        name_en VARCHAR(255),
        room_number VARCHAR(50),
        room_type VARCHAR(100),
        area_sqm DECIMAL(10,2),
        capacity_persons INT,
        floor INT,
        responsibility_person VARCHAR(255),
        status_text VARCHAR(50),
        bookable TINYINT(1) DEFAULT 0,
        INDEX idx_room_building(building_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $upsertBuilding = $pdo->prepare("INSERT INTO buildings(id, organization_id, name, name_en, code, shortname)
                                     VALUES(:id,1,:name,:name_en,:code,:shortname)
                                     ON DUPLICATE KEY UPDATE name=VALUES(name), name_en=VALUES(name_en), code=VALUES(code), shortname=VALUES(shortname)");
    foreach (csvRows(__DIR__ . '/../data/4.อาคารจัดเก็บสาร.csv', 1) as $row) {
        $id = (int)($row[0] ?? 0);
        if ($id <= 0) continue;
        $upsertBuilding->execute([
            ':id' => $id,
            ':name' => trim($row[1] ?? '') ?: "อาคาร {$id}",
            ':name_en' => trim($row[2] ?? '') ?: null,
            ':code' => trim($row[3] ?? '') ?: null,
            ':shortname' => trim($row[3] ?? '') ?: null,
        ]);
    }

    $upsertRoom = $pdo->prepare("INSERT INTO rooms(id, building_id, name, name_en, room_number, room_type, area_sqm, capacity_persons, floor, responsibility_person, status_text, bookable)
                                 VALUES(:id,:building_id,:name,:name_en,:room_number,:room_type,:area_sqm,:capacity,:floor,:responsibility,:status_text,:bookable)
                                 ON DUPLICATE KEY UPDATE building_id=VALUES(building_id), name=VALUES(name), name_en=VALUES(name_en), room_number=VALUES(room_number), room_type=VALUES(room_type), area_sqm=VALUES(area_sqm), capacity_persons=VALUES(capacity_persons), floor=VALUES(floor), responsibility_person=VALUES(responsibility_person), status_text=VALUES(status_text), bookable=VALUES(bookable)");
    foreach (csvRows(__DIR__ . '/../data/5.ชื่อห้อง หมายเลข ที่เก็บสาร.csv', 1) as $row) {
        $id = (int)($row[0] ?? 0);
        $buildingId = (int)($row[10] ?? 0);
        if ($id < 0 || $buildingId <= 0) continue;

        $upsertRoom->execute([
            ':id' => $id,
            ':building_id' => $buildingId,
            ':name' => trim($row[1] ?? '') ?: (trim($row[4] ?? '') ?: "ห้อง {$id}"),
            ':name_en' => trim($row[2] ?? '') ?: null,
            ':room_number' => trim($row[3] ?? '') ?: null,
            ':room_type' => trim($row[4] ?? '') ?: null,
            ':area_sqm' => parseDecimal($row[5] ?? null),
            ':capacity' => (int)parseDecimal($row[6] ?? '0'),
            ':floor' => (int)parseDecimal($row[9] ?? '0'),
            ':responsibility' => trim($row[13] ?? '') ?: null,
            ':status_text' => trim($row[15] ?? '') ?: null,
            ':bookable' => trim((string)($row[16] ?? '')) !== '' ? 1 : 0,
        ]);
    }

    // Import chemical master (file 2)
    $insertMfr = $pdo->prepare("INSERT IGNORE INTO manufacturers(name) VALUES(:name)");
    $findMfrId = $pdo->prepare("SELECT id FROM manufacturers WHERE name=:name");
    $findChem = $pdo->prepare("SELECT id FROM chemicals WHERE name=:name AND COALESCE(cas_number,'')=:cas LIMIT 1");
    $insertChem = $pdo->prepare("INSERT INTO chemicals(name, cas_number, manufacturer_id, catalogue_number, physical_state, substance_type, substance_category, created_by, is_active, verified)
                                 VALUES(:name,:cas,:mfr,:catalogue,:state,:stype,:scat,1,1,0)");

    foreach (csvRows(__DIR__ . '/../data/2.ชื่อสาร CAS No. ชื่อผู้ผลิต ข้อมูลสารเคมี ที่ระบบต้องรู้จัก.csv', 3) as $row) {
        $name = trim($row[0] ?? '');
        if ($name === '' || $name === 'ชื่อ') continue;
        $cas = trim($row[1] ?? '');
        $mfr = trim($row[2] ?? '');

        $mfrId = null;
        if ($mfr !== '') {
            $insertMfr->execute([':name' => $mfr]);
            $findMfrId->execute([':name' => $mfr]);
            $mfrId = (int)($findMfrId->fetchColumn() ?: 0) ?: null;
        }

        $findChem->execute([':name' => $name, ':cas' => $cas]);
        if (!$findChem->fetchColumn()) {
            $insertChem->execute([
                ':name' => $name,
                ':cas' => $cas ?: null,
                ':mfr' => $mfrId,
                ':catalogue' => trim($row[3] ?? '') ?: null,
                ':state' => in_array(strtolower(trim((string)($row[4] ?? ''))), ['solid', 'liquid', 'gas'], true) ? strtolower(trim((string)$row[4])) : 'solid',
                ':stype' => trim($row[5] ?? '') ?: null,
                ':scat' => trim($row[6] ?? '') ?: null,
            ]);
            $id = (int)$pdo->lastInsertId();
            if ($cas !== '') $chemicalMap[$cas] = $id;
        }
    }

    // Import users from user.csv (only if username does not exist)
    $findUserByUsername = $pdo->prepare("SELECT id FROM users WHERE username = :username LIMIT 1");
    $insertUser = $pdo->prepare("INSERT INTO users (id, organization_id, role_id, username, email, password_hash, first_name, last_name, full_name_th, phone, department, position, is_active)
                                 VALUES(:id, 1, 1, :username, :email, :password_hash, :first_name, :last_name, :full_name, :phone, :department, :position, 1)");
    foreach (csvRows(__DIR__ . '/../data/user.csv', 1) as $row) {
        $username = trim($row[2] ?? '');
        if ($username === '') continue;

        $findUserByUsername->execute([':username' => $username]);
        if ($findUserByUsername->fetchColumn()) {
            continue;
        }

        $fullName = trim($row[1] ?? '');
        $parts = preg_split('/\s+/u', $fullName, 2);
        $first = trim($parts[0] ?? $fullName);
        $last = trim($parts[1] ?? '-');
        $insertUser->execute([
            ':id' => (int)($row[0] ?? 0) ?: null,
            ':username' => $username,
            ':email' => trim($row[4] ?? '') ?: null,
            ':password_hash' => password_hash(trim((string)($row[3] ?? '123')), PASSWORD_BCRYPT),
            ':first_name' => $first,
            ':last_name' => $last,
            ':full_name' => $fullName ?: null,
            ':phone' => trim($row[5] ?? '') ?: null,
            ':department' => trim($row[7] ?? '') ?: null,
            ':position' => trim($row[8] ?? '') ?: null,
        ]);
    }

    // Build user map
    $userMap = [];
    foreach ($pdo->query("SELECT id, CONCAT(first_name,' ',last_name) AS full_name_th, full_name_th AS full_name_full FROM users") as $u) {
        $name1 = mb_strtolower(trim((string)$u['full_name_th']));
        $name2 = mb_strtolower(trim((string)$u['full_name_full']));
        if ($name1 !== '') $userMap[$name1] = (int)$u['id'];
        if ($name2 !== '') $userMap[$name2] = (int)$u['id'];
    }

    $upsertStock = $pdo->prepare("INSERT INTO chemical_stock (
            bottle_code, chemical_name, cas_no, grade, package_size, remaining_qty, unit, owner_name, owner_user_id,
            added_at, storage_location, building_name, room_name, room_code, cabinet_name, shelf_name, slot_name,
            manufacturer_name, supplier_name, invoice_number, price, vat_percent, notes,
            funding_source, funding_subsource, center_name, division_name, section_name, project_name,
            store_name, store_affiliation, un_class_text, ghs_hazard_text, expiry_date, chemical_id
        ) VALUES (
            :bottle_code, :chemical_name, :cas_no, :grade, :package_size, :remaining_qty, :unit, :owner_name, :owner_user_id,
            :added_at, :storage_location, :building_name, :room_name, :room_code, :cabinet_name, :shelf_name, :slot_name,
            :manufacturer_name, :supplier_name, :invoice_number, :price, :vat_percent, :notes,
            :funding_source, :funding_subsource, :center_name, :division_name, :section_name, :project_name,
            :store_name, :store_affiliation, :un_class_text, :ghs_hazard_text, :expiry_date, :chemical_id
        ) ON DUPLICATE KEY UPDATE
            chemical_name = VALUES(chemical_name), cas_no = VALUES(cas_no), grade = VALUES(grade),
            package_size = VALUES(package_size), remaining_qty = VALUES(remaining_qty), unit = VALUES(unit),
            owner_name = VALUES(owner_name), owner_user_id = VALUES(owner_user_id), added_at = VALUES(added_at),
            storage_location = VALUES(storage_location), building_name = VALUES(building_name), room_name = VALUES(room_name),
            room_code = VALUES(room_code), manufacturer_name = VALUES(manufacturer_name), supplier_name = VALUES(supplier_name),
            invoice_number = VALUES(invoice_number), price = VALUES(price), vat_percent = VALUES(vat_percent),
            notes = VALUES(notes), funding_source = VALUES(funding_source), funding_subsource = VALUES(funding_subsource),
            center_name = VALUES(center_name), division_name = VALUES(division_name), section_name = VALUES(section_name),
            project_name = VALUES(project_name), store_name = VALUES(store_name), store_affiliation = VALUES(store_affiliation),
            un_class_text = VALUES(un_class_text), ghs_hazard_text = VALUES(ghs_hazard_text), expiry_date = VALUES(expiry_date),
            chemical_id = VALUES(chemical_id)");

    // full layout (file 1)
    foreach (csvRows(__DIR__ . '/../data/1.ตัวอย่างการจัดเก็บข้อมูลแบบเดิม.csv', 1) as $row) {
        $bottleCode = trim($row[0] ?? '');
        if ($bottleCode === '') continue;

        [$pkg, $pkgUnit] = splitQuantityWithUnit($row[4] ?? null);
        [$rem, $remUnit] = splitQuantityWithUnit($row[5] ?? null);
        $unit = $remUnit ?: $pkgUnit;
        $ownerName = trim($row[6] ?? '');
        $ownerId = $userMap[mb_strtolower($ownerName)] ?? null;
        $cas = trim($row[2] ?? '');

        $upsertStock->execute([
            ':bottle_code' => $bottleCode,
            ':chemical_name' => trim($row[1] ?? ''),
            ':cas_no' => $cas ?: null,
            ':grade' => trim($row[3] ?? '') ?: null,
            ':package_size' => $pkg,
            ':remaining_qty' => $rem,
            ':unit' => $unit,
            ':owner_name' => $ownerName ?: null,
            ':owner_user_id' => $ownerId,
            ':added_at' => parseDateTimeTH($row[7] ?? null),
            ':storage_location' => trim($row[27] ?? '') ?: null,
            ':building_name' => trim($row[8] ?? '') ?: null,
            ':room_name' => trim($row[9] ?? '') ?: null,
            ':room_code' => trim($row[10] ?? '') ?: null,
            ':cabinet_name' => trim($row[11] ?? '') ?: null,
            ':shelf_name' => trim($row[12] ?? '') ?: null,
            ':slot_name' => trim($row[13] ?? '') ?: null,
            ':manufacturer_name' => trim($row[14] ?? '') ?: null,
            ':supplier_name' => trim($row[15] ?? '') ?: null,
            ':invoice_number' => trim($row[16] ?? '') ?: null,
            ':price' => parseDecimal($row[17] ?? null),
            ':vat_percent' => parseDecimal($row[18] ?? null),
            ':notes' => trim($row[19] ?? '') ?: null,
            ':funding_source' => trim($row[20] ?? '') ?: null,
            ':funding_subsource' => trim($row[21] ?? '') ?: null,
            ':center_name' => trim($row[22] ?? '') ?: null,
            ':division_name' => trim($row[23] ?? '') ?: null,
            ':section_name' => trim($row[24] ?? '') ?: null,
            ':project_name' => trim($row[25] ?? '') ?: null,
            ':store_name' => trim($row[26] ?? '') ?: null,
            ':store_affiliation' => trim($row[27] ?? '') ?: null,
            ':un_class_text' => trim($row[28] ?? '') ?: null,
            ':ghs_hazard_text' => trim($row[29] ?? '') ?: null,
            ':expiry_date' => parseDateTH($row[30] ?? null),
            ':chemical_id' => $chemicalMap[$cas] ?? null,
        ]);
    }

    // compact layout (file 6)
    foreach (csvRows(__DIR__ . '/../data/6.สารเคมีที่มีอยู่ในคลังฯ.csv', 3) as $row) {
        $bottleCode = trim($row[0] ?? '');
        if ($bottleCode === '') continue;

        $ownerName = trim($row[7] ?? '');
        $ownerId = $userMap[mb_strtolower($ownerName)] ?? null;
        $cas = trim($row[2] ?? '');
        $upsertStock->execute([
            ':bottle_code' => $bottleCode,
            ':chemical_name' => trim($row[1] ?? ''),
            ':cas_no' => $cas ?: null,
            ':grade' => trim($row[3] ?? '') ?: null,
            ':package_size' => parseDecimal($row[4] ?? null),
            ':remaining_qty' => parseDecimal($row[5] ?? null),
            ':unit' => trim($row[6] ?? '') ?: null,
            ':owner_name' => $ownerName ?: null,
            ':owner_user_id' => $ownerId,
            ':added_at' => parseDateTimeTH($row[8] ?? null),
            ':storage_location' => trim($row[32] ?? '') ?: null,
            ':building_name' => null,
            ':room_name' => null,
            ':room_code' => null,
            ':cabinet_name' => null,
            ':shelf_name' => null,
            ':slot_name' => null,
            ':manufacturer_name' => null,
            ':supplier_name' => null,
            ':invoice_number' => null,
            ':price' => null,
            ':vat_percent' => null,
            ':notes' => null,
            ':funding_source' => null,
            ':funding_subsource' => null,
            ':center_name' => null,
            ':division_name' => null,
            ':section_name' => null,
            ':project_name' => null,
            ':store_name' => null,
            ':store_affiliation' => null,
            ':un_class_text' => null,
            ':ghs_hazard_text' => null,
            ':expiry_date' => null,
            ':chemical_id' => $chemicalMap[$cas] ?? null,
        ]);
    }

    $pdo->commit();

    $countStock = (int)$pdo->query("SELECT COUNT(*) FROM chemical_stock")->fetchColumn();
    $countChem = (int)$pdo->query("SELECT COUNT(*) FROM chemicals")->fetchColumn();

    echo "Imported successfully.\n";
    echo "- chemicals: {$countChem}\n";
    echo "- chemical_stock: {$countStock}\n";
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    fwrite(STDERR, "Import failed: " . $e->getMessage() . "\n");
    exit(1);
}
