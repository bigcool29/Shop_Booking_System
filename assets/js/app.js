/**
 * Front-end Application Interactivity & Utilities
 */

const App = {
    // Show SweetAlert Toast
    toast: (icon, title) => {
        if (typeof Swal === 'undefined') {
            alert(title);
            return;
        }
        const Toast = Swal.mixin({
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 3000,
            timerProgressBar: true,
            didOpen: (toast) => {
                toast.addEventListener('mouseenter', Swal.stopTimer);
                toast.addEventListener('mouseleave', Swal.resumeTimer);
            }
        });
        Toast.fire({
            icon: icon,
            title: title
        });
    },

    // Copy text to clipboard
    copyText: (text, label = 'ข้อความ') => {
        navigator.clipboard.writeText(text).then(() => {
            App.toast('success', `คัดลอก ${label} สำเร็จแล้ว`);
        }).catch(() => {
            // Fallback
            const el = document.createElement('textarea');
            el.value = text;
            document.body.appendChild(el);
            el.select();
            document.execCommand('copy');
            document.body.removeChild(el);
            App.toast('success', `คัดลอก ${label} สำเร็จแล้ว`);
        });
    },

    // Preview image before upload
    previewImage: (inputEl, previewElId, containerId = null) => {
        const file = inputEl.files[0];
        if (file) {
            // Check size (5MB)
            if (file.size > 5 * 1024 * 1024) {
                Swal.fire({
                    icon: 'warning',
                    title: 'ขนาดไฟล์เกินกำหนด',
                    text: 'ไฟล์ต้องมีขนาดไม่เกิน 5 MB',
                    confirmButtonColor: '#2563eb'
                });
                inputEl.value = '';
                return;
            }

            const reader = new FileReader();
            reader.onload = (e) => {
                const previewImg = document.getElementById(previewElId);
                if (previewImg) {
                    previewImg.src = e.target.result;
                    if (containerId) {
                        const c = document.getElementById(containerId);
                        if (c) c.classList.remove('hidden');
                    }
                }
            };
            reader.readAsDataURL(file);
        }
    },

    // Render QR Code in a container
    renderQR: (elementId, text, size = 220) => {
        const el = document.getElementById(elementId);
        if (!el || !text) return;
        el.innerHTML = '';
        if (typeof QRCode !== 'undefined') {
            new QRCode(el, {
                text: text,
                width: size,
                height: size,
                colorDark: "#0f172a",
                colorLight: "#ffffff",
                correctLevel: QRCode.CorrectLevel.M
            });
        }
    },

    // Initialize Countdown Timer for Payment Timeout
    initCountdown: (targetTimestamp, displayElId, onExpireUrl = null) => {
        const displayEl = document.getElementById(displayElId);
        if (!displayEl) return;

        const updateTimer = () => {
            const now = Math.floor(Date.now() / 1000);
            const diff = targetTimestamp - now;

            if (diff <= 0) {
                displayEl.innerHTML = '<span class="text-rose-600 font-bold">หมดเวลาชำระเงิน</span>';
                clearInterval(interval);
                if (onExpireUrl) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'หมดเวลาชำระเงินแล้ว',
                        text: 'ระบบได้ยกเลิกคำสั่งซื้อและคืนสต็อกเรียบร้อยแล้ว',
                        confirmButtonText: 'รับทราบ',
                        confirmButtonColor: '#2563eb'
                    }).then(() => {
                        window.location.href = onExpireUrl;
                    });
                }
                return;
            }

            const minutes = Math.floor(diff / 60);
            const seconds = diff % 60;
            displayEl.innerHTML = `<span class="tabular-nums font-mono font-bold text-amber-600">${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')} นาที</span>`;
        };

        updateTimer();
        const interval = setInterval(updateTimer, 1000);
    }
};

// Global helper to trigger print
function printSlip() {
    window.print();
}
