/**
 * Hope Haven Global Telemedicine Signaling (V2 - High Reliability)
 * Handles background alerts and seamless handovers.
 */
(function() {
    if (!window.HospitalSync) return;

    window.HospitalSync.subscribe('telemedicine_chat', (signal) => {
        // 1. Identify ourselves
        const currentUserId = typeof dId !== 'undefined' ? dId : (typeof userId !== 'undefined' ? userId : 0);
        if (signal.sender_id == currentUserId) return;

        // 2. Filter for Call Signals
        if (signal.signal_type === 'HUDDLE_START' || signal.signal_type === 'AUDIO_START' || signal.signal_type === 'WEBRTC_OFFER') {
            
            const urlParams = new URLSearchParams(window.location.search);
            const currentCaseId = urlParams.get('id') || (typeof cId !== 'undefined' ? cId : null);
            
            // 3. Page Context Check
            const isOnWarRoom = window.location.pathname.includes('telemedicine_case.php');
            const isTargetCase = currentCaseId == signal.data_id;

            // If we are already on the target case, let the page's internal UI handle it
            if (isOnWarRoom && isTargetCase) {
                console.log("RTCLOG: Internal page alert should trigger.");
                return;
            }

            // 4. Background Alert (If on Dashboard or another page)
            const audio = new Audio('https://assets.mixkit.co/active_storage/sfx/2358/2358-preview.mp3');
            audio.play().catch(e => console.log("Audio alert blocked by browser"));
            
            const initiator = signal.sender_name || 'Specialist';
            const isNative = signal.signal_type === 'WEBRTC_OFFER' || signal.signal_type === 'AUDIO_START';
            
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: isNative ? 'Incoming Audio Call' : 'Incoming Clinical Huddle',
                    text: `${initiator} is requesting a consultation for Case #${signal.data_id}.`,
                    icon: 'info',
                    showCancelButton: true,
                    confirmButtonText: 'Join Now',
                    cancelButtonText: 'Ignore',
                    confirmButtonColor: '#10b981',
                    backdrop: `rgba(2, 6, 23, 0.9)`,
                    allowOutsideClick: false
                }).then((result) => {
                    if (result.isConfirmed) {
                        window.location.href = `telemedicine_case.php?id=${signal.data_id}&autojoin=true&mode=${isNative?'audio':'video'}`;
                    }
                });
            }
        }
    });
})();