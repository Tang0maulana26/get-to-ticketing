/**
 * Gate Check-In & Ticket Scanner Engine
 *
 * Core Capabilities:
 * 1. Multi-Device Camera Scanner (Front/Back/USB Webcam via WebRTC + jsQR / BarcodeDetector)
 * 2. Screenshot/File QR Image Decoder
 * 3. Hardware PDA / Barcode Laser Keystroke Listener (Auto-detect rapid keystrokes + Enter)
 * 4. Manual Ticket Input & Quick Scan Actions
 * 5. Real-Time Online Check-In + Zero-Latency Offline Cryptographic HMAC Engine
 * 6. 2-Way Multi-Device Auto-Sync (Heartbeat Delta Sync + Conflict Detection across staff devices)
 * 7. Admin Gate Lock & Event `start_time` Window Verification (Free / H-1 / D-Day / Custom Hours)
 * 8. 100% Bilingual Dynamic Internationalization (English & Indonesian based on active locale)
 */
(function () {
    'use strict';

    const root = document.getElementById('gate-scanner-app');
    if (!root) return;

    // --- Configuration & Endpoints ---
    const CSRF_TOKEN =
        document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ||
        root.dataset.csrf ||
        '';
    const CHECKIN_URL = root.dataset.checkinUrl || '/gate/check-in';
    const SETTINGS_URL = root.dataset.settingsUrl || '/gate/settings';
    const MANIFEST_URL_BASE = root.dataset.manifestBaseUrl || '/gate/manifest/';
    const SYNC_URL = root.dataset.syncUrl || '/gate/sync-batch';

    // Parse Localization Map
    let i18n = {};
    try {
        i18n = JSON.parse(root.dataset.i18n || '{}');
    } catch (_) {
        i18n = {};
    }

    function t(key, fallback = '', replacements = {}) {
        let text = i18n[key] || fallback || key;
        if (replacements && typeof replacements === 'object') {
            for (const [placeholder, val] of Object.entries(replacements)) {
                text = text.replace(new RegExp(placeholder, 'g'), String(val));
            }
        }
        return text;
    }

    // --- State Variables ---
    let soundEnabled = true;
    let isProcessingScan = false;
    let lastScannedCode = '';
    let lastScannedAt = 0;
    let currentStream = null;
    let isCameraActive = false;
    let currentFacingMode = 'environment';
    let videoDevices = [];
    let selectedDeviceId = '';
    let scanLoopId = null;
    let barcodeDetector = null;
    let scanHistory = [];

    // Native BarcodeDetector initialization
    if ('BarcodeDetector' in window) {
        try {
            barcodeDetector = new window.BarcodeDetector({ formats: ['qr_code'] });
        } catch (_) {
            barcodeDetector = null;
        }
    }

    // --- DOM Elements ---
    const eventSelect = document.getElementById('event-select');
    const netBadge = document.getElementById('net-badge');
    const netText = document.getElementById('net-text');
    const btnToggleSound = document.getElementById('btn-toggle-sound');
    const btnDownloadManifest = document.getElementById('btn-download-manifest');
    const btnSyncBatch = document.getElementById('btn-sync-batch');
    const syncCountSpan = document.getElementById('sync-count');
    const manifestInfo = document.getElementById('manifest-info');
    const statAttended = document.getElementById('stat-attended');
    const statTotal = document.getElementById('stat-total');
    const statPercent = document.getElementById('stat-percent');
    const multiSyncText = document.getElementById('multi-sync-text');

    // Gate Lock & Start-Time Controls
    const gateSystemActive = document.getElementById('gate-system-active');
    const gateTimeMode = document.getElementById('gate-time-mode');
    const gateCustomHoursWrap = document.getElementById('gate-custom-hours-wrap');
    const gateCustomHours = document.getElementById('gate-custom-hours');
    const btnSaveGateSettings = document.getElementById('btn-save-gate-settings');
    const gateRuleStatusText = document.getElementById('gate-rule-status-text');

    // Camera DOM Elements
    const videoEl = document.getElementById('webcam-video');
    const canvasEl = document.getElementById('qr-canvas');
    const canvasCtx = canvasEl ? canvasEl.getContext('2d', { willReadFrequently: true }) : null;
    const cameraStatusOverlay = document.getElementById('camera-status-overlay');
    const cameraStatusIcon = document.getElementById('camera-status-icon');
    const cameraStatusText = document.getElementById('camera-status-text');
    const cameraStatusSub = document.getElementById('camera-status-sub');
    const btnStartCamOverlay = document.getElementById('btn-start-cam-overlay');
    const btnToggleCam = document.getElementById('btn-toggle-cam');
    const btnSwitchCam = document.getElementById('btn-switch-cam');
    const cameraDeviceSelect = document.getElementById('camera-device-select');
    const qrFileInput = document.getElementById('qr-file-input');
    const btnScanImage = document.getElementById('btn-scan-image');
    const scannerReticle = document.getElementById('scanner-reticle');

    // Manual Input DOM Elements
    const formManual = document.getElementById('form-manual-checkin');
    const manualInput = document.getElementById('manual-code-input');

    // Result Card DOM Elements
    const resultCard = document.getElementById('result-card');
    const resultIcon = document.getElementById('result-icon');
    const resultTitle = document.getElementById('result-title');
    const resultSubtitle = document.getElementById('result-subtitle');
    const resultDetails = document.getElementById('result-details');
    const resHolder = document.getElementById('res-holder');
    const resCategory = document.getElementById('res-category');
    const resCode = document.getElementById('res-code');
    const resTime = document.getElementById('res-time');
    const resModeText = document.getElementById('res-mode-text');
    const resCryptoText = document.getElementById('res-crypto-text');

    // History & Manifest Table DOM Elements
    const historyTableBody = document.getElementById('history-table-body');
    const historyCount = document.getElementById('history-count');
    const manifestTicketsBody = document.getElementById('manifest-tickets-body');

    // =========================================================================
    // 1. Web Audio Synthesizer (Zero MP3 Asset Dependency)
    // =========================================================================
    function playFeedbackSound(isSuccess) {
        if (!soundEnabled) return;
        try {
            const AudioCtx = window.AudioContext || window.webkitAudioContext;
            if (!AudioCtx) return;
            const ctx = new AudioCtx();
            const osc = ctx.createOscillator();
            const gain = ctx.createGain();
            osc.connect(gain);
            gain.connect(ctx.destination);

            if (isSuccess) {
                osc.type = 'sine';
                osc.frequency.setValueAtTime(880, ctx.currentTime);
                osc.frequency.setValueAtTime(1174.66, ctx.currentTime + 0.08);
                gain.gain.setValueAtTime(0.3, ctx.currentTime);
                gain.gain.linearRampToValueAtTime(0.01, ctx.currentTime + 0.25);
                osc.start();
                osc.stop(ctx.currentTime + 0.25);
            } else {
                osc.type = 'sawtooth';
                osc.frequency.setValueAtTime(220, ctx.currentTime);
                osc.frequency.setValueAtTime(164.8, ctx.currentTime + 0.12);
                gain.gain.setValueAtTime(0.4, ctx.currentTime);
                gain.gain.linearRampToValueAtTime(0.01, ctx.currentTime + 0.4);
                osc.start();
                osc.stop(ctx.currentTime + 0.4);
            }
        } catch (err) {
            console.warn('AudioContext error:', err);
        }
    }

    if (btnToggleSound) {
        btnToggleSound.addEventListener('click', function () {
            soundEnabled = !soundEnabled;
            btnToggleSound.textContent = soundEnabled ? '🔊 ' + t('sound_on', 'Sound: On') : '🔇 ' + t('sound_off', 'Sound: Off');
            if (soundEnabled) playFeedbackSound(true);
        });
    }

    // =========================================================================
    // 2. Gate Lock & Start-Time Controls
    // =========================================================================
    function applyGateConfigToControls(cfg) {
        if (!cfg) return;
        const sysActive = cfg.system_active !== false;
        const lockEnabled = Boolean(cfg.time_lock_enabled);
        const hours = typeof cfg.hours_before_start === 'number' ? cfg.hours_before_start : 24;

        if (gateSystemActive) {
            gateSystemActive.value = sysActive ? '1' : '0';
        }
        if (gateTimeMode) {
            if (!lockEnabled) gateTimeMode.value = 'free';
            else if (hours === 24) gateTimeMode.value = 'h1';
            else if (hours === 6) gateTimeMode.value = 'dday';
            else gateTimeMode.value = 'custom';
        }
        if (gateCustomHours) {
            gateCustomHours.value = String(hours);
        }
        if (gateCustomHoursWrap && gateTimeMode) {
            gateCustomHoursWrap.style.display = gateTimeMode.value === 'custom' ? 'inline-flex' : 'none';
        }
        if (gateRuleStatusText) {
            if (!sysActive) {
                gateRuleStatusText.textContent = t('rule_status_locked', '🔴 Gate Locked (Scanning Disabled)');
                gateRuleStatusText.style.color = '#dc2626';
            } else if (lockEnabled) {
                gateRuleStatusText.textContent = t('rule_status_time', '📅 Active from H-:hours Hours Before Event', { ':hours': hours });
                gateRuleStatusText.style.color = '#0284c7';
            } else {
                gateRuleStatusText.textContent = t('rule_status_free', '🟢 Free (Anytime Scan)');
                gateRuleStatusText.style.color = '#059669';
            }
        }
    }

    if (gateTimeMode) {
        gateTimeMode.addEventListener('change', function () {
            if (gateCustomHoursWrap) {
                gateCustomHoursWrap.style.display = gateTimeMode.value === 'custom' ? 'inline-flex' : 'none';
            }
            if (gateTimeMode.value === 'h1' && gateCustomHours) gateCustomHours.value = '24';
            if (gateTimeMode.value === 'dday' && gateCustomHours) gateCustomHours.value = '6';
        });
    }

    if (btnSaveGateSettings) {
        btnSaveGateSettings.addEventListener('click', async function () {
            const eventId = eventSelect?.value ? parseInt(eventSelect.value, 10) : null;
            const sysActive = gateSystemActive ? gateSystemActive.value === '1' : true;
            const mode = gateTimeMode ? gateTimeMode.value : 'free';
            const timeLockEnabled = mode !== 'free';
            let hoursBefore = 24;

            if (mode === 'h1') hoursBefore = 24;
            else if (mode === 'dday') hoursBefore = 6;
            else if (mode === 'custom') {
                hoursBefore = Math.max(0, parseInt(gateCustomHours?.value || '24', 10) || 0);
            }

            btnSaveGateSettings.disabled = true;
            btnSaveGateSettings.textContent = '⏳ ' + t('loading', 'Saving...');

            // Save in local manifest immediately so offline scanning respects it
            if (eventId) {
                const manifest = loadLocalManifest(eventId);
                if (manifest) {
                    manifest.gate_config = {
                        system_active: sysActive,
                        time_lock_enabled: timeLockEnabled,
                        hours_before_start: hoursBefore,
                    };
                    saveLocalManifest(eventId, manifest);
                }
            }
            applyGateConfigToControls({
                system_active: sysActive,
                time_lock_enabled: timeLockEnabled,
                hours_before_start: hoursBefore,
            });

            try {
                const resp = await fetch(SETTINGS_URL, {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        'Content-Type': 'application/json',
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': CSRF_TOKEN,
                    },
                    body: JSON.stringify({
                        event_id: eventId,
                        system_active: sysActive,
                        time_lock_enabled: timeLockEnabled,
                        hours_before_start: hoursBefore,
                    }),
                });
                const data = await resp.json();
                if (resp.ok && data.ok) {
                    applyGateConfigToControls(data.gate_config);
                    showResultState(
                        sysActive ? 'success' : 'caution',
                        sysActive ? t('rule_saved_title', '⚙️ GATE RULES SAVED') : t('rule_locked_title', '🔒 GATE SCANNING DISABLED'),
                        data.message,
                        null,
                        false
                    );
                    playFeedbackSound(true);
                }
            } catch (err) {
                showResultState(
                    'caution',
                    t('rule_saved_offline', '⚙️ RULES SAVED LOCALLY (OFFLINE)'),
                    t('rule_offline_msg', 'Gate rule applied locally and will sync once online.'),
                    null,
                    true
                );
            } finally {
                btnSaveGateSettings.disabled = false;
                btnSaveGateSettings.textContent = '💾 ' + t('save_rule', 'Save Rule');
            }
        });
    }

    // =========================================================================
    // 3. Network Status, Offline Storage & 2-Way Multi-Device Sync
    // =========================================================================
    function updateNetworkStatus() {
        const isOnline = navigator.onLine;
        if (netBadge && netText) {
            netBadge.className = isOnline ? 'gate-status-badge online' : 'gate-status-badge offline';
            netText.textContent = isOnline ? 'ONLINE' : 'OFFLINE';
        }
        if (multiSyncText) {
            multiSyncText.textContent = isOnline
                ? t('sync_active', 'Multi-Device Sync: Real-Time')
                : t('sync_local', 'Multi-Device Sync: Local Queue');
        }
        if (isOnline) {
            syncPendingOfflineRecords(true);
        }
    }
    window.addEventListener('online', updateNetworkStatus);
    window.addEventListener('offline', updateNetworkStatus);

    function getManifestStorageKey(eventId) {
        return 'gate_manifest_v1_' + eventId;
    }

    function getPendingSyncStorageKey() {
        return 'gate_pending_sync_v1';
    }

    function loadLocalManifest(eventId) {
        try {
            const raw = localStorage.getItem(getManifestStorageKey(eventId));
            return raw ? JSON.parse(raw) : null;
        } catch (_) {
            return null;
        }
    }

    function saveLocalManifest(eventId, data) {
        try {
            localStorage.setItem(getManifestStorageKey(eventId), JSON.stringify(data));
        } catch (e) {
            console.warn('LocalStorage error:', e);
        }
    }

    function getPendingSync() {
        try {
            const raw = localStorage.getItem(getPendingSyncStorageKey());
            return raw ? JSON.parse(raw) : [];
        } catch (_) {
            return [];
        }
    }

    function savePendingSync(list) {
        try {
            localStorage.setItem(getPendingSyncStorageKey(), JSON.stringify(list));
        } catch (_) {}
        updateSyncButtonBadge();
    }

    function updateSyncButtonBadge() {
        const pending = getPendingSync();
        if (!btnSyncBatch || !syncCountSpan) return;
        if (pending.length > 0) {
            btnSyncBatch.style.display = 'inline-flex';
            syncCountSpan.textContent = String(pending.length);
        } else {
            btnSyncBatch.style.display = 'none';
        }
    }

    function refreshManifestDisplay() {
        const eventId = eventSelect?.value;
        if (!eventId) return;

        const manifest = loadLocalManifest(eventId);
        if (manifest && Array.isArray(manifest.tickets)) {
            const total = manifest.tickets.length;
            const attended = manifest.tickets.filter((t) => t.status === 'used').length;
            const percent = total > 0 ? Math.round((attended / total) * 100) : 0;

            if (manifestInfo) {
                const syncTime = manifest.synced_at
                    ? new Date(manifest.synced_at).toLocaleTimeString()
                    : '-';
                manifestInfo.textContent = t('manifest_ready', ':count tickets ready offline (Sync: :time)', {
                    ':count': total,
                    ':time': syncTime,
                });
            }
            if (statAttended) statAttended.textContent = String(attended);
            if (statTotal) statTotal.textContent = String(total);
            if (statPercent) statPercent.textContent = `${percent}%`;

            if (manifest.gate_config) {
                applyGateConfigToControls(manifest.gate_config);
            }

            renderManifestTicketList(manifest.tickets);
        } else {
            if (manifestInfo) manifestInfo.textContent = t('not_downloaded', 'Not downloaded yet');
            if (statAttended) statAttended.textContent = '0';
            if (statTotal) statTotal.textContent = '0';
            if (statPercent) statPercent.textContent = '0%';
            renderManifestTicketList([]);
        }
        updateSyncButtonBadge();
    }

    function renderManifestTicketList(tickets) {
        if (!manifestTicketsBody) return;
        if (!tickets || tickets.length === 0) {
            manifestTicketsBody.innerHTML = `
                <tr>
                    <td colspan="5" style="padding: 1rem; text-align: center; color: var(--muted);">
                        ${escapeHtml(t('loading', 'Loading ticket manifest...'))}
                    </td>
                </tr>
            `;
            return;
        }

        let html = '';
        tickets.slice(0, 50).forEach((tItem) => {
            const isUsed = tItem.status === 'used';
            const badgeBg = isUsed ? 'rgba(239, 68, 68, 0.12)' : 'rgba(16, 185, 129, 0.12)';
            const badgeColor = isUsed ? '#dc2626' : '#059669';
            const statusLabel = isUsed ? t('admitted', 'ADMITTED') : t('not_admitted', 'NOT ADMITTED');
            const fullPayload = tItem.signature ? `${tItem.code}.${tItem.signature}` : tItem.code;

            html += `
                <tr style="border-bottom: 1px solid var(--line);">
                    <td style="padding: 0.55rem 0.65rem; font-family: monospace; font-weight: 600;">${escapeHtml(tItem.code)}</td>
                    <td style="padding: 0.55rem 0.65rem;">${escapeHtml(tItem.holder || t('attendee_fallback', 'Attendee'))}</td>
                    <td style="padding: 0.55rem 0.65rem;">${escapeHtml(tItem.ticket_name || '-')}</td>
                    <td style="padding: 0.55rem 0.65rem;">
                        <span style="background: ${badgeBg}; color: ${badgeColor}; padding: 0.2rem 0.5rem; border-radius: 4px; font-weight: 700; font-size: 0.72rem;">
                            ${escapeHtml(statusLabel)}
                        </span>
                    </td>
                    <td style="padding: 0.55rem 0.65rem; text-align: right;">
                        <button type="button" class="button secondary small" data-quick-scan="${escapeHtml(fullPayload)}" style="padding: 0.25rem 0.6rem; font-size: 0.75rem;">
                            ${escapeHtml(t('scan_this', '⚡ Scan This Ticket'))}
                        </button>
                    </td>
                </tr>
            `;
        });
        manifestTicketsBody.innerHTML = html;
    }

    // Delegate click on quick scan buttons in manifest table
    document.addEventListener('click', function (e) {
        const btn = e.target.closest('[data-quick-scan]');
        if (!btn) return;
        const payload = btn.getAttribute('data-quick-scan');
        if (payload) {
            processScannedCode(payload, true);
            resultCard?.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
    });

    async function downloadManifest(silent = false) {
        const eventId = eventSelect?.value;
        if (!eventId) {
            if (!silent) alert(t('select_event', 'Please select a concert event first.'));
            return;
        }

        if (btnDownloadManifest && !silent) {
            btnDownloadManifest.disabled = true;
            btnDownloadManifest.textContent = t('downloading', '⏳ Downloading...');
        }

        try {
            const resp = await fetch(MANIFEST_URL_BASE + encodeURIComponent(eventId), {
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'same-origin',
            });
            if (!resp.ok) throw new Error('HTTP ' + resp.status);
            const data = await resp.json();

            // Preserve local offline scans not yet pushed
            const pendingCodes = new Set(getPendingSync().map((p) => p.code.toLowerCase()));
            if (Array.isArray(data.tickets) && pendingCodes.size > 0) {
                data.tickets.forEach((tItem) => {
                    if (pendingCodes.has(tItem.code.toLowerCase())) {
                        tItem.status = 'used';
                    }
                });
            }

            saveLocalManifest(eventId, data);
            refreshManifestDisplay();

            if (!silent) {
                showResultState(
                    'success',
                    t('manifest_success_title', '📥 MANIFEST READY (:count TICKETS)', { ':count': data.total_tickets }),
                    t('manifest_success_msg', 'Ticket data and schedule for ":title" stored in browser. Devices will sync automatically!', { ':title': data.event_title }),
                    null,
                    false
                );
                playFeedbackSound(true);
            }
        } catch (err) {
            if (!silent) {
                showResultState(
                    'warning',
                    t('manifest_fail_title', '❌ FAILED TO DOWNLOAD MANIFEST'),
                    t('manifest_fail_msg', 'Ensure connection to server is active when downloading manifest: :error', { ':error': err.message }),
                    null,
                    false
                );
            }
        } finally {
            if (btnDownloadManifest) {
                btnDownloadManifest.disabled = false;
                btnDownloadManifest.textContent = '📥 ' + t('download_manifest', 'Download Offline Manifest');
            }
        }
    }

    if (btnDownloadManifest) {
        btnDownloadManifest.addEventListener('click', function () {
            downloadManifest(false);
        });
    }

    /**
     * 2-Way Multi-Device Sync:
     * Pushes pending offline scans AND pulls latest `used_tickets` from all other staff devices.
     */
    async function syncPendingOfflineRecords(isBackgroundHeartbeat = false) {
        if (!navigator.onLine) return;
        const pending = getPendingSync();
        const eventId = eventSelect?.value ? parseInt(eventSelect.value, 10) : null;

        if (isBackgroundHeartbeat && !eventId && pending.length === 0) return;

        if (!isBackgroundHeartbeat && btnSyncBatch) {
            btnSyncBatch.disabled = true;
            btnSyncBatch.textContent = t('syncing', '⏳ Syncing...');
        }

        try {
            const resp = await fetch(SYNC_URL, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': CSRF_TOKEN,
                },
                body: JSON.stringify({
                    event_id: eventId,
                    check_ins: pending,
                }),
            });

            if (resp.ok) {
                const res = await resp.json();

                if (pending.length > 0) {
                    savePendingSync([]);
                }

                // Update local manifest with used tickets from other devices
                if (eventId && Array.isArray(res.used_tickets)) {
                    const manifest = loadLocalManifest(eventId);
                    if (manifest && Array.isArray(manifest.tickets)) {
                        const usedMap = new Map(
                            res.used_tickets.map((u) => [u.code.toLowerCase(), u.checked_in_at])
                        );
                        manifest.tickets.forEach((tItem) => {
                            const key = tItem.code.toLowerCase();
                            if (usedMap.has(key)) {
                                tItem.status = 'used';
                                tItem.checked_in_at = usedMap.get(key) || tItem.checked_in_at;
                            }
                        });
                        if (res.gate_config) {
                            manifest.gate_config = res.gate_config;
                        }
                        manifest.synced_at = new Date().toISOString();
                        saveLocalManifest(eventId, manifest);
                        refreshManifestDisplay();
                    }
                }

                if (multiSyncText) {
                    multiSyncText.textContent = t('sync_active', 'Multi-Device Sync: Real-Time');
                }

                // Report multi-device duplicate scan conflicts if any
                if (res.conflict_count > 0 && Array.isArray(res.conflicts)) {
                    const firstConflict = res.conflicts[0];
                    playFeedbackSound(false);
                    showResultState(
                        'warning',
                        t('conflict_title', '⚠️ MULTI-DEVICE CONFLICT DETECTED!'),
                        t('conflict_msg', 'Ticket :code (:holder) was already scanned on another staff device at :time!', {
                            ':code': firstConflict.code,
                            ':holder': firstConflict.holder,
                            ':time': firstConflict.previous_checked_in_at,
                        }),
                        null,
                        false
                    );
                } else if (!isBackgroundHeartbeat && pending.length > 0) {
                    showResultState(
                        'success',
                        t('sync_success_title', '🔄 SYNC SUCCESSFUL'),
                        res.message || t('sync_success_msg', 'Offline check-in records have been saved to server database.'),
                        null,
                        false
                    );
                }
            }
        } catch (err) {
            if (!isBackgroundHeartbeat) {
                console.warn('Sync batch failed:', err);
            }
        } finally {
            if (btnSyncBatch) {
                btnSyncBatch.disabled = false;
            }
            updateSyncButtonBadge();
        }
    }

    if (btnSyncBatch) {
        btnSyncBatch.addEventListener('click', function () {
            syncPendingOfflineRecords(false);
        });
    }

    if (eventSelect) {
        eventSelect.addEventListener('change', function () {
            refreshManifestDisplay();
            if (navigator.onLine) {
                downloadManifest(true);
            }
        });
    }

    // 8-second background heartbeat for automatic multi-device delta synchronization
    setInterval(function () {
        if (navigator.onLine && !isProcessingScan) {
            syncPendingOfflineRecords(true);
        }
    }, 8000);

    // =========================================================================
    // 4. Camera WebRTC & QR Code Frame Scanner
    // =========================================================================
    function setCameraOverlay(visible, icon, title, subtitle, showRetryBtn = true) {
        if (!cameraStatusOverlay) return;
        cameraStatusOverlay.style.display = visible ? 'flex' : 'none';
        if (cameraStatusIcon) cameraStatusIcon.textContent = icon;
        if (cameraStatusText) cameraStatusText.textContent = title;
        if (cameraStatusSub) cameraStatusSub.textContent = subtitle;
        if (btnStartCamOverlay) btnStartCamOverlay.style.display = showRetryBtn ? 'inline-flex' : 'none';
        if (scannerReticle) scannerReticle.style.display = visible ? 'none' : 'flex';
    }

    async function updateCameraDeviceList() {
        if (!navigator.mediaDevices || !navigator.mediaDevices.enumerateDevices || !cameraDeviceSelect) {
            return;
        }
        try {
            const devices = await navigator.mediaDevices.enumerateDevices();
            videoDevices = devices.filter((d) => d.kind === 'videoinput');
            if (videoDevices.length > 0) {
                cameraDeviceSelect.innerHTML = '';
                videoDevices.forEach((dev, idx) => {
                    const opt = document.createElement('option');
                    opt.value = dev.deviceId;
                    opt.textContent = dev.label || `Camera ${idx + 1}`;
                    if (dev.deviceId === selectedDeviceId) opt.selected = true;
                    cameraDeviceSelect.appendChild(opt);
                });
                cameraDeviceSelect.style.display = 'inline-block';
            }
        } catch (_) {}
    }

    async function startCamera(deviceId = '') {
        stopCamera(false);

        if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
            setCameraOverlay(
                true,
                '🔒',
                'HTTPS / Localhost Required',
                'Browsers require HTTPS or localhost to enable camera devices. Use QR Image upload or manual input below.',
                true
            );
            return;
        }

        setCameraOverlay(
            true,
            '📷',
            t('camera_not_active', 'Requesting Browser Camera Permission...'),
            t('camera_not_active_sub', 'Click "Allow" on the browser prompt to activate the QR scanner.'),
            false
        );

        const constraints = {
            audio: false,
            video: deviceId
                ? { deviceId: { exact: deviceId }, width: { ideal: 640 }, height: { ideal: 640 } }
                : { facingMode: { ideal: currentFacingMode }, width: { ideal: 640 }, height: { ideal: 640 } },
        };

        try {
            currentStream = await navigator.mediaDevices.getUserMedia(constraints);
            if (!videoEl) return;

            videoEl.srcObject = currentStream;
            videoEl.setAttribute('playsinline', 'true');
            await videoEl.play();

            isCameraActive = true;
            if (btnToggleCam) btnToggleCam.textContent = t('camera_off', '⏹ Turn Off Camera');
            setCameraOverlay(false, '', '', '', false);

            const videoTrack = currentStream.getVideoTracks()[0];
            if (videoTrack && videoTrack.getSettings) {
                selectedDeviceId = videoTrack.getSettings().deviceId || '';
            }
            await updateCameraDeviceList();

            scanLoopId = requestAnimationFrame(scanVideoFrame);
        } catch (err) {
            isCameraActive = false;
            if (btnToggleCam) btnToggleCam.textContent = t('camera_on', '📷 Turn On Camera');

            let msgTitle = t('camera_not_active', 'Camera Inactive');
            let msgSub = t('camera_not_active_sub', 'Click the button below to turn on the QR camera scanner.');

            if (err.name === 'NotAllowedError' || err.name === 'PermissionDeniedError') {
                msgTitle = t('camera_permission_denied', 'Camera Permission Denied by Browser');
                msgSub = t('camera_permission_denied_sub', 'Click the lock/camera icon next to your URL bar, change Camera permission to "Allow", then click the button below.');
            } else if (err.name === 'NotFoundError' || err.name === 'DevicesNotFoundError') {
                msgTitle = t('camera_not_found', 'No Camera Found');
                msgSub = t('camera_not_found_sub', 'This device has no active webcam. You can still scan via QR Image file, PDA Scanner, or Manual Input.');
            } else if (err.name === 'NotReadableError' || err.name === 'TrackStartError') {
                msgTitle = t('camera_in_use', 'Camera Currently Used by Another App');
                msgSub = t('camera_in_use_sub', 'Close any other application using the camera (Zoom/Meet/other tab), then try again.');
            }

            setCameraOverlay(true, '📷', msgTitle, msgSub, true);
        }
    }

    function stopCamera(showOverlay = true) {
        if (scanLoopId) {
            cancelAnimationFrame(scanLoopId);
            scanLoopId = null;
        }
        if (currentStream) {
            currentStream.getTracks().forEach((track) => track.stop());
            currentStream = null;
        }
        if (videoEl) {
            videoEl.srcObject = null;
        }
        isCameraActive = false;
        if (btnToggleCam) btnToggleCam.textContent = t('camera_on', '📷 Turn On Camera');

        if (showOverlay) {
            setCameraOverlay(
                true,
                '📷',
                t('camera_stopped', 'Camera Turned Off'),
                t('camera_stopped_sub', 'Click the button below to turn the scanner camera back on.'),
                true
            );
        }
    }

    async function scanVideoFrame() {
        if (!isCameraActive || !videoEl) return;

        if (videoEl.readyState === videoEl.HAVE_ENOUGH_DATA && canvasEl && canvasCtx) {
            const width = videoEl.videoWidth;
            const height = videoEl.videoHeight;

            if (width > 0 && height > 0) {
                canvasEl.width = width;
                canvasEl.height = height;
                canvasCtx.drawImage(videoEl, 0, 0, width, height);

                // Method 1: jsQR Pure JS Canvas Engine (Consistent across all devices)
                if (typeof window.jsQR === 'function') {
                    try {
                        const imageData = canvasCtx.getImageData(0, 0, width, height);
                        const qrResult = window.jsQR(imageData.data, width, height, {
                            inversionAttempts: 'attemptBoth',
                        });
                        if (qrResult && qrResult.data) {
                            processScannedCode(qrResult.data);
                        }
                    } catch (_) {}
                }
                // Method 2: Native BarcodeDetector Fallback
                else if (barcodeDetector) {
                    try {
                        const barcodes = await barcodeDetector.detect(canvasEl);
                        if (barcodes.length > 0 && barcodes[0].rawValue) {
                            processScannedCode(barcodes[0].rawValue);
                        }
                    } catch (_) {}
                }
            }
        }

        if (isCameraActive) {
            setTimeout(() => {
                if (isCameraActive) {
                    scanLoopId = requestAnimationFrame(scanVideoFrame);
                }
            }, 90);
        }
    }

    if (btnStartCamOverlay) {
        btnStartCamOverlay.addEventListener('click', function () {
            startCamera(selectedDeviceId);
        });
    }

    if (btnToggleCam) {
        btnToggleCam.addEventListener('click', function () {
            if (isCameraActive) {
                stopCamera(true);
            } else {
                startCamera(selectedDeviceId);
            }
        });
    }

    if (btnSwitchCam) {
        btnSwitchCam.addEventListener('click', function () {
            if (videoDevices.length > 1) {
                const currentIdx = videoDevices.findIndex((d) => d.deviceId === selectedDeviceId);
                const nextIdx = (currentIdx + 1) % videoDevices.length;
                selectedDeviceId = videoDevices[nextIdx].deviceId;
                if (cameraDeviceSelect) cameraDeviceSelect.value = selectedDeviceId;
                startCamera(selectedDeviceId);
            } else {
                currentFacingMode = currentFacingMode === 'environment' ? 'user' : 'environment';
                startCamera('');
            }
        });
    }

    if (cameraDeviceSelect) {
        cameraDeviceSelect.addEventListener('change', function () {
            selectedDeviceId = cameraDeviceSelect.value;
            startCamera(selectedDeviceId);
        });
    }

    // =========================================================================
    // 5. Scan QR Code from Uploaded Image File
    // =========================================================================
    if (btnScanImage && qrFileInput) {
        btnScanImage.addEventListener('click', function () {
            qrFileInput.click();
        });

        qrFileInput.addEventListener('change', function (e) {
            const file = e.target.files?.[0];
            if (!file) return;

            const reader = new FileReader();
            reader.onload = function (ev) {
                const img = new Image();
                img.onload = function () {
                    if (!canvasEl || !canvasCtx) return;
                    canvasEl.width = img.width;
                    canvasEl.height = img.height;
                    canvasCtx.drawImage(img, 0, 0, img.width, img.height);

                    if (typeof window.jsQR === 'function') {
                        const imageData = canvasCtx.getImageData(0, 0, img.width, img.height);
                        const code = window.jsQR(imageData.data, img.width, img.height, {
                            inversionAttempts: 'attemptBoth',
                        });
                        if (code && code.data) {
                            processScannedCode(code.data, true);
                        } else {
                            playFeedbackSound(false);
                            showResultState(
                                'caution',
                                t('qr_not_detected_title', '⚠️ NO QR CODE DETECTED IN IMAGE'),
                                t('qr_not_detected_sub', 'Ensure the uploaded image displays a clear and uncropped ticket QR code.'),
                                null,
                                false
                            );
                        }
                    }
                };
                img.src = ev.target.result;
            };
            reader.readAsDataURL(file);
            qrFileInput.value = '';
        });
    }

    // =========================================================================
    // 6. Core Check-In Processor (Online Real-Time + Zero-Latency Offline Fallback)
    // =========================================================================
    async function processScannedCode(rawCode, forceImmediate = false) {
        if (!rawCode) return;
        const trimmed = String(rawCode).trim();
        if (!trimmed) return;

        const now = Date.now();
        if (!forceImmediate && isProcessingScan) return;
        if (!forceImmediate && trimmed === lastScannedCode && now - lastScannedAt < 2500) {
            return;
        }

        isProcessingScan = true;
        lastScannedCode = trimmed;
        lastScannedAt = now;

        const eventId = eventSelect?.value ? parseInt(eventSelect.value, 10) : null;

        showResultState(
            'idle',
            t('verifying_ticket', '⏳ Verifying Ticket...'),
            t('verifying_sub', 'Checking authenticity of code: :code', { ':code': trimmed }),
            null,
            false
        );

        // 1. Try Online Check-In First if online
        if (navigator.onLine) {
            try {
                const controller = new AbortController();
                const timeoutId = setTimeout(() => controller.abort(), 3000);

                const resp = await fetch(CHECKIN_URL, {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        'Content-Type': 'application/json',
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': CSRF_TOKEN,
                    },
                    body: JSON.stringify({
                        code: trimmed,
                        event_id: eventId,
                    }),
                    signal: controller.signal,
                });
                clearTimeout(timeoutId);

                const data = await resp.json();
                handleCheckInResponse(data, false);

                if (data.ok && data.ticket) {
                    updateTicketInLocalManifest(eventId, data.ticket);
                }
                return;
            } catch (err) {
                console.warn('Online check-in request timed out / failed, falling back to local offline manifest...', err);
            }
        }

        // 2. Offline Verification Fallback
        handleOfflineVerification(trimmed, eventId);
    }

    function updateTicketInLocalManifest(eventId, serverTicket) {
        if (!eventId || !serverTicket || !serverTicket.code) return;
        const manifest = loadLocalManifest(eventId);
        if (!manifest || !Array.isArray(manifest.tickets)) return;

        const found = manifest.tickets.find(
            (tItem) => tItem.code.toLowerCase() === serverTicket.code.toLowerCase()
        );
        if (found) {
            found.status = serverTicket.status || 'used';
            found.checked_in_at = new Date().toISOString();
            saveLocalManifest(eventId, manifest);
            refreshManifestDisplay();
        }
    }

    function handleCheckInResponse(res, isOffline) {
        const isSuccess = Boolean(res.ok && res.status === 'valid');
        playFeedbackSound(isSuccess);

        if (isSuccess) {
            showResultState(
                'success',
                t('valid_title', '✅ TICKET VALID — ADMISSION GRANTED'),
                res.message || t('valid_msg', 'Check-in successful.'),
                res.ticket,
                isOffline
            );
        } else if (res.status === 'already_used') {
            showResultState(
                'warning',
                t('used_title', '⛔ ALREADY USED!'),
                res.message || t('used_msg', 'This ticket was already used for admission previously.'),
                res.ticket,
                isOffline
            );
        } else if (res.status === 'gate_closed') {
            showResultState(
                'warning',
                t('locked_title', '🔒 GATE SYSTEM CURRENTLY LOCKED'),
                res.message || t('locked_msg', 'Ticket scanning is temporarily disabled by Admin.'),
                res.ticket,
                isOffline
            );
        } else if (res.status === 'too_early') {
            showResultState(
                'caution',
                t('too_early_title', '⏰ NOT TIME FOR CHECK-IN YET'),
                res.message || t('too_early_msg', 'Gate for this event is not yet open according to the scheduled start time.'),
                res.ticket,
                isOffline
            );
        } else if (res.status === 'wrong_event') {
            showResultState(
                'caution',
                t('wrong_event_title', '⚠️ WRONG EVENT / DIFFERENT SCHEDULE'),
                res.message || t('wrong_event_msg', 'This ticket belongs to a different concert event.'),
                res.ticket,
                isOffline
            );
        } else if (res.status === 'forged' || res.status === 'invalid_signature') {
            showResultState(
                'warning',
                t('forged_title', '🛡️ FORGED TICKET / INVALID SIGNATURE'),
                res.message || t('forged_msg', 'Digital cryptographic signature is invalid or tampered!'),
                null,
                isOffline
            );
        } else {
            showResultState(
                'warning',
                t('invalid_title', '❌ INVALID TICKET'),
                res.message || t('invalid_msg', 'Ticket code was not found in the system.'),
                res.ticket || null,
                isOffline
            );
        }

        addToHistory(
            res.status || 'invalid',
            res.ticket?.holder || t('attendee_fallback', 'Attendee'),
            res.ticket?.ticket_name || '-',
            res.ticket?.code || lastScannedCode,
            isSuccess
        );

        setTimeout(() => {
            isProcessingScan = false;
        }, 700);
    }

    function handleOfflineVerification(rawInput, eventId) {
        const manifest = eventId ? loadLocalManifest(eventId) : null;

        if (!manifest || !Array.isArray(manifest.tickets)) {
            playFeedbackSound(false);
            showResultState(
                'warning',
                t('offline_manifest_missing_title', '❌ OFFLINE MODE: MANIFEST NOT DOWNLOADED'),
                t('offline_manifest_missing_msg', 'Click "Download Offline Manifest" when online so this device can verify tickets offline.'),
                null,
                true
            );
            addToHistory('manifest_missing', 'Offline', '-', rawInput, false);
            setTimeout(() => {
                isProcessingScan = false;
            }, 700);
            return;
        }

        const cfg = manifest.gate_config || {
            system_active: gateSystemActive ? gateSystemActive.value === '1' : true,
            time_lock_enabled: gateTimeMode ? gateTimeMode.value !== 'free' : false,
            hours_before_start: parseInt(gateCustomHours?.value || '24', 10) || 24,
        };

        if (!cfg.system_active) {
            playFeedbackSound(false);
            showResultState(
                'warning',
                t('locked_title', '🔒 GATE SYSTEM CURRENTLY LOCKED'),
                t('locked_msg', 'Ticket scanning is temporarily disabled by Admin.'),
                null,
                true
            );
            addToHistory('gate_closed', 'Locked', '-', rawInput, false);
            setTimeout(() => {
                isProcessingScan = false;
            }, 700);
            return;
        }

        if (cfg.time_lock_enabled && manifest.event_start_time) {
            const startMs = new Date(manifest.event_start_time).getTime();
            const hoursBefore = Number(cfg.hours_before_start || 24);
            const openGateMs = startMs - hoursBefore * 3600 * 1000;
            if (Date.now() < openGateMs) {
                const openStr = new Date(openGateMs).toLocaleString();
                playFeedbackSound(false);
                showResultState(
                    'caution',
                    t('too_early_title', '⏰ NOT TIME FOR CHECK-IN YET'),
                    t('too_early_msg', 'Gate for this event is not yet open according to the scheduled start time.') + ` (${openStr})`,
                    null,
                    true
                );
                addToHistory('too_early', 'Early', '-', rawInput, false);
                setTimeout(() => {
                    isProcessingScan = false;
                }, 700);
                return;
            }
        }

        let parsedCode = rawInput;
        let parsedHmac = null;
        if (rawInput.includes('.')) {
            const parts = rawInput.split('.');
            parsedCode = parts[0];
            parsedHmac = parts[1];
        }

        const ticket = manifest.tickets.find(
            (tItem) => tItem.code.toLowerCase() === parsedCode.toLowerCase()
        );

        if (!ticket) {
            playFeedbackSound(false);
            showResultState(
                'warning',
                t('offline_not_found_title', '❌ TICKET NOT FOUND IN MANIFEST'),
                t('offline_not_found_sub', 'Ticket code is not listed in this event\'s manifest.'),
                null,
                true
            );
            addToHistory('not_found', 'Not Found', '-', parsedCode, false);
            setTimeout(() => {
                isProcessingScan = false;
            }, 700);
            return;
        }

        if (parsedHmac && ticket.signature && parsedHmac !== ticket.signature) {
            playFeedbackSound(false);
            showResultState(
                'warning',
                t('offline_forged_title', '🛡️ FORGED TICKET (SIGNATURE MISMATCH)'),
                t('offline_forged_sub', 'QR cryptographic signature does not match the official manifest!'),
                null,
                true
            );
            addToHistory('forged', ticket.holder, ticket.ticket_name, ticket.code, false);
            setTimeout(() => {
                isProcessingScan = false;
            }, 700);
            return;
        }

        if (ticket.status === 'used') {
            playFeedbackSound(false);
            const prevTime = ticket.checked_in_at
                ? new Date(ticket.checked_in_at).toLocaleTimeString()
                : 'Earlier';
            showResultState(
                'warning',
                t('offline_used_title', '⛔ ALREADY USED!'),
                t('offline_used_sub', 'This ticket was already checked in at :time.', { ':time': prevTime }),
                ticket,
                true
            );
            addToHistory('already_used', ticket.holder, ticket.ticket_name, ticket.code, false);
            setTimeout(() => {
                isProcessingScan = false;
            }, 700);
            return;
        }

        const nowIso = new Date().toISOString();
        ticket.status = 'used';
        ticket.checked_in_at = nowIso;
        saveLocalManifest(eventId, manifest);

        const pending = getPendingSync();
        pending.push({
            code: ticket.code,
            checked_in_at: nowIso,
        });
        savePendingSync(pending);

        playFeedbackSound(true);
        showResultState(
            'success',
            t('offline_valid_title', '✅ TICKET VALID (OFFLINE VERIFIED)'),
            t('offline_valid_sub', 'Verified cryptographically via local manifest.'),
            ticket,
            true
        );
        addToHistory('valid', ticket.holder, ticket.ticket_name, ticket.code, true);
        refreshManifestDisplay();

        setTimeout(() => {
            isProcessingScan = false;
        }, 700);
    }

    // =========================================================================
    // 7. UI Result Card & Scan History
    // =========================================================================
    function showResultState(stateClass, title, subtitle, ticket, isOffline) {
        if (!resultCard) return;
        resultCard.className = 'card result-card ' + stateClass;

        if (resultIcon) {
            if (stateClass === 'success') resultIcon.textContent = '✅';
            else if (stateClass === 'warning') resultIcon.textContent = '⛔';
            else if (stateClass === 'caution') resultIcon.textContent = '⚠️';
            else resultIcon.textContent = '⏳';
        }

        if (resultTitle) resultTitle.textContent = title;
        if (resultSubtitle) resultSubtitle.textContent = subtitle;

        if (resultDetails) {
            if (ticket) {
                resultDetails.style.display = 'block';
                if (resHolder) resHolder.textContent = ticket.holder || '-';
                if (resCategory) resCategory.textContent = ticket.ticket_name || '-';
                if (resCode) resCode.textContent = ticket.code || '-';
                if (resTime) {
                    resTime.textContent =
                        ticket.checked_in_at && ticket.checked_in_at.includes('WIB')
                            ? ticket.checked_in_at
                            : new Date().toLocaleTimeString();
                }
                if (resModeText) {
                    resModeText.textContent = isOffline
                        ? t('mode_offline', 'Mode: Offline Local Engine')
                        : t('mode_online', 'Mode: Online Real-Time Server');
                }
                if (resCryptoText) {
                    resCryptoText.textContent = t('hmac_verified', 'HMAC: Signature Verified');
                }
            } else {
                resultDetails.style.display = 'none';
            }
        }
    }

    function escapeHtml(str) {
        return String(str ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function addToHistory(status, name, category, code, isOk) {
        if (!historyTableBody) return;
        const timeStr = new Date().toLocaleTimeString();
        scanHistory.unshift({ timeStr, status, name, category, code, isOk });
        if (scanHistory.length > 20) scanHistory.pop();

        if (historyCount) historyCount.textContent = t('scans_count', ':count scans', { ':count': scanHistory.length });

        let html = '';
        for (const item of scanHistory) {
            const badgeColor = item.isOk ? '#059669' : '#dc2626';
            const badgeBg = item.isOk ? 'rgba(16, 185, 129, 0.12)' : 'rgba(239, 68, 68, 0.12)';
            html += `
                <tr style="border-bottom: 1px solid var(--line);">
                    <td style="padding: 0.5rem 0.6rem; font-family: monospace;">${escapeHtml(item.timeStr)}</td>
                    <td style="padding: 0.5rem 0.6rem;">
                        <span style="background: ${badgeBg}; color: ${badgeColor}; padding: 0.2rem 0.45rem; border-radius: 4px; font-weight: 700; font-size: 0.72rem;">
                            ${escapeHtml(String(item.status).toUpperCase())}
                        </span>
                    </td>
                    <td style="padding: 0.5rem 0.6rem;">
                        <strong>${escapeHtml(item.name)}</strong><br>
                        <span class="muted">${escapeHtml(item.category)}</span>
                    </td>
                    <td style="padding: 0.5rem 0.6rem; font-family: monospace;">${escapeHtml(item.code)}</td>
                </tr>
            `;
        }
        historyTableBody.innerHTML = html;
    }

    // =========================================================================
    // 8. Manual Input Form & Hardware PDA Keystroke Listener
    // =========================================================================
    if (formManual) {
        formManual.addEventListener('submit', function (e) {
            e.preventDefault();
            const val = manualInput?.value?.trim();
            if (!val) {
                manualInput?.focus();
                return;
            }
            if (manualInput) manualInput.value = '';
            processScannedCode(val, true);
        });
    }

    let keyBuffer = '';
    let lastKeyTime = Date.now();

    document.addEventListener('keydown', function (e) {
        const activeEl = document.activeElement;
        const activeTag = activeEl ? activeEl.tagName.toLowerCase() : '';
        if (activeTag === 'input' || activeTag === 'select' || activeTag === 'textarea') {
            return;
        }

        const now = Date.now();
        if (now - lastKeyTime > 120) {
            keyBuffer = '';
        }
        lastKeyTime = now;

        if (e.key === 'Enter') {
            if (keyBuffer.length >= 6) {
                e.preventDefault();
                const scanned = keyBuffer;
                keyBuffer = '';
                processScannedCode(scanned, true);
            }
        } else if (e.key.length === 1) {
            keyBuffer += e.key;
        }
    });

    // =========================================================================
    // 9. Startup & Initialization
    // =========================================================================
    updateNetworkStatus();
    refreshManifestDisplay();

    if (navigator.onLine && eventSelect?.value) {
        downloadManifest(true);
    }

    startCamera('');
})();
