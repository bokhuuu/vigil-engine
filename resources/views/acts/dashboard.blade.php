<!DOCTYPE html>
<html lang="ka">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Vigil · აქტები</title>
    <style>
        #npb {
            position: fixed;
            top: 0;
            left: 0;
            height: 3px;
            width: 0;
            background: #2563eb;
            z-index: 999999;
            pointer-events: none;
        }
    </style>
    <script>
        (function() {
            function go() {
                var b = document.getElementById('npb');
                if (!b) return;
                b.style.transition = 'none';
                b.style.width = '0%';
                requestAnimationFrame(function() {
                    b.style.transition = 'width 10s cubic-bezier(0.1,0.02,0,1)';
                    b.style.width = '85%';
                });
            }
            go();
            document.addEventListener('click', function(e) {
                var a = e.target.closest('a[href]');
                if (!a) return;
                var h = a.getAttribute('href');
                if (!h || h === '#' || a.target === '_blank') return;
                go();
            });
            document.addEventListener('submit', function() {
                go();
            });
            window.addEventListener('pageshow', function() {
                var b = document.getElementById('npb');
                if (b) {
                    b.style.transition = 'none';
                    b.style.width = '0';
                }
            });
        })();
    </script>
    <link rel="icon"
        href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='%232563eb'><path d='M12 2L3 7v5c0 5.25 3.75 10.15 9 11.35C17.25 22.15 21 17.25 21 12V7L12 2z'/></svg>">
    <link rel="shortcut icon"
        href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='%232563eb'><path d='M12 2L3 7v5c0 5.25 3.75 10.15 9 11.35C17.25 22.15 21 17.25 21 12V7L12 2z'/></svg>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet"
        href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans:wght@400;500;600&family=IBM+Plex+Mono:wght@400;500&display=swap">
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'IBM Plex Sans', sans-serif;
            background: #f0f2f5;
            color: #1a1f36;
            font-size: 13px;
        }

        /* Header */
        .header {
            background: #1a1f36;
            padding: 12px 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 100;
            flex-wrap: wrap;
            gap: 10px;
        }

        .header-left {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .header-title {
            font-size: 15px;
            font-weight: 600;
            color: #fff;
        }

        .header-badge {
            font-size: 10px;
            color: #8892a4;
            background: #2d3448;
            padding: 2px 8px;
            border-radius: 10px;
        }

        .header-right {
            display: flex;
            align-items: center;
            gap: 16px;
            flex-wrap: wrap;
        }

        .stats-link {
            font-size: 11px;
            color: #7c9df7;
            text-decoration: none;
            white-space: nowrap;
        }

        .stats-link:hover {
            color: #adc4ff;
        }

        .timer {
            font-size: 11px;
            color: #8892a4;
            display: flex;
            align-items: center;
            gap: 6px;
            white-space: nowrap;
        }

        .timer-dot {
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background: #34d399;
            flex-shrink: 0;
            animation: pulse 2s infinite;
        }

        .timer-countdown {
            font-family: 'IBM Plex Mono', monospace;
            font-size: 10px;
            color: #4b5563;
        }

        @keyframes pulse {

            0%,
            100% {
                opacity: 1
            }

            50% {
                opacity: 0.4
            }
        }

        /* Stats */
        .stats-row {
            display: flex;
            gap: 10px;
            padding: 14px 24px;
            flex-wrap: wrap;
        }

        .stat-card {
            background: #fff;
            border: 1px solid #e4e8f0;
            border-radius: 8px;
            padding: 12px 18px;
            flex: 1;
            min-width: 120px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.06);
        }

        .stat-label {
            font-size: 10px;
            color: #8892a4;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            margin-bottom: 4px;
        }

        .stat-value {
            font-size: 24px;
            font-weight: 600;
            font-family: 'IBM Plex Mono', monospace;
            line-height: 1.1;
        }

        .stat-value.green {
            color: #059669;
        }

        .stat-value.red {
            color: #dc2626;
        }

        .stat-value.blue {
            color: #2563eb;
        }

        .progress-card {
            flex: 2;
            min-width: 180px;
        }

        .progress-bar-wrap {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-top: 6px;
        }

        .progress-bar-bg {
            flex: 1;
            height: 6px;
            background: #e4e8f0;
            border-radius: 3px;
            overflow: hidden;
        }

        .progress-bar-fill {
            height: 100%;
            background: #059669;
            border-radius: 3px;
            transition: width 0.5s;
        }

        .progress-pct {
            font-size: 12px;
            font-weight: 600;
            font-family: 'IBM Plex Mono', monospace;
            white-space: nowrap;
            color: #059669;
        }

        /* Filters */
        .filters-wrap {
            padding: 0 24px 10px;
            display: flex;
            gap: 6px;
            flex-wrap: wrap;
            align-items: center;
        }

        .filter-group {
            position: relative;
        }

        .filter-toggle {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 5px 12px;
            border-radius: 6px;
            font-size: 11px;
            font-family: 'IBM Plex Sans', sans-serif;
            cursor: pointer;
            border: 1px solid #e4e8f0;
            background: #fff;
            color: #4b5563;
            user-select: none;
            white-space: nowrap;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
        }

        .filter-toggle:hover {
            border-color: #2563eb;
            color: #2563eb;
        }

        .filter-toggle.has-active {
            border-color: #2563eb;
            color: #2563eb;
            background: #eff6ff;
            font-weight: 500;
        }

        .filter-dropdown {
            display: none;
            position: absolute;
            top: calc(100% + 4px);
            left: 0;
            right: auto;
            background: #fff;
            border: 1px solid #e4e8f0;
            border-radius: 8px;
            padding: 8px;
            z-index: 200;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12);
            min-width: 180px;
        }

        .filter-dropdown.open {
            display: block;
        }

        .filter-dropdown-label {
            font-size: 10px;
            color: #8892a4;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            margin-bottom: 6px;
            padding: 0 4px;
        }

        .filter-options {
            display: flex;
            flex-direction: column;
            gap: 1px;
        }

        .filter-opt {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 6px 8px;
            border-radius: 5px;
            font-size: 12px;
            color: #4b5563;
            text-decoration: none;
            white-space: nowrap;
        }

        .filter-opt:hover {
            background: #f4f5f7;
        }

        .filter-opt.active {
            background: #eff6ff;
            color: #2563eb;
            font-weight: 500;
        }

        .filter-opt-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            flex-shrink: 0;
        }

        .biznes-btn {
            padding: 5px 12px;
            border-radius: 6px;
            font-size: 11px;
            font-family: 'IBM Plex Sans', sans-serif;
            cursor: pointer;
            border: 1px solid #e4e8f0;
            background: #fff;
            color: #4b5563;
            text-decoration: none;
            white-space: nowrap;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
        }

        .biznes-btn:hover {
            border-color: #2563eb;
            color: #2563eb;
        }

        .biznes-btn.active {
            background: #2563eb;
            border-color: #2563eb;
            color: #fff;
        }

        .clear-btn {
            padding: 5px 12px;
            border-radius: 6px;
            font-size: 11px;
            border: 1px solid #fca5a5;
            background: #fff5f5;
            color: #dc2626;
            text-decoration: none;
            white-space: nowrap;
        }

        .clear-btn:hover {
            background: #fee2e2;
        }

        /* Legend */
        .legend {
            display: flex;
            gap: 16px;
            padding: 0 24px 8px;
            align-items: center;
            flex-wrap: wrap;
        }

        .legend-item {
            display: flex;
            align-items: center;
            gap: 5px;
            font-size: 10px;
            color: #8892a4;
        }

        /* Table */
        .table-wrap {
            margin: 0 24px 40px;
            background: #fff;
            border: 1px solid #e4e8f0;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.06);
        }

        .table-scroll {
            overflow-x: auto;
            overflow-y: auto;
            -webkit-overflow-scrolling: touch;
            max-height: calc(100vh - 255px);
        }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 700px;
        }

        thead tr {
            background: #f8f9fc;
            position: sticky;
            top: 0;
            z-index: 10;
        }

        th {
            padding: 6px 8px;
            text-align: left;
            font-size: 10px;
            font-weight: 600;
            color: #8892a4;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            white-space: nowrap;
            border-bottom: 1px solid #e4e8f0;
        }

        th.center,
        td.center {
            text-align: center;
        }

        td {
            padding: 6px 8px;
            border-bottom: 1px solid #f4f5f7;
            vertical-align: middle;
            position: relative;
        }

        tr:last-child td {
            border-bottom: none;
        }

        tr:hover td {
            background: #f8f9fc;
        }

        tr:nth-child(even) td {
            background: #fafbfd;
        }

        tr:nth-child(even):hover td {
            background: #f0f4ff;
        }

        .act-num {
            font-family: 'IBM Plex Mono', monospace;
            font-weight: 500;
            color: #2563eb;
            font-size: 12px;
            white-space: nowrap;
            width: 80px;
        }

        .act-link {
            color: inherit;
            text-decoration: none;
        }

        .act-link:hover {
            text-decoration: underline;
        }

        .badge {
            display: inline-block;
            font-size: 10px;
            font-weight: 500;
            padding: 1px 7px;
            border-radius: 10px;
            white-space: nowrap;
        }

        .badge.complete {
            color: #059669;
            background: #d1fae5;
        }

        .badge.incomplete {
            color: #dc2626;
            background: #fee2e2;
        }

        .badge.warn {
            color: #92400e;
            background: #fef3c7;
        }

        .col-status {
            width: 90px;
        }

        .doc-check {
            font-size: 12px;
            line-height: 1;
        }

        .kvleva-dot {
            display: inline-block;
            width: 8px;
            height: 8px;
            border-radius: 50%;
        }

        .biznes-count {
            display: inline-block;
            font-size: 10px;
            font-weight: 600;
            font-family: 'IBM Plex Mono', monospace;
            color: #2563eb;
            background: #dbeafe;
            border-radius: 10px;
            padding: 1px 6px;
        }

        .drive-link {
            color: #8892a4;
            text-decoration: none;
            font-size: 13px;
        }

        .drive-link:hover {
            color: #2563eb;
        }

        .folder-create-btn {
            font-size: 10px;
            font-family: 'IBM Plex Sans', sans-serif;
            padding: 2px 8px;
            border: 1px solid #d0d7de;
            border-radius: 10px;
            background: #fff;
            color: #4b5563;
            cursor: pointer;
            white-space: nowrap;
        }

        .folder-create-btn:hover:not(:disabled) {
            border-color: #2563eb;
            color: #2563eb;
        }

        .folder-create-btn:disabled {
            cursor: wait;
            opacity: 0.6;
        }

        .excel-enter-btn {
            font-size: 10px;
            font-family: 'IBM Plex Sans', sans-serif;
            padding: 2px 8px;
            border: 1px solid #6d28d9;
            border-radius: 10px;
            background: #f5f3ff;
            color: #6d28d9;
            cursor: pointer;
            white-space: nowrap;
        }

        .excel-enter-btn:hover:not(:disabled) {
            background: #ede9fe;
        }

        .excel-enter-btn:disabled {
            cursor: wait;
            opacity: 0.6;
        }

        .flash {
            padding: 8px 24px;
            font-size: 12px;
        }

        .flash.ok {
            background: #d1fae5;
            color: #065f46;
        }

        .flash.err {
            background: #fee2e2;
            color: #991b1b;
        }

        .empty {
            padding: 48px;
            text-align: center;
            color: #8892a4;
        }

        /* Tooltip */
        .tip {
            cursor: help;
            font-size: 12px;
            position: relative;
            display: inline-block;
        }

        .tip::after {
            content: attr(data-tip);
            position: fixed;
            background: #1a1f36;
            color: #fff;
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 11px;
            font-family: 'IBM Plex Sans', sans-serif;
            white-space: pre-wrap;
            max-width: 320px;
            z-index: 9999;
            pointer-events: none;
            opacity: 0;
            transition: opacity 0.15s;
            left: var(--tip-x, 0);
            top: var(--tip-y, 0);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
        }

        .tip:hover::after {
            opacity: 1;
        }

        /* Fixed footer */
        .footer-bar {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background: #1a1f36;
            border-top: 1px solid #2d3448;
            padding: 6px 24px;
            padding-bottom: calc(6px + env(safe-area-inset-bottom, 0px));
            font-size: 11px;
            color: #8892a4;
            z-index: 200;
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .footer-bar strong {
            color: #e2e8f0;
        }

        /* Mobile */
        @media (max-width: 640px) {
            .header {
                padding: 10px 16px;
            }

            .stats-row {
                padding: 10px 16px;
                gap: 8px;
            }

            .stat-card {
                padding: 10px 14px;
            }

            .stat-value {
                font-size: 20px;
            }

            .filters-wrap {
                padding: 0 16px 8px;
            }

            .legend {
                padding: 0 16px 8px;
            }

            .table-wrap {
                margin: 0 16px 40px;
            }

            .filter-dropdown {
                left: 0;
                right: auto;
            }

            #fg-missing .filter-dropdown,

            #fg-excel .filter-dropdown {
                left: auto !important;
                right: 0 !important;
            }
        }

        /* Column tints */
        .col-manual {
            background: #f0f7ff !important;
        }

        .col-auto {
            background: #f0faf4 !important;
        }

        .col-biznes {
            background: #f5f0ff !important;
        }

        .col-kvleva {
            background: #fff8f0 !important;
        }

        .col-drive {
            background: #f8f9fc !important;
        }

        tr:hover .col-manual {
            background: #e4f0ff !important;
        }

        tr:hover .col-auto {
            background: #e4f5ec !important;
        }

        tr:hover .col-biznes {
            background: #ede4ff !important;
        }

        tr:hover .col-kvleva {
            background: #fff0e0 !important;
        }

        tr:hover .col-drive {
            background: #eef0f5 !important;
        }

        /* Loading spinner */
        #page-loader {
            position: fixed;
            inset: 0;
            background: #f0f2f5;
            z-index: 99999;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-direction: column;
            gap: 16px;
        }

        body:not(.is-loading) #page-loader {
            display: none;
        }

        .spinner-ring {
            width: 40px;
            height: 40px;
            border: 3px solid #e4e8f0;
            border-top-color: #2563eb;
            border-radius: 50%;
            animation: spin 0.7s linear infinite;
        }

        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }

        .spinner-label {
            font-size: 11px;
            color: #8892a4;
            letter-spacing: 0.05em;
        }

        #pre-spinner {
            position: fixed;
            inset: 0;
            background: #f0f2f5;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        #pre-spinner-ring {
            width: 40px;
            height: 40px;
            border: 3px solid #e4e8f0;
            border-top-color: #2563eb;
            border-radius: 50%;
            animation: spin 0.7s linear infinite;
        }
    </style>
</head>

<body class="is-loading">
    <div id="npb"></div>
    <div id="upload-warning-banner"
        style="display:none;position:fixed;top:0;left:0;right:0;z-index:99999;background:#d97706;color:#fff;text-align:center;padding:10px;font-size:13px;font-weight:600;">
        ⚠️ ატვირთვა მიმდინარეობს — გვერდის განახლება შეაჩერებს პროცესს
    </div>
    <div id="pre-spinner">
        <div id="pre-spinner-ring"></div>
    </div>
    {{-- Page loader --}}
    <div id="page-loader">
        <div class="spinner-ring"></div>
        <div class="spinner-label">იტვირთება…</div>
    </div>

    {{-- Header --}}
    <div class="header">
        <div class="header-left">
            <span class="header-title">Vigil 🛡️</span>
            <span class="header-badge">Dashboard</span>
        </div>
        <div class="header-right">
            <div class="timer">
                <div class="timer-dot"
                    @if ($isStale) style="background:#f59e0b;animation:none;" @endif></div>
                <span>განახლდა {{ $fetchedAt->format('H:i') }}</span>
                <span class="timer-countdown" id="reload-countdown"></span>
            </div>
            <a href="{{ $statsUrl }}" target="_blank" class="stats-link">Airtable სტატისტიკა →</a>
            @if ($role === 'manager')
                <button id="rescan-btn" @if ($rescanRunning) data-rescan-running="1" @endif
                    @if (!$rescanRunning) onclick="openRescanModal()" @endif
                    style="font-size:11px;padding:4px 10px;border-radius:6px;border:1px solid #3d4f7c;background:#2d3448;color:#7c9df7;white-space:nowrap;{{ $rescanRunning ? 'cursor:not-allowed;opacity:0.6;' : 'cursor:pointer;' }}"
                    title="ყველა Drive საქაღალდის სრული გადამოწმება" @if ($rescanRunning) disabled @endif>
                    {{ $rescanRunning ? '⏳ მიმდინარეობს…' : '🔄 გადამოწმება' }}</button>
                <form id="rescan-form" method="POST" action="{{ route('dashboard.rescan') }}" style="display:none;">
                    @csrf
                </form>
                <button onclick="document.getElementById('tools-modal').style.display='flex'"
                    style="font-size:11px;padding:4px 10px;border-radius:6px;border:1px solid #3d4f7c;background:#2d3448;color:#7c9df7;white-space:nowrap;cursor:pointer;">
                    🛠️ Tools
                </button>
            @endif
            <form method="POST" action="{{ route('acts.logout') }}" style="display:inline;">
                @csrf
                <button type="submit"
                    style="font-size:11px;padding:4px 10px;border-radius:6px;border:1px solid #4b5563;background:transparent;color:#8892a4;white-space:nowrap;cursor:pointer;">
                    გასვლა
                </button>
            </form>
        </div>
    </div>

    @if ($isStale)
        <div style="background:#fef3c7;color:#92400e;padding:8px 24px;font-size:12px;border-bottom:1px solid #fcd34d;">
            ⚠️ Airtable მიუწვდომელია — ნაჩვენებია {{ $fetchedAt->format('H:i') }}-ის მონაცემები
        </div>
    @endif

    @if (session('success'))
        <div class="flash ok">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="flash err">{{ session('error') }}</div>
    @endif

    {{-- Stats --}}
    <div class="stats-row">
        <div class="stat-card">
            <div class="stat-label">სულ აქტები</div>
            <div class="stat-value blue">{{ $total }}</div>
        </div>
        <div class="stat-card">
            <div class="stat-label">დასრულებული</div>
            <div class="stat-value green">{{ $complete }}</div>
            <div style="margin-top:5px;display:flex;gap:10px;">
                <span style="font-size:10px;color:#059669;font-family:'IBM Plex Mono',monospace;"
                    title="სრულად დახურული">✅ {{ $closed }}</span>
                <span style="font-size:10px;color:#92400e;font-family:'IBM Plex Mono',monospace;"
                    title="დასრულებული, მაგრამ დოკუმენტები არასრულია">⚠️ {{ $incompleteDocs }}</span>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-label">მიმდინარე</div>
            <div class="stat-value red">{{ $incomplete }}</div>
        </div>
        <div class="stat-card progress-card">
            <div class="stat-label">პროგრესი</div>
            @php
                $pctClosed = $total > 0 ? round(($closed / $total) * 100, 1) : 0;
                $pctWarn = $total > 0 ? round(($incompleteDocs / $total) * 100, 1) : 0;
                $pctTotal = $total > 0 ? round(($complete / $total) * 100, 1) : 0;
            @endphp
            <div class="progress-bar-wrap">
                <div class="progress-bar-bg" style="display:flex;overflow:hidden;border-radius:3px;">
                    <div style="height:6px;background:#059669;width:{{ $pctClosed }}%;transition:width 0.5s;"></div>
                    <div style="height:6px;background:#f59e0b;width:{{ $pctWarn }}%;transition:width 0.5s;"></div>
                </div>
                <span
                    style="font-size:11px;font-weight:600;font-family:'IBM Plex Mono',monospace;white-space:nowrap;color:#374151;">
                    <span style="color:#059669;" title="სრულად დახურული">✅ {{ $pctClosed }}%</span>
                    &nbsp;<span style="color:#92400e;" title="დასრულებული, მაგრამ დოკუმენტები არასრულია">⚠️
                        {{ $pctWarn }}%</span>
                    &nbsp;<span style="color:#8892a4;">= {{ $pctTotal }}%</span>
                </span>
            </div>
        </div>
    </div>

    {{-- Filters --}}
    <div class="filters-wrap">
        <div style="position:relative;display:inline-flex;align-items:center;">
            <input type="text" id="act-search" placeholder="🔍 ACT ძებნა..."
                style="padding:5px 28px 5px 12px;border-radius:6px;border:1px solid #e4e8f0;font-size:11px;font-family:'IBM Plex Mono',monospace;width:150px;background:#fff;color:#1a1f36;box-shadow:0 1px 2px rgba(0,0,0,0.05);outline:none;">
            <span id="search-clear" onclick="clearSearch()"
                style="display:none;position:absolute;right:8px;cursor:pointer;color:#dc2626;font-size:16px;font-weight:600;line-height:1;">×</span>
        </div>

        {{-- სტატუსი --}}
        <div class="filter-group" id="fg-status">
            <div class="filter-toggle {{ $activeStatus ? 'has-active' : '' }}" onclick="toggleFilter('status')">
                სტატუსი
                {{ $activeStatus? '· ' .match ($activeStatus) {'incomplete_docs' => 'დასრულდა','closed' => 'დაიხურა','complete' => 'ორივე',default => 'მიმდინარე'}: '' }}
                <span>▼</span>
            </div>
            <div class="filter-dropdown" id="fd-status">
                <div class="filter-dropdown-label">სტატუსი</div>
                <div class="filter-options">
                    <a href="{{ request()->fullUrlWithQuery(['status' => null]) }}"
                        class="filter-opt {{ !$activeStatus ? 'active' : '' }}">ყველა</a>
                    <a href="{{ request()->fullUrlWithQuery(['status' => 'incomplete']) }}"
                        class="filter-opt {{ $activeStatus === 'incomplete' ? 'active' : '' }}">⏳ მიმდინარე</a>
                    <a href="{{ request()->fullUrlWithQuery(['status' => 'incomplete_docs']) }}"
                        class="filter-opt {{ $activeStatus === 'incomplete_docs' ? 'active' : '' }}">⚠️ დასრულდა</a>
                    <a href="{{ request()->fullUrlWithQuery(['status' => 'closed']) }}"
                        class="filter-opt {{ $activeStatus === 'closed' ? 'active' : '' }}">✅ დაიხურა</a>
                    <a href="{{ request()->fullUrlWithQuery(['status' => $activeStatus === 'complete' ? null : 'complete']) }}"
                        class="filter-opt {{ $activeStatus === 'complete' ? 'active' : '' }}">📋 დასრულდა +
                        დაიხურა</a>
                </div>
            </div>
        </div>

        {{-- ❌ აკლია --}}
        <div class="filter-group" id="fg-missing">
            <div class="filter-toggle {{ $activeMissing ? 'has-active' : '' }}" onclick="toggleFilter('missing')">
                ❌ აკლია {{ $activeMissing ? '· ' . $activeMissing : '' }} <span>▼</span>
            </div>
            <div class="filter-dropdown" id="fd-missing">
                <div class="filter-dropdown-label">რა აკლია</div>
                <div class="filter-options">
                    <a href="{{ request()->fullUrlWithQuery(['missing' => null]) }}"
                        class="filter-opt {{ !$activeMissing ? 'active' : '' }}">ყველა</a>
                    <a href="{{ request()->fullUrlWithQuery(['missing' => $activeMissing === 'scan' ? null : 'scan']) }}"
                        class="filter-opt {{ $activeMissing === 'scan' ? 'active' : '' }}">სკანი</a>
                    <a href="{{ request()->fullUrlWithQuery(['missing' => $activeMissing === 'photo' ? null : 'photo']) }}"
                        class="filter-opt {{ $activeMissing === 'photo' ? 'active' : '' }}">ფოტოები</a>
                    <a href="{{ request()->fullUrlWithQuery(['missing' => $activeMissing === 'signatures' ? null : 'signatures']) }}"
                        class="filter-opt {{ $activeMissing === 'signatures' ? 'active' : '' }}">ხელმოწერები</a>
                    <a href="{{ request()->fullUrlWithQuery(['missing' => $activeMissing === 'drawings' ? null : 'drawings']) }}"
                        class="filter-opt {{ $activeMissing === 'drawings' ? 'active' : '' }}">შიდა ნახაზები</a>
                    <a href="{{ request()->fullUrlWithQuery(['missing' => $activeMissing === 'boundary' ? null : 'boundary']) }}"
                        class="filter-opt {{ $activeMissing === 'boundary' ? 'active' : '' }}">გამიჯვნები</a>
                    <a href="{{ request()->fullUrlWithQuery(['missing' => $activeMissing === 'cost' ? null : 'cost']) }}"
                        class="filter-opt {{ $activeMissing === 'cost' ? 'active' : '' }}">ხარჯთაღრიცხვა</a>
                    <a href="{{ request()->fullUrlWithQuery(['missing' => $activeMissing === 'topo' ? null : 'topo']) }}"
                        class="filter-opt {{ $activeMissing === 'topo' ? 'active' : '' }}">ტოპო ნარგავები</a>
                </div>
            </div>
        </div>

        {{-- ✅ არის --}}
        <div class="filter-group" id="fg-has">
            <div class="filter-toggle {{ $activeHas ? 'has-active' : '' }}" onclick="toggleFilter('has')">
                ✅ არის {{ $activeHas ? '· ' . $activeHas : '' }} <span>▼</span>
            </div>
            <div class="filter-dropdown" id="fd-has">
                <div class="filter-dropdown-label">რა არის</div>
                <div class="filter-options">
                    <a href="{{ request()->fullUrlWithQuery(['has' => null]) }}"
                        class="filter-opt {{ !$activeHas ? 'active' : '' }}">ყველა</a>
                    <a href="{{ request()->fullUrlWithQuery(['has' => $activeHas === 'scan' ? null : 'scan']) }}"
                        class="filter-opt {{ $activeHas === 'scan' ? 'active' : '' }}">სკანი</a>
                    <a href="{{ request()->fullUrlWithQuery(['has' => $activeHas === 'photo' ? null : 'photo']) }}"
                        class="filter-opt {{ $activeHas === 'photo' ? 'active' : '' }}">ფოტოები</a>
                    <a href="{{ request()->fullUrlWithQuery(['has' => $activeHas === 'signatures' ? null : 'signatures']) }}"
                        class="filter-opt {{ $activeHas === 'signatures' ? 'active' : '' }}">ხელმოწერები</a>
                    <a href="{{ request()->fullUrlWithQuery(['has' => $activeHas === 'drawings' ? null : 'drawings']) }}"
                        class="filter-opt {{ $activeHas === 'drawings' ? 'active' : '' }}">შიდა ნახაზები</a>
                    <a href="{{ request()->fullUrlWithQuery(['has' => $activeHas === 'boundary' ? null : 'boundary']) }}"
                        class="filter-opt {{ $activeHas === 'boundary' ? 'active' : '' }}">გამიჯვნები</a>
                    <a href="{{ request()->fullUrlWithQuery(['has' => $activeHas === 'cost' ? null : 'cost']) }}"
                        class="filter-opt {{ $activeHas === 'cost' ? 'active' : '' }}">ხარჯთაღრიცხვა</a>
                    <a href="{{ request()->fullUrlWithQuery(['has' => $activeHas === 'topo' ? null : 'topo']) }}"
                        class="filter-opt {{ $activeHas === 'topo' ? 'active' : '' }}">ტოპო ნარგავები</a>
                </div>
            </div>
        </div>

        {{-- კვლევა --}}
        <div class="filter-group" id="fg-kvleva">
            <div class="filter-toggle {{ $activeKvleva ? 'has-active' : '' }}" onclick="toggleFilter('kvleva')">
                კვლევა {{ $activeKvleva ? '·' : '' }} <span>▼</span>
            </div>
            <div class="filter-dropdown" id="fd-kvleva">
                <div class="filter-dropdown-label">სოც-ეკონომიკური კვლევა</div>
                <div class="filter-options">
                    <a href="{{ request()->fullUrlWithQuery(['kvleva' => null]) }}"
                        class="filter-opt {{ !$activeKvleva ? 'active' : '' }}">ყველა</a>
                    @foreach ([
        '⏳ გამოსაკითხია' => ['label' => 'გამოსაკითხია', 'color' => '#c7381e'],
        '✅ შესრულებულია' => ['label' => 'შესრულებულია', 'color' => '#0a7c42'],
        '🔐 სახელმწიფო საკუთრება' => ['label' => 'სახელმწიფო საკუთრება', 'color' => '#b3bac5'],
        '🔢 არ გვაქვს ნომერი' => ['label' => 'არ გვაქვს ნომერი', 'color' => '#6b778c'],
        '📍 ადგილზე შეავსებს' => ['label' => 'ადგილზე შეავსებს', 'color' => '#d29922'],
        '🚫 უარი თქვა' => ['label' => 'უარი თქვა', 'color' => '#1a1f36'],
    ] as $value => $info)
                        <a href="{{ request()->fullUrlWithQuery(['kvleva' => $activeKvleva === $value ? null : $value]) }}"
                            class="filter-opt {{ $activeKvleva === $value ? 'active' : '' }}">
                            <span class="filter-opt-dot" style="background: {{ $info['color'] }};"></span>
                            {{ $info['label'] }}
                        </a>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- ბიზნესი --}}
        <div class="biznes-filters">
            <a href="{{ request()->fullUrlWithQuery(['biznes' => $activeBiznes === 'yes' ? null : 'yes']) }}"
                class="biznes-btn {{ $activeBiznes === 'yes' ? 'active' : '' }}">ბიზნესი</a>
        </div>

        {{-- Excel --}}
        @if ($role === 'manager')
            <div class="filter-group" id="fg-excel">
                <div class="filter-toggle {{ $activeExcel ? 'has-active' : '' }}" onclick="toggleFilter('excel')">
                    📊 Excel
                    {{ $activeExcel === 'entered' ? '· შეტანილია' : ($activeExcel === 'not_entered' ? '· არ არის შეტანილი' : '') }}
                    <span>▼</span>
                </div>
                <div class="filter-dropdown" id="fd-excel">
                    <div class="filter-dropdown-label">Excel სტატუსი</div>
                    <div class="filter-options">
                        <a href="{{ request()->fullUrlWithQuery(['excel' => null]) }}"
                            class="filter-opt {{ !$activeExcel ? 'active' : '' }}">ყველა</a>
                        <a href="{{ request()->fullUrlWithQuery(['excel' => $activeExcel === 'entered' ? null : 'entered']) }}"
                            class="filter-opt {{ $activeExcel === 'entered' ? 'active' : '' }}">📊 Excel-შია</a>
                        <a href="{{ request()->fullUrlWithQuery(['excel' => $activeExcel === 'not_entered' ? null : 'not_entered']) }}"
                            class="filter-opt {{ $activeExcel === 'not_entered' ? 'active' : '' }}">⏳ Excel-ში არ
                            არის</a>
                    </div>
                </div>
            </div>
        @endif

        @if ($hasActiveFilter)
            <a href="/dashboard" class="clear-btn">✕ გასუფთავება</a>
        @endif

    </div>

    {{-- Legend --}}
    <div class="legend">
        <div class="legend-item">✅ არის</div>
        <div class="legend-item">❌ აკლია</div>
        <div class="legend-item" style="color:#b3bac5;">· არ საჭიროებს</div>
        <div class="legend-item">❗ hover = შენიშვნა</div>
    </div>

    {{-- Table --}}
    <div class="table-wrap">
        <div class="table-scroll">
            <table>
                <thead>
                    <tr>
                        <th style="width:80px;">ნომერი</th>
                        <th class="col-status">სტატუსი</th>
                        <th class="center">შენ.</th>
                        <th class="center col-manual">სკანი</th>
                        <th class="center col-manual">ფოტო</th>
                        <th class="center col-manual">ხელმ.</th>
                        <th class="center col-auto">ნახაზი</th>
                        <th class="center col-auto">გამიჯ.</th>
                        <th class="center col-auto">ხარჯ.</th>
                        <th class="center col-auto">ტოპო</th>
                        <th class="center col-biznes">ბიზნ.</th>
                        <th class="center col-kvleva">კვლევა</th>
                        <th class="center col-drive">Drive</th>
                    </tr>
                </thead>
                <tbody id="act-tbody">
                    @forelse($acts as $act)
                        @php
                            $isComplete = ($act['🚦სტატუსი'] ?? '') === '✅ დასრულებული';
                            $missing = $act['❗რა აკლია აქტს'] ?? null;
                            $folder = $act['💼 საქაღალდე'] ?? null;
                            $required = $act['_required'] ?? [];
                            $biznesCount = $act['_biznes_count'] ?? 0;
                            $kvleva = $act['_kvleva'] ?? null;
                            $kvlevaColor = $kvlevaColors[$kvleva] ?? '#e1e4e8';
                        @endphp
                        <tr>
                            <td class="act-num">
                                <a href="{{ $act['_url'] }}" target="_blank" rel="noopener"
                                    class="act-link">{{ $act['🔖 აქტის ნომერი'] ?? '—' }}</a>
                            </td>
                            <td>
                                @php
                                    $docsIncomplete =
                                        $isComplete &&
                                        (($act['_scan'] ?? null) !== 'full' ||
                                            collect($required)->contains(
                                                fn($field) => empty($act['_drive_doc'][$field] ?? false),
                                            ));
                                @endphp
                                <span
                                    class="badge {{ $isComplete ? ($docsIncomplete ? 'warn' : 'complete') : 'incomplete' }}"
                                    @if ($docsIncomplete) title="დასრულებული მაგრამ დოკუმენტები არასრულია" @endif>
                                    {{ $isComplete ? ($docsIncomplete ? '⚠️ დასრულდა' : 'დაიხურა') : 'მიმდინარე' }}
                                </span>
                            </td>
                            <td class="center">
                                @if ($missing)
                                    <span class="tip" data-tip="{{ $missing }}">❗</span>
                                @else
                                    <span style="color:#d0d7de;">—</span>
                                @endif
                            </td>
                            {{-- სკანი --}}
                            <td class="center col-manual">
                                @php $scanState = $act['_scan'] ?? null; @endphp
                                @if ($scanState === 'full')
                                    <span class="doc-check" title="სკანი ატვირთულია">✅</span>
                                @elseif ($scanState === 'grace')
                                    <span class="doc-check" title="სკანი — წაშლის მოლოდინში">⏳</span>
                                @elseif ($scanState === 'removed')
                                    <span class="doc-check" title="სკანი — ფაილი წაიშალა">❌</span>
                                @else
                                    <span class="doc-check" title="სკანი — არ არის ატვირთული">❌</span>
                                @endif
                            </td>
                            @foreach ($docFields as $label => $cfg)
                                @php $colClass = in_array($cfg['airtable'], ['📥 ფოტოები', '📥 ხელმოწერები']) ? 'col-manual' : 'col-auto'; @endphp
                                <td class="center {{ $colClass }}">
                                    @if ($cfg['airtable'] === '📥 ხარჯთაღრიცხვა')
                                        @if ($act['_excel_entered'])
                                            <span class="doc-check" title="Excel-ში შეტანილია">✅</span>
                                        @elseif (($act['_drive_doc']['📥 ხარჯთაღრიცხვა'] ?? false) && $role === 'manager')
                                            <button class="excel-enter-btn" data-record-id="{{ $act['_id'] }}"
                                                data-act-number="{{ $act['🔖 აქტის ნომერი'] ?? '' }}"
                                                onclick="openExcelModal(this)" title="Excel-ში შეტანა">📊
                                                შეტანა</button>
                                        @elseif (in_array($cfg['airtable'], $required))
                                            <span
                                                class="doc-check">{{ $act['_drive_doc'][$cfg['airtable']] ?? false ? '✅' : '❌' }}</span>
                                        @else
                                            <span style="color:#d0d7de;font-size:16px;">·</span>
                                        @endif
                                    @elseif (in_array($cfg['airtable'], $required))
                                        <span
                                            class="doc-check">{{ $act['_drive_doc'][$cfg['airtable']] ?? false ? '✅' : '❌' }}</span>
                                    @else
                                        <span style="color:#d0d7de;font-size:16px;">·</span>
                                    @endif
                                </td>
                            @endforeach
                            <td class="center col-biznes">
                                @if ($biznesCount > 0)
                                    <span class="biznes-count">{{ $biznesCount }}</span>
                                @else
                                    <span style="color:#d0d7de;">—</span>
                                @endif
                            </td>
                            <td class="center col-kvleva">
                                @if ($kvleva)
                                    <span class="kvleva-dot" style="background: {{ $kvlevaColor }};"
                                        title="{{ $kvleva }}"></span>
                                @else
                                    <span style="color:#d0d7de;">—</span>
                                @endif
                            </td>
                            <td class="center col-drive">
                                @if ($folder)
                                    <a href="{{ $folder }}" target="_blank" class="drive-link">📁</a>
                                    @if ($role !== 'viewer')
                                        <button class="upload-btn" data-act-id="{{ $act['_id'] }}"
                                            data-act-number="{{ $act['🔖 აქტის ნომერი'] ?? '' }}"
                                            title="ფაილების ატვირთვა"
                                            style="cursor:pointer;background:none;border:none;padding:0;margin-left:6px;">⬆️</button>
                                    @endif
                                @elseif ($role === 'manager')
                                    <form method="POST" action="{{ route('dashboard.folder', $act['_id']) }}"
                                        onsubmit="const b = this.querySelector('button'); b.disabled = true; b.textContent = '…';">
                                        @csrf
                                        <button type="submit" class="folder-create-btn"
                                            title="Drive საქაღალდის შექმნა">+ შექმნა</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ count($docFields) + 7 }}" class="empty">ACT-ები ვერ მოიძებნა</td>
                        </tr>
                    @endforelse
                    <tr id="search-empty-row" style="display:none;">
                        <td colspan="{{ count($docFields) + 7 }}" class="empty">
                            🔍 ACT ვერ მოიძებნა
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>


    <div class="footer-bar">
        ნაჩვენები: <strong id="visible-count">{{ $acts->count() }}</strong>
        &nbsp;·&nbsp;
        <a id="prev-page" href="#" style="color:#7c9df7;text-decoration:none;font-size:11px;display:none;">←
            წინა</a>
        <span id="page-info" style="font-size:11px;color:#8892a4;"></span>
        <a id="next-page" href="#"
            style="color:#7c9df7;text-decoration:none;font-size:11px;display:none;">შემდეგი →</a>
    </div>

    <script>
        // ── Filter dropdowns ────────────────────────────────────────────
        function toggleFilter(name) {
            const dropdown = document.getElementById('fd-' + name);
            const isOpen = dropdown.classList.contains('open');
            document.querySelectorAll('.filter-dropdown').forEach(d => d.classList.remove('open'));
            if (!isOpen) dropdown.classList.add('open');
        }

        document.addEventListener('click', (e) => {
            if (!e.target.closest('.filter-group')) {
                document.querySelectorAll('.filter-dropdown').forEach(d => d.classList.remove('open'));
            }
        });

        // ── Tooltip positioning ─────────────────────────────────────────
        document.querySelectorAll('.tip').forEach(el => {
            el.addEventListener('mousemove', (e) => {
                el.style.setProperty('--tip-x', (e.clientX + 12) + 'px');
                el.style.setProperty('--tip-y', (e.clientY + 16) + 'px');
            });
        });

        // ── Search + Pagination ─────────────────────────────────────────
        const PAGE_SIZE = 100;
        const allRows = Array.from(document.querySelectorAll('#act-tbody tr:not(#search-empty-row)'));
        let filteredRows = [...allRows];
        let currentPage = 0;

        const emptyRow = document.getElementById('search-empty-row');

        function renderPage() {
            const start = currentPage * PAGE_SIZE;
            const end = start + PAGE_SIZE;

            allRows.forEach(r => r.style.display = 'none');
            filteredRows.slice(start, end).forEach(r => r.style.display = '');

            if (emptyRow) {
                emptyRow.style.display = filteredRows.length === 0 ? '' : 'none';
            }

            const total = filteredRows.length;
            const totalPages = Math.ceil(total / PAGE_SIZE);
            const showing = Math.min(end, total) - start;

            document.getElementById('visible-count').textContent = total;

            const pageInfo = document.getElementById('page-info');
            const prevBtn = document.getElementById('prev-page');
            const nextBtn = document.getElementById('next-page');

            if (totalPages > 1) {
                pageInfo.textContent = ' გვ. ' + (currentPage + 1) + '/' + totalPages + ' ';
                prevBtn.style.display = currentPage > 0 ? 'inline' : 'none';
                nextBtn.style.display = currentPage < totalPages - 1 ? 'inline' : 'none';
            } else {
                pageInfo.textContent = '';
                prevBtn.style.display = 'none';
                nextBtn.style.display = 'none';
            }
        }

        function applySearch(query) {
            const q = query.toLowerCase().trim().replace(/\s*-\s*/g, '-');
            filteredRows = allRows.filter(row => {
                const actNum = row.querySelector('.act-num');
                return !q || (actNum && actNum.textContent.toLowerCase().trim().startsWith(q));
            });
            currentPage = 0;
            renderPage();
        }

        const searchInput = document.getElementById('act-search');
        const searchClear = document.getElementById('search-clear');

        searchInput.addEventListener('input', function() {
            searchClear.style.display = this.value ? 'block' : 'none';
            sessionStorage.setItem('_searchQuery', this.value);
            applySearch(this.value);
        });

        function clearSearch() {
            searchInput.value = '';
            searchClear.style.display = 'none';
            sessionStorage.removeItem('_searchQuery');
            applySearch('');
        }

        document.getElementById('prev-page').addEventListener('click', (e) => {
            e.preventDefault();
            if (currentPage > 0) {
                currentPage--;
                renderPage();
                document.querySelector('.table-scroll').scrollTo(0, 0);
            }
        });

        document.getElementById('next-page').addEventListener('click', (e) => {
            e.preventDefault();
            if (currentPage < Math.ceil(filteredRows.length / PAGE_SIZE) - 1) {
                currentPage++;
                renderPage();
                document.querySelector('.table-scroll').scrollTo(0, 0);
            }
        });

        // ── Initial render ──────────────────────────────────────────────
        (function() {
            const saved = sessionStorage.getItem('_searchQuery');
            if (saved) {
                searchInput.value = saved;
                searchClear.style.display = 'block';
                applySearch(saved);
            } else {
                renderPage();
            }
        })();

        // ── Silent auto-reload ──────────────────────────────────────────
        // Keeps an open dashboard fresh; the shown time only changes
        // when new data is actually fetched.
        const _reloadMs = {{ $cacheMinutes }} * 60 * 1000;
        const _stored = sessionStorage.getItem('_reloadAt');
        const _reloadAt = (_stored && parseInt(_stored) > Date.now()) ?
            parseInt(_stored) :
            (() => {
                const t = Date.now() + _reloadMs;
                sessionStorage.setItem('_reloadAt', t);
                return t;
            })();
        const _countdownEl = document.getElementById('reload-countdown');

        function _hasActiveFilters() {
            return window._uploadInProgress ||
                document.querySelector('.filter-toggle.has-active') !== null ||
                (searchInput && searchInput.value.trim() !== '');
        }

        function _updateCountdown() {
            if (_hasActiveFilters()) {
                _countdownEl.textContent = '';
                return;
            }
            const left = Math.max(0, _reloadAt - Date.now());
            const m = Math.floor(left / 60000);
            const s = Math.floor((left % 60000) / 1000);
            _countdownEl.textContent = m + ':' + String(s).padStart(2, '0') + '-ში';
        }

        _updateCountdown();
        setInterval(_updateCountdown, 1000);

        setInterval(() => {
            if (!_hasActiveFilters() && Date.now() >= _reloadAt && !window._uploadInProgress) {
                window.location.reload();
            }
        }, 5000);
    </script>

    {{-- Upload modal --}}
    <div id="upload-modal"
        style="display:none;position:fixed;inset:0;z-index:9999;background:rgba(0,0,0,.55);align-items:center;justify-content:center;">
        <div
            style="background:#fff;border-radius:10px;padding:28px 32px;min-width:340px;max-width:460px;width:90%;box-shadow:0 8px 32px rgba(0,0,0,.25);position:relative;">
            <button onclick="closeUploadModal()"
                style="position:absolute;top:12px;right:16px;background:none;border:none;font-size:1.4rem;cursor:pointer;color:#666;">✕</button>
            <h3 style="margin:0 0 18px;font-size:1.05rem;">📁 ფაილების ატვირთვა</h3>
            <label style="display:block;margin-bottom:6px;font-size:.85rem;color:#555;">საქაღალდე</label>
            <select id="upload-folder"
                style="width:100%;padding:8px 10px;border:1px solid #ccc;border-radius:6px;font-size:.9rem;margin-bottom:14px;">
                <option value="">იტვირთება…</option>
            </select>
            <label style="display:block;margin-bottom:6px;font-size:.85rem;color:#555;">ფაილები</label>
            <input type="file" id="upload-files" multiple style="display:block;width:100%;margin-bottom:10px;">
            <label style="display:block;margin-bottom:6px;font-size:.85rem;color:#555;">ან მთელი საქაღალდე</label>
            <input type="file" id="upload-folder-input" webkitdirectory multiple
                style="display:block;width:100%;margin-bottom:18px;">
            <div id="upload-progress" style="display:none;margin-bottom:12px;font-size:.85rem;color:#555;"></div>
            <button onclick="submitUpload()"
                style="width:100%;padding:10px;background:#1a56db;color:#fff;border:none;border-radius:6px;font-size:.95rem;cursor:pointer;font-weight:600;">ატვირთვა</button>
        </div>
    </div>

    <script>
        // ── Page loader ─────────────────────────────────────────────
        document.addEventListener('DOMContentLoaded', () => {
            document.getElementById('pre-spinner')?.remove();
            document.body.classList.remove('is-loading');

            // Check if any upload is already running on another device
            @if ($uploadScansRunning)
                pollScansUploadStatus();
            @endif
            @if ($uploadPhotosRunning)
                pollPhotosUploadStatus();
            @endif
            @if (Cache::has('upload_drawings_running'))
                pollDrawingsUploadStatus();
            @endif
        });

        window.addEventListener('beforeunload', function(e) {
            if (window._uploadInProgress) {
                e.preventDefault();
                e.returnValue = '';
            }
        });
        // ── Rescan modal ────────────────────────────────────────────
        function openRescanModal() {
            document.getElementById('rescan-modal').style.display = 'flex';
        }

        function closeRescanModal() {
            document.getElementById('rescan-modal').style.display = 'none';
        }

        function submitRescan() {
            closeRescanModal();
            const btn = document.getElementById('rescan-btn');
            btn.disabled = true;
            btn.textContent = '⏳ მიმდინარეობს…';
            btn.style.opacity = '0.6';
            btn.style.cursor = 'not-allowed';
            document.getElementById('rescan-form').submit();
        }
        document.getElementById('rescan-modal')?.addEventListener('click', e => {
            if (e.target === document.getElementById('rescan-modal')) closeRescanModal();
        });

        let _uploadActId = null;

        document.querySelectorAll('.upload-btn').forEach(btn => {
            btn.addEventListener('click', e => {
                e.stopPropagation();
                openUploadModal(btn.dataset.actId, btn.dataset.actNumber);
            });
        });

        async function openUploadModal(actId, actNumber) {
            _uploadActId = actId;
            const modal = document.getElementById('upload-modal');
            const select = document.getElementById('upload-folder');
            const files = document.getElementById('upload-files');
            const progress = document.getElementById('upload-progress');

            files.value = '';
            progress.style.display = 'none';
            progress.textContent = '';
            select.innerHTML = '<option value="">იტვირთება…</option>';
            modal.style.display = 'flex';

            modal.querySelector('h3').textContent = '📁 ACT ' + actNumber + ' — ფაილების ატვირთვა';

            try {
                const res = await fetch(`/upload/folders?act=${encodeURIComponent(actId)}`);
                const data = await res.json();
                if (!res.ok) throw new Error(data.error ?? 'შეცდომა');
                select.innerHTML = data.folders.length ?
                    data.folders.map(f => `<option value="${f}">${f}</option>`).join('') :
                    '<option value="">საქაღალდე ვერ მოიძებნა</option>';
            } catch (err) {
                select.innerHTML = `<option value="">შეცდომა: ${err.message}</option>`;
            }
        }

        function closeUploadModal() {
            document.getElementById('upload-modal').style.display = 'none';
            _uploadActId = null;
            window._uploadInProgress = false;
        }

        async function submitUpload() {
            const select = document.getElementById('upload-folder');
            const filesInput = document.getElementById('upload-files');
            const folderInput = document.getElementById('upload-folder-input');
            const progress = document.getElementById('upload-progress');
            const folder = select.value;
            const folderFiles = Array.from(folderInput.files || []);
            const files = [
                ...Array.from(filesInput.files || []).map(f => ({
                    file: f,
                    path: f.name
                })),
                ...folderFiles.map(f => {
                    const parts = (f.webkitRelativePath || f.name).split('/');
                    const path = parts.length > 1 ? parts.slice(1).join('/') : parts[0];
                    return {
                        file: f,
                        path
                    };
                }),
            ];

            if (!folder) {
                showToast('⚠️ აირჩიე საქაღალდე');
                return;
            }
            if (!files.length) {
                showToast('⚠️ აირჩიე ფაილ(ებ)ი');
                return;
            }

            window._uploadInProgress = true;
            progress.style.display = 'block';

            const CHUNK = 5;
            const total = files.length;
            let uploaded = [];
            let failed = [];

            for (let i = 0; i < total; i += CHUNK) {
                const chunk = files.slice(i, i + CHUNK);
                progress.textContent = `⏳ იტვირთება… ${Math.min(i + CHUNK, total)} / ${total}`;

                const form = new FormData();
                form.append('act_id', _uploadActId);
                form.append('folder', folder);
                for (const item of chunk) {
                    form.append('files[]', item.file);
                    form.append('paths[]', item.path);
                }

                try {
                    const res = await fetch('/upload', {
                        method: 'POST',
                        body: form
                    });
                    const data = await res.json();
                    if (!res.ok) throw new Error(data.error ?? 'სერვერის შეცდომა');
                    if (data.uploaded) uploaded = uploaded.concat(data.uploaded);
                    if (data.failed) failed = failed.concat(data.failed);
                } catch (err) {
                    failed = failed.concat(chunk.map(item => item.file.name));
                }
            }

            filesInput.value = '';
            document.getElementById('upload-folder-input').value = '';

            if (failed.length) {
                progress.textContent = `✅ ატვირთულია: ${uploaded.length}  ❌ ვერ ატვირთა: ${failed.length}`;
            } else {
                progress.textContent = `✅ ${uploaded.length} ფაილი ატვირთულია`;
            }
            setTimeout(closeUploadModal, 1500);
        }

        document.getElementById('upload-modal').addEventListener('click', e => {
            if (e.target === document.getElementById('upload-modal')) closeUploadModal();
        });
    </script>



    {{-- Rescan confirm modal --}}
    <div id="rescan-modal"
        style="display:none;position:fixed;inset:0;z-index:9999;background:rgba(0,0,0,.55);align-items:center;justify-content:center;">
        <div
            style="background:#fff;border-radius:10px;padding:28px 32px;max-width:400px;width:90%;box-shadow:0 8px 32px rgba(0,0,0,.25);">
            <h3 style="margin:0 0 12px;font-size:1rem;color:#1a1f36;">🔄 სრული Drive გადამოწმება</h3>
            <p style="font-size:13px;color:#4b5563;margin:0 0 20px;line-height:1.6;">ყველა Drive საქაღალდის სრული
                გადამოწმება დაახლოებით <strong>30–60 წუთი</strong> გრძელდება. გაგრძელება?</p>
            <div style="display:flex;gap:10px;justify-content:flex-end;">
                <button onclick="closeRescanModal()"
                    style="padding:8px 18px;border-radius:6px;border:1px solid #e4e8f0;background:#fff;color:#4b5563;font-size:13px;cursor:pointer;">გაუქმება</button>
                <button onclick="submitRescan()"
                    style="padding:8px 18px;border-radius:6px;border:none;background:#2563eb;color:#fff;font-size:13px;font-weight:600;cursor:pointer;">დადასტურება</button>
            </div>
        </div>
    </div>


    {{-- Tools modal --}}
    <div id="tools-modal"
        style="display:none;position:fixed;inset:0;z-index:9999;background:rgba(0,0,0,.55);align-items:center;justify-content:center;">
        <div
            style="background:#fff;border-radius:10px;padding:20px 18px;min-width:0;max-width:420px;width:95%;box-shadow:0 8px 32px rgba(0,0,0,.25);position:relative;">
            <button onclick="document.getElementById('tools-modal').style.display='none'"
                style="position:absolute;top:12px;right:16px;background:none;border:none;font-size:1.4rem;cursor:pointer;color:#666;">✕</button>
            <h3 style="margin:0 0 16px;font-size:1rem;color:#1a1f36;">🛠️ Bulk Tools</h3>

            {{-- Tab switcher --}}
            <div
                style="display:flex;gap:0;border:1px solid #e4e8f0;border-radius:7px;overflow:hidden;margin-bottom:16px;">
                <button id="tab-btn-download" onclick="switchTab('download')"
                    style="flex:1;padding:7px 0;font-size:11px;font-weight:600;font-family:'IBM Plex Sans',sans-serif;border:none;cursor:pointer;transition:background .15s,color .15s;background:#1a1f36;color:#7c9df7;">
                    ⬇️ გადმოწერა
                </button>
                <button id="tab-btn-upload" onclick="switchTab('upload')"
                    style="flex:1;padding:7px 0;font-size:11px;font-weight:600;font-family:'IBM Plex Sans',sans-serif;border:none;border-left:1px solid #e4e8f0;cursor:pointer;transition:background .15s,color .15s;background:#f8f9fc;color:#8892a4;">
                    ⬆️ ატვირთვა
                </button>
            </div>

            {{-- Downloads tab --}}
            <div id="tab-download">
                <div style="border:1px solid #e4e8f0;border-radius:8px;padding:14px 16px;margin-bottom:10px;">
                    <div style="font-size:12px;font-weight:600;color:#1a1f36;margin-bottom:4px;">📦 სკანების გადმოწერა
                    </div>
                    <div style="font-size:11px;color:#8892a4;margin-bottom:10px;">ACT range (სურვილისამებრ): მაგ.
                        <code>1-100</code>
                    </div>
                    <div style="display:flex;gap:8px;align-items:center;margin-bottom:10px;">
                        <input id="scan-range-input" type="text" placeholder="ყველა" maxlength="20"
                            style="font-size:11px;padding:4px 8px;border-radius:5px;border:1px solid #d1d5db;width:100px;font-family:'IBM Plex Mono',monospace;">
                    </div>
                    <div id="scan-status">
                        @if ($downloadScanRunning)
                            <div style="font-size:11px;color:#8892a4;">
                                ⏳ მიმდინარეობს…
                                @if ($downloadScanProgress)
                                    <span style="font-family:'IBM Plex Mono',monospace;color:#4b5563;">
                                        {{ $downloadScanProgress['done'] }} / {{ $downloadScanProgress['total'] }}
                                        ACT
                                    </span>
                                @endif
                            </div>
                        @elseif ($downloadScanReady)
                            <div style="display:flex;gap:8px;align-items:center;">
                                <a href="{{ route('dashboard.download.file', ['range' => $scanSlug]) }}"
                                    style="display:inline-block;font-size:11px;padding:6px 14px;border-radius:6px;border:1px solid #3d7c4f;background:#2d4438;color:#6fcf97;text-decoration:none;">
                                    ⬇️ ZIP გადმოწერა
                                </a>
                                <button onclick="resetScanDownload(event)"
                                    style="font-size:11px;padding:6px 10px;border-radius:6px;border:1px solid #6b778c;background:transparent;color:#8892a4;cursor:pointer;">
                                    ↺ ახალი
                                </button>
                            </div>
                        @else
                            <button onclick="startScanDownload()"
                                style="font-size:11px;padding:6px 14px;border-radius:6px;border:1px solid #3d4f7c;background:#2d3448;color:#7c9df7;cursor:pointer;">
                                📦 დაწყება
                            </button>
                            <form id="download-scan-form" method="POST" action="{{ route('dashboard.download') }}"
                                style="display:none;">
                                @csrf
                                <input type="hidden" name="range" id="scan-range-hidden" value="all">
                            </form>
                        @endif
                    </div>
                </div>

                <div style="border:1px solid #e4e8f0;border-radius:8px;padding:14px 16px;margin-bottom:10px;">
                    <div style="font-size:12px;font-weight:600;color:#1a1f36;margin-bottom:4px;">📸 ფოტოების გადმოწერა
                    </div>
                    <div style="font-size:11px;color:#8892a4;margin-bottom:10px;">ACT range (სავალდებულო): მაგ.
                        <code>1-100</code>
                    </div>
                    <div style="display:flex;gap:8px;align-items:center;margin-bottom:10px;">
                        <input id="photos-range-input" type="text" placeholder="მაგ. 1-100" maxlength="20"
                            style="font-size:11px;padding:4px 8px;border-radius:5px;border:1px solid #d1d5db;width:100px;font-family:'IBM Plex Mono',monospace;">
                    </div>
                    <div id="photos-status">
                        @if ($downloadPhotosRunning)
                            <div style="font-size:11px;color:#8892a4;">
                                ⏳ მიმდინარეობს…
                                @if ($downloadPhotosProgress)
                                    <span style="font-family:'IBM Plex Mono',monospace;color:#4b5563;">
                                        {{ $downloadPhotosProgress['done'] }} /
                                        {{ $downloadPhotosProgress['total'] }} ACT
                                    </span>
                                @endif
                            </div>
                        @elseif ($downloadPhotosReady)
                            <div style="display:flex;gap:8px;align-items:center;">
                                <a href="{{ route('dashboard.download.photos.file', ['range' => $photosSlug]) }}"
                                    style="display:inline-block;font-size:11px;padding:6px 14px;border-radius:6px;border:1px solid #3d7c4f;background:#2d4438;color:#6fcf97;text-decoration:none;">
                                    ⬇️ ZIP გადმოწერა
                                </a>
                                <button onclick="resetPhotosDownload(event)"
                                    style="font-size:11px;padding:6px 10px;border-radius:6px;border:1px solid #6b778c;background:transparent;color:#8892a4;cursor:pointer;">
                                    ↺ ახალი
                                </button>
                            </div>
                        @else
                            <button onclick="startPhotosDownload()"
                                style="font-size:11px;padding:6px 14px;border-radius:6px;border:1px solid #3d4f7c;background:#2d3448;color:#7c9df7;cursor:pointer;">
                                📸 დაწყება
                            </button>
                            <div id="photos-range-error"
                                style="display:none;font-size:11px;color:#f87171;margin-top:4px;">range სავალდებულოა
                                (მაგ. 1-100)</div>
                            <form id="download-photos-form" method="POST"
                                action="{{ route('dashboard.download.photos') }}" style="display:none;">
                                @csrf
                                <input type="hidden" name="range" id="photos-range-hidden" value="">
                            </form>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Uploads tab --}}
            <div id="tab-upload" style="display:none;">
                <div style="border:1px solid #e4e8f0;border-radius:8px;padding:14px 16px;margin-bottom:10px;">
                    <div style="font-size:12px;font-weight:600;color:#1a1f36;margin-bottom:4px;">📤 სკანების ატვირთვა
                    </div>
                    <div style="font-size:11px;color:#8892a4;margin-bottom:6px;">ZIP ფაილი სკანებით</div>
                    <div style="margin-bottom:10px;">
                        <input type="file" id="scans-zip-input" accept=".zip"
                            style="font-size:11px;width:100%;margin-bottom:0;">
                    </div>
                    <div style="margin-bottom:10px;">
                        <label
                            style="font-size:11px;color:#8892a4;display:flex;align-items:center;gap:6px;cursor:pointer;">
                            <input type="checkbox" id="scans-replace-mode">
                            🔄 არსებული ჩაანაცვლე (replace)
                        </label>
                    </div>
                    <div id="scans-upload-status">
                        @if ($uploadScansRunning)
                            <div style="font-size:11px;color:#8892a4;">⏳ მიმდინარეობს…</div>
                        @else
                            <button onclick="startScansUpload()"
                                style="font-size:11px;padding:6px 14px;border-radius:6px;border:1px solid #3d4f7c;background:#2d3448;color:#7c9df7;cursor:pointer;">
                                📤 გაშვება
                            </button>
                        @endif
                    </div>
                </div>
                <div style="border:1px solid #e4e8f0;border-radius:8px;padding:14px 16px;margin-bottom:10px;">
                    <div style="font-size:12px;font-weight:600;color:#1a1f36;margin-bottom:4px;">📸 ფოტოების ატვირთვა
                    </div>
                    <div style="font-size:11px;color:#8892a4;margin-bottom:6px;">ZIP ფაილი ფოტოებით</div>
                    <div style="margin-bottom:10px;">
                        <input type="file" id="photos-zip-input" accept=".zip"
                            style="font-size:11px;width:100%;margin-bottom:0;">
                    </div>
                    <div id="photos-upload-status">
                        @if ($uploadPhotosRunning)
                            <div style="font-size:11px;color:#8892a4;">⏳ მიმდინარეობს…</div>
                        @else
                            <button onclick="startPhotosUpload()"
                                style="font-size:11px;padding:6px 14px;border-radius:6px;border:1px solid #3d4f7c;background:#2d3448;color:#7c9df7;cursor:pointer;">
                                📸 გაშვება
                            </button>
                        @endif
                    </div>
                </div>
                <div style="border:1px solid #e4e8f0;border-radius:8px;padding:14px 16px;margin-bottom:10px;">
                    <div style="font-size:12px;font-weight:600;color:#1a1f36;margin-bottom:4px;">📐 ნახაზების ატვირთვა
                    </div>
                    <div style="margin-bottom:10px;">
                        <input type="file" id="drawings-zip-input" accept=".zip"
                            style="font-size:11px;width:100%;margin-bottom:0;">
                    </div>
                    <div style="margin-bottom:10px;">
                        <label
                            style="font-size:11px;color:#8892a4;display:flex;align-items:center;gap:6px;cursor:pointer;">
                            <input type="checkbox" id="drawings-replace-mode">
                            🔄 არსებული ჩაანაცვლე (replace)
                        </label>
                    </div>
                    <div id="drawings-upload-status">
                        @if (Cache::has('upload_drawings_running'))
                            <div style="font-size:11px;color:#8892a4;">⏳ მიმდინარეობს…</div>
                        @else
                            <button onclick="startDrawingsUpload()"
                                style="font-size:11px;padding:6px 14px;border-radius:6px;border:1px solid #3d4f7c;background:#2d3448;color:#7c9df7;cursor:pointer;">
                                📐 გაშვება
                            </button>
                        @endif
                    </div>
                </div>
                <div style="border:1px solid #e4e8f0;border-radius:8px;padding:14px 16px;margin-bottom:10px;">

                </div>
            </div>

            {{-- Excel entry confirm modal --}}
            <div id="excel-modal"
                style="display:none;position:fixed;inset:0;z-index:9999;background:rgba(0,0,0,.55);align-items:center;justify-content:center;">
                <div
                    style="background:#fff;border-radius:10px;padding:28px 32px;max-width:380px;width:90%;box-shadow:0 8px 32px rgba(0,0,0,.25);">
                    <h3 style="margin:0 0 12px;font-size:1rem;color:#1a1f36;">📊 Excel-ში შეტანა</h3>
                    <p id="excel-modal-text" style="font-size:13px;color:#4b5563;margin:0 0 20px;line-height:1.6;">
                    </p>
                    <div style="display:flex;gap:10px;justify-content:flex-end;">
                        <button onclick="closeExcelModal()"
                            style="padding:8px 18px;border-radius:6px;border:1px solid #e4e8f0;background:#fff;color:#4b5563;font-size:13px;cursor:pointer;">გაუქმება</button>
                        <button id="excel-modal-confirm"
                            style="padding:8px 18px;border-radius:6px;border:none;background:#6d28d9;color:#fff;font-size:13px;font-weight:600;cursor:pointer;"
                            onclick="submitExcelEntry()">დადასტურება</button>
                    </div>
                </div>
            </div>

            @php
                $uploadScansRoute = route('dashboard.upload.scans');
                $uploadPhotosRoute = route('dashboard.upload.photos');
            @endphp

            <script>
                let _excelBtn = null;

                function openExcelModal(btn) {
                    _excelBtn = btn;
                    const actNumber = btn.dataset.actNumber;
                    document.getElementById('excel-modal-text').textContent =
                        'ACT ' + actNumber + ' — დარწმუნებული ხარ, რომ Excel-ში შეტანილია?';
                    document.getElementById('excel-modal').style.display = 'flex';
                }

                function closeExcelModal() {
                    document.getElementById('excel-modal').style.display = 'none';
                    _excelBtn = null;
                }

                async function submitExcelEntry() {
                    if (!_excelBtn) return;

                    const btn = _excelBtn;
                    const recordId = btn.dataset.recordId;
                    closeExcelModal();

                    btn.disabled = true;
                    btn.textContent = '⏳';

                    try {
                        const res = await fetch('/dashboard/acts/' + recordId + '/excel-entered', {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ??
                                    '{{ csrf_token() }}'
                            },
                        });

                        if (!res.ok) throw new Error('server error');

                        // Swap button to ✅ checkmark
                        const td = btn.closest('td');
                        td.innerHTML = '<span class="doc-check" title="Excel-ში შეტანილია">✅</span>';

                    } catch (err) {
                        btn.disabled = false;
                        btn.textContent = '📊 შეტანა';
                        showToast('❌ შეცდომა — სცადეთ თავიდან');
                    }
                }

                document.getElementById('excel-modal').addEventListener('click', e => {
                    if (e.target === document.getElementById('excel-modal')) closeExcelModal();
                });

                // ── Scan download ────────────────────────────────────────────────
                function startScanDownload() {
                    const raw = document.getElementById('scan-range-input').value.trim();
                    const slug = raw === '' ? 'all' : raw;
                    document.getElementById('scan-range-hidden').value = slug;
                    const btn = document.querySelector('[onclick="startScanDownload()"]');
                    if (btn) btn.disabled = true;
                    const form = document.getElementById('download-scan-form');
                    fetch(form.action, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'Accept': 'application/json'
                        },
                        body: new FormData(form),
                    }).then(() => pollScanStatus(slug));
                }

                function pollScanStatus(slug) {
                    fetch('/dashboard/download/status?type=scans&range=' + encodeURIComponent(slug))
                        .then(r => r.json())
                        .then(data => {
                            const box = document.getElementById('scan-status');
                            if (!box) return;
                            if (data.running) {
                                box.innerHTML =
                                    '<div style="font-size:11px;color:#8892a4;">⏳ მიმდინარეობს… <span style="font-family:\'IBM Plex Mono\',monospace;color:#4b5563;">' +
                                    (data.progress ? data.progress.done + ' / ' + data.progress.total + ' ACT' : '') +
                                    '</span></div>';
                                setTimeout(() => pollScanStatus(slug), 5000);
                            } else if (data.ready) {
                                box.innerHTML =
                                    '<div style="display:flex;gap:8px;align-items:center;"><a href="/dashboard/download/file?range=' +
                                    encodeURIComponent(slug) +
                                    '" style="display:inline-block;font-size:11px;padding:6px 14px;border-radius:6px;border:1px solid #3d7c4f;background:#2d4438;color:#6fcf97;text-decoration:none;">⬇️ ZIP გადმოწერა</a>' +
                                    '<button onclick="resetScanDownload(event)" style="font-size:11px;padding:6px 10px;border-radius:6px;border:1px solid #6b778c;background:transparent;color:#8892a4;cursor:pointer;">↺ ახალი</button></div>';
                            } else {
                                setTimeout(() => pollScanStatus(slug), 5000);
                            }
                        })
                        .catch(() => setTimeout(() => pollScanStatus(slug), 8000));
                }

                // ── Photos download ──────────────────────────────────────────────
                function startPhotosDownload() {
                    const raw = document.getElementById('photos-range-input').value.trim();
                    const errBox = document.getElementById('photos-range-error');
                    if (!raw) {
                        if (errBox) errBox.style.display = 'block';
                        return;
                    }
                    const parts = raw.split('-');
                    const from = parseInt(parts[0]);
                    const to = parseInt(parts[1]);
                    if (parts.length !== 2 || isNaN(from) || isNaN(to) || from < 1 || to <= from || (to - from) > 99) {
                        if (errBox) {
                            errBox.textContent = 'range უნდა იყოს მაქსიმუმ 100 ACT, from < to (მაგ. 1-100)';
                            errBox.style.display = 'block';
                        }
                        return;
                    }
                    if (errBox) errBox.style.display = 'none';
                    const btn = document.querySelector('[onclick="startPhotosDownload()"]');
                    if (btn) btn.disabled = true;
                    document.getElementById('photos-range-hidden').value = raw;
                    const form = document.getElementById('download-photos-form');
                    fetch(form.action, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'Accept': 'application/json'
                        },
                        body: new FormData(form),
                    }).then(() => pollPhotosStatus(raw));
                }

                function pollPhotosStatus(slug) {
                    fetch('/dashboard/download/status?type=photos&range=' + encodeURIComponent(slug))
                        .then(r => r.json())
                        .then(data => {
                            const box = document.getElementById('photos-status');
                            if (!box) return;
                            if (data.running) {
                                box.innerHTML =
                                    '<div style="font-size:11px;color:#8892a4;">⏳ მიმდინარეობს… <span style="font-family:\'IBM Plex Mono\',monospace;color:#4b5563;">' +
                                    (data.progress ? data.progress.done + ' / ' + data.progress.total + ' ACT' : '') +
                                    '</span></div>';
                                setTimeout(() => pollPhotosStatus(slug), 5000);
                            } else if (data.ready) {
                                box.innerHTML =
                                    '<div style="display:flex;gap:8px;align-items:center;"><a href="/dashboard/download/photos/file?range=' +
                                    encodeURIComponent(slug) +
                                    '" style="display:inline-block;font-size:11px;padding:6px 14px;border-radius:6px;border:1px solid #3d7c4f;background:#2d4438;color:#6fcf97;text-decoration:none;">⬇️ ZIP გადმოწერა</a>' +
                                    '<button onclick="resetPhotosDownload(event)" style="font-size:11px;padding:6px 10px;border-radius:6px;border:1px solid #6b778c;background:transparent;color:#8892a4;cursor:pointer;">↺ ახალი</button></div>';
                            } else {
                                setTimeout(() => pollPhotosStatus(slug), 5000);
                            }
                        })
                        .catch(() => setTimeout(() => pollPhotosStatus(slug), 8000));
                }

                function resetScanDownload(e) {
                    e?.stopPropagation();
                    const slug = document.getElementById('scan-range-input')?.value.trim() || 'all';
                    fetch('/dashboard/download/cancel?type=scans&range=' + encodeURIComponent(slug), {
                        method: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        }
                    });
                    const box = document.getElementById('scan-status');
                    if (!box) return;
                    box.innerHTML =
                        '<button onclick="startScanDownload()" style="font-size:11px;padding:6px 14px;border-radius:6px;border:1px solid #3d4f7c;background:#2d3448;color:#7c9df7;cursor:pointer;">📦 დაწყება</button>' +
                        '<form id="download-scan-form" method="POST" action="/dashboard/download" style="display:none;"><input type="hidden" name="_token" value="{{ csrf_token() }}"><input type="hidden" name="range" id="scan-range-hidden" value="all"></form>';
                }

                function resetPhotosDownload(e) {
                    e?.stopPropagation();
                    const slug = document.getElementById('photos-range-input')?.value.trim() || '';
                    if (slug) {
                        fetch('/dashboard/download/cancel?type=photos&range=' + encodeURIComponent(slug), {
                            method: 'DELETE',
                            headers: {
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                            }
                        });
                    }
                    const box = document.getElementById('photos-status');
                    if (!box) return;
                    box.innerHTML =
                        '<button onclick="startPhotosDownload()" style="font-size:11px;padding:6px 14px;border-radius:6px;border:1px solid #3d4f7c;background:#2d3448;color:#7c9df7;cursor:pointer;">📸 დაწყება</button>' +
                        '<div id="photos-range-error" style="display:none;font-size:11px;color:#f87171;margin-top:4px;">range სავალდებულოა (მაგ. 1-100)</div>' +
                        '<form id="download-photos-form" method="POST" action="/dashboard/download/photos" style="display:none;"><input type="hidden" name="_token" value="{{ csrf_token() }}"><input type="hidden" name="range" id="photos-range-hidden" value=""></form>';
                }

                function switchTab(tab) {
                    const isDownload = tab === 'download';
                    document.getElementById('tab-download').style.display = isDownload ? '' : 'none';
                    document.getElementById('tab-upload').style.display = isDownload ? 'none' : '';
                    const btnD = document.getElementById('tab-btn-download');
                    const btnU = document.getElementById('tab-btn-upload');
                    btnD.style.background = isDownload ? '#1a1f36' : '#f8f9fc';
                    btnD.style.color = isDownload ? '#7c9df7' : '#8892a4';
                    btnU.style.background = isDownload ? '#f8f9fc' : '#1a1f36';
                    btnU.style.color = isDownload ? '#8892a4' : '#7c9df7';
                }

                function setOtherUploadButtonsDisabled(activeType, disabled) {
                    const map = {
                        scans: '[onclick="startScansUpload()"]',
                        photos: '[onclick="startPhotosUpload()"]',
                        drawings: '[onclick="startDrawingsUpload()"]',
                    };
                    Object.entries(map).forEach(([type, sel]) => {
                        if (type === activeType) return;
                        const b = document.querySelector(sel);
                        if (b) b.disabled = disabled;
                    });
                    const rescanBtn = document.getElementById('rescan-btn');
                    if (rescanBtn && !rescanBtn.dataset.rescanRunning) {
                        rescanBtn.disabled = disabled;
                        rescanBtn.style.opacity = disabled ? '0.6' : '';
                        rescanBtn.style.cursor = disabled ? 'not-allowed' : 'pointer';
                        if (disabled) rescanBtn.title = '⏳ გთხოვთ დაელოდოთ — ატვირთვა მიმდინარეობს';
                        else rescanBtn.title = 'ყველა Drive საქაღალდის სრული გადამოწმება';
                    }
                }

                function startScansUpload() {
                    const fileInput = document.getElementById('scans-zip-input');
                    if (!fileInput || !fileInput.files.length) {
                        showToast('⚠️ აირჩიე ZIP ფაილი');
                        return;
                    }
                    if (fileInput.files[0].size > 5 * 1024 * 1024 * 1024) {
                        showToast('⚠️ ფაილი 5GB-ზე მეტია. მაქსიმალური ზომა: 5GB.');
                        return;
                    }
                    if (!fileInput.files[0].name.toLowerCase().endsWith('.zip')) {
                        showToast('⚠️ მხოლოდ ZIP ფაილია დაშვებული');
                        return;
                    }
                    window._uploadInProgress = true;
                    document.getElementById('upload-warning-banner').style.display = 'block';
                    const btn = document.querySelector('[onclick="startScansUpload()"]');
                    btn.disabled = true;
                    btn.textContent = '⏳…';
                    const fd = new FormData();
                    fd.append('_token', document.querySelector('meta[name="csrf-token"]').content);
                    fd.append('zip', fileInput.files[0]);
                    fd.append('mode', document.getElementById('scans-replace-mode')?.checked ? 'replace' : 'skip');
                    fetch('{{ route('dashboard.upload.scans') }}', {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                        },
                        body: fd,
                    }).then(async res => {
                        if (!res.ok) {
                            const data = await res.json().catch(() => ({}));
                            const msg = data?.message || (data?.errors?.zip?.[0]) || 'სერვერის შეცდომა';
                            showToast('❌ ' + msg);
                            btn.disabled = false;
                            btn.textContent = '📤 გაშვება';
                            window._uploadInProgress = false;
                            document.getElementById('upload-warning-banner').style.display = 'none';
                            return;
                        }
                        pollScansUploadStatus();
                    }).catch(() => {
                        showToast('❌ ატვირთვა ვერ მოხერხდა');
                        btn.disabled = false;
                        btn.textContent = '📤 გაშვება';
                        window._uploadInProgress = false;
                        document.getElementById('upload-warning-banner').style.display = 'none';
                    });

                }

                function pollScansUploadStatus() {
                    fetch('/dashboard/upload/status')
                        .then(r => r.json())
                        .then(data => {
                            const box = document.getElementById('scans-upload-status');
                            if (!box) return;
                            if (data.running) {
                                box.innerHTML = '<div style="font-size:11px;color:#8892a4;">⏳ მიმდინარეობს…</div>';
                                setOtherUploadButtonsDisabled('scans', true);
                                setTimeout(pollScansUploadStatus, 5000);
                            } else {
                                box.innerHTML =
                                    '<div style="font-size:11px;color:#4ade80;margin-bottom:6px;">✅ ატვირთვა დასრულდა</div>' +
                                    '<button onclick="startScansUpload()" style="font-size:11px;padding:6px 14px;border-radius:6px;border:1px solid #3d4f7c;background:#2d3448;color:#7c9df7;cursor:pointer;">📤 გაშვება</button>';
                                setOtherUploadButtonsDisabled('scans', false);
                                window._uploadInProgress = false;
                                document.getElementById('upload-warning-banner').style.display = 'none';
                            }
                        })
                        .catch(() => setTimeout(pollScansUploadStatus, 8000));
                }

                function startPhotosUpload() {
                    const fileInput = document.getElementById('photos-zip-input');
                    if (!fileInput || !fileInput.files.length) {
                        showToast('⚠️ აირჩიე ZIP ფაილი');
                        return;
                    }
                    if (fileInput.files[0].size > 5 * 1024 * 1024 * 1024) {
                        showToast('⚠️ ფაილი 5GB-ზე მეტია. მაქსიმალური ზომა: 5GB.');
                        return;
                    }
                    if (!fileInput.files[0].name.toLowerCase().endsWith('.zip')) {
                        showToast('⚠️ მხოლოდ ZIP ფაილია დაშვებული');
                        return;
                    }
                    window._uploadInProgress = true;
                    document.getElementById('upload-warning-banner').style.display = 'block';
                    const btn = document.querySelector('[onclick="startPhotosUpload()"]');
                    if (btn) {
                        btn.disabled = true;
                        btn.textContent = '⏳…';
                    }
                    const fd = new FormData();
                    fd.append('_token', document.querySelector('meta[name="csrf-token"]').content);
                    fd.append('zip', fileInput.files[0]);
                    fetch('{{ route('dashboard.upload.photos') }}', {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                        },
                        body: fd,
                    }).then(async res => {
                        if (!res.ok) {
                            const data = await res.json().catch(() => ({}));
                            const msg = data?.message || (data?.errors?.zip?.[0]) || 'სერვერის შეცდომა';
                            showToast('❌ ' + msg);
                            if (btn) {
                                btn.disabled = false;
                                btn.textContent = '📸 გაშვება';
                            }
                            window._uploadInProgress = false;
                            document.getElementById('upload-warning-banner').style.display = 'none';
                            return;
                        }
                        pollPhotosUploadStatus();
                    }).catch(() => {
                        showToast('❌ ატვირთვა ვერ მოხერხდა');
                        if (btn) {
                            btn.disabled = false;
                            btn.textContent = '📸 გაშვება';
                        }
                        window._uploadInProgress = false;
                        document.getElementById('upload-warning-banner').style.display = 'none';
                    });
                }

                function pollPhotosUploadStatus() {
                    fetch('/dashboard/upload/status?type=photos')
                        .then(r => r.json())
                        .then(data => {
                            const box = document.getElementById('photos-upload-status');
                            if (!box) return;
                            if (data.running) {
                                const p = data.progress;
                                const detail = p ? ` ✅ ${p.uploaded} ⏭️ ${p.skipped} ❌ ${p.failed}` : '';
                                box.innerHTML = '<div style="font-size:11px;color:#8892a4;">⏳ მიმდინარეობს…' + detail +
                                '</div>';
                                setOtherUploadButtonsDisabled('photos', true);
                                setTimeout(pollPhotosUploadStatus, 5000);
                            } else {
                                box.innerHTML =
                                    '<div style="font-size:11px;color:#4ade80;margin-bottom:6px;">✅ ატვირთვა დასრულდა</div>' +
                                    '<button onclick="startPhotosUpload()" style="font-size:11px;padding:6px 14px;border-radius:6px;border:1px solid #3d4f7c;background:#2d3448;color:#7c9df7;cursor:pointer;">📸 გაშვება</button>';
                                setOtherUploadButtonsDisabled('photos', false);
                                window._uploadInProgress = false;
                                document.getElementById('upload-warning-banner').style.display = 'none';
                            }
                        })
                        .catch(() => setTimeout(pollPhotosUploadStatus, 8000));
                }

                function startDrawingsUpload() {
                    const fileInput = document.getElementById('drawings-zip-input');
                    if (!fileInput || !fileInput.files.length) {
                        showToast('⚠️ აირჩიე ZIP ფაილი');
                        return;
                    }
                    if (!fileInput.files[0].name.toLowerCase().endsWith('.zip')) {
                        showToast('⚠️ მხოლოდ ZIP ფაილია დაშვებული');
                        return;
                    }
                    window._uploadInProgress = true;
                    document.getElementById('upload-warning-banner').style.display = 'block';
                    const btn = document.querySelector('[onclick="startDrawingsUpload()"]');
                    if (btn) {
                        btn.disabled = true;
                        btn.textContent = '⏳…';
                    }
                    const fd = new FormData();
                    fd.append('_token', document.querySelector('meta[name="csrf-token"]').content);
                    fd.append('zip', fileInput.files[0]);
                    fd.append('mode', document.getElementById('drawings-replace-mode')?.checked ? 'replace' : 'skip');
                    fetch('/dashboard/upload-drawings', {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json'
                        },
                        body: fd,
                    }).then(async res => {
                        if (!res.ok) {
                            const data = await res.json().catch(() => ({}));
                            showToast('❌ ' + (data?.message || 'სერვერის შეცდომა'));
                            if (btn) {
                                btn.disabled = false;
                                btn.textContent = '📐 გაშვება';
                            }
                            window._uploadInProgress = false;
                            document.getElementById('upload-warning-banner').style.display = 'none';
                            return;
                        }
                        pollDrawingsUploadStatus();
                    }).catch(() => {
                        showToast('❌ ატვირთვა ვერ მოხერხდა');
                        if (btn) {
                            btn.disabled = false;
                            btn.textContent = '📐 გაშვება';
                        }
                        window._uploadInProgress = false;
                        document.getElementById('upload-warning-banner').style.display = 'none';
                    });
                }

                function pollDrawingsUploadStatus() {
                    fetch('/dashboard/upload/status?type=drawings')
                        .then(r => r.json())
                        .then(data => {
                            const box = document.getElementById('drawings-upload-status');
                            if (!box) return;
                            if (data.running) {
                                box.innerHTML = '<div style="font-size:11px;color:#8892a4;">⏳ მიმდინარეობს…</div>';
                                setOtherUploadButtonsDisabled('drawings', true);
                                setTimeout(pollDrawingsUploadStatus, 5000);
                            } else {
                                box.innerHTML =
                                    '<div style="font-size:11px;color:#4ade80;margin-bottom:6px;">✅ ატვირთვა დასრულდა</div>' +
                                    '<button onclick="startDrawingsUpload()" style="font-size:11px;padding:6px 14px;border-radius:6px;border:1px solid #3d4f7c;background:#2d3448;color:#7c9df7;cursor:pointer;">📐 გაშვება</button>';
                                setOtherUploadButtonsDisabled('drawings', false);
                                window._uploadInProgress = false;
                                document.getElementById('upload-warning-banner').style.display = 'none';
                            }
                        })
                        .catch(() => setTimeout(pollDrawingsUploadStatus, 8000));
                }

                document.getElementById('tools-modal')?.addEventListener('click', e => {
                    if (!e.target.closest('#tools-modal > div')) {
                        document.getElementById('tools-modal').style.display = 'none';
                    }
                });

                function showToast(message) {
                    const existing = document.getElementById('vigil-toast');
                    if (existing) existing.remove();
                    const toast = document.createElement('div');
                    toast.id = 'vigil-toast';
                    toast.textContent = message;
                    toast.style.cssText =
                        'position:fixed;top:0;left:0;right:0;z-index:999999;background:#d97706;color:#fff;text-align:center;padding:10px;font-size:13px;font-weight:600;animation:slideDown .2s ease;';
                    document.body.appendChild(toast);
                    setTimeout(() => toast.remove(), 4000);
                }
            </script>

</body>

</html>
