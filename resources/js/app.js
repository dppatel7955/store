import './bootstrap';
import Swal from 'sweetalert2';
import 'sweetalert2/dist/sweetalert2.min.css';

window.Swal = Swal;

document.addEventListener('DOMContentLoaded', () => {
    window.addEventListener('swal', (event) => {
        const detail = event.detail;

        const title = detail.title || (Array.isArray(detail) ? detail[0]?.title : 'Notification');
        const text = detail.text || (Array.isArray(detail) ? detail[0]?.text : '');
        const icon = detail.icon || (Array.isArray(detail) ? detail[0]?.icon : 'success');
        const isToast = detail.toast !== false && (Array.isArray(detail) ? detail[0]?.toast !== false : true);

        if (isToast) {
            Swal.fire({
                toast: true,
                title,
                position: 'top-end',
                text,
                icon,
                showConfirmButton: false,
                timer: 3000,
                timerProgressBar: true,
                didOpen: (toast) => {
                    toast.onmouseenter = Swal.stopTimer;
                    toast.onmouseleave = Swal.resumeTimer;
                },
            });
        } else {
            Swal.fire({
                toast: false,
                title,
                text,
                icon,
                confirmButtonText: 'OK',
                confirmButtonColor: '#4f46e5',
            });
        }
    });
});
