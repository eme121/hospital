<?php
session_start();
require_once 'includes/db_connect.php';

if (!isset($_SESSION['doctor_id']) && !isset($_SESSION['patient_id'])) {
    header('Location: index.php');
    exit;
}

$case_id = intval($_GET['case_id'] ?? 0);
if ($case_id <= 0) exit('Invalid Case');

// Fetch Case and Doctor/Patient Info
$case_stmt = $conn->prepare("SELECT tc.*, td.name as doctor_name, p.full_name as patient_name 
                            FROM telemedicine_cases tc 
                            JOIN telemedicine_doctors td ON tc.created_by = td.id 
                            JOIN patients p ON tc.patient_id = p.id 
                            WHERE tc.id = ?");
$case_stmt->bind_param("i", $case_id);
$case_stmt->execute();
$case = $case_stmt->get_result()->fetch_assoc();

if (!$case) exit('Case not found');

$is_doctor = isset($_SESSION['doctor_id']);
$user_id = $is_doctor ? $_SESSION['doctor_id'] : $_SESSION['patient_id'];
$user_type = $is_doctor ? 'doctor' : 'patient';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Private Consultation | Hope Haven</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; background: #f0f2f5; }
        .message-bubble { max-width: 80%; padding: 1rem; border-radius: 1.5rem; position: relative; }
        .sent { background: #2563eb; color: white; border-bottom-right-radius: 0.25rem; }
        .received { background: white; color: #1e293b; border-bottom-left-radius: 0.25rem; }
        #privateChatWindow { scroll-behavior: smooth; }
        .menu-dropdown { 
            display: none; position: absolute; right: 0.5rem; top: 2.5rem; 
            background: white; border: 1px solid #e2e8f0; border-radius: 0.75rem; 
            box-shadow: 0 10px 15px -3px rgb(0 0 0 / 0.1); z-index: 50; min-width: 140px;
        }
        .menu-dropdown.show { display: block; }
        .menu-item { 
            padding: 0.75rem 1rem; font-size: 10px; font-weight: 800; text-transform: uppercase; 
            letter-spacing: 0.05em; color: #64748b; cursor: pointer; transition: all 0.2s;
            border-bottom: 1px solid #f8fafc;
        }
        .menu-item:last-child { border-bottom: none; }
        .menu-item:hover { background: #f8fafc; color: #2563eb; }
        .menu-item.delete { color: #ef4444; }
        .menu-item.delete:hover { background: #fef2f2; }
        
        /* Modern Voice Note UI */
        .voice-note {
            display: flex; align-items: center; gap: 12px; padding: 12px 16px;
            background: rgba(255,255,255,0.1); border-radius: 1.25rem; min-width: 220px;
        }
        .received .voice-note { background: #f1f5f9; }
        .play-btn {
            width: 36px; height: 36px; border-radius: 50%; display: flex; align-items: center; justify-content: center;
            background: white; color: #2563eb; transition: all 0.2s; flex-shrink: 0;
        }
        .received .play-btn { background: #2563eb; color: white; }
        .waveform { height: 3px; flex: 1; background: rgba(255,255,255,0.3); border-radius: 2px; position: relative; overflow: hidden; }
        .received .waveform { background: #cbd5e1; }
        .waveform-progress { height: 100%; width: 0%; background: white; transition: width 0.1s linear; }
        .received .waveform-progress { background: #2563eb; }
    </style>
</head>
<body class="h-screen flex flex-col">
    <input type="file" id="replaceInput" class="hidden" accept="audio/*">
    <header class="bg-white border-b border-slate-100 p-6 flex justify-between items-center shadow-sm shrink-0">
        <div class="flex items-center gap-4">
            <a href="<?php echo $is_doctor ? 'telemedicine_dashboard.php' : 'telemedicine_dashboard_patient.php'; ?>" class="text-slate-400 hover:text-blue-600 transition-colors">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
            </a>
            <div>
                <h1 class="text-lg font-black text-slate-900 leading-tight">Private Consultation</h1>
                <p class="text-[10px] font-bold text-blue-600 uppercase tracking-widest">
                    <?php echo $is_doctor ? "Patient: " . $case['patient_name'] : "Doctor: " . $case['doctor_name']; ?>
                </p>
            </div>
        </div>
        <div class="flex items-center gap-2 md:gap-4">
            <button onclick="initNativeAudioCall(true)" class="flex items-center gap-2 px-3 md:px-5 py-2.5 bg-emerald-600 text-white rounded-xl text-[10px] font-black uppercase tracking-widest shadow-lg shadow-emerald-100 hover:bg-emerald-700 transition-all">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path></svg>
                <span class="hidden md:inline">Audio Call</span>
            </button>
            <button onclick="startVideoCall()" class="flex items-center gap-2 px-3 md:px-5 py-2.5 bg-indigo-600 text-white rounded-xl text-[10px] font-black uppercase tracking-widest shadow-lg shadow-indigo-100 hover:bg-indigo-700 transition-all">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"></path></svg>
                <span class="hidden md:inline">Video Call</span>
            </button>
            <div class="hidden md:flex items-center gap-2 border-l border-slate-100 pl-4">
                <span class="w-2 h-2 bg-emerald-500 rounded-full animate-pulse"></span>
                <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Secure Channel</span>
            </div>
        </div>
    </header>

    <!-- Video Call Overlay -->
    <div id="videoOverlay" class="fixed inset-0 bg-slate-900 z-[100] hidden flex flex-col">
        <!-- Remote View (Full Screen) -->
        <div class="relative flex-1 bg-black overflow-hidden flex items-center justify-center">
            <div id="remoteVideoPlaceholder" class="text-center">
                <div class="w-24 h-24 bg-slate-800 rounded-full flex items-center justify-center mx-auto mb-4 text-slate-600">
                    <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                </div>
                <p class="text-slate-400 font-black text-xs uppercase tracking-[0.3em]">Connecting to <?php echo $is_doctor ? $case['patient_name'] : $case['doctor_name']; ?>...</p>
            </div>
            <video id="remoteVideo" class="w-full h-full object-cover hidden" autoplay playsinline></video>

            <!-- Local View (PIP) -->
            <div class="absolute bottom-10 right-10 w-48 h-64 bg-slate-800 rounded-3xl border-4 border-slate-700 shadow-2xl overflow-hidden z-20">
                <video id="localVideo" class="w-full h-full object-cover bg-slate-900" autoplay playsinline muted></video>
                <div class="absolute inset-0 bg-gradient-to-t from-black/40 to-transparent pointer-events-none"></div>
                <p class="absolute bottom-3 left-4 text-[8px] font-black text-white uppercase tracking-widest">You</p>
            </div>

            <!-- Call Info -->
            <div class="absolute top-10 left-10 z-20">
                <div class="bg-black/20 backdrop-blur-md px-6 py-3 rounded-2xl border border-white/10">
                    <h3 class="text-white font-black text-sm"><?php echo $is_doctor ? $case['patient_name'] : $case['doctor_name']; ?></h3>
                    <p class="text-emerald-400 text-[9px] font-black uppercase tracking-widest flex items-center gap-2">
                        <span class="w-1.5 h-1.5 bg-emerald-500 rounded-full animate-pulse"></span> Encrypted Call
                    </p>
                </div>
            </div>
        </div>

        <!-- Call Controls -->
        <div class="h-32 bg-slate-900 border-t border-white/5 flex items-center justify-center gap-6 shrink-0">
            <button onclick="toggleMic()" id="micBtn" class="w-14 h-14 bg-slate-800 text-white rounded-2xl flex items-center justify-center hover:bg-slate-700 transition-all border border-white/5">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z"></path></svg>
            </button>
            <button onclick="endCall()" class="w-20 h-20 bg-rose-600 text-white rounded-3xl flex items-center justify-center hover:bg-rose-700 transition-all shadow-2xl shadow-rose-900/40">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M16 8l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2M5 3a2 2 0 00-2 2v1c0 8.284 6.716 15 15 15h1a2 2 0 002-2v-3.28a1 1 0 00-.684-.948l-4.493-1.498a1 1 0 00-1.209.688l-1.139 3.418a17.085 17.085 0 01-8.192-8.192l3.418-1.139a1 1 0 00.688-1.209L7.146 6.14a1 1 0 00-.948-.684H5z"></path></svg>
            </button>
            <button onclick="toggleCamera()" id="camBtn" class="w-14 h-14 bg-slate-800 text-white rounded-2xl flex items-center justify-center hover:bg-slate-700 transition-all border border-white/5">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"></path></svg>
            </button>
        </div>
    </div>

    <div id="privateChatWindow" class="flex-1 overflow-y-auto p-6 flex flex-col gap-4">
        <!-- Messages loaded here -->
    </div>

    <footer class="bg-white p-4 border-t border-slate-100 shrink-0">
        <div class="max-w-4xl mx-auto">
            <form id="privateChatForm" class="flex items-center gap-2">
                <textarea id="privateMsgInput" placeholder="Type a message..." class="flex-1 bg-slate-50 border-0 rounded-2xl px-6 py-3 outline-none focus:ring-2 focus:ring-blue-500 transition-all resize-none" rows="1"></textarea>
                
                <input type="file" id="fileInput" class="hidden" onchange="window.previewFile(this)">
                <button type="button" onclick="document.getElementById('fileInput').click()" class="p-3 text-slate-400 hover:text-blue-600 transition-colors" title="Attach File">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"></path></svg>
                </button>
                
                <button type="button" id="voiceBtn" onclick="window.handleVoiceClick()" class="p-3 text-slate-400 hover:text-red-600 transition-colors" title="Record Voice">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z"></path></svg>
                </button>

                <button type="submit" class="bg-blue-600 text-white p-4 rounded-2xl font-black shadow-lg shadow-blue-200 hover:bg-blue-700 transition-all">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path></svg>
                </button>
            </form>
            
            <div id="recordingStatus" class="hidden mt-3 text-[10px] font-black text-rose-600 uppercase tracking-[0.2em] animate-pulse flex items-center justify-center gap-2">
                <span class="w-1.5 h-1.5 bg-rose-600 rounded-full"></span>
                Recording: <span id="timer">0:00</span>
                <button onclick="window.stopRecording()" class="ml-4 underline hover:text-rose-800 transition-colors">Stop Recording</button>
            </div>
            
            <div id="filePreview" class="hidden mt-3 p-3 bg-blue-50 border border-blue-100 rounded-xl flex items-center justify-between animate-fade-in">
                <div class="flex items-center gap-3">
                    <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"></path></svg>
                    <span id="fileName" class="text-[10px] font-black text-blue-700 uppercase tracking-widest truncate max-w-[200px]"></span>
                </div>
                <button onclick="window.clearFile()" class="text-rose-500 hover:text-rose-700 transition-colors font-black text-lg">&times;</button>
            </div>
        </div>
    </footer>

    <div id="activeCallBar" class="hidden fixed top-0 left-0 right-0 bg-emerald-600 text-white py-3 px-6 flex justify-between items-center z-[2000] shadow-lg animate-in slide-in-from-top duration-500">
        <div class="flex items-center gap-4">
            <span class="w-2.5 h-2.5 bg-white rounded-full animate-ping"></span>
            <span class="text-[10px] font-black uppercase tracking-[0.2em]">Secure Audio Connection Active</span>
            <span id="callPartner" class="text-[10px] font-bold opacity-70 italic ml-2"></span>
        </div>
        <button onclick="window.endNativeCall()" class="bg-white/20 hover:bg-white/30 px-5 py-2 rounded-xl text-[10px] font-black uppercase transition-all">End Call</button>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        window.APP_BASE_URL = '<?php echo BASE_URL; ?>';
    </script>
    <script src="<?php echo BASE_URL; ?>/assets/js/sync_engine.js"></script>
    <script>
        const caseId = <?php echo $case_id; ?>;
        const userId = <?php echo $user_id; ?>;
        const userType = '<?php echo $user_type; ?>';
        const userName = '<?php echo $is_doctor ? addslashes($case['doctor_name']) : addslashes($case['patient_name']); ?>';
        let lastId = 0;
        let mRec, chunks = [], tInt, sTime;
        let replacingMsgId = null;
        let recordedBlob = null;
        let ringtone = new Audio('https://assets.mixkit.co/active_storage/sfx/1233/1233-preview.mp3');
        ringtone.loop = true;

        // --- NATIVE WEBRTC AUDIO SYSTEM ---
        let pc = null;
        let localStream = null;
        const rtcConfig = { iceServers: [{ urls: 'stun:stun.l.google.com:19302' }] };

        window.initNativeAudioCall = async (isInitiator = true) => {
            if (ringtone) ringtone.pause();
            
            try {
                localStream = await navigator.mediaDevices.getUserMedia({ audio: true });
                pc = new RTCPeerConnection(rtcConfig);
                
                localStream.getTracks().forEach(track => pc.addTrack(track, localStream));

                pc.ontrack = (event) => {
                    const remoteAudio = new Audio();
                    remoteAudio.srcObject = event.streams[0];
                    remoteAudio.play();
                    document.getElementById('activeCallBar').classList.remove('hidden');
                };

                pc.onicecandidate = (event) => {
                    if (event.candidate) {
                        sendRTCData('ICE', event.candidate);
                    }
                };

                if (isInitiator) {
                    const offer = await pc.createOffer();
                    await pc.setLocalDescription(offer);
                    sendRTCData('OFFER', offer);
                    
                    Swal.fire({
                        title: 'Calling...',
                        text: 'Waiting for partner to answer the secure line.',
                        icon: 'info',
                        showCancelButton: true,
                        confirmButtonText: 'Cancel Call',
                        confirmButtonColor: '#ef4444',
                        allowOutsideClick: false,
                        didOpen: () => Swal.showLoading()
                    }).then((r) => { if(r.isConfirmed) window.endNativeCall(); });
                }
            } catch (e) {
                console.error("RTC Error:", e);
                alert("Microphone access is required for audio calls.");
            }
        };

        window.handleRTCMessage = async (signal) => {
            const data = JSON.parse(signal.payload);
            const senderName = signal.sender_name || 'Specialist';
            
            if (signal.signal_type === 'WEBRTC_OFFER') {
                if (pc) return; 
                if (ringtone) ringtone.play().catch(e => console.log("Audio blocked"));
                
                document.getElementById('callPartner').textContent = `With ${senderName}`;

                Swal.fire({
                    title: 'Incoming Call',
                    text: `${senderName} is calling you on a private secure line.`,
                    icon: 'info',
                    showCancelButton: true,
                    confirmButtonText: 'Answer Call',
                    cancelButtonText: 'Decline',
                    confirmButtonColor: '#10b981',
                    allowOutsideClick: false,
                    backdrop: `rgba(0,0,0,0.8)`
                }).then(async (result) => {
                    if (ringtone) ringtone.pause();
                    if (result.isConfirmed) {
                        await window.initNativeAudioCall(false);
                        await pc.setRemoteDescription(new RTCSessionDescription(data));
                        const answer = await pc.createAnswer();
                        await pc.setLocalDescription(answer);
                        sendRTCData('ANSWER', answer);
                    } else {
                        sendRTCData('DECLINE', null);
                    }
                });
            } else if (signal.signal_type === 'WEBRTC_ANSWER' && pc) {
                Swal.close();
                await pc.setRemoteDescription(new RTCSessionDescription(data));
            } else if (signal.signal_type === 'WEBRTC_ICE' && pc) {
                await pc.addIceCandidate(new RTCIceCandidate(data));
            } else if (signal.signal_type === 'WEBRTC_STOP' || signal.signal_type === 'WEBRTC_DECLINE') {
                window.endNativeCall(false);
                if (signal.signal_type === 'WEBRTC_DECLINE') Swal.fire('Call Declined', '', 'error');
            }
        };

        function sendRTCData(type, data) {
            const fd = new FormData();
            fd.append('payload', JSON.stringify(data));
            fetch(`api/telemedicine_private_chat_handler.php?action=send_signal&case_id=${caseId}&type=WEBRTC_${type}`, {
                method: 'POST',
                body: fd
            });
        }

        window.endNativeCall = (notify = true) => {
            if (notify) sendRTCData('STOP', null);
            if (pc) { pc.close(); pc = null; }
            if (localStream) { localStream.getTracks().forEach(t => t.stop()); localStream = null; }
            document.getElementById('activeCallBar').classList.add('hidden');
            Swal.close();
            if (ringtone) ringtone.pause();
        };

        // Real-Time Sync Subscription
        if (window.HospitalSync) {
            window.HospitalSync.subscribe('telemedicine_chat', (signal) => {
                if (signal.data_id == caseId) {
                    // Native WebRTC Handling
                    if (signal.signal_type.startsWith('WEBRTC_') && (signal.sender_id != userId || signal.sender_type != userType)) {
                        window.handleRTCMessage(signal);
                        return;
                    }

                    if (signal.action === 'CALL_START') {
                        if (videoOverlay.classList.contains('hidden')) startVideoCall();
                    } else if (signal.action === 'CALL_END') {
                        endCall();
                    } else {
                        fetchPrivateMessages();
                    }
                }
            });
        }

        window.previewFile = (i) => { 
            if(i.files[0]){ 
                recordedBlob = null;
                document.getElementById('fileName').textContent = i.files[0].name; 
                document.getElementById('filePreview').classList.remove('hidden'); 
            } 
        };
        window.clearFile = () => { 
            document.getElementById('fileInput').value = ''; 
            recordedBlob = null;
            document.getElementById('filePreview').classList.add('hidden'); 
        };

        function fetchPrivateMessages() {
            fetch(`api/telemedicine_private_chat_handler.php?action=get&case_id=${caseId}&last_id=0`)
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        const win = document.getElementById('privateChatWindow');
                        const isAtBottom = win.scrollHeight - win.scrollTop <= win.clientHeight + 100;
                        
                        data.messages.forEach(msg => {
                            const existing = document.getElementById(`msg-${msg.id}`);
                            if(existing) {
                                updateMsgUI(msg, existing);
                            } else {
                                addMsg(msg);
                            }
                            if(msg.id > lastId) lastId = msg.id;
                        });

                        if (isAtBottom && data.messages.length > 0) {
                            win.scrollTop = win.scrollHeight;
                        }
                    }
                });
        }

        function updateMsgUI(msg, div) {
            const isMe = msg.sender_type === userType && Number(msg.sender_id) === userId;
            const bubble = div.querySelector('.message-bubble');
            
            if(msg.is_deleted) {
                if(!bubble.querySelector('.deleted-placeholder')) {
                    bubble.innerHTML = `<p class="deleted-placeholder text-xs italic opacity-50">This message was deleted</p>`;
                    bubble.classList.add('opacity-60');
                    const menuBtn = div.querySelector('.menu-btn');
                    if(menuBtn) menuBtn.remove();
                }
                return;
            }

            // Prevent resetting playing audio by checking if content changed
            const lastUpdated = div.getAttribute('data-updated') || '0';
            const currentUpdated = msg.updated_at || msg.created_at;
            if (lastUpdated === currentUpdated && bubble.querySelector('.msg-content').innerHTML !== '') return;
            div.setAttribute('data-updated', currentUpdated);

            const contentDiv = bubble.querySelector('.msg-content');
            if(!contentDiv) return;

            let newContent = '';
            if(msg.file_path) {
                if(msg.file_type === 'audio') {
                    const ts = msg.updated_at ? new Date(msg.updated_at).getTime() : new Date(msg.created_at).getTime();
                    newContent = `
                        <div class="voice-note mb-2" id="vn-${msg.id}">
                            <button class="play-btn" onclick="toggleAudio(${msg.id})">
                                <svg class="w-5 h-5" id="icon-${msg.id}" fill="currentColor" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
                            </button>
                            <div class="waveform">
                                <div class="waveform-progress" id="prog-${msg.id}"></div>
                            </div>
                            <audio id="aud-${msg.id}" ontimeupdate="updateProg(${msg.id})" onended="resetAud(${msg.id})" class="hidden" preload="auto">
                                <source src="${msg.file_path}?t=${ts}" type="audio/webm">
                                <source src="${msg.file_path}?t=${ts}" type="audio/mpeg">
                            </audio>
                            ${msg.duration ? `<span class="text-[8px] font-bold opacity-40 ml-2">${msg.duration}s</span>` : ''}
                        </div>
                    `;
                } else if(msg.file_type === 'image') {
                    const ts = msg.updated_at ? new Date(msg.updated_at).getTime() : new Date(msg.created_at).getTime();
                    newContent = `<img src="${msg.file_path}?t=${ts}" class="rounded-xl max-h-64 mb-2 cursor-pointer hover:opacity-90" onclick="window.open('${msg.file_path}?t=${ts}')">`;
                } else {
                    newContent = `<a href="${msg.file_path}" target="_blank" class="flex items-center gap-2 p-3 ${isMe?'bg-white/10':'bg-blue-50'} rounded-xl text-[10px] font-black uppercase tracking-widest mb-2">📎 Document</a>`;
                }
            }
            if(msg.message) newContent += `<p class="text-sm font-medium leading-relaxed">${msg.message}</p>`;
            
            if(contentDiv.innerHTML !== newContent) {
                contentDiv.innerHTML = newContent;
                if(msg.updated_at) {
                    const status = bubble.querySelector('.msg-status');
                    if(status) status.textContent = ' • Updated';
                }
            }
        }

        window.toggleAudio = (id) => {
            const aud = document.getElementById(`aud-${id}`);
            const icon = document.getElementById(`icon-${id}`);
            
            // WebM Duration Fix: If duration is Infinity, seek to a massive time to force browser to find the end
            if (aud.duration === Infinity || isNaN(aud.duration)) {
                aud.currentTime = 1e101;
                aud.ontimeupdate = function() {
                    this.ontimeupdate = () => window.updateProg(id);
                    this.currentTime = 0;
                    this.play();
                    icon.innerHTML = '<path d="M6 19h4V5H6v14zm8-14v14h4V5h-4z"/>';
                };
                return;
            }

            if(aud.paused) {
                document.querySelectorAll('audio').forEach(a => { if(a!==aud) { a.pause(); a.currentTime=0; } });
                aud.play();
                icon.innerHTML = '<path d="M6 19h4V5H6v14zm8-14v14h4V5h-4z"/>';
            } else {
                aud.pause();
                icon.innerHTML = '<path d="M8 5v14l11-7z"/>';
            }
        };

        window.updateProg = (id) => {
            const aud = document.getElementById(`aud-${id}`);
            const prog = document.getElementById(`prog-${id}`);
            if(aud && prog && isFinite(aud.duration)) prog.style.width = (aud.currentTime / aud.duration * 100) + '%';
        };

        window.resetAud = (id) => {
            const icon = document.getElementById(`icon-${id}`);
            const prog = document.getElementById(`prog-${id}`);
            if(icon) icon.innerHTML = '<path d="M8 5v14l11-7z"/>';
            if(prog) prog.style.width = '0%';
        };

        document.addEventListener('loadeddata', (e) => {
            if (e.target.tagName === 'AUDIO') {
                const aud = e.target;
                if (aud.duration === Infinity) {
                    aud.currentTime = 1e101;
                    aud.addEventListener('timeupdate', function fix() { this.currentTime = 0; this.removeEventListener('timeupdate', fix); }, { once: true });
                }
            }
        }, true);

        function addMsg(msg) {
            const isMe = msg.sender_type === userType && Number(msg.sender_id) === userId;
            const div = document.createElement('div');
            div.id = `msg-${msg.id}`;
            div.className = `flex ${isMe ? 'justify-end' : 'justify-start'} group mb-4`;
            
            let content = '';
            if(msg.is_deleted) {
                content = `<p class="deleted-placeholder text-xs italic opacity-50">This message was deleted</p>`;
            } else {
                content = `<div class="msg-content"></div>`;
            }
            
            const time = new Date(msg.created_at).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
            const updatedTag = msg.updated_at ? ' • Updated' : '';

            div.innerHTML = `
                <div class="relative max-w-[80%]">
                    <div class="message-bubble shadow-sm ${isMe ? 'sent' : 'received'} ${msg.is_deleted ? 'opacity-60' : ''}">
                        ${content}
                        <p class="text-[8px] mt-2 ${isMe ? 'text-white/50' : 'text-slate-400'} font-black uppercase tracking-widest">
                            ${time}<span class="msg-status">${updatedTag}</span>
                        </p>
                        ${isMe && !msg.is_deleted ? `
                            <button onclick="toggleMenu(event, ${msg.id})" class="menu-btn absolute top-2 right-2 p-1 text-white/50 hover:text-white transition-colors z-10">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M12 8c1.1 0 2-.9 2-2s-.9-2-2-2-2 .9-2 2 .9 2 2 2zm0 2c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2zm0 6c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2z"/></svg>
                            </button>
                            <div id="menu-${msg.id}" class="menu-dropdown">
                                ${msg.file_path && msg.file_type === 'audio' ? `<div class="menu-item" onclick="playAudio(${msg.id})">Play Audio</div>` : ''}
                                ${msg.file_path && msg.file_type === 'audio' ? `<div class="menu-item" onclick="prepareReplace(${msg.id})">Replace Audio</div>` : ''}
                                <div class="menu-item delete" onclick="deleteMsg(${msg.id})">Delete Message</div>
                            </div>
                        ` : ''}
                    </div>
                </div>
            `;
            document.getElementById('privateChatWindow').appendChild(div);
            if(!msg.is_deleted) updateMsgUI(msg, div);
        }

        window.playAudio = (id) => {
            const aud = document.getElementById(`aud-${id}`);
            if(aud) window.toggleAudio(id);
        };

        window.toggleMenu = (e, id) => {
            e.stopPropagation();
            document.querySelectorAll('.menu-dropdown').forEach(m => { if(m.id !== `menu-${id}`) m.classList.remove('show'); });
            const menu = document.getElementById(`menu-${id}`);
            if(menu) menu.classList.toggle('show');
        };

        document.addEventListener('click', () => document.querySelectorAll('.menu-dropdown').forEach(m => m.classList.remove('show')));

        window.deleteMsg = (id) => {
            if(!confirm('Are you sure you want to delete this message?')) return;
            const fd = new FormData();
            fd.append('msg_id', id);
            fetch('api/telemedicine_private_chat_handler.php?action=delete', { method: 'POST', body: fd })
                .then(res => res.json())
                .then(data => { if(data.success) fetchPrivateMessages(); else alert(data.message); });
        };

        window.prepareReplace = (id) => {
            replacingMsgId = id;
            document.getElementById('replaceInput').click();
        };

        document.getElementById('replaceInput').onchange = function() {
            if(!this.files[0] || !replacingMsgId) return;
            const fd = new FormData();
            fd.append('msg_id', replacingMsgId);
            fd.append('file', this.files[0]);
            fd.append('is_voice', '1');
            
            fetch('api/telemedicine_private_chat_handler.php?action=replace', { method: 'POST', body: fd })
                .then(res => res.json())
                .then(data => {
                    if(data.success) {
                        fetchPrivateMessages();
                        replacingMsgId = null;
                        this.value = '';
                    } else {
                        alert(data.message);
                    }
                });
        };

        document.getElementById('privateChatForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const input = document.getElementById('privateMsgInput');
            const fileInput = document.getElementById('fileInput');
            const msg = input.value.trim();
            const file = recordedBlob || fileInput.files[0];
            
            if (!msg && !file) return;

            const formData = new FormData();
            formData.append('case_id', caseId);
            formData.append('message', msg);
            if(file) {
                if(recordedBlob) {
                    formData.append('file', recordedBlob, 'voice.webm');
                    formData.append('is_voice', '1');
                    if(recordedDuration) formData.append('duration', recordedDuration);
                } else {
                    formData.append('file', file);
                }
            }

            fetch('api/telemedicine_private_chat_handler.php?action=send', {
                method: 'POST',
                body: formData
            }).then(res => res.json()).then(data => {
                if(data.success) {
                    input.value = '';
                    window.clearFile();
                    fetchPrivateMessages();
                } else {
                    alert(data.message);
                }
            });
        });

        window.handleVoiceClick = () => { if(mRec && mRec.state==='recording') window.stopRecording(); else startRec(); };
        function startRec() {
            if(!navigator.mediaDevices) return alert("Mic blocked.");
            navigator.mediaDevices.getUserMedia({audio:true}).then(s => {
                const options = { mimeType: 'audio/webm;codecs=opus' };
                if (!MediaRecorder.isTypeSupported(options.mimeType)) options.mimeType = 'audio/webm';
                mRec = new MediaRecorder(s, options); chunks = [];
                mRec.ondataavailable = e => { if(e.data.size>0) chunks.push(e.data); };
                mRec.onstop = () => {
                    recordedBlob = new Blob(chunks, {type: options.mimeType});
                    const aud = new Audio(URL.createObjectURL(recordedBlob));
                    aud.onloadedmetadata = () => { recordedDuration = Math.round(aud.duration); document.getElementById('fileName').textContent = `Voice Recording (${recordedDuration}s)`; };
                    document.getElementById('filePreview').classList.remove('hidden');
                    s.getTracks().forEach(t=>t.stop());
                };
                mRec.start(); sTime = Date.now();
                document.getElementById('recordingStatus').classList.remove('hidden');
                tInt = setInterval(() => { const sec = Math.floor((Date.now()-sTime)/1000); document.getElementById('timer').textContent = Math.floor(sec/60)+':'+(sec%60).toString().padStart(2,'0'); }, 1000);
                document.getElementById('voiceBtn').classList.add('text-red-600');
            }).catch(()=>alert("Mic access denied."));
        }
        window.stopRecording = () => { if(mRec && mRec.state!=='inactive'){ mRec.stop(); clearInterval(tInt); document.getElementById('recordingStatus').classList.add('hidden'); document.getElementById('voiceBtn').classList.remove('text-red-600'); } };

        // Handle Enter key for textarea
        document.getElementById('privateMsgInput').addEventListener('keydown', e => {
            if(e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                document.getElementById('privateChatForm').dispatchEvent(new Event('submit'));
            }
        });

        // --- Video Call Logic ---
        let localStream = null;
        const videoOverlay = document.getElementById('videoOverlay');
        const localVideo = document.getElementById('localVideo');
        const remoteVideo = document.getElementById('remoteVideo');
        const remotePlaceholder = document.getElementById('remoteVideoPlaceholder');

        window.startVideoCall = async () => {
            try {
                localStream = await navigator.mediaDevices.getUserMedia({ video: true, audio: true });
                localVideo.srcObject = localStream;
                videoOverlay.classList.remove('hidden');
                
                // Signaling: Notify other party (In a real app, this sends a WebRTC offer)
                // For now, we simulate a signal
                fetch(`api/telemedicine_private_chat_handler.php?action=send_signal&case_id=${caseId}&type=call_start`);
                
                // Mock: After 3 seconds, simulate remote person joining
                setTimeout(() => {
                    remotePlaceholder.classList.add('hidden');
                    remoteVideo.classList.remove('hidden');
                    // In real app: remoteVideo.srcObject = remoteStream;
                }, 3000);

            } catch (err) {
                alert("Could not access camera/mic: " + err.message);
            }
        };

        window.endCall = () => {
            if (localStream) {
                localStream.getTracks().forEach(track => track.stop());
                localStream = null;
            }
            videoOverlay.classList.add('hidden');
            remotePlaceholder.classList.remove('hidden');
            remoteVideo.classList.add('hidden');
            fetch(`api/telemedicine_private_chat_handler.php?action=send_signal&case_id=${caseId}&type=call_end`);
        };

        window.toggleMic = () => {
            if (localStream) {
                const audioTrack = localStream.getAudioTracks()[0];
                audioTrack.enabled = !audioTrack.enabled;
                document.getElementById('micBtn').classList.toggle('bg-rose-600', !audioTrack.enabled);
                document.getElementById('micBtn').classList.toggle('bg-slate-800', audioTrack.enabled);
            }
        };

        window.toggleCamera = () => {
            if (localStream) {
                const videoTrack = localStream.getVideoTracks()[0];
                videoTrack.enabled = !videoTrack.enabled;
                document.getElementById('camBtn').classList.toggle('bg-rose-600', !videoTrack.enabled);
                document.getElementById('camBtn').classList.toggle('bg-slate-800', videoTrack.enabled);
            }
        };

        fetchPrivateMessages();
    </script>
</body>
</html>