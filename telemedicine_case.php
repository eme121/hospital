<?php
session_start();
require_once 'includes/db_connect.php';
if (!isset($_SESSION['doctor_id'])) { header('Location: telemedicine_login.php'); exit; }
$case_id = intval($_GET['id'] ?? 0);
if ($case_id <= 0) { header('Location: telemedicine_dashboard.php'); exit; }
$doctor_id = $_SESSION['doctor_id'];
$doctor_name = $_SESSION['doctor_name'];
$stmt = $conn->prepare("SELECT c.*, COALESCE(d.name, 'System') as created_by_name FROM telemedicine_cases c LEFT JOIN telemedicine_doctors d ON c.created_by = d.id WHERE c.id = ?");
$stmt->bind_param("i", $case_id);
$stmt->execute();
$case = $stmt->get_result()->fetch_assoc();
if (!$case) { header('Location: telemedicine_dashboard.php'); exit; }

// Membership and Status Check
$member_stmt = $conn->prepare("SELECT status, role FROM telemedicine_case_members WHERE case_id = ? AND doctor_id = ?");
$member_stmt->bind_param("ii", $case_id, $doctor_id);
$member_stmt->execute();
$member_data = $member_stmt->get_result()->fetch_assoc();

if (!$member_data) {
    $is_owner = ($doctor_id == $case['created_by']);
    if (!$is_owner) {
        header('Location: telemedicine_dashboard.php?error=unauthorized');
        exit;
    }
}

$invitation_pending = ($member_data && $member_data['status'] === 'pending');
$is_owner = ($doctor_id == $case['created_by'] || ($member_data && $member_data['role'] === 'Lead Physician'));

if (!$is_owner) {
    $c_email = $conn->query("SELECT email FROM telemedicine_doctors WHERE id = " . intval($doctor_id))->fetch_assoc()['email'] ?? '';
    $o_email = $conn->query("SELECT email FROM telemedicine_doctors WHERE id = " . intval($case['created_by']))->fetch_assoc()['email'] ?? '';
    if ($c_email && $c_email === $o_email) $is_owner = true;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>War Room | Case #<?php echo $case_id; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://meet.ffmuc.net/external_api.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="assets/css/telemedicine_war_room.css?v=<?php echo time(); ?>">
    <style>.pending-overlay { backdrop-filter: blur(12px); background: rgba(2, 6, 23, 0.8); }</style>
</head>
<body class="war-room-body">
    <?php if ($invitation_pending): ?>
    <div class="fixed inset-0 z-[1000] flex items-center justify-center pending-overlay p-6">
        <div class="bg-slate-900 border border-slate-800 p-12 rounded-[40px] max-w-lg w-full text-center shadow-2xl">
            <div class="w-20 h-20 bg-indigo-500/10 text-indigo-400 rounded-3xl flex items-center justify-center mx-auto mb-8 border border-indigo-500/20"><i data-lucide="handshake" class="w-10 h-10"></i></div>
            <h2 class="text-2xl font-black text-white uppercase tracking-tighter mb-4">Board Room Invitation</h2>
            <p class="text-slate-400 text-sm leading-relaxed mb-10">You have been invited to collaborate on this clinical case.</p>
            <button onclick="acceptCaseInvitation(<?php echo $case_id; ?>)" class="w-full py-5 bg-indigo-600 hover:bg-indigo-500 text-white rounded-2xl font-black text-xs uppercase tracking-[0.2em] transition-all shadow-xl shadow-indigo-900/40">Accept & Join Board</button>
        </div>
    </div>
    <script>
        function acceptCaseInvitation(cId) {
            const fd = new FormData(); fd.append('case_id', cId);
            fetch('api/telemedicine_cases.php?action=accept_invitation', { method: 'POST', body: fd }).then(r => r.json()).then(d => { if (d.success) location.reload(); });
        }
    </script>
    <?php endif; ?>

    <div class="bento-grid">
        <header class="bento-header">
            <div class="flex items-center gap-4">
                <a href="telemedicine_dashboard.php" class="p-2 bg-slate-800 rounded-lg hover:bg-slate-700 transition-all"><i data-lucide="arrow-left" class="w-4 h-4 text-slate-400"></i></a>
                <div>
                    <h1 class="text-sm font-black text-white uppercase tracking-tighter"><?php echo htmlspecialchars($case['patient_name_or_id']); ?></h1>
                    <div class="flex items-center gap-2"><span class="w-2 h-2 rounded-full bg-emerald-500 vital-pulse"></span><span class="text-[10px] font-bold text-emerald-500 uppercase">Live Collaboration</span></div>
                </div>
            </div>
            <div id="presenceIndicator" class="flex -space-x-2"></div>
            <div class="flex gap-1 md:gap-2 flex-wrap justify-end">
                <button id="huddleBtn" onclick="window.toggleHuddle(true, false)" class="bg-indigo-500/10 text-indigo-400 border border-indigo-500/20 px-3 md:px-4 py-2 rounded-xl text-[10px] font-black uppercase hover:bg-indigo-500/20 transition-all flex items-center gap-2">
                    <i data-lucide="video" class="w-3 h-3"></i> <span class="hidden md:inline">Video Huddle</span>
                </button>
                <button id="audioBtn" onclick="window.toggleHuddle(true, true)" class="bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 px-3 md:px-4 py-2 rounded-xl text-[10px] font-black uppercase hover:bg-emerald-500/20 transition-all flex items-center gap-2">
                    <i data-lucide="phone" class="w-3 h-3"></i> <span class="hidden md:inline">Audio Call</span>
                </button>
                <button onclick="window.toggleModal('vitalsModal')" class="bg-rose-500/10 text-rose-400 border border-rose-500/20 px-3 md:px-4 py-2 rounded-xl text-[10px] font-black uppercase hover:bg-rose-500/20 transition-all">
                    <i data-lucide="activity" class="md:hidden w-3 h-3"></i> <span class="hidden md:inline">Log Vitals</span>
                </button>
                <button onclick="window.toggleModal('prescriptionModal')" class="bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 px-3 md:px-4 py-2 rounded-xl text-[10px] font-black uppercase hover:bg-emerald-500/20 transition-all">
                    <i data-lucide="pill" class="md:hidden w-3 h-3"></i> <span class="hidden md:inline">Prescribe</span>
                </button>
                <button onclick="window.toggleModal('labModal')" class="bg-blue-500/10 text-blue-400 border border-blue-500/20 px-3 md:px-4 py-2 rounded-xl text-[10px] font-black uppercase hover:bg-blue-500/20 transition-all">
                    <i data-lucide="test-tube" class="md:hidden w-3 h-3"></i> <span class="hidden md:inline">Lab Order</span>
                </button>
                <?php if ($is_owner): ?>
                <button onclick="window.toggleModal('finalizeModal')" class="bg-slate-100 text-slate-900 px-3 md:px-4 py-2 rounded-xl text-[10px] font-black uppercase hover:bg-white transition-all border border-white/20">
                    <i data-lucide="check-circle" class="md:hidden w-3 h-3"></i> <span class="hidden md:inline">Finalize Assessment</span>
                </button>
                <?php endif; ?>
            </div>
        </header>

        <aside class="bento-sidebar border-r border-slate-800">
            <div class="bento-panel-title"><i data-lucide="activity" class="w-3 h-3 text-rose-500"></i> Patient Pulse</div>
            <div id="vitalsPanel" class="p-6 space-y-6">
                <div class="grid grid-cols-2 gap-4">
                    <div class="clinical-card flex flex-col items-center justify-center py-4"><span class="text-[10px] text-slate-500 font-bold uppercase mb-1">BP (Sys)</span><span id="stat-bp" class="text-xl font-black text-white">--</span><canvas id="spark-bp" class="w-full h-8 mt-2 opacity-50"></canvas></div>
                    <div class="clinical-card flex flex-col items-center justify-center py-4"><span class="text-[10px] text-slate-500 font-bold uppercase mb-1">HR (BPM)</span><span id="stat-hr" class="text-xl font-black text-rose-400">--</span><canvas id="spark-hr" class="w-full h-8 mt-2 opacity-50"></canvas></div>
                </div>
            </div>
        </aside>

        <main class="bento-canvas">
            <div class="bento-panel-title"><i data-lucide="message-square" class="w-3 h-3 text-cyan-500"></i> Clinical Discussion</div>
            <div id="chatWindow" class="flex-1 overflow-y-auto p-8 flex flex-col gap-6"></div>
            <div class="p-6 bg-slate-900/50 border-t border-slate-800">
                <div class="mb-6 px-4">
                    <div class="flex justify-between items-center mb-2"><span class="text-[9px] font-black text-slate-500 uppercase tracking-widest">Diagnostic Timeline</span><span id="timelineLabel" class="text-[9px] font-black text-cyan-500 uppercase">Live (Real-Time)</span></div>
                    <input type="range" id="timelineSlider" min="0" max="100" value="100" class="w-full h-1.5 bg-slate-800 rounded-lg appearance-none cursor-pointer accent-cyan-500 transition-all">
                </div>
                <form onsubmit="window.handleSendMessage(); return false;" class="flex gap-4 max-w-4xl mx-auto items-end">
                    <div class="flex-1 relative">
                        <textarea id="msgInput" placeholder="Contribute to clinical assessment..." class="w-full bg-slate-800 text-white rounded-2xl p-4 pr-32 outline-none border border-slate-700 focus:border-cyan-500 transition-all resize-none min-h-[56px] text-sm" rows="1"></textarea>
                        <div class="absolute right-4 bottom-3.5 flex items-center gap-3">
                            <input type="file" id="fileInput" class="hidden" onchange="window.previewFile(this)">
                            <button type="button" onclick="document.getElementById('fileInput').click()" class="text-slate-500 hover:text-cyan-400 transition-colors"><i data-lucide="paperclip" class="w-5 h-5"></i></button>
                            <button type="button" id="voiceBtn" onclick="window.handleVoiceClick()" class="text-slate-500 hover:text-rose-400 transition-colors"><i data-lucide="mic" class="w-5 h-5"></i></button>
                        </div>
                    </div>
                    <button type="submit" class="bg-cyan-600 hover:bg-cyan-500 text-white px-8 py-4 rounded-2xl font-black text-sm uppercase tracking-widest transition-all shadow-lg shadow-cyan-900/20">Send</button>
                </form>
            </div>
        </main>

        <aside class="bento-sidebar border-l border-slate-800">
            <div class="bento-panel-title"><i data-lucide="brain-circuit" class="w-3 h-3 text-emerald-500"></i> Case Intelligence</div>
            <div class="p-6 space-y-8">
                <div>
                    <div class="flex justify-between items-center mb-4">
                        <h4 class="text-[10px] font-black text-slate-500 uppercase tracking-widest">Medical Board</h4>
                        <div class="flex gap-2">
                            <button onclick="window.copyInviteLink()" class="text-[10px] font-black text-slate-500 uppercase hover:underline">Link</button>
                            <?php if ($is_owner): ?>
                                <button onclick="window.openInviteModal()" class="text-[10px] font-black text-emerald-500 uppercase hover:underline">+ Invite</button>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div id="medicalBoardList" class="space-y-3"></div>
                </div>
            </div>
        </aside>
        </div>

        <!-- Modals -->
        <div id="vitalsModal" class="fixed inset-0 bg-black/50 hidden items-center justify-center p-4 z-[100]">     
            <div class="bg-white p-8 rounded-2xl w-full max-w-md">
                <h3 class="font-bold mb-4 text-slate-900">Log Vitals</h3>
                <form onsubmit="window.handleVitals(event)" class="space-y-4">
                    <input type="hidden" name="patient_id" value="<?php echo $case['patient_id']; ?>">
                    <input type="hidden" name="case_id" value="<?php echo $case_id; ?>">
                    <div class="flex gap-2"><input type="number" name="blood_pressure_sys" placeholder="SYS" class="flex-1 bg-slate-50 p-2 rounded text-slate-900"><input type="number" name="blood_pressure_dia" placeholder="DIA" class="flex-1 bg-slate-50 p-2 rounded text-slate-900"></div>
                    <div class="flex gap-2"><input type="number" step="0.1" name="temperature" placeholder="Temp" class="flex-1 bg-slate-50 p-2 rounded text-slate-900"><input type="number" name="heart_rate" placeholder="BPM" class="flex-1 bg-slate-50 p-2 rounded text-slate-900"></div>
                    <div class="flex gap-2"><button type="button" onclick="window.toggleModal('vitalsModal')" class="flex-1 p-2 text-slate-500">Cancel</button><button type="submit" class="flex-1 bg-rose-600 text-white p-2 rounded">Save</button></div>
                </form>
            </div>
        </div>

        <div id="prescriptionModal" class="fixed inset-0 bg-slate-900/80 backdrop-blur-md hidden items-center justify-center p-4 z-[1000]">
            <div class="bg-white rounded-[40px] w-full max-w-lg p-10 shadow-2xl">
                <div class="flex justify-between items-center mb-8">
                    <h3 class="text-2xl font-black text-slate-900 uppercase">Prescription Script</h3>
                    <button onclick="window.toggleModal('prescriptionModal')"><i data-lucide="x" class="text-slate-400"></i></button>
                </div>
                <form id="prescriptionForm" onsubmit="window.submitPrescription(event)" class="space-y-4">
                    <input type="hidden" name="case_id" value="<?php echo $case_id; ?>">
                    <input type="hidden" name="patient_id" value="<?php echo $case['patient_id']; ?>">
                    <div class="space-y-2">
                        <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-4">Medication & Strength</label>
                        <input type="text" name="medications" required class="w-full px-6 py-4 bg-slate-50 rounded-2xl border-0 text-sm font-bold text-slate-900" placeholder="e.g. Tab. Amoxicillin 500mg">
                    </div>
                    <div class="space-y-2">
                        <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-4">Dosage Instruction</label>
                        <input type="text" name="dosage" required class="w-full px-6 py-4 bg-slate-50 rounded-2xl border-0 text-sm font-bold text-slate-900" placeholder="e.g. 1 cap TID for 5 days">
                    </div>
                    <div class="space-y-2">
                        <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-4">Pharmacist Notes</label>
                        <textarea name="notes" class="w-full px-6 py-4 bg-slate-50 rounded-2xl border-0 text-sm font-bold text-slate-900 h-24 resize-none" placeholder="Special instructions..."></textarea>
                    </div>
                    <div class="pt-4 flex gap-4">
                        <button type="button" onclick="window.toggleModal('prescriptionModal')" class="flex-1 py-4 text-slate-400 font-bold uppercase text-[10px]">Cancel</button>
                        <button type="submit" class="flex-2 py-4 bg-emerald-600 text-white rounded-2xl font-black uppercase text-[10px] tracking-widest shadow-lg shadow-emerald-100">Send to Pharmacy</button>
                    </div>
                </form>
            </div>
        </div>

        <div id="labModal" class="fixed inset-0 bg-slate-900/80 backdrop-blur-md hidden items-center justify-center p-4 z-[1000]">
            <div class="bg-white rounded-[40px] w-full max-w-2xl shadow-2xl overflow-hidden flex flex-col max-h-[85vh]">
                <div class="p-8 border-b border-slate-100 flex justify-between items-center">
                    <h3 class="text-xl font-black text-slate-900 uppercase">Specialist Lab Order</h3>
                    <button onclick="window.toggleModal('labModal')"><i data-lucide="x" class="text-slate-400"></i></button>
                </div>
                <form id="labForm" onsubmit="window.submitLabOrder(event)" class="flex-1 overflow-y-auto p-8">
                    <input type="hidden" name="case_id" value="<?php echo $case_id; ?>">
                    <input type="hidden" name="patient_id" value="<?php echo $case['patient_id']; ?>">
                    
                    <div id="tele-lab-list" class="grid grid-cols-2 gap-3 mb-8">
                        <p class="col-span-2 text-center text-slate-400 italic py-4">Loading tests...</p>
                    </div>
                    
                    <div class="space-y-2 border-t border-slate-100 pt-6">
                        <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-4">Other / Manual Request</label>
                        <input type="text" name="manual_test" class="w-full px-6 py-4 bg-slate-50 rounded-2xl border-0 text-sm font-bold text-slate-900" placeholder="Type specific test name...">
                    </div>

                    <div class="space-y-2 mt-4">
                        <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-4">Priority</label>
                        <select name="priority" class="w-full px-6 py-4 bg-slate-50 rounded-2xl border-0 text-sm font-bold text-slate-900">
                            <option value="Normal">Normal</option>
                            <option value="Urgent">Urgent / STAT</option>
                        </select>
                    </div>
                </form>
                <div class="p-8 bg-slate-50 border-t border-slate-100 flex gap-4">
                    <button onclick="window.toggleModal('labModal')" class="flex-1 py-4 bg-white text-slate-500 rounded-2xl font-bold uppercase text-[10px]">Back</button>
                    <button onclick="document.getElementById('labForm').requestSubmit()" class="flex-1 py-4 bg-blue-600 text-white rounded-2xl font-black uppercase text-[10px] tracking-widest">Post Order</button>
                </div>
            </div>
        </div>

        <div id="finalizeModal" class="fixed inset-0 bg-slate-900/95 backdrop-blur-xl hidden items-start justify-center p-4 z-[2000] overflow-y-auto">
            <div class="bg-slate-900 border border-slate-800 p-6 md:p-8 rounded-[32px] max-w-2xl w-full shadow-2xl my-4 md:my-10">
                <div class="flex justify-between items-start mb-6">
                    <div>
                        <h3 class="text-xl font-black text-white uppercase tracking-tighter">Finalize Clinical Opinion</h3>
                        <p class="text-[9px] font-black text-blue-500 uppercase tracking-widest mt-1">SOAP Assessment Framework</p>
                    </div>
                    <button onclick="window.toggleModal('finalizeModal')" class="text-slate-500 hover:text-white"><i data-lucide="x"></i></button>
                </div>

                <form id="finalizeForm" class="space-y-4">
                    <input type="hidden" name="case_id" value="<?php echo $case_id; ?>">
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="space-y-1">
                            <label class="text-[8px] font-black text-slate-500 uppercase tracking-widest">Subjective (S)</label>
                            <textarea name="subjective" placeholder="Complaints..." class="w-full bg-slate-800/50 border border-slate-700 rounded-xl p-3 text-xs text-white outline-none focus:border-blue-500 h-20 resize-none"></textarea>
                        </div>
                        <div class="space-y-1">
                            <label class="text-[8px] font-black text-slate-500 uppercase tracking-widest">Objective (O)</label>
                            <textarea name="objective" placeholder="Findings..." class="w-full bg-slate-800/50 border border-slate-700 rounded-xl p-3 text-xs text-white outline-none focus:border-blue-500 h-20 resize-none"></textarea>
                        </div>
                    </div>

                    <div class="space-y-1">
                        <label class="text-[8px] font-black text-slate-500 uppercase tracking-widest">Assessment (A)</label>
                        <textarea name="assessment" required placeholder="Diagnosis..." class="w-full bg-slate-800/50 border border-slate-700 rounded-xl p-3 text-xs text-white outline-none focus:border-blue-500 h-20 resize-none"></textarea>
                    </div>

                    <div class="space-y-1">
                        <label class="text-[8px] font-black text-slate-500 uppercase tracking-widest">Plan (P)</label>
                        <textarea name="plan" required placeholder="Treatment..." class="w-full bg-slate-800/50 border border-slate-700 rounded-xl p-3 text-xs text-white outline-none focus:border-blue-500 h-20 resize-none"></textarea>
                    </div>

                    <!-- Digital Signature Pad -->
                    <div class="space-y-1">
                        <label class="text-[8px] font-black text-slate-500 uppercase tracking-widest">Lead Physician Signature</label>
                        <div class="relative bg-white rounded-xl h-24 overflow-hidden">
                            <canvas id="sig-canvas" class="w-full h-full cursor-crosshair"></canvas>
                            <button type="button" onclick="clearSignature()" class="absolute bottom-2 right-2 text-[8px] font-black text-slate-400 uppercase tracking-widest hover:text-rose-500">Clear</button>
                        </div>
                        <input type="hidden" name="signature_data" id="sig-data">
                    </div>

                    <div class="pt-2 flex gap-3">
                        <button type="button" onclick="window.toggleModal('finalizeModal')" class="flex-1 py-3 bg-slate-800 text-slate-400 rounded-xl font-black text-[10px] uppercase tracking-widest">Cancel</button>
                        <button type="submit" class="flex-[2] py-3 bg-blue-600 hover:bg-blue-500 text-white rounded-xl font-black text-[10px] uppercase tracking-[0.2em] transition-all shadow-xl shadow-blue-900/40">Confirm & Close Case</button>
                    </div>
                </form>
            </div>
        </div>

        <script>
            // Signature Pad Logic
            let sigCanvas, sigCtx, isDrawing = false;
            
            document.addEventListener('DOMContentLoaded', () => {
                sigCanvas = document.getElementById('sig-canvas');
                if(!sigCanvas) return;
                sigCtx = sigCanvas.getContext('2d');
                sigCanvas.width = sigCanvas.offsetWidth;
                sigCanvas.height = sigCanvas.offsetHeight;
                sigCtx.strokeStyle = "#0f172a";
                sigCtx.lineWidth = 2;

                const getPos = (e) => {
                    const rect = sigCanvas.getBoundingClientRect();
                    return { x: (e.clientX || e.touches[0].clientX) - rect.left, y: (e.clientY || e.touches[0].clientY) - rect.top };
                };

                const start = (e) => { isDrawing = true; const p = getPos(e); sigCtx.beginPath(); sigCtx.moveTo(p.x, p.y); };
                const move = (e) => { if(!isDrawing) return; e.preventDefault(); const p = getPos(e); sigCtx.lineTo(p.x, p.y); sigCtx.stroke(); };
                const stop = () => { isDrawing = false; document.getElementById('sig-data').value = sigCanvas.toDataURL(); };

                sigCanvas.addEventListener('mousedown', start);
                sigCanvas.addEventListener('mousemove', move);
                window.addEventListener('mouseup', stop);
                sigCanvas.addEventListener('touchstart', start);
                sigCanvas.addEventListener('touchmove', move);
                sigCanvas.addEventListener('touchend', stop);
            });

            function clearSignature() {
                sigCtx.clearRect(0, 0, sigCanvas.width, sigCanvas.height);
                document.getElementById('sig-data').value = '';
            }

            document.getElementById('finalizeForm').addEventListener('submit', function(e) {
                e.preventDefault();
                if(!document.getElementById('sig-data').value) {
                    Swal.fire('Signature Required', 'Please provide your digital signature to finalize.', 'warning');
                    return;
                }

                Swal.fire({
                    title: 'Finalize Case?',
                    text: "This will close the collaboration and generate the final SOAP report.",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Yes, Finalize',
                    confirmButtonColor: '#2563eb'
                }).then((result) => {
                    if (result.isConfirmed) {
                        const fd = new FormData(this);
                        fetch('api/telemedicine_cases.php?action=finalize', { method: 'POST', body: fd })
                        .then(r => r.json())
                        .then(d => {
                            if(d.success) {
                                Swal.fire('Case Finalized', 'The SOAP assessment has been generated and the case is now closed.', 'success')
                                .then(() => window.location.href = 'telemedicine_dashboard.php');
                            } else {
                                Swal.fire('Error', d.message, 'error');
                            }
                        });
                    }
                });
            });
        </script>
        
        <div id="inviteModal" class="fixed inset-0 bg-slate-900/80 backdrop-blur-sm hidden items-center justify-center p-4 z-[1000]">
        <div class="bg-slate-900 border border-slate-800 p-8 rounded-[40px] max-w-lg w-full shadow-2xl">
            <div class="flex justify-between items-center mb-6">
                <h3 class="text-xl font-black text-white uppercase tracking-tighter">Invite Specialist</h3>
                <button onclick="window.toggleModal('inviteModal')" class="text-slate-400 hover:text-white"><i data-lucide="x"></i></button>
            </div>
            <div class="space-y-4 max-h-96 overflow-y-auto pr-2 custom-scrollbar" id="specialistList">
                <p class="text-slate-500 text-center py-8 italic">Loading specialists...</p>
            </div>
        </div>
        </div>
    <div id="activeCallBar" class="hidden fixed top-0 left-0 right-0 bg-emerald-600 text-white py-2 px-6 flex justify-between items-center z-[2000] animate-in slide-in-from-top duration-500">
        <div class="flex items-center gap-4"><span class="w-2 h-2 bg-white rounded-full animate-pulse"></span><span class="text-[10px] font-black uppercase tracking-widest">Active Audio Consultation</span><span id="callPartner" class="text-[10px] font-medium opacity-80 italic"></span></div>
        <button onclick="window.endNativeCall()" class="bg-white/20 hover:bg-white/30 px-4 py-1 rounded-lg text-[9px] font-black uppercase">End Call</button>
    </div>

    <div id="debugConsole" class="fixed bottom-20 left-4 w-72 bg-slate-900 border border-slate-700 rounded-xl p-4 z-[2500] font-mono text-[10px] text-slate-300 shadow-2xl">
        <div class="flex justify-between items-center mb-2 border-b border-slate-800 pb-2">
            <span class="text-cyan-500 font-black uppercase tracking-widest">Signaling Monitor</span>
            <div class="flex items-center gap-3">
                <button onclick="window.testSignaling()" class="bg-cyan-600 hover:bg-cyan-500 text-white px-2 py-0.5 rounded text-[9px] font-black uppercase tracking-tighter transition-colors">Test Sync</button>
                <span id="syncHeartbeat" class="w-2 h-2 bg-slate-700 rounded-full"></span>
            </div>
        </div>
        <div id="debugOutput" class="space-y-1 max-h-40 overflow-hidden flex flex-col-reverse"></div>
    </div>

    <!-- Scripts -->
    <script>window.APP_BASE_URL = '<?php echo BASE_URL; ?>';</script>
    <script src="assets/js/sync_engine.js?v=<?php echo time(); ?>"></script>
    <script src="assets/js/telemedicine_signals.js?v=<?php echo time(); ?>"></script>
    <script>
        const cId = <?php echo $case_id; ?>, dId = <?php echo $doctor_id; ?>;
        const apiPath = window.APP_BASE_URL + '/api/';
        
        let lastId = 0, huddleSwal = null, ringtone = new Audio('https://assets.mixkit.co/active_storage/sfx/1233/1233-preview.mp3');
        ringtone.loop = true;
        let pc = null, localStream = null, handshakeInterval = null;
        const rtcConfig = { iceServers: [{ urls: 'stun:stun.l.google.com:19302' }] };

        function clinicalLog(msg, color = 'text-slate-400') {
            const out = document.getElementById('debugOutput'); if(!out) return;
            const entry = document.createElement('div'); entry.className = color;
            entry.textContent = `[${new Date().toLocaleTimeString()}] ${msg}`;
            out.appendChild(entry); if(out.children.length > 10) out.firstElementChild.remove();
        }

        window.toggleModal = (id) => { 
            const m = document.getElementById(id); 
            if(m){ 
                m.classList.toggle('hidden'); 
                m.classList.toggle('flex'); 
                if (id === 'labModal' && m.classList.contains('flex')) {
                    window.loadLabTests();
                }
            } 
        };

        window.loadLabTests = () => {
            const list = document.getElementById('tele-lab-list');
            fetch(`${apiPath}nursing_v2.php?action=get_available_tests`)
                .then(r => r.json())
                .then(d => {
                    if (d.success) {
                        list.innerHTML = d.tests.map(t => `
                            <label class="cursor-pointer group">
                                <input type="checkbox" name="test_ids[]" value="${t.id}" class="hidden peer">
                                <div class="p-4 rounded-2xl border-2 border-slate-100 bg-slate-50 peer-checked:border-blue-600 peer-checked:bg-blue-50 transition-all">
                                    <p class="font-black text-slate-900 text-[10px] uppercase">${t.test_name}</p>
                                    <p class="text-[8px] font-bold text-slate-400">₦${parseFloat(t.price).toLocaleString()}</p>
                                </div>
                            </label>
                        `).join('');
                    }
                });
        };

        window.submitPrescription = (e) => {
            e.preventDefault();
            const fd = new FormData(document.getElementById('prescriptionForm'));
            clinicalLog("Posting prescription...");
            fetch(`${apiPath}telemedicine_prescriptions.php?action=create`, { method: 'POST', body: fd })
                .then(r => r.json())
                .then(d => {
                    if (d.success) {
                        Swal.fire('Success', 'Prescription added to case.', 'success');
                        window.toggleModal('prescriptionModal');
                        fetchMsgs();
                    } else {
                        Swal.fire('Error', d.message, 'error');
                    }
                });
        };

        window.submitLabOrder = (e) => {
            e.preventDefault();
            const fd = new FormData(document.getElementById('labForm'));
            clinicalLog("Posting lab order...");
            fetch(`${apiPath}telemedicine_labs.php?action=create`, { method: 'POST', body: fd })
                .then(r => r.json())
                .then(d => {
                    if (d.success) {
                        Swal.fire('Success', 'Lab order placed and invoiced.', 'success');
                        window.toggleModal('labModal');
                        fetchMsgs();
                    } else {
                        Swal.fire('Error', d.message, 'error');
                    }
                });
        };

        window.handleVitals = (e) => {
            e.preventDefault();
            const fd = new FormData(e.target);
            clinicalLog("Saving clinical vitals...");
            fetch(`${apiPath}telemedicine_vitals.php`, { method: 'POST', body: fd })
                .then(r => r.json())
                .then(d => {
                    if (d.success) {
                        Swal.fire('Vitals Saved', 'Patient vitals have been logged to the case.', 'success');
                        window.toggleModal('vitalsModal');
                        fetchMsgs();
                    } else {
                        Swal.fire('Error', d.message, 'error');
                    }
                });
        };

        window.testSignaling = () => {
            clinicalLog("TEST: Dispatching Ping...", "text-yellow-500");
            fetch(`${apiPath}telemedicine_chat.php?action=send_signal&case_id=${cId}&type=TEST_PING`)
                .then(r => {
                    if(!r.ok) throw new Error(`HTTP ${r.status}`);
                    return r.json();
                })
                .then(d => {
                    if(d.success) clinicalLog("TEST: Signal Sent to Server", "text-emerald-500");
                    else clinicalLog("TEST: Server Error: " + d.message, "text-rose-500");
                })
                .catch(e => {
                    clinicalLog("TEST: Error: " + e.message, "text-rose-500");
                    console.error(e);
                });
        };

        window.initNativeCall = async (isInitiator = true) => {
            clinicalLog(`INIT_CALL: ${isInitiator?'Initiator':'Receiver'}`);
            if (ringtone) ringtone.pause();
            try {
                if (!localStream) localStream = await navigator.mediaDevices.getUserMedia({ audio: true });
                if (pc) pc.close(); pc = new RTCPeerConnection(rtcConfig);
                localStream.getTracks().forEach(t => pc.addTrack(t, localStream));
                pc.ontrack = e => { clinicalLog("AUDIO_LINKED", "text-emerald-400"); const aud = new Audio(); aud.srcObject = e.streams[0]; aud.play(); document.getElementById('activeCallBar').classList.remove('hidden'); Swal.close(); };
                pc.onicecandidate = e => { if(e.candidate) sendRTCData('ICE', e.candidate); };
                if (isInitiator) { const offer = await pc.createOffer(); await pc.setLocalDescription(offer); sendRTCData('OFFER', offer); }
            } catch (e) { alert("Mic Access Denied"); }
        };

        window.handleRTCMessage = async (signal) => {
            const data = signal.payload ? JSON.parse(signal.payload) : null;
            clinicalLog(`SIG_IN: ${signal.signal_type}`);
            if (signal.signal_type === 'WEBRTC_READY') window.initNativeCall(true);
            else if (signal.signal_type === 'WEBRTC_OFFER') {
                await window.initNativeCall(false); await pc.setRemoteDescription(new RTCSessionDescription(data));
                const ans = await pc.createAnswer(); await pc.setLocalDescription(ans); sendRTCData('ANSWER', ans);
            } else if (signal.signal_type === 'WEBRTC_ANSWER') await pc.setRemoteDescription(new RTCSessionDescription(data));
            else if (signal.signal_type === 'WEBRTC_ICE') pc.addIceCandidate(new RTCIceCandidate(data)).catch(()=>{});
            else if (signal.signal_type === 'WEBRTC_STOP') window.endNativeCall(false);
        };

        function sendRTCData(type, data) {
            const fd = new FormData(); if(data) fd.append('payload', JSON.stringify(data));
            fetch(`${apiPath}telemedicine_chat.php?action=send_signal&case_id=${cId}&type=WEBRTC_${type}`, { method: 'POST', body: fd });
        }

        window.endNativeCall = (notify = true) => {
            if (notify) sendRTCData('STOP', null);
            if (pc) { pc.close(); pc = null; }
            if (localStream) { localStream.getTracks().forEach(t => t.stop()); localStream = null; }
            document.getElementById('activeCallBar').classList.add('hidden');
            if (ringtone) ringtone.pause();
            Swal.close();
        };

        window.toggleHuddle = async (isInitiator = true, audioOnly = false) => {
            if (audioOnly) {
                if (isInitiator) {
                    fetch(`${apiPath}telemedicine_chat.php?action=send_signal&case_id=${cId}&type=AUDIO_START`);
                    Swal.fire({ 
                        title: 'Calling...', 
                        text: 'Waiting for colleague to join',
                        icon: 'info', 
                        showCancelButton: true, 
                        cancelButtonText: 'End Call',
                        showConfirmButton: false,
                        allowOutsideClick: false
                    }).then(r => { if(r.dismiss === Swal.DismissReason.cancel) window.endNativeCall(); });
                } else {
                    if (!localStream) localStream = await navigator.mediaDevices.getUserMedia({ audio: true });
                    if (handshakeInterval) clearInterval(handshakeInterval);
                    handshakeInterval = setInterval(() => { if (pc && pc.remoteDescription) clearInterval(handshakeInterval); else sendRTCData('READY', null); }, 3000);
                    sendRTCData('READY', null);
                    if (ringtone) ringtone.pause();
                }
            } else {
                const room = `HopeHaven-Huddle-Case-${cId}`;
                window.open(`https://meet.ffmuc.net/${room}`, 'Huddle', 'width=450,height=450');
                if (isInitiator) fetch(`${apiPath}telemedicine_cases.php?action=send_signal&case_id=${cId}&type=HUDDLE_START`);
            }
        };

        window.HospitalSync.subscribe('telemedicine_chat', (signal) => {
            const hb = document.getElementById('syncHeartbeat');
            if(hb) { hb.classList.add('bg-emerald-500'); setTimeout(() => hb.classList.remove('bg-emerald-500'), 500); }
            
            clinicalLog(`RECV: ${signal.signal_type} from ${signal.sender_name || 'User ' + signal.sender_id}`, 'text-cyan-400');

            if (signal.data_id == cId) {
                if (signal.signal_type.startsWith('WEBRTC_') && signal.sender_id != dId) window.handleRTCMessage(signal);
                else if ((signal.signal_type === 'AUDIO_START' || signal.signal_type === 'HUDDLE_START') && signal.sender_id != dId) {
                    ringtone.play().catch(e => clinicalLog("Ringtone blocked - waiting for interaction"));
                    Swal.fire({ 
                        title: signal.signal_type === 'AUDIO_START' ? 'Incoming Call' : 'Incoming Huddle', 
                        text: `${signal.sender_name || 'A colleague'} is calling you for Case #${cId}.`,
                        icon: 'info', 
                        showCancelButton: true, 
                        confirmButtonText: 'Accept & Join',
                        cancelButtonText: 'Decline',
                        confirmButtonColor: '#10b981',
                        allowOutsideClick: false
                    }).then(r => { 
                        if(r.isConfirmed) {
                            window.toggleHuddle(false, signal.signal_type === 'AUDIO_START'); 
                        } else {
                            ringtone.pause();
                        }
                    });
                }
                fetchMsgs();
                fetchBoardMembers();
            }
        });

        function fetchMsgs() {
            fetch(`${apiPath}telemedicine_chat.php?action=get&case_id=${cId}&last_id=${lastId}`).then(r=>r.json()).then(d => {
                if(d.success) {
                    const w = document.getElementById('chatWindow');
                    d.messages.forEach(m => { if(!document.getElementById(`msg-${m.id}`)) addMsg(m); if(m.id > lastId) lastId = m.id; });
                    w.scrollTop = w.scrollHeight;
                }
            });
        }
        function addMsg(m) {
            const w = document.getElementById('chatWindow'), isS = Number(m.doctor_id) === dId, div = document.createElement('div');
            div.id = `msg-${m.id}`; div.className = `flex flex-col ${isS?'items-end':'items-start'} gap-1`;
            
            let content = `<div class="p-4 ${isS?'bg-indigo-600 text-white rounded-t-2xl rounded-bl-2xl':'bg-slate-800 text-slate-200 rounded-t-2xl rounded-br-2xl'} text-sm shadow-lg">`;
            
            if (m.file_path) {
                const fullPath = m.file_path.startsWith('http') ? m.file_path : `${window.APP_BASE_URL}/${m.file_path}`;
                if (m.file_type === 'image') {
                    content += `<a href="${fullPath}" target="_blank"><img src="${fullPath}" class="max-w-xs rounded-lg mb-2 hover:opacity-90 transition-opacity"></a>`;
                } else if (m.file_type === 'audio') {
                    content += `<audio controls class="max-w-xs mb-2 h-8"><source src="${fullPath}" type="audio/mpeg"></audio>`;
                } else {
                    content += `<a href="${fullPath}" target="_blank" class="flex items-center gap-2 p-2 bg-black/20 rounded-lg mb-2 hover:bg-black/30 transition-all">
                        <i data-lucide="file" class="w-4 h-4"></i>
                        <span class="text-xs font-bold uppercase truncate max-w-[150px]">View Attachment</span>
                    </a>`;
                }
            }
            
            content += `<div>${m.message || ''}</div>`;
            content += `<div class="text-[8px] mt-1 opacity-50 font-bold uppercase">${m.name || 'Doctor'} • ${new Date(m.created_at).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})}</div>`;
            content += `</div>`;
            
            div.innerHTML = content;
            w.appendChild(div);
            lucide.createIcons();
        }
        window.handleSendMessage = () => {
            const i = document.getElementById('msgInput');
            if (!i.value.trim()) return;
            
            const fd = new FormData(); 
            fd.append('case_id', cId); 
            fd.append('message', i.value);
            
            fetch(`${apiPath}telemedicine_chat.php?action=send`, {method:'POST', body:fd})
                .then(r => r.json())
                .then(d => {
                    if(d.success) {
                        i.value = '';
                        i.style.height = '56px';
                        fetchMsgs();
                    }
                });
        };

        // Support Enter to Send
        document.getElementById('msgInput').addEventListener('keydown', (e) => {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                window.handleSendMessage();
            }
        });

        window.previewFile = (input) => {
            if (!input.files || !input.files[0]) return;
            const file = input.files[0];
            Swal.fire({
                title: 'Upload File?',
                text: `Send "${file.name}" to the board?`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Upload',
                confirmButtonColor: '#0891b2'
            }).then(r => {
                if (r.isConfirmed) {
                    const fd = new FormData();
                    fd.append('case_id', cId);
                    fd.append('file', file);
                    fd.append('message', `[Attachment: ${file.name}]`);
                    
                    clinicalLog("Uploading file...");
                    fetch(`${apiPath}telemedicine_chat.php?action=send`, {method:'POST', body:fd})
                        .then(r => r.json())
                        .then(d => {
                            if(d.success) fetchMsgs();
                            else Swal.fire('Error', d.message, 'error');
                        });
                }
            });
        };

        let mediaRecorder, audioChunks = [], recordingStartTime;
        window.handleVoiceClick = async () => {
            const btn = document.getElementById('voiceBtn');
            const msgInput = document.getElementById('msgInput');
            
            if (mediaRecorder && mediaRecorder.state === 'recording') {
                clinicalLog("Stopping recording...");
                mediaRecorder.stop();
                btn.classList.remove('recording-active');
                msgInput.placeholder = "Contribute to clinical assessment...";
                return;
            }

            try {
                clinicalLog("Requesting microphone access...");
                const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
                
                // Determine best supported mime type
                const mimeType = MediaRecorder.isTypeSupported('audio/webm') ? 'audio/webm' : 'audio/ogg';
                mediaRecorder = new MediaRecorder(stream, { mimeType });
                audioChunks = [];
                recordingStartTime = Date.now();

                mediaRecorder.ondataavailable = (e) => {
                    if (e.data.size > 0) audioChunks.push(e.data);
                };

                mediaRecorder.onstop = () => {
                    const duration = Math.round((Date.now() - recordingStartTime) / 1000);
                    const audioBlob = new Blob(audioChunks, { type: mimeType });
                    const extension = mimeType.split('/')[1].split(';')[0];
                    const file = new File([audioBlob], `voice_${Date.now()}.${extension}`, { type: mimeType });
                    
                    stream.getTracks().forEach(track => track.stop()); // Release mic

                    Swal.fire({
                        title: 'Send Voice Note?',
                        text: `Duration: ${duration}s`,
                        icon: 'info',
                        showCancelButton: true,
                        confirmButtonText: 'Send Recording',
                        confirmButtonColor: '#10b981'
                    }).then(r => {
                        if (r.isConfirmed) {
                            const fd = new FormData();
                            fd.append('case_id', cId);
                            fd.append('file', file);
                            fd.append('message', '[Voice Message]');
                            fd.append('is_voice', '1');
                            fd.append('duration', duration);
                            
                            clinicalLog("Uploading voice note...");
                            fetch(`${apiPath}telemedicine_chat.php?action=send`, {method:'POST', body:fd})
                                .then(res => res.json())
                                .then(data => {
                                    if(data.success) {
                                        clinicalLog("Voice note sent!", "text-emerald-500");
                                        fetchMsgs();
                                    } else {
                                        Swal.fire('Error', data.message, 'error');
                                    }
                                });
                        }
                    });
                };

                mediaRecorder.start();
                btn.classList.add('recording-active');
                msgInput.placeholder = "🔴 Recording clinical notes... Click mic again to stop.";
                clinicalLog("Recording started", "text-rose-400");
                
            } catch (e) {
                clinicalLog("Recording failed: " + e.message, "text-rose-500");
                Swal.fire('Mic Access Error', 'Please enable microphone access to record voice notes.', 'error');
            }
        };

        function fetchBoardMembers() {
            fetch(`${apiPath}telemedicine_cases.php?action=get_case_members&case_id=${cId}`).then(r=>r.json()).then(d => {
                if(d.success) {
                    const list = document.getElementById('medicalBoardList');
                    list.innerHTML = d.members.map(m => `
                        <div class="flex items-center gap-3 p-3 bg-slate-900/50 rounded-xl border border-slate-800">
                            <div class="w-8 h-8 bg-slate-800 rounded-lg flex items-center justify-center text-[10px] font-black text-slate-500 uppercase">${m.name.charAt(0)}</div>
                            <div class="flex-1 min-w-0">
                                <p class="text-[10px] font-black text-white uppercase truncate">${m.name}</p>
                                <p class="text-[8px] font-bold text-slate-500 uppercase tracking-tighter truncate">${m.specialty || 'General Specialist'} • ${m.role}</p>
                            </div>
                        </div>
                    `).join('');
                }
            });
        }

        window.openInviteModal = () => {
            window.toggleModal('inviteModal');
            fetch(`${apiPath}telemedicine_cases.php?action=get_specialists`).then(r=>r.json()).then(d => {
                if(d.success) {
                    const list = document.getElementById('specialistList');
                    list.innerHTML = d.specialists.map(s => `
                        <div class="flex items-center justify-between p-4 bg-slate-800/50 rounded-2xl border border-slate-700/50 hover:border-emerald-500 transition-all">
                            <div>
                                <p class="text-xs font-black text-white uppercase">${s.name}</p>
                                <p class="text-[9px] font-bold text-slate-500 uppercase tracking-widest">${s.specialty || 'General Specialist'}</p>
                            </div>
                            <button onclick="window.inviteSpecialist(${s.id}, '${s.name}')" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-500 text-white rounded-xl text-[9px] font-black uppercase tracking-widest transition-all">Invite</button>
                        </div>
                    `).join('');
                }
            });
        };

        window.inviteSpecialist = (sId, sName) => {
            const fd = new FormData(); fd.append('case_id', cId); fd.append('specialist_id', sId);
            fetch(`${apiPath}telemedicine_cases.php?action=invite_specialist`, { method: 'POST', body: fd }).then(r=>r.json()).then(d => {
                if(d.success) {
                    Swal.fire({ title: 'Invited!', text: `Dr. ${sName} has been invited to the case.`, icon: 'success', toast: true, position: 'top-end', showConfirmButton: false, timer: 3000 });
                    window.toggleModal('inviteModal');
                    fetchBoardMembers();
                } else {
                    Swal.fire('Error', d.message, 'error');
                }
            });
        };

        window.copyInviteLink = () => {
            const url = window.location.href;
            navigator.clipboard.writeText(url).then(() => {
                Swal.fire({ title: 'Link Copied!', text: 'Invitation link copied to clipboard. Send it to a colleague.', icon: 'success', toast: true, position: 'top-end', showConfirmButton: false, timer: 3000 });
            });
        };

        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.get('autojoin') === 'true' && urlParams.get('mode') === 'audio') {
            setTimeout(() => { Swal.fire({ title: 'Connect Audio?', icon: 'success', confirmButtonText: 'Connect' }).then(r => { if(r.isConfirmed) window.toggleHuddle(false, true); }); }, 1000);
        }

        fetchMsgs(); fetchBoardMembers(); lucide.createIcons(); 
        window.HospitalSync.setCurrentCase(cId);
        window.HospitalSync.start();
    </script>
</body>
</html>