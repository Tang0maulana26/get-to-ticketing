<x-layout :title="__('Gate Scanner')" :admin="auth()->user()?->isAdmin() ?? false">
    @php
        $gateConfig = $gateConfig ?? ['system_active' => true, 'time_lock_enabled' => false, 'hours_before_start' => 24];
        $sysActive = (bool) ($gateConfig['system_active'] ?? true);
        $lockEnabled = (bool) ($gateConfig['time_lock_enabled'] ?? false);
        $lockHours = (int) ($gateConfig['hours_before_start'] ?? 24);
        $currentMode = !$lockEnabled ? 'free' : ($lockHours === 24 ? 'h1' : ($lockHours === 6 ? 'dday' : 'custom'));

        $i18n = [
            'sound_on' => __('Sound: On'),
            'sound_off' => __('Sound: Off'),
            'sync_active' => __('Multi-Device Sync: Real-Time'),
            'sync_local' => __('Multi-Device Sync: Local Queue'),
            'manifest_ready' => __(':count tickets ready offline (Sync: :time)'),
            'not_downloaded' => __('Not downloaded yet'),
            'loading' => __('Loading...'),
            'admitted' => __('ADMITTED'),
            'not_admitted' => __('NOT ADMITTED'),
            'scan_this' => __('⚡ Scan This Ticket'),
            'select_event' => __('Please select a concert event first.'),
            'downloading' => __('⏳ Downloading...'),
            'manifest_success_title' => __('📥 MANIFEST READY (:count TICKETS)'),
            'manifest_success_msg' => __('Ticket data and schedule for ":title" stored in browser. Devices will sync automatically!'),
            'manifest_fail_title' => __('❌ FAILED TO DOWNLOAD MANIFEST'),
            'manifest_fail_msg' => __('Ensure connection to server is active when downloading manifest: :error'),
            'syncing' => __('⏳ Syncing...'),
            'sync_success_title' => __('🔄 SYNC SUCCESSFUL'),
            'sync_success_msg' => __('Offline check-in records have been saved to server database.'),
            'conflict_title' => __('⚠️ MULTI-DEVICE CONFLICT DETECTED!'),
            'conflict_msg' => __('Ticket :code (:holder) was already scanned on another staff device at :time!'),
            'rule_saved_title' => __('⚙️ GATE RULES SAVED'),
            'rule_locked_title' => __('🔒 GATE SCANNING DISABLED'),
            'rule_saved_offline' => __('⚙️ RULES SAVED LOCALLY (OFFLINE)'),
            'rule_offline_msg' => __('Gate rule applied locally and will sync once online.'),
            'rule_status_locked' => __('🔴 Gate Locked (Scanning Disabled)'),
            'rule_status_time' => __('📅 Active from H-:hours Hours Before Event'),
            'rule_status_free' => __('🟢 Free (Anytime Scan)'),
            'camera_off' => __('⏹ Turn Off Camera'),
            'camera_on' => __('📷 Turn On Camera'),
            'camera_not_active' => __('Camera Inactive'),
            'camera_not_active_sub' => __('Click the button below to turn on the QR camera scanner.'),
            'camera_permission_denied' => __('Camera Permission Denied by Browser'),
            'camera_permission_denied_sub' => __('Click the lock/camera icon next to your URL bar, change Camera permission to "Allow", then click the button below.'),
            'camera_not_found' => __('No Camera Found'),
            'camera_not_found_sub' => __('This device has no active webcam. You can still scan via QR Image file, PDA Scanner, or Manual Input.'),
            'camera_in_use' => __('Camera Currently Used by Another App'),
            'camera_in_use_sub' => __('Close any other application using the camera (Zoom/Meet/other tab), then try again.'),
            'camera_stopped' => __('Camera Turned Off'),
            'camera_stopped_sub' => __('Click the button below to turn the scanner camera back on.'),
            'qr_not_detected_title' => __('⚠️ NO QR CODE DETECTED IN IMAGE'),
            'qr_not_detected_sub' => __('Ensure the uploaded image displays a clear and uncropped ticket QR code.'),
            'verifying_ticket' => __('⏳ Verifying Ticket...'),
            'verifying_sub' => __('Checking authenticity of code: :code'),
            'valid_title' => __('✅ TICKET VALID — ADMISSION GRANTED'),
            'valid_msg' => __('Check-in successful.'),
            'used_title' => __('⛔ ALREADY USED!'),
            'used_msg' => __('This ticket was already used for admission previously.'),
            'locked_title' => __('🔒 GATE SYSTEM CURRENTLY LOCKED'),
            'locked_msg' => __('Ticket scanning is temporarily disabled by Admin.'),
            'too_early_title' => __('⏰ NOT TIME FOR CHECK-IN YET'),
            'too_early_msg' => __('Gate for this event is not yet open according to the scheduled start time.'),
            'wrong_event_title' => __('⚠️ WRONG EVENT / DIFFERENT SCHEDULE'),
            'wrong_event_msg' => __('This ticket belongs to a different concert event.'),
            'forged_title' => __('🛡️ FORGED TICKET / INVALID SIGNATURE'),
            'forged_msg' => __('Digital cryptographic signature is invalid or tampered!'),
            'invalid_title' => __('❌ INVALID TICKET'),
            'invalid_msg' => __('Ticket code was not found in the system.'),
            'offline_manifest_missing_title' => __('❌ OFFLINE MODE: MANIFEST NOT DOWNLOADED'),
            'offline_manifest_missing_msg' => __('Click "Download Offline Manifest" when online so this device can verify tickets offline.'),
            'offline_not_found_title' => __('❌ TICKET NOT FOUND IN MANIFEST'),
            'offline_not_found_sub' => __('Ticket code is not listed in this event\'s manifest.'),
            'offline_forged_title' => __('🛡️ FORGED TICKET (SIGNATURE MISMATCH)'),
            'offline_forged_sub' => __('QR cryptographic signature does not match the official manifest!'),
            'offline_used_title' => __('⛔ ALREADY USED!'),
            'offline_used_sub' => __('This ticket was already checked in at :time.'),
            'offline_valid_title' => __('✅ TICKET VALID (OFFLINE VERIFIED)'),
            'offline_valid_sub' => __('Verified cryptographically via local manifest.'),
            'mode_offline' => __('Mode: Offline Local Engine'),
            'mode_online' => __('Mode: Online Real-Time Server'),
            'hmac_verified' => __('HMAC: Signature Verified'),
            'scans_count' => __(':count scans'),
            'attendee_fallback' => __('Attendee'),
        ];
    @endphp

    <section
        id="gate-scanner-app"
        class="shell page-space"
        data-csrf="{{ csrf_token() }}"
        data-checkin-url="{{ route('gate.check-in') }}"
        data-settings-url="{{ route('gate.settings') }}"
        data-manifest-base-url="/gate/manifest/"
        data-sync-url="{{ route('gate.sync-batch') }}"
        data-i18n="{{ json_encode($i18n) }}"
    >
        {{-- Header & Title --}}
        <div class="section-heading" style="margin-bottom: 1.5rem;">
            <div>
                <p class="eyebrow">GATE CONTROL & ADMISSION</p>
                <h1 style="display: flex; align-items: center; gap: 0.5rem; flex-wrap: wrap;">
                    <span>📱</span> {{ __('Gate Ticket Scanner') }}
                </h1>
                <p class="muted">
                    {{ __('Support mobile cameras, laptop webcams, and barcode/PDA scanners. Works real-time online or 100% offline.') }}
                </p>
            </div>
            <div style="display: flex; gap: 0.5rem; flex-wrap: wrap; align-items: center;">
                {{-- Network Status Indicator --}}
                <div id="net-badge" class="gate-status-badge online" title="{{ __('Network Status') }}">
                    <span class="dot"></span>
                    <span id="net-text">ONLINE</span>
                </div>
                {{-- Audio Mute/Unmute Toggle --}}
                <button type="button" id="btn-toggle-sound" class="button secondary small" style="padding: 0.4rem 0.8rem; font-size: 0.85rem;" title="{{ __('Toggle sound feedback') }}">
                    🔊 {{ __('Sound: On') }}
                </button>
            </div>
        </div>

        {{-- Event Selector & Offline Manifest Bar --}}
        <div class="card" style="margin-bottom: 1.5rem; padding: 1.25rem;">
            <div style="display: grid; grid-template-columns: 1fr auto; gap: 1rem; align-items: center;" class="gate-top-bar">
                <div>
                    <label for="event-select" style="font-weight: 600; font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.05em; color: var(--muted); display: block; margin-bottom: 0.35rem;">
                        {{ __('Select Concert Event:') }}
                    </label>
                    <select id="event-select" class="form-input" style="font-weight: 600; font-size: 1rem; width: 100%; max-width: 440px; padding: 0.55rem 0.75rem; border-radius: 6px; border: 1px solid var(--line); background: var(--panel); color: var(--ink);">
                        @forelse($events as $event)
                            <option value="{{ $event->id }}" {{ $selectedEventId == $event->id ? 'selected' : '' }}>
                                {{ $event->title }} ({{ $event->start_time->format('d M Y') }})
                            </option>
                        @empty
                            <option value="">{{ __('No active events') }}</option>
                        @endforelse
                    </select>
                </div>
                <div style="display: flex; gap: 0.5rem; flex-wrap: wrap; justify-content: flex-end;">
                    <button type="button" id="btn-download-manifest" class="button secondary" style="font-size: 0.85rem;" title="{{ __('Save event tickets to device memory for zero-internet scanning') }}">
                        📥 {{ __('Download Offline Manifest') }}
                    </button>
                    <button type="button" id="btn-sync-batch" class="button" style="font-size: 0.85rem; display: none; background: #059669;" title="{{ __('Upload offline check-in records to server') }}">
                        🔄 <span id="sync-count">0</span> {{ __('Sync Offline Tickets') }}
                    </button>
                </div>
            </div>

            {{-- Manifest & Attendance Info Strip --}}
            <div style="display: flex; gap: 1.5rem; margin-top: 1rem; padding-top: 0.85rem; border-top: 1px solid var(--line); font-size: 0.85rem; flex-wrap: wrap;" class="muted">
                <div>
                    <span>{{ __('Manifest Status:') }} </span>
                    <strong id="manifest-info" style="color: var(--ink);">{{ __('Loading...') }}</strong>
                </div>
                <div>
                    <span>{{ __('Total Admitted:') }} </span>
                    <strong id="stat-attended" style="color: #059669;">0</strong> / <strong id="stat-total" style="color: var(--ink);">0</strong>
                    (<span id="stat-percent">0%</span>)
                </div>
                <div id="multi-device-sync-indicator" style="display: inline-flex; align-items: center; gap: 0.35rem; color: #059669;">
                    <span>🔄</span> <span id="multi-sync-text">{{ __('Multi-Device Sync: Active') }}</span>
                </div>
                <div id="pda-indicator" style="display: inline-flex; align-items: center; gap: 0.35rem; color: #0284c7;">
                    <span>⚡</span> <span>{{ __('PDA / Laser Scanner Ready') }}</span>
                </div>
            </div>

            {{-- Gate System & Start-Time Controls --}}
            @if(auth()->user()?->isAdmin())
            <div style="margin-top: 1rem; padding-top: 0.9rem; border-top: 1px dashed var(--line); display: flex; align-items: center; justify-content: space-between; gap: 1rem; flex-wrap: wrap;">
                <div style="display: flex; align-items: center; gap: 0.65rem; flex-wrap: wrap;">
                    <span style="font-size: 0.82rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.04em; color: var(--muted);">
                        🔒 {{ __('Gate System & Time Controls:') }}
                    </span>

                    {{-- System Active Toggle --}}
                    <select id="gate-system-active" style="padding: 0.38rem 0.65rem; font-size: 0.83rem; font-weight: 600; border-radius: 6px; border: 1px solid var(--line); background: var(--panel); color: var(--ink);">
                        <option value="1" {{ $sysActive ? 'selected' : '' }}>{{ __('System Active (Gate Open)') }}</option>
                        <option value="0" {{ ! $sysActive ? 'selected' : '' }}>{{ __('Lock / Disable Gate Scanning') }}</option>
                    </select>

                    <select id="gate-time-mode" style="padding: 0.38rem 0.65rem; font-size: 0.83rem; font-weight: 600; border-radius: 6px; border: 1px solid var(--line); background: var(--panel); color: var(--ink);">
                        <option value="free" {{ $currentMode === 'free' ? 'selected' : '' }}>{{ __('Free (Allow Check-In Anytime)') }}</option>
                        <option value="h1" {{ $currentMode === 'h1' ? 'selected' : '' }}>{{ __('Lock H-1 (24 Hours Before Start Time)') }}</option>
                        <option value="dday" {{ $currentMode === 'dday' ? 'selected' : '' }}>{{ __('Lock D-Day (6 Hours Before Start Time)') }}</option>
                        <option value="custom" {{ $currentMode === 'custom' ? 'selected' : '' }}>{{ __('Custom Hours Before Start Time') }}</option>
                    </select>

                    {{-- Custom Hours Input --}}
                    <div id="gate-custom-hours-wrap"
                         @if($currentMode !== 'custom') style="display: none; align-items: center; gap: 0.35rem; font-size: 0.82rem;"
                         @else style="display: inline-flex; align-items: center; gap: 0.35rem; font-size: 0.82rem;"
                         @endif>
                        <span>{{ __('From H-') }}</span>
                        <input
                            type="number"
                            id="gate-custom-hours"
                            min="0"
                            max="720"
                            value="{{ $lockHours }}"
                            style="width: 68px; padding: 0.35rem 0.5rem; border-radius: 6px; border: 1px solid var(--line); background: var(--panel); color: var(--ink); font-weight: 700; text-align: center;"
                        />
                        <span>{{ __('hours before event') }}</span>
                    </div>

                    <button type="button" id="btn-save-gate-settings" class="button secondary small" style="padding: 0.38rem 0.8rem; font-size: 0.82rem; font-weight: 600;">
                        💾 {{ __('Save Rule') }}
                    </button>
                </div>

                <div id="gate-rule-badge" style="font-size: 0.8rem; font-weight: 600; color: var(--muted);">
                    {{ __('Rule:') }} <strong id="gate-rule-status-text" style="color: var(--ink);">{{ __('Free Scan') }}</strong>
                </div>
            </div>
            @endif
        </div>

        {{-- Main Scanner Section: Grid (Camera & PDA on left, Live Result & History on right) --}}
        <div class="gate-grid">
            {{-- Left Column: Camera Stream, Image Upload & Manual Input --}}
            <div class="gate-scanner-box card" style="padding: 1.25rem;">
                <div style="display: flex; justify-content: space-between; align-items: center; gap: 0.5rem; flex-wrap: wrap; margin-bottom: 0.75rem;">
                    <h2 style="font-size: 1.1rem; margin: 0; font-weight: 600;">📷 {{ __('Camera Scanner') }}</h2>
                    <div style="display: flex; gap: 0.4rem; flex-wrap: wrap; align-items: center;">
                        <select id="camera-device-select" style="display: none; padding: 0.28rem 0.5rem; font-size: 0.78rem; border-radius: 6px; border: 1px solid var(--line); background: var(--panel); color: var(--ink); max-width: 160px;"></select>
                        <button type="button" id="btn-toggle-cam" class="button secondary small" style="padding: 0.3rem 0.65rem; font-size: 0.8rem;">
                            {{ __('Turn Off Camera') }}
                        </button>
                        <button type="button" id="btn-switch-cam" class="button secondary small" style="padding: 0.3rem 0.65rem; font-size: 0.8rem;" title="{{ __('Switch front / back / USB webcam') }}">
                            {{ __('Switch Camera') }}
                        </button>
                        <button type="button" id="btn-scan-image" class="button secondary small" style="padding: 0.3rem 0.65rem; font-size: 0.8rem;" title="{{ __('Upload screenshot or QR ticket image from device') }}">
                            {{ __('Scan QR Image') }}
                        </button>
                        <input type="file" id="qr-file-input" accept="image/*" style="display: none;" />
                    </div>
                </div>

                {{-- Camera Viewport --}}
                <div class="scanner-viewport-wrapper">
                    <div id="qr-reader" style="width: 100%; border-radius: 8px; overflow: hidden; background: #0f172a; min-height: 310px; position: relative; display: flex; align-items: center; justify-content: center;">
                        {{-- Live WebRTC Video Feed --}}
                        <video id="webcam-video" autoplay playsinline muted style="width: 100%; height: 310px; object-fit: cover; display: block;"></video>
                        {{-- Hidden Canvas for QR Frame Decoding --}}
                        <canvas id="qr-canvas" style="display: none;"></canvas>

                        {{-- Camera Permission / Status Overlay --}}
                        <div id="camera-status-overlay" style="position: absolute; inset: 0; background: rgba(15, 23, 42, 0.92); color: #f8fafc; display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 1.5rem; text-align: center; z-index: 10;">
                            <div id="camera-status-icon" style="font-size: 2.75rem; margin-bottom: 0.5rem;">📷</div>
                            <h3 id="camera-status-text" style="margin: 0 0 0.4rem 0; font-size: 1.1rem; font-weight: 700; color: #fff;">
                                {{ __('Requesting Browser Camera Permission...') }}
                            </h3>
                            <p id="camera-status-sub" style="margin: 0 0 1rem 0; font-size: 0.85rem; color: #cbd5e1; max-width: 360px; line-height: 1.45;">
                                {{ __('Click "Allow" on the browser prompt to activate the QR scanner.') }}
                            </p>
                            <button type="button" id="btn-start-cam-overlay" class="button" style="background: #38bdf8; color: #0f172a; font-weight: 700; padding: 0.55rem 1.1rem; font-size: 0.85rem;">
                                {{ __('Activate & Open Camera Now') }}
                            </button>
                        </div>

                        {{-- Scanner Target Crosshairs / Scanline --}}
                        <div class="scanner-overlay" id="scanner-reticle" style="display: none;">
                            <div class="scanner-laser"></div>
                            <div class="scanner-corner tl"></div>
                            <div class="scanner-corner tr"></div>
                            <div class="scanner-corner bl"></div>
                            <div class="scanner-corner br"></div>
                        </div>
                    </div>
                </div>

                {{-- Manual & PDA Input Area --}}
                <div style="margin-top: 1rem;">
                    <form id="form-manual-checkin" style="display: flex; gap: 0.5rem;">
                        <input
                            type="text"
                            id="manual-code-input"
                            class="form-input"
                            placeholder="{{ __('Type / paste ticket code or scan with PDA...') }}"
                            autocomplete="off"
                            style="flex: 1; padding: 0.65rem 0.75rem; border-radius: 6px; border: 1px solid var(--line); background: var(--panel); color: var(--ink); font-family: monospace;"
                        />
                        <button type="submit" id="btn-submit-manual" class="button" style="white-space: nowrap; padding: 0.65rem 1.1rem;">
                            {{ __('Verify') }}
                        </button>
                    </form>
                    <p style="font-size: 0.75rem; margin-top: 0.4rem; color: var(--muted);">
                        {{ __('Tip: Supports live Camera, uploaded QR image, manual code entry, or PDA/barcode laser scanner.') }}
                    </p>
                </div>
            </div>

            {{-- Right Column: Big Result Card & Recent History --}}
            <div style="display: flex; flex-direction: column; gap: 1rem;">
                {{-- Live Big Validation Card --}}
                <div id="result-card" class="card result-card idle" style="padding: 1.5rem; text-align: center; transition: all 0.25s ease;">
                    <div id="result-icon" style="font-size: 3.5rem; line-height: 1; margin-bottom: 0.5rem;">
                        ⏳
                    </div>
                    <h2 id="result-title" style="margin: 0 0 0.5rem 0; font-size: 1.45rem; font-weight: 700;">
                        {{ __('Waiting for Ticket...') }}
                    </h2>
                    <p id="result-subtitle" class="muted" style="margin: 0 0 1rem 0; font-size: 0.95rem;">
                        {{ __('Point ticket QR at camera, upload QR image, or click Scan on the ticket manifest below.') }}
                    </p>

                    <div id="result-details" style="display: none; text-align: left; background: var(--surface); border: 1px solid var(--line); border-radius: 8px; padding: 1rem; margin-top: 0.5rem;">
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem; font-size: 0.85rem;">
                            <div>
                                <span class="muted" style="display: block; font-size: 0.72rem;">{{ __('ATTENDEE NAME') }}</span>
                                <strong id="res-holder" style="color: var(--ink); font-size: 1.05rem;">-</strong>
                            </div>
                            <div>
                                <span class="muted" style="display: block; font-size: 0.72rem;">{{ __('TICKET CATEGORY') }}</span>
                                <strong id="res-category" style="color: var(--ink); font-size: 1.05rem;">-</strong>
                            </div>
                            <div>
                                <span class="muted" style="display: block; font-size: 0.72rem;">{{ __('TICKET CODE') }}</span>
                                <code id="res-code" style="color: var(--ink); font-weight: 600;">-</code>
                            </div>
                            <div>
                                <span class="muted" style="display: block; font-size: 0.72rem;">{{ __('CHECK-IN TIME') }}</span>
                                <span id="res-time" style="color: var(--ink); font-weight: 600;">-</span>
                            </div>
                        </div>
                        <div id="res-mode-badge" style="margin-top: 0.75rem; padding-top: 0.5rem; border-top: 1px dashed var(--line); font-size: 0.75rem; color: var(--muted); display: flex; justify-content: space-between;">
                            <span id="res-mode-text">{{ __('Mode: Online Server') }}</span>
                            <span id="res-crypto-text">{{ __('HMAC: Verified') }}</span>
                        </div>
                    </div>
                </div>

                {{-- Recent Scans Stream --}}
                <div class="card" style="padding: 1.25rem; flex: 1;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.75rem;">
                        <h3 style="margin: 0; font-size: 1rem; font-weight: 600;">📋 {{ __('Recent Scan History') }}</h3>
                        <span id="history-count" class="muted" style="font-size: 0.8rem;">0 scan</span>
                    </div>

                    <div style="max-height: 240px; overflow-y: auto; border: 1px solid var(--line); border-radius: 6px;">
                        <table style="width: 100%; border-collapse: collapse; font-size: 0.8rem; text-align: left;">
                            <thead style="background: var(--surface); color: var(--muted); position: sticky; top: 0;">
                                <tr>
                                    <th style="padding: 0.5rem 0.6rem;">{{ __('Time') }}</th>
                                    <th style="padding: 0.5rem 0.6rem;">{{ __('Status') }}</th>
                                    <th style="padding: 0.5rem 0.6rem;">{{ __('Attendee / Tier') }}</th>
                                    <th style="padding: 0.5rem 0.6rem;">{{ __('Code') }}</th>
                                </tr>
                            </thead>
                            <tbody id="history-table-body">
                                <tr>
                                    <td colspan="4" style="padding: 1rem; text-align: center; color: var(--muted);">
                                        {{ __('No scan activity yet.') }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        {{-- Manifest Ticket List & 1-Click Test Table --}}
        <div class="card" style="margin-top: 1.5rem; padding: 1.25rem;">
            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 0.5rem; margin-bottom: 0.75rem;">
                <div>
                    <h3 style="margin: 0; font-size: 1.05rem; font-weight: 600;">🎫 {{ __('Event Ticket Manifest (Manual Check-In & Quick Test)') }}</h3>
                    <p class="muted" style="margin: 0.2rem 0 0 0; font-size: 0.82rem;">
                        {{ __('Paid tickets for selected event. Click "Scan This Ticket" to test check-in and HMAC signature verification directly.') }}
                    </p>
                </div>
            </div>

            <div style="max-height: 300px; overflow-y: auto; border: 1px solid var(--line); border-radius: 6px;">
                <table style="width: 100%; border-collapse: collapse; font-size: 0.83rem; text-align: left;">
                    <thead style="background: var(--surface); color: var(--muted); position: sticky; top: 0;">
                        <tr>
                            <th style="padding: 0.6rem 0.65rem;">{{ __('Ticket Code') }}</th>
                            <th style="padding: 0.6rem 0.65rem;">{{ __('Attendee') }}</th>
                            <th style="padding: 0.6rem 0.65rem;">{{ __('Category') }}</th>
                            <th style="padding: 0.6rem 0.65rem;">{{ __('Admission Status') }}</th>
                            <th style="padding: 0.6rem 0.65rem; text-align: right;">{{ __('Quick Action') }}</th>
                        </tr>
                    </thead>
                    <tbody id="manifest-tickets-body">
                        <tr>
                            <td colspan="5" style="padding: 1rem; text-align: center; color: var(--muted);">
                                {{ __('Loading ticket manifest...') }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </section>

    {{-- Scanner Component CSS --}}
    <style>
        .gate-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem;
        }
        @media (max-width: 900px) {
            .gate-grid {
                grid-template-columns: 1fr;
            }
            .gate-top-bar {
                grid-template-columns: 1fr !important;
            }
        }
        .gate-status-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            padding: 0.35rem 0.75rem;
            border-radius: 999px;
            font-size: 0.75rem;
            font-weight: 700;
            letter-spacing: 0.05em;
        }
        .gate-status-badge.online {
            background: rgba(16, 185, 129, 0.15);
            color: #059669;
            border: 1px solid rgba(16, 185, 129, 0.3);
        }
        .gate-status-badge.offline {
            background: rgba(245, 158, 11, 0.15);
            color: #d97706;
            border: 1px solid rgba(245, 158, 11, 0.3);
        }
        .gate-status-badge .dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: currentColor;
            display: inline-block;
            animation: pulse-dot 1.8s infinite;
        }
        @keyframes pulse-dot {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.4; transform: scale(0.85); }
        }

        /* Viewport Reticle */
        .scanner-viewport-wrapper {
            position: relative;
            width: 100%;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: inset 0 0 20px rgba(0,0,0,0.5);
        }
        .scanner-overlay {
            position: absolute;
            inset: 0;
            pointer-events: none;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .scanner-laser {
            position: absolute;
            width: 80%;
            height: 2px;
            background: linear-gradient(90deg, transparent, #38bdf8, #3b82f6, #38bdf8, transparent);
            box-shadow: 0 0 12px #38bdf8;
            top: 20%;
            animation: scan-sweep 2.2s infinite ease-in-out;
        }
        @keyframes scan-sweep {
            0% { top: 15%; opacity: 0.8; }
            50% { top: 85%; opacity: 1; }
            100% { top: 15%; opacity: 0.8; }
        }
        .scanner-corner {
            position: absolute;
            width: 32px;
            height: 32px;
            border-color: #38bdf8;
            border-style: solid;
        }
        .scanner-corner.tl { top: 20px; left: 20px; border-width: 4px 0 0 4px; }
        .scanner-corner.tr { top: 20px; right: 20px; border-width: 4px 4px 0 0; }
        .scanner-corner.bl { bottom: 20px; left: 20px; border-width: 0 0 4px 4px; }
        .scanner-corner.br { bottom: 20px; right: 20px; border-width: 0 4px 4px 0; }

        /* Big Result Cards */
        .result-card.idle {
            background: var(--panel);
            border: 2px dashed var(--line);
        }
        .result-card.success {
            background: rgba(16, 185, 129, 0.08);
            border: 2px solid #10b981;
            box-shadow: 0 0 25px rgba(16, 185, 129, 0.2);
            animation: bounce-card 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }
        .result-card.success #result-title {
            color: #059669;
        }
        .result-card.warning {
            background: rgba(239, 68, 68, 0.08);
            border: 2px solid #ef4444;
            box-shadow: 0 0 25px rgba(239, 68, 68, 0.2);
            animation: shake-card 0.35s ease;
        }
        .result-card.warning #result-title {
            color: #dc2626;
        }
        .result-card.caution {
            background: rgba(245, 158, 11, 0.08);
            border: 2px solid #f59e0b;
            box-shadow: 0 0 25px rgba(245, 158, 11, 0.2);
        }
        .result-card.caution #result-title {
            color: #d97706;
        }

        @keyframes bounce-card {
            0% { transform: scale(0.96); }
            50% { transform: scale(1.02); }
            100% { transform: scale(1); }
        }
        @keyframes shake-card {
            0%, 100% { transform: translateX(0); }
            20%, 60% { transform: translateX(-6px); }
            40%, 80% { transform: translateX(6px); }
        }
    </style>

    {{-- Local QR Decoder & Gate Scanner Logic (100% Self-Hosted, CSP-Safe, Offline-Ready) --}}
    <script src="{{ asset('jsqr.min.js') }}" defer></script>
    <script src="{{ asset('gate-scanner.js') }}" defer></script>
</x-layout>
