<?php include 'includes/header.php'; ?>

    <!-- Page Header -->
    <section class="relative py-24 bg-blue-600 overflow-hidden">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10 text-center">
            <h1 class="text-4xl lg:text-6xl font-black text-white mb-6" data-aos="fade-down">Get In Touch</h1>
            <p class="text-xl text-blue-100 font-medium max-w-2xl mx-auto" data-aos="fade-up" data-aos-delay="100">We are here to help you 24/7. Reach out to us for any inquiries or emergencies.</p>
        </div>
    </section>

    <!-- Contact Content -->
    <section class="py-24 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid lg:grid-cols-3 gap-16">
                <!-- Info Column -->
                <div class="space-y-12" data-aos="fade-right">
                    <div>
                        <h3 class="text-2xl font-black text-slate-900 mb-6">Contact Information</h3>
                        <p class="text-slate-500 font-medium mb-8">Visit us or call our emergency line for immediate assistance.</p>
                    </div>
                    
                    <div class="space-y-8">
                        <div class="flex items-start space-x-6">
                            <div class="w-12 h-12 bg-blue-50 text-blue-600 rounded-2xl flex items-center justify-center shrink-0">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                            </div>
                            <div>
                                <h5 class="font-extrabold text-slate-900">Our Location</h5>
                                <p class="text-slate-500 font-medium text-sm">Emmause Road, Plateau, Nigeria</p>
                            </div>
                        </div>
                        <div class="flex items-start space-x-6">
                            <div class="w-12 h-12 bg-emerald-50 text-emerald-600 rounded-2xl flex items-center justify-center shrink-0">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path></svg>
                            </div>
                            <div>
                                <h5 class="font-extrabold text-slate-900">Phone Number</h5>
                                <p class="text-slate-500 font-medium text-sm">+234 123 456 7890</p>
                                <p class="text-blue-600 font-bold text-sm">Emergency: +234 800 123 4567</p>
                            </div>
                        </div>
                        <div class="flex items-start space-x-6">
                            <div class="w-12 h-12 bg-indigo-50 text-indigo-600 rounded-2xl flex items-center justify-center shrink-0">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                            </div>
                            <div>
                                <h5 class="font-extrabold text-slate-900">Email Address</h5>
                                <p class="text-slate-500 font-medium text-sm">info@hopehaven.ng</p>
                                <p class="text-slate-500 font-medium text-sm">support@hopehaven.ng</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Form Column -->
                <div class="lg:col-span-2" data-aos="fade-left">
                    <div class="bg-slate-50 p-10 lg:p-16 rounded-[48px]">
                        <h3 class="text-3xl font-black text-slate-900 mb-8">Send a Message</h3>
                        <form id="contactForm" class="grid md:grid-cols-2 gap-6">
                            <div class="space-y-2">
                                <label class="text-sm font-bold text-slate-700 ml-2">Full Name</label>
                                <input type="text" name="name" id="name" required placeholder="John Doe" class="w-full bg-white border-0 rounded-2xl px-6 py-4 text-slate-900 focus:ring-2 focus:ring-blue-500 transition-all outline-none">
                            </div>
                            <div class="space-y-2">
                                <label class="text-sm font-bold text-slate-700 ml-2">Email Address</label>
                                <input type="email" name="email" id="email" required placeholder="john@example.com" class="w-full bg-white border-0 rounded-2xl px-6 py-4 text-slate-900 focus:ring-2 focus:ring-blue-500 transition-all outline-none">
                            </div>
                            <div class="md:col-span-2 space-y-2">
                                <label class="text-sm font-bold text-slate-700 ml-2">Subject</label>
                                <input type="text" name="subject" id="subject" required placeholder="How can we help?" class="w-full bg-white border-0 rounded-2xl px-6 py-4 text-slate-900 focus:ring-2 focus:ring-blue-500 transition-all outline-none">
                            </div>
                            <div class="md:col-span-2 space-y-2">
                                <label class="text-sm font-bold text-slate-700 ml-2">Message</label>
                                <textarea name="message" id="message" required rows="5" placeholder="Your message here..." class="w-full bg-white border-0 rounded-2xl px-6 py-4 text-slate-900 focus:ring-2 focus:ring-blue-500 transition-all outline-none"></textarea>
                            </div>
                            <div class="md:col-span-2 pt-4">
                                <button type="submit" id="submitBtn" class="w-full md:w-auto px-12 py-5 bg-blue-600 text-white rounded-2xl font-extrabold btn-premium text-lg shadow-xl shadow-blue-200">
                                    Send Message
                                </button>
                            </div>
                        </form>
                        <div id="contact-success" class="hidden text-center bg-green-50 p-10 rounded-3xl mt-8">
                            <h4 class="text-2xl font-black text-green-700">Message Sent!</h4>
                            <p class="text-green-600 font-medium">Thank you for contacting us. A confirmation email has been sent to you.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <script>
        document.getElementById('contactForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const btn = document.getElementById('submitBtn');
            const originalText = btn.innerText;
            btn.innerText = 'Sending...';
            btn.disabled = true;

            const formData = new FormData(this);

            fetch('<?php echo BASE_URL; ?>/api/send_contact.php', {
                method: 'POST',
                body: formData
            })
            .then(async res => {
                const text = await res.text();
                try {
                    return JSON.parse(text);
                } catch (e) {
                    throw new Error('Server returned invalid response: ' + text.substring(0, 100));
                }
            })
            .then(data => {
                if (data.success) {
                    document.getElementById('contactForm').classList.add('hidden');
                    document.getElementById('contact-success').classList.remove('hidden');
                } else {
                    alert('System Message: ' + data.message);
                    btn.innerText = originalText;
                    btn.disabled = false;
                }
            })
            .catch(err => {
                console.error(err);
                alert('Debug Info: ' + err.message);
                btn.innerText = originalText;
                btn.disabled = false;
            });
        });
    </script>

<?php include 'includes/footer.php'; ?>
