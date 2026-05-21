<?php include 'includes/header.php'; ?>

    <!-- Page Header -->
    <style>
        @keyframes ken-burns-zoom {
            0% { transform: scale(1); opacity: 0; }
            5% { opacity: 1; }
            20% { transform: scale(1.1); opacity: 1; }
            25% { transform: scale(1.15); opacity: 0; }
            100% { opacity: 0; }
        }
        .header-slider-container {
            position: absolute;
            inset: 0;
            z-index: 0;
            overflow: hidden;
            background: #0f172a;
        }
        .header-slide {
            position: absolute;
            inset: 0;
            background-size: cover;
            background-position: center;
            opacity: 0;
            animation: ken-burns-zoom 25s linear infinite;
        }
        .slide-1 { background-image: url('assets/external/d1.png'); animation-delay: 0s; }
        .slide-2 { background-image: url('assets/external/d2.png'); animation-delay: 5s; }
        .slide-3 { background-image: url('assets/external/d3.png'); animation-delay: 10s; }
        .slide-4 { background-image: url('assets/external/d4.png'); animation-delay: 15s; }
        .slide-5 { background-image: url('assets/external/d5.png'); animation-delay: 20s; }
    </style>

    <section class="relative py-24 bg-slate-900 overflow-hidden">
        <!-- Background Slider -->
        <div class="header-slider-container">
            <div class="header-slide slide-1"></div>
            <div class="header-slide slide-2"></div>
            <div class="header-slide slide-3"></div>
            <div class="header-slide slide-4"></div>
            <div class="header-slide slide-5"></div>
            <div class="absolute inset-0 bg-slate-900/40"></div>
            <div class="absolute inset-0 bg-gradient-to-t from-slate-900/80 via-transparent to-transparent"></div>
        </div>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10 text-center">
            <h1 class="text-4xl lg:text-6xl font-black text-white mb-6" data-aos="fade-down">Why Choose Us</h1>
            <p class="text-xl text-blue-100 font-medium max-w-2xl mx-auto" data-aos="fade-up" data-aos-delay="100">Our mission is to provide world-class healthcare with human compassion and clinical excellence.</p>
        </div>
    </section>

    <!-- Our Story -->
    <section class="py-24 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid lg:grid-cols-2 gap-20 items-center">
                <div data-aos="fade-right">
                    <span class="text-sm font-bold text-blue-600 uppercase tracking-widest mb-4 block">Our Heritage</span>
                    <h2 class="text-4xl font-black text-slate-900 mb-8">We are here with you<span class="text-blue-600"> Live the Life...</span></h2>
                    <p class="text-lg text-slate-600 mb-8 leading-relaxed font-medium">
                        For over two decades, our facility has been a pillar of health in Nigeria. Transitioning from CMC to Hope Haven Hospital, we have expanded our technology and expertise to meet the demands of modern medicine.
                    </p>
                    <div class="space-y-6">
                        <div class="flex items-center space-x-4">
                            <div class="w-12 h-12 bg-blue-50 rounded-2xl flex items-center justify-center text-blue-600 font-bold italic text-xl">M</div>
                            <div>
                                <h4 class="font-extrabold text-slate-900 text-lg">Our Mission</h4>
                                <p class="text-slate-500 font-medium">Pioneering pleasantly ideal patient care in every clinical touchpoint.</p>
                            </div>
                        </div>
                        <div class="flex items-center space-x-4">
                            <div class="w-12 h-12 bg-indigo-50 rounded-2xl flex items-center justify-center text-indigo-600 font-bold italic text-xl">V</div>
                            <div>
                                <h4 class="font-extrabold text-slate-900 text-lg">Our Vision</h4>
                                <p class="text-slate-500 font-medium">To be the most trusted healthcare partner in the region through innovation.</p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="relative" data-aos="fade-left">
                    <img src="assets/external/doc.png" class="rounded-[48px] shadow-2xl border border-slate-50 relative z-10" alt="Medical Team">
                    <div class="absolute -bottom-10 -right-10 w-64 h-64 bg-blue-600/5 rounded-full blur-3xl -z-0"></div>
                </div>
            </div>
        </div>
    </section>

    <!-- OUR TEAM & SERVICES SHUFFLE SECTION -->
    <section class="py-24 bg-white overflow-hidden">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16" data-aos="fade-up">
                <span class="text-sm font-bold text-blue-600 uppercase tracking-widest mb-4 block">The Hope Haven Standard</span>
                <h2 class="text-4xl lg:text-5xl font-black text-slate-900 mb-6">Human Care Meets <span class="text-blue-600">Clinical Precision</span></h2>
                <p class="text-lg text-slate-500 max-w-3xl mx-auto">Experience our dedicated team and world-class environments through our interactive clinical decks.</p>
            </div>

            <div class="grid lg:grid-cols-2 gap-12 items-center">
                <!-- Left Deck: Human Touch (Real Staff) -->
                <div class="relative h-[480px] flex flex-col items-center justify-center bg-slate-50/50 rounded-[48px] border border-slate-100 p-8" data-aos="fade-right">
                    <h4 class="text-xl font-black text-slate-900 mb-8 flex items-center">
                        <span class="w-8 h-8 bg-blue-600 text-white rounded-full flex items-center justify-center text-xs mr-3">01</span>
                        The Human Touch
                    </h4>
                    <div id="deck-human" class="relative w-full max-w-[320px] h-[400px]">
                        <?php 
                        // Use the new staff photos from pixnow folder
                        $local_staff_files = glob('pixnow/*.{jpeg,jpg,png,JPEG,JPG,PNG}', GLOB_BRACE);
                        
                        $staff_deck = [];
                        if($local_staff_files) {
                            foreach($local_staff_files as $f) {
                                $staff_deck[] = BASE_URL . '/' . str_replace('\\', '/', $f);
                            }
                        } else {
                            // Fallback if pixnow is empty
                            $staff_deck = [
                                'https://images.unsplash.com/photo-1519494026892-80bbd2d6fd0d?q=80&w=800&auto=format&fit=crop',
                                'https://images.unsplash.com/photo-1516549655169-df83a0774514?q=80&w=800&auto=format&fit=crop'
                            ];
                        }
                        shuffle($staff_deck);
                        
                        foreach(array_reverse($staff_deck) as $i => $url): 
                            $rotation = rand(-4, 4);
                            $offset = $i * 0.5;
                        ?>
                        <div class="stack-card-human absolute inset-0 bg-white p-3 rounded-[32px] shadow-2xl transition-all duration-700 cursor-pointer overflow-hidden border border-slate-100" 
                             style="z-index: <?php echo $i; ?>; transform: rotate(<?php echo $rotation; ?>deg) translate(<?php echo $offset; ?>px, <?php echo $offset; ?>px);">
                            <img src="<?php echo $url; ?>" class="w-full h-full object-cover object-top rounded-[24px] clinical-img" alt="Staff/Environment">
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <p class="mt-8 text-sm font-bold text-slate-400 uppercase tracking-widest">Click to Shuffle</p>
                </div>

                <!-- Right Deck: Clinical Precision (Wards/Labs) -->
                <div class="relative h-[480px] flex flex-col items-center justify-center bg-slate-50/50 rounded-[48px] border border-slate-100 p-8" data-aos="fade-left">
                    <h4 class="text-xl font-black text-slate-900 mb-8 flex items-center">
                        <span class="w-8 h-8 bg-blue-600 text-white rounded-full flex items-center justify-center text-xs mr-3">02</span>
                        Clinical Precision
                    </h4>
                    <div id="deck-clinical" class="relative w-full max-w-[320px] h-[320px]">
                        <?php 
                        $local_facility_files = glob('pix_converted/IMG_82*.jpg');
                        $facility_stock = [
                            'https://images.unsplash.com/photo-1579154236528-a3587b7a976a?q=80&w=800&auto=format&fit=crop',
                            'https://images.unsplash.com/photo-1587854692152-cbe660dbbb88?q=80&w=800&auto=format&fit=crop',
                            'https://images.unsplash.com/photo-1551076805-e1869033e561?q=80&w=800&auto=format&fit=crop'
                        ];
                        
                        $facility_deck = [];
                        if($local_facility_files) {
                            foreach(array_slice($local_facility_files, 0, 10) as $f) {
                                $facility_deck[] = BASE_URL . '/' . str_replace('\\', '/', $f);
                            }
                        }
                        $facility_deck = array_merge($facility_deck, $facility_stock);
                        shuffle($facility_deck);
                        
                        foreach(array_reverse($facility_deck) as $i => $url): 
                            $rotation = rand(-4, 4);
                            $offset = $i * 0.5;
                        ?>
                        <div class="stack-card-clinical absolute inset-0 bg-white p-3 rounded-[32px] shadow-2xl transition-all duration-700 cursor-pointer overflow-hidden border border-slate-100" 
                             style="z-index: <?php echo $i; ?>; transform: rotate(<?php echo $rotation; ?>deg) translate(<?php echo $offset; ?>px, <?php echo $offset; ?>px);">
                            <img src="<?php echo $url; ?>" class="w-full h-full object-cover rounded-[24px] clinical-img" alt="Facility/Equipment">
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <p class="mt-8 text-sm font-bold text-slate-400 uppercase tracking-widest">Click to Shuffle</p>
                </div>
            </div>
        </div>
    </section>

    <style>
        /* Majestic Glide Animation (Slow & Visible) */
        .stack-card-human, .stack-card-clinical { 
            transition: transform 2.5s cubic-bezier(0.45, 0, 0.55, 1), opacity 2.0s ease-in !important; 
        }
        
        /* Define the exit states (Where the cards glide to) */
        .shuffled-card-left { 
            transform: translate(-250%, -10%) rotate(-45deg) scale(1) !important; 
            opacity: 0 !important; 
            z-index: 1000 !important;
            pointer-events: none; 
        }
        .shuffled-card-right { 
            transform: translate(250%, -10%) rotate(45deg) scale(1) !important; 
            opacity: 0 !important; 
            z-index: 1000 !important;
            pointer-events: none; 
        }

        .clinical-img { filter: brightness(1.1) contrast(1.05); }
    </style>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const humanShuffle = initDeck('deck-human', 'stack-card-human', 'shuffled-card-left');
            const clinicalShuffle = initDeck('deck-clinical', 'stack-card-clinical', 'shuffled-card-right');
            let activeDeck = 0;
            const shufflers = [humanShuffle, clinicalShuffle];
            setInterval(() => {
                if (shufflers[activeDeck]) shufflers[activeDeck]();
                activeDeck = (activeDeck + 1) % shufflers.length;
            }, 4500);
        });

        function initDeck(containerId, cardClass, shuffleClass) {
            const container = document.getElementById(containerId);
            if(!container) return null;
            const cards = container.querySelectorAll('.' + cardClass);
            let current = cards.length - 1;
            const shuffleAction = () => {
                if (current < 0) {
                    cards.forEach((c, i) => {
                        c.classList.remove(shuffleClass);
                        c.style.transform = `rotate(${Math.random()*6-3}deg) translate(${i*0.5}px, ${i*0.5}px)`;
                    });
                    current = cards.length - 1;
                    return;
                }
                cards[current].classList.add(shuffleClass);
                current--;
            };
            container.addEventListener('click', shuffleAction);
            return shuffleAction;
        }
    </script>

    <!-- Core Values -->
    <section class="py-24 bg-slate-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-20" data-aos="fade-up">
                <h2 class="text-4xl font-black text-slate-900">Our Core Values</h2>
            </div>
            <div class="grid md:grid-cols-3 gap-8">
                <div class="p-10 bg-white rounded-[32px] shadow-sm hover:shadow-xl transition-all border border-slate-100 group" data-aos="fade-up" data-aos-delay="100">
                    <div class="w-14 h-14 bg-blue-50 text-blue-600 rounded-2xl flex items-center justify-center mb-6 group-hover:bg-blue-600 group-hover:text-white transition-all">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>
                    </div>
                    <h3 class="text-xl font-extrabold text-slate-900 mb-4">Patient Integrity</h3>
                    <p class="text-slate-500 font-medium leading-relaxed">We treat every patient with the highest level of respect, transparency, and ethical clinical standards.</p>
                </div>
                <div class="p-10 bg-white rounded-[32px] shadow-sm hover:shadow-xl transition-all border border-slate-100 group" data-aos="fade-up" data-aos-delay="200">
                    <div class="w-14 h-14 bg-indigo-50 text-indigo-600 rounded-2xl flex items-center justify-center mb-6 group-hover:bg-indigo-600 group-hover:text-white transition-all">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                    </div>
                    <h3 class="text-xl font-extrabold text-slate-900 mb-4">Innovation</h3>
                    <p class="text-slate-500 font-medium leading-relaxed">Constant investment in modern medical equipment and digital health solutions to improve outcomes.</p>
                </div>
                <div class="p-10 bg-white rounded-[32px] shadow-sm hover:shadow-xl transition-all border border-slate-100 group" data-aos="fade-up" data-aos-delay="300">
                    <div class="w-14 h-14 bg-emerald-50 text-emerald-600 rounded-2xl flex items-center justify-center mb-6 group-hover:bg-emerald-600 group-hover:text-white transition-all">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.828 14.828a4 4 0 01-5.656 0L4 10.172a4 4 0 015.656-5.656L14.828 9.172a4 4 0 010 5.656z"></path></svg>
                    </div>
                    <h3 class="text-xl font-extrabold text-slate-900 mb-4">Excellence</h3>
                    <p class="text-slate-500 font-medium leading-relaxed">We don't just treat; we care. Our excellence is measured by the smiles and recovery of our patients.</p>
                </div>
            </div>
        </div>
    </section>

<?php include 'includes/footer.php'; ?>
