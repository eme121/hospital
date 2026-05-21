<?php include 'includes/header.php'; ?>

    <!-- PWA Install Page Header -->
    <section class="relative py-24 bg-slate-900 overflow-hidden">
        <div class="absolute inset-0 opacity-40">
            <img src="assets/img/external/hero-fallback.jpg" class="w-full h-full object-cover" alt="Mobile App Header">
        </div>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10 text-center">
            <h1 class="text-4xl lg:text-6xl font-black text-white mb-6" data-aos="fade-down">Download Our App</h1>
            <p class="text-xl text-blue-100 font-medium max-w-2xl mx-auto" data-aos="fade-up" data-aos-delay="100">Experience world-class healthcare right in your pocket. Access consultations, medical records, and emergency services instantly.</p>
        </div>
    </section>

    <!-- Premium PWA Install Section -->
    <section id="pwa-install-section" class="py-32 bg-white relative overflow-hidden">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="relative rounded-[60px] bg-slate-900 p-12 lg:p-24 overflow-hidden shadow-2xl border border-white/5">
                <!-- Decorative Background (Anime-style glowing orbs) -->
                <div class="absolute top-0 right-0 w-1/2 h-full bg-gradient-to-l from-blue-600/20 to-transparent"></div>
                <div class="absolute -bottom-24 -left-24 w-96 h-96 bg-indigo-600/20 rounded-full blur-[100px] animate-pulse"></div>
                <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[800px] h-[800px] bg-blue-500/5 rounded-full blur-[120px] pointer-events-none"></div>
                
                <div class="relative z-10 grid lg:grid-cols-2 gap-16 items-center">
                    <div data-aos="fade-right">
                        <div class="inline-flex items-center gap-3 px-4 py-2 mb-8 bg-blue-500/10 rounded-2xl border border-blue-500/20">
                            <span class="relative flex h-2 w-2">
                                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-blue-400 opacity-75"></span>
                                <span class="relative inline-flex rounded-full h-2 w-2 bg-blue-500"></span>
                            </span>
                            <span class="text-[10px] font-black tracking-[0.3em] text-blue-400 uppercase">Mobile Experience</span>
                        </div>
                        
                        <h2 class="text-4xl lg:text-7xl font-black text-white leading-[1.1] mb-8 tracking-tighter">
                            Your Health,<br>
                            <span class="text-transparent bg-clip-text bg-gradient-to-r from-blue-400 via-indigo-300 to-blue-500">Perfectly Portable.</span>
                        </h2>
                        
                        <p class="text-slate-400 text-lg mb-12 leading-relaxed font-medium max-w-lg">
                            Experience the future of healthcare. Install our high-performance portal directly to your home screen for instant consultations and real-time health tracking.
                        </p>
                        
                        <div class="flex flex-wrap gap-8 items-center">
                            <button id="pwa-install-btn" class="group relative px-12 py-6 bg-blue-600 text-white rounded-[2rem] font-black text-xl shadow-2xl shadow-blue-900/40 hover:bg-blue-500 transition-all flex items-center gap-4 overflow-hidden">
                                <span class="relative z-10">Get App Now</span>
                                <div class="relative z-10 p-2 bg-white/20 rounded-xl group-hover:rotate-12 transition-transform">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                                </div>
                                <div class="absolute inset-0 bg-gradient-to-r from-transparent via-white/20 to-transparent -translate-x-full group-hover:animate-shimmer"></div>
                            </button>
                            
                            <div class="flex items-center gap-6">
                                <div class="h-12 w-[1px] bg-slate-800"></div>
                                <div>
                                    <div class="flex gap-1 mb-1">
                                        <i data-lucide="star" class="w-3 h-3 text-amber-400 fill-current"></i>
                                        <i data-lucide="star" class="w-3 h-3 text-amber-400 fill-current"></i>
                                        <i data-lucide="star" class="w-3 h-3 text-amber-400 fill-current"></i>
                                        <i data-lucide="star" class="w-3 h-3 text-amber-400 fill-current"></i>
                                        <i data-lucide="star" class="w-3 h-3 text-amber-400 fill-current"></i>
                                    </div>
                                    <p class="text-[10px] font-black text-slate-500 uppercase tracking-widest">Premium Rated</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="relative flex justify-center lg:justify-end">
                        <!-- Floating Phone Mockup (Anime Floating Style) -->
                        <div id="phone-mockup" class="relative w-80 h-[620px] bg-slate-900 rounded-[3.5rem] border-[12px] border-slate-800 shadow-[0_50px_100px_-20px_rgba(0,0,0,0.5)] overflow-hidden z-20">
                            <!-- Notch -->
                            <div class="absolute top-0 w-full h-8 bg-slate-800 flex justify-center items-end pb-1">
                                <div class="w-24 h-4 bg-slate-900 rounded-full"></div>
                            </div>
                            
                            <!-- Mock App UI -->
                            <div class="p-8 pt-16 space-y-8">
                                <div class="flex justify-between items-center">
                                    <div class="w-10 h-10 bg-blue-600 rounded-xl shadow-lg shadow-blue-500/40"></div>
                                    <div class="w-8 h-8 rounded-full bg-slate-800"></div>
                                </div>
                                <div class="space-y-3">
                                    <div class="w-full h-2.5 bg-slate-800 rounded-full"></div>
                                    <div class="w-3/4 h-2.5 bg-slate-800 rounded-full"></div>
                                </div>
                                <div class="p-6 bg-gradient-to-br from-blue-600 to-indigo-700 rounded-[2.5rem] shadow-xl relative overflow-hidden group/card">
                                    <div class="relative z-10">
                                        <p class="text-[8px] font-black text-blue-200 uppercase tracking-widest mb-2">Live Status</p>
                                        <h4 class="text-white font-black text-lg leading-tight">Emergency <br>Ready.</h4>
                                    </div>
                                    <div class="absolute -right-4 -bottom-4 w-20 h-20 bg-white/10 rounded-full blur-xl group-hover/card:scale-150 transition-transform"></div>
                                </div>
                                <div class="grid grid-cols-2 gap-4">
                                    <div class="h-24 bg-slate-800/50 rounded-3xl border border-white/5 flex flex-col items-center justify-center gap-2">
                                        <i data-lucide="activity" class="w-5 h-5 text-blue-400"></i>
                                        <span class="text-[8px] font-black text-slate-500 uppercase tracking-tighter">Vitals</span>
                                    </div>
                                    <div class="h-24 bg-slate-800/50 rounded-3xl border border-white/5 flex flex-col items-center justify-center gap-2">
                                        <i data-lucide="message-circle" class="w-5 h-5 text-emerald-400"></i>
                                        <span class="text-[8px] font-black text-slate-500 uppercase tracking-tighter">Chat</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Floating Glass Cards (Anime Parallax) -->
                        <div class="floating-card absolute top-10 -right-16 w-32 h-32 bg-white/5 backdrop-blur-2xl rounded-[2rem] border border-white/10 flex flex-col items-center justify-center shadow-2xl z-30 transform rotate-12">
                            <i data-lucide="heart" class="w-8 h-8 text-rose-500 fill-current animate-pulse"></i>
                            <span class="text-[8px] font-black text-white uppercase mt-2 tracking-widest">Syncing</span>
                        </div>
                        <div class="floating-card absolute bottom-20 -left-16 w-32 h-40 bg-blue-600/10 backdrop-blur-3xl rounded-[2rem] border border-blue-400/20 flex flex-col items-center justify-center shadow-2xl z-30 transform -rotate-12">
                            <div class="w-12 h-1 bg-blue-400 rounded-full mb-4"></div>
                            <div class="space-y-2 w-full px-6">
                                <div class="h-1 w-full bg-white/20 rounded-full"></div>
                                <div class="h-1 w-2/3 bg-white/20 rounded-full"></div>
                                <div class="h-1 w-full bg-white/20 rounded-full"></div>
                            </div>
                            <span class="text-[8px] font-black text-blue-400 uppercase mt-4 tracking-widest">Records</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <style>
        @keyframes shimmer {
            100% { transform: translateX(100%); }
        }
        .animate-shimmer {
            animation: shimmer 2s infinite;
        }
    </style>

    <!-- Premium PWA Instruction Modal -->
    <div id="pwa-guide-modal" class="fixed inset-0 z-[200] hidden items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm">
        <div class="bg-white rounded-[40px] shadow-2xl max-w-md w-full overflow-hidden transform transition-all">
            <div class="bg-blue-600 p-8 text-white relative">
                <button onclick="document.getElementById('pwa-guide-modal').classList.replace('flex', 'hidden')" class="absolute top-6 right-6 text-white/50 hover:text-white">
                    <i data-lucide="x" class="w-6 h-6"></i>
                </button>
                <div class="w-16 h-16 bg-white/20 rounded-2xl flex items-center justify-center mb-6">
                    <i data-lucide="smartphone" class="w-8 h-8"></i>
                </div>
                <h3 class="text-2xl font-black leading-tight">One Last Step to <br>Go Premium</h3>
            </div>
            <div class="p-8 space-y-8">
                <!-- iOS Instructions -->
                <div class="flex items-start gap-6">
                    <div class="w-10 h-10 rounded-full bg-slate-100 flex items-center justify-center shrink-0 font-black text-blue-600">1</div>
                    <div>
                        <p class="text-xs font-black text-slate-400 uppercase tracking-widest mb-2">For iPhone (Safari)</p>
                        <p class="text-sm font-bold text-slate-700">
                            Tap the <span class="inline-flex items-center px-2 py-1 bg-slate-100 rounded-md mx-1"><svg class="w-4 h-4 text-blue-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 12v8a2 2 0 002 2h12a2 2 0 002-2v-8M16 6l-4-4-4 4M12 2v13"/></svg> Share icon</span> at the bottom of your screen.
                        </p>
                    </div>
                </div>
                <!-- Universal Step 2 -->
                <div class="flex items-start gap-6">
                    <div class="w-10 h-10 rounded-full bg-slate-100 flex items-center justify-center shrink-0 font-black text-blue-600">2</div>
                    <div>
                        <p class="text-xs font-black text-slate-400 uppercase tracking-widest mb-2">All Devices</p>
                        <p class="text-sm font-bold text-slate-700">
                            Scroll down and select <span class="text-blue-600">"Add to Home Screen"</span> to launch the full-screen portal.
                        </p>
                    </div>
                </div>
                <button onclick="document.getElementById('pwa-guide-modal').classList.replace('flex', 'hidden')" class="w-full py-4 bg-slate-900 text-white rounded-2xl font-black text-sm uppercase tracking-widest hover:bg-blue-600 transition-colors">
                    Got it, thanks!
                </button>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            // PWA Install Logic
            let deferredPrompt;
            const installBtn = document.getElementById('pwa-install-btn');
            const guideModal = document.getElementById('pwa-guide-modal');
            const installSection = document.getElementById('pwa-install-section');

            window.addEventListener('beforeinstallprompt', (e) => {
                e.preventDefault();
                deferredPrompt = e;
                console.log('PWA: Ready to install');
            });

            if (installBtn) {
                installBtn.addEventListener('click', (e) => {
                    if (deferredPrompt) {
                        deferredPrompt.prompt();
                        deferredPrompt.userChoice.then((choiceResult) => {
                            if (choiceResult.outcome === 'accepted') {
                                console.log('PWA: User accepted install');
                                if(installSection) installSection.style.display = 'none';
                            }
                            deferredPrompt = null;
                        });
                    } else {
                        // Show visual guide for iOS or if prompt is missing
                        guideModal.classList.replace('hidden', 'flex');
                    }
                });
            }

            window.addEventListener('appinstalled', (evt) => {
                console.log('PWA: Hospital App installed successfully');
                if(installSection) installSection.style.display = 'none';
            });
        });
    </script>

<?php include 'includes/footer.php'; ?>