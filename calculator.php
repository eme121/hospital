<?php include 'includes/header.php'; ?>

    <!-- Page Header -->
    <section class="relative py-24 bg-slate-900 overflow-hidden">
        <div class="absolute inset-0 opacity-30">
            <img src="assets/img/external/medical-tools.jpg" class="w-full h-full object-cover" alt="Medical Tools">
        </div>
        <div class="absolute top-0 right-0 w-96 h-96 bg-blue-600/20 rounded-full -mr-48 -mt-48 blur-3xl"></div>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10 text-center">
            <h1 class="text-4xl lg:text-6xl font-black text-white mb-6" data-aos="fade-down">Health Calculators</h1>
            <p class="text-xl text-blue-100 font-medium max-w-2xl mx-auto" data-aos="fade-up" data-aos-delay="100">Professional clinical tools for quick and precise medical assessments.</p>
        </div>
    </section>

    <!-- Calculator Section -->
    <section class="py-24 bg-slate-50">
        <div class="max-w-5xl mx-auto px-4">
            <!-- Tab Switcher -->
            <div class="flex flex-wrap justify-center gap-3 mb-12" data-aos="fade-up">
                <button onclick="switchTab('bmi')" id="btn-bmi" class="tab-btn active-tab px-6 py-3 bg-white rounded-2xl font-black text-sm text-slate-600 hover:text-blue-600 transition-all shadow-sm border-2 border-transparent">General (BMI)</button>
                <button onclick="switchTab('dosage')" id="btn-dosage" class="tab-btn px-6 py-3 bg-white rounded-2xl font-black text-sm text-slate-600 hover:text-blue-600 transition-all shadow-sm border-2 border-transparent">Pediatric Dosage</button>
                <button onclick="switchTab('heart')" id="btn-heart" class="tab-btn px-6 py-3 bg-white rounded-2xl font-black text-sm text-slate-600 hover:text-blue-600 transition-all shadow-sm border-2 border-transparent">Heart Health</button>
                <button onclick="switchTab('liver')" id="btn-liver" class="tab-btn px-6 py-3 bg-white rounded-2xl font-black text-sm text-slate-600 hover:text-blue-600 transition-all shadow-sm border-2 border-transparent">Liver Function</button>
                <button onclick="switchTab('gfr')" id="btn-gfr" class="tab-btn px-6 py-3 bg-white rounded-2xl font-black text-sm text-slate-600 hover:text-blue-600 transition-all shadow-sm border-2 border-transparent">Kidney (GFR)</button>
                <button onclick="switchTab('glucose')" id="btn-glucose" class="tab-btn px-6 py-3 bg-white rounded-2xl font-black text-sm text-slate-600 hover:text-blue-600 transition-all shadow-sm border-2 border-transparent">Blood Sugar</button>
            </div>

            <!-- Calculator Containers -->
            <div class="bg-white rounded-[48px] p-10 lg:p-16 shadow-2xl shadow-slate-200 border border-white relative overflow-hidden" data-aos="zoom-in" data-aos-duration="800">
                
                <!-- BMI Calculator -->
                <div id="calc-bmi" class="calc-section">
                    <h2 class="text-3xl font-black text-slate-900 mb-8 flex items-center">
                        <span class="w-12 h-12 bg-blue-50 text-blue-600 rounded-2xl flex items-center justify-center mr-5 shadow-inner">
                            <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                        </span>
                        Body Mass Index (BMI)
                    </h2>
                    <div class="grid md:grid-cols-2 gap-12">
                        <div class="space-y-6">
                            <div>
                                <label class="block text-sm font-bold text-slate-500 uppercase tracking-widest mb-3 ml-1">Weight (kg)</label>
                                <input type="number" id="bmi-weight" placeholder="e.g. 70" class="w-full px-6 py-4 rounded-2xl bg-slate-50 border-2 border-transparent focus:bg-white focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 transition-all outline-none font-bold text-lg">
                            </div>
                            <div>
                                <label class="block text-sm font-bold text-slate-500 uppercase tracking-widest mb-3 ml-1">Height (cm)</label>
                                <input type="number" id="bmi-height" placeholder="e.g. 175" class="w-full px-6 py-4 rounded-2xl bg-slate-50 border-2 border-transparent focus:bg-white focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 transition-all outline-none font-bold text-lg">
                            </div>
                            <button onclick="calculateBMI()" class="w-full py-5 bg-blue-600 text-white rounded-2xl font-black text-lg transition-all shadow-xl shadow-blue-200 hover:bg-blue-700 hover:-translate-y-1 active:scale-[0.98]">Calculate BMI</button>
                        </div>
                        <div id="bmi-result-box" class="bg-blue-50/50 rounded-[40px] p-10 flex flex-col items-center justify-center text-center border-2 border-dashed border-blue-100">
                            <div id="bmi-placeholder">
                                <div class="w-20 h-20 bg-white rounded-full flex items-center justify-center mb-4 shadow-sm">
                                    <svg class="w-10 h-10 text-blue-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>
                                </div>
                                <p class="text-slate-400 font-bold">Results will appear here</p>
                            </div>
                            <div id="bmi-content" class="hidden">
                                <span class="text-xs font-black text-blue-600 uppercase tracking-[0.2em] mb-4 block">Your Health Index</span>
                                <div id="bmi-value" class="text-7xl font-black text-slate-900 mb-6 tracking-tighter">24.5</div>
                                <div id="bmi-category" class="px-6 py-2.5 rounded-full text-sm font-black uppercase tracking-wider bg-green-100 text-green-700 border border-green-200 inline-block shadow-sm">Normal Weight</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Dosage Calculator -->
                <div id="calc-dosage" class="calc-section hidden">
                    <h2 class="text-3xl font-black text-slate-900 mb-8 flex items-center">
                        <span class="w-12 h-12 bg-indigo-50 text-indigo-600 rounded-2xl flex items-center justify-center mr-5 shadow-inner">
                            <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.642.316a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.823.362l-1.17.976a1 1 0 00.35 1.726l8.223 2.303a2 2 0 001.074 0l8.223-2.303a1 1 0 00.35-1.726l-1.17-.976z"></path></svg>
                        </span>
                        Pediatric Dosage (Clark's Rule)
                    </h2>
                    <div class="grid md:grid-cols-2 gap-12">
                        <div class="space-y-6">
                            <div>
                                <label class="block text-sm font-bold text-slate-500 uppercase tracking-widest mb-3 ml-1">Child's Weight (kg)</label>
                                <input type="number" id="dosage-weight" placeholder="Weight in kg" class="w-full px-6 py-4 rounded-2xl bg-slate-50 border-2 border-transparent focus:bg-white focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10 transition-all outline-none font-bold text-lg">
                            </div>
                            <div>
                                <label class="block text-sm font-bold text-slate-500 uppercase tracking-widest mb-3 ml-1">Adult Dose (mg)</label>
                                <input type="number" id="dosage-adult" placeholder="e.g. 500" class="w-full px-6 py-4 rounded-2xl bg-slate-50 border-2 border-transparent focus:bg-white focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10 transition-all outline-none font-bold text-lg">
                            </div>
                            <button onclick="calculateDosage()" class="w-full py-5 bg-indigo-600 text-white rounded-2xl font-black text-lg transition-all shadow-xl shadow-indigo-200 hover:bg-indigo-700 hover:-translate-y-1 active:scale-[0.98]">Calculate Dose</button>
                        </div>
                        <div id="dosage-result-box" class="bg-indigo-50/50 rounded-[40px] p-10 flex flex-col items-center justify-center text-center border-2 border-dashed border-indigo-100">
                            <div id="dosage-placeholder">
                                <div class="w-20 h-20 bg-white rounded-full flex items-center justify-center mb-4 shadow-sm">
                                    <svg class="w-10 h-10 text-indigo-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                                </div>
                                <p class="text-slate-400 font-bold">Results will appear here</p>
                            </div>
                            <div id="dosage-content" class="hidden">
                                <span class="text-xs font-black text-indigo-600 uppercase tracking-[0.2em] mb-4 block">Child Dosage Estimate</span>
                                <div id="dosage-value" class="text-6xl font-black text-slate-900 mb-4 tracking-tighter">125 mg</div>
                                <p class="text-xs text-slate-500 font-extrabold italic bg-white/60 p-4 rounded-2xl border border-indigo-50">Note: Always consult a physician before administering medication.</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Heart Rate Calculator -->
                <div id="calc-heart" class="calc-section hidden">
                    <h2 class="text-3xl font-black text-slate-900 mb-8 flex items-center">
                        <span class="w-12 h-12 bg-emerald-50 text-emerald-600 rounded-2xl flex items-center justify-center mr-5 shadow-inner">
                            <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path></svg>
                        </span>
                        Target Heart Rate
                    </h2>
                    <div class="grid md:grid-cols-2 gap-12">
                        <div class="space-y-6">
                            <div>
                                <label class="block text-sm font-bold text-slate-500 uppercase tracking-widest mb-3 ml-1">Age (Years)</label>
                                <input type="number" id="heart-age" placeholder="e.g. 30" class="w-full px-6 py-4 rounded-2xl bg-slate-50 border-2 border-transparent focus:bg-white focus:border-emerald-500 focus:ring-4 focus:ring-emerald-500/10 transition-all outline-none font-bold text-lg">
                            </div>
                            <button onclick="calculateHeartRate()" class="w-full py-5 bg-emerald-600 text-white rounded-2xl font-black text-lg transition-all shadow-xl shadow-emerald-200 hover:bg-emerald-700 hover:-translate-y-1 active:scale-[0.98]">Calculate Target</button>
                        </div>
                        <div id="heart-result-box" class="bg-emerald-50/50 rounded-[40px] p-10 flex flex-col items-center justify-center text-center border-2 border-dashed border-emerald-100">
                            <div id="heart-placeholder">
                                <div class="w-20 h-20 bg-white rounded-full flex items-center justify-center mb-4 shadow-sm">
                                    <svg class="w-10 h-10 text-emerald-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path></svg>
                                </div>
                                <p class="text-slate-400 font-bold">Results will appear here</p>
                            </div>
                            <div id="heart-content" class="hidden text-left w-full">
                                <div class="mb-6 text-center">
                                    <span class="text-xs font-black text-emerald-600 uppercase tracking-[0.2em] mb-3 block">Maximum Heart Rate</span>
                                    <div id="heart-max" class="text-5xl font-black text-slate-900 tracking-tighter">190 bpm</div>
                                </div>
                                <div class="p-6 bg-white rounded-3xl border border-emerald-50 shadow-sm">
                                    <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest block mb-3">Vigorous Exercise Zone (50% - 85%)</span>
                                    <div id="heart-target" class="text-2xl font-black text-emerald-600 tracking-tight">95 - 162 bpm</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Liver Function (Child-Pugh Score) -->
                <div id="calc-liver" class="calc-section hidden">
                    <h2 class="text-3xl font-black text-slate-900 mb-8 flex items-center">
                        <span class="w-12 h-12 bg-orange-50 text-orange-600 rounded-2xl flex items-center justify-center mr-5 shadow-inner">
                            <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.642.316a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.823.362l-1.17.976a1 1 0 00.35 1.726l8.223 2.303a2 2 0 001.074 0l8.223-2.303a1 1 0 00.35-1.726l-1.17-.976z"></path></svg>
                        </span>
                        Liver Function (Child-Pugh)
                    </h2>
                    <div class="grid md:grid-cols-2 gap-12">
                        <div class="space-y-4">
                            <div>
                                <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Bilirubin (μmol/L)</label>
                                <select id="liver-bilirubin" class="w-full px-4 py-3 rounded-xl bg-slate-50 border-2 border-transparent focus:border-orange-500 outline-none font-bold text-sm">
                                    <option value="1">Less than 34</option>
                                    <option value="2">34 - 50</option>
                                    <option value="3">Greater than 50</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Serum Albumin (g/L)</label>
                                <select id="liver-albumin" class="w-full px-4 py-3 rounded-xl bg-slate-50 border-2 border-transparent focus:border-orange-500 outline-none font-bold text-sm">
                                    <option value="1">Greater than 35</option>
                                    <option value="2">28 - 35</option>
                                    <option value="3">Less than 28</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">INR / Clotting</label>
                                <select id="liver-inr" class="w-full px-4 py-3 rounded-xl bg-slate-50 border-2 border-transparent focus:border-orange-500 outline-none font-bold text-sm">
                                    <option value="1">Less than 1.7</option>
                                    <option value="2">1.7 - 2.3</option>
                                    <option value="3">Greater than 2.3</option>
                                </select>
                            </div>
                            <button onclick="calculateLiver()" class="w-full py-4 bg-orange-600 text-white rounded-2xl font-black text-sm shadow-lg shadow-orange-100 mt-4">Analyze Liver Health</button>
                        </div>
                        <div id="liver-result-box" class="bg-orange-50/50 rounded-[40px] p-8 flex flex-col items-center justify-center text-center border-2 border-dashed border-orange-100">
                            <div id="liver-placeholder">
                                <p class="text-slate-400 font-bold text-sm">Input data to see scoring</p>
                            </div>
                            <div id="liver-content" class="hidden">
                                <span class="text-[10px] font-black text-orange-600 uppercase tracking-[0.2em] mb-2 block">Child-Pugh Score</span>
                                <div id="liver-score" class="text-6xl font-black text-slate-900 mb-4 tracking-tighter">5</div>
                                <div id="liver-class" class="px-5 py-2 rounded-full text-xs font-black uppercase bg-green-100 text-green-700">Class A (Stable)</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Kidney GFR Calculator -->
                <div id="calc-gfr" class="calc-section hidden">
                    <h2 class="text-3xl font-black text-slate-900 mb-8 flex items-center">
                        <span class="w-12 h-12 bg-cyan-50 text-cyan-600 rounded-2xl flex items-center justify-center mr-5 shadow-inner">
                            <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        </span>
                        Kidney Function (eGFR)
                    </h2>
                    <div class="grid md:grid-cols-2 gap-12">
                        <div class="space-y-4">
                            <div>
                                <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Creatinine (mg/dL)</label>
                                <input type="number" id="gfr-creatinine" step="0.1" placeholder="e.g. 1.0" class="w-full px-4 py-3 rounded-xl bg-slate-50 border-2 border-transparent focus:border-cyan-500 outline-none font-bold">
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Age</label>
                                    <input type="number" id="gfr-age" placeholder="Age" class="w-full px-4 py-3 rounded-xl bg-slate-50 border-2 border-transparent focus:border-cyan-500 outline-none font-bold">
                                </div>
                                <div>
                                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Gender</label>
                                    <select id="gfr-gender" class="w-full px-4 py-3 rounded-xl bg-slate-50 border-2 border-transparent focus:border-cyan-500 outline-none font-bold">
                                        <option value="male">Male</option>
                                        <option value="female">Female</option>
                                    </select>
                                </div>
                            </div>
                            <button onclick="calculateGFR()" class="w-full py-4 bg-cyan-600 text-white rounded-2xl font-black text-sm shadow-lg shadow-cyan-100">Calculate eGFR</button>
                        </div>
                        <div id="gfr-result-box" class="bg-cyan-50/50 rounded-[40px] p-8 flex flex-col items-center justify-center text-center border-2 border-dashed border-cyan-100">
                            <div id="gfr-placeholder"><p class="text-slate-400 font-bold text-sm">Kidney health estimate</p></div>
                            <div id="gfr-content" class="hidden">
                                <span class="text-[10px] font-black text-cyan-600 uppercase tracking-[0.2em] mb-2 block">Estimated GFR</span>
                                <div id="gfr-value" class="text-6xl font-black text-slate-900 mb-4 tracking-tighter">95</div>
                                <div id="gfr-stage" class="px-5 py-2 rounded-full text-xs font-black uppercase bg-green-100 text-green-700">Stage 1 (Normal)</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Blood Sugar (HbA1c to Glucose) -->
                <div id="calc-glucose" class="calc-section hidden">
                    <h2 class="text-3xl font-black text-slate-900 mb-8 flex items-center">
                        <span class="w-12 h-12 bg-rose-50 text-rose-600 rounded-2xl flex items-center justify-center mr-5 shadow-inner">
                            <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                        </span>
                        Blood Sugar Converter
                    </h2>
                    <div class="grid md:grid-cols-2 gap-12">
                        <div class="space-y-6">
                            <div>
                                <label class="block text-sm font-bold text-slate-500 uppercase tracking-widest mb-3 ml-1">HbA1c (%)</label>
                                <input type="number" id="glucose-hba1c" step="0.1" placeholder="e.g. 7.0" class="w-full px-6 py-4 rounded-2xl bg-slate-50 border-2 border-transparent focus:bg-white focus:border-rose-500 outline-none font-bold text-lg">
                            </div>
                            <button onclick="calculateGlucose()" class="w-full py-5 bg-rose-600 text-white rounded-2xl font-black text-lg shadow-xl shadow-rose-200">Convert to Avg Glucose</button>
                        </div>
                        <div id="glucose-result-box" class="bg-rose-50/50 rounded-[40px] p-10 flex flex-col items-center justify-center text-center border-2 border-dashed border-rose-100">
                            <div id="glucose-placeholder"><p class="text-slate-400 font-bold">Convert HbA1c to mg/dL</p></div>
                            <div id="glucose-content" class="hidden">
                                <span class="text-xs font-black text-rose-600 uppercase tracking-[0.2em] mb-4 block">Average Blood Glucose</span>
                                <div id="glucose-value" class="text-6xl font-black text-slate-900 mb-4 tracking-tighter">154</div>
                                <span class="text-sm font-black text-slate-400 uppercase tracking-widest">mg/dL</span>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </section>

    <style>
        .active-tab {
            border-color: #2563eb !important;
            color: #2563eb !important;
            box-shadow: 0 20px 25px -5px rgba(37, 99, 235, 0.1), 0 10px 10px -5px rgba(37, 99, 235, 0.04) !important;
            transform: translateY(-2px);
        }
    </style>

    <script>
        function switchTab(type) {
            document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active-tab'));
            document.getElementById('btn-' + type).classList.add('active-tab');
            document.querySelectorAll('.calc-section').forEach(sec => sec.classList.add('hidden'));
            document.getElementById('calc-' + type).classList.remove('hidden');
        }

        function calculateBMI() {
            const weight = parseFloat(document.getElementById('bmi-weight').value);
            const height = parseFloat(document.getElementById('bmi-height').value) / 100;
            if (weight > 0 && height > 0) {
                const bmi = (weight / (height * height)).toFixed(1);
                document.getElementById('bmi-placeholder').classList.add('hidden');
                document.getElementById('bmi-content').classList.remove('hidden');
                document.getElementById('bmi-value').innerText = bmi;
                let cat = "Normal Weight"; let cls = "bg-green-100 text-green-700 border-green-200";
                if (bmi < 18.5) { cat = "Underweight"; cls = "bg-orange-100 text-orange-700 border-orange-200"; }
                else if (bmi >= 30) { cat = "Obese"; cls = "bg-red-100 text-red-700 border-red-200"; }
                else if (bmi >= 25) { cat = "Overweight"; cls = "bg-yellow-100 text-yellow-700 border-yellow-200"; }
                const catEl = document.getElementById('bmi-category');
                catEl.innerText = cat; catEl.className = "px-6 py-2.5 rounded-full text-sm font-black uppercase tracking-wider border inline-block shadow-sm " + cls;
            }
        }

        function calculateDosage() {
            const weightKg = parseFloat(document.getElementById('dosage-weight').value);
            const adultDose = parseFloat(document.getElementById('dosage-adult').value);
            if (weightKg > 0 && adultDose > 0) {
                const childDose = (((weightKg * 2.204) / 150) * adultDose).toFixed(1);
                document.getElementById('dosage-placeholder').classList.add('hidden');
                document.getElementById('dosage-content').classList.remove('hidden');
                document.getElementById('dosage-value').innerText = childDose + " mg";
            }
        }

        function calculateHeartRate() {
            const age = parseInt(document.getElementById('heart-age').value);
            if (age > 0) {
                const max = 220 - age;
                document.getElementById('heart-placeholder').classList.add('hidden');
                document.getElementById('heart-content').classList.remove('hidden');
                document.getElementById('heart-max').innerText = max + " bpm";
                document.getElementById('heart-target').innerText = Math.round(max * 0.5) + " - " + Math.round(max * 0.85) + " bpm";
            }
        }

        function calculateLiver() {
            const score = parseInt(document.getElementById('liver-bilirubin').value) + 
                          parseInt(document.getElementById('liver-albumin').value) + 
                          parseInt(document.getElementById('liver-inr').value) + 2; // Fixed values for basic Pugh
            document.getElementById('liver-placeholder').classList.add('hidden');
            document.getElementById('liver-content').classList.remove('hidden');
            document.getElementById('liver-score').innerText = score;
            let res = "Class A (Stable)"; let cls = "bg-green-100 text-green-700";
            if (score > 9) { res = "Class C (Severe)"; cls = "bg-red-100 text-red-700"; }
            else if (score > 6) { res = "Class B (Moderate)"; cls = "bg-orange-100 text-orange-700"; }
            const catEl = document.getElementById('liver-class');
            catEl.innerText = res; catEl.className = "px-5 py-2 rounded-full text-xs font-black uppercase " + cls;
        }

        function calculateGFR() {
            const cr = parseFloat(document.getElementById('gfr-creatinine').value);
            const age = parseInt(document.getElementById('gfr-age').value);
            const gender = document.getElementById('gfr-gender').value;
            if (cr > 0 && age > 0) {
                // Simplified MDRD formula
                let gfr = 175 * Math.pow(cr, -1.154) * Math.pow(age, -0.203);
                if (gender === 'female') gfr *= 0.742;
                gfr = Math.round(gfr);
                document.getElementById('gfr-placeholder').classList.add('hidden');
                document.getElementById('gfr-content').classList.remove('hidden');
                document.getElementById('gfr-value').innerText = gfr;
                let stg = "Stage 1 (Normal)"; let cls = "bg-green-100 text-green-700";
                if (gfr < 15) { stg = "Stage 5 (Failure)"; cls = "bg-red-100 text-red-700"; }
                else if (gfr < 30) { stg = "Stage 4 (Severe)"; cls = "bg-red-100 text-red-700"; }
                else if (gfr < 60) { stg = "Stage 3 (Moderate)"; cls = "bg-orange-100 text-orange-700"; }
                else if (gfr < 90) { stg = "Stage 2 (Mild)"; cls = "bg-yellow-100 text-yellow-700"; }
                const catEl = document.getElementById('gfr-stage');
                catEl.innerText = stg; catEl.className = "px-5 py-2 rounded-full text-xs font-black uppercase " + cls;
            }
        }

        function calculateGlucose() {
            const hba1c = parseFloat(document.getElementById('glucose-hba1c').value);
            if (hba1c > 0) {
                const glucose = Math.round((28.7 * hba1c) - 46.7);
                document.getElementById('glucose-placeholder').classList.add('hidden');
                document.getElementById('glucose-content').classList.remove('hidden');
                document.getElementById('glucose-value').innerText = glucose;
            }
        }
    </script>

<?php include 'includes/footer.php'; ?>
