<?php 
session_start();
if (isset($_SESSION['patient_id'])) {
    header('Location: telemedicine_dashboard_patient.php');
    exit;
}
include 'includes/header.php'; ?>

    <!-- Telemedicine Hero Section -->
    <section class="relative py-24 lg:py-40 bg-slate-900 overflow-hidden">
        <div class="absolute inset-0 opacity-40">
            <img src="assets/img/external/medical-tools.jpg" class="w-full h-full object-cover" alt="Telemedicine Header">
        </div>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
            <div class="max-w-3xl" data-aos="fade-right">
                <div class="inline-flex items-center px-4 py-2 mb-8 bg-blue-500/20 text-blue-300 rounded-2xl border border-blue-500/30">
                    <span class="text-xs font-bold uppercase tracking-widest">Medical Collaboration Platform</span>
                </div>
                <h1 class="text-5xl lg:text-7xl font-black text-white leading-tight mb-8">
                    Advanced <span class="text-blue-400">Telemedicine</span> for Specialists.
                </h1>
                <p class="text-xl text-blue-100/80 mb-10 font-medium leading-relaxed">
                    A secure, WhatsApp-style collaboration platform for doctors to discuss patient cases, share medical files, and provide multidisciplinary reviews in real-time.
                </p>
                <div class="flex flex-col sm:flex-row gap-5">
                    <a href="telemedicine_login.php" class="inline-flex items-center justify-center px-10 py-5 bg-blue-600 text-white rounded-2xl font-extrabold btn-premium text-lg shadow-xl shadow-blue-900/40">
                        Doctor Login
                    </a>
                    <a href="telemedicine_register.php" class="inline-flex items-center justify-center px-10 py-5 bg-white text-slate-900 rounded-2xl font-extrabold hover:bg-slate-50 transition-all text-lg border-2 border-transparent">
                        Register as Specialist
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="py-24 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid md:grid-cols-3 gap-12">
                <div class="p-10 rounded-[40px] bg-slate-50 border border-slate-100" data-aos="fade-up">
                    <div class="w-16 h-16 bg-blue-600 text-white rounded-2xl flex items-center justify-center mb-8">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"></path></svg>
                    </div>
                    <h3 class="text-2xl font-black text-slate-900 mb-4">Case Discussions</h3>
                    <p class="text-slate-500 leading-relaxed font-medium">Create detailed patient cases and invite specialists for real-time discussion and review.</p>
                </div>
                <div class="p-10 rounded-[40px] bg-slate-50 border border-slate-100" data-aos="fade-up" data-aos-delay="100">
                    <div class="w-16 h-16 bg-indigo-600 text-white rounded-2xl flex items-center justify-center mb-8">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path></svg>
                    </div>
                    <h3 class="text-2xl font-black text-slate-900 mb-4">File Sharing</h3>
                    <p class="text-slate-500 leading-relaxed font-medium">Upload and preview medical reports, X-rays, and PDFs directly within the discussion thread.</p>
                </div>
                <div class="p-10 rounded-[40px] bg-slate-50 border border-slate-100" data-aos="fade-up" data-aos-delay="200">
                    <div class="w-16 h-16 bg-emerald-600 text-white rounded-2xl flex items-center justify-center mb-8">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.040L3 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622l-1.382-.576z"></path></svg>
                    </div>
                    <h3 class="text-2xl font-black text-slate-900 mb-4">Secure & Private</h3>
                    <p class="text-slate-500 leading-relaxed font-medium">End-to-end encrypted medical communication ensuring HIPAA-compliant patient data handling.</p>
                </div>
            </div>
        </div>
    </section>

<?php include 'includes/footer.php'; ?>
