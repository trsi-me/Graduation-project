/**
 * لوحة التحكم — تنبيه، مواعيد، قوائم متطوعين
 */
document.addEventListener('DOMContentLoaded', () => {
    const emergencyBtn = document.getElementById('emergencyBtn');
    const modalEmergency = document.getElementById('modalEmergency612');
    const alertMessageDiv = document.getElementById('alertMessage');

    function openModal(el) {
        if (!el) return;
        el.style.display = 'flex';
        document.body.style.overflow = 'hidden';
        if (el.id === 'modalEmergency612' && window.initMap612) {
            window.initMap612();
        }
    }

    function closeModal612(el) {
        if (!el) return;
        el.style.display = 'none';
        document.body.style.overflow = 'auto';
    }

    if (emergencyBtn && modalEmergency) {
        emergencyBtn.addEventListener('click', (e) => {
            e.preventDefault();
            openModal(modalEmergency);
        });
    }

    document.querySelectorAll('[data-close-modal612]').forEach((btn) => {
        btn.addEventListener('click', () => {
            const id = btn.getAttribute('data-close-modal612');
            closeModal612(document.getElementById(id));
        });
    });

    window.addEventListener('click', (e) => {
        if (e.target === modalEmergency) closeModal612(modalEmergency);
        const ap = document.getElementById('modalAppointment612');
        if (e.target === ap) closeModal612(ap);
    });

    const formHelp = document.getElementById('formEmergency612');
    if (formHelp) {
        formHelp.addEventListener('submit', async (e) => {
            e.preventDefault();
            const fd = new FormData(formHelp);
            try {
                const res = await fetch('api_help612.php', { method: 'POST', body: fd, credentials: 'same-origin' });
                const data = await res.json();
                if (data.ok) {
                    if (alertMessageDiv) {
                        alertMessageDiv.style.display = 'block';
                        alertMessageDiv.innerHTML = '<i class="fas fa-check-circle"></i> ' + (data.message || 'تم إرسال الطلب.');
                    }
                    closeModal612(modalEmergency);
                    formHelp.reset();
                    try {
                        const AudioContext = window.AudioContext || window.webkitAudioContext;
                        const ctx = new AudioContext();
                        const oscillator = ctx.createOscillator();
                        const gain = ctx.createGain();
                        oscillator.connect(gain);
                        gain.connect(ctx.destination);
                        oscillator.frequency.value = 880;
                        gain.gain.value = 0.15;
                        oscillator.start();
                        gain.gain.exponentialRampToValueAtTime(0.00001, ctx.currentTime + 1);
                        oscillator.stop(ctx.currentTime + 1);
                    } catch (err) { /* ignore */ }
                } else {
                    alert(data.error || 'تعذر الإرسال');
                }
            } catch (err) {
                alert('تعذر الاتصال بالخادم');
            }
        });
    }

    const shareLocBtn = document.getElementById('btnShareLocation612');
    if (shareLocBtn) {
        shareLocBtn.addEventListener('click', () => {
            if (!navigator.geolocation) {
                alert('المتصفح لا يدعم تحديد الموقع');
                return;
            }
            navigator.geolocation.getCurrentPosition(
                (pos) => {
                    document.getElementById('helpLat612').value = pos.coords.latitude;
                    document.getElementById('helpLng612').value = pos.coords.longitude;
                    if (window.map612Marker && window.map612) {
                        window.map612Marker.setLatLng([pos.coords.latitude, pos.coords.longitude]);
                        window.map612.setView([pos.coords.latitude, pos.coords.longitude], 15);
                    }
                },
                () => alert('تعذر الحصول على الموقع')
            );
        });
    }

    const appointmentLinks = document.querySelectorAll('[data-open-appointment612]');
    const modalAppt = document.getElementById('modalAppointment612');
    appointmentLinks.forEach((a) => {
        a.addEventListener('click', (e) => {
            e.preventDefault();
            openModal(modalAppt);
        });
    });

    const formAppt = document.getElementById('formAppointment612');
    if (formAppt) {
        formAppt.addEventListener('submit', async (e) => {
            e.preventDefault();
            const fd = new FormData(formAppt);
            const dt = document.getElementById('appointmentDatetime612').value;
            fd.append('appointment_datetime', dt);
            try {
                const res = await fetch('api_appointments612.php', { method: 'POST', body: fd, credentials: 'same-origin' });
                const data = await res.json();
                if (data.ok) {
                    alert('تم حفظ الموعد كـ «قيد الانتظار»');
                    closeModal612(modalAppt);
                    window.location.reload();
                } else {
                    alert(data.error || 'تعذر الحفظ');
                }
            } catch (err) {
                alert('تعذر الاتصال بالخادم');
            }
        });
    }

    const volPending = document.getElementById('volunteerPendingHelp612');
    function loadPendingHelp612() {
        if (!volPending) return;
        volPending.innerHTML = '<p style="color:var(--color-text-light)">جاري التحميل…</p>';
        fetch('api_pending_help612.php', { credentials: 'same-origin' })
            .then((r) => r.json())
            .then((data) => {
                if (!data.ok || !data.requests || !data.requests.length) {
                    volPending.innerHTML = '<p style="color:var(--color-text-light)">لا توجد طلبات معلقة حالياً.</p>';
                    return;
                }
                volPending.innerHTML = data.requests.map((req) => {
                    const lat = req.location_lat;
                    const lng = req.location_lng;
                    const mapLink =
                        lat != null && lng != null && lat !== '' && lng !== ''
                            ? `<a class="service-link ph-map612" href="https://www.google.com/maps?q=${encodeURIComponent(
                                  lat + ',' + lng
                              )}" target="_blank" rel="noopener">موقع على الخريطة</a>`
                            : '';
                    const rawPhone = req.phone != null ? String(req.phone).replace(/\s+/g, '') : '';
                    const telLink =
                        rawPhone.length >= 8
                            ? `<a class="service-link" href="tel:${encodeURIComponent(rawPhone)}">اتصال</a>`
                            : '';
                    return `
                    <div class="pending-help-item612" data-help-id="${escapeHtml(String(req.id))}">
                        <strong>${escapeHtml(req.request_type)}</strong>
                        <span class="ph-time612">${escapeHtml(req.created_at)}</span>
                        <p>${escapeHtml(req.description || '')}</p>
                        <small>${escapeHtml(req.requester_name)} — ${escapeHtml(req.phone)}</small>
                        <div class="ph-actions612">${mapLink}${telLink}
                        <button type="button" class="btn btn-primary btn-sm612" data-accept-help612="${escapeHtml(
                            String(req.id)
                        )}">قبول الطلب</button></div>
                    </div>`;
                }).join('');
            })
            .catch(() => {
                volPending.textContent = 'تعذر تحميل الطلبات';
            });
    }
    if (volPending) {
        loadPendingHelp612();
        volPending.addEventListener('click', async (e) => {
            const btn = e.target.closest('[data-accept-help612]');
            if (!btn) return;
            const hid = btn.getAttribute('data-accept-help612');
            btn.disabled = true;
            try {
                const fd = new FormData();
                fd.append('help_id', hid);
                fd.append('action', 'accept');
                const res = await fetch('api_help_action612.php', { method: 'POST', body: fd, credentials: 'same-origin' });
                const data = await res.json();
                if (data.ok) {
                    alert(data.message || 'تم القبول');
                    loadPendingHelp612();
                } else {
                    alert(data.error || 'تعذر القبول');
                    btn.disabled = false;
                }
            } catch (err) {
                alert('تعذر الاتصال بالخادم');
                btn.disabled = false;
            }
        });
    }
});

function escapeHtml(s) {
    if (!s) return '';
    const d = document.createElement('div');
    d.textContent = s;
    return d.innerHTML;
}
