/* ═══════════════════════════════════════════════════════════════════════
   user_chat.js — Rewind Studio Chat
   Bot ALWAYS replies instantly. Admin can add on top anytime.
═══════════════════════════════════════════════════════════════════════ */

const box     = document.getElementById('chatBox');
const form    = document.getElementById('chatForm');
const input   = document.getElementById('msgInput');
const sendBtn = document.getElementById('sendBtn');
const typing  = document.getElementById('typingIndicator');

/* ── Scroll to bottom ────────────────────────────────────────────────── */
function scrollBottom(smooth = false) {
    box.scrollTo({ top: box.scrollHeight, behavior: smooth ? 'smooth' : 'instant' });
}
scrollBottom();

/* ── Auto-grow textarea ──────────────────────────────────────────────── */
input.addEventListener('input', () => {
    input.style.height = 'auto';
    input.style.height = Math.min(input.scrollHeight, 140) + 'px';
});

/* ── Send on Ctrl/Cmd+Enter ──────────────────────────────────────────── */
input.addEventListener('keydown', e => {
    if ((e.ctrlKey || e.metaKey) && e.key === 'Enter') {
        e.preventDefault();
        form.dispatchEvent(new Event('submit'));
    }
});

/* ── Escape HTML ─────────────────────────────────────────────────────── */
function escHtml(str) {
    return String(str)
        .replace(/&/g, '&amp;').replace(/</g, '&lt;')
        .replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}

/* ── Format time as g:i A ────────────────────────────────────────────── */
function nowTime() {
    const d = new Date();
    let h = d.getHours(), m = d.getMinutes();
    const ampm = h >= 12 ? 'PM' : 'AM';
    h = h % 12 || 12;
    return `${h}:${String(m).padStart(2, '0')} ${ampm}`;
}

/* ── Render a bubble ─────────────────────────────────────────────────── */
function appendBubble({ message, sender, label, time, isBot = false }) {
    const empty = box.querySelector('.chat-empty');
    if (empty) empty.remove();

    const isUser = sender === 'user';
    const row    = document.createElement('div');
    row.className = `bubble-row ${isUser ? 'bubble-row--user' : 'bubble-row--admin'}`;

    const avatarHTML = !isUser
        ? `<div class="bubble-avatar ${isBot ? 'bubble-avatar--bot' : ''}" aria-hidden="true">
               ${isBot ? '<i class="fas fa-robot"></i>' : 'RS'}
           </div>`
        : '';

    const bubbleClass = isUser ? 'bubble--user' : (isBot ? 'bubble--bot' : 'bubble--admin');

    row.innerHTML = `
        ${avatarHTML}
        <div class="bubble ${bubbleClass}">
            ${escHtml(message).replace(/\n/g, '<br>')}
            <span class="bubble-meta">
                ${escHtml(label)} &middot; ${escHtml(time)}
            </span>
        </div>
    `;

    box.insertBefore(row, typing);
    scrollBottom(true);
}

/* ── Typing indicator ────────────────────────────────────────────────── */
function showTyping() { typing.style.display = 'flex'; scrollBottom(true); }
function hideTyping()  { typing.style.display = 'none'; }

/* ── Track current CSRF (rotated after each save) ───────────────────── */
let currentCsrf = form.querySelector('[name="csrf_token"]').value;

/* ── Main submit ─────────────────────────────────────────────────────── */
form.addEventListener('submit', async e => {
    e.preventDefault();

    const msgText = input.value.trim();
    if (!msgText) return;

    /* Disable while processing */
    input.disabled  = true;
    sendBtn.disabled = true;

    /* Render user bubble immediately */
    appendBubble({ message: msgText, sender: 'user', label: 'You', time: nowTime() });
    input.value = '';
    input.style.height = 'auto';

    try {
        /* 1. Save message, get its DB id back */
        const saveResp = await fetch('save_message.php', {
            method:  'POST',
            headers: { 'Content-Type': 'application/json' },
            body:    JSON.stringify({
                csrf_token:  currentCsrf,
                booking_id:  BOOKING_ID,
                message:     msgText,
            }),
        });

        const saveData = await saveResp.json();

        if (!saveData.success) {
            console.error('Save failed:', saveData.error);
            input.disabled  = false;
            sendBtn.disabled = false;
            return;
        }

        /* Update CSRF for next send */
        if (saveData.new_csrf) currentCsrf = saveData.new_csrf;
        form.querySelector('[name="csrf_token"]').value = currentCsrf;

        /* 2. Always call the bot — it's a first responder, not a fallback */
        showTyping();

        /* Small human-feel delay (0.6 – 1.8s) */
        await new Promise(r => setTimeout(r, 600 + Math.random() * 1200));

        const botResp = await fetch('chatbot_reply.php', {
            method:  'POST',
            headers: { 'Content-Type': 'application/json' },
            body:    JSON.stringify({
                message:    msgText,
                booking_id: BOOKING_ID,
                message_id: saveData.message_id,
            }),
        });

        hideTyping();

        const botData = await botResp.json();

        if (botData.success) {
            appendBubble({
                message: botData.reply,
                sender:  'admin',
                label:   'AI Assistant',
                time:    botData.time,
                isBot:   true,
            });
        }

    } catch (err) {
        hideTyping();
        console.error('Chat error:', err);
    }

    input.disabled  = false;
    sendBtn.disabled = false;
    input.focus();
});