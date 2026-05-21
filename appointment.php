<?php 
session_start();
require_once 'includes/db_connect.php';

$patient_name = "";
$patient_email = "";
$patient_phone = "";
$is_logged_in = false;

if (isset($_SESSION['patient_id'])) {
    $patient_id = $_SESSION['patient_id'];
    $stmt = $conn->prepare("SELECT full_name, email, phone FROM patients WHERE id = ?");
    $stmt->bind_param("i", $patient_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($patient = $result->fetch_assoc()) {
        $patient_name = $patient['full_name'];
        $patient_email = $patient['email'];
        $patient_phone = $patient['phone'];
        $is_logged_in = true;
    }
}
if ($is_logged_in) {
    include 'includes/dashboard_header.php';
} else {
    include 'includes/header.php';
} 
?>

    <!-- Page Header -->
    <section class="relative py-24 bg-slate-900 overflow-hidden">
        <div class="absolute inset-0 opacity-40">
            <img src="assets/img/external/appointment-header.jpg" class="w-full h-full object-cover" alt="Appointment Header">
        </div>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10 text-center">
            <h1 class="text-4xl lg:text-6xl font-black text-white mb-6" data-aos="fade-down">Book Appointment</h1>
            <p class="text-xl text-blue-100 font-medium max-w-2xl mx-auto" data-aos="fade-up" data-aos-delay="100">Schedule your visit with our specialized doctors easily and quickly.</p>
        </div>
    </section>

    <!-- Appointment Form Section -->
    <section class="py-24 bg-slate-50">
        <div class="max-w-5xl mx-auto px-4">
            <div class="bg-white rounded-[48px] p-10 lg:p-16 shadow-xl border border-slate-100 relative overflow-hidden" data-aos="zoom-in">
                
                <!-- Progress Bar -->
                <div class="flex justify-between mb-12 relative max-w-2xl mx-auto">
                    <div class="absolute top-1/2 left-0 w-full h-1 bg-slate-100 -z-10 rounded-full"></div>
                    <div class="step-indicator active w-10 h-10 rounded-full bg-blue-600 text-white flex items-center justify-center font-bold text-sm shadow-lg shadow-blue-200 z-10">1</div>
                    <div class="step-indicator w-10 h-10 rounded-full bg-slate-200 text-slate-500 flex items-center justify-center font-bold text-sm z-10">2</div>
                    <div class="step-indicator w-10 h-10 rounded-full bg-slate-200 text-slate-500 flex items-center justify-center font-bold text-sm z-10">3</div>
                    <div class="step-indicator w-10 h-10 rounded-full bg-slate-200 text-slate-500 flex items-center justify-center font-bold text-sm z-10">4</div>
                </div>

                <form id="appointmentForm" class="space-y-8">
                    <!-- Step 1: Specialist Selection -->
                    <div class="step-content" id="step1">
                        <div class="text-center mb-10">
                            <h3 class="text-3xl font-black text-slate-900 mb-2">Select Specialist</h3>
                            <p class="text-slate-500 font-medium">Choose the department, doctor, and consultation type.</p>
                        </div>
                        <div class="grid md:grid-cols-3 gap-8 mb-8">
                            <div class="space-y-2">
                                <label class="text-sm font-bold text-slate-700 ml-2">Consultation Type</label>
                                <select name="type" id="type" required class="w-full bg-slate-50 border-0 rounded-2xl px-6 py-4 text-slate-900 focus:ring-2 focus:ring-blue-500 transition-all outline-none cursor-pointer">
                                    <option value="physical">Physical Visit</option>
                                    <option value="virtual">Virtual Consultation</option>
                                </select>
                            </div>
                            <div class="space-y-2">
                                <label class="text-sm font-bold text-slate-700 ml-2">Department</label>
                                <select name="department" id="department" required class="w-full bg-slate-50 border-0 rounded-2xl px-6 py-4 text-slate-900 focus:ring-2 focus:ring-blue-500 transition-all outline-none cursor-pointer">
                                    <option value="">Choose Department</option>
                                </select>
                            </div>
                            <div class="space-y-2">
                                <label class="text-sm font-bold text-slate-700 ml-2">Doctor</label>
                                <select name="doctor" id="doctor" required class="w-full bg-slate-50 border-0 rounded-2xl px-6 py-4 text-slate-900 focus:ring-2 focus:ring-blue-500 transition-all outline-none cursor-pointer" disabled>
                                    <option value="">Select Doctor</option>
                                </select>
                            </div>
                        </div>
                        
                        <div id="doctor-card" class="mt-10 bg-blue-50 rounded-[32px] p-8 hidden items-center space-x-8 border border-blue-100 transition-all animate-fade-in">
                            <div class="relative">
                                <img id="doctor-card-img" src="" class="w-24 h-24 rounded-3xl object-cover shadow-lg border-4 border-white">
                                <div class="absolute -bottom-2 -right-2 w-8 h-8 bg-emerald-500 rounded-full border-4 border-white flex items-center justify-center">
                                    <svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 20 20"><path d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 011.414 0l4-4z"></path></svg>
                                </div>
                            </div>
                            <div>
                                <h4 id="doctor-card-name" class="text-xl font-black text-slate-900"></h4>
                                <p id="doctor-card-dept" class="text-sm font-bold text-blue-600 uppercase tracking-wider mb-2"></p>
                                <div class="flex items-center text-slate-500 text-xs font-bold">
                                    <svg class="w-4 h-4 mr-1 text-yellow-400" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path></svg>
                                    <span>Top Rated Specialist</span>
                                </div>
                            </div>
                        </div>

                        <!-- Consultation Summary & Fee -->
                        <div id="fee-summary" class="mt-8 bg-slate-900 rounded-3xl p-8 text-white hidden animate-fade-in">
                            <div class="flex flex-col md:flex-row justify-between items-center gap-6">
                                <div>
                                    <p class="text-blue-400 text-[10px] font-black uppercase tracking-[0.2em] mb-1">Consultation Fee</p>
                                    <h4 class="text-3xl font-black">₦<span id="display-fee">0</span></h4>
                                </div>
                                <div class="text-center md:text-right">
                                    <p class="text-slate-400 text-[10px] font-bold uppercase tracking-widest mb-2">Required for booking</p>
                                    <div class="flex items-center gap-2 justify-center md:justify-end">
                                        <div class="w-2 h-2 bg-emerald-500 rounded-full animate-pulse"></div>
                                        <span class="text-xs font-bold text-emerald-400">Secure Payment Enabled</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mt-12 text-center">
                            <button type="button" onclick="validateStep1()" class="px-12 py-5 bg-blue-600 text-white rounded-2xl font-black text-lg hover:bg-blue-700 transition-all shadow-xl shadow-blue-200 group">
                                Continue to Calendar
                                <svg class="w-5 h-5 ml-2 inline group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"></path></svg>
                            </button>
                        </div>
                    </div>

                    <!-- Step 2: Interactive Calendar -->
                    <div class="step-content hidden" id="step2">
                        <div class="text-center mb-10">
                            <h3 class="text-3xl font-black text-slate-900 mb-2">Select Date & Time</h3>
                            <p class="text-slate-500 font-medium">Pick an available day highlighted in <span class="text-emerald-500 font-black uppercase">Green</span>.</p>
                        </div>

                        <div class="grid lg:grid-cols-3 gap-10">
                            <!-- Calendar Widget -->
                            <div class="lg:col-span-2 bg-white rounded-[32px] border border-slate-100 shadow-sm p-6">
                                <div class="flex items-center justify-between mb-8 px-4">
                                    <h4 id="calendar-month" class="text-xl font-black text-slate-900">March 2026</h4>
                                    <div class="flex space-x-2">
                                        <button type="button" onclick="changeMonth(-1)" class="p-3 rounded-xl bg-slate-50 hover:bg-blue-50 text-slate-400 hover:text-blue-600 transition-all border border-slate-100">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
                                        </button>
                                        <button type="button" onclick="changeMonth(1)" class="p-3 rounded-xl bg-slate-50 hover:bg-blue-50 text-slate-400 hover:text-blue-600 transition-all border border-slate-100">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                                        </button>
                                    </div>
                                </div>
                                
                                <div class="grid grid-cols-7 gap-2 text-center mb-4">
                                    <div class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Sun</div>
                                    <div class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Mon</div>
                                    <div class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Tue</div>
                                    <div class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Wed</div>
                                    <div class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Thu</div>
                                    <div class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Fri</div>
                                    <div class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Sat</div>
                                </div>
                                <div id="calendar-days" class="grid grid-cols-7 gap-2">
                                    <!-- Days injected via JS -->
                                </div>
                            </div>

                            <!-- Time Slots -->
                            <div class="space-y-6">
                                <div class="bg-blue-50 rounded-[32px] p-8 border border-blue-100">
                                    <h4 class="text-lg font-black text-slate-900 mb-6">Select Time</h4>
                                    <div class="grid grid-cols-2 gap-3" id="time-slots">
                                        <button type="button" class="time-slot px-4 py-3 rounded-2xl bg-white border border-slate-100 text-sm font-bold text-slate-600 hover:border-blue-500 transition-all" data-slot="09:00" onclick="selectTime('09:00', this)">09:00 AM</button>
                                        <button type="button" class="time-slot px-4 py-3 rounded-2xl bg-white border border-slate-100 text-sm font-bold text-slate-600 hover:border-blue-500 transition-all" data-slot="10:00" onclick="selectTime('10:00', this)">10:00 AM</button>
                                        <button type="button" class="time-slot px-4 py-3 rounded-2xl bg-white border border-slate-100 text-sm font-bold text-slate-600 hover:border-blue-500 transition-all" data-slot="11:00" onclick="selectTime('11:00', this)">11:00 AM</button>
                                        <button type="button" class="time-slot px-4 py-3 rounded-2xl bg-white border border-slate-100 text-sm font-bold text-slate-600 hover:border-blue-500 transition-all" data-slot="12:00" onclick="selectTime('12:00', this)">12:00 PM</button>
                                        <button type="button" class="time-slot px-4 py-3 rounded-2xl bg-white border border-slate-100 text-sm font-bold text-slate-600 hover:border-blue-500 transition-all" data-slot="14:00" onclick="selectTime('14:00', this)">02:00 PM</button>
                                        <button type="button" class="time-slot px-4 py-3 rounded-2xl bg-white border border-slate-100 text-sm font-bold text-slate-600 hover:border-blue-500 transition-all" data-slot="15:00" onclick="selectTime('15:00', this)">03:00 PM</button>
                                        <button type="button" class="time-slot px-4 py-3 rounded-2xl bg-white border border-slate-100 text-sm font-bold text-slate-600 hover:border-blue-500 transition-all" data-slot="16:00" onclick="selectTime('16:00', this)">04:00 PM</button>
                                    </div>
                                    <p id="time-error" class="mt-4 text-xs font-bold text-red-500 hidden text-center">Please select a time slot.</p>
                                </div>
                                <div class="bg-slate-900 rounded-[32px] p-8 text-white">
                                    <p class="text-xs font-bold text-blue-400 uppercase tracking-widest mb-2">Selected Schedule</p>
                                    <p id="selection-summary" class="text-lg font-bold">No date selected</p>
                                </div>
                            </div>
                        </div>

                        <input type="hidden" name="date" id="selected-date">
                        <input type="hidden" name="time" id="selected-time">

                        <div class="mt-12 flex justify-between">
                            <button type="button" onclick="prevStep(1)" class="px-8 py-4 bg-slate-100 text-slate-600 rounded-2xl font-bold hover:bg-slate-200 transition-all">&larr; Change Specialist</button>
                            <button type="button" onclick="validateStep2()" class="px-8 py-4 bg-blue-600 text-white rounded-2xl font-bold hover:bg-blue-700 transition-all shadow-lg shadow-blue-200">Fill My Info &rarr;</button>
                        </div>
                    </div>

                    <!-- Step 3: Patient Information -->
                    <div class="step-content hidden" id="step3">
                        <div class="text-center mb-10">
                            <h3 class="text-3xl font-black text-slate-900 mb-2">Patient Details</h3>
                            <p class="text-slate-500 font-medium">Please provide your contact information for the appointment.</p>
                        </div>
                        <div class="grid md:grid-cols-2 gap-8">
                            <div class="space-y-2">
                                <label class="text-sm font-bold text-slate-700 ml-2">Full Name</label>
                                <input type="text" name="name" id="name" required class="w-full bg-slate-50 border-0 rounded-2xl px-6 py-4 text-slate-900 focus:ring-2 focus:ring-blue-500 transition-all outline-none" placeholder="John Doe" value="<?php echo htmlspecialchars($patient_name); ?>">
                            </div>
                            <div class="space-y-2">
                                <label class="text-sm font-bold text-slate-700 ml-2">Phone Number</label>
                                <input type="tel" name="phone" id="phone" required class="w-full bg-slate-50 border-0 rounded-2xl px-6 py-4 text-slate-900 focus:ring-2 focus:ring-blue-500 transition-all outline-none" placeholder="+234 ..." value="<?php echo htmlspecialchars($patient_phone); ?>">
                            </div>
                            <div class="md:col-span-2 space-y-2">
                                <label class="text-sm font-bold text-slate-700 ml-2">Email Address</label>
                                <input type="email" name="email" id="email" required class="w-full bg-slate-50 border-0 rounded-2xl px-6 py-4 text-slate-900 focus:ring-2 focus:ring-blue-500 transition-all outline-none" placeholder="john@example.com" value="<?php echo htmlspecialchars($patient_email); ?>">
                            </div>
                            <div class="md:col-span-2 space-y-2">
                                <label class="text-sm font-bold text-slate-700 ml-2">Reason for Visit</label>
                                <textarea name="reason" id="reason" rows="3" class="w-full bg-slate-50 border-0 rounded-2xl px-6 py-4 text-slate-900 focus:ring-2 focus:ring-blue-500 transition-all outline-none resize-none" placeholder="Briefly describe your symptoms or reason for the visit..."></textarea>
                            </div>
                        </div>
                        <div class="mt-12 flex justify-between">
                            <button type="button" onclick="prevStep(2)" class="px-8 py-4 bg-slate-100 text-slate-600 rounded-2xl font-bold hover:bg-slate-200 transition-all">&larr; Change Date</button>
                            <button type="button" onclick="validateStep3()" class="px-8 py-4 bg-blue-600 text-white rounded-2xl font-bold hover:bg-blue-700 transition-all shadow-lg shadow-blue-200">Review Booking &rarr;</button>
                        </div>
                    </div>

                    <!-- Step 4: Final Confirmation -->
                    <div class="step-content hidden" id="step4">
                        <div class="text-center mb-10">
                            <h3 class="text-3xl font-black text-slate-900 mb-2">Review & Confirm</h3>
                            <p class="text-slate-500 font-medium">Please review your appointment details before submitting.</p>
                        </div>
                        <div class="bg-white rounded-[40px] border border-slate-100 shadow-xl overflow-hidden mb-10">
                            <div class="bg-blue-600 p-8 text-white">
                                <div class="flex justify-between items-start">
                                    <div>
                                        <p class="text-blue-100 text-xs font-bold uppercase tracking-widest mb-1">Appointment With</p>
                                        <h4 id="summary-doctor" class="text-2xl font-black"></h4>
                                        <p id="summary-dept" class="text-sm font-bold text-blue-200 uppercase"></p>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-blue-100 text-xs font-bold uppercase tracking-widest mb-1">Scheduled For</p>
                                        <p id="summary-datetime" class="text-xl font-black"></p>
                                    </div>
                                </div>
                            </div>
                            <div class="p-10 space-y-6">
                                <div class="grid md:grid-cols-2 gap-8">
                                    <div>
                                        <p class="text-slate-400 text-[10px] font-black uppercase tracking-[0.2em] mb-2">Patient Name</p>
                                        <p id="summary-name" class="text-slate-900 font-bold"></p>
                                    </div>
                                    <div>
                                        <p class="text-slate-400 text-[10px] font-black uppercase tracking-[0.2em] mb-2">Contact Info</p>
                                        <p id="summary-contact" class="text-slate-900 font-bold"></p>
                                    </div>
                                    <div class="md:col-span-2">
                                        <p class="text-slate-400 text-[10px] font-black uppercase tracking-[0.2em] mb-2">Reason for Visit</p>
                                        <p id="summary-reason" class="text-slate-900 font-medium leading-relaxed"></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="mt-8 flex justify-between">
                            <button type="button" onclick="prevStep(3)" class="px-8 py-4 bg-slate-100 text-slate-600 rounded-2xl font-bold hover:bg-slate-200 transition-all focus:ring-4 focus:ring-slate-100">&larr; Edit Info</button>
                            <button type="submit" id="submitBtn" class="px-12 py-5 bg-emerald-500 text-white rounded-2xl font-black text-lg hover:bg-emerald-600 transition-all shadow-xl shadow-emerald-200 btn-pulse flex items-center justify-center gap-3 min-w-[240px] active:scale-95">
                                <span id="btnText">Confirm & Book Now</span>
                                <div id="btnLoader" class="hidden">
                                    <svg class="animate-spin h-6 w-6 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                </div>
                            </button>
                        </div>
                    </div>
                </form>

                <!-- Success Message -->
                <div id="success-msg" class="hidden text-center py-10 animate-fade-in">
                    <div class="w-24 h-24 bg-emerald-100 rounded-full flex items-center justify-center mx-auto mb-8 shadow-inner">
                        <svg class="w-12 h-12 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path></svg>
                    </div>
                    <h3 class="text-4xl font-black text-slate-900 mb-4">Booking Successful!</h3>
                    <p class="text-slate-500 font-medium text-lg mb-10 max-w-md mx-auto">Your appointment has been confirmed. A confirmation email with details has been sent to your inbox.</p>
                    <div class="flex justify-center gap-4">
                        <a href="index.php" class="px-8 py-4 bg-blue-600 text-white rounded-2xl font-bold hover:bg-blue-700 transition-all shadow-lg active:scale-95">Back to Home</a>
                        <button onclick="window.location.reload()" class="px-8 py-4 bg-slate-100 text-slate-600 rounded-2xl font-bold hover:bg-slate-200 transition-all active:scale-95">Book Another</button>
                    </div>
                </div>

            </div>
        </div>
    </section>

    <style>
        .calendar-day {
            aspect-ratio: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.875rem;
            font-weight: 700;
            border-radius: 1rem;
            cursor: pointer;
            transition: all 0.2s;
            border: 2px solid transparent;
        }
        .day-available { background-color: #f0fdf4; color: #059669; border-color: #dcfce7; }
        .day-available:hover { transform: scale(1.05); background-color: #dcfce7; border-color: #10b981; }
        .day-booked { background-color: #fef2f2; color: #dc2626; border-color: #fee2e2; cursor: not-allowed; }
        .day-selected { background-color: #2563eb !important; color: white !important; border-color: #1d4ed8 !important; box-shadow: 0 10px 15px -3px rgba(37, 99, 235, 0.4); transform: scale(1.1); }
        .day-muted { color: #cbd5e1; cursor: default; }
        .day-today { border-color: #2563eb; }
        
        .time-slot.selected { background-color: #2563eb; color: white; border-color: #1d4ed8; }
        .time-slot.disabled { background-color: #f1f5f9; color: #cbd5e1; border-color: #e2e8f0; cursor: not-allowed; opacity: 0.7; }
        
        @keyframes fade-in {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .animate-fade-in { animation: fade-in 0.5s ease-out forwards; }
        .btn-pulse:active { transform: scale(0.95); }
    </style>

    <script>
        let currentYear = new Date().getFullYear();
        let currentMonth = new Date().getMonth();
        let selectedDateStr = "";
        let bookedDates = [];
        let doctorData = [];

        document.addEventListener('DOMContentLoaded', () => {
            fetch('api/get_departments.php')
                .then(res => res.json())
                .then(data => {
                    const deptSelect = document.getElementById('department');
                    data.forEach(dept => {
                        const option = document.createElement('option');
                        option.value = dept.id;
                        option.textContent = dept.name;
                        deptSelect.appendChild(option);
                    });
                });
        });

        // Step 1: Specialist Selection
        document.getElementById('department').addEventListener('change', async function() {
            const deptId = this.value;
            const type = document.getElementById('type').value;
            const docSelect = document.getElementById('doctor');
            const docCard = document.getElementById('doctor-card');
            const feeSummary = document.getElementById('fee-summary');
            const feeDisplay = document.getElementById('display-fee');
            
            docSelect.innerHTML = '<option value="">Select Doctor</option>';
            docSelect.disabled = true;
            docCard.classList.add('hidden');
            feeSummary.classList.add('hidden');

            if (deptId) {
                // 1. Fetch Fee
                try {
                    const feeRes = await fetch(`api/billing_engine_helper.php?action=get_fee&dept_id=${deptId}&type=${type}`);
                    const feeData = await feeRes.json();
                    if (feeData.success) {
                        feeDisplay.textContent = new Intl.NumberFormat().format(feeData.fee);
                        feeSummary.classList.remove('hidden');
                    }
                } catch(e) { console.error("Fee fetch failed", e); }

                // 2. Fetch Doctors
                fetch(`api/get_doctors.php?dept_id=${deptId}&type=${type}`)
                    .then(res => res.json())
                    .then(data => {
                        doctorData = data;
                        data.forEach(doc => {
                            const option = document.createElement('option');
                            option.value = doc.id;
                            option.textContent = doc.name;
                            docSelect.appendChild(option);
                        });
                        docSelect.disabled = false;
                    });
            }
        });

        // Also refresh doctors when type changes
        document.getElementById('type').addEventListener('change', () => {
            document.getElementById('department').dispatchEvent(new Event('change'));
        });

        document.getElementById('doctor').addEventListener('change', function() {
            const docId = this.value;
            const docCard = document.getElementById('doctor-card');
            if (docId) {
                const doctor = doctorData.find(d => d.id == docId);
                if (doctor) {
                    document.getElementById('doctor-card-img').src = doctor.image_url;
                    document.getElementById('doctor-card-name').textContent = doctor.name;
                    document.getElementById('doctor-card-dept').textContent = document.getElementById('department').options[document.getElementById('department').selectedIndex].text;
                    docCard.classList.remove('hidden');
                    docCard.classList.add('flex');
                    // Reset selection when doctor changes
                    selectedDateStr = "";
                    document.getElementById('selected-date').value = "";
                    document.getElementById('selected-time').value = "";
                    document.querySelectorAll('.time-slot').forEach(s => {
                        s.classList.remove('selected', 'disabled');
                        s.disabled = false;
                    });
                    updateSelectionSummary();
                    // Load booked dates for this doctor
                    loadBookedDates(docId);
                }
            } else {
                docCard.classList.add('hidden');
            }
        });

        function validateStep1() {
            if (document.getElementById('doctor').value) {
                showStep(2);
                renderCalendar();
            } else {
                alert("Please select a doctor first.");
            }
        }

        // Step 2: Calendar Logic
        async function loadBookedDates(doctorId) {
            const type = document.getElementById('type').value;
            const res = await fetch(`api/get_booked_dates.php?doctor_id=${doctorId}&month=${currentMonth + 1}&year=${currentYear}&type=${type}`);
            bookedDates = await res.json();
            renderCalendar();
        }

        function renderCalendar() {
            const calendarDays = document.getElementById('calendar-days');
            const monthHeader = document.getElementById('calendar-month');
            const date = new Date(currentYear, currentMonth, 1);
            
            monthHeader.textContent = date.toLocaleDateString('default', { month: 'long', year: 'numeric' });
            
            calendarDays.innerHTML = "";
            
            const firstDayIndex = date.getDay();
            const lastDay = new Date(currentYear, currentMonth + 1, 0).getDate();
            const prevLastDay = new Date(currentYear, currentMonth, 0).getDate();
            const today = new Date();

            // Muted days from prev month
            for (let x = firstDayIndex; x > 0; x--) {
                calendarDays.innerHTML += `<div class="calendar-day day-muted">${prevLastDay - x + 1}</div>`;
            }

            // Current month days
            for (let i = 1; i <= lastDay; i++) {
                const dateStr = `${currentYear}-${String(currentMonth + 1).padStart(2, '0')}-${String(i).padStart(2, '0')}`;
                const isBooked = bookedDates.includes(dateStr);
                const isPast = new Date(currentYear, currentMonth, i) < today.setHours(0,0,0,0);
                const isToday = new Date(currentYear, currentMonth, i).toDateString() === new Date().toDateString();
                
                let classes = isBooked ? 'day-booked' : 'day-available';
                if (isPast) classes = 'day-muted';
                if (isToday) classes += ' day-today';
                if (dateStr === selectedDateStr) classes += ' day-selected';

                const onclick = (isPast || isBooked) ? '' : `onclick="selectDate('${dateStr}', this)"`;
                
                calendarDays.innerHTML += `<div class="calendar-day ${classes}" ${onclick}>${i}</div>`;
            }
        }

        function changeMonth(dir) {
            currentMonth += dir;
            if (currentMonth < 0) {
                currentMonth = 11;
                currentYear--;
            } else if (currentMonth > 11) {
                currentMonth = 0;
                currentYear++;
            }
            loadBookedDates(document.getElementById('doctor').value);
        }

        function selectDate(dateStr, el) {
            selectedDateStr = dateStr;
            document.getElementById('selected-date').value = dateStr;
            
            document.querySelectorAll('.calendar-day').forEach(d => d.classList.remove('day-selected'));
            el.classList.add('day-selected');
            
            // Clear current time selection when date changes
            document.getElementById('selected-time').value = "";
            document.querySelectorAll('.time-slot').forEach(s => s.classList.remove('selected'));

            // Fetch booked slots for this date
            const doctorId = document.getElementById('doctor').value;
            const type = document.getElementById('type').value;
            
            fetch(`api/get_booked_slots.php?doctor_id=${doctorId}&date=${dateStr}&type=${type}`)
                .then(res => res.json())
                .then(bookedSlots => {
                    document.querySelectorAll('.time-slot').forEach(slotBtn => {
                        const slotTime = slotBtn.getAttribute('data-slot');
                        if (bookedSlots.includes(slotTime)) {
                            slotBtn.classList.add('disabled');
                            slotBtn.disabled = true;
                        } else {
                            slotBtn.classList.remove('disabled');
                            slotBtn.disabled = false;
                        }
                    });
                    updateSelectionSummary();
                });
        }

        function selectTime(time, el) {
            if (el.disabled) return;
            document.getElementById('selected-time').value = time;
            document.querySelectorAll('.time-slot').forEach(s => s.classList.remove('selected'));
            el.classList.add('selected');
            updateSelectionSummary();
        }

        function updateSelectionSummary() {
            const date = selectedDateStr ? new Date(selectedDateStr).toLocaleDateString('default', { day: 'numeric', month: 'short', year: 'numeric' }) : "No date";
            const time = document.getElementById('selected-time').value || "";
            document.getElementById('selection-summary').textContent = time ? `${date} at ${time}` : date;
        }

        function validateStep2() {
            const date = document.getElementById('selected-date').value;
            const time = document.getElementById('selected-time').value;
            
            if (!date) { alert("Please select a date from the calendar."); return; }
            if (!time) { document.getElementById('time-error').classList.remove('hidden'); return; }
            
            document.getElementById('time-error').classList.add('hidden');
            showStep(3);
        }

        // Step 3: Info Validation
        function validateStep3() {
            const inputs = document.querySelectorAll(`#step3 input[required]`);
            let valid = true;
            inputs.forEach(input => {
                if (!input.checkValidity()) {
                    input.reportValidity();
                    valid = false;
                }
            });

            if (valid) {
                updateSummary();
                showStep(4);
            }
        }

        function updateSummary() {
            document.getElementById('summary-doctor').textContent = document.getElementById('doctor').options[document.getElementById('doctor').selectedIndex].text;
            document.getElementById('summary-dept').textContent = document.getElementById('department').options[document.getElementById('department').selectedIndex].text;
            document.getElementById('summary-datetime').textContent = document.getElementById('selection-summary').textContent;
            document.getElementById('summary-name').textContent = document.getElementById('name').value;
            document.getElementById('summary-contact').textContent = `${document.getElementById('email').value} | ${document.getElementById('phone').value}`;
            document.getElementById('summary-reason').textContent = document.getElementById('reason').value || "Routine check-up";
        }

        function showStep(step) {
            document.querySelectorAll('.step-content').forEach(el => el.classList.add('hidden'));
            document.getElementById(`step${step}`).classList.remove('hidden');
            
            document.querySelectorAll('.step-indicator').forEach((el, index) => {
                if (index < step) {
                    el.classList.add('bg-blue-600', 'text-white');
                    el.classList.remove('bg-slate-200', 'text-slate-500');
                } else {
                    el.classList.remove('bg-blue-600', 'text-white');
                    el.classList.add('bg-slate-200', 'text-slate-500');
                }
            });
        }

        function prevStep(step) {
            showStep(step);
        }

        // Final Submission
        document.getElementById('appointmentForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const btn = document.getElementById('submitBtn');
            const btnText = document.getElementById('btnText');
            const btnLoader = document.getElementById('btnLoader');
            
            // Loading State
            btn.disabled = true;
            btn.classList.add('opacity-80', 'cursor-not-allowed');
            btnText.textContent = 'Processing...';
            btnLoader.classList.remove('hidden');

            const formData = new FormData(this);

            fetch('api/book_appointment.php', {
                method: 'POST',
                body: formData
            })
            .then(async res => {
                const text = await res.text();
                try {
                    return JSON.parse(text);
                } catch (e) {
                    console.error('Raw Response:', text);
                    throw new Error('Server returned invalid response');
                }
            })
            .then(data => {
                if (data.success) {
                    document.getElementById('appointmentForm').classList.add('hidden');
                    document.getElementById('success-msg').classList.remove('hidden');

                    // If there's an invoice, show a payment button and redirect
                    if (data.invoice_id) {
                        const payContainer = document.createElement('div');
                        payContainer.className = "mt-8 p-6 bg-emerald-50 rounded-2xl border border-emerald-100 animate-pulse";
                        payContainer.innerHTML = `
                            <p class="text-sm font-bold text-emerald-700 mb-4 text-center">Your appointment is reserved! Redirecting you to payment...</p>
                            <div class="flex justify-center">
                                <a href="view_invoice_patient.php?id=${data.invoice_id}" class="px-8 py-4 bg-emerald-600 text-white rounded-2xl font-black text-xs uppercase tracking-widest shadow-xl shadow-emerald-200 hover:bg-emerald-700 transition-all">
                                    Click here if not redirected
                                </a>
                            </div>
                        `;
                        document.getElementById('success-msg').appendChild(payContainer);

                        // Automatic redirection after 2 seconds
                        setTimeout(() => {
                            window.location.href = `view_invoice_patient.php?id=${data.invoice_id}`;
                        }, 2000);
                    }

                    document.getElementById('success-msg').scrollIntoView({ behavior: 'smooth', block: 'center' });
                } else {
                    alert('Booking Error: ' + data.message);
                    resetBtn();
                }
                })
                .catch(err => {
                console.error('Fetch Error:', err);
                alert('An unexpected error occurred. Please check your connection and try again.');
                resetBtn();
                });

                function resetBtn() {
                btn.disabled = false;
                btn.classList.remove('opacity-80', 'cursor-not-allowed');
                btnText.textContent = 'Confirm & Book Now';
                btnLoader.classList.add('hidden');
                }
                });

                function payNow(invoiceId) {
                const btn = document.getElementById('directPayBtn');
                btn.innerText = 'Initializing...';
                btn.disabled = true;

                const formData = new FormData();
                formData.append('invoice_id', invoiceId);

                fetch('api/initialize_payment.php', {
                method: 'POST',
                body: formData
                })
                .then(response => response.json())
                .then(data => {
                if (data.status === 'success') {
                    window.location.href = data.authorization_url;
                } else {
                    alert(data.message || 'Could not initialize payment.');
                    btn.innerText = 'Proceed to Payment';
                    btn.disabled = false;
                }
                })
                .catch(error => {
                console.error('Error:', error);
                alert('An unexpected error occurred.');
                btn.innerText = 'Proceed to Payment';
                btn.disabled = false;
                });
                }
                </script>
<?php include 'includes/footer.php'; ?>
