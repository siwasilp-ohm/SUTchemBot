<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <title>AR View — SUT chemBot</title>
    
    <!-- Google Model Viewer for AR + 3D -->
    <script type="module" src="https://unpkg.com/@google/model-viewer/dist/model-viewer.min.js"></script>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    
    <style>
        *{margin:0;padding:0;box-sizing:border-box}
        body{font-family:'Inter',sans-serif;overflow:hidden;background:#0a0a1a;color:#fff;height:100vh;width:100vw}

        /* ═══ Model Viewer ═══ */
        model-viewer{width:100%;height:100vh;background:linear-gradient(135deg,#0c0c1d 0%,#1a1a3e 50%,#0c0c1d 100%);--poster-color:transparent}
        model-viewer::part(default-ar-button){display:none}

        /* ═══ Embed Viewer ═══ */
        .embed-viewer{width:100%;height:100vh;position:relative}
        .embed-viewer iframe{width:100%;height:100%;border:none}

        /* ═══ Fallback 3D (CSS bottle) ═══ */
        .fallback-3d{width:100%;height:100vh;display:flex;align-items:center;justify-content:center;background:linear-gradient(135deg,#0c0c1d 0%,#1a1a3e 50%,#0c0c1d 100%);position:relative;overflow:hidden}
        .fallback-3d::before{content:'';position:absolute;width:200%;height:200%;background:radial-gradient(ellipse,rgba(99,102,241,.08) 0%,transparent 70%);animation:bgPulse 8s ease-in-out infinite}
        @keyframes bgPulse{0%,100%{transform:scale(1);opacity:.5}50%{transform:scale(1.1);opacity:1}}

        .bottle-3d{width:120px;position:relative;animation:bottleFloat 4s ease-in-out infinite}
        @keyframes bottleFloat{0%,100%{transform:translateY(0) rotate(-2deg)}50%{transform:translateY(-12px) rotate(2deg)}}
        .bottle-body{width:120px;height:200px;border:3px solid rgba(255,255,255,.25);border-radius:20px;position:relative;overflow:hidden;background:rgba(255,255,255,.03);backdrop-filter:blur(4px)}
        .bottle-neck{width:40px;height:30px;border:3px solid rgba(255,255,255,.25);border-bottom:none;border-radius:8px 8px 0 0;margin:0 auto;background:rgba(255,255,255,.03)}
        .bottle-cap{width:48px;height:14px;background:rgba(255,255,255,.2);border-radius:6px 6px 0 0;margin:0 auto}
        .bottle-fluid{position:absolute;bottom:0;left:0;right:0;transition:height .8s cubic-bezier(.4,0,.2,1);border-radius:0 0 17px 17px}
        .bottle-fluid::before{content:'';position:absolute;top:0;left:0;right:0;height:8px;background:rgba(255,255,255,.15);border-radius:50%;animation:wave 3s ease-in-out infinite}
        @keyframes wave{0%,100%{transform:scaleX(.95)}50%{transform:scaleX(1.05)}}
        .bottle-label{position:absolute;top:30%;left:8px;right:8px;text-align:center;padding:8px 4px;background:rgba(0,0,0,.3);backdrop-filter:blur(4px);border-radius:6px;border:1px solid rgba(255,255,255,.1)}
        .bottle-label .bl-name{font-size:10px;font-weight:700;line-height:1.2;margin-bottom:2px;word-break:break-word}
        .bottle-label .bl-cas{font-size:8px;opacity:.6}
        .bottle-pct-label{text-align:center;margin-top:8px;font-size:24px;font-weight:900}

        /* ═══ Top Header Bar ═══ */
        .ar-header{position:fixed;top:0;left:0;right:0;z-index:200;padding:12px 16px;background:linear-gradient(to bottom,rgba(0,0,0,.8) 0%,rgba(0,0,0,.4) 60%,transparent 100%);display:flex;align-items:center;justify-content:space-between;gap:8px}
        .ar-header a,.ar-header button{width:40px;height:40px;border-radius:12px;background:rgba(255,255,255,.12);backdrop-filter:blur(8px);border:1px solid rgba(255,255,255,.08);display:flex;align-items:center;justify-content:center;color:#fff;text-decoration:none;font-size:16px;cursor:pointer;transition:all .15s}
        .ar-header a:hover,.ar-header button:hover{background:rgba(255,255,255,.22)}
        .ar-id-pill{background:rgba(0,0,0,.5);backdrop-filter:blur(8px);border:1px solid rgba(255,255,255,.1);border-radius:20px;padding:6px 14px;font-size:11px;font-weight:600;display:flex;align-items:center;gap:6px;flex-shrink:1;min-width:0;overflow:hidden}
        .ar-id-pill i{color:#818cf8;flex-shrink:0}
        .ar-act-spatial{width:40px;height:40px;border-radius:12px;display:flex;align-items:center;justify-content:center;padding:6px 10px;background:linear-gradient(135deg,#6366f1,#818cf8);border:1px solid rgba(99,102,241,.25);color:#fff;font-size:14px;cursor:pointer;transition:all .15s}
        .ar-act-spatial:hover{box-shadow:0 6px 20px rgba(99,102,241,.18);transform:translateY(-2px)}
        .ar-act-spatial:active{transform:scale(.92)}

        /* ═══ Hazard Strip (left) ═══ */
        .ar-hazard-strip{position:fixed;top:70px;left:12px;z-index:200;display:flex;flex-direction:column;gap:6px}
        .ar-hz-diamond{width:40px;height:40px;position:relative;cursor:pointer;transition:transform .15s}
        .ar-hz-diamond:hover{transform:scale(1.15)}
        .ar-hz-inner{position:absolute;inset:4px;transform:rotate(45deg);border-radius:3px;display:flex;align-items:center;justify-content:center;font-size:14px;border-width:2px;border-style:solid;backdrop-filter:blur(4px)}
        .ar-hz-inner i{transform:rotate(-45deg)}
        .ar-hz-tip{position:absolute;left:calc(100% + 6px);top:50%;transform:translateY(-50%);background:rgba(0,0,0,.85);backdrop-filter:blur(8px);color:#fff;padding:4px 10px;border-radius:6px;font-size:10px;font-weight:600;white-space:nowrap;pointer-events:none;opacity:0;transition:opacity .15s}
        .ar-hz-diamond:hover .ar-hz-tip{opacity:1}

        /* Hazard color map */
        .hz-compressed_gas .ar-hz-inner{background:rgba(217,119,6,.15);border-color:#d97706;color:#fbbf24}
        .hz-flammable .ar-hz-inner{background:rgba(220,38,38,.15);border-color:#dc2626;color:#f87171}
        .hz-oxidizing .ar-hz-inner{background:rgba(217,119,6,.15);border-color:#d97706;color:#fbbf24}
        .hz-toxic .ar-hz-inner{background:rgba(220,38,38,.2);border-color:#dc2626;color:#fca5a5}
        .hz-corrosive .ar-hz-inner{background:rgba(124,58,237,.15);border-color:#7c3aed;color:#c4b5fd}
        .hz-irritant .ar-hz-inner{background:rgba(245,158,11,.15);border-color:#f59e0b;color:#fde68a}
        .hz-environmental .ar-hz-inner{background:rgba(22,163,74,.15);border-color:#16a34a;color:#86efac}
        .hz-health_hazard .ar-hz-inner{background:rgba(220,38,38,.15);border-color:#dc2626;color:#fca5a5}
        .hz-explosive .ar-hz-inner{background:rgba(234,88,12,.15);border-color:#ea580c;color:#fb923c}

        /* ═══ Fluid Level (right) ═══ */
        .ar-fluid-col{position:fixed;top:70px;right:12px;z-index:200;text-align:center}
        .ar-fluid-bar{width:44px;height:130px;border:2px solid rgba(255,255,255,.3);border-radius:12px;position:relative;overflow:hidden;background:rgba(255,255,255,.05);backdrop-filter:blur(4px)}
        .ar-fluid-fill{position:absolute;bottom:0;left:0;right:0;border-radius:0 0 10px 10px;transition:height .8s cubic-bezier(.4,0,.2,1)}
        .ar-fluid-fill::before{content:'';position:absolute;top:0;left:0;right:0;height:4px;background:rgba(255,255,255,.2);border-radius:50%;animation:wave 3s ease-in-out infinite}
        .ar-fluid-pct{position:absolute;inset:0;display:flex;align-items:center;justify-content:center;font-size:14px;font-weight:900;text-shadow:0 1px 4px rgba(0,0,0,.6)}
        .ar-fluid-label{font-size:9px;font-weight:600;opacity:.6;margin-top:4px;text-transform:uppercase;letter-spacing:.5px}

        /* ═══ Signal Word (top center) ═══ */
        .ar-signal{position:fixed;top:62px;left:50%;transform:translateX(-50%);z-index:200;padding:5px 16px;border-radius:20px;font-size:11px;font-weight:800;letter-spacing:1px;text-transform:uppercase;display:none;align-items:center;gap:6px;backdrop-filter:blur(8px)}
        .ar-signal.danger{display:flex;background:rgba(220,38,38,.2);border:1.5px solid rgba(220,38,38,.5);color:#fca5a5;animation:signalPulse 2s infinite}
        .ar-signal.warning{display:flex;background:rgba(245,158,11,.2);border:1.5px solid rgba(245,158,11,.5);color:#fde68a}
        @keyframes signalPulse{0%,100%{box-shadow:0 0 0 0 rgba(220,38,38,.3)}50%{box-shadow:0 0 0 8px rgba(220,38,38,0)}}

        /* ═══ Bottom Info Card ═══ */
        .ar-card{position:fixed;bottom:0;left:0;right:0;z-index:200;background:rgba(10,10,26,.92);backdrop-filter:blur(20px);border-top:1px solid rgba(255,255,255,.08);border-radius:24px 24px 0 0;transition:transform .35s cubic-bezier(.4,0,.2,1);padding:0;max-height:70vh;overflow-y:auto}
        .ar-card.minimized{transform:translateY(calc(100% - 56px))}
        .ar-card-handle{width:40px;height:4px;border-radius:2px;background:rgba(255,255,255,.2);margin:10px auto 0;cursor:pointer}
        .ar-card-head{padding:12px 20px 8px;display:flex;align-items:flex-start;gap:12px}
        .ar-card-head .type-ic{width:42px;height:42px;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:18px;flex-shrink:0}
        .ar-card-head .info{flex:1;min-width:0}
        .ar-card-head .chem-name{font-size:16px;font-weight:800;line-height:1.25;margin-bottom:2px}
        .ar-card-head .chem-sub{font-size:11px;color:rgba(255,255,255,.5);display:flex;flex-wrap:wrap;gap:6px;align-items:center}
        .ar-card-head .chem-sub b{color:rgba(255,255,255,.8)}

        .ar-card-tags{padding:0 20px;display:flex;gap:5px;flex-wrap:wrap;margin-bottom:10px}
        .ar-tag{font-size:9px;padding:3px 8px;border-radius:6px;font-weight:700;border:1px solid rgba(255,255,255,.1);background:rgba(255,255,255,.06)}
        .ar-tag-type{border-color:rgba(99,102,241,.3);color:#818cf8}
        .ar-tag-material{border-color:rgba(148,163,184,.3);color:#94a3b8}
        .ar-tag-grade{border-color:rgba(34,197,94,.3);color:#4ade80}
        .ar-tag-danger{border-color:rgba(220,38,38,.4);background:rgba(220,38,38,.1);color:#fca5a5}
        .ar-tag-warning{border-color:rgba(245,158,11,.4);background:rgba(245,158,11,.1);color:#fde68a}

        /* Props grid */
        .ar-props{display:grid;grid-template-columns:repeat(auto-fit,minmax(90px,1fr));gap:6px;padding:0 20px 12px}
        .ar-prop{background:rgba(255,255,255,.04);border:1px solid rgba(255,255,255,.06);border-radius:10px;padding:8px 6px;text-align:center}
        .ar-prop .p-v{font-size:13px;font-weight:800;line-height:1.1}
        .ar-prop .p-l{font-size:8px;color:rgba(255,255,255,.4);text-transform:uppercase;letter-spacing:.5px;margin-top:2px}

        /* Detail rows */
        .ar-details{padding:0 20px 12px;display:grid;grid-template-columns:1fr 1fr;gap:6px}
        .ar-detail{padding:6px 0;border-bottom:1px solid rgba(255,255,255,.04)}
        .ar-detail .d-l{font-size:9px;color:rgba(255,255,255,.35);text-transform:uppercase;font-weight:700;letter-spacing:.3px}
        .ar-detail .d-v{font-size:12px;font-weight:500;color:rgba(255,255,255,.85);margin-top:1px}
        .ar-detail-full{grid-column:1/-1}

        /* Expiry banner */
        .ar-expiry{margin:0 20px 10px;padding:8px 14px;border-radius:10px;font-size:11px;font-weight:600;display:flex;align-items:center;gap:8px}
        .ar-expiry.ok{background:rgba(34,197,94,.1);border:1px solid rgba(34,197,94,.2);color:#4ade80}
        .ar-expiry.warn{background:rgba(245,158,11,.1);border:1px solid rgba(245,158,11,.2);color:#fde68a}
        .ar-expiry.expired{background:rgba(220,38,38,.1);border:1px solid rgba(220,38,38,.2);color:#fca5a5}

        /* Action buttons */
        .ar-actions{padding:10px 20px 20px;display:flex;gap:8px}
        .ar-actions a,.ar-actions button{flex:1;padding:10px;border:none;border-radius:12px;font-size:12px;font-weight:700;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:6px;text-decoration:none;transition:all .15s}
        .ar-act-primary{background:linear-gradient(135deg,#6366f1,#818cf8);color:#fff}
        .ar-act-primary:hover{background:linear-gradient(135deg,#4f46e5,#6366f1)}
        .ar-act-secondary{background:rgba(255,255,255,.08);border:1px solid rgba(255,255,255,.1)!important;color:#fff}
        .ar-act-secondary:hover{background:rgba(255,255,255,.14)}
        .ar-act-ar{background:linear-gradient(135deg,#0d9488,#14b8a6);color:#fff}
        .ar-act-ar:hover{background:linear-gradient(135deg,#0f766e,#0d9488)}

        /* ═══ AR Mode Button ═══ */
        .ar-launch{position:fixed;bottom:20px;right:20px;z-index:300;width:56px;height:56px;border-radius:16px;background:linear-gradient(135deg,#0d9488,#14b8a6);color:#fff;border:none;font-size:22px;cursor:pointer;display:flex;align-items:center;justify-content:center;box-shadow:0 4px 20px rgba(13,148,136,.4);transition:all .2s}
        .ar-launch:hover{transform:scale(1.05);box-shadow:0 6px 28px rgba(13,148,136,.5)}
        .ar-card:not(.minimized) ~ .ar-launch{display:none}

        /* ═══ Responsive ═══ */
        @media(max-width:400px){
            .ar-props{grid-template-columns:repeat(3,1fr)}
            .ar-card-head .chem-name{font-size:14px}
        }
    </style>
</head>
<body>
<?php
require_once __DIR__ . '/../includes/database.php';

$qrCode = $_GET['qr'] ?? '';
$containerId = $_GET['id'] ?? '';
$source = $_GET['source'] ?? ''; // 'stock' or 'container' or auto-detect
$container = null;
$isStock = false;

// ── Container SELECT ──
$cnSelect = "SELECT cn.*, 
    ch.name as chemical_name, ch.cas_number, ch.hazard_pictograms,
    ch.signal_word, ch.ghs_classifications, ch.description as chem_description,
    ch.molecular_formula, ch.molecular_weight, ch.physical_state,
    ch.sds_url as chem_sds_url, ch.image_url as chem_image,
    u.first_name, u.last_name, u.full_name_th,
    l.name as lab_name,
    b.name as building_name, b.shortname as building_short,
    rm.name as room_name, rm.code as room_code,
    mfr.name as manufacturer_name
FROM containers cn
LEFT JOIN chemicals ch ON cn.chemical_id = ch.id
LEFT JOIN users u ON cn.owner_id = u.id
LEFT JOIN labs l ON cn.lab_id = l.id
LEFT JOIN buildings b ON cn.building_id = b.id
LEFT JOIN rooms rm ON cn.room_id = rm.id
LEFT JOIN manufacturers mfr ON cn.manufacturer_id = mfr.id";

// ── Chemical Stock SELECT (maps to same column names) ──
$csSelect = "SELECT s.id, 'bottle' as container_type, 'glass' as container_material,
    s.package_size as initial_quantity, s.remaining_qty as current_quantity,
    s.unit as quantity_unit, s.remaining_pct as remaining_percentage,
    s.status, 'good' as quality_status, s.grade,
    NULL as cost, NULL as expiry_date, s.added_at as received_date,
    s.bottle_code, NULL as qr_code, NULL as batch_number, NULL as lot_number,
    NULL as building_id, NULL as room_id, NULL as container_3d_model,
    NULL as notes, s.created_at,
    s.chemical_name, s.cas_no as cas_number,
    COALESCE(ch2.hazard_pictograms, '[]') as hazard_pictograms,
    ch2.signal_word, ch2.ghs_classifications,
    ch2.description as chem_description,
    ch2.molecular_formula, ch2.molecular_weight, ch2.physical_state,
    ch2.sds_url as chem_sds_url, ch2.image_url as chem_image,
    u2.first_name, u2.last_name, u2.full_name_th,
    NULL as lab_name,
    NULL as building_name, NULL as building_short,
    NULL as room_name, NULL as room_code,
    NULL as manufacturer_name,
    s.storage_location
FROM chemical_stock s
LEFT JOIN chemicals ch2 ON s.chemical_id = ch2.id
LEFT JOIN users u2 ON s.owner_user_id = u2.id";

if ($qrCode) {
    // QR code is only for containers table
    $container = Database::fetch($cnSelect . " WHERE cn.qr_code = :qr", [':qr' => $qrCode]);
} elseif ($containerId) {
    $numId = (int)$containerId;
    
    // Auto-detect source: negative ID = stock, positive = container
    if ($source === 'stock' || $numId < 0) {
        $isStock = true;
        $realId = abs($numId);
        $container = Database::fetch($csSelect . " WHERE s.id = :id", [':id' => $realId]);
    } else {
        // Try containers first
        $container = Database::fetch($cnSelect . " WHERE cn.id = :id", [':id' => $numId]);
        // Fallback to stock if not found
        if (!$container) {
            $isStock = true;
            $container = Database::fetch($csSelect . " WHERE s.id = :id", [':id' => $numId]);
        }
    }
}

if (!$container) {
    echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@600;800&display=swap" rel="stylesheet">
    <style>body{margin:0;height:100vh;display:flex;align-items:center;justify-content:center;background:linear-gradient(135deg,#0c0c1d,#1a1a3e);font-family:Inter,sans-serif;color:#fff;text-align:center}
    .box{padding:40px}.box i{font-size:48px;color:#fbbf24;margin-bottom:16px;display:block}.box h2{font-size:20px;font-weight:800;margin:0 0 8px}.box p{font-size:13px;color:rgba(255,255,255,.5);margin:0 0 20px}
    .box a{display:inline-block;padding:10px 24px;background:linear-gradient(135deg,#6366f1,#818cf8);border-radius:12px;color:#fff;text-decoration:none;font-weight:700;font-size:13px}</style></head>
    <body><div class="box"><i class="fas fa-exclamation-triangle"></i><h2>ไม่พบข้อมูล Container</h2><p>QR Code หรือ ID ไม่ถูกต้อง</p><a href="/v1/pages/containers.php"><i class="fas fa-arrow-left"></i> กลับหน้าหลัก</a></div></body></html>';
    exit;
}

// Parse data
$hazardPictograms = json_decode($container['hazard_pictograms'] ?? '[]', true);
if (!is_array($hazardPictograms)) $hazardPictograms = [];
$ghsClassifications = json_decode($container['ghs_classifications'] ?? '[]', true);
if (!is_array($ghsClassifications)) $ghsClassifications = [];
$remainingPercent = (float)($container['remaining_percentage'] ?? 100);
$fluidColor = $remainingPercent > 50 ? '#3b82f6' : ($remainingPercent > 20 ? '#eab308' : '#ef4444');
$fluidGrad = $remainingPercent > 50 ? 'linear-gradient(to top,#2563eb,#60a5fa)' : ($remainingPercent > 20 ? 'linear-gradient(to top,#ca8a04,#fbbf24)' : 'linear-gradient(to top,#dc2626,#f87171)');
$signalWord = $container['signal_word'] ?? '';
$ownerName = $container['full_name_th'] ?? trim(($container['first_name'] ?? '') . ' ' . ($container['last_name'] ?? ''));
$chemName = $container['chemical_name'] ?? 'Unknown Chemical';
$casNumber = $container['cas_number'] ?? '';
$formula = $container['molecular_formula'] ?? '';
$mw = !empty($container['molecular_weight']) ? number_format((float)$container['molecular_weight'], 2) : '';
$physState = $container['physical_state'] ?? '';
$containerType = $container['container_type'] ?? 'bottle';
$containerMaterial = $container['container_material'] ?? '';
$grade = $container['grade'] ?? '';
$curQty = $container['current_quantity'] ?? 0;
$initQty = $container['initial_quantity'] ?? 0;
$unit = $container['quantity_unit'] ?? '';
$bottleCode = $container['bottle_code'] ?? '';
$labName = $container['lab_name'] ?? '';
$locationParts = [];
if ($isStock) {
    $locationText = $container['storage_location'] ?? '-';
} else {
    if (!empty($container['building_short'])) $locationParts[] = $container['building_short'];
    elseif (!empty($container['building_name'])) $locationParts[] = $container['building_name'];
    if (!empty($container['room_code'])) $locationParts[] = $container['room_code'];
    elseif (!empty($container['room_name'])) $locationParts[] = $container['room_name'];
    $locationText = implode(' › ', $locationParts) ?: '-';
}
$mfrName = $container['manufacturer_name'] ?? '';
$sdsUrl = $container['chem_sds_url'] ?? '';
$expiryDate = $container['expiry_date'] ?? '';
$isExpired = $expiryDate && strtotime($expiryDate) < time();
$isExpiringSoon = $expiryDate && !$isExpired && strtotime($expiryDate) <= strtotime('+30 days');

// ═══ Resolve 3D Model from packaging_3d_models ═══
$modelUrl = null;
$modelType = null; // 'glb' or 'embed'
$embedCode = null;

// 1. Check container_3d_model field first (direct assignment)
if (!empty($container['container_3d_model'])) {
    $modelUrl = $container['container_3d_model'];
    $modelType = 'glb';
}

// 2. Look up packaging_3d_models by container_type + material
if (!$modelUrl) {
    // Try exact match on type + material first
    $model3d = null;
    if (!empty($containerMaterial)) {
        $model3d = Database::fetch(
            "SELECT * FROM packaging_3d_models 
             WHERE container_type = :t 
             AND container_material = :m
             AND is_active = 1
             ORDER BY is_default DESC, id DESC LIMIT 1",
            [':t' => $containerType, ':m' => $containerMaterial]
        );
    }
    // Fallback: try just by type (any material or null)
    if (!$model3d) {
        $model3d = Database::fetch(
            "SELECT * FROM packaging_3d_models 
             WHERE container_type = :t AND is_active = 1 
             ORDER BY is_default DESC, id DESC LIMIT 1",
            [':t' => $containerType]
        );
    }
    // Last fallback: any default model
    if (!$model3d) {
        $model3d = Database::fetch(
            "SELECT * FROM packaging_3d_models 
             WHERE is_active = 1 AND is_default = 1 
             ORDER BY id DESC LIMIT 1"
        );
    }
    if ($model3d) {
        if ($model3d['source_type'] === 'embed' && !empty($model3d['embed_url'])) {
            $modelUrl = $model3d['embed_url'];
            $modelType = 'embed';
            $embedCode = $model3d['embed_code'] ?? null;
        } elseif (!empty($model3d['file_url'])) {
            $modelUrl = $model3d['file_url'];
            $modelType = 'glb';
        }
    }
}

$hasModel = !empty($modelUrl);

// GHS icon/label mappings
$ghsIcons = [
    'compressed_gas' => 'fa-wind', 'flammable' => 'fa-fire-flame-curved', 'oxidizing' => 'fa-circle-radiation',
    'toxic' => 'fa-skull-crossbones', 'corrosive' => 'fa-flask-vial', 'irritant' => 'fa-exclamation-triangle',
    'environmental' => 'fa-leaf', 'health_hazard' => 'fa-heart-crack', 'explosive' => 'fa-explosion'
];
$ghsLabels = [
    'compressed_gas' => 'ก๊าซอัด', 'flammable' => 'ไวไฟ', 'oxidizing' => 'วัตถุออกซิไดซ์',
    'toxic' => 'พิษเฉียบพลัน', 'corrosive' => 'กัดกร่อน', 'irritant' => 'ระคายเคือง',
    'environmental' => 'อันตรายต่อสิ่งแวดล้อม', 'health_hazard' => 'อันตรายต่อสุขภาพ', 'explosive' => 'วัตถุระเบิด'
];
$typeIcons = ['bottle'=>'fa-wine-bottle','vial'=>'fa-vial','flask'=>'fa-flask','canister'=>'fa-gas-pump','cylinder'=>'fa-fire-extinguisher','ampoule'=>'fa-syringe','bag'=>'fa-bag-shopping'];
$typeColors = ['bottle'=>'#818cf8','vial'=>'#c084fc','flask'=>'#34d399','canister'=>'#fb923c','cylinder'=>'#f472b6','ampoule'=>'#60a5fa','bag'=>'#a1a1aa'];
$typeBg = ['bottle'=>'rgba(99,102,241,.15)','vial'=>'rgba(168,85,247,.15)','flask'=>'rgba(16,185,129,.15)','canister'=>'rgba(234,88,12,.15)','cylinder'=>'rgba(236,72,153,.15)','ampoule'=>'rgba(59,130,246,.15)','bag'=>'rgba(161,161,170,.15)'];
?>

<!-- ═══ 3D / AR View Area ═══ -->
<div id="viewerArea">
<?php if ($hasModel && $modelType === 'glb'): ?>
    <model-viewer
        id="model-viewer"
        src="<?php echo htmlspecialchars($modelUrl); ?>"
        alt="3D model of <?php echo htmlspecialchars($chemName); ?> container"
        camera-controls
        auto-rotate
        rotation-per-second="20deg"
        ar
        ar-modes="webxr scene-viewer quick-look"
        ar-scale="auto"
        ar-placement="floor"
        xr-environment
        shadow-intensity="1.2"
        shadow-softness="0.8"
        exposure="0.9"
        environment-image="neutral"
        interaction-prompt="none"
        style="display:block">
    </model-viewer>
<?php elseif ($hasModel && $modelType === 'embed'): ?>
    <div class="embed-viewer">
        <iframe src="<?php echo htmlspecialchars($modelUrl); ?>" allow="autoplay; fullscreen; xr-spatial-tracking" allowfullscreen></iframe>
    </div>
<?php else: ?>
    <!-- Fallback: Animated CSS Bottle -->
    <div class="fallback-3d">
        <div class="bottle-3d">
            <div class="bottle-cap"></div>
            <div class="bottle-neck"></div>
            <div class="bottle-body">
                <div class="bottle-fluid" style="height:<?php echo $remainingPercent; ?>%;background:<?php echo $fluidGrad; ?>"></div>
                <div class="bottle-label">
                    <div class="bl-name"><?php echo htmlspecialchars(mb_strimwidth($chemName, 0, 30, '…')); ?></div>
                    <?php if ($casNumber): ?><div class="bl-cas">CAS: <?php echo htmlspecialchars($casNumber); ?></div><?php endif; ?>
                </div>
            </div>
            <div class="bottle-pct-label" style="color:<?php echo $fluidColor; ?>"><?php echo round($remainingPercent); ?>%</div>
        </div>
    </div>
<?php endif; ?>
</div>

<!-- ═══ Top Header ═══ -->
<div class="ar-header">
    <a href="/v1/pages/containers.php" title="Back"><i class="fas fa-arrow-left"></i></a>
    <div class="ar-id-pill">
        <i class="fas <?php echo $isStock ? 'fa-database' : 'fa-box'; ?>"></i>
        <span><?php echo htmlspecialchars($bottleCode ?: ('ID: ' . ($isStock ? '-' : '') . $container['id'])); ?></span>
        <?php if ($isStock): ?><span style="font-size:8px;padding:1px 6px;border-radius:4px;background:rgba(245,158,11,.2);color:#fbbf24;margin-left:2px">CSV</span><?php endif; ?>
    </div>
    <div style="display:flex;gap:8px;align-items:center">
        <?php if ($hasModel && $modelType === 'glb'): ?>
        <button id="btnHeaderARSpatial" class="ar-act-spatial" title="AR เชิงพื้นที่"><i class="fas fa-cube"></i></button>
        <?php endif; ?>
        <button id="btnShare" title="Share"><i class="fas fa-share-alt"></i></button>
    </div>
</div>

<!-- ═══ Signal Word Badge ═══ -->
<?php if ($signalWord): ?>
<div class="ar-signal <?php echo $signalWord === 'Danger' ? 'danger' : 'warning'; ?>">
    <i class="fas <?php echo $signalWord === 'Danger' ? 'fa-radiation' : 'fa-exclamation-triangle'; ?>"></i>
    <?php echo $signalWord === 'Danger' ? 'อันตราย — DANGER' : 'ระวัง — WARNING'; ?>
</div>
<?php endif; ?>

<!-- ═══ Hazard Diamonds (Left) ═══ -->
<?php if (count($hazardPictograms) > 0): ?>
<div class="ar-hazard-strip">
    <?php foreach ($hazardPictograms as $picto): ?>
    <div class="ar-hz-diamond hz-<?php echo htmlspecialchars($picto); ?>">
        <div class="ar-hz-inner"><i class="fas <?php echo $ghsIcons[$picto] ?? 'fa-exclamation'; ?>"></i></div>
        <div class="ar-hz-tip"><?php echo $ghsLabels[$picto] ?? $picto; ?></div>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<!-- ═══ Fluid Level (Right) ═══ -->
<div class="ar-fluid-col">
    <div class="ar-fluid-bar">
        <div class="ar-fluid-fill" style="height:<?php echo $remainingPercent; ?>%;background:<?php echo $fluidGrad; ?>"></div>
        <div class="ar-fluid-pct"><?php echo round($remainingPercent); ?>%</div>
    </div>
    <div class="ar-fluid-label">เหลือ</div>
</div>

<!-- ═══ Bottom Info Card ═══ -->
<div class="ar-card" id="arCard">
    <div class="ar-card-handle" id="cardHandle"></div>

    <!-- Header -->
    <div class="ar-card-head">
        <div class="type-ic" style="background:<?php echo $typeBg[$containerType] ?? 'rgba(99,102,241,.15)'; ?>;color:<?php echo $typeColors[$containerType] ?? '#818cf8'; ?>">
            <i class="fas <?php echo $typeIcons[$containerType] ?? 'fa-box'; ?>"></i>
        </div>
        <div class="info">
            <div class="chem-name"><?php echo htmlspecialchars($chemName); ?></div>
            <div class="chem-sub">
                <?php if ($casNumber): ?>CAS: <b><?php echo htmlspecialchars($casNumber); ?></b><?php endif; ?>
                <?php if ($formula): ?> &bull; <b><?php echo htmlspecialchars($formula); ?></b><?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Tags -->
    <div class="ar-card-tags">
        <span class="ar-tag ar-tag-type"><i class="fas <?php echo $typeIcons[$containerType] ?? 'fa-box'; ?>" style="font-size:8px"></i> <?php echo ucfirst($containerType); ?></span>
        <?php if ($containerMaterial): ?><span class="ar-tag ar-tag-material"><?php echo ucfirst($containerMaterial); ?></span><?php endif; ?>
        <?php if ($grade): ?><span class="ar-tag ar-tag-grade"><?php echo htmlspecialchars($grade); ?></span><?php endif; ?>
        <?php if ($signalWord === 'Danger'): ?><span class="ar-tag ar-tag-danger"><i class="fas fa-radiation" style="font-size:8px"></i> Danger</span>
        <?php elseif ($signalWord === 'Warning'): ?><span class="ar-tag ar-tag-warning"><i class="fas fa-exclamation-triangle" style="font-size:8px"></i> Warning</span><?php endif; ?>
        <?php foreach (array_slice($ghsClassifications, 0, 3) as $gc): ?>
        <span class="ar-tag" style="border-color:rgba(220,38,38,.2);color:#fca5a5"><?php echo htmlspecialchars($gc); ?></span>
        <?php endforeach; ?>
    </div>

    <!-- Chemical Properties -->
    <div class="ar-props">
        <div class="ar-prop">
            <div class="p-v" style="color:<?php echo $fluidColor; ?>"><?php echo round($remainingPercent); ?>%</div>
            <div class="p-l">เหลือ</div>
        </div>
        <div class="ar-prop">
            <div class="p-v"><?php echo $curQty; ?><span style="font-size:9px;opacity:.5"> <?php echo htmlspecialchars($unit); ?></span></div>
            <div class="p-l">ปริมาณ</div>
        </div>
        <?php if ($formula): ?>
        <div class="ar-prop">
            <div class="p-v"><?php echo htmlspecialchars($formula); ?></div>
            <div class="p-l">สูตร</div>
        </div>
        <?php endif; ?>
        <?php if ($mw): ?>
        <div class="ar-prop">
            <div class="p-v"><?php echo $mw; ?></div>
            <div class="p-l">MW (g/mol)</div>
        </div>
        <?php endif; ?>
        <?php if ($physState): ?>
        <div class="ar-prop">
            <div class="p-v"><?php echo ucfirst($physState); ?></div>
            <div class="p-l">สถานะ</div>
        </div>
        <?php endif; ?>
        <div class="ar-prop">
            <div class="p-v"><?php echo $initQty; ?><span style="font-size:9px;opacity:.5"> <?php echo htmlspecialchars($unit); ?></span></div>
            <div class="p-l">บรรจุเดิม</div>
        </div>
    </div>

    <!-- Expiry -->
    <?php if ($expiryDate): ?>
    <div class="ar-expiry <?php echo $isExpired ? 'expired' : ($isExpiringSoon ? 'warn' : 'ok'); ?>">
        <i class="fas <?php echo $isExpired ? 'fa-exclamation-circle' : ($isExpiringSoon ? 'fa-clock' : 'fa-calendar-check'); ?>"></i>
        <?php if ($isExpired): ?>
            หมดอายุแล้ว: <?php echo date('d/m/Y', strtotime($expiryDate)); ?>
        <?php elseif ($isExpiringSoon): ?>
            ใกล้หมดอายุ: <?php echo date('d/m/Y', strtotime($expiryDate)); ?> (<?php echo ceil((strtotime($expiryDate) - time()) / 86400); ?> วัน)
        <?php else: ?>
            หมดอายุ: <?php echo date('d/m/Y', strtotime($expiryDate)); ?>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <!-- Detail rows -->
    <div class="ar-details">
        <div class="ar-detail">
            <div class="d-l"><i class="fas fa-user" style="margin-right:3px;font-size:8px"></i> เจ้าของ</div>
            <div class="d-v"><?php echo htmlspecialchars($ownerName ?: '-'); ?></div>
        </div>
        <div class="ar-detail">
            <div class="d-l"><i class="fas fa-map-marker-alt" style="margin-right:3px;font-size:8px"></i> ตำแหน่ง</div>
            <div class="d-v"><?php echo htmlspecialchars($locationText); ?></div>
        </div>
        <?php if ($labName): ?>
        <div class="ar-detail">
            <div class="d-l"><i class="fas fa-flask" style="margin-right:3px;font-size:8px"></i> แลป</div>
            <div class="d-v"><?php echo htmlspecialchars($labName); ?></div>
        </div>
        <?php endif; ?>
        <?php if ($mfrName): ?>
        <div class="ar-detail">
            <div class="d-l"><i class="fas fa-industry" style="margin-right:3px;font-size:8px"></i> ผู้ผลิต</div>
            <div class="d-v"><?php echo htmlspecialchars($mfrName); ?></div>
        </div>
        <?php endif; ?>
        <div class="ar-detail">
            <div class="d-l"><i class="fas fa-barcode" style="margin-right:3px;font-size:8px"></i> รหัสขวด</div>
            <div class="d-v" style="font-family:monospace;color:#818cf8"><?php echo htmlspecialchars($bottleCode ?: '-'); ?></div>
        </div>
        <div class="ar-detail">
            <div class="d-l"><i class="fas fa-box" style="margin-right:3px;font-size:8px"></i> สถานะ</div>
            <div class="d-v"><?php echo ucfirst($container['status'] ?? 'active'); ?></div>
        </div>
        <?php if (!empty($container['batch_number'])): ?>
        <div class="ar-detail">
            <div class="d-l">Batch No.</div>
            <div class="d-v" style="font-family:monospace"><?php echo htmlspecialchars($container['batch_number']); ?></div>
        </div>
        <?php endif; ?>
        <?php if (!empty($container['lot_number'])): ?>
        <div class="ar-detail">
            <div class="d-l">Lot No.</div>
            <div class="d-v" style="font-family:monospace"><?php echo htmlspecialchars($container['lot_number']); ?></div>
        </div>
        <?php endif; ?>
        <?php if (!empty($container['cost'])): ?>
        <div class="ar-detail">
            <div class="d-l"><i class="fas fa-coins" style="margin-right:3px;font-size:8px"></i> ราคา</div>
            <div class="d-v" style="color:#4ade80;font-weight:700"><?php echo number_format((float)$container['cost']); ?> ฿</div>
        </div>
        <?php endif; ?>
        <?php if (!empty($container['received_date'])): ?>
        <div class="ar-detail">
            <div class="d-l"><i class="fas fa-calendar" style="margin-right:3px;font-size:8px"></i> วันที่รับ</div>
            <div class="d-v"><?php echo date('d/m/Y', strtotime($container['received_date'])); ?></div>
        </div>
        <?php endif; ?>
        <?php if (!empty($container['notes'])): ?>
        <div class="ar-detail ar-detail-full">
            <div class="d-l">หมายเหตุ</div>
            <div class="d-v"><?php echo htmlspecialchars($container['notes']); ?></div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Actions -->
    <div class="ar-actions">
        <a href="/v1/pages/containers.php" class="ar-act-secondary"><i class="fas fa-arrow-left"></i> กลับ</a>
        <?php if ($sdsUrl): ?>
        <a href="<?php echo htmlspecialchars($sdsUrl); ?>" target="_blank" class="ar-act-secondary"><i class="fas fa-file-pdf"></i> SDS</a>
        <?php endif; ?>
        <?php if ($hasModel && $modelType === 'glb'): ?>
        <button id="btnAR" class="ar-act-ar"><i class="fas fa-vr-cardboard"></i> AR</button>
        <button id="btnARSpatial" class="ar-act-spatial"><i class="fas fa-cube"></i> AR เชิงพื้นที่</button>
        <?php endif; ?>
        <?php $detailId = $isStock ? -(int)$container['id'] : (int)$container['id']; ?>
        <a href="/v1/pages/containers.php" onclick="event.preventDefault();openDetail(<?php echo $detailId; ?>)" class="ar-act-primary"><i class="fas fa-info-circle"></i> รายละเอียด</a>
    </div>
</div>

<!-- AR Launch FAB (visible when card is minimized) -->
<?php if ($hasModel && $modelType === 'glb'): ?>
<button class="ar-launch" id="arFab" title="เปิด AR"><i class="fas fa-vr-cardboard"></i></button>
<?php endif; ?>

<script>
// ═══ Card Toggle ═══
const card = document.getElementById('arCard');
const handle = document.getElementById('cardHandle');
handle.addEventListener('click', () => card.classList.toggle('minimized'));

// Swipe to minimize/expand
let startY = 0;
card.addEventListener('touchstart', e => { startY = e.touches[0].clientY; }, {passive:true});
card.addEventListener('touchend', e => {
    const dy = e.changedTouches[0].clientY - startY;
    if (dy > 60) card.classList.add('minimized');
    else if (dy < -60) card.classList.remove('minimized');
});

// ═══ Share ═══
document.getElementById('btnShare').addEventListener('click', () => {
    const data = {
        title: <?php echo json_encode($chemName, JSON_UNESCAPED_UNICODE); ?>,
        text: 'Chemical Container: ' + <?php echo json_encode($chemName . ' (' . $bottleCode . ')', JSON_UNESCAPED_UNICODE); ?>,
        url: window.location.href
    };
    if (navigator.share) navigator.share(data);
    else { navigator.clipboard.writeText(window.location.href); alert('คัดลอกลิงก์แล้ว!'); }
});

// ═══ AR Launch ═══
<?php if ($hasModel && $modelType === 'glb'): ?>
function launchAR() {
    const mv = document.getElementById('model-viewer');
    if (mv && mv.canActivateAR) {
        mv.activateAR();
    } else {
        alert('อุปกรณ์นี้ไม่รองรับ AR\nกรุณาใช้โทรศัพท์มือถือที่รองรับ ARCore/ARKit');
    }
}
document.getElementById('btnAR')?.addEventListener('click', launchAR);
document.getElementById('arFab')?.addEventListener('click', launchAR);

// AR Status Banner
(function() {
    const mv = document.getElementById('model-viewer');
    if (!mv) return;
    
    const banner = document.createElement('div');
    banner.style.cssText = 'position:fixed;top:100px;left:50%;transform:translateX(-50%);z-index:9999;display:none;align-items:center;gap:8px;padding:8px 18px;border-radius:12px;background:rgba(0,0,0,.85);backdrop-filter:blur(16px);border:1px solid rgba(255,255,255,.1);font-size:12px;font-weight:600;color:#fff;white-space:nowrap';
    document.body.appendChild(banner);
    
    let anchored = false;
    mv.addEventListener('ar-status', e => {
        switch (e.detail) {
            case 'session-started':
                anchored = false;
                banner.innerHTML = '<span style="width:7px;height:7px;border-radius:50%;background:#fbbf24;flex-shrink:0"></span> สแกนพื้นผิว — เลื่อนกล้องช้าๆ';
                banner.style.display = 'flex';
                break;
            case 'object-placed':
                banner.innerHTML = '<span style="width:7px;height:7px;border-radius:50%;background:#22c55e;flex-shrink:0"></span> ✅ วางวัตถุแล้ว';
                setTimeout(() => {
                    anchored = true;
                    banner.innerHTML = '<span style="width:7px;height:7px;border-radius:50%;background:#a78bfa;flex-shrink:0"></span> 🔒 Spatial Anchor ยึดตำแหน่ง';
                }, 600);
                break;
            case 'failed':
            case 'not-presenting':
                banner.style.display = 'none';
                anchored = false;
                break;
        }
    });
    mv.addEventListener('ar-tracking', e => {
        if (e.detail === 'tracking' && !anchored) {
            banner.innerHTML = '<span style="width:7px;height:7px;border-radius:50%;background:#fbbf24;flex-shrink:0"></span> ตรวจจับพื้นผิวแล้ว — แตะเพื่อวาง';
        }
    });
})();
<?php endif; ?>

// ═══ AR Advanced - Full Featured AR with Spatial Anchors ═══
<?php if ($hasModel && $modelType === 'glb'): ?>
function openArSpatial() {
    const modelUrl = <?php echo json_encode($modelUrl); ?>;
    const chemName = <?php echo json_encode($chemName, JSON_UNESCAPED_UNICODE); ?>;
    const casNo = <?php echo json_encode($casNumber ?? '', JSON_UNESCAPED_UNICODE); ?>;
    const signalWord = <?php echo json_encode($signalWord ?? '', JSON_UNESCAPED_UNICODE); ?>;

    const params = new URLSearchParams({
        src: modelUrl,
        title: chemName,
        chem_name: chemName,
        cas: casNo,
        signal: signalWord
    });

    window.location.href = '/v1/ar/ar_spatial.php?' + params.toString();
}

document.getElementById('btnHeaderARSpatial')?.addEventListener('click', openArSpatial);
document.getElementById('btnARSpatial')?.addEventListener('click', openArSpatial);
<?php endif; ?>

// ═══ openDetail link back to containers page ═══
function openDetail(id) {
    window.location.href = '/v1/pages/containers.php#detail-' + id;
}
</script>
</body>
</html>
