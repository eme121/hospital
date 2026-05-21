/**
 * Professional Real-Time Sync Engine (Senior Architect implementation)
 * Handles client-side state monitoring and UI delta-refreshes.
 */
class SyncEngine {
    constructor() {
        this.subscriptions = {};
        this.lastTokens = {};
        this.lastSignalId = 0;
        this.pollInterval = 10000; // Increased to 10s to save bandwidth
        this.isRunning = false;
        this.currentCaseId = null;
        this.debug = true;
        this.setupVisibilityHandler();
    }

    setupVisibilityHandler() {
        document.addEventListener('visibilitychange', () => {
            if (document.hidden) {
                if(this.debug) console.log('🌙 [SyncEngine] Tab hidden, pausing pulse.');
                this.pause();
            } else {
                if(this.debug) console.log('☀️ [SyncEngine] Tab visible, resuming pulse.');
                this.resume();
            }
        });
    }

    pause() {
        this.isRunning = false;
    }

    resume() {
        if (!this.isRunning) {
            this.isRunning = true;
            this.pulse();
        }
    }

    setFrequency(ms) {
        this.pollInterval = ms;
    }

    setCurrentCase(caseId) {
        this.currentCaseId = caseId;
    }

    subscribe(module, callback) {
        if (!this.subscriptions[module]) {
            this.subscriptions[module] = [];
        }
        this.subscriptions[module].push(callback);
    }
start() {
    if (this.isRunning) return;
    this.isRunning = true;

    // Simplify API path detection: assume api/ is at the same level as the main pages
    const currentPath = window.location.pathname;
    const appRoot = currentPath.substring(0, currentPath.lastIndexOf('/') + 1);
    this.apiUrl = `${appRoot}api/sync_hub.php`;

    if(this.debug) console.log('📡 [SyncEngine] Targeting API:', this.apiUrl);    // First, get current baseline
    fetch(this.apiUrl)
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                this.lastSignalId = data.newest_signal_id;
                this.lastTokens = data.registry;
                if(this.debug) console.log('📡 [SyncEngine] Initialized baseline at ID:', this.lastSignalId);
            }
        })
        .catch(e => console.error('☁️ [SyncEngine] Init failed:', e))
        .finally(() => {
            this.pulse(); 
        });
}

pulse() {
    if (!this.isRunning) return;

    let url = `${this.apiUrl}?last_signal_id=${this.lastSignalId}`;

    if (this.currentCaseId) {
        url += `&presence_case_id=${this.currentCaseId}`;
    }

    fetch(url)
        .then(r => r.json())
        .then(data => {                // Update Connection UI
                const statusDot = document.querySelector('#syncStatus span');
                const statusText = document.querySelector('#syncStatus span:nth-child(2)');
                if (statusDot) statusDot.className = 'w-2 h-2 rounded-full bg-emerald-500 animate-pulse';
                if (statusText) { statusText.textContent = 'System Linked'; statusText.className = 'text-[10px] font-black text-emerald-500 uppercase'; }

                if (data.success) {
                    // 1. Process Database Registry Changes
                    for (const [module, token] of Object.entries(data.registry)) {
                        if (this.lastTokens[module] !== undefined && this.lastTokens[module] !== token) {
                            this.notify(module, { signal_type: 'UPDATE', is_global: true });
                        }
                        this.lastTokens[module] = token;
                    }

                    // 2. Process Individual Real-Time Signals
                    if (data.signals && data.signals.length > 0) {
                        data.signals.forEach(s => {
                            if(this.debug) console.log(`📩 [SyncEngine] RECEIVED SIGNAL FROM HUB:`, s.module_name, s.signal_type, s.data_id);
                            this.notify(s.module_name, s);
                        });
                    }

                    this.lastSignalId = data.newest_signal_id;
                    
                    if (data.presence) {
                        this.notify('presence', data.presence);
                    }
                }
            })
            .catch(e => {
                console.error('☁️ [SyncEngine] Pulse Error:', e);
                // Update Connection UI for Failure
                const statusDot = document.querySelector('#syncStatus span');
                const statusText = document.querySelector('#syncStatus span:nth-child(2)');
                if (statusDot) statusDot.className = 'w-2 h-2 rounded-full bg-rose-500';
                if (statusText) { statusText.textContent = 'Link Lost'; statusText.className = 'text-[10px] font-black text-rose-500 uppercase'; }
            })
            .finally(() => {
                setTimeout(() => this.pulse(), this.pollInterval);
            });
    }

    notify(module, signal) {
        if (this.subscriptions[module]) {
            this.subscriptions[module].forEach(callback => callback(signal));
        }
    }
}

// Global Singleton Instance
window.HospitalSync = new SyncEngine();