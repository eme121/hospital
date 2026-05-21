<?php 
require_once 'includes/config.php';
include 'includes/header.php'; 
?>

    <!-- Portal Gateway -->
    <section class="relative py-32 lg:py-48 bg-slate-900 overflow-hidden min-h-screen flex items-center">
        <!-- Background Decor -->
        <div class="absolute inset-0 opacity-20">
            <img src="<?php echo BASE_URL; ?>/assets/img/external/about-pro.jpg" class="w-full h-full object-cover" onerror="this.style.display='none'">
        </div>
        <div class="absolute inset-0 bg-[radial-gradient(circle_at_50%_50%,rgba(37,99,235,0.1)_0%,transparent_70%)]"></div>

        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10 w-full text-center">
            <div data-aos="fade-up">
                <span class="text-sm font-black text-blue-500 uppercase tracking-[0.4em] mb-6 block">Internal Clinical System</span>
                <h1 class="text-5xl lg:text-8xl font-black text-white leading-tight mb-8">Staff <span class="text-blue-500 text-glow">Gateway</span></h1>
                <p class="text-xl text-blue-100/60 max-w-2xl mx-auto font-medium leading-relaxed mb-12">
                    A unified secure entry point for all Hope Haven Hospital personnel. Log in to access your specific clinical dashboard, patient records, and administrative tools.
                </p>

                <div class="flex flex-col sm:flex-row items-center justify-center gap-6">
                    <button onclick="openLoginModal()" class="group relative px-12 py-6 bg-blue-600 text-white rounded-[32px] font-black text-xl hover:bg-blue-500 transition-all shadow-2xl shadow-blue-500/20 flex items-center overflow-hidden">
                        <span class="relative z-10">Enter Secure Portal</span>
                        <i data-lucide="shield-check" class="relative z-10 ml-3 w-6 h-6 group-hover:rotate-12 transition-transform"></i>
                        <div class="absolute inset-0 bg-gradient-to-r from-blue-400/20 to-transparent translate-x-[-100%] group-hover:translate-x-[100%] transition-transform duration-1000"></div>
                    </button>
                    
                    <div class="flex items-center space-x-4 text-blue-100/30">
                        <div class="w-12 h-[1px] bg-white/10"></div>
                        <span class="text-[10px] font-black uppercase tracking-widest">Encrypted Session</span>
                        <div class="w-12 h-[1px] bg-white/10"></div>
                    </div>
                </div>

                <!-- Role Icons Decoration -->
                <div class="mt-24 grid grid-cols-4 md:grid-cols-7 gap-4 max-w-3xl mx-auto opacity-90">
                    <div class="flex flex-col items-center gap-2 group cursor-default animate-float" style="animation-delay: 0.0s;">
                        <div class="w-12 h-12 bg-blue-500/10 rounded-2xl flex items-center justify-center mb-1 group-hover:bg-blue-500/20 group-hover:scale-110 transition-all duration-500 border border-blue-500/20 shadow-lg shadow-blue-500/5">
                            <i data-lucide="shield" class="w-6 h-6 text-blue-500"></i>
                        </div>
                        <span class="text-[9px] font-black uppercase tracking-[0.2em] text-blue-400/80">Admin</span>
                    </div>
                    <div class="flex flex-col items-center gap-2 group cursor-default animate-float" style="animation-delay: 0.2s;">
                        <div class="w-12 h-12 bg-rose-500/10 rounded-2xl flex items-center justify-center mb-1 group-hover:bg-rose-500/20 group-hover:scale-110 transition-all duration-500 border border-rose-500/20 shadow-lg shadow-rose-500/5">
                            <i data-lucide="activity" class="w-6 h-6 text-rose-500"></i>
                        </div>
                        <span class="text-[9px] font-black uppercase tracking-[0.2em] text-rose-400/80">Nurse</span>
                    </div>
                    <div class="flex flex-col items-center gap-2 group cursor-default animate-float" style="animation-delay: 0.4s;">
                        <div class="w-12 h-12 bg-emerald-500/10 rounded-2xl flex items-center justify-center mb-1 group-hover:bg-emerald-500/20 group-hover:scale-110 transition-all duration-500 border border-emerald-500/20 shadow-lg shadow-emerald-500/5">
                            <i data-lucide="pill" class="w-6 h-6 text-emerald-500"></i>
                        </div>
                        <span class="text-[9px] font-black uppercase tracking-[0.2em] text-emerald-400/80">Pharma</span>
                    </div>
                    <div class="flex flex-col items-center gap-2 group cursor-default animate-float" style="animation-delay: 0.6s;">
                        <div class="w-12 h-12 bg-indigo-500/10 rounded-2xl flex items-center justify-center mb-1 group-hover:bg-indigo-500/20 group-hover:scale-110 transition-all duration-500 border border-indigo-500/20 shadow-lg shadow-indigo-500/5">
                            <i data-lucide="microscope" class="w-6 h-6 text-indigo-500"></i>
                        </div>
                        <span class="text-[9px] font-black uppercase tracking-[0.2em] text-indigo-400/80">Lab</span>
                    </div>
                    <div class="flex flex-col items-center gap-2 group cursor-default animate-float" style="animation-delay: 0.8s;">
                        <div class="w-12 h-12 bg-blue-500/10 rounded-2xl flex items-center justify-center mb-1 group-hover:bg-blue-500/20 group-hover:scale-110 transition-all duration-500 border border-blue-500/20 shadow-lg shadow-blue-500/5">
                            <i data-lucide="folder-open" class="w-6 h-6 text-blue-500"></i>
                        </div>
                        <span class="text-[9px] font-black uppercase tracking-[0.2em] text-blue-400/80">Records</span>
                    </div>
                    <div class="flex flex-col items-center gap-2 group cursor-default animate-float" style="animation-delay: 1.0s;">
                        <div class="w-12 h-12 bg-teal-500/10 rounded-2xl flex items-center justify-center mb-1 group-hover:bg-teal-500/20 group-hover:scale-110 transition-all duration-500 border border-teal-500/20 shadow-lg shadow-teal-500/5">
                            <i data-lucide="banknote" class="w-6 h-6 text-teal-500"></i>
                        </div>
                        <span class="text-[9px] font-black uppercase tracking-[0.2em] text-teal-400/80">Finance</span>
                    </div>
                    <div class="flex flex-col items-center gap-2 group cursor-default animate-float" style="animation-delay: 1.2s;">
                        <div class="w-12 h-12 bg-sky-500/10 rounded-2xl flex items-center justify-center mb-1 group-hover:bg-sky-500/20 group-hover:scale-110 transition-all duration-500 border border-sky-500/20 shadow-lg shadow-sky-500/5">
                            <i data-lucide="user-plus" class="w-6 h-6 text-sky-500"></i>
                        </div>
                        <span class="text-[9px] font-black uppercase tracking-[0.2em] text-sky-400/80">Doctor</span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- UNIFIED LOGIN MODAL -->
    <div id="loginModal" class="fixed inset-0 z-[100] hidden overflow-y-auto overflow-x-hidden">
        <!-- Backdrop -->
        <div class="fixed inset-0 bg-slate-950/80 backdrop-blur-2xl transition-opacity duration-500" onclick="closeLoginModal()"></div>
        
        <!-- Modal Scroll Wrapper -->
        <div class="flex min-h-full items-center justify-center p-4 py-20">
            <!-- Modal Content Box -->
            <div class="relative w-full max-w-md bg-slate-900 border border-white/10 rounded-[3rem] p-8 lg:p-12 shadow-[0_0_100px_rgba(37,99,235,0.3)] transform transition-all duration-500 scale-95 opacity-0" id="modalContainer">
                
                <!-- FLOATING LOGO AT THE VERY TOP -->
                <div class="absolute -top-16 left-1/2 -translate-x-1/2 z-30">
                    <div class="w-32 h-32 bg-white rounded-[2.5rem] flex items-center justify-center shadow-2xl p-6 relative overflow-hidden border border-white/20">
                        <img src="<?php echo BASE_URL; ?>/logo.png?v=<?php echo time(); ?>" alt="Hospital Logo" class="w-full h-full object-contain relative z-10 animate-float-slow">
                        <div class="absolute inset-0 bg-gradient-to-br from-blue-50 to-transparent opacity-50"></div>
                        <div class="modal-scan-line"></div>
                    </div>
                    <!-- Outer Glow Ornament -->
                    <div class="absolute -inset-4 border border-blue-500/20 rounded-[3rem] animate-pulse"></div>
                </div>

                <!-- Close Button -->
                <button onclick="closeLoginModal()" class="absolute top-8 right-8 text-white/20 hover:text-white transition-colors z-40">
                    <i data-lucide="x" class="w-6 h-6"></i>
                </button>

                <!-- Text Content -->
                <div class="text-center mb-10 mt-12">
                    <h2 class="text-3xl font-black text-white tracking-tighter">Staff <span class="text-blue-500">Central</span></h2>
                    <p class="text-blue-400/30 font-black uppercase tracking-[0.4em] text-[10px] mt-2">Unified Secure Gateway</p>
                </div>

                <form id="modalLoginForm" class="space-y-6">
                    <div class="space-y-2 group">
                        <label class="text-[10px] font-black text-blue-400 uppercase tracking-widest ml-4">Credential ID</label>
                        <div class="relative">
                            <i data-lucide="user" class="absolute left-5 top-1/2 -translate-y-1/2 w-5 h-5 text-slate-500 group-focus-within:text-blue-500 transition-colors"></i>
                            <input type="text" name="identity" required placeholder="name@hopehaven.com" 
                                class="w-full bg-slate-950/50 border border-white/5 rounded-2xl px-12 py-5 text-white placeholder:text-slate-700 focus:border-blue-500/50 outline-none transition-all font-bold">
                        </div>
                    </div>

                    <div class="space-y-2 group">
                        <label class="text-[10px] font-black text-blue-400 uppercase tracking-widest ml-4">Secure Key</label>
                        <div class="relative">
                            <i data-lucide="lock" class="absolute left-5 top-1/2 -translate-y-1/2 w-5 h-5 text-slate-500 group-focus-within:text-blue-500 transition-colors"></i>
                            <input type="password" name="password" required placeholder="••••••••" 
                                class="w-full bg-slate-950/50 border border-white/5 rounded-2xl px-12 py-5 text-white placeholder:text-slate-700 focus:border-blue-500/50 outline-none transition-all font-bold">
                        </div>
                    </div>

                    <div id="modal-error-msg" class="bg-red-500/10 border border-red-500/20 text-red-400 text-[10px] font-black uppercase tracking-widest p-4 rounded-2xl text-center hidden"></div>

                    <button type="submit" id="modalSubmitBtn" class="w-full bg-blue-600 text-white py-6 rounded-2xl font-black text-lg hover:bg-blue-500 transition-all shadow-2xl shadow-blue-500/20 flex items-center justify-center group">
                        <span>Authorize Access</span>
                        <i data-lucide="shield-check" class="ml-3 w-6 h-6 group-hover:rotate-12 transition-transform"></i>
                    </button>
                </form>
                
                <div class="mt-10 pt-8 border-t border-white/5 text-center">
                    <p class="text-slate-600 font-bold text-[8px] uppercase tracking-widest">
                        AES-256 Encrypted Tunnel • Session Isolation Active
                    </p>
                </div>
            </div>
        </div>
    </div>

    <style>
        .text-glow { text-shadow: 0 0 30px rgba(37,99,235,0.4); }
        .animate-float { animation: float 6s ease-in-out infinite; }
        .animate-float-slow { animation: float 4s ease-in-out infinite; }
        @keyframes float { 0%, 100% { transform: translateY(0px); } 50% { transform: translateY(-15px); } }
        
        .modal-scan-line {
            width: 100%; height: 2px;
            background: linear-gradient(to right, transparent, #3b82f6, transparent);
            position: absolute; top: 0; left: 0; z-index: 20;
            animation: scan 3s linear infinite; opacity: 0.5;
        }
        @keyframes scan { 0% { top: 0; opacity: 0; } 50% { opacity: 1; } 100% { top: 100%; opacity: 0; } }
        
        /* Prevent body scroll when modal is open */
        body.modal-open {
            overflow: hidden;
        }
    </style>

    <script>
        function openLoginModal() {
            const modal = document.getElementById('loginModal');
            const container = document.getElementById('modalContainer');
            document.body.classList.add('modal-open');
            modal.classList.remove('hidden');
            setTimeout(() => {
                container.classList.remove('scale-95', 'opacity-0');
                container.classList.add('scale-100', 'opacity-100');
            }, 10);
            if (window.lucide) lucide.createIcons();
        }

        function closeLoginModal() {
            const modal = document.getElementById('loginModal');
            const container = document.getElementById('modalContainer');
            container.classList.add('scale-95', 'opacity-0');
            container.classList.remove('scale-100', 'opacity-100');
            setTimeout(() => {
                modal.classList.add('hidden');
                document.body.classList.remove('modal-open');
            }, 500);
        }

        document.getElementById('modalLoginForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const btn = document.getElementById('modalSubmitBtn');
            const error = document.getElementById('modal-error-msg');
            const originalText = btn.innerHTML;
            
            btn.innerHTML = '<i data-lucide="loader-2" class="w-6 h-6 animate-spin"></i>';
            btn.disabled = true;
            error.classList.add('hidden');
            if (window.lucide) lucide.createIcons();

            const formData = new FormData(this);
            fetch('<?php echo BASE_URL; ?>/api/unified_staff_auth.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    btn.innerHTML = '<i data-lucide="check" class="w-6 h-6"></i>';
                    if (window.lucide) lucide.createIcons();
                    setTimeout(() => { window.location.href = '<?php echo BASE_URL; ?>/' + data.redirect; }, 600);
                } else {
                    error.textContent = data.message;
                    error.classList.remove('hidden');
                    btn.innerHTML = originalText;
                    btn.disabled = false;
                    if (window.lucide) lucide.createIcons();
                }
            })
            .catch(err => {
                error.textContent = "Security handshake failed.";
                error.classList.remove('hidden');
                btn.innerHTML = originalText;
                btn.disabled = false;
                if (window.lucide) lucide.createIcons();
            });
        });
    </script>

<?php include 'includes/footer.php'; ?>
