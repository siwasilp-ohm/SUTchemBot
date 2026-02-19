<?php
require_once __DIR__ . '/../includes/layout.php';
$user = Auth::getCurrentUser();
if (!$user) { header('Location: /v1/pages/login.php'); exit; }
$lang = I18n::getCurrentLang();
Layout::head(__('ai_title'));
?>
<style>
.chat-msg{animation:slideIn .3s ease-out}
@keyframes slideIn{from{opacity:0;transform:translateY(10px)}to{opacity:1;transform:translateY(0)}}
.typing-dot{animation:blink 1.4s infinite both}.typing-dot:nth-child(2){animation-delay:.2s}.typing-dot:nth-child(3){animation-delay:.4s}
@keyframes blink{0%,100%{opacity:.2}50%{opacity:1}}
</style>
<body>
<?php Layout::sidebar('ai-assistant'); Layout::beginContent(); ?>

<div class="ci-card ci-chat-card" style="display:flex;flex-direction:column;height:calc(100vh - 108px);overflow:hidden">
    <div class="ci-card-head">
        <div style="display:flex;align-items:center;gap:10px">
            <div style="width:36px;height:36px;background:linear-gradient(135deg,var(--accent),#7b1fa2);border-radius:10px;display:flex;align-items:center;justify-content:center;color:#fff"><i class="fas fa-robot"></i></div>
            <div><strong><?php echo __('ai_title'); ?></strong><p style="font-size:11px;color:var(--ok);display:flex;align-items:center;gap:4px"><span style="width:6px;height:6px;background:var(--ok);border-radius:50%;display:inline-block"></span> <?php echo __('online'); ?></p></div>
        </div>
    </div>
    <div id="chatMessages" style="flex:1;overflow-y:auto;padding:16px;display:flex;flex-direction:column;gap:12px">
        <div class="chat-msg" style="display:flex;gap:10px">
            <div style="width:32px;height:32px;background:linear-gradient(135deg,var(--accent),#7b1fa2);border-radius:8px;display:flex;align-items:center;justify-content:center;color:#fff;flex-shrink:0;font-size:12px"><i class="fas fa-robot"></i></div>
            <div style="background:#f5f5f5;border-radius:12px;border-top-left-radius:0;padding:14px;max-width:80%">
                <p style="font-size:13px;color:#333"><?php echo __('ai_welcome'); ?></p>
                <ul style="margin-top:8px;font-size:13px;color:#666;list-style:none;display:flex;flex-direction:column;gap:4px">
                    <li>🔍 <?php echo __('ai_help_find'); ?></li>
                    <li>📄 <?php echo __('ai_help_sds'); ?></li>
                    <li>⚠️ <?php echo __('ai_help_expiry'); ?></li>
                    <li>📦 <?php echo __('ai_help_borrow'); ?></li>
                    <li>🛡️ <?php echo __('ai_help_safety'); ?></li>
                </ul>
            </div>
        </div>
    </div>
    <div style="padding:10px 16px;border-top:1px solid var(--border);background:#fafafa">
        <p style="font-size:11px;color:#aaa;margin-bottom:6px"><?php echo __('ai_suggestions'); ?>:</p>
        <div style="display:flex;gap:6px;overflow-x:auto;padding-bottom:4px">
            <button onclick="sendSuggestion('Where is HCl?')" class="ci-btn ci-btn-secondary ci-btn-sm" style="white-space:nowrap">Where is HCl?</button>
            <button onclick="sendSuggestion('Show expiring chemicals')" class="ci-btn ci-btn-secondary ci-btn-sm" style="white-space:nowrap">Expiring chemicals</button>
            <button onclick="sendSuggestion('What chemicals are low in stock?')" class="ci-btn ci-btn-secondary ci-btn-sm" style="white-space:nowrap">Low stock</button>
            <button onclick="sendSuggestion('SDS for ethanol')" class="ci-btn ci-btn-secondary ci-btn-sm" style="white-space:nowrap">SDS for ethanol</button>
        </div>
    </div>
    <div style="padding:12px 16px;border-top:1px solid var(--border);display:flex;gap:10px">
        <input type="text" id="msgInput" class="ci-input" placeholder="<?php echo __('ai_placeholder'); ?>" onkeypress="if(event.key==='Enter')sendMsg()">
        <button onclick="sendMsg()" class="ci-btn ci-btn-primary" style="padding:8px 18px"><i class="fas fa-paper-plane"></i></button>
    </div>
</div>

<?php Layout::endContent(); ?>
<style>
@media(max-width:768px){
    .ci-chat-card{height:calc(100vh - 60px - 48px - 28px)!important}
    .ci-chat-card .ci-card-head{padding:8px 10px!important}
    #chatMessages{padding:8px!important}
    #chatMessages>div>div:last-child{max-width:92%!important}
}
@media(max-width:480px){
    .ci-chat-card{height:calc(100vh - 60px - 48px - 20px)!important}
}
</style>
<script>
let session=null,typing=false;
function sendSuggestion(t){document.getElementById('msgInput').value=t;sendMsg()}
async function sendMsg(){
    const inp=document.getElementById('msgInput'),msg=inp.value.trim();if(!msg||typing)return;
    addMsg(msg,'user');inp.value='';showTyping();
    try{
        const d=await apiFetch('/v1/api/ai_assistant.php',{method:'POST',body:JSON.stringify({action:'chat',message:msg,session_id:session})});
        hideTyping();
        if(d.success){session=d.data.session_id;addMsg(d.data.response,'bot',d.data)}
        else addMsg('<?php echo $lang==="th"?"ขออภัย เกิดข้อผิดพลาด กรุณาลองใหม่":"Sorry, an error occurred. Please try again."; ?>','bot');
    }catch(e){hideTyping();addMsg('<?php echo $lang==="th"?"ไม่สามารถเชื่อมต่อได้":"Unable to connect. Please try again."; ?>','bot')}
}
function addMsg(text,role,data=null){
    const c=document.getElementById('chatMessages'),div=document.createElement('div');
    div.className='chat-msg';div.style.cssText='display:flex;gap:10px'+(role==='user'?';flex-direction:row-reverse':'');
    if(role==='user'){
        div.innerHTML=`<div style="width:32px;height:32px;background:var(--accent);border-radius:8px;display:flex;align-items:center;justify-content:center;color:#fff;flex-shrink:0;font-size:12px"><i class="fas fa-user"></i></div><div style="background:var(--accent);color:#fff;border-radius:12px;border-top-right-radius:0;padding:14px;max-width:80%"><p style="font-size:13px">${esc(text)}</p></div>`;
    }else{
        let actions='';
        if(data?.actions){actions='<div style="margin-top:10px;display:flex;flex-wrap:wrap;gap:6px">'+data.actions.map(a=>`<button onclick="handleAction('${a.type}')" class="ci-btn ci-btn-outline ci-btn-sm">${a.label}</button>`).join('')+'</div>'}
        div.innerHTML=`<div style="width:32px;height:32px;background:linear-gradient(135deg,var(--accent),#7b1fa2);border-radius:8px;display:flex;align-items:center;justify-content:center;color:#fff;flex-shrink:0;font-size:12px"><i class="fas fa-robot"></i></div><div style="background:#f5f5f5;border-radius:12px;border-top-left-radius:0;padding:14px;max-width:80%"><p style="font-size:13px;color:#333;white-space:pre-line">${esc(text)}</p>${actions}</div>`;
    }
    c.appendChild(div);c.scrollTop=c.scrollHeight;
}
function showTyping(){typing=true;const c=document.getElementById('chatMessages'),d=document.createElement('div');d.id='typingEl';d.className='chat-msg';d.style.cssText='display:flex;gap:10px';d.innerHTML=`<div style="width:32px;height:32px;background:linear-gradient(135deg,var(--accent),#7b1fa2);border-radius:8px;display:flex;align-items:center;justify-content:center;color:#fff;flex-shrink:0;font-size:12px"><i class="fas fa-robot"></i></div><div style="background:#f5f5f5;border-radius:12px;border-top-left-radius:0;padding:14px"><div style="display:flex;gap:4px"><span class="typing-dot" style="width:8px;height:8px;background:#aaa;border-radius:50%"></span><span class="typing-dot" style="width:8px;height:8px;background:#aaa;border-radius:50%"></span><span class="typing-dot" style="width:8px;height:8px;background:#aaa;border-radius:50%"></span></div></div>`;c.appendChild(d);c.scrollTop=c.scrollHeight}
function hideTyping(){typing=false;document.getElementById('typingEl')?.remove()}
function handleAction(type){const m={navigate:'/v1/pages/locations.php',search_chemical:'/v1/pages/chemicals.php',view_sds:'/v1/pages/chemicals.php'};if(m[type])window.location.href=m[type]}
function esc(t){const d=document.createElement('div');d.textContent=t;return d.innerHTML}
</script>
</body></html>
