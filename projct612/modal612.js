const faqModal = document.getElementById('faqModal');
const supportModal = document.getElementById('supportModal');
const openFaqBtn = document.getElementById('openFaqBtn');
const openSupportBtn = document.getElementById('openSupportBtn');
const closeBtns = document.querySelectorAll('.close-modal');

if (openFaqBtn) {
    openFaqBtn.addEventListener('click', (e) => {
        e.preventDefault();
        faqModal.style.display = 'flex';
        document.body.style.overflow = 'hidden';
    });
}

if (openSupportBtn) {
    openSupportBtn.addEventListener('click', (e) => {
        e.preventDefault();
        supportModal.style.display = 'flex';
        document.body.style.overflow = 'hidden';
    });
}

closeBtns.forEach(btn => {
    btn.addEventListener('click', () => {
        faqModal.style.display = 'none';
        supportModal.style.display = 'none';
        document.body.style.overflow = 'auto';
    });
});

window.addEventListener('click', (e) => {
    if (e.target === faqModal) {
        faqModal.style.display = 'none';
        document.body.style.overflow = 'auto';
    }
    if (e.target === supportModal) {
        supportModal.style.display = 'none';
        document.body.style.overflow = 'auto';
    }
});

document.querySelectorAll('.faq-question').forEach(question => {
    question.addEventListener('click', () => {
        const answer = question.nextElementSibling;
        question.classList.toggle('active');
        answer.classList.toggle('active');
    });
});

const supportForm = document.getElementById('supportForm');
if (supportForm) {
    supportForm.addEventListener('submit', (e) => {
        e.preventDefault();
        alert('تم إرسال استفسارك بنجاح. سنتواصل معك قريباً.');
        supportForm.reset();
        supportModal.style.display = 'none';
        document.body.style.overflow = 'auto';
    });
}
// التحكم في النوافذ المنبثقة للسياسات
const privacyModal = document.getElementById('privacyModal');
const termsModal = document.getElementById('termsModal');
const disclaimerModal = document.getElementById('disclaimerModal');
const privacyLink = document.getElementById('privacyLink');
const termsLink = document.getElementById('termsLink');
const disclaimerLink = document.getElementById('disclaimerLink');

function openModal(modal) {
    if (modal) {
        modal.style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }
}

function closeModal(modal) {
    if (modal) {
        modal.style.display = 'none';
        document.body.style.overflow = 'auto';
    }
}

if (privacyLink) {
    privacyLink.addEventListener('click', (e) => {
        e.preventDefault();
        openModal(privacyModal);
    });
}

if (termsLink) {
    termsLink.addEventListener('click', (e) => {
        e.preventDefault();
        openModal(termsModal);
    });
}

if (disclaimerLink) {
    disclaimerLink.addEventListener('click', (e) => {
        e.preventDefault();
        openModal(disclaimerModal);
    });
}

// إضافة مستمع لأزرار الإغلاق (بما فيها الجديدة)
document.querySelectorAll('.close-modal').forEach(btn => {
    btn.addEventListener('click', () => {
        const modalId = btn.getAttribute('data-modal');
        if (modalId) {
            const modal = document.getElementById(modalId);
            closeModal(modal);
        } else {
            // إغلاق جميع النوافذ (للتوافق مع القديم)
            closeModal(privacyModal);
            closeModal(termsModal);
            closeModal(disclaimerModal);
            closeModal(document.getElementById('faqModal'));
            closeModal(document.getElementById('supportModal'));
        }
    });
});

// إغلاق النوافذ عند النقر خارج المحتوى
window.addEventListener('click', (e) => {
    if (e.target === privacyModal) closeModal(privacyModal);
    if (e.target === termsModal) closeModal(termsModal);
    if (e.target === disclaimerModal) closeModal(disclaimerModal);
    if (e.target === document.getElementById('faqModal')) closeModal(document.getElementById('faqModal'));
    if (e.target === document.getElementById('supportModal')) closeModal(document.getElementById('supportModal'));
});