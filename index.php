<?php include 'includes/header.php'; ?>

<!-- Add Swiper.js CSS -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css" />
<style>
    .clinics-swiper {
        padding-bottom: 50px !important;
        overflow: visible !important;
    }
    .swiper-button-next, .swiper-button-prev {
        width: 50px !important;
        height: 50px !important;
        background: white !important;
        border-radius: 20px !important;
        box-shadow: 0 10px 30px -5px rgba(0, 0, 0, 0.1) !important;
        color: #2563eb !important;
        border: 1px solid #f1f5f9 !important;
    }
    .swiper-button-next:after, .swiper-button-prev:after {
        font-size: 20px !important;
        font-weight: 900 !important;
    }
    .swiper-pagination-bullet-active {
        background: #2563eb !important;
        width: 30px !important;
        border-radius: 10px !important;
    }
</style>

    <!-- Premium Video Hero Section -->
    <section id="home" class="relative h-[70vh] min-h-[500px] flex items-center justify-center overflow-hidden">
        <!-- Background Video -->
        <div class="absolute inset-0 z-0 bg-slate-900">
            <video id="hero-video" autoplay muted loop playsinline preload="none" 
                class="w-full h-full object-cover scale-125 animate-slow-zoom opacity-0" 
                style="filter: brightness(1.3) contrast(1.1) saturate(1.2); transition: opacity 2s ease-in-out;">
                <source src="<?php echo BASE_URL; ?>/hospitalvideocap2.mp4" type="video/mp4">
                <!-- Fallback Image -->
                <img src="<?php echo BASE_URL; ?>/assets/img/external/hero-fallback.jpg" class="w-full h-full object-cover" alt="Hospital Interior">
            </video>

            <script>
                document.addEventListener('DOMContentLoaded', () => {
                    const video = document.getElementById('hero-video');
                    if(!video) return;
                    // Only start loading when the main page is ready to prevent freezing
                    setTimeout(() => {
                        video.preload = "metadata";
                        video.load();
                        video.play().then(() => {
                            video.classList.remove('opacity-0');
                        }).catch(e => {
                            console.log("Video autoplay prevented or loading...");
                            video.classList.remove('opacity-0');
                        });
                    }, 1000);
                });
            </script>
            <!-- Overlay (Slightly lighter to let the brightened video show through) -->
            <div class="absolute inset-0 bg-gradient-to-b from-slate-900/40 via-transparent to-slate-900/80"></div>
        </div>

        <!-- Hero Content -->
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10 text-center">
            <div class="hero-reveal-delayed">
                <div class="inline-flex items-center px-4 py-2 mb-8 bg-blue-600/20 backdrop-blur-md text-blue-400 rounded-full border border-blue-500/30">
                    <span class="relative flex h-3 w-3 mr-3">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-blue-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-3 w-3 bg-blue-500"></span>
                    </span>
                    <span class="text-xs font-bold uppercase tracking-[0.2em]">Pioneering Premium Healthcare</span>
                </div>
                
                <h1 class="text-5xl md:text-7xl lg:text-8xl font-black text-white leading-[1.1] mb-8 tracking-tighter">
                    Healing Lives,<br>
                    <span class="text-transparent bg-clip-text bg-gradient-to-r from-blue-400 to-indigo-300">Restoring Hope</span>.
                </h1>
                
                <p class="text-lg md:text-xl text-slate-200 mb-12 max-w-2xl mx-auto leading-relaxed font-medium opacity-90">
                    Experience a new standard of medical excellence in Nigeria. Our world-class facilities and expert specialists are dedicated to your complete recovery and well-being.
                </p>
                
                <div class="flex flex-col sm:flex-row items-center justify-center gap-6">
                    <a href="appointment.php" class="group relative inline-flex items-center justify-center px-10 py-5 bg-blue-600 text-white rounded-2xl font-black text-lg shadow-2xl shadow-blue-600/40 hover:bg-blue-700 hover:-translate-y-1 transition-all duration-300 overflow-hidden">
                        <span class="relative z-10">Book Appointment</span>
                        <div class="absolute inset-0 bg-gradient-to-r from-white/0 via-white/20 to-white/0 translate-x-[-100%] group-hover:translate-x-[100%] transition-transform duration-1000"></div>
                    </a>
                    <a href="app.php" class="inline-flex items-center justify-center px-10 py-5 bg-white/10 backdrop-blur-md border border-white/20 text-white rounded-2xl font-black text-lg hover:bg-white hover:text-slate-900 transition-all duration-300 gap-3 group">
                        <i data-lucide="smartphone" class="w-5 h-5 group-hover:scale-110 transition-transform"></i>
                        Download App
                    </a>
                    <a href="about.php" class="inline-flex items-center justify-center px-10 py-5 bg-white/10 backdrop-blur-md border border-white/20 text-white rounded-2xl font-black text-lg hover:bg-white hover:text-slate-900 transition-all duration-300">
                        Explore Our Services
                    </a>
                </div>
            </div>
        </div>

        <!-- Scroll Down Indicator -->
        <div class="absolute bottom-10 left-1/2 -translate-x-1/2 z-10 flex flex-col items-center gap-2">
            <span class="text-[10px] font-bold text-white/50 uppercase tracking-[0.3em]">Scroll Down</span>
            <div class="w-6 h-10 border-2 border-white/30 rounded-full flex justify-center p-1">
                <div class="w-1 h-2 bg-blue-400 rounded-full animate-scroll-indicator"></div>
            </div>
        </div>

        <!-- Extra Styles for Hero -->
        <style>
            .hero-reveal-delayed {
                opacity: 0;
                animation: reveal-hero 1.5s cubic-bezier(0.4, 0, 0.2, 1) forwards;
                animation-delay: 0.5s;
            }
            @keyframes reveal-hero {
                from { opacity: 0; transform: translateY(30px); }
                to { opacity: 1; transform: translateY(0); }
            }
            @keyframes slow-zoom {
                from { transform: scale(1.05); }
                to { transform: scale(1.15); }
            }
            .animate-slow-zoom {
                animation: slow-zoom 20s ease-in-out infinite alternate;
            }
            @keyframes scroll-indicator {
                0% { transform: translateY(0); opacity: 1; }
                100% { transform: translateY(15px); opacity: 0; }
            }
            .animate-scroll-indicator {
                animation: scroll-indicator 2s infinite;
            }
        </style>
    </section>

    <!-- Info Bar -->
    <section class="py-12 bg-white border-y border-slate-100">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-12 text-center md:text-left">
                <div class="flex flex-col md:flex-row items-center md:items-start space-y-4 md:space-y-0 md:space-x-6 group">
                    <div class="w-14 h-14 bg-red-600 text-white rounded-2xl flex items-center justify-center shrink-0 shadow-lg shadow-red-200 transition-transform group-hover:scale-110">
                        <i data-lucide="phone-forwarded" class="w-7 h-7"></i>
                    </div>
                    <div>
                        <h4 class="font-extrabold text-slate-900 mb-1">Emergency 24/7</h4>
                        <p class="text-red-600 font-black text-xl">+234 800 123 4567</p>
                    </div>
                </div>
                <a href="telemedicine.php" class="flex flex-col md:flex-row items-center md:items-start space-y-4 md:space-y-0 md:space-x-6 group">
                    <div class="w-14 h-14 bg-blue-600 text-white rounded-2xl flex items-center justify-center shrink-0 shadow-lg shadow-blue-200 transition-all group-hover:scale-110 group-hover:brightness-110">
                        <i data-lucide="video" class="w-7 h-7"></i>
                    </div>
                    <div>
                        <h4 class="font-extrabold text-slate-900 mb-1 group-hover:text-blue-600 transition-colors">e-Review Request</h4>
                        <p class="text-slate-500 font-medium text-sm">Online consultations with our specialists.</p>
                    </div>
                </a>
                <a href="appointment.php" class="flex flex-col md:flex-row items-center md:items-start space-y-4 md:space-y-0 md:space-x-6 group">
                    <div class="w-14 h-14 bg-orange-500 text-white rounded-2xl flex items-center justify-center shrink-0 shadow-lg shadow-orange-200 transition-all group-hover:scale-110 group-hover:brightness-110">
                        <i data-lucide="calendar-days" class="w-7 h-7"></i>
                    </div>
                    <div>
                        <h4 class="font-extrabold text-slate-900 mb-1 group-hover:text-orange-600 transition-colors">Book Appointment</h4>
                        <p class="text-slate-500 font-medium text-sm">Schedule a visit with our experts.</p>
                    </div>
                </a>
                <div class="flex flex-col md:flex-row items-center md:items-start space-y-4 md:space-y-0 md:space-x-6 group">
                    <div class="w-14 h-14 bg-emerald-500 text-white rounded-2xl flex items-center justify-center shrink-0 shadow-lg shadow-emerald-200 transition-transform group-hover:scale-110">
                        <i data-lucide="clock" class="w-7 h-7"></i>
                    </div>
                    <div>
                        <h4 class="font-extrabold text-slate-900 mb-1">Opening Hours</h4>
                        <p class="text-slate-500 font-medium text-sm">24/7 Service: Mon - Sun</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- About Introduction Section (Refined Creative Design) -->
    <section class="py-24 bg-white overflow-hidden relative">
        <div class="absolute top-0 left-0 w-full h-full bg-[radial-gradient(#e2e8f0_1px,transparent_1px)] [background-size:40px_40px] opacity-20"></div>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
            <div class="grid lg:grid-cols-12 gap-16 items-center">
                
                <!-- Left: Creative Image Composition (40%) -->
                <div class="lg:col-span-5 relative" data-aos="fade-right">
                    <div class="relative rounded-[40px] overflow-hidden border-[8px] border-slate-50 shadow-xl max-w-[90%] mx-auto">
                        <img src="assets/external/doc.png" class="w-full aspect-square object-cover" alt="Hospital Professional">
                        <div class="absolute inset-0 bg-gradient-to-t from-slate-900/40 to-transparent"></div>
                    </div>
                    
                    <!-- Overlapping Stat Card (Interactive) -->
                    <div class="absolute -bottom-10 -right-4 w-48 bg-blue-600 rounded-3xl shadow-2xl p-6 text-white transition-all duration-500 hover:w-64 group/stat animate-float-slow cursor-default overflow-hidden">
                        <!-- Visual Hint Icon -->
                        <div class="absolute top-4 right-4 group-hover/stat:opacity-0 transition-opacity duration-300">
                            <span class="relative flex h-3 w-3">
                                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-white opacity-75"></span>
                                <i data-lucide="info" class="relative inline-flex w-3 h-3 text-white"></i>
                            </span>
                        </div>

                        <div class="relative z-10">
                            <p class="text-3xl font-black mb-1">15+</p>
                            <p class="text-[10px] font-bold uppercase tracking-widest opacity-80 mb-0 group-hover/stat:mb-3 transition-all">Years of Clinical Excellence</p>
                            
                            <!-- Hint Text -->
                            <p class="text-[8px] font-black uppercase tracking-[0.2em] mt-2 group-hover/stat:hidden animate-pulse opacity-60">Hover to learn more</p>
                            
                            <!-- Expanded Content -->
                            <div class="max-h-0 opacity-0 overflow-hidden group-hover/stat:max-h-32 group-hover/stat:opacity-100 transition-all duration-700">
                                <p class="text-[11px] leading-relaxed font-medium text-blue-50 border-t border-white/20 pt-3">
                                    Since our founding, we have pioneered advanced surgical techniques and compassionate care, serving over 50,000 patients with a commitment to medical innovation.
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Subtle Floating Element -->
                    <div class="absolute -top-6 -left-6 w-24 h-24 bg-blue-50 rounded-full mix-blend-multiply filter blur-xl animate-pulse"></div>
                </div>

                <!-- Right: Content (60%) -->
                <div class="lg:col-span-7" data-aos="fade-left">
                    <div class="inline-flex items-center px-4 py-2 mb-6 bg-blue-50 text-blue-600 rounded-xl">
                        <span class="text-[10px] font-black uppercase tracking-widest">About Our Mission</span>
                    </div>
                    <h2 class="text-3xl lg:text-5xl font-black text-slate-900 leading-tight mb-6">
                        Hope Haven Hospital:<br>
                        <span class="text-blue-600">Your Partner in Health.</span>
                    </h2>
                    <p class="text-lg text-slate-600 font-medium leading-relaxed mb-8">
                        We combine compassion with cutting-edge medical care to serve patients from near and far. From routine check-ups to critical care, our commitment is to provide exceptional service every time.
                    </p>

                    <!-- Feature Grid -->
                    <div class="grid sm:grid-cols-2 gap-4">
                        <div class="flex items-center space-x-3 p-4 bg-slate-50 rounded-2xl border border-slate-100 hover:border-blue-100 transition-colors">
                            <div class="w-8 h-8 bg-blue-600 text-white rounded-lg flex items-center justify-center shrink-0">
                                <i data-lucide="check" class="w-4 h-4"></i>
                            </div>
                            <span class="text-xs font-black text-slate-800 uppercase tracking-tight">Patient-Centered</span>
                        </div>
                        <div class="flex items-center space-x-3 p-4 bg-slate-50 rounded-2xl border border-slate-100 hover:border-blue-100 transition-colors">
                            <div class="w-8 h-8 bg-blue-600 text-white rounded-lg flex items-center justify-center shrink-0">
                                <i data-lucide="globe" class="w-4 h-4"></i>
                            </div>
                            <span class="text-xs font-black text-slate-800 uppercase tracking-tight">Globally Informed</span>
                        </div>
                        <div class="flex items-center space-x-3 p-4 bg-slate-50 rounded-2xl border border-slate-100 hover:border-blue-100 transition-colors">
                            <div class="w-8 h-8 bg-blue-600 text-white rounded-lg flex items-center justify-center shrink-0">
                                <i data-lucide="shield-check" class="w-4 h-4"></i>
                            </div>
                            <span class="text-xs font-black text-slate-800 uppercase tracking-tight">Safe Environment</span>
                        </div>
                        <div class="flex items-center space-x-3 p-4 bg-slate-50 rounded-2xl border border-slate-100 hover:border-blue-100 transition-colors">
                            <div class="w-8 h-8 bg-blue-600 text-white rounded-lg flex items-center justify-center shrink-0">
                                <i data-lucide="award" class="w-4 h-4"></i>
                            </div>
                            <span class="text-xs font-black text-slate-800 uppercase tracking-tight">Clinical Excellence</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <style>
            @keyframes float-slow {
                0%, 100% { transform: translateY(0); }
                50% { transform: translateY(-20px); }
            }
            .animate-float-slow {
                animation: float-slow 6s ease-in-out infinite;
            }
        </style>
    </section>

    <!-- Patient Care Excellence & Core Services -->
    <section class="py-24 bg-slate-50 overflow-hidden relative">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
            <div class="grid lg:grid-cols-12 gap-16 items-center">
                
                <!-- Left: Mission Text -->
                <div class="lg:col-span-7" data-aos="fade-right">
                    <div class="inline-flex items-center px-4 py-2 mb-6 bg-white border border-slate-200 text-blue-600 rounded-2xl font-black text-[10px] uppercase tracking-[0.2em] shadow-sm">
                        Clinical Integrity
                    </div>
                    <h2 class="text-4xl lg:text-5xl font-black text-slate-900 leading-tight mb-8">
                        Individualised <span class="text-blue-600">Care Plans</span> <br>Designed for You.
                    </h2>
                    <p class="text-xl text-slate-600 font-medium leading-relaxed mb-8">
                        We will work with you to develop individualised care plans, including management of chronic diseases. If we cannot assist, we can provide referrals or advice about the type of practitioner you require.
                    </p>
                    <!-- Rotating Trust Engine (Fixed & Robust) -->
                    <div class="inline-flex items-center gap-4 px-6 py-4 bg-blue-50/50 rounded-2xl border border-blue-100 min-w-[340px]">
                        <div id="trust-icon-container" class="w-10 h-10 bg-blue-600 rounded-xl flex items-center justify-center shrink-0 shadow-lg shadow-blue-200 relative">
                            <i data-lucide="shield-check" class="trust-icon w-5 h-5 text-white transition-all duration-500 absolute opacity-100 scale-100"></i>
                            <i data-lucide="lock" class="trust-icon w-5 h-5 text-white transition-all duration-500 absolute opacity-0 scale-0 rotate-90"></i>
                            <i data-lucide="activity" class="trust-icon w-5 h-5 text-white transition-all duration-500 absolute opacity-0 scale-0 rotate-90"></i>
                        </div>
                        <div class="overflow-hidden h-5 relative flex-grow">
                            <div id="trust-text-container">
                                <p class="trust-message text-xs font-black text-slate-700 tracking-tight absolute inset-0 opacity-100 translate-y-0 transition-all duration-700">Strict Confidentiality Guaranteed</p>
                                <p class="trust-message text-xs font-black text-slate-700 tracking-tight absolute inset-0 opacity-0 translate-y-4 transition-all duration-700">HIPAA Compliant Data Handling</p>
                                <p class="trust-message text-xs font-black text-slate-700 tracking-tight absolute inset-0 opacity-0 translate-y-4 transition-all duration-700">24/7 Secure Medical Support</p>
                            </div>
                        </div>
                    </div>

                    <script>
                        document.addEventListener('DOMContentLoaded', () => {
                            const messages = document.querySelectorAll('.trust-message');
                            const icons = document.querySelectorAll('.trust-icon');
                            let current = 0;

                            setInterval(() => {
                                // Fade out current message and icon
                                messages[current].classList.replace('opacity-100', 'opacity-0');
                                messages[current].classList.add('translate-y-[-16px]');
                                
                                icons[current].classList.replace('opacity-100', 'opacity-0');
                                icons[current].classList.replace('scale-100', 'scale-0');
                                icons[current].classList.add('rotate-[-90deg]');

                                current = (current + 1) % messages.length;

                                // Fade in next message and icon
                                messages[current].classList.remove('translate-y-[-16px]', 'translate-y-4');
                                messages[current].classList.replace('opacity-0', 'opacity-100');
                                messages[current].classList.add('translate-y-0');

                                icons[current].classList.remove('rotate-[-90deg]', 'rotate-90');
                                icons[current].classList.replace('opacity-0', 'opacity-100');
                                icons[current].classList.replace('scale-0', 'scale-100');
                                icons[current].classList.add('rotate-0');
                            }, 4000);
                        });
                    </script>
                </div>

                <!-- Right: Service Highlights Grid -->
                <div id="service-highlights" class="lg:col-span-5 grid grid-cols-2 gap-4">
                    <div class="service-highlight-item group bg-white p-5 rounded-[30px] border border-slate-100 hover:border-blue-200 hover:shadow-xl transition-all flex flex-col items-start gap-4 opacity-0 translate-x-20">
                        <div class="w-12 h-12 bg-red-50 text-red-600 rounded-xl flex items-center justify-center group-hover:scale-110 transition-transform">
                            <i data-lucide="bone" class="w-6 h-6"></i>
                        </div>
                        <div>
                            <h4 class="font-black text-slate-800 text-sm mb-1">Fractures</h4>
                            <p class="text-[10px] text-slate-500 font-medium leading-relaxed">Expert orthopedic care for urgent bone injuries.</p>
                        </div>
                    </div>
                    <div class="service-highlight-item group bg-white p-5 rounded-[30px] border border-slate-100 hover:border-blue-200 hover:shadow-xl transition-all flex flex-col items-start gap-4 opacity-0 translate-x-20">
                        <div class="w-12 h-12 bg-blue-50 text-blue-600 rounded-xl flex items-center justify-center group-hover:scale-110 transition-transform">
                            <i data-lucide="syringe" class="w-6 h-6"></i>
                        </div>
                        <div>
                            <h4 class="font-black text-slate-800 text-sm mb-1">Injections</h4>
                            <p class="text-[10px] text-slate-500 font-medium leading-relaxed">Specialized immunotherapy for allergic reactions.</p>
                        </div>
                    </div>
                    <div class="service-highlight-item group bg-white p-5 rounded-[30px] border border-slate-100 hover:border-blue-200 hover:shadow-xl transition-all flex flex-col items-start gap-4 opacity-0 translate-x-20">
                        <div class="w-12 h-12 bg-emerald-50 text-emerald-600 rounded-xl flex items-center justify-center group-hover:scale-110 transition-transform">
                            <i data-lucide="pill" class="w-6 h-6"></i>
                        </div>
                        <div>
                            <h4 class="font-black text-slate-800 text-sm mb-1">Meds Review</h4>
                            <p class="text-[10px] text-slate-500 font-medium leading-relaxed">In-home assessment of your current medications.</p>
                        </div>
                    </div>
                    <div class="service-highlight-item group bg-white p-5 rounded-[30px] border border-slate-100 hover:border-blue-200 hover:shadow-xl transition-all flex flex-col items-start gap-4 opacity-0 translate-x-20">
                        <div class="w-12 h-12 bg-indigo-50 text-indigo-600 rounded-xl flex items-center justify-center group-hover:scale-110 transition-transform">
                            <i data-lucide="clipboard-check" class="w-6 h-6"></i>
                        </div>
                        <div>
                            <h4 class="font-black text-slate-800 text-sm mb-1">Assessments</h4>
                            <p class="text-[10px] text-slate-500 font-medium leading-relaxed">Detailed check-ups for your specific health risks.</p>
                        </div>
                    </div>
                    <div class="service-highlight-item col-span-2 group bg-white p-5 rounded-[30px] border border-slate-100 hover:border-blue-200 hover:shadow-xl transition-all flex items-center gap-6 opacity-0 translate-x-20">
                        <div class="w-12 h-12 bg-orange-50 text-orange-600 rounded-xl flex items-center justify-center group-hover:scale-110 transition-transform shrink-0">
                            <i data-lucide="heart-pulse" class="w-6 h-6"></i>
                        </div>
                        <div>
                            <h4 class="font-black text-slate-800 text-sm mb-1">Chronic Disease Care</h4>
                            <p class="text-[10px] text-slate-500 font-medium">Long-term management for diabetes, hypertension, and complex chronic conditions.</p>
                        </div>
                    </div>
                </div>

                <script>
                    document.addEventListener('DOMContentLoaded', () => {
                        if (typeof gsap !== 'undefined') {
                            gsap.to(".service-highlight-item", {
                                scrollTrigger: {
                                    trigger: "#service-highlights",
                                    start: "top 85%",
                                    toggleActions: "play none none none"
                                },
                                x: 0,
                                opacity: 1,
                                duration: 1.2,
                                stagger: 0.15,
                                ease: "power4.out"
                            });
                        }
                    });
                </script>

            </div>
        </div>
    </section>

    <!-- Health Tools CTA (Compact Premium Version) -->
    <section class="py-16 bg-gradient-to-r from-blue-700 to-indigo-800 relative overflow-hidden">
        <div class="absolute top-0 right-0 w-96 h-96 bg-white/5 rounded-full -mr-48 -mt-48 blur-3xl"></div>
        <div class="absolute bottom-0 left-0 w-64 h-64 bg-white/5 rounded-full -ml-32 -mb-32 blur-3xl"></div>
        
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
            <div class="flex flex-col lg:flex-row items-center justify-between gap-10">
                <div class="text-white max-w-2xl text-center lg:text-left" data-aos="fade-right">
                    <div class="inline-flex items-center px-3 py-1 mb-6 bg-white/10 backdrop-blur-md border border-white/20 rounded-xl">
                        <span class="text-[10px] font-black uppercase tracking-widest">Clinical Decision Support</span>
                    </div>
                    <h2 class="text-3xl lg:text-4xl font-black mb-6 leading-tight">Smart Medical <span class="text-blue-300">Calculators</span>.</h2>
                    
                    <!-- Tool Pills -->
                    <div class="flex flex-wrap justify-center lg:justify-start gap-3 mb-8">
                        <div class="px-4 py-2 bg-white/10 backdrop-blur-sm border border-white/10 rounded-2xl flex items-center gap-2">
                            <i data-lucide="calculator" class="w-3 h-3 text-blue-300"></i>
                            <span class="text-[10px] font-black uppercase tracking-wider">BMI Tracker</span>
                        </div>
                        <div class="px-4 py-2 bg-white/10 backdrop-blur-sm border border-white/10 rounded-2xl flex items-center gap-2">
                            <i data-lucide="baby" class="w-3 h-3 text-blue-300"></i>
                            <span class="text-[10px] font-black uppercase tracking-wider">Pediatric Dosage</span>
                        </div>
                        <div class="px-4 py-2 bg-white/10 backdrop-blur-sm border border-white/10 rounded-2xl flex items-center gap-2">
                            <i data-lucide="heart" class="w-3 h-3 text-blue-300"></i>
                            <span class="text-[10px] font-black uppercase tracking-wider">Cardiac Risks</span>
                        </div>
                    </div>
                </div>

                <div data-aos="fade-left" class="shrink-0">
                    <a href="calculator.php" class="inline-flex items-center px-10 py-5 bg-white text-blue-700 rounded-2xl font-black text-lg hover:bg-blue-50 transition-all shadow-2xl shadow-blue-900/40 group">
                        Launch Tools
                        <i data-lucide="arrow-right" class="w-6 h-6 ml-3 group-hover:translate-x-1 transition-transform"></i>
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- Specialized Clinics (Enhanced Unique Design) -->
    <section class="py-24 bg-white overflow-hidden" id="clinics">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-20" data-aos="fade-up">
                <span class="text-sm font-black text-blue-600 uppercase tracking-[0.4em] mb-4 block">Our Excellence</span>
                <h2 class="text-4xl lg:text-6xl font-black text-slate-900 leading-tight">Specialized <span class="text-blue-600">Medical Clinics</span></h2>
                <p class="text-slate-500 mt-6 font-medium max-w-2xl mx-auto">Experience world-class healthcare across our diverse range of specialized departments, each equipped with cutting-edge technology and expert consultants.</p>
            </div>

            <!-- Swiper Slider for Clinics -->
            <div class="swiper clinics-swiper" data-aos="fade-up">
                <div class="swiper-wrapper">
                    <!-- Endocrinology Clinic -->
                    <div class="swiper-slide">
                        <div class="clinic-card group relative rounded-[45px] bg-white border border-slate-100 hover:border-blue-200 transition-all duration-700 overflow-hidden shadow-sm h-full">
                            <div class="h-48 overflow-hidden relative">
                                <img src="<?php echo BASE_URL; ?>/assets/img/clinics/endocrinology.jpg" class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-110" alt="Endocrinology">
                                <div class="absolute inset-0 bg-gradient-to-t from-white via-transparent to-transparent"></div>
                            </div>
                            <div class="p-10 pt-0 relative z-10">
                                <h3 class="text-xl font-black text-slate-900 mb-3">Endocrinology</h3>
                                <p class="text-slate-500 text-sm font-medium leading-relaxed mb-8">Specialized management of diabetes, hormonal imbalances, and metabolic disorders.</p>
                                <a href="appointment.php" class="inline-flex items-center text-blue-600 font-black text-xs uppercase tracking-widest group-hover:gap-3 transition-all">
                                    Book Now <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"></path></svg>
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Cardiovascular Clinic -->
                    <div class="swiper-slide">
                        <div class="clinic-card group relative rounded-[45px] bg-white border border-slate-100 hover:border-blue-200 transition-all duration-700 overflow-hidden shadow-sm h-full">
                            <div class="h-48 overflow-hidden relative">
                                <img src="<?php echo BASE_URL; ?>/assets/img/clinics/cardiovascular.jpg" class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-110" alt="Cardiovascular">
                                <div class="absolute inset-0 bg-gradient-to-t from-white via-transparent to-transparent"></div>
                            </div>
                            <div class="p-10 pt-0 relative z-10">
                                <h3 class="text-xl font-black text-slate-900 mb-3">Cardiovascular</h3>
                                <p class="text-slate-500 text-sm font-medium leading-relaxed mb-8">Advanced heart care services including diagnostics, surgery, and long-term management.</p>
                                <a href="appointment.php" class="inline-flex items-center text-blue-600 font-black text-xs uppercase tracking-widest group-hover:gap-3 transition-all">
                                    Book Now <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"></path></svg>
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Geriatric Clinic -->
                    <div class="swiper-slide">
                        <div class="clinic-card group relative rounded-[45px] bg-white border border-slate-100 hover:border-blue-200 transition-all duration-700 overflow-hidden shadow-sm h-full">
                            <div class="h-48 overflow-hidden relative">
                                <img src="<?php echo BASE_URL; ?>/assets/img/clinics/geriatric.jpg" class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-110" alt="Geriatric">
                                <div class="absolute inset-0 bg-gradient-to-t from-white via-transparent to-transparent"></div>
                            </div>
                            <div class="p-10 pt-0 relative z-10">
                                <h3 class="text-xl font-black text-slate-900 mb-3">Geriatric</h3>
                                <p class="text-slate-500 text-sm font-medium leading-relaxed mb-8">Comprehensive care for elderly patients, focusing on healthy aging and chronic disease care.</p>
                                <a href="appointment.php" class="inline-flex items-center text-blue-600 font-black text-xs uppercase tracking-widest group-hover:gap-3 transition-all">
                                    Book Now <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"></path></svg>
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Renal Clinic -->
                    <div class="swiper-slide">
                        <div class="clinic-card group relative rounded-[45px] bg-white border border-slate-100 hover:border-blue-200 transition-all duration-700 overflow-hidden shadow-sm h-full">
                            <div class="h-48 overflow-hidden relative">
                                <img src="<?php echo BASE_URL; ?>/assets/img/clinics/renal.jpg" class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-110" alt="Renal">
                                <div class="absolute inset-0 bg-gradient-to-t from-white via-transparent to-transparent"></div>
                            </div>
                            <div class="p-10 pt-0 relative z-10">
                                <h3 class="text-xl font-black text-slate-900 mb-3">Renal</h3>
                                <p class="text-slate-500 text-sm font-medium leading-relaxed mb-8">Expert kidney health management including state-of-the-art dialysis and renal therapy.</p>
                                <a href="appointment.php" class="inline-flex items-center text-blue-600 font-black text-xs uppercase tracking-widest group-hover:gap-3 transition-all">
                                    Book Now <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"></path></svg>
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Eye Clinic -->
                    <div class="swiper-slide">
                        <div class="clinic-card group relative rounded-[45px] bg-white border border-slate-100 hover:border-blue-200 transition-all duration-700 overflow-hidden shadow-sm h-full">
                            <div class="h-48 overflow-hidden relative">
                                <img src="<?php echo BASE_URL; ?>/assets/img/clinics/eye.jpg" class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-110" alt="Eye Clinic">
                                <div class="absolute inset-0 bg-gradient-to-t from-white via-transparent to-transparent"></div>
                            </div>
                            <div class="p-10 pt-0 relative z-10">
                                <h3 class="text-xl font-black text-slate-900 mb-3">Eye Clinic</h3>
                                <p class="text-slate-500 text-sm font-medium leading-relaxed mb-8">Professional ophthalmology services for all ages, from vision tests to corrective surgery.</p>
                                <a href="appointment.php" class="inline-flex items-center text-blue-600 font-black text-xs uppercase tracking-widest group-hover:gap-3 transition-all">
                                    Book Now <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"></path></svg>
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Hematology Clinic -->
                    <div class="swiper-slide">
                        <div class="clinic-card group relative rounded-[45px] bg-white border border-slate-100 hover:border-blue-200 transition-all duration-700 overflow-hidden shadow-sm h-full">
                            <div class="h-48 overflow-hidden relative">
                                <img src="<?php echo BASE_URL; ?>/assets/img/clinics/hematology.jpg" class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-110" alt="Hematology">
                                <div class="absolute inset-0 bg-gradient-to-t from-white via-transparent to-transparent"></div>
                            </div>
                            <div class="p-10 pt-0 relative z-10">
                                <h3 class="text-xl font-black text-slate-900 mb-3">Hematology</h3>
                                <p class="text-slate-500 text-sm font-medium leading-relaxed mb-8">Specialized diagnosis and treatment of blood disorders and related complications.</p>
                                <a href="appointment.php" class="inline-flex items-center text-blue-600 font-black text-xs uppercase tracking-widest group-hover:gap-3 transition-all">
                                    Book Now <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"></path></svg>
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Physiotherapy Clinic -->
                    <div class="swiper-slide">
                        <div class="clinic-card group relative rounded-[45px] bg-white border border-slate-100 hover:border-blue-200 transition-all duration-700 overflow-hidden shadow-sm h-full">
                            <div class="h-48 overflow-hidden relative">
                                <img src="<?php echo BASE_URL; ?>/assets/img/clinics/physiotherapy.jpg" class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-110" alt="Physiotherapy">
                                <div class="absolute inset-0 bg-gradient-to-t from-white via-transparent to-transparent"></div>
                            </div>
                            <div class="p-10 pt-0 relative z-10">
                                <h3 class="text-xl font-black text-slate-900 mb-3">Physiotherapy</h3>
                                <p class="text-slate-500 text-sm font-medium leading-relaxed mb-8">Expert physical therapy and rehabilitation programs to restore mobility and strength.</p>
                                <a href="appointment.php" class="inline-flex items-center text-blue-600 font-black text-xs uppercase tracking-widest group-hover:gap-3 transition-all">
                                    Book Now <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"></path></svg>
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Orthopedic Clinic -->
                    <div class="swiper-slide">
                        <div class="clinic-card group relative rounded-[45px] bg-white border border-slate-100 hover:border-blue-200 transition-all duration-700 overflow-hidden shadow-sm h-full">
                            <div class="h-48 overflow-hidden relative">
                                <img src="<?php echo BASE_URL; ?>/assets/img/clinics/orthopedic.jpg" class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-110" alt="Orthopedic">
                                <div class="absolute inset-0 bg-gradient-to-t from-white via-transparent to-transparent"></div>
                            </div>
                            <div class="p-10 pt-0 relative z-10">
                                <h3 class="text-xl font-black text-slate-900 mb-3">Orthopedic</h3>
                                <p class="text-slate-500 text-sm font-medium leading-relaxed mb-8">Comprehensive bone and joint care, specialized orthopedic surgery, and recovery.</p>
                                <a href="appointment.php" class="inline-flex items-center text-blue-600 font-black text-xs uppercase tracking-widest group-hover:gap-3 transition-all">
                                    Book Now <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"></path></svg>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Swiper Navigation -->
                <div class="swiper-button-next"></div>
                <div class="swiper-button-prev"></div>
                <!-- Swiper Pagination -->
                <div class="swiper-pagination"></div>
            </div>

            <!-- Global Style for Clinic Cards -->
            <style>
                .clinic-card {
                    box-shadow: 0 10px 30px -5px rgba(0, 0, 0, 0.04), 0 4px 10px -2px rgba(0, 0, 0, 0.02);
                }
                .clinic-card:hover {
                    box-shadow: 0 40px 80px -15px rgba(0, 0, 0, 0.1), 0 10px 30px -5px rgba(0, 0, 0, 0.05);
                    transform: translateY(-10px);
                }
            </style>
        </div>
    </section>

    <!-- Upcoming Events Section -->
    <section class="py-24 bg-slate-50 overflow-hidden">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex flex-col md:flex-row items-end justify-between mb-16 gap-6" data-aos="fade-up">
                <div class="max-w-2xl">
                    <span class="text-sm font-bold text-blue-600 uppercase tracking-[0.3em] mb-4 block">Stay Updated</span>
                    <h2 class="text-4xl lg:text-5xl font-black text-slate-900 leading-tight">Upcoming <span class="text-blue-600">Events</span> & Activities</h2>
                    <p class="text-slate-500 mt-6 font-medium leading-relaxed">Join us in our mission to provide better healthcare through community engagement and medical workshops.</p>
                </div>
                <a href="events.php" class="inline-flex items-center px-8 py-4 bg-white text-blue-600 border border-blue-100 rounded-2xl font-black text-sm hover:bg-blue-600 hover:text-white transition-all shadow-xl shadow-blue-900/5 group">
                    View All Events
                    <svg class="w-5 h-5 ml-3 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"></path></svg>
                </a>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                <?php
                require_once 'includes/db_connect.php';
                $today = date('Y-m-d');
                $events_res = $conn->query("SELECT * FROM events WHERE is_deleted = 0 AND event_date >= '$today' ORDER BY event_date ASC LIMIT 3");
                if($events_res->num_rows > 0):
                    while($event = $events_res->fetch_assoc()):
                ?>
                    <div class="group bg-white rounded-[40px] overflow-hidden border border-slate-100 hover:shadow-2xl hover:shadow-blue-100 transition-all duration-500 flex flex-col h-full" data-aos="fade-up">
                        <div class="relative h-64 overflow-hidden">
                            <?php if($event['image']): ?>
                                <img src="assets/images/events/<?php echo $event['image']; ?>" class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-700" alt="<?php echo htmlspecialchars($event['title']); ?>">
                            <?php else: ?>
                                <div class="w-full h-full bg-blue-600 flex items-center justify-center">
                                    <svg class="w-20 h-20 text-blue-400 opacity-30" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                </div>
                            <?php endif; ?>
                            <div class="absolute top-6 left-6 bg-white/90 backdrop-blur-md px-4 py-2 rounded-2xl shadow-lg text-center min-w-[70px]">
                                <span class="block text-2xl font-black text-blue-600 leading-none"><?php echo date('d', strtotime($event['event_date'])); ?></span>
                                <span class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mt-1"><?php echo date('M', strtotime($event['event_date'])); ?></span>
                            </div>
                        </div>
                        <div class="p-10 flex flex-col flex-grow">
                            <h3 class="text-2xl font-black text-slate-900 mb-4 group-hover:text-blue-600 transition-colors"><?php echo htmlspecialchars($event['title']); ?></h3>
                            <p class="text-slate-500 text-sm leading-relaxed mb-8 flex-grow"><?php echo htmlspecialchars(substr($event['description'], 0, 120)) . '...'; ?></p>
                            <a href="events.php?id=<?php echo $event['id']; ?>" class="inline-flex items-center text-blue-600 font-black text-sm group-hover:gap-3 transition-all">
                                Event Details <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"></path></svg>
                            </a>
                        </div>
                    </div>
                <?php 
                    endwhile;
                else:
                ?>
                    <div class="col-span-full py-20 text-center bg-white rounded-[40px] border border-dashed border-slate-200">
                        <p class="text-slate-400 font-bold italic">No upcoming events scheduled at the moment. Check back soon!</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- Outreach Story Preview Section -->
    <section class="py-24 bg-slate-50 overflow-hidden">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="bg-white rounded-[64px] p-8 lg:p-16 shadow-2xl border border-slate-100 relative overflow-hidden group" data-aos="zoom-in">
                
                <div class="grid lg:grid-cols-2 gap-16 items-center">
                    <div class="relative z-10 order-2 lg:order-1">
                        <div class="inline-flex items-center px-4 py-2 mb-6 bg-blue-50 text-blue-600 rounded-2xl">
                            <span class="text-xs font-bold uppercase tracking-widest">Changing Lives Through Care</span>
                        </div>
                        <h2 class="text-4xl lg:text-5xl font-black text-slate-900 leading-tight mb-6">Our Impact: <span class="text-blue-600">Mobility & Hope Outreach</span></h2>
                        <p class="text-lg text-slate-500 mb-10 leading-relaxed font-medium">
                            Experience the emotional journey of our 2026 wheelchair distribution program. We're not just providing mobility; we're unlocking new futures for our community members.
                        </p>
                        <div class="flex flex-col sm:flex-row gap-5">
                            <a href="impact.php" class="inline-flex items-center justify-center px-10 py-5 bg-blue-600 text-white rounded-2xl font-extrabold btn-premium text-lg shadow-xl shadow-blue-200 group">
                                Watch Full Story
                                <svg class="w-6 h-6 ml-3 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            </a>
                        </div>
                    </div>

                    <div class="relative order-1 lg:order-2" data-aos="fade-left" data-aos-delay="200">
                        <div class="relative z-10 bg-slate-900 p-3 rounded-[48px] shadow-2xl border border-slate-200 overflow-hidden transform group-hover:scale-[1.02] transition-transform duration-700">
                            <!-- Short Video Preview -->
                            <div class="relative rounded-[36px] overflow-hidden aspect-video bg-black">
                                <video autoplay muted loop playsinline class="w-full h-full object-cover opacity-80 group-hover:opacity-100 transition-opacity duration-700">
                                    <source src="videos/wheelchairscreenin2026.mp4" type="video/mp4">
                                </video>
                                <div class="absolute inset-0 bg-gradient-to-t from-slate-900/40 to-transparent pointer-events-none"></div>
                                <div class="absolute inset-0 flex items-center justify-center pointer-events-none">
                                    <div class="w-20 h-20 bg-white/20 backdrop-blur-md rounded-full flex items-center justify-center border border-white/30 animate-pulse">
                                        <svg class="w-10 h-10 text-white fill-current" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"></path></svg>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- Background Decorative Elements -->
                        <div class="absolute -top-12 -right-12 w-64 h-64 bg-blue-100 rounded-full blur-3xl opacity-50 -z-10 group-hover:bg-blue-200 transition-colors duration-700"></div>
                        <div class="absolute -bottom-12 -left-12 w-64 h-64 bg-indigo-100 rounded-full blur-3xl opacity-50 -z-10"></div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Add Swiper.js JS and Initialization -->
    <script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            new Swiper('.clinics-swiper', {
                slidesPerView: 1,
                spaceBetween: 30,
                loop: true,
                centeredSlides: false,
                slidesPerGroup: 1,
                autoplay: {
                    delay: 6000,
                    disableOnInteraction: false,
                },
                pagination: {
                    el: '.swiper-pagination',
                    clickable: true,
                    dynamicBullets: true,
                },
                navigation: {
                    nextEl: '.swiper-button-next',
                    prevEl: '.swiper-button-prev',
                },
                breakpoints: {
                    640: {
                        slidesPerView: 2,
                        slidesPerGroup: 2,
                        centeredSlides: false,
                    },
                    1024: {
                        slidesPerView: 4,
                        slidesPerGroup: 4,
                        centeredSlides: false,
                    },
                },
            });
        });
    </script>

    <!-- Testimonial Section (Refined Split Trust Layout) -->
    <section class="py-16 bg-blue-50 relative overflow-hidden">
        <div class="absolute top-0 left-0 w-full h-full overflow-hidden pointer-events-none">
            <div class="absolute top-10 left-10 w-64 h-64 bg-blue-200/20 rounded-full blur-3xl"></div>
            <div class="absolute bottom-10 right-10 w-64 h-64 bg-indigo-200/20 rounded-full blur-3xl"></div>
        </div>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
            <div class="grid lg:grid-cols-12 gap-16 items-center">
                
                <!-- Left: Trust Stats (40%) -->
                <div class="lg:col-span-5" data-aos="fade-right">
                    <span class="text-[10px] font-black text-blue-600 uppercase tracking-[0.4em] mb-4 block">Patient Trust</span>
                    <h2 class="text-3xl lg:text-4xl font-black text-slate-900 leading-tight mb-8">What Our <span class="text-blue-600">Patients</span> Say</h2>
                    
                    <div class="grid grid-cols-2 gap-4 mt-10">
                        <div class="p-6 bg-white rounded-3xl shadow-sm border border-blue-100/50">
                            <p class="text-2xl font-black text-blue-600 mb-1">5k+</p>
                            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Happy Patients</p>
                        </div>
                        <div class="p-6 bg-white rounded-3xl shadow-sm border border-blue-100/50">
                            <p class="text-2xl font-black text-blue-600 mb-1">99%</p>
                            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Satisfaction</p>
                        </div>
                    </div>
                </div>

                <!-- Right: Testimonial Spotlight (60%) -->
                <div class="lg:col-span-7 relative" data-aos="fade-left">
                    <div class="testimonial-slider relative h-[280px]">
                        <!-- Testimonial 1 -->
                        <div class="testimonial-item absolute inset-0 bg-white p-8 lg:p-10 rounded-[40px] shadow-xl border border-slate-100 transition-all duration-700">
                            <div class="flex items-center space-x-4 mb-6">
                                <div class="w-12 h-12 rounded-full bg-blue-100 flex items-center justify-center text-blue-600 font-bold">SJ</div>
                                <div>
                                    <h4 class="font-black text-slate-900">Sarah Jenkins</h4>
                                    <p class="text-blue-600 text-[10px] font-black uppercase tracking-wider">Eye Clinic</p>
                                </div>
                            </div>
                            <p class="text-slate-500 italic leading-relaxed font-medium">"The eye clinic specialists were exceptional. My vision correction surgery was a success, and the clarity I have now is life-changing."</p>
                        </div>

                        <!-- Testimonial 2 (Hidden by default, cycled via JS) -->
                        <div class="testimonial-item absolute inset-0 bg-white p-8 lg:p-10 rounded-[40px] shadow-xl border border-slate-100 opacity-0 translate-y-4 transition-all duration-700 pointer-events-none">
                            <div class="flex items-center space-x-4 mb-6">
                                <div class="w-12 h-12 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-600 font-bold">MD</div>
                                <div>
                                    <h4 class="font-black text-slate-900">Michael Durotoye</h4>
                                    <p class="text-blue-600 text-[10px] font-black uppercase tracking-wider">Cardiac Surgery</p>
                                </div>
                            </div>
                            <p class="text-slate-500 italic leading-relaxed font-medium">"I owe my life to the cardiology team at Hope Haven. Their swift action and expertise during my emergency were nothing short of miraculous."</p>
                        </div>

                        <!-- Testimonial 3 (Hidden) -->
                        <div class="testimonial-item absolute inset-0 bg-white p-8 lg:p-10 rounded-[40px] shadow-xl border border-slate-100 opacity-0 translate-y-4 transition-all duration-700 pointer-events-none">
                            <div class="flex items-center space-x-4 mb-6">
                                <div class="w-12 h-12 rounded-full bg-emerald-100 flex items-center justify-center text-emerald-600 font-bold">AO</div>
                                <div>
                                    <h4 class="font-black text-slate-900">Amina Okon</h4>
                                    <p class="text-blue-600 text-[10px] font-black uppercase tracking-wider">Physiotherapy</p>
                                </div>
                            </div>
                            <p class="text-slate-500 italic leading-relaxed font-medium">"After my sports injury, the physiotherapy team here was instrumental in my recovery. Their personalized rehabilitation program was life-changing."</p>
                        </div>
                    </div>

                    <!-- Slider Controls -->
                    <div class="flex justify-center lg:justify-start gap-4 mt-8">
                        <button id="prev-test" class="w-10 h-10 rounded-full border border-blue-200 flex items-center justify-center text-blue-600 hover:bg-blue-600 hover:text-white transition-all">
                            <i data-lucide="chevron-left" class="w-5 h-5"></i>
                        </button>
                        <button id="next-test" class="w-10 h-10 rounded-full border border-blue-200 flex items-center justify-center text-blue-600 hover:bg-blue-600 hover:text-white transition-all">
                            <i data-lucide="chevron-right" class="w-5 h-5"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <script>
            document.addEventListener('DOMContentLoaded', () => {
                const items = document.querySelectorAll('.testimonial-item');
                let current = 0;

                function showTestimonial(index) {
                    items.forEach((item, i) => {
                        if(i === index) {
                            item.classList.remove('opacity-0', 'translate-y-4', 'pointer-events-none');
                            item.classList.add('opacity-100', 'translate-y-0');
                        } else {
                            item.classList.add('opacity-0', 'translate-y-4', 'pointer-events-none');
                            item.classList.remove('opacity-100', 'translate-y-0');
                        }
                    });
                }

                const nextBtn = document.getElementById('next-test');
                const prevBtn = document.getElementById('prev-test');

                if(nextBtn) {
                    nextBtn.addEventListener('click', () => {
                        current = (current + 1) % items.length;
                        showTestimonial(current);
                    });
                }

                if(prevBtn) {
                    prevBtn.addEventListener('click', () => {
                        current = (current - 1 + items.length) % items.length;
                        showTestimonial(current);
                    });
                }

                // Auto cycle every 8 seconds
                setInterval(() => {
                    current = (current + 1) % items.length;
                    showTestimonial(current);
                }, 8000);
            });
        </script>
    </section>

<?php include 'includes/footer.php'; ?>
