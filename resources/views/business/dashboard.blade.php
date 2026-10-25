<!DOCTYPE html>
<html lang="ka">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>Vigil · ბიზნეს მოდული</title>
    <link rel="icon"
        href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>💼</text></svg>">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        *,
        *::before,
        *::after {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        :root {
            --bg: #f0f2f5;
            --surface: #ffffff;
            --surface-2: #f8f9fb;
            --border: #e4e7ec;
            --navy: #1a2332;
            --navy-2: #243447;
            --text: #111827;
            --text-2: #6b7280;
            --text-3: #9ca3af;
            --green: #059669;
            --green-bg: #ecfdf5;
            --amber: #d97706;
            --amber-bg: #fffbeb;
            --red: #dc2626;
            --red-bg: #fef2f2;
            --blue: #2563eb;
            --blue-bg: #eff6ff;
            --slate: #64748b;
            --slate-bg: #f1f5f9;
        }

        body {
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            background: var(--bg);
            color: var(--text);
            min-height: 100vh;
            -webkit-font-smoothing: antialiased;
        }

        /* HEADER */
        .header {
            background: var(--navy);
            height: 58px;
            display: flex;
            align-items: center;
            padding: 0 1.5rem;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 50;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.3);
        }

        .header-brand {
            display: flex;
            align-items: center;
            gap: 0.625rem;
        }

        .header-icon {
            font-size: 1.1rem;
        }

        .header-title {
            font-size: 0.95rem;
            font-weight: 600;
            color: #fff;
            letter-spacing: -0.01em;
        }

        .header-sep {
            width: 1px;
            height: 16px;
            background: #2d4060;
            margin: 0 0.625rem;
        }

        .header-sub {
            font-size: 0.75rem;
            color: #7a92a8;
            font-weight: 400;
        }

        .header-right {
            display: flex;
            align-items: center;
            gap: 0.875rem;
        }

        .header-user {
            font-size: 0.8rem;
            color: #7a92a8;
        }

        .btn-logout {
            font-family: inherit;
            font-size: 0.78rem;
            color: #7a92a8;
            background: transparent;
            border: 1px solid #2d4060;
            padding: 0.3rem 0.75rem;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.15s;
            letter-spacing: 0.01em;
        }

        .btn-logout:hover {
            color: #fff;
            border-color: #4a6080;
        }

        /* PAGE */
        .page {
            max-width: 1440px;
            margin: 0 auto;
            padding: 1.75rem 1.5rem 4rem;
        }

        /* STATS */
        .stats {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 0.875rem;
            margin-bottom: 1.75rem;
        }

        .stat {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 1.25rem 1.375rem;
            position: relative;
            overflow: hidden;
        }

        .stat::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: var(--border);
            border-radius: 12px 12px 0 0;
        }

        .stat.s-active::before {
            background: var(--blue);
        }

        .stat.s-complete::before {
            background: var(--green);
        }

        .stat.s-overdue::before {
            background: var(--red);
        }

        .stat.s-pending::before {
            background: var(--amber);
        }

        .stat-n {
            font-size: 2.25rem;
            font-weight: 700;
            line-height: 1;
            margin-bottom: 0.375rem;
            letter-spacing: -0.03em;
        }

        .stat.s-active .stat-n {
            color: var(--blue);
        }

        .stat.s-complete .stat-n {
            color: var(--green);
        }

        .stat.s-overdue .stat-n {
            color: var(--red);
        }

        .stat.s-pending .stat-n {
            color: var(--amber);
        }

        .stat-l {
            font-size: 0.72rem;
            color: var(--text-2);
            font-weight: 500;
            text-transform: none;
            letter-spacing: 0;
        }

        /* TOOLBAR */
        .toolbar {
            display: flex;
            align-items: center;
            gap: 0.625rem;
            margin-bottom: 1rem;
            flex-wrap: wrap;
        }

        .search-wrap {
            position: relative;
            flex: 1;
            min-width: 180px;
            max-width: 280px;
        }

        .search-icon {
            position: absolute;
            left: 0.75rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-3);
            font-size: 0.85rem;
            pointer-events: none;
        }

        .search-input {
            width: 100%;
            font-family: inherit;
            font-size: 0.85rem;
            padding: 0.55rem 2rem 0.55rem 2.25rem;
            border: 1px solid var(--border);
            border-radius: 8px;
            background: var(--surface);
            color: var(--text);
            outline: none;
            transition: border-color 0.15s;
        }

        .search-input:focus {
            border-color: var(--blue);
        }

        .search-clear {
            position: absolute;
            right: 0.625rem;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            cursor: pointer;
            color: var(--text-3);
            font-size: 1rem;
            display: none;
            line-height: 1;
            padding: 0;
        }

        .filter-wrap {
            position: relative;
        }

        .filter-select {
            font-family: inherit;
            font-size: 0.82rem;
            padding: 0.55rem 2rem 0.55rem 0.75rem;
            border: 1px solid var(--border);
            border-radius: 8px;
            background: var(--surface);
            color: var(--text);
            cursor: pointer;
            outline: none;
            appearance: none;
            -webkit-appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%239ca3af' d='M6 8L1 3h10z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 0.625rem center;
            transition: border-color 0.15s;
        }

        .filter-select:focus {
            border-color: var(--blue);
        }

        .filter-select.active {
            border-color: var(--blue);
            background-color: var(--blue-bg);
            color: var(--blue);
        }

        .btn-clear-filters {
            font-family: inherit;
            font-size: 0.8rem;
            color: var(--text-2);
            background: none;
            border: 1px solid var(--border);
            padding: 0.55rem 0.875rem;
            border-radius: 8px;
            cursor: pointer;
            display: none;
            transition: all 0.15s;
        }

        .btn-clear-filters:hover {
            border-color: var(--red);
            color: var(--red);
        }

        .btn-add {
            margin-left: auto;
            font-family: inherit;
            font-size: 0.82rem;
            font-weight: 600;
            padding: 0.55rem 1.125rem;
            background: var(--navy);
            color: #fff;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            transition: background 0.15s;
            white-space: nowrap;
        }

        .btn-add:hover {
            background: var(--navy-2);
        }

        /* TABLE */
        .table-wrap {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 12px;
            overflow: hidden;
        }

        .table-scroll {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.875rem;
            min-width: 600px;
        }

        thead th {
            background: var(--surface-2);
            padding: 0.75rem 1rem;
            text-align: left;
            font-size: 0.7rem;
            font-weight: 600;
            color: var(--text-3);
            border-bottom: 1px solid var(--border);
            white-space: nowrap;
            letter-spacing: 0.04em;
            text-transform: uppercase;
        }

        tbody tr {
            border-bottom: 1px solid var(--border);
            transition: background 0.1s;
            cursor: pointer;
        }

        tbody tr:last-child {
            border-bottom: none;
        }

        tbody tr:hover {
            background: var(--surface-2);
        }

        td {
            padding: 0.875rem 1rem;
            vertical-align: middle;
        }

        .td-name {
            font-weight: 500;
            color: var(--text);
        }

        .td-code {
            color: var(--text-3);
            font-size: 0.78rem;
            font-variant-numeric: tabular-nums;
        }

        /* BADGES */
        .badge {
            display: inline-flex;
            align-items: center;
            padding: 0.2rem 0.625rem;
            border-radius: 20px;
            font-size: 0.72rem;
            font-weight: 500;
            white-space: nowrap;
        }

        .badge-active {
            background: var(--blue-bg);
            color: var(--blue);
        }

        .badge-complete {
            background: var(--green-bg);
            color: var(--green);
        }

        .badge-pending {
            background: var(--amber-bg);
            color: var(--amber);
        }

        .badge-overdue {
            background: var(--red-bg);
            color: var(--red);
        }

        .type-pill {
            display: inline-flex;
            padding: 0.18rem 0.5rem;
            border-radius: 4px;
            font-size: 0.72rem;
            font-weight: 500;
            background: var(--slate-bg);
            color: var(--slate);
            white-space: nowrap;
        }

        .lot-chip {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 28px;
            height: 28px;
            padding: 0 6px;
            border-radius: 6px;
            font-size: 0.78rem;
            font-weight: 700;
            background: var(--navy);
            color: #fff;
        }

        .td-empty {
            color: var(--text-3);
            font-size: 0.78rem;
        }

        /* EMPTY STATE */
        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
        }

        .empty-icon {
            font-size: 2.5rem;
            margin-bottom: 0.875rem;
        }

        .empty-text {
            font-size: 0.875rem;
            color: var(--text-2);
        }

        /* COUNT BAR */
        .count-bar {
            font-size: 0.78rem;
            color: var(--text-3);
            margin-bottom: 0.625rem;
            padding-left: 0.25rem;
        }

        /* RESPONSIVE */
        @media (max-width: 900px) {
            .stats {
                grid-template-columns: repeat(3, 1fr);
            }
        }

        @media (max-width: 600px) {
            .page {
                padding: 1rem 0.875rem 3rem;
            }

            .stats {
                grid-template-columns: repeat(2, 1fr);
            }

            .stat:first-child {
                grid-column: span 2;
            }

            .stat-n {
                font-size: 1.875rem;
            }

            .search-wrap {
                max-width: 100%;
            }

            .toolbar {
                gap: 0.5rem;
            }

            .header-sub {
                display: none;
            }

            .btn-add {
                font-size: 0.78rem;
                padding: 0.55rem 0.75rem;
            }
        }
    </style>
</head>

<body>

    <header class="header">
        <div class="header-brand">
            <span class="header-icon">💼</span>
            <span class="header-title">ბიზნეს მოდული</span>
            <span class="header-sep"></span>
            <span class="header-sub">Vigil · Colliers Georgia</span>
        </div>
        <div class="header-right">
            <span class="header-user">{{ auth('business')->user()->name }}</span>
            <form method="POST" action="{{ route('business.logout') }}">
                @csrf
                <button type="submit" class="btn-logout">გამოსვლა</button>
            </form>
        </div>
    </header>

    <div class="page">

        {{-- STATS --}}
        <div class="stats">
            <div class="stat">
                <div class="stat-n">{{ $stats['total'] }}</div>
                <div class="stat-l">სულ ბიზნესი</div>
            </div>
            <div class="stat s-active">
                <div class="stat-n">{{ $stats['active'] }}</div>
                <div class="stat-l">მიმდინარე</div>
            </div>
            <div class="stat s-pending">
                <div class="stat-n">{{ $stats['pending'] }}</div>
                <div class="stat-l">გასაგზავნი</div>
            </div>
            <div class="stat s-complete">
                <div class="stat-n">{{ $stats['complete'] }}</div>
                <div class="stat-l">დასრულებული</div>
            </div>
            <div class="stat s-overdue">
                <div class="stat-n">{{ $stats['overdue'] }}</div>
                <div class="stat-l">ვადაგადაცილებული</div>
            </div>
        </div>

        {{-- TOOLBAR --}}
        <div class="toolbar">
            <div class="search-wrap">
                <span class="search-icon">🔍</span>
                <input type="text" class="search-input" id="search" placeholder="სახელი, კოდი ან ACT...">
                <button class="search-clear" id="search-clear" title="გასუფთავება">×</button>
            </div>

            <select class="filter-select" id="filter-status">
                <option value="">ყველა სტატუსი</option>
                <option value="active">მიმდინარე</option>
                <option value="pending">გასაგზავნი</option>
                <option value="complete">დასრულებული</option>
                <option value="overdue">⚠️ ვადაგადაცილებული</option>
            </select>

            <select class="filter-select" id="filter-type">
                <option value="">ყველა ტიპი</option>
                <option value="მეპატრონე">მეპატრონე</option>
                <option value="მოიჯარე">მოიჯარე</option>
                <option value="მეიჯარე">მეიჯარე</option>
                <option value="სხვა">სხვა</option>
            </select>

            <select class="filter-select" id="filter-lot">
                <option value="">ყველა ლოტი</option>
                @foreach (range(2, 5) as $lot)
                    <option value="{{ $lot }}">ლოტი {{ $lot }}</option>
                @endforeach
            </select>

            <button class="btn-clear-filters" id="btn-clear">✕ გასუფთავება</button>

            <a href="{{ route('business.create') }}" class="btn-add">+ ბიზნესის დამატება</a>
        </div>

        {{-- COUNT --}}
        <div class="count-bar" id="count-bar"></div>

        {{-- TABLE --}}
        <div class="table-wrap">
            <div class="table-scroll">
                <table>
                    <thead>
                        <tr>
                            <th>სახელი</th>
                            <th>კოდი</th>
                            <th>ტიპი</th>
                            <th>ლოტი</th>
                            <th>ACT</th>
                            <th>სტატუსი</th>
                        </tr>
                    </thead>
                    <tbody id="tbody">
                        @forelse($businesses as $business)
                            <tr data-name="{{ mb_strtolower($business->name) }}"
                                data-code="{{ $business->registration_code }}" data-act="{{ $business->act_number }}"
                                data-status="{{ $business->status }}"
                                data-type="{{ implode(',', (array) $business->type) }}"
                                data-lot="{{ $business->lot_number }}"
                                data-overdue="{{ $business->correspondence->where('status', 'overdue')->count() > 0 ? 'true' : 'false' }}"
                                onclick="window.location='{{ route('business.show', $business) }}'"
                                style="cursor:pointer;">
                                <td class="td-name">{{ $business->name }}</td>
                                <td class="td-code">{{ $business->registration_code }}</td>
                                <td>
                                    @foreach ((array) $business->type as $t)
                                        <span class="type-pill" style="margin-right:2px;">{{ $t }}</span>
                                    @endforeach
                                </td>
                                <td><span class="lot-chip">{{ $business->lot_number }}</span></td>
                                <td class="td-code td-act" style="white-space:nowrap;">
                                    {{ $business->act_number ?? '—' }}</td>
                                <td>
                                    <span
                                        class="badge {{ $business->correspondence->where('status', 'overdue')->count() > 0 ? 'badge-overdue' : 'badge-' . $business->status }}">
                                        @switch($business->status)
                                            @case('active')
                                                მიმდინარე
                                            @break

                                            @case('complete')
                                                დასრულებული
                                            @break

                                            @case('pending')
                                                გასაგზავნი
                                            @break
                                        @endswitch
                                    </span>
                                    @if ($business->correspondence->where('status', 'overdue')->count() > 0)
                                        <span class="badge badge-overdue" style="margin-left:0.375rem;"
                                            title="ვადაგადაცილებული მიმოწერა">⚠️</span>
                                    @endif
                                </td>
                            </tr>
                            @empty
                                <tr>
                                    <td colspan="6">
                                        <div class="empty-state">
                                            <div class="empty-icon">💼</div>
                                            <div class="empty-text">ბიზნესები არ მოიძებნა</div>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

        </div>

        <script>
            const search = document.getElementById('search');
            const searchClear = document.getElementById('search-clear');
            const filterStatus = document.getElementById('filter-status');
            const filterType = document.getElementById('filter-type');
            const filterLot = document.getElementById('filter-lot');
            const btnClear = document.getElementById('btn-clear');
            const countBar = document.getElementById('count-bar');
            const rows = Array.from(document.querySelectorAll('#tbody tr[data-name]'));
            const total = rows.length;

            function normalize(str) {
                return (str || '').toLowerCase().replace(/\s*-\s*/g, '-').trim();
            }

            function applyFilters() {
                const q = normalize(search.value);
                const status = filterStatus.value;
                const type = filterType.value;
                const lot = filterLot.value;

                let visible = 0;

                rows.forEach(row => {
                    const name = normalize(row.dataset.name);
                    const code = row.dataset.code || '';
                    const act = normalize(row.dataset.act);

                    const matchSearch = !q ||
                        name.includes(q) ||
                        code === q ||
                        code.startsWith(q) ||
                        act === q ||
                        act.includes(q);

                    const matchStatus = !status ||
                        (status === 'overdue' ? row.dataset.overdue === 'true' : row.dataset.status === status);
                    const matchType = !type || row.dataset.type.split(',').includes(type);
                    const matchLot = !lot || row.dataset.lot === lot;
                    const show = matchSearch && matchStatus && matchType && matchLot;
                    row.style.display = show ? '' : 'none';
                    if (show) visible++;
                });

                countBar.textContent = visible < total ?
                    `${visible} / ${total} ბიზნესი` :
                    `${total} ბიზნესი`;

                searchClear.style.display = q ? 'block' : 'none';
                const hasFilter = status || type || lot;
                btnClear.style.display = hasFilter ? 'inline-flex' : 'none';

                filterStatus.classList.toggle('active', !!status);
                filterType.classList.toggle('active', !!type);
                filterLot.classList.toggle('active', !!lot);
            }

            searchClear.addEventListener('click', () => {
                search.value = '';
                applyFilters();
                search.focus();
            });

            btnClear.addEventListener('click', () => {
                filterStatus.value = '';
                filterType.value = '';
                filterLot.value = '';
                applyFilters();
            });

            search.addEventListener('input', applyFilters);
            filterStatus.addEventListener('change', applyFilters);
            filterType.addEventListener('change', applyFilters);
            filterLot.addEventListener('change', applyFilters);

            applyFilters();
        </script>


    </body>

    </html>
