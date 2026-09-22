<!DOCTYPE html>
<html lang="ka">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vigil 🛡️ Dashboard</title>
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
            background: #f4f5f7;
            color: #1a1f36;
            font-size: 14px;
        }

        /* Header */
        .header {
            background: #fff;
            border-bottom: 1px solid #e1e4e8;
            padding: 14px 24px;
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
            font-size: 16px;
            font-weight: 600;
        }

        .header-badge {
            font-size: 11px;
            color: #6b778c;
            background: #f4f5f7;
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
            font-size: 12px;
            color: #0052cc;
            text-decoration: none;
            white-space: nowrap;
        }

        .stats-link:hover {
            text-decoration: underline;
        }

        .timer {
            font-size: 12px;
            color: #6b778c;
            display: flex;
            align-items: center;
            gap: 6px;
            white-space: nowrap;
        }

        .timer-dot {
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background: #0a7c42;
            flex-shrink: 0;
            animation: pulse 2s infinite;
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
            gap: 12px;
            padding: 16px 24px;
            flex-wrap: wrap;
        }

        .stat-card {
            background: #fff;
            border: 1px solid #e1e4e8;
            border-radius: 8px;
            padding: 14px 20px;
            flex: 1;
            min-width: 140px;
        }

        .stat-label {
            font-size: 11px;
            color: #6b778c;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            margin-bottom: 4px;
        }

        .stat-value {
            font-size: 26px;
            font-weight: 600;
            font-family: 'IBM Plex Mono', monospace;
        }

        .stat-value.green {
            color: #0a7c42;
        }

        .stat-value.red {
            color: #c7381e;
        }

        .stat-value.blue {
            color: #0052cc;
        }

        .progress-card {
            flex: 2;
            min-width: 200px;
        }

        .progress-bar-wrap {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-top: 8px;
        }

        .progress-bar-bg {
            flex: 1;
            height: 8px;
            background: #e1e4e8;
            border-radius: 4px;
            overflow: hidden;
        }

        .progress-bar-fill {
            height: 100%;
            background: #0a7c42;
            border-radius: 4px;
        }

        .progress-pct {
            font-size: 13px;
            font-weight: 600;
            font-family: 'IBM Plex Mono', monospace;
            white-space: nowrap;
        }

        /* Filters */
        .filters-wrap {
            padding: 0 24px 12px;
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            align-items: center;
        }

        .filter-group {
            position: relative;
        }

        .filter-toggle {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 14px;
            border-radius: 4px;
            font-size: 12px;
            font-family: 'IBM Plex Sans', sans-serif;
            cursor: pointer;
            border: 1px solid #e1e4e8;
            background: #fff;
            color: #42526e;
            user-select: none;
            white-space: nowrap;
        }

        .filter-toggle:hover {
            border-color: #0052cc;
            color: #0052cc;
        }

        .filter-toggle.has-active {
            border-color: #0052cc;
            color: #0052cc;
            background: #f0f5ff;
            font-weight: 500;
        }

        .filter-dropdown {
            display: none;
            position: absolute;
            top: calc(100% + 6px);
            left: 0;
            background: #fff;
            border: 1px solid #e1e4e8;
            border-radius: 6px;
            padding: 10px 12px;
            z-index: 200;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.1);
            min-width: 200px;
        }

        .filter-dropdown.open {
            display: block;
        }

        .filter-dropdown-label {
            font-size: 11px;
            color: #6b778c;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            margin-bottom: 8px;
        }

        .filter-options {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .filter-opt {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 5px 8px;
            border-radius: 4px;
            font-size: 12px;
            color: #42526e;
            text-decoration: none;
            white-space: nowrap;
        }

        .filter-opt:hover {
            background: #f4f5f7;
        }

        .filter-opt.active {
            background: #e8f0fe;
            color: #0052cc;
            font-weight: 500;
        }

        .filter-opt-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            flex-shrink: 0;
        }

        /* ბიზნესი inline buttons */
        .biznes-filters {
            display: flex;
            gap: 6px;
            align-items: center;
            padding: 6px 0;
        }

        .biznes-btn {
            padding: 5px 14px;
            border-radius: 4px;
            font-size: 12px;
            font-family: 'IBM Plex Sans', sans-serif;
            cursor: pointer;
            border: 1px solid #e1e4e8;
            background: #fff;
            color: #42526e;
            text-decoration: none;
            white-space: nowrap;
        }

        .biznes-btn:hover {
            border-color: #0052cc;
            color: #0052cc;
        }

        .biznes-btn.active {
            background: #0052cc;
            border-color: #0052cc;
            color: #fff;
        }

        /* Legend */
        .legend {
            display: flex;
            gap: 20px;
            padding: 0 24px 10px;
            align-items: center;
            flex-wrap: wrap;
        }

        .legend-item {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 11px;
            color: #6b778c;
        }

        /* Table */
        .table-wrap {
            margin: 0 24px 36px;
            background: #fff;
            border: 1px solid #e1e4e8;
            border-radius: 8px;
            overflow: hidden;
        }

        .table-scroll {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 700px;
        }

        thead tr {
            background: #f4f5f7;
        }

        th {
            padding: 9px 14px;
            text-align: left;
            font-size: 11px;
            font-weight: 600;
            color: #6b778c;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            white-space: nowrap;
            border-bottom: 1px solid #e1e4e8;
        }

        th.center,
        td.center {
            text-align: center;
        }

        td {
            padding: 10px 14px;
            border-bottom: 1px solid #f4f5f7;
            vertical-align: middle;
            position: relative;
        }

        tr:last-child td {
            border-bottom: none;
        }

        tr:hover td {
            background: #f8f9ff;
        }

        .act-num {
            font-family: 'IBM Plex Mono', monospace;
            font-weight: 500;
            color: #0052cc;
            font-size: 13px;
            white-space: nowrap;
        }

        .badge {
            display: inline-block;
            font-size: 11px;
            font-weight: 500;
            padding: 2px 10px;
            border-radius: 3px;
            white-space: nowrap;
        }

        .badge.complete {
            color: #0a7c42;
            background: #e3fcef;
        }

        .badge.incomplete {
            color: #c7381e;
            background: #ffecea;
        }

        .doc-check {
            font-size: 13px;
            line-height: 1;
        }

        .kvleva-dot {
            display: inline-block;
            width: 10px;
            height: 10px;
            border-radius: 50%;
        }

        .biznes-count {
            display: inline-block;
            font-size: 11px;
            font-weight: 600;
            font-family: 'IBM Plex Mono', monospace;
            color: #0052cc;
            background: #e8f0fe;
            border-radius: 10px;
            padding: 1px 7px;
        }

        .drive-link {
            color: #6b778c;
            text-decoration: none;
            font-size: 14px;
        }

        .drive-link:hover {
            color: #0052cc;
        }

        .empty {
            padding: 48px;
            text-align: center;
            color: #6b778c;
        }

        /* Tooltip */
        .tip {
            cursor: help;
            font-size: 13px;
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
            font-size: 12px;
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

        /* Mobile */
        @media (max-width: 640px) {
            .header {
                padding: 12px 16px;
            }

            .stats-row {
                padding: 12px 16px;
                gap: 8px;
            }

            .stat-card {
                padding: 10px 14px;
            }

            .stat-value {
                font-size: 20px;
            }

            .filters-wrap {
                padding: 0 16px 10px;
            }

            .legend {
                padding: 0 16px 10px;
            }

            .table-wrap {
                margin: 0 16px 24px;
            }
        }

        body {
            padding-bottom: 0;
        }
    </style>
</head>

<body>

    {{-- Header --}}
    <div class="header">
        <div class="header-left">
            <span class="header-title">Vigil 🛡️</span>
            <span class="header-badge">Dashboard</span>
        </div>
        <div class="header-right">
            <div class="timer">
                <div class="timer-dot"></div>
                <span id="timer-text">იტვირთება...</span>
            </div>
            <a href="{{ $statsUrl }}" target="_blank" class="stats-link">Airtable სტატისტიკა →</a>
        </div>
    </div>

    {{-- Stats --}}
    <div class="stats-row">
        <div class="stat-card">
            <div class="stat-label">სულ აქტები</div>
            <div class="stat-value blue">{{ $total }}</div>
        </div>
        <div class="stat-card">
            <div class="stat-label">დასრულებული</div>
            <div class="stat-value green">{{ $complete }}</div>
        </div>
        <div class="stat-card">
            <div class="stat-label">მიმდინარე</div>
            <div class="stat-value red">{{ $incomplete }}</div>
        </div>
        <div class="stat-card progress-card">
            <div class="stat-label">პროგრესი</div>
            @php $pct = $total > 0 ? round($complete / $total * 100, 1) : 0; @endphp
            <div class="progress-bar-wrap">
                <div class="progress-bar-bg">
                    <div class="progress-bar-fill" style="width: {{ $pct }}%"></div>
                </div>
                <span class="progress-pct">{{ $pct }}%</span>
            </div>
        </div>
    </div>

    {{-- Filters --}}
    <div class="filters-wrap">

        {{-- სტატუსი --}}
        <div class="filter-group" id="fg-status">
            <div class="filter-toggle {{ $activeStatus ? 'has-active' : '' }}" onclick="toggleFilter('status')">
                სტატუსი {{ $activeStatus ? '· ' . ($activeStatus === 'complete' ? 'დასრულდა' : 'მიმდინარე') : '' }}
                <span>▼</span>
            </div>
            <div class="filter-dropdown" id="fd-status">
                <div class="filter-dropdown-label">სტატუსი</div>
                <div class="filter-options">
                    <a href="{{ request()->fullUrlWithQuery(['status' => null]) }}"
                        class="filter-opt {{ !$activeStatus ? 'active' : '' }}">ყველა</a>
                    <a href="{{ request()->fullUrlWithQuery(['status' => 'incomplete']) }}"
                        class="filter-opt {{ $activeStatus === 'incomplete' ? 'active' : '' }}">⏳ მიმდინარე</a>
                    <a href="{{ request()->fullUrlWithQuery(['status' => 'complete']) }}"
                        class="filter-opt {{ $activeStatus === 'complete' ? 'active' : '' }}">✅ დასრულებული</a>
                </div>
            </div>
        </div>

        {{-- აკლია --}}
        <div class="filter-group" id="fg-missing">
            <div class="filter-toggle {{ $activeMissing ? 'has-active' : '' }}" onclick="toggleFilter('missing')">
                აკლია {{ $activeMissing ? '· ' . $activeMissing : '' }} <span>▼</span>
            </div>
            <div class="filter-dropdown" id="fd-missing">
                <div class="filter-dropdown-label">რა აკლია</div>
                <div class="filter-options">
                    <a href="{{ request()->fullUrlWithQuery(['missing' => null]) }}"
                        class="filter-opt {{ !$activeMissing ? 'active' : '' }}">ყველა</a>
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

        @if ($hasActiveFilter)
            <a href="/dashboard"
                style="display:inline-flex;align-items:center;gap:4px;padding:6px 14px;border-radius:4px;font-size:12px;border:1px solid #ffd0cc;background:#fff5f5;color:#c7381e;text-decoration:none;">
                ✕ გასუფთავება
            </a>
        @endif

    </div>

    </div>

    {{-- Legend --}}
    <div class="legend">
        <div class="legend-item">✅ მიღებულია</div>
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
                        <th>ნომერი</th>
                        <th>სტატუსი</th>
                        <th class="center">შენ.</th>
                        @foreach ($docFields as $label => $field)
                            <th class="center">{{ $label }}</th>
                        @endforeach
                        <th class="center">ბიზნ.</th>
                        <th class="center">კვლევა</th>
                        <th class="center">Drive</th>
                    </tr>
                </thead>
                <tbody>
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
                            <td class="act-num">{{ $act['🔖 აქტის ნომერი'] ?? '—' }}</td>
                            <td>
                                <span class="badge {{ $isComplete ? 'complete' : 'incomplete' }}">
                                    {{ $isComplete ? 'დასრულდა' : 'მიმდინარე' }}
                                </span>
                            </td>
                            <td class="center">
                                @if ($missing)
                                    <span class="tip" data-tip="{{ $missing }}">❗</span>
                                @else
                                    <span style="color:#d0d7de;">—</span>
                                @endif
                            </td>
                            @foreach ($docFields as $label => $field)
                                <td class="center">
                                    @if (in_array($field, $required))
                                        <span class="doc-check">{{ $act[$field] ?? false ? '✅' : '❌' }}</span>
                                    @else
                                        <span style="color:#d0d7de;font-size:16px;">·</span>
                                    @endif
                                </td>
                            @endforeach
                            <td class="center">
                                @if ($biznesCount > 0)
                                    <span class="biznes-count">{{ $biznesCount }}</span>
                                @else
                                    <span style="color:#d0d7de;">—</span>
                                @endif
                            </td>
                            <td class="center">
                                @if ($kvleva)
                                    <span class="kvleva-dot" style="background: {{ $kvlevaColor }};"
                                        title="{{ $kvleva }}"></span>
                                @else
                                    <span style="color:#d0d7de;">—</span>
                                @endif
                            </td>
                            <td class="center">
                                @if ($folder)
                                    <a href="{{ $folder }}" target="_blank" class="drive-link">📁</a>
                                @else
                                    <span style="color:#d0d7de;">—</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ count($docFields) + 6 }}" class="empty">ACT-ები ვერ მოიძებნა</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>


    <div
        style="position:fixed;bottom:0;left:0;right:0;background:#f4f5f7;border-top:2px solid #e1e4e8;padding:8px 24px;padding-bottom:calc(8px + env(safe-area-inset-bottom, 0px));font-size:12px;color:#6b778c;z-index:200;">
        ნაჩვენები: <strong style="color:#1a1f36;">{{ $acts->count() }}</strong>
    </div>

    <script>
        // Filter dropdowns
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

        // Tooltip positioning
        document.querySelectorAll('.tip').forEach(el => {
            el.addEventListener('mousemove', (e) => {
                el.style.setProperty('--tip-x', (e.clientX + 12) + 'px');
                el.style.setProperty('--tip-y', (e.clientY + 16) + 'px');
            });
        });

        // Countdown timer
        const cachedAt = new Date({{ $cachedAt instanceof \Carbon\Carbon ? $cachedAt->timestamp : now()->timestamp }} *
            1000);
        const cacheMinutes = {{ $cacheMinutes }};

        function updateTimer() {
            const now = new Date();
            const elapsedSec = Math.floor((now - cachedAt) / 1000);
            const refreshInSec = (cacheMinutes * 60) - elapsedSec;

            if (refreshInSec <= 0) {
                document.getElementById('timer-text').textContent = 'განახლება...';
                window.location.reload();
                return;
            }

            const mins = Math.floor(refreshInSec / 60);
            const secs = refreshInSec % 60;
            const elapsedMins = Math.floor(elapsedSec / 60);
            const el = document.getElementById('timer-text');

            if (elapsedMins === 0) {
                el.textContent = 'ახლახან განახლდა · განახლება ' + mins + ':' + String(secs).padStart(2, '0') + '-ში';
            } else {
                el.textContent = 'განახლდა ' + elapsedMins + ' წთ წინ · განახლება ' + mins + ':' + String(secs).padStart(2,
                    '0') + '-ში';
            }
        }

        updateTimer();
        setInterval(updateTimer, 1000);
    </script>

</body>

</html>
