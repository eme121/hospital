<?php include 'includes/header.php'; ?>

    <!-- Page Header -->
    <style>
        @keyframes ken-burns-slide {
            0% { transform: scale(1) translateX(0); opacity: 0; }
            10% { opacity: 0.5; }
            90% { opacity: 0.5; }
            100% { transform: scale(1.1) translateX(-2%); opacity: 0; }
        }
        .header-slider-container {
            position: absolute;
            inset: 0;
            z-index: 0;
            overflow: hidden;
        }
        .header-slide {
            position: absolute;
            inset: 0;
            background-size: cover;
            background-position: center;
            opacity: 0;
            animation: ken-burns-slide 18s linear infinite;
        }
        .slide-1 { background-image: url('wheel/20260411_081906.jpg'); animation-delay: 0s; }
        .slide-2 { background-image: url('wheel/IMG_8012.jpg'); animation-delay: 6s; }
        .slide-3 { background-image: url('wheel/20260411_084903.jpg'); animation-delay: 12s; }
    </style>

    <section class="relative py-32 lg:py-48 bg-slate-900 overflow-hidden">
        <!-- Background Slider -->
        <div class="header-slider-container">
            <div class="header-slide slide-1"></div>
            <div class="header-slide slide-2"></div>
            <div class="header-slide slide-3"></div>
            <!-- Lighter, more transparent overlay for better clarity -->
            <div class="absolute inset-0 bg-slate-900/40"></div>
            <div class="absolute inset-0 bg-gradient-to-t from-slate-900/80 via-transparent to-transparent"></div>
        </div>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10 text-center">
            <div class="inline-flex items-center px-4 py-2 bg-blue-500/20 text-blue-300 rounded-full mb-8 border border-blue-500/30" data-aos="fade-up">
                <span class="text-xs font-bold uppercase tracking-[0.2em]">Community Outreach</span>
            </div>
            <h1 class="text-5xl lg:text-7xl font-black text-white mb-6 leading-tight" data-aos="fade-down">
                Our <span class="text-blue-400">Impact</span> 2026
            </h1>
            <p class="text-xl text-blue-100 font-medium max-w-2xl mx-auto opacity-90" data-aos="fade-up" data-aos-delay="100">
                Witness the power of collective compassion and the tangible difference we're making in mobility and independence.
            </p>
        </div>
    </section>

    <!-- Main Content Section -->
    <section class="py-24 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid lg:grid-cols-2 gap-16 items-center">
                
                <!-- Video Display -->
                <div class="relative z-10 bg-slate-900 p-4 rounded-[48px] shadow-2xl border border-slate-800 overflow-hidden" data-aos="fade-right">
                    <div class="relative rounded-[36px] overflow-hidden aspect-video bg-black">
                        <video controls class="w-full h-full object-cover shadow-2xl">
                            <source src="<?php echo BASE_URL; ?>/videos/wheelchairscreenin2026.mp4" type="video/mp4">
                            Your browser does not support the video tag.
                        </video>
                    </div>
                </div>

                <!-- Content Area -->
                <div class="space-y-8" data-aos="fade-left">
                    <div class="inline-flex items-center px-4 py-2 bg-blue-50 text-blue-600 rounded-2xl">
                        <span class="text-xs font-bold uppercase tracking-widest">Community Outreach 2026</span>
                    </div>
                    <h2 class="text-4xl lg:text-5xl font-black text-slate-900 leading-tight">Wheelchair Distribution: <span class="text-blue-600">Mobility & Hope</span></h2>
                    
                    <p class="text-lg text-slate-600 leading-relaxed font-medium">
                        At Hope Haven Hospital, our mission extends beyond the walls of our clinical facilities. We believe that healthcare is a fundamental right, and independence is a cornerstone of dignity.
                    </p>

                    <div class="bg-slate-50 p-8 rounded-[32px] border border-slate-100 space-y-4">
                        <h4 class="text-xl font-bold text-slate-900">Event Highlights</h4>
                        <ul class="space-y-3">
                            <li class="flex items-center text-slate-600 font-medium">
                                <svg class="w-5 h-5 text-emerald-500 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                Distributed 100+ high-quality wheelchairs.
                            </li>
                            <li class="flex items-center text-slate-600 font-medium">
                                <svg class="w-5 h-5 text-emerald-500 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                Reached 5 underserved local communities.
                            </li>
                            <li class="flex items-center text-slate-600 font-medium">
                                <svg class="w-5 h-5 text-emerald-500 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                Provided free medical screenings on-site.
                            </li>
                        </ul>
                    </div>

                    <p class="text-slate-600 leading-relaxed font-medium">
                        This outreach program was designed to identify and support individuals with mobility challenges who lacked access to assistive devices. Through this initiative, we have not only provided mobility but also unlocked new opportunities for education, employment, and social integration.
                    </p>

                    <div class="pt-6">
                        <a href="contact.php" class="inline-flex items-center justify-center px-10 py-5 bg-blue-600 text-white rounded-2xl font-extrabold btn-premium text-lg shadow-xl shadow-blue-200">
                            Partner With Us
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Wheelchair Outreach Gallery Section (Enhanced Immersive Design) -->
    <section class="py-16 bg-slate-900 overflow-hidden relative">
        <div class="absolute top-0 left-0 w-full h-full bg-[radial-gradient(circle_at_50%_50%,#1e293b_0%,#0f172a_100%)] opacity-50"></div>
        
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10 mb-12 text-center">
            <div data-aos="fade-up">
                <span class="text-[10px] font-black text-blue-500 uppercase tracking-[0.4em] mb-4 block">Our Impact in Motion</span>
                <h2 class="text-3xl lg:text-4xl font-black text-white leading-tight">Moments of <span class="text-blue-500">Pure Joy</span></h2>
                <div class="w-16 h-1 bg-blue-600 mx-auto mt-4 rounded-full"></div>
            </div>
        </div>

        <!-- Marquee Row 1: Moving Left -->
        <div class="relative flex overflow-hidden mb-4">
            <div class="flex space-x-4 animate-marquee-left whitespace-nowrap will-change-transform">
                <!-- Unique Images from wheel folder -->
                <?php 
                $row1_images = [
                    ['src' => 'wheel/IMG_7967.jpg', 'tag' => 'Outreach', 'title' => 'Mobility Restored'],
                    ['src' => 'wheel/IMG_7973.jpg', 'tag' => 'Support', 'title' => 'Community Care'],
                    ['src' => 'wheel/IMG_7983.jpg', 'tag' => 'Hope', 'title' => 'New Beginnings'],
                    ['src' => 'wheel/IMG_7989.jpg', 'tag' => 'Dignity', 'title' => 'Empowered Lives'],
                    ['src' => 'wheel/IMG_7994.jpg', 'tag' => 'Mission', 'title' => 'Global Impact'],
                    ['src' => 'wheel/IMG_8002.jpg', 'tag' => 'Joy', 'title' => 'Pure Happiness']
                ];
                foreach(array_merge($row1_images, $row1_images) as $img): ?>
                <div class="w-[240px] h-[180px] rounded-[20px] overflow-hidden border-2 border-white/5 shadow-2xl relative group shrink-0">
                    <img src="<?php echo $img['src']; ?>" loading="lazy" decoding="async" class="w-full h-full object-cover transition-transform duration-1000 group-hover:scale-110" alt="<?php echo $img['title']; ?>" style="backface-visibility: hidden;">
                    <div class="absolute inset-0 bg-gradient-to-t from-slate-900/90 via-transparent to-transparent p-4 flex flex-col justify-end opacity-0 group-hover:opacity-100 transition-all duration-500 translate-y-4 group-hover:translate-y-0">
                        <span class="text-blue-400 font-black text-[8px] uppercase tracking-widest mb-1"><?php echo $img['tag']; ?></span>
                        <h4 class="text-white text-sm font-black"><?php echo $img['title']; ?></h4>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Marquee Row 2: Moving Right -->
        <div class="relative flex overflow-hidden">
            <div class="flex space-x-4 animate-marquee-right whitespace-nowrap will-change-transform">
                <!-- Different Unique Images from wheel folder -->
                <?php 
                $row2_images = [
                    ['src' => 'wheel/IMG_8012.jpg', 'tag' => 'Compassion', 'title' => 'Dedicated Care'],
                    ['src' => 'wheel/IMG_8015.jpg', 'tag' => 'Outreach', 'title' => 'Touching Lives'],
                    ['src' => 'wheel/IMG_8019.jpg', 'tag' => 'Impact', 'title' => 'Visionary Help'],
                    ['src' => 'wheel/IMG_8034.jpg', 'tag' => 'Future', 'title' => 'Sustainable Aid'],
                    ['src' => 'wheel/20260411_081906.jpg', 'tag' => 'Team', 'title' => 'Collective Effort'],
                    ['src' => 'wheel/20260411_084903.jpg', 'tag' => 'Service', 'title' => 'Heart for All']
                ];
                foreach(array_merge($row2_images, $row2_images) as $img): ?>
                <div class="w-[240px] h-[180px] rounded-[20px] overflow-hidden border-2 border-white/5 shadow-2xl relative group shrink-0">
                    <img src="<?php echo $img['src']; ?>" loading="lazy" decoding="async" class="w-full h-full object-cover transition-transform duration-1000 group-hover:scale-110" alt="<?php echo $img['title']; ?>" style="backface-visibility: hidden;">
                    <div class="absolute inset-0 bg-gradient-to-t from-blue-900/90 via-transparent to-transparent p-4 flex flex-col justify-end opacity-0 group-hover:opacity-100 transition-all duration-500 translate-y-4 group-hover:translate-y-0">
                        <span class="text-blue-400 font-black text-[8px] uppercase tracking-widest mb-1"><?php echo $img['tag']; ?></span>
                        <h4 class="text-white text-sm font-black"><?php echo $img['title']; ?></h4>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <style>
            @keyframes marquee-left {
                0% { transform: translateX(0); }
                100% { transform: translateX(calc(-240px * 6 - 1rem * 6)); }
            }
            @keyframes marquee-right {
                0% { transform: translateX(calc(-240px * 6 - 1rem * 6)); }
                100% { transform: translateX(0); }
            }
            .animate-marquee-left {
                animation: marquee-left 50s linear infinite;
            }
            .animate-marquee-right {
                animation: marquee-right 50s linear infinite;
            }
            .animate-marquee-left:hover, .animate-marquee-right:hover {
                animation-play-state: paused;
            }
            .will-change-transform {
                will-change: transform;
                backface-visibility: hidden;
            }
        </style>
    </section>

    <!-- Wheelchair Outreach Gallery Section (Enhanced Immersive Design) -->
    <section class="py-16 bg-slate-900 overflow-hidden relative">
        ... (rest of existing section) ...
    </section>

    <!-- UNIQUE SHUFFLED GALLERY SECTION -->
    <style>
        .shuffle-container {
            perspective: 1200px;
        }
        .card-shuffle {
            transition: all 0.6s cubic-bezier(0.34, 1.56, 0.64, 1);
            cursor: pointer;
            position: relative;
            z-index: 10;
        }
        .card-shuffle:hover {
            transform: rotate(0deg) translateY(-25px) scale(1.08) !important;
            z-index: 50;
            box-shadow: 0 40px 80px -15px rgba(0, 0, 0, 0.2);
        }
        .card-rotate-left { transform: rotate(-4deg); }
        .card-rotate-right { transform: rotate(3deg); }
        .card-rotate-slight { transform: rotate(-1.5deg); }
        .card-rotate-deep { transform: rotate(5deg); }
        
        @media (max-width: 768px) {
            .card-shuffle { transform: rotate(0deg) !important; margin-bottom: 1.5rem; }
        }

        /* Entrance Animation */
        .reveal-card {
            opacity: 0;
            transform: translateY(50px) scale(0.9);
        }
        .reveal-card.active {
            opacity: 1;
            transform: translateY(0) scale(1);
            transition: all 0.8s cubic-bezier(0.22, 1, 0.36, 1);
        }
    </style>

    <section class="py-24 bg-slate-50 overflow-hidden">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-20" data-aos="fade-up">
                <span class="text-sm font-bold text-blue-600 uppercase tracking-widest mb-4 block">Moment of Joy</span>
                <h2 class="text-4xl lg:text-5xl font-black text-slate-900 mb-6">Capturing <span class="text-blue-600">Hope in Action</span></h2>
                <p class="text-lg text-slate-500 max-w-2xl mx-auto">Every wheelchair represents a life transformed, a family supported, and a community strengthened.</p>
            </div>

            <div class="shuffle-container grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-y-16 gap-x-12 px-4">
                
                <!-- Card 1 -->
                <div class="card-shuffle card-rotate-left bg-white p-4 rounded-[32px] shadow-xl reveal-card" style="transition-delay: 0.1s;">
                    <div class="relative overflow-hidden rounded-[24px] aspect-[4/5]">
                        <img src="<?php echo BASE_URL; ?>/wheel/IMG_7967.jpg" alt="Wheelchair Distribution" decoding="async" class="w-full h-full object-cover">
                        <div class="absolute inset-0 bg-gradient-to-t from-slate-900/60 via-transparent to-transparent flex items-end p-6">
                            <span class="text-white font-bold text-lg">Restoring Mobility</span>
                        </div>
                    </div>
                </div>

                <!-- Card 2 (Focal Point - Slightly Larger) -->
                <div class="card-shuffle card-rotate-right lg:scale-110 bg-white p-4 rounded-[32px] shadow-2xl z-20 reveal-card" style="transition-delay: 0.2s;">
                    <div class="relative overflow-hidden rounded-[24px] aspect-[4/5]">
                        <img src="<?php echo BASE_URL; ?>/wheel/IMG_8002.jpg" alt="A smile of hope" decoding="async" class="w-full h-full object-cover">
                        <div class="absolute inset-0 bg-gradient-to-t from-blue-900/60 via-transparent to-transparent flex items-end p-6">
                            <div class="text-white">
                                <p class="text-blue-200 text-xs font-bold uppercase tracking-widest mb-1">Impact 2026</p>
                                <h4 class="text-xl font-black">Changing Lives</h4>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Card 3 -->
                <div class="card-shuffle card-rotate-slight bg-white p-4 rounded-[32px] shadow-xl reveal-card" style="transition-delay: 0.3s;">
                    <div class="relative overflow-hidden rounded-[24px] aspect-[4/5]">
                        <img src="<?php echo BASE_URL; ?>/wheel/IMG_7973.jpg" alt="Community Outreach" decoding="async" class="w-full h-full object-cover">
                        <div class="absolute inset-0 bg-gradient-to-t from-slate-900/60 via-transparent to-transparent flex items-end p-6">
                            <span class="text-white font-bold text-lg">Hope in Action</span>
                        </div>
                    </div>
                </div>

                <!-- Card 4 -->
                <div class="card-shuffle card-rotate-deep bg-white p-4 rounded-[32px] shadow-xl reveal-card" style="transition-delay: 0.4s;">
                    <div class="relative overflow-hidden rounded-[24px] aspect-[4/5]">
                        <img src="<?php echo BASE_URL; ?>/wheel/IMG_8015.jpg" alt="Distribution Event" decoding="async" class="w-full h-full object-cover">
                        <div class="absolute inset-0 bg-gradient-to-t from-slate-900/60 via-transparent to-transparent flex items-end p-6">
                            <span class="text-white font-bold text-lg">Compassion First</span>
                        </div>
                    </div>
                </div>

                <!-- Card 5 -->
                <div class="card-shuffle card-rotate-left bg-white p-4 rounded-[32px] shadow-xl reveal-card" style="transition-delay: 0.5s;">
                    <div class="relative overflow-hidden rounded-[24px] aspect-[4/5]">
                        <img src="<?php echo BASE_URL; ?>/wheel/IMG_8034.jpg" alt="New Beginnings" decoding="async" class="w-full h-full object-cover">
                        <div class="absolute inset-0 bg-gradient-to-t from-slate-900/60 via-transparent to-transparent flex items-end p-6">
                            <span class="text-white font-bold text-lg">New Beginnings</span>
                        </div>
                    </div>
                </div>

                <!-- Card 6 -->
                <div class="card-shuffle card-rotate-right bg-white p-4 rounded-[32px] shadow-xl reveal-card" style="transition-delay: 0.6s;">
                    <div class="relative overflow-hidden rounded-[24px] aspect-[4/5]">
                        <img src="<?php echo BASE_URL; ?>/wheel/20260411_081934.jpg" alt="Dedicated Care" decoding="async" class="w-full h-full object-cover">
                        <div class="absolute inset-0 bg-gradient-to-t from-slate-900/60 via-transparent to-transparent flex items-end p-6">
                            <span class="text-white font-bold text-lg">Unwavering Support</span>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </section>

    <!-- Mission Alignment Section -->
    <section class="py-24 bg-white">
        <div class="max-w-4xl mx-auto px-4 text-center">
            <h3 class="text-3xl font-black text-slate-900 mb-8">Pioneering Pleasantly Ideal Patient Care</h3>
            <p class="text-xl text-slate-500 leading-relaxed italic font-medium">
                "Our goal is to ensure that no one is left behind in our journey towards a healthier and more mobile society. This outreach is just the beginning of our 2026 commitment to community health."
            </p>
            <div class="mt-8">
                <p class="font-black text-slate-900 text-lg">— Medical Director, Hope Haven Hospital</p>
            </div>
        </div>
    </section>

    <script>
        // Simple Intersection Observer for the entrance animation
        const observerOptions = {
            threshold: 0.2
        };

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('active');
                }
            });
        }, observerOptions);

        document.querySelectorAll('.reveal-card').forEach(card => {
            observer.observe(card);
        });

        // Initialize AOS
        window.addEventListener('load', () => {
            if (typeof AOS !== 'undefined') {
                AOS.init({
                    duration: 1000,
                    once: true,
                    offset: 100
                });
            }
        });
    </script>

<?php include 'includes/footer.php'; ?>
