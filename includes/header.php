<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$toast_message = null;
$toast_type = 'success';

if (isset($_SESSION['toast_success'])) {
    $toast_message = $_SESSION['toast_success'];
    $toast_type = 'success';
    unset($_SESSION['toast_success']);
} elseif (isset($_SESSION['toast_error'])) {
    $toast_message = $_SESSION['toast_error'];
    $toast_type = 'error';
    unset($_SESSION['toast_error']);
} elseif (isset($_GET['logout']) && $_GET['logout'] === 'success') {
    $toast_message = 'Anda berhasil keluar. Sampai jumpa kembali!';
    $toast_type = 'success';
} elseif (isset($_GET['registration']) && $_GET['registration'] === 'success') {
    $toast_message = 'Registrasi berhasil! Silakan masuk menggunakan akun baru Anda.';
    $toast_type = 'success';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? htmlspecialchars($page_title) . " - Petromine Analytics" : "Petromine Analytics"; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?php echo isset($base_path) ? $base_path : ''; ?>assets/css/style.css">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        @keyframes toastSlideIn {
            from { transform: translateX(120%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
        @keyframes toastSlideOut {
            from { transform: translateX(0); opacity: 1; }
            to { transform: translateX(120%); opacity: 0; }
        }
        @keyframes toastProgress {
            from { width: 100%; }
            to { width: 0%; }
        }
        .toast-enter { animation: toastSlideIn 0.35s cubic-bezier(0.16, 1, 0.3, 1) forwards; }
        .toast-exit { animation: toastSlideOut 0.35s cubic-bezier(0.16, 1, 0.3, 1) forwards; }
        .toast-progress-bar { animation: toastProgress 4s linear forwards; }
    </style>
    <?php echo $extra_head ?? ''; ?>
    <script>
    document.addEventListener("DOMContentLoaded", function() {
        // Create toast container dynamically inside body
        const container = document.createElement('div');
        container.id = 'toast-container';
        container.className = 'fixed top-5 right-5 z-[9999] flex flex-col gap-3 pointer-events-none';
        document.body.appendChild(container);

        window.showToast = function(message, type = 'success') {
            const toast = document.createElement('div');
            toast.className = 'relative bg-slate-900/95 border ' + 
                (type === 'success' ? 'border-emerald-500/30' : 'border-rose-500/30') + 
                ' text-slate-100 px-4 py-3.5 rounded-2xl shadow-2xl flex items-center gap-3 pointer-events-auto toast-enter max-w-sm w-[320px] overflow-hidden backdrop-blur';
            
            const iconBg = type === 'success' ? 'bg-emerald-500/10 text-emerald-400' : 'bg-rose-500/10 text-rose-400';
            const iconClass = type === 'success' ? 'fa-check-circle' : 'fa-triangle-exclamation';
            const progressBg = type === 'success' ? 'bg-emerald-500' : 'bg-rose-500';

            toast.innerHTML = `
                <div class="w-8 h-8 rounded-xl ${iconBg} flex items-center justify-center flex-shrink-0">
                    <i class="fas ${iconClass} text-sm"></i>
                </div>
                <div class="flex-1">
                    <p class="text-xs font-semibold text-slate-200 leading-snug">${message}</p>
                </div>
                <button class="text-slate-500 hover:text-slate-300 text-xs flex-shrink-0 transition-colors focus:outline-none" 
                        onclick="this.parentElement.classList.replace('toast-enter', 'toast-exit'); setTimeout(() => this.parentElement.remove(), 350)">
                    <i class="fas fa-times"></i>
                </button>
                <div class="absolute bottom-0 left-0 h-[3px] ${progressBg} toast-progress-bar"></div>
            `;

            container.appendChild(toast);

            // Auto dismiss after 4 seconds
            setTimeout(() => {
                if (toast.parentNode) {
                    toast.classList.replace('toast-enter', 'toast-exit');
                    setTimeout(() => toast.remove(), 350);
                }
            }, 4000);
        };

        <?php if ($toast_message): ?>
            window.showToast(<?php echo json_encode($toast_message); ?>, <?php echo json_encode($toast_type); ?>);
        <?php endif; ?>
    });
    </script>
</head>
