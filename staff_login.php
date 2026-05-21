<?php 
require_once 'includes/config.php';
include 'includes/header.php'; 
?>

    <style>
        /* Hide main nav for true gateway experience */
        nav.glass-nav { display: none !important; }
        .bg-blue-600.text-white.py-2 { display: none !important; } /* Hide top bar */
        
        @keyframes cyber-pulse {
            0%, 100% { box-shadow: 0 0 20px rgba(37, 99, 235, 0.2); }
            50% { box-shadow: 0 0 40px rgba(37, 99, 235, 0.4); }
        }
        .cyber-card {
            animation: cyber-pulse 8s infinite;
        }
        .scan-line {
            width: 100%;
            height: 2px;
            background: linear-gradient(to right, transparent, #3b82f6, transparent);
            position: absolute;
            top: 0;
            left: 0;
            z-index: 20;
            animation: scan 3s linear infinite;
            opacity: 0.5;
        }
        @keyframes scan {
            0% { top: 0; opacity: 0; }
            50% { opacity: 1; }
            100% { top: 100%; opacity: 0; }
        }
    </style>

    <section class="min-h-screen bg-[#0f172a] flex items-center justify-center relative overflow-hidden">
        <!-- Animated Background Elements -->
        <div class="absolute inset-0 pointer-events-none">
            <div class="absolute top-[-10%] left-[-10%] w-[40%] h-[40%] bg-blue-600/10 rounded-full blur-[120px] animate-pulse"></div>
            <div class="absolute bottom-[-10%] right-[-10%] w-[40%] h-[40%] bg-indigo-600/10 rounded-full blur-[120px] animate-pulse" style="animation-delay: 2s;"></div>
        </div>

        <div class="max-w-md mx-auto px-4 relative z-10 w-full">
            <div class="text-center mb-10" data-aos="fade-down">
                <div class="inline-block relative">
                    <div class="w-28 h-28 bg-white rounded-[2rem] flex items-center justify-center shadow-2xl p-6 relative overflow-hidden mb-6">
                        <img src="logo.png" alt="Hospital Logo" class="w-full h-full object-contain relative z-10">
                        <div class="absolute inset-0 bg-gradient-to-br from-blue-50 to-transparent"></div>
                        <div class="scan-line"></div>
                    </div>
                </div>
                <h1 class="text-4xl font-black text-white tracking-tighter">Staff <span class="text-blue-500">Central</span></h1>
                <p class="text-blue-400/50 font-black mt-2 uppercase tracking-[0.4em] text-[10px]">Unified Secure Gateway</p>
            </div>

            <div class="bg-slate-800/40 backdrop-blur-3xl rounded-[3rem] p-8 lg:p-12 border border-white/5 shadow-2xl cyber-card" data-aos="zoom-in">
                <form id="unifiedLoginForm" class="space-y-6">
                    <div class="space-y-2">
                        <label class="text-[10px] font-black text-blue-400 uppercase tracking-widest ml-4">Credential ID</label>
                        <div class="relative group">
                            <i data-lucide="user" class="absolute left-5 top-1/2 -translate-y-1/2 w-5 h-5 text-slate-500 group-focus-within:text-blue-500 transition-colors"></i>
                            <input type="text" name="identity" required placeholder="name@hopehaven.com" 
                                class="w-full bg-slate-900/50 border border-white/5 rounded-2xl px-12 py-5 text-white placeholder:text-slate-600 focus:border-blue-500/50 outline-none transition-all font-bold">
                        </div>
                    </div>

                    <div class="space-y-2">
                        <label class="text-[10px] font-black text-blue-400 uppercase tracking-widest ml-4">Secure Key</label>
                        <div class="relative group">
                            <i data-lucide="lock" class="absolute left-5 top-1/2 -translate-y-1/2 w-5 h-5 text-slate-500 group-focus-within:text-blue-500 transition-colors"></i>
                            <input type="password" name="password" required placeholder="••••••••" 
                                class="w-full bg-slate-900/50 border border-white/5 rounded-2xl px-12 py-5 text-white placeholder:text-slate-600 focus:border-blue-500/50 outline-none transition-all font-bold">
                        </div>
                    </div>

                    <div id="error-msg" class="bg-red-500/10 border border-red-500/20 text-red-400 text-[10px] font-black uppercase tracking-widest p-4 rounded-2xl text-center hidden"></div>

                    <button type="submit" id="submitBtn" class="w-full bg-blue-600 text-white py-6 rounded-2xl font-black text-lg hover:bg-blue-500 transition-all shadow-2xl shadow-blue-500/20 flex items-center justify-center group">
                        <span class="group-hover:scale-105 transition-transform">Authorize Access</span>
                        <i data-lucide="shield-check" class="ml-3 w-6 h-6 group-hover:rotate-12 transition-transform"></i>
                    </button>
                </form>

                <div class="mt-10 pt-8 border-t border-white/5 text-center">
                    <div class="flex items-center justify-center gap-4 mb-4">
                        <div class="w-2 h-2 bg-blue-500 rounded-full animate-pulse"></div>
                        <div class="w-2 h-2 bg-emerald-500 rounded-full animate-pulse" style="animation-delay: 0.5s;"></div>
                        <div class="w-2 h-2 bg-amber-500 rounded-full animate-pulse" style="animation-delay: 1s;"></div>
                    </div>
                    <p class="text-slate-500 font-bold text-[9px] uppercase tracking-widest">
                        System Status: Operational • AES-256
                    </p>
                </div>
            </div>
            
            <div class="text-center mt-8">
                <a href="index.php" class="text-slate-500 hover:text-blue-400 text-[10px] font-black uppercase tracking-widest transition-colors">← Back to Public Website</a>
            </div>
        </div>
    </section>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            if (typeof lucide !== 'undefined') lucide.createIcons();

            const form = document.getElementById('unifiedLoginForm');
            const btn = document.getElementById('submitBtn');
            const error = document.getElementById('error-msg');

            form.addEventListener('submit', function(e) {
                e.preventDefault();
                const originalText = btn.innerHTML;
                btn.innerHTML = '<i data-lucide="loader-2" class="w-6 h-6 animate-spin"></i>';
                btn.disabled = true;
                error.classList.add('hidden');
                if (window.lucide) lucide.createIcons();

                const formData = new FormData(this);
                fetch('api/unified_staff_auth.php', {
                    method: 'POST',
                    body: formData
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        btn.innerHTML = '<i data-lucide="check" class="w-6 h-6"></i>';
                        if (window.lucide) lucide.createIcons();
                        setTimeout(() => { window.location.href = data.redirect; }, 600);
                    } else {
                        error.textContent = data.message;
                        error.classList.remove('hidden');
                        btn.innerHTML = originalText;
                        btn.disabled = false;
                        if (window.lucide) lucide.createIcons();
                    }
                })
                .catch(err => {
                    error.textContent = "Security handshake failed. Try again.";
                    error.classList.remove('hidden');
                    btn.innerHTML = originalText;
                    btn.disabled = false;
                    if (window.lucide) lucide.createIcons();
                });
            });
        });
    </script>

<?php include 'includes/footer.php'; ?>
