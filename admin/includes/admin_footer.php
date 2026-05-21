
<!-- Notification Sound -->
<audio id="adminNotificationSound" preload="auto">
    <source src="https://assets.mixkit.co/active_storage/sfx/2869/2869-preview.mp3" type="audio/mpeg">
</audio>

<!-- Prominent Alert Modal -->
<div id="prominentAlert" class="fixed inset-0 z-[200] flex items-center justify-center p-4 bg-slate-900/80 backdrop-blur-md hidden no-print">
    <div class="bg-white rounded-[40px] w-full max-w-md p-10 shadow-2xl animate-zoom-in text-center relative overflow-hidden">
        <div class="absolute top-0 left-0 w-full h-2 bg-blue-600"></div>
        <div id="alertIconBox" class="w-24 h-24 bg-blue-50 text-blue-600 rounded-[32px] flex items-center justify-center mx-auto mb-8 animate-pulse shadow-inner">
            <i class="fas fa-bell text-3xl"></i>
        </div>
        <div class="space-y-3 mb-10">
            <span class="px-4 py-1.5 bg-blue-50 text-blue-600 text-[10px] font-black uppercase tracking-[0.2em] rounded-full">New Update</span>
            <h3 id="alertTitle" class="text-2xl font-black text-slate-900 leading-tight">New Notification</h3>
            <p id="alertMessage" class="text-slate-500 font-medium leading-relaxed px-4">You have a new update in the system.</p>
        </div>
        <div class="flex flex-col gap-3">
            <a id="alertAction" href="#" class="w-full py-5 bg-blue-600 text-white rounded-[24px] font-black text-sm tracking-widest uppercase hover:bg-blue-700 transition-all shadow-xl shadow-blue-200 flex items-center justify-center gap-3">
                View Details
                <i class="fas fa-arrow-right"></i>
            </a>
            <button onclick="closeAlert()" class="w-full py-4 text-slate-400 font-bold text-xs uppercase tracking-widest hover:text-slate-600 transition-all">Dismiss Now</button>
        </div>
    </div>
</div>

<script>
function closeAlert() {
    document.getElementById('prominentAlert').classList.add('hidden');
}
</script>
