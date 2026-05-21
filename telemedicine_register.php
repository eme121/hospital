<?php include 'includes/header.php'; ?>

    <section class="min-h-screen bg-slate-50 py-12 md:py-24">
        <div class="max-w-xl mx-auto px-4">
            <div class="bg-white rounded-[40px] p-8 md:p-10 lg:p-16 shadow-2xl border border-slate-100">
                <div class="text-center mb-10">
                    <h2 class="text-3xl md:text-4xl font-black text-slate-900 leading-tight">Specialist Registration</h2>
                    <p class="text-slate-500 font-medium mt-2">Join the HOPE HAVEN collaboration network.</p>
                </div>

                <form id="registerForm" class="space-y-6" enctype="multipart/form-data">
                    <div class="grid md:grid-cols-2 gap-6">
                        <div class="space-y-2">
                            <label class="text-sm font-bold text-slate-700 ml-2">Full Name</label>
                            <input type="text" name="name" required class="w-full bg-slate-50 border-0 rounded-2xl px-6 py-4 text-slate-900 focus:ring-2 focus:ring-blue-500 transition-all outline-none">
                        </div>
                        <div class="space-y-2">
                            <label class="text-sm font-bold text-slate-700 ml-2">Email Address</label>
                            <input type="email" name="email" required class="w-full bg-slate-50 border-0 rounded-2xl px-6 py-4 text-slate-900 focus:ring-2 focus:ring-blue-500 transition-all outline-none">
                        </div>
                        <div class="space-y-2">
                            <label class="text-sm font-bold text-slate-700 ml-2">Password</label>
                            <input type="password" name="password" required class="w-full bg-slate-50 border-0 rounded-2xl px-6 py-4 text-slate-900 focus:ring-2 focus:ring-blue-500 transition-all outline-none">
                        </div>
                        <div class="space-y-2">
                            <label class="text-sm font-bold text-slate-700 ml-2">Department</label>
                            <select name="department" id="department" required class="w-full bg-slate-50 border-0 rounded-2xl px-6 py-4 text-slate-900 focus:ring-2 focus:ring-blue-500 transition-all outline-none cursor-pointer">
                                <option value="">Choose Specialty</option>
                                <!-- Dynamic Options -->
                            </select>
                        </div>
                    </div>

                    <div class="space-y-2">
                        <label class="text-sm font-bold text-slate-700 ml-2">Profile Picture</label>
                        <input type="file" name="profile_pix" accept="image/*" class="w-full bg-slate-50 border-0 rounded-2xl px-6 py-4 text-slate-500 focus:ring-2 focus:ring-blue-500 transition-all outline-none">
                        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mt-1 ml-2">Optional: JPG/PNG format (Max 2MB)</p>
                    </div>

                    <div id="status-msg" class="text-sm font-bold text-center hidden p-4 rounded-2xl"></div>

                    <button type="submit" class="w-full bg-blue-600 text-white py-5 rounded-2xl font-black text-lg hover:bg-blue-700 transition-all shadow-xl shadow-blue-200">
                        Register Account
                    </button>
                </form>

                <p class="text-center mt-10 text-slate-500 font-medium text-sm">
                    Already a member? <a href="telemedicine_login.php" class="text-blue-600 font-bold hover:underline">Sign In here</a>
                </p>
            </div>
        </div>
    </section>

    <script>
        // Fetch Departments
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

        document.getElementById('registerForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            const statusMsg = document.getElementById('status-msg');

            fetch('api/telemedicine_auth.php?action=register', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                statusMsg.textContent = data.message;
                statusMsg.classList.remove('hidden', 'bg-red-50', 'text-red-600', 'bg-green-50', 'text-green-600');
                if (data.success) {
                    statusMsg.classList.add('bg-green-50', 'text-green-600');
                    setTimeout(() => window.location.href = 'telemedicine_login.php', 3000);
                } else {
                    statusMsg.classList.add('bg-red-50', 'text-red-600');
                }
            });
        });
    </script>

<?php include 'includes/footer.php'; ?>
