<?php 
include 'includes/header.php'; 

// Fetch Most Needed Data
$most_needed_category = 'Hospital Equipment'; // Default
$json_path = 'assets/data/most_needed.json';
if (file_exists($json_path)) {
    $data = json_decode(file_get_contents($json_path), true);
    if (isset($data['most_needed_category'])) {
        $most_needed_category = $data['most_needed_category'];
    }
}
?>


<!-- Paystack JS -->
<script src="https://js.paystack.co/v2/inline.js"></script>
<link rel="stylesheet" href="assets/css/donate.css">
<style>
    .amount-card.active, .category-card.active {
        border-color: #2563eb;
        background-color: #eff6ff;
        box-shadow: 0 4px 6px -1px rgba(37, 99, 235, 0.1), 0 2px 4px -1px rgba(37, 99, 235, 0.06);
    }
    .blob {
        position: absolute;
        width: 500px;
        height: 500px;
        background: linear-gradient(180deg, rgba(59, 130, 246, 0.15) 0%, rgba(37, 99, 235, 0.05) 100%);
        filter: blur(80px);
        border-radius: 50%;
        z-index: -1;
    }
    .category-card.active .check-indicator {
        opacity: 1 !important;
    }
</style>

<!-- Lucide Icons -->
<script src="https://unpkg.com/lucide@latest"></script>

<!-- Hero Section -->
<section class="relative pt-32 pb-20 lg:pt-48 lg:pb-32 hero-gradient overflow-hidden">
    <div class="blob top-[-10%] right-[-10%] animate-pulse"></div>
    <div class="blob bottom-[-10%] left-[-10%]"></div>
    
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center relative" data-aos="fade-up">
        <span class="inline-block px-4 py-1.5 mb-6 text-xs font-bold tracking-widest text-blue-600 uppercase bg-blue-50 rounded-full">Your Support Matters</span>
        <h1 class="text-5xl lg:text-7xl font-extrabold text-slate-900 leading-tight mb-8">
            Give Hope. <span class="text-blue-600">Save Lives.</span>
        </h1>
        <p class="text-lg lg:text-xl text-slate-600 mb-10 leading-relaxed max-w-2xl mx-auto font-medium">
            At Hope Haven Hospital, every contribution helps us provide world-class medical care to those who need it most. Together, we can build a healthier future.
        </p>
        <div class="flex flex-col sm:flex-row justify-center gap-4">
            <a href="#donate-form" class="bg-blue-600 text-white px-10 py-4 rounded-full text-lg font-bold btn-premium shadow-lg shadow-blue-200">Donate Now</a>
            <a href="#impact" class="bg-white text-slate-700 border border-slate-200 px-10 py-4 rounded-full text-lg font-bold hover:bg-slate-50 transition-all">See Your Impact</a>
        </div>
    </div>
</section>

<!-- Impact Stats Section -->
<section id="impact" class="py-24 bg-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8 text-center">
            <div class="p-8 rounded-3xl bg-blue-50/50 border border-blue-100" data-aos="fade-up" data-aos-delay="100">
                <div class="w-16 h-16 bg-blue-600 rounded-2xl flex items-center justify-center mx-auto mb-6 shadow-lg shadow-blue-200">
                    <i data-lucide="users" class="text-white w-8 h-8"></i>
                </div>
                <h3 class="text-4xl font-extrabold text-slate-900 mb-2 counter" data-target="15000">0</h3>
                <p class="text-slate-600 font-bold italic">Patients Treated Yearly</p>
            </div>
            <div class="p-8 rounded-3xl bg-emerald-50/50 border border-emerald-100" data-aos="fade-up" data-aos-delay="200">
                <div class="w-16 h-16 bg-emerald-500 rounded-2xl flex items-center justify-center mx-auto mb-6 shadow-lg shadow-emerald-200">
                    <i data-lucide="heart-pulse" class="text-white w-8 h-8"></i>
                </div>
                <h3 class="text-4xl font-extrabold text-slate-900 mb-2 counter" data-target="2500">0</h3>
                <p class="text-slate-600 font-bold italic">Emergency Surgeries</p>
            </div>
            <div class="p-8 rounded-3xl bg-amber-50/50 border border-amber-100" data-aos="fade-up" data-aos-delay="300">
                <div class="w-16 h-16 bg-amber-500 rounded-2xl flex items-center justify-center mx-auto mb-6 shadow-lg shadow-amber-200">
                    <i data-lucide="baby" class="text-white w-8 h-8"></i>
                </div>
                <h3 class="text-4xl font-extrabold text-slate-900 mb-2 counter" data-target="1200">0</h3>
                <p class="text-slate-600 font-bold italic">Newborns Delivered</p>
            </div>
        </div>
    </div>
</section>

<!-- Donation Section -->
<section id="donate-form" class="py-24 bg-slate-50 relative overflow-hidden">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 relative">
        <div class="bg-white rounded-[3rem] shadow-2xl overflow-hidden border border-slate-100">
            <div class="p-8 lg:p-12">
                <div class="text-center mb-12">
                    <h2 class="text-3xl font-black text-slate-900 mb-4 uppercase tracking-tight">Make a Donation</h2>
                    <p class="text-slate-500 font-medium">Choose a category and amount to help us continue our mission.</p>
                    
                    <!-- Frequency Toggle -->
                    <div class="flex justify-center mt-8">
                        <div class="bg-slate-100 p-1.5 rounded-2xl flex space-x-1">
                            <button id="btn-onetime" class="px-8 py-2.5 rounded-xl text-sm font-bold bg-white shadow-sm text-blue-600 transition-all">One-time</button>
                            <button id="btn-monthly" class="px-8 py-2.5 rounded-xl text-sm font-bold text-slate-500 hover:text-blue-600 transition-all">Monthly</button>
                        </div>
                    </div>
                </div>

                <form id="donation-form" action="process_donation.php" method="POST" class="space-y-12">
                    <input type="hidden" name="frequency" id="input-frequency" value="onetime">
                    <input type="hidden" name="donation_category" id="donation_category" value="Patient Care">
                    
                    <!-- Donation Categories -->
                    <div class="space-y-6">
                        <label class="block text-sm font-black text-slate-700 uppercase tracking-widest text-center lg:text-left">1. Select Donation Category</label>
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                            <!-- Category 1 -->
                            <div class="category-card cursor-pointer p-6 border-2 border-slate-100 rounded-[2rem] transition-all hover:border-blue-200 group relative overflow-hidden h-full" data-category="Hospital Consumables">
                                <div class="flex items-start space-x-4 relative z-10">
                                    <div class="w-12 h-12 bg-blue-50 text-blue-600 rounded-2xl flex items-center justify-center shrink-0 group-hover:bg-blue-600 group-hover:text-white transition-all duration-300">
                                        <i data-lucide="package" class="w-6 h-6"></i>
                                    </div>
                                    <div>
                                        <h4 class="font-black text-slate-900 group-hover:text-blue-600 transition-colors">Consumables</h4>
                                        <p class="text-[11px] text-slate-500 font-medium leading-relaxed mt-1">Surgical gloves, bandages, and essential medical supplies for daily operations.</p>
                                    </div>
                                </div>
                                <div class="absolute top-4 right-4 check-indicator opacity-0 transition-opacity">
                                    <div class="w-6 h-6 bg-blue-600 text-white rounded-full flex items-center justify-center">
                                        <i data-lucide="check" class="w-4 h-4"></i>
                                    </div>
                                </div>
                                <?php if ($most_needed_category === 'Hospital Consumables'): ?>
                                <span class="absolute top-0 right-0 bg-amber-500 text-white text-[8px] font-black px-3 py-1 rounded-bl-xl uppercase tracking-widest">Most Needed</span>
                                <?php endif; ?>
                            </div>

                            <!-- Category 2 -->
                            <div class="category-card cursor-pointer p-6 border-2 border-slate-100 rounded-[2rem] transition-all hover:border-blue-200 group relative overflow-hidden h-full" data-category="Hospital Equipment">
                                <div class="flex items-start space-x-4 relative z-10">
                                    <div class="w-12 h-12 bg-indigo-50 text-indigo-600 rounded-2xl flex items-center justify-center shrink-0 group-hover:bg-indigo-600 group-hover:text-white transition-all duration-300">
                                        <i data-lucide="stethoscope" class="w-6 h-6"></i>
                                    </div>
                                    <div>
                                        <h4 class="font-black text-slate-900 group-hover:text-indigo-600 transition-colors">Equipment</h4>
                                        <p class="text-[11px] text-slate-500 font-medium leading-relaxed mt-1">Modernizing our facility with advanced diagnostic tools and life-saving machinery.</p>
                                    </div>
                                </div>
                                <div class="absolute top-4 right-4 check-indicator opacity-0 transition-opacity">
                                    <div class="w-6 h-6 bg-indigo-600 text-white rounded-full flex items-center justify-center">
                                        <i data-lucide="check" class="w-4 h-4"></i>
                                    </div>
                                </div>
                                <?php if ($most_needed_category === 'Hospital Equipment'): ?>
                                <span class="absolute top-0 right-0 bg-amber-500 text-white text-[8px] font-black px-3 py-1 rounded-bl-xl uppercase tracking-widest">Most Needed</span>
                                <?php endif; ?>
                            </div>

                            <!-- Category 3 -->
                            <div class="category-card active cursor-pointer p-6 border-2 border-slate-100 rounded-[2rem] transition-all hover:border-blue-200 group relative overflow-hidden h-full" data-category="Patient Care">
                                <div class="flex items-start space-x-4 relative z-10">
                                    <div class="w-12 h-12 bg-emerald-50 text-emerald-600 rounded-2xl flex items-center justify-center shrink-0 group-hover:bg-emerald-600 group-hover:text-white transition-all duration-300">
                                        <i data-lucide="heart" class="w-6 h-6"></i>
                                    </div>
                                    <div>
                                        <h4 class="font-black text-slate-900 group-hover:text-emerald-600 transition-colors">Patient Care</h4>
                                        <p class="text-[11px] text-slate-500 font-medium leading-relaxed mt-1">Supporting vulnerable patients with treatment costs, medication, and recovery support.</p>
                                    </div>
                                </div>
                                <div class="absolute top-4 right-4 check-indicator opacity-0 transition-opacity">
                                    <div class="w-6 h-6 bg-emerald-600 text-white rounded-full flex items-center justify-center">
                                        <i data-lucide="check" class="w-4 h-4"></i>
                                    </div>
                                </div>
                            </div>

                            <!-- Category 4 -->
                            <div class="category-card cursor-pointer p-6 border-2 border-slate-100 rounded-[2rem] transition-all hover:border-blue-200 group relative overflow-hidden h-full" data-category="Emergency Support">
                                <div class="flex items-start space-x-4 relative z-10">
                                    <div class="w-12 h-12 bg-red-50 text-red-600 rounded-2xl flex items-center justify-center shrink-0 group-hover:bg-red-600 group-hover:text-white transition-all duration-300">
                                        <i data-lucide="ambulance" class="w-6 h-6"></i>
                                    </div>
                                    <div>
                                        <h4 class="font-black text-slate-900 group-hover:text-red-600 transition-colors">Emergency</h4>
                                        <p class="text-[11px] text-slate-500 font-medium leading-relaxed mt-1">Funding for rapid response teams, trauma care, and critical emergency services.</p>
                                    </div>
                                </div>
                                <div class="absolute top-4 right-4 check-indicator opacity-0 transition-opacity">
                                    <div class="w-6 h-6 bg-red-600 text-white rounded-full flex items-center justify-center">
                                        <i data-lucide="check" class="w-4 h-4"></i>
                                    </div>
                                </div>
                            </div>

                            <!-- Category 5 -->
                            <div class="category-card cursor-pointer p-6 border-2 border-slate-100 rounded-[2rem] transition-all hover:border-blue-200 group relative overflow-hidden h-full" data-category="Community Outreach">
                                <div class="flex items-start space-x-4 relative z-10">
                                    <div class="w-12 h-12 bg-amber-50 text-amber-600 rounded-2xl flex items-center justify-center shrink-0 group-hover:bg-amber-600 group-hover:text-white transition-all duration-300">
                                        <i data-lucide="users" class="w-6 h-6"></i>
                                    </div>
                                    <div>
                                        <h4 class="font-black text-slate-900 group-hover:text-amber-600 transition-colors">Outreach</h4>
                                        <p class="text-[11px] text-slate-500 font-medium leading-relaxed mt-1">Health awareness programs, free screenings, and medical aid in rural communities.</p>
                                    </div>
                                </div>
                                <div class="absolute top-4 right-4 check-indicator opacity-0 transition-opacity">
                                    <div class="w-6 h-6 bg-amber-600 text-white rounded-full flex items-center justify-center">
                                        <i data-lucide="check" class="w-4 h-4"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Amount Selection -->
                    <div class="space-y-6 pt-6 border-t border-slate-100">
                        <label class="block text-sm font-black text-slate-700 uppercase tracking-widest text-center lg:text-left">2. Choose Amount (₦)</label>
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                            <div class="amount-card active cursor-pointer p-6 border-2 border-slate-100 rounded-2xl text-center transition-all hover:border-blue-200" data-amount="5000">
                                <span class="block text-[10px] font-black text-slate-400 mb-1 uppercase tracking-widest">STABILIZE</span>
                                <span class="text-2xl font-black text-slate-900">₦5,000</span>
                            </div>
                            <div class="amount-card cursor-pointer p-6 border-2 border-slate-100 rounded-2xl text-center transition-all hover:border-blue-200" data-amount="10000">
                                <span class="block text-[10px] font-black text-slate-400 mb-1 uppercase tracking-widest">CARE</span>
                                <span class="text-2xl font-black text-slate-900">₦10,000</span>
                            </div>
                            <div class="amount-card cursor-pointer p-6 border-2 border-slate-100 rounded-2xl text-center transition-all hover:border-blue-200" data-amount="25000">
                                <span class="block text-[10px] font-black text-slate-400 mb-1 uppercase tracking-widest">HEAL</span>
                                <span class="text-2xl font-black text-slate-900">₦25,000</span>
                            </div>
                            <div id="custom-amount-trigger" class="amount-card cursor-pointer p-6 border-2 border-slate-100 rounded-2xl text-center transition-all hover:border-blue-200">
                                <span class="block text-[10px] font-black text-slate-400 mb-1 uppercase tracking-widest">CUSTOM</span>
                                <span class="text-2xl font-black text-slate-900">Other</span>
                            </div>
                        </div>
                    </div>

                    <div id="custom-amount-container" class="hidden">
                        <label class="block text-sm font-black text-slate-700 mb-2 uppercase tracking-widest">Custom Amount (₦)</label>
                        <input type="number" id="input-amount" name="amount" value="5000" class="w-full px-6 py-4 bg-slate-50 border-0 rounded-2xl focus:ring-2 focus:ring-blue-500 font-black text-lg" placeholder="Enter amount">
                    </div>

                    <!-- Personal Info -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8 pt-6 border-t border-slate-100">
                        <div>
                            <label class="block text-sm font-black text-slate-700 mb-2 uppercase tracking-widest">Full Name</label>
                            <input type="text" name="name" required class="w-full px-6 py-4 bg-slate-50 border-0 rounded-2xl focus:ring-2 focus:ring-blue-500 font-medium" placeholder="John Doe">
                        </div>
                        <div>
                            <label class="block text-sm font-black text-slate-700 mb-2 uppercase tracking-widest">Email Address</label>
                            <input type="email" name="email" required class="w-full px-6 py-4 bg-slate-50 border-0 rounded-2xl focus:ring-2 focus:ring-blue-500 font-medium" placeholder="john@example.com">
                        </div>
                    </div>

                    <!-- Payment Method -->
                    <div class="space-y-6 pt-6 border-t border-slate-100">
                        <label class="block text-sm font-black text-slate-700 uppercase tracking-widest text-center lg:text-left">3. Payment Method</label>
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                            <label class="flex items-center p-5 border-2 border-slate-100 rounded-2xl cursor-pointer hover:bg-slate-50 transition-all">
                                <input type="radio" name="payment_method" value="card" checked class="hidden peer">
                                <div class="w-full flex items-center justify-between peer-checked:text-blue-600">
                                    <div class="flex items-center">
                                        <i data-lucide="credit-card" class="w-5 h-5 mr-3"></i>
                                        <span class="font-black">Credit Card</span>
                                    </div>
                                    <div class="w-5 h-5 rounded-full border-2 border-slate-200 flex items-center justify-center peer-checked:border-blue-600 peer-checked:bg-blue-600">
                                        <div class="w-2 h-2 bg-white rounded-full"></div>
                                    </div>
                                </div>
                            </label>
                            <label class="flex items-center p-5 border-2 border-slate-100 rounded-2xl cursor-pointer hover:bg-slate-50 transition-all">
                                <input type="radio" name="payment_method" value="paypal" class="hidden peer">
                                <div class="w-full flex items-center justify-between peer-checked:text-blue-600">
                                    <div class="flex items-center">
                                        <i data-lucide="wallet" class="w-5 h-5 mr-3"></i>
                                        <span class="font-black">PayPal</span>
                                    </div>
                                    <div class="w-5 h-5 rounded-full border-2 border-slate-200 flex items-center justify-center">
                                        <div class="w-2 h-2 bg-white rounded-full opacity-0"></div>
                                    </div>
                                </div>
                            </label>
                            <label class="flex items-center p-5 border-2 border-slate-100 rounded-2xl cursor-pointer hover:bg-slate-50 transition-all">
                                <input type="radio" name="payment_method" value="transfer" class="hidden peer">
                                <div class="w-full flex items-center justify-between peer-checked:text-blue-600">
                                    <div class="flex items-center">
                                        <i data-lucide="landmark" class="w-5 h-5 mr-3"></i>
                                        <span class="font-black">Bank Transfer</span>
                                    </div>
                                    <div class="w-5 h-5 rounded-full border-2 border-slate-200 flex items-center justify-center peer-checked:border-blue-600 peer-checked:bg-blue-600">
                                        <div class="w-2 h-2 bg-white rounded-full"></div>
                                    </div>
                                </div>
                            </label>
                        </div>

                        <!-- Card Details Info -->
                        <div id="card-details-section" class="mt-6 p-8 bg-blue-50/50 rounded-[2rem] border border-blue-100 space-y-4 text-center">
                            <i data-lucide="shield-check" class="w-12 h-12 text-blue-600 mx-auto mb-2"></i>
                            <p class="text-sm font-bold text-slate-700">Secure Payment via Paystack</p>
                            <p class="text-[10px] text-slate-500 uppercase tracking-widest font-medium">Cards • Mobile App • USSD • QR</p>
                        </div>

                        <!-- Direct Bank Transfer Details (Hidden by default) -->
                        <div id="bank-transfer-section" class="hidden mt-6 p-8 bg-slate-900 rounded-[2rem] text-white space-y-6">
                            <div class="flex justify-between items-center border-b border-white/10 pb-4">
                                <span class="text-xs font-black uppercase tracking-widest text-slate-400">Our Bank Accounts</span>
                                <i data-lucide="info" class="w-4 h-4 text-blue-400"></i>
                            </div>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div class="p-4 bg-white/5 rounded-2xl border border-white/10">
                                    <p class="text-[10px] font-black text-blue-400 uppercase mb-2">Naira Account (NGN)</p>
                                    <p class="text-lg font-black tracking-tight">0123456789</p>
                                    <p class="text-xs font-bold text-slate-300">Hope Haven Hospital</p>
                                    <p class="text-[10px] text-slate-500 uppercase mt-1">First Bank PLC</p>
                                </div>
                                <div class="p-4 bg-white/5 rounded-2xl border border-white/10">
                                    <p class="text-[10px] font-black text-emerald-400 uppercase mb-2">Dollar Account (USD)</p>
                                    <p class="text-lg font-black tracking-tight">9876543210</p>
                                    <p class="text-xs font-bold text-slate-300">Hope Haven Hospital</p>
                                    <p class="text-[10px] text-slate-500 uppercase mt-1">Zenith Bank PLC</p>
                                </div>
                            </div>

                            <div class="pt-4">
                                <label class="block text-[10px] font-black text-slate-400 mb-2 uppercase tracking-widest">Transaction Reference / Remark</label>
                                <input type="text" name="transfer_ref" id="transfer_ref" class="w-full px-6 py-4 bg-white/10 border border-white/10 rounded-2xl focus:ring-2 focus:ring-blue-500 text-white font-medium" placeholder="Enter your transfer remark or ID">
                                <p class="text-[9px] text-slate-500 mt-2 italic">* Our team will manually verify this transfer within 24 hours.</p>
                            </div>
                        </div>
                    </div>

                    <button type="submit" class="w-full bg-blue-600 text-white py-6 rounded-2xl text-xl font-black btn-premium shadow-xl shadow-blue-100 mt-4 uppercase tracking-widest">
                        Complete Donation
                    </button>

                    <p class="text-center text-[10px] font-bold text-slate-400 px-8 uppercase tracking-widest">
                        Your transaction is secure and encrypted. Thank you for your support.
                    </p>
                </form>
            </div>
        </div>
    </div>
</section>

<!-- Success Modal -->
<div id="thank-you-modal" class="fixed inset-0 z-[100] hidden flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-md">
    <div class="bg-white rounded-[2.5rem] max-w-lg w-full p-10 text-center shadow-2xl scale-95 opacity-0 transition-all duration-300 transform" id="modal-content">
        <div class="w-24 h-24 bg-emerald-100 rounded-full flex items-center justify-center mx-auto mb-8">
            <i data-lucide="heart" class="text-emerald-500 w-12 h-12 fill-current"></i>
        </div>
        <h2 class="text-4xl font-black text-slate-900 mb-4 leading-tight uppercase">Thank You!</h2>
        <p class="text-slate-600 mb-10 text-lg leading-relaxed font-medium">
            Your generous contribution of <span id="display-amount" class="font-black text-blue-600">$0</span> has been received. Your support directly impacts the lives of our patients.
        </p>
        <button onclick="closeModal()" class="w-full bg-slate-900 text-white py-5 rounded-2xl text-xl font-black hover:bg-blue-600 transition-all uppercase tracking-widest">Back to Home</button>
    </div>
</div>

<script src="assets/js/donate.js"></script>
<script>
    // Initialize Lucide
    lucide.createIcons();

    // Counter Animation
    const counters = document.querySelectorAll('.counter');
    const speed = 200;

    const startCounters = () => {
        counters.forEach(counter => {
            const updateCount = () => {
                const target = +counter.getAttribute('data-target');
                const count = +counter.innerText.replace(/,/g, '');
                const inc = target / speed;

                if (count < target) {
                    counter.innerText = Math.ceil(count + inc).toLocaleString();
                    setTimeout(updateCount, 1);
                } else {
                    counter.innerText = target.toLocaleString();
                }
            };
            updateCount();
        });
    };

    // ScrollTrigger for counters
    ScrollTrigger.create({
        trigger: "#impact",
        start: "top 80%",
        onEnter: () => startCounters()
    });
</script>

<?php include 'includes/footer.php'; ?>
