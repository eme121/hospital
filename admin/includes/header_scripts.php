
<!-- Font Awesome -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<!-- Tailwind CSS (Keep current) -->
<script src="https://cdn.tailwindcss.com"></script>
<!-- Custom Dashboard Styles -->
<link rel="stylesheet" href="assets/css/admin_layout.css">
<!-- Custom Dashboard Scripts -->
<script src="assets/js/admin_layout.js" defer></script>
<script src="assets/js/admin_notifications.js" defer></script>

<!-- Professional Real-Time Sync Engine -->
<script>
    window.APP_BASE_URL = '<?php echo BASE_URL; ?>';
</script>
<script src="<?php echo BASE_URL; ?>/assets/js/sync_engine.js?v=<?php echo time(); ?>"></script>

<style>
    /* Additional Utility Styles */
    @keyframes zoom-in {
        from { opacity: 0; transform: scale(0.9) translateY(20px); }
        to { opacity: 1; transform: scale(1) translateY(0); }
    }
    .animate-zoom-in { animation: zoom-in 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275) forwards; }
</style>
