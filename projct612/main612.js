// ملف JavaScript الرئيسي
document.addEventListener('DOMContentLoaded', function() {
    // تبديل قائمة الهاتف
    const menuToggle = document.getElementById('menuToggle');
    const navLinks = document.querySelector('.nav-links');
    
    if (menuToggle) {
        menuToggle.addEventListener('click', function() {
            navLinks.classList.toggle('active');
            const icon = this.querySelector('i');
            if (navLinks.classList.contains('active')) {
                icon.classList.remove('fa-bars');
                icon.classList.add('fa-times');
            } else {
                icon.classList.remove('fa-times');
                icon.classList.add('fa-bars');
            }
        });
    }
    
    // إغلاق القائمة عند النقر على رابط
    document.querySelectorAll('.nav-links a').forEach(link => {
        link.addEventListener('click', () => {
            navLinks.classList.remove('active');
            if (menuToggle) {
                menuToggle.querySelector('i').classList.remove('fa-times');
                menuToggle.querySelector('i').classList.add('fa-bars');
            }
        });
    });
    
    // التحقق من النماذج
    const requestForm = document.querySelector('.request-form');
    if (requestForm) {
        requestForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // التحقق من الحقول المطلوبة
            const inputs = this.querySelectorAll('input[required], select[required]');
            let isValid = true;
            
            inputs.forEach(input => {
                if (!input.value.trim()) {
                    isValid = false;
                    input.style.borderColor = 'var(--color-danger)';
                } else {
                    input.style.borderColor = '#e0e0e0';
                }
            });
            
            if (isValid) {
                // محاكاة إرسال النموذج
                const submitBtn = this.querySelector('button[type="submit"]');
                const originalText = submitBtn.innerHTML;
                
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> جاري الإرسال...';
                submitBtn.disabled = true;
                
                setTimeout(() => {
                    submitBtn.innerHTML = '<i class="fas fa-check"></i> تم الإرسال بنجاح';
                    submitBtn.style.backgroundColor = 'var(--color-success)';
                    
                    // إعادة تعيين النموذج
                    setTimeout(() => {
                        requestForm.reset();
                        submitBtn.innerHTML = originalText;
                        submitBtn.style.backgroundColor = '';
                        submitBtn.disabled = false;
                        
                        // عرض رسالة نجاح
                        alert('شكراً لك! سنتواصل معك خلال 24 ساعة.');
                    }, 2000);
                }, 1500);
            }
        });
    }
    
    // إضافة تأثير التمرير السلس للروابط
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            
            const targetId = this.getAttribute('href');
            if (targetId === '#') return;
            
            const targetElement = document.querySelector(targetId);
            if (targetElement) {
                window.scrollTo({
                    top: targetElement.offsetTop - 80,
                    behavior: 'smooth'
                });
            }
        });
    });
    
    // تأثير الظهور عند التمرير
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };
    
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('animate');
            }
        });
    }, observerOptions);
    
    // مراقبة العناصر لإضافة تأثيرات
    document.querySelectorAll('.feature-card, .rank-card, .service-card').forEach(card => {
        observer.observe(card);
    });
});
function setRandomHeroBackground() {
    const heroBg = document.getElementById('heroBackground');
    if (!heroBg) {
        return;
    }
    const localPath = 'assets/images/hero-bg.jpg612.jpg';
    const unsplashFallback =
        'https://images.unsplash.com/photo-1581578731548-c64695cc6952?w=1600&h=900&fit=crop&q=80';
    const img = new Image();
    img.onload = function () {
        heroBg.style.backgroundImage = "url('" + localPath + "')";
    };
    img.onerror = function () {
        heroBg.style.backgroundImage = "url('" + unsplashFallback + "')";
    };
    img.src = localPath;
}

window.addEventListener('load', setRandomHeroBackground);