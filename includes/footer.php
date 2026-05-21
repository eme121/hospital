<?php require_once __DIR__ . '/db_connect.php'; ?>

    <!-- Premium Footer -->
    <footer class="bg-slate-900 text-white pt-24 pb-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            
            <!-- Strategic Synergy Strip (Tactile Glass Ticker) -->
            <div class="mb-20 py-10 px-8 bg-slate-800/20 backdrop-blur-xl rounded-[50px] border border-white/5 overflow-hidden relative group/ticker shadow-2xl shadow-black/20">
                <!-- Inner Glow Ornament -->
                <div class="absolute -top-24 -left-24 w-48 h-48 bg-blue-500/10 rounded-full blur-[80px]"></div>
                
                <div class="flex flex-col lg:flex-row items-center justify-between gap-12 relative z-10">
                    <div class="shrink-0 text-center lg:text-left">
                        <div class="inline-flex items-center gap-2 mb-2">
                            <span class="w-2 h-2 bg-blue-500 rounded-full animate-pulse"></span>
                            <span class="text-[10px] font-black text-blue-500 uppercase tracking-[0.4em] block">Strategic Network</span>
                        </div>
                        <h3 class="text-xl font-black">Global <span class="text-blue-500">Synergy</span>.</h3>
                    </div>
                    
                    <!-- Ticker Container -->
                    <div class="flex-grow overflow-hidden relative h-20 mask-edge flex items-center">
                        <div class="flex items-center gap-12 animate-infinite-scroll group-hover/ticker:[animation-play-state:paused]">
                            
                            <!-- Logo Pod Generator (Repeated for loop) -->
                            <?php for($i=0; $i<3; $i++): ?>
                            <div class="flex items-center gap-12 shrink-0">
                                <!-- Pod 1 -->
                                <div class="partner-pod group/pod relative w-32 h-16 bg-slate-900/40 backdrop-blur-md rounded-2xl border border-white/5 flex items-center justify-center p-4 transition-all duration-700 shadow-[inset_0_1px_1px_rgba(255,255,255,0.05)]">
                                    <img src="<?php echo BASE_URL; ?>/PARTNER/joni.jpg" alt="Joni & Friends" class="partner-logo h-7 w-auto grayscale brightness-[2] opacity-30 transition-all duration-700 group-hover/pod:grayscale-0 group-hover/pod:opacity-100 group-hover/pod:brightness-100">
                                    <div class="absolute inset-0 rounded-2xl bg-gradient-to-tr from-white/5 to-transparent opacity-0 group-hover/pod:opacity-100 transition-opacity"></div>
                                </div>
                                <!-- Pod 2 -->
                                <div class="partner-pod group/pod relative w-32 h-16 bg-slate-900/40 backdrop-blur-md rounded-2xl border border-white/5 flex items-center justify-center p-4 transition-all duration-700 shadow-[inset_0_1px_1px_rgba(255,255,255,0.05)]">
                                    <img src="<?php echo BASE_URL; ?>/PARTNER/mega1.jpg" alt="Mega Hospital" class="partner-logo h-7 w-auto grayscale brightness-[2] opacity-30 transition-all duration-700 group-hover/pod:grayscale-0 group-hover/pod:opacity-100 group-hover/pod:brightness-100">
                                    <div class="absolute inset-0 rounded-2xl bg-gradient-to-tr from-white/5 to-transparent opacity-0 group-hover/pod:opacity-100 transition-opacity"></div>
                                </div>
                                <!-- Pod 3 -->
                                <div class="partner-pod group/pod relative w-32 h-16 bg-slate-900/40 backdrop-blur-md rounded-2xl border border-white/5 flex items-center justify-center p-4 transition-all duration-700 shadow-[inset_0_1px_1px_rgba(255,255,255,0.05)]">
                                    <img src="<?php echo BASE_URL; ?>/PARTNER/pharma.jpg" alt="Pharmaceutical Partners" class="partner-logo h-7 w-auto grayscale brightness-[2] opacity-30 transition-all duration-700 group-hover/pod:grayscale-0 group-hover/pod:opacity-100 group-hover/pod:brightness-100">
                                    <div class="absolute inset-0 rounded-2xl bg-gradient-to-tr from-white/5 to-transparent opacity-0 group-hover/pod:opacity-100 transition-opacity"></div>
                                </div>
                                <!-- Divider Dot -->
                                <div class="w-1.5 h-1.5 rounded-full bg-slate-700/50 shadow-[0_0_10px_rgba(0,0,0,0.5)]"></div>
                            </div>
                            <?php endfor; ?>

                        </div>
                    </div>
                </div>

                <style>
                    .mask-edge {
                        mask-image: linear-gradient(to right, transparent, black 10%, black 90%, transparent);
                    }
                    @keyframes infinite-scroll {
                        from { transform: translateX(0); }
                        to { transform: translateX(calc(-33.33% - 3.25rem)); }
                    }
                    .animate-infinite-scroll {
                        animation: infinite-scroll 40s linear infinite;
                    }
                </style>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-16 mb-20">
                <!-- Brand Column -->
                <div class="space-y-8">
                    <div class="flex items-center gap-3">
                        <div class="w-12 h-12 bg-white rounded-2xl flex items-center justify-center shadow-lg shadow-blue-500/20 overflow-hidden">
                            <img src="<?php echo BASE_URL; ?>/logo.png" alt="Hope Haven Logo" class="w-10 h-10 object-contain">
                        </div>
                        <span class="text-2xl font-black tracking-tighter uppercase">Hope Haven <span class="text-blue-500">Hospital</span></span>
                    </div>
                    <p class="text-slate-400 font-medium leading-relaxed">
                        Redefining healthcare excellence through innovation, compassion, and world-class medical expertise.
                    </p>
                </div>

                <!-- Quick Links -->
                <div>
                    <h4 class="text-sm font-black uppercase tracking-widest text-blue-500 mb-8">Navigation</h4>
                    <ul class="space-y-4">
                        <li><a href="<?php echo BASE_URL; ?>/about.php" class="text-slate-400 hover:text-white transition-colors font-medium">Why Choose Us</a></li>
                        <li><a href="<?php echo BASE_URL; ?>/services.php" class="text-slate-400 hover:text-white transition-colors font-medium">Medical Services</a></li>
                        <li><a href="<?php echo BASE_URL; ?>/telemedicine.php" class="text-slate-400 hover:text-white transition-colors font-medium">Telemedicine</a></li>
                        <li><a href="<?php echo BASE_URL; ?>/impact.php" class="text-slate-400 hover:text-white transition-colors font-medium">Our Impact</a></li>
                    </ul>
                </div>

                <!-- Support -->
                <div>
                    <h4 class="text-sm font-black uppercase tracking-widest text-blue-500 mb-8">Patient Portal</h4>
                    <ul class="space-y-4">
                        <li><a href="<?php echo BASE_URL; ?>/patient_login.php" class="text-slate-400 hover:text-white transition-colors font-medium">Patient Login</a></li>
                        <li><a href="<?php echo BASE_URL; ?>/appointment.php" class="text-slate-400 hover:text-white transition-colors font-medium">Book Appointment</a></li>
                        <li><a href="<?php echo BASE_URL; ?>/contact.php" class="text-slate-400 hover:text-white transition-colors font-medium">Contact Support</a></li>
                        <li><a href="<?php echo BASE_URL; ?>/staff_portal.php" class="text-slate-400 hover:text-white transition-colors font-medium">Staff Portal</a></li>
                    </ul>
                </div>

                <!-- Contact Info -->
                <div>
                    <h4 class="text-sm font-black uppercase tracking-widest text-blue-500 mb-8">Contact Us</h4>
                    <ul class="space-y-6">
                        <li class="flex items-start gap-4">
                            <i data-lucide="map-pin" class="w-5 h-5 text-blue-500 shrink-0"></i>
                            <span class="text-slate-400 font-medium">Kwang, <br>Plateau State, Nigeria</span>
                        </li>
                        <li class="flex items-center gap-4">
                            <i data-lucide="phone" class="w-5 h-5 text-blue-500 shrink-0"></i>
                            <span class="text-slate-400 font-medium">+234 800 123 4567</span>
                        </li>
                        <li class="flex items-center gap-4">
                            <i data-lucide="mail" class="w-5 h-5 text-blue-500 shrink-0"></i>
                            <span class="text-slate-400 font-medium">contact@hopehaven.ng</span>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Bottom Bar -->
            <div class="pt-12 border-t border-slate-800 flex flex-col md:flex-row justify-between items-center gap-6">
                <p class="text-slate-500 text-sm font-medium">
                    &copy; <?php echo date('Y'); ?> Hope Haven Hospital. All rights reserved.
                </p>
                <div class="flex items-center gap-8">
                    <a href="#" class="text-slate-500 hover:text-white text-xs font-bold uppercase tracking-widest transition-colors">Privacy Policy</a>
                    <a href="#" class="text-slate-500 hover:text-white text-xs font-bold uppercase tracking-widest transition-colors">Terms of Service</a>
                </div>
            </div>
        </div>
    </footer>

    <!-- Chat AI Assistant (HOPE) -->
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/ai_assistant/chatbot.css?v=<?php echo time(); ?>">
    
    <div id="hope-chat-icon" class="three-d-glow" onclick="if(typeof toggleChat === 'function') toggleChat();">
        <div class="icon-inner">
            <img src="<?php echo BASE_URL; ?>/logo.png" alt="HOPE Logo" class="w-10 h-10 object-contain">
        </div>
    </div>

    <div id="hope-chat-label" onclick="if(typeof toggleChat === 'function') toggleChat();">
        <span>Ask HOPE</span>
        <div class="status-dot-small"></div>
    </div>

    <div id="hope-chat-window">
        <div class="chat-header">
            <div class="info">
                <div class="avatar bg-white overflow-hidden flex items-center justify-center">
                    <img src="<?php echo BASE_URL; ?>/logo.png" alt="HOPE AI" class="w-full h-full object-contain p-1">
                </div>
                <div>
                    <h4 class="text-sm font-black">HOPE AI</h4>
                    <p class="text-[10px] font-bold opacity-70 uppercase tracking-widest">Medical Assistant</p>
                </div>
            </div>
            <button onclick="document.getElementById('hope-chat-window').classList.remove('active')" class="text-white/50 hover:text-white transition-colors">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>
        
        <div id="chat-messages"></div>
        <div class="typing">HOPE is typing...</div>

        <div class="chat-input-area">
            <input type="text" id="chat-input" placeholder="Type your health question...">
            <button id="send-btn">
                <i data-lucide="send" class="w-5 h-5"></i>
            </button>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://unpkg.com/aos@next/dist/aos.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/lucide/0.263.0/lucide.min.js"></script>
    <script src="<?php echo BASE_URL; ?>/ai_assistant/chatbot.js?v=<?php echo time(); ?>"></script>
    <script>
        // Initialize AOS
        AOS.init({ once: false, offset: 100, duration: 800, easing: 'ease-out-back' });
        // Initialize Lucide Icons
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }
        
        // Safety toggle if main script fails to bind
        function toggleChat() {
            const win = document.getElementById('hope-chat-window');
            if(win) win.classList.toggle('active');
        }
    </script>
</body>
</html>