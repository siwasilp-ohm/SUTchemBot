<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
<meta name="apple-mobile-web-app-capable" content="yes">
<title>AR Spatial Viewer — SUT chemBot</title>

<!-- Google model-viewer with WebXR spatial anchoring -->
<script type="module" src="https://ajax.googleapis.com/ajax/libs/model-viewer/3.4.0/model-viewer.min.js"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<style>
* { margin: 0; padding: 0; box-sizing: border-box; }
body { background: #000; color: #fff; font-family: 'Inter', -apple-system, sans-serif; overflow: hidden; height: 100vh; height: 100dvh; }

/* ═══ Model Viewer ═══ */
model-viewer {
    width: 100%; height: 100vh; height: 100dvh; display: block;
    --poster-color: transparent;
    --progress-bar-color: #6C5CE7;
    --progress-bar-height: 3px;
    background: radial-gradient(ellipse at center, #1a1a3a 0%, #0a0a1a 100%);
}
model-viewer::part(default-ar-button) { display: none; }

/* ═══ Loading Overlay ═══ */
#loadOverlay {
    position: fixed; inset: 0; z-index: 100;
    background: radial-gradient(ellipse at center, #1a1a3a 0%, #0a0a1a 100%);
    display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 20px;
    transition: opacity .5s;
}
#loadOverlay.hidden { opacity: 0; pointer-events: none; }
.ld-ring { width: 56px; height: 56px; position: relative; display: inline-block; }
.ld-ring div { position: absolute; width: 48px; height: 48px; margin: 4px; border: 3px solid transparent; border-radius: 50%; animation: ldSpin 1.2s cubic-bezier(.5,0,.5,1) infinite; border-top-color: #6C5CE7; }
.ld-ring div:nth-child(1) { animation-delay: -.45s; border-top-color: #a78bfa; }
.ld-ring div:nth-child(2) { animation-delay: -.3s; }
.ld-ring div:nth-child(3) { animation-delay: -.15s; border-top-color: #4c3fad; }
@keyframes ldSpin { 0%{transform:rotate(0)} 100%{transform:rotate(360deg)} }
#loadOverlay p { font-size: 14px; color: #888; font-weight: 500; }

/* ═══ Top Header Bar ═══ */
.sp-header {
    position: fixed; top: 0; left: 0; right: 0; z-index: 50;
    display: flex; align-items: center; justify-content: space-between;
    padding: 12px 16px;
    background: linear-gradient(to bottom, rgba(0,0,0,.7) 0%, transparent 100%);
    pointer-events: none;
}
.sp-header > * { pointer-events: auto; }
.sp-btn {
    width: 40px; height: 40px; border-radius: 12px; border: none;
    background: rgba(255,255,255,.12); backdrop-filter: blur(12px);
    color: #fff; font-size: 16px; display: flex; align-items: center; justify-content: center;
    cursor: pointer; transition: all .15s;
}
.sp-btn:active { transform: scale(.92); }
.sp-header-info { text-align: center; flex: 1; min-width: 0; }
.sp-header-title { font-size: 14px; font-weight: 700; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.sp-header-sub { font-size: 10px; color: rgba(255,255,255,.5); font-family: monospace; }

/* ═══ AR Status Banner ═══ */
.sp-ar-status {
    position: fixed; top: 70px; left: 50%; transform: translateX(-50%); z-index: 50;
    display: none; align-items: center; gap: 8px;
    padding: 10px 20px; border-radius: 14px;
    background: rgba(0,0,0,.75); backdrop-filter: blur(16px);
    border: 1px solid rgba(255,255,255,.08);
    font-size: 13px; font-weight: 600; white-space: nowrap;
    animation: spSlideDown .4s ease;
}
@keyframes spSlideDown { from { opacity:0; transform:translateX(-50%) translateY(-20px); } to { opacity:1; transform:translateX(-50%) translateY(0); } }
.sp-ar-status.show { display: flex; }
.sp-ar-status .sp-dot { width: 8px; height: 8px; border-radius: 50%; flex-shrink: 0; }
.sp-dot-scan { background: #fbbf24; animation: spPulse 1s ease-in-out infinite; }
.sp-dot-placed { background: #22c55e; }
.sp-dot-anchored { background: #6C5CE7; animation: spPulse 1.5s ease-in-out infinite; }
@keyframes spPulse { 0%,100%{opacity:1;transform:scale(1)} 50%{opacity:.5;transform:scale(.7)} }

/* ═══ Placement Reticle ═══ */
.sp-reticle {
    position: fixed; top: 50%; left: 50%; transform: translate(-50%,-50%); z-index: 40;
    width: 80px; height: 80px; display: none;
    pointer-events: none;
}
.sp-reticle.show { display: block; }
.sp-reticle-ring {
    width: 100%; height: 100%; border: 2px solid rgba(108,92,231,.6);
    border-radius: 50%; position: relative;
    animation: spReticle 2s ease-in-out infinite;
}
.sp-reticle-ring::before {
    content: ''; position: absolute; top: 50%; left: 50%; transform: translate(-50%,-50%);
    width: 8px; height: 8px; background: #6C5CE7; border-radius: 50%;
}
.sp-reticle-ring::after {
    content: ''; position: absolute; top: 50%; left: 50%; transform: translate(-50%,-50%);
    width: 40px; height: 40px; border: 1px dashed rgba(108,92,231,.3); border-radius: 50%;
}
@keyframes spReticle { 0%,100%{transform:scale(1);opacity:1} 50%{transform:scale(1.1);opacity:.7} }

/* ═══ Bottom Control Panel ═══ */
.sp-controls {
    position: fixed; bottom: 0; left: 0; right: 0; z-index: 50;
    background: linear-gradient(to top, rgba(0,0,0,.85) 0%, rgba(0,0,0,.5) 70%, transparent 100%);
    padding: 0 16px 20px; pointer-events: none;
}
.sp-controls > * { pointer-events: auto; }

/* Chemical Card */
.sp-chem-card {
    display: flex; align-items: center; gap: 12px;
    background: rgba(255,255,255,.06); backdrop-filter: blur(12px);
    border: 1px solid rgba(255,255,255,.08); border-radius: 14px;
    padding: 12px 16px; margin-bottom: 10px;
}
.sp-chem-icon { width: 42px; height: 42px; border-radius: 10px; background: rgba(108,92,231,.15); display: flex; align-items: center; justify-content: center; font-size: 18px; color: #a78bfa; flex-shrink: 0; }
.sp-chem-info { flex: 1; min-width: 0; }
.sp-chem-name { font-size: 13px; font-weight: 700; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.sp-chem-cas { font-size: 11px; color: rgba(255,255,255,.4); font-family: monospace; }
.sp-chem-tags { display: flex; gap: 4px; }
.sp-tag { padding: 3px 8px; border-radius: 6px; font-size: 9px; font-weight: 700; }
.sp-tag-danger { background: rgba(220,38,38,.2); color: #fca5a5; }
.sp-tag-warning { background: rgba(217,119,6,.2); color: #fcd34d; }

/* Action Bar */
.sp-action-bar { display: flex; gap: 6px; align-items: center; }
.sp-action-btn {
    flex: 1; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 3px;
    padding: 12px 4px; border-radius: 14px;
    border: 1px solid rgba(255,255,255,.06); background: rgba(255,255,255,.04);
    backdrop-filter: blur(8px); color: #ccc; cursor: pointer;
    transition: all .2s; text-align: center;
}
.sp-action-btn:active { transform: scale(.94); }
.sp-action-btn.active { background: rgba(108,92,231,.2); color: #a78bfa; border-color: rgba(108,92,231,.3); }
.sp-action-btn i { font-size: 18px; }
.sp-action-btn span { font-size: 10px; font-weight: 600; }

/* AR Launch Button (main) */
.sp-ar-main-btn {
    width: 64px; height: 64px; border-radius: 50%; flex-shrink: 0;
    background: linear-gradient(135deg, #059669, #10b981);
    border: 3px solid rgba(255,255,255,.15);
    color: #fff; font-size: 24px;
    display: flex; align-items: center; justify-content: center;
    cursor: pointer; transition: all .2s;
    box-shadow: 0 4px 20px rgba(5,150,105,.4);
}
.sp-ar-main-btn:active { transform: scale(.92); }
.sp-ar-main-btn.placed {
    background: linear-gradient(135deg, #6C5CE7, #a78bfa);
    box-shadow: 0 4px 20px rgba(108,92,231,.4);
}

/* Anchor Info Pill */
.sp-anchor-pill {
    display: none; align-items: center; gap: 6px; justify-content: center;
    padding: 6px 14px; margin-top: 8px; border-radius: 10px;
    background: rgba(108,92,231,.12); border: 1px solid rgba(108,92,231,.2);
    font-size: 11px; font-weight: 600; color: #a78bfa;
}
.sp-anchor-pill.show { display: flex; }

/* Scale slider */
.sp-scale-wrap {
    display: none; align-items: center; gap: 8px; margin-top: 8px;
    padding: 8px 14px; background: rgba(255,255,255,.04); border-radius: 10px;
}
.sp-scale-wrap.show { display: flex; }
.sp-scale-wrap label { font-size: 10px; color: #888; font-weight: 600; white-space: nowrap; }
.sp-scale-wrap input[type=range] { flex: 1; accent-color: #6C5CE7; height: 4px; }
.sp-scale-val { font-size: 11px; color: #a78bfa; font-weight: 700; min-width: 32px; text-align: right; }

/* ═══ Instructions Overlay ═══ */
.sp-instruct {
    position: fixed; bottom: 200px; left: 50%; transform: translateX(-50%); z-index: 45;
    display: none; flex-direction: column; align-items: center; gap: 8px;
    padding: 16px 24px; border-radius: 16px;
    background: rgba(0,0,0,.7); backdrop-filter: blur(16px);
    border: 1px solid rgba(255,255,255,.06);
    animation: spBounce 2s ease-in-out infinite;
    pointer-events: none;
}
.sp-instruct.show { display: flex; }
@keyframes spBounce { 0%,100%{transform:translateX(-50%) translateY(0)} 50%{transform:translateX(-50%) translateY(-8px)} }
.sp-instruct i { font-size: 28px; color: #a78bfa; }
.sp-instruct p { font-size: 12px; color: #ccc; text-align: center; max-width: 200px; }

/* ═══ Toast ═══ */
.sp-toast {
    position: fixed; top: 120px; left: 50%; transform: translateX(-50%); z-index: 200;
    padding: 10px 20px; border-radius: 12px;
    background: rgba(0,0,0,.85); backdrop-filter: blur(16px);
    border: 1px solid rgba(255,255,255,.06);
    font-size: 13px; font-weight: 500; color: #fff;
    opacity: 0; transition: opacity .3s; pointer-events: none;
}
.sp-toast.show { opacity: 1; }
</style>
</head>
<body>

<!-- Loading -->
<div id="loadOverlay">
    <div class="ld-ring"><div></div><div></div><div></div></div>
    <p>กำลังโหลดโมเดล 3D...</p>
</div>

<!-- Model Viewer -->
<model-viewer id="mainViewer"
    camera-controls
    auto-rotate
    touch-action="pan-y"
    interaction-prompt="auto"
    shadow-intensity="1"
    shadow-softness="0.8"
    exposure="1.1"
    environment-image="neutral"
    tone-mapping="commerce"
    ar
    ar-modes="webxr scene-viewer quick-look"
    ar-scale="auto"
    ar-placement="floor"
    xr-environment
>
    <button slot="ar-button" style="display:none"></button>
</model-viewer>

<!-- Header -->
<div class="sp-header">
    <button class="sp-btn" onclick="goBack()" title="กลับ"><i class="fas fa-arrow-left"></i></button>
    <div class="sp-header-info">
        <div class="sp-header-title" id="hdrTitle">โมเดล 3D</div>
        <div class="sp-header-sub" id="hdrSub"></div>
    </div>
    <button class="sp-btn" onclick="toggleFullscreen()" title="เต็มจอ"><i class="fas fa-expand"></i></button>
</div>

<!-- AR Status Banner -->
<div class="sp-ar-status" id="arStatus">
    <div class="sp-dot" id="arStatusDot"></div>
    <span id="arStatusText"></span>
</div>

<!-- Placement Reticle (shown during AR hit-test) -->
<div class="sp-reticle" id="reticle">
    <div class="sp-reticle-ring"></div>
</div>

<!-- Instructions Overlay -->
<div class="sp-instruct" id="arInstruct">
    <i class="fas fa-mobile-alt"></i>
    <p id="arInstructText">เลื่อนอุปกรณ์ช้าๆ เพื่อสแกนพื้นผิว</p>
</div>

<!-- Bottom Controls -->
<div class="sp-controls">
    <!-- Chemical Info -->
    <div class="sp-chem-card" id="chemCard" style="display:none">
        <div class="sp-chem-icon"><i class="fas fa-flask"></i></div>
        <div class="sp-chem-info">
            <div class="sp-chem-name" id="chemName"></div>
            <div class="sp-chem-cas" id="chemCas"></div>
        </div>
        <div class="sp-chem-tags" id="chemTags"></div>
    </div>

    <!-- Action Bar -->
    <div class="sp-action-bar">
        <button class="sp-action-btn" id="btnRotate" onclick="toggleRotate()">
            <i class="fas fa-sync-alt"></i><span>หมุน</span>
        </button>
        <button class="sp-action-btn" id="btnScale" onclick="toggleScaleUI()">
            <i class="fas fa-expand-arrows-alt"></i><span>ขนาด</span>
        </button>
        <button class="sp-ar-main-btn" id="btnAR" onclick="startAR()">
            <i class="fas fa-vr-cardboard"></i>
        </button>
        <button class="sp-action-btn" id="btnAnchor" onclick="placeAnchor()" style="display:none">
            <i class="fas fa-anchor"></i><span>ยึด</span>
        </button>
        <button class="sp-action-btn" id="btnReset" onclick="resetView()">
            <i class="fas fa-crosshairs"></i><span>รีเซ็ต</span>
        </button>
    </div>

    <!-- Anchor Status Pill -->
    <div class="sp-anchor-pill" id="anchorPill">
        <i class="fas fa-anchor" style="font-size:10px"></i>
        <span id="anchorPillText">Spatial Anchor ยึดตำแหน่งแล้ว — เดินรอบวัตถุได้</span>
    </div>

    <!-- Scale Slider -->
    <div class="sp-scale-wrap" id="scaleWrap">
        <label><i class="fas fa-expand-arrows-alt"></i> ขนาด</label>
        <input type="range" id="scaleSlider" min="10" max="300" value="100" oninput="onScaleChange(this.value)">
        <span class="sp-scale-val" id="scaleVal">100%</span>
    </div>
</div>

<!-- Toast -->
<div class="sp-toast" id="toast"></div>

<script>
// ═══════ State ═══════
let arSession = null;
let arAnchor = null;
let arPlaced = false;
let autoRotate = true;
let modelScale = 1.0;
let modelSrc = '';
let modelLabel = '';
let chemName = '';
let chemCas = '';
let signalWord = '';

// ═══════ Init ═══════
(function init() {
    const params = new URLSearchParams(location.search);
    modelSrc = params.get('src') || '';
    modelLabel = params.get('title') || 'โมเดล 3D';
    chemName = params.get('chem_name') || '';
    chemCas = params.get('cas') || '';
    signalWord = params.get('signal') || '';

    if (!modelSrc) {
        document.getElementById('loadOverlay').innerHTML = '<i class="fas fa-exclamation-triangle" style="font-size:40px;color:#e17055;margin-bottom:12px"></i><p style="color:#e17055">ไม่ได้ระบุไฟล์โมเดล</p>';
        return;
    }

    // Set model
    const mv = document.getElementById('mainViewer');
    mv.setAttribute('src', modelSrc);
    mv.setAttribute('alt', modelLabel);

    // Header
    document.getElementById('hdrTitle').textContent = modelLabel;
    document.getElementById('hdrSub').textContent = modelSrc.split('/').pop()?.substring(0, 40) || '';

    // Chemical info
    if (chemName) {
        document.getElementById('chemName').textContent = chemName;
        document.getElementById('chemCas').textContent = chemCas ? 'CAS ' + chemCas : '';
        let tags = '';
        if (signalWord === 'Danger') tags = '<span class="sp-tag sp-tag-danger">⚠ DANGER</span>';
        else if (signalWord === 'Warning') tags = '<span class="sp-tag sp-tag-warning">⚠ WARNING</span>';
        document.getElementById('chemTags').innerHTML = tags;
        document.getElementById('chemCard').style.display = '';
    }

    // Events
    mv.addEventListener('load', () => {
        document.getElementById('loadOverlay').classList.add('hidden');
        // Check AR availability
        checkARSupport();
    });
    mv.addEventListener('error', () => {
        document.getElementById('loadOverlay').innerHTML = '<i class="fas fa-exclamation-triangle" style="font-size:36px;color:#e17055;margin-bottom:10px"></i><p style="color:#e17055">โหลดโมเดลไม่สำเร็จ</p>';
    });

    // AR session events
    mv.addEventListener('ar-status', (e) => {
        onArStatus(e.detail);
    });
    mv.addEventListener('ar-tracking', (e) => {
        onArTracking(e.detail);
    });

    // Set auto-rotate active
    document.getElementById('btnRotate').classList.add('active');

    // Timeout fallback
    setTimeout(() => {
        const ld = document.getElementById('loadOverlay');
        if (!ld.classList.contains('hidden')) ld.classList.add('hidden');
    }, 10000);
})();

// ═══════ AR Support Check ═══════
async function checkARSupport() {
    const mv = document.getElementById('mainViewer');
    const btnAR = document.getElementById('btnAR');
    
    // Check model-viewer's built-in AR
    if (mv.canActivateAR) {
        btnAR.style.display = '';
        return;
    }

    // Check WebXR immersive-ar support
    if (navigator.xr) {
        try {
            const supported = await navigator.xr.isSessionSupported('immersive-ar');
            if (supported) {
                btnAR.style.display = '';
                return;
            }
        } catch (e) {}
    }

    // Check Scene Viewer (Android) / Quick Look (iOS)
    const isAndroid = /android/i.test(navigator.userAgent);
    const isIOS = /iPad|iPhone|iPod/.test(navigator.userAgent);
    if (isAndroid || isIOS) {
        btnAR.style.display = '';
        return;
    }

    // No AR support
    btnAR.style.opacity = '0.3';
    btnAR.onclick = () => showToast('อุปกรณ์นี้ไม่รองรับ AR');
}

// ═══════ Start AR ═══════
async function startAR() {
    const mv = document.getElementById('mainViewer');

    // Try model-viewer's built-in AR first (handles WebXR + Scene Viewer + Quick Look)
    if (mv.canActivateAR) {
        setArStatus('scan', 'กำลังเปิด AR — สแกนพื้นผิว...');
        showInstruction('เลื่อนอุปกรณ์ช้าๆ เพื่อสแกนพื้นผิว\nแตะเพื่อวางวัตถุ');
        
        mv.activateAR();
        
        // Listen for AR session start
        mv.addEventListener('ar-status', handleArSessionStatus, { once: false });
        return;
    }

    // Fallback: try raw WebXR with anchors
    if (navigator.xr) {
        try {
            const supported = await navigator.xr.isSessionSupported('immersive-ar');
            if (supported) {
                startWebXRSession();
                return;
            }
        } catch (e) {
            console.warn('WebXR check failed:', e);
        }
    }

    // Last fallback: Scene Viewer / Quick Look
    launchNativeAR();
}

// ═══════ Handle model-viewer AR status ═══════
function handleArSessionStatus(e) {
    const status = e.detail;
    console.log('AR status:', status);
    
    switch (status) {
        case 'session-started':
            setArStatus('scan', 'สแกนพื้นผิว — เลื่อนกล้องช้าๆ');
            showInstruction('เลื่อนอุปกรณ์ช้าๆ เพื่อให้ระบบจดจำพื้นผิว');
            document.getElementById('btnAnchor').style.display = '';
            arPlaced = false;
            break;
            
        case 'object-placed':
            setArStatus('placed', 'วางวัตถุแล้ว — แตะเพื่อย้าย');
            hideInstruction();
            arPlaced = true;
            showToast('✅ วางวัตถุสำเร็จ — ลองเดินรอบดู!');
            // Auto-anchor after placement
            setTimeout(() => {
                createSpatialAnchor();
            }, 500);
            break;

        case 'failed':
            setArStatus('', '');
            hideInstruction();
            showToast('AR session จบ');
            document.getElementById('btnAnchor').style.display = 'none';
            break;

        case 'not-presenting':
            // AR session ended
            clearArUI();
            break;
    }
}

// ═══════ WebXR Raw Session with Anchors ═══════
async function startWebXRSession() {
    if (!navigator.xr) return;
    
    try {
        // Request features including anchors and hit-test
        const requiredFeatures = ['local-floor', 'hit-test'];
        const optionalFeatures = ['anchors', 'dom-overlay', 'light-estimation'];
        
        arSession = await navigator.xr.requestSession('immersive-ar', {
            requiredFeatures,
            optionalFeatures,
            domOverlay: { root: document.body }
        });
        
        setArStatus('scan', 'WebXR AR เปิดแล้ว — สแกนพื้นผิว');
        showInstruction('เลื่อนอุปกรณ์รอบๆ เพื่อสแกนพื้นผิว');
        
        arSession.addEventListener('end', () => {
            arSession = null;
            clearArUI();
            showToast('ออกจาก AR แล้ว');
        });

        // The model-viewer will handle the rendering, we just manage the anchor
        showToast('🔍 กำลังสแกนพื้นผิว...');
        
    } catch (e) {
        console.error('WebXR session failed:', e);
        // Fall back to native AR
        launchNativeAR();
    }
}

// ═══════ Create Spatial Anchor ═══════
function createSpatialAnchor() {
    const mv = document.getElementById('mainViewer');
    
    // model-viewer handles anchoring internally via WebXR hit-test
    // When ar-placement="floor" is set, it uses hit-test to place on real surfaces
    // The placed model is automatically anchored to the world coordinate
    
    arAnchor = true; // Track that we have an anchor
    setArStatus('anchored', '🔒 Spatial Anchor ยึดตำแหน่งแล้ว');
    
    document.getElementById('anchorPill').classList.add('show');
    document.getElementById('btnAR').classList.add('placed');
    document.getElementById('btnAnchor').classList.add('active');
    
    showToast('🔒 Spatial Anchor สร้างแล้ว — เดินรอบวัตถุได้!');
}

// ═══════ Manual Anchor Button ═══════
function placeAnchor() {
    if (arAnchor) {
        // Remove anchor
        arAnchor = null;
        arPlaced = false;
        document.getElementById('anchorPill').classList.remove('show');
        document.getElementById('btnAR').classList.remove('placed');
        document.getElementById('btnAnchor').classList.remove('active');
        setArStatus('scan', 'ยกเลิก Anchor — แตะเพื่อวางใหม่');
        showToast('ยกเลิก Anchor — วางวัตถุใหม่ได้');
    } else {
        createSpatialAnchor();
    }
}

// ═══════ Native AR (Scene Viewer / Quick Look) ═══════
function launchNativeAR() {
    if (!modelSrc) return;
    
    const isIOS = /iPad|iPhone|iPod/.test(navigator.userAgent);
    const fullUrl = modelSrc.startsWith('http') ? modelSrc : window.location.origin + modelSrc;
    
    if (isIOS) {
        // AR Quick Look
        const a = document.createElement('a');
        a.rel = 'ar';
        a.href = modelSrc;
        const img = document.createElement('img'); // Quick Look requires an img child
        a.appendChild(img);
        document.body.appendChild(a);
        a.click();
        setTimeout(() => a.remove(), 100);
        showToast('เปิด AR Quick Look...');
    } else {
        // Android Scene Viewer  
        const intentUrl = 'https://arvr.google.com/scene-viewer/1.0?' + new URLSearchParams({
            file: fullUrl,
            mode: 'ar_preferred',
            title: modelLabel,
            resizable: 'true',
            enable_vertical_placement: 'true'
        }).toString();
        
        // Scene Viewer supports anchoring natively
        const a = document.createElement('a');
        a.href = 'intent://arvr.google.com/scene-viewer/1.0?' + new URLSearchParams({
            file: fullUrl,
            mode: 'ar_preferred',
            title: modelLabel,
            resizable: 'true',
            enable_vertical_placement: 'true',
        }).toString() + '#Intent;scheme=https;package=com.google.android.googlequicksearchbox;action=android.intent.action.VIEW;S.browser_fallback_url=' + encodeURIComponent(intentUrl) + ';end;';
        
        document.body.appendChild(a);
        a.click();
        setTimeout(() => a.remove(), 100);
        showToast('เปิด Scene Viewer...');
    }
}

// ═══════ UI Helpers ═══════
function setArStatus(type, text) {
    const el = document.getElementById('arStatus');
    const dot = document.getElementById('arStatusDot');
    const txt = document.getElementById('arStatusText');
    
    if (!type) { el.classList.remove('show'); return; }
    
    dot.className = 'sp-dot sp-dot-' + type;
    txt.textContent = text;
    el.classList.add('show');
}

function showInstruction(text) {
    const el = document.getElementById('arInstruct');
    document.getElementById('arInstructText').textContent = text;
    el.classList.add('show');
}

function hideInstruction() {
    document.getElementById('arInstruct').classList.remove('show');
}

function clearArUI() {
    setArStatus('', '');
    hideInstruction();
    document.getElementById('reticle').classList.remove('show');
    document.getElementById('btnAnchor').style.display = 'none';
    if (!arAnchor) {
        document.getElementById('anchorPill').classList.remove('show');
        document.getElementById('btnAR').classList.remove('placed');
    }
}

function showToast(msg) {
    const t = document.getElementById('toast');
    t.textContent = msg;
    t.classList.add('show');
    setTimeout(() => t.classList.remove('show'), 3000);
}

// ═══════ Controls ═══════
function toggleRotate() {
    autoRotate = !autoRotate;
    const mv = document.getElementById('mainViewer');
    const btn = document.getElementById('btnRotate');
    if (autoRotate) { mv.setAttribute('auto-rotate', ''); btn.classList.add('active'); }
    else { mv.removeAttribute('auto-rotate'); btn.classList.remove('active'); }
}

function toggleScaleUI() {
    const wrap = document.getElementById('scaleWrap');
    const btn = document.getElementById('btnScale');
    const show = !wrap.classList.contains('show');
    wrap.classList.toggle('show', show);
    btn.classList.toggle('active', show);
}

function onScaleChange(val) {
    modelScale = val / 100;
    document.getElementById('scaleVal').textContent = val + '%';
    const mv = document.getElementById('mainViewer');
    mv.setAttribute('scale', modelScale + ' ' + modelScale + ' ' + modelScale);
}

function resetView() {
    const mv = document.getElementById('mainViewer');
    mv.cameraOrbit = 'auto auto auto';
    mv.cameraTarget = 'auto auto auto';
    mv.fieldOfView = 'auto';
    if (typeof mv.jumpCameraToGoal === 'function') mv.jumpCameraToGoal();
    
    // Reset scale
    modelScale = 1.0;
    document.getElementById('scaleSlider').value = 100;
    document.getElementById('scaleVal').textContent = '100%';
    mv.setAttribute('scale', '1 1 1');
    
    showToast('รีเซ็ตมุมมอง');
}

function toggleFullscreen() {
    if (!document.fullscreenElement) {
        document.documentElement.requestFullscreen().catch(() => {});
    } else {
        document.exitFullscreen().catch(() => {});
    }
}

function goBack() {
    if (window.opener || window.parent !== window) {
        window.close();
    } else {
        history.back();
    }
}
</script>
</body>
</html>
