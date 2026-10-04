/**
 * محادثة أنيس الروح — جلب وإرسال
 */
(function () {
    const box = document.getElementById('chat612Messages');
    const form = document.getElementById('chat612Form');
    const input = document.getElementById('chat612Input');
    const endBtn = document.getElementById('chat612EndBtn');
    if (!box || !form || !input) return;

    const peer = box.getAttribute('data-peer');
    const me = box.getAttribute('data-me');
    const myType = box.getAttribute('data-my-type');

    /** فقاعة بترولية للمتطوع، وبني فاتح لكبير السن */
    function bubbleClassForMessage(m) {
        const mine = String(m.sender_id) === String(me);
        if (myType === 'volunteer') {
            return mine ? 'chat-bubble-vol612' : 'chat-bubble-senior612';
        }
        return mine ? 'chat-bubble-senior612' : 'chat-bubble-vol612';
    }

    function render(messages) {
        box.innerHTML = messages
            .map(
                (m) => `
            <div class="chat-row612 ${String(m.sender_id) === String(me) ? 'mine612' : 'theirs612'}">
                <div class="chat-bubble612 ${bubbleClassForMessage(m)}">
                    <div class="chat-text612">${escapeHtml(m.message)}</div>
                    <div class="chat-time612">${formatTime(m.ts)}</div>
                </div>
            </div>`
            )
            .join('');
        box.scrollTop = box.scrollHeight;
    }

    function escapeHtml(s) {
        const d = document.createElement('div');
        d.textContent = s || '';
        return d.innerHTML;
    }

    function formatTime(ts) {
        if (!ts) return '';
        try {
            const d = new Date(ts.replace(' ', 'T'));
            return d.toLocaleString('ar-SA', { hour: '2-digit', minute: '2-digit', day: 'numeric', month: 'short' });
        } catch (e) {
            return ts;
        }
    }

    async function load() {
        try {
            const res = await fetch('api_chat612.php?peer=' + encodeURIComponent(peer), { credentials: 'same-origin' });
            const data = await res.json();
            if (data.ok && data.messages) {
                render(data.messages);
            }
        } catch (e) { /* silent */ }
    }

    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        const text = input.value.trim();
        if (!text) return;
        const fd = new FormData();
        fd.append('peer_id', peer);
        fd.append('message', text);
        try {
            const res = await fetch('api_chat612.php', { method: 'POST', body: fd, credentials: 'same-origin' });
            const data = await res.json();
            if (data.ok) {
                input.value = '';
                load();
            }
        } catch (err) { /* ignore */ }
    });

    if (endBtn) {
        endBtn.addEventListener('click', () => {
            if (confirm('إغلاق نافذة المحادثة والعودة للوحة التحكم؟')) {
                window.location.href = 'dashboard612.php';
            }
        });
    }

    load();
    setInterval(load, 7000);
})();
