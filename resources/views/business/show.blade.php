<!DOCTYPE html>
<html lang="ka">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $business->name }} · Vigil</title>
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
            font-family: 'Inter', system-ui, sans-serif;
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

        .back-link {
            color: #7a92a8;
            text-decoration: none;
            font-size: 0.8rem;
            display: flex;
            align-items: center;
            gap: 0.375rem;
            transition: color 0.15s;
        }

        .back-link:hover {
            color: #fff;
        }

        .header-sep {
            width: 1px;
            height: 16px;
            background: #2d4060;
            margin: 0 0.625rem;
        }

        .header-title {
            font-size: 0.95rem;
            font-weight: 600;
            color: #fff;
            letter-spacing: -0.01em;
        }

        .header-right {
            display: flex;
            align-items: center;
            gap: 0.875rem;
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
        }

        .btn-logout:hover {
            color: #fff;
            border-color: #4a6080;
        }

        /* PAGE */
        .page {
            max-width: 1200px;
            margin: 0 auto;
            padding: 1.75rem 1.5rem 4rem;
        }

        /* FLASH */
        .flash {
            padding: 0.75rem 1rem;
            border-radius: 8px;
            margin-bottom: 1.25rem;
            font-size: 0.875rem;
            font-weight: 500;
        }

        .flash-success {
            background: var(--green-bg);
            color: var(--green);
        }

        .flash-error {
            background: var(--red-bg);
            color: var(--red);
        }

        /* BUSINESS HEADER */
        .biz-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            margin-bottom: 1.75rem;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .biz-title-wrap {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            flex-wrap: wrap;
        }

        .biz-title {
            font-size: 1.375rem;
            font-weight: 700;
            letter-spacing: -0.02em;
            color: var(--text);
        }

        .badge {
            display: inline-flex;
            align-items: center;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 500;
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

        .biz-actions {
            display: flex;
            gap: 0.5rem;
        }

        .btn {
            font-family: inherit;
            font-size: 0.82rem;
            font-weight: 500;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            cursor: pointer;
            border: none;
            transition: all 0.15s;
        }

        .btn-edit {
            background: var(--navy);
            color: #fff;
        }

        .btn-edit:hover {
            background: var(--navy-2);
        }

        .btn-save {
            background: var(--green);
            color: #fff;
            display: none;
        }

        .btn-save:hover {
            background: #047857;
        }

        .btn-cancel {
            background: var(--surface);
            color: var(--text-2);
            border: 1px solid var(--border);
            display: none;
        }

        .btn-cancel:hover {
            border-color: var(--text-2);
        }

        .btn-delete {
            background: var(--red-bg);
            color: var(--red);
            border: 1px solid #fecaca;
        }

        .btn-delete:hover {
            background: var(--red);
            color: #fff;
        }

        /* GRID */
        .grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.25rem;
        }

        /* CARDS */
        .card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 12px;
            overflow: hidden;
        }

        .card-header {
            padding: 1rem 1.25rem;
            border-bottom: 1px solid var(--border);
            background: var(--surface-2);
            font-size: 0.78rem;
            font-weight: 600;
            color: var(--text-3);
            letter-spacing: 0.04em;
            text-transform: uppercase;
        }

        .card-body {
            padding: 1.25rem;
        }

        /* INFO ROWS */
        .info-row {
            display: flex;
            align-items: flex-start;
            gap: 1rem;
            padding: 0.625rem 0;
            border-bottom: 1px solid var(--border);
        }

        .info-row:last-child {
            border-bottom: none;
        }

        .info-label {
            font-size: 0.78rem;
            color: var(--text-3);
            font-weight: 500;
            min-width: 120px;
            flex-shrink: 0;
            padding-top: 0.1rem;
        }

        .info-value {
            font-size: 0.875rem;
            color: var(--text);
            flex: 1;
            word-break: break-all;
        }

        .info-empty {
            color: var(--text-3);
        }

        .contact-section-owner {
            border-left: 3px solid var(--blue);
            padding-left: 0.75rem;
            margin-bottom: 0.5rem;
        }

        .contact-section-accountant {
            border-left: 3px solid var(--green);
            padding-left: 0.75rem;
            margin-top: 0.5rem;
        }

        /* EDIT INPUTS */
        .edit-input {
            font-family: inherit;
            font-size: 0.875rem;
            padding: 0.4rem 0.625rem;
            border: 1px solid var(--border);
            border-radius: 6px;
            width: 100%;
            color: var(--text);
            outline: none;
            display: none;
            transition: border-color 0.15s;
        }

        .edit-input:focus {
            border-color: var(--blue);
        }

        .edit-select {
            font-family: inherit;
            font-size: 0.875rem;
            padding: 0.4rem 0.625rem;
            border: 1px solid var(--border);
            border-radius: 6px;
            width: 100%;
            color: var(--text);
            outline: none;
            display: none;
            background: var(--surface);
        }

        /* CORRESPONDENCE */
        .corr-item {
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 0.75rem;
        }

        .corr-item:last-child {
            margin-bottom: 0;
        }

        .corr-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 0.625rem;
        }

        .corr-title {
            font-size: 0.875rem;
            font-weight: 600;
            color: var(--text);
        }

        .badge-received {
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

        .corr-meta {
            display: flex;
            gap: 1.25rem;
            flex-wrap: wrap;
        }

        .corr-meta-item {
            font-size: 0.78rem;
            color: var(--text-2);
        }

        .corr-meta-item span {
            color: var(--text-3);
            margin-right: 0.25rem;
        }

        .corr-notes {
            margin-top: 0.5rem;
            font-size: 0.8rem;
            color: var(--text-2);
            font-style: italic;
        }

        .btn-add-corr {
            font-family: inherit;
            font-size: 0.82rem;
            font-weight: 500;
            padding: 0.5rem 1rem;
            background: var(--surface);
            color: var(--navy);
            border: 1px dashed var(--border);
            border-radius: 8px;
            cursor: pointer;
            width: 100%;
            margin-top: 0.75rem;
            transition: all 0.15s;
        }

        .btn-add-corr:hover {
            border-color: var(--navy);
            background: var(--surface-2);
        }

        /* DELETE MODAL */
        .modal-overlay {
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.5);
            z-index: 200;
            display: none;
            align-items: center;
            justify-content: center;
        }

        .modal-overlay.active {
            display: flex;
        }

        .modal {
            background: var(--surface);
            border-radius: 12px;
            padding: 1.75rem;
            max-width: 400px;
            width: 90%;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.2);
        }

        .modal-title {
            font-size: 1rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .modal-text {
            font-size: 0.875rem;
            color: var(--text-2);
            margin-bottom: 1.25rem;
        }

        .modal-actions {
            display: flex;
            gap: 0.5rem;
            justify-content: flex-end;
        }

        /* RESPONSIVE */
        @media (max-width: 600px) {
            .info-row {
                flex-direction: column;
                gap: 0.25rem;
            }

            .info-label {
                min-width: unset;
            }

            .corr-header {
                flex-wrap: wrap;
                gap: 0.5rem;
            }

            .corr-header>div {
                flex-wrap: wrap;
            }

            .corr-item {
                padding: 0.75rem;
            }
        }

        @media (max-width: 768px) {
            .grid {
                grid-template-columns: 1fr;
            }

            .card[style*="grid-column"] {
                grid-column: span 1 !important;
            }

            .biz-header {
                flex-direction: column;
            }

            .page {
                padding: 1rem 0.875rem 3rem;
            }
        }
    </style>
</head>

<body>

    <header class="header">
        <div class="header-brand">
            <a href="{{ route('business.dashboard') }}" class="back-link">← უკან</a>
            <span class="header-sep"></span>
            <span class="header-title">{{ $business->name }}</span>
        </div>
        <div class="header-right">
            <form method="POST" action="{{ route('business.logout') }}">
                @csrf
                <button type="submit" class="btn-logout">გამოსვლა</button>
            </form>
        </div>
    </header>

    <div class="page">

        @if (session('success'))
            <div class="flash flash-success">{{ session('success') }}</div>
        @endif

        {{-- BUSINESS HEADER --}}
        <div class="biz-header">
            <div class="biz-title-wrap">
                <h1 class="biz-title">{{ $business->name }}</h1>
                <span class="badge badge-{{ $business->status }}">
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
            </div>
            <div class="biz-actions">
                <button class="btn btn-edit" id="btn-edit" onclick="startEdit()">✏️ რედაქტირება</button>
                <button class="btn btn-save" id="btn-save" onclick="submitEdit()">შენახვა</button>
                <button class="btn btn-cancel" id="btn-cancel" onclick="cancelEdit()">გაუქმება</button>
                <button class="btn btn-delete"
                    onclick="document.getElementById('delete-modal').classList.add('active')">🗑 წაშლა</button>
            </div>
        </div>

        <form id="edit-form" method="POST" action="{{ route('business.update', $business) }}">
            @csrf
            @method('PUT')

            <div class="grid">

                {{-- INFO CARD --}}
                <div class="card">
                    <div class="card-header">ძირითადი ინფო</div>
                    <div class="card-body">

                        <div class="info-row">
                            <span class="info-label">სახელი</span>
                            <span class="info-value" id="view-name">{{ $business->name }}</span>
                            <input class="edit-input" name="name" value="{{ $business->name }}" id="edit-name">
                        </div>

                        <div class="info-row">
                            <span class="info-label">საიდ. კოდი</span>
                            <span class="info-value" id="view-code">{{ $business->registration_code }}</span>
                            <input class="edit-input" name="registration_code"
                                value="{{ $business->registration_code }}" id="edit-code">
                        </div>

                        <div class="info-row">
                            <span class="info-label">ACT</span>
                            <span class="info-value {{ !$business->act_number ? 'info-empty' : '' }}"
                                id="view-act">{{ $business->act_number ?? '—' }}</span>
                            <input class="edit-input" name="act_number" value="{{ $business->act_number }}"
                                placeholder="მაგ: 7-1" id="edit-act">
                        </div>

                        <div class="info-row">
                            <span class="info-label">ლოტი</span>
                            <span class="info-value" id="view-lot">{{ $business->lot_number }}</span>
                            <input class="edit-input" name="lot_number" type="number"
                                value="{{ $business->lot_number }}" id="edit-lot">
                        </div>

                        <div class="info-row">
                            <span class="info-label">ტიპი</span>
                            <span class="info-value" id="view-type">{{ $business->type }}</span>
                            <select class="edit-select" name="type" id="edit-type">
                                <option value="მეპატრონე" {{ $business->type === 'მეპატრონე' ? 'selected' : '' }}>
                                    მეპატრონე</option>
                                <option value="მოიჯარე" {{ $business->type === 'მოიჯარე' ? 'selected' : '' }}>მოიჯარე
                                </option>
                                <option value="მეიჯარე" {{ $business->type === 'მეიჯარე' ? 'selected' : '' }}>მეიჯარე
                                </option>
                                <option value="სხვა" {{ $business->type === 'სხვა' ? 'selected' : '' }}>სხვა</option>
                            </select>
                        </div>

                        <div class="info-row">
                            <span class="info-label">სტატუსი</span>
                            <span class="info-value" id="view-status">
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
                            <select class="edit-select" name="status" id="edit-status">
                                <option value="active" {{ $business->status === 'active' ? 'selected' : '' }}>მიმდინარე
                                </option>
                                <option value="pending" {{ $business->status === 'pending' ? 'selected' : '' }}>
                                    გასაგზავნი</option>
                                <option value="complete" {{ $business->status === 'complete' ? 'selected' : '' }}>
                                    დასრულებული</option>
                            </select>
                        </div>

                        <div class="info-row">
                            <span class="info-label">Drive</span>
                            <span class="info-value" id="view-drive">
                                @if ($business->drive_folder_url)
                                    <a href="{{ $business->drive_folder_url }}" target="_blank"
                                        style="color:var(--blue);">📁 Drive</a>
                                    <button type="button" onclick="openUploadModal()"
                                        style="margin-left:0.5rem;background:none;border:none;cursor:pointer;font-size:1rem;padding:0;line-height:1;"
                                        title="ფაილის ატვირთვა">⬆️</button>
                                @else
                                    <button type="button" class="btn btn-edit"
                                        style="font-size:0.78rem;padding:0.25rem 0.75rem;"
                                        onclick="document.getElementById('drive-create-form').submit()">📁 Drive
                                        შექმნა</button>
                                @endif
                            </span>
                            <input class="edit-input" name="drive_folder_url"
                                value="{{ $business->drive_folder_url }}" placeholder="https://drive.google.com/..."
                                id="edit-drive">
                        </div>

                    </div>
                </div>

                {{-- CONTACTS CARD --}}
                <div class="card">
                    <div class="card-header">კონტაქტები</div>
                    <div class="card-body">

                        <div class="contact-section-owner">
                            <div class="info-row">
                                <span class="info-label">მეპატრონე</span>
                                <span class="info-value {{ !$business->owner_name ? 'info-empty' : '' }}"
                                    id="view-owner-name">{{ $business->owner_name ?? '—' }}</span>
                                <input class="edit-input" name="owner_name" value="{{ $business->owner_name }}"
                                    placeholder="სახელი გვარი" id="edit-owner-name">
                            </div>

                            <div class="info-row">
                                <span class="info-label">ტელეფონი</span>
                                <span class="info-value {{ !$business->owner_phone ? 'info-empty' : '' }}"
                                    id="view-owner-phone">{{ $business->owner_phone ?? '—' }}</span>
                                <input class="edit-input" name="owner_phone" value="{{ $business->owner_phone }}"
                                    placeholder="5XX XXX XXX" id="edit-owner-phone">
                            </div>

                            <div class="info-row">
                                <span class="info-label">ელ-ფოსტა</span>
                                <span class="info-value {{ !$business->owner_email ? 'info-empty' : '' }}"
                                    id="view-owner-email">{{ $business->owner_email ?? '—' }}</span>
                                <input class="edit-input" type="email" name="owner_email"
                                    value="{{ $business->owner_email }}" id="edit-owner-email">
                            </div>
                        </div>

                        <div class="contact-section-accountant">
                            <div class="info-row">
                                <span class="info-label">ბუღალტერი</span>
                                <span class="info-value {{ !$business->accountant_name ? 'info-empty' : '' }}"
                                    id="view-acc-name">{{ $business->accountant_name ?? '—' }}</span>
                                <input class="edit-input" name="accountant_name"
                                    value="{{ $business->accountant_name }}" placeholder="სახელი გვარი"
                                    id="edit-acc-name">
                            </div>

                            <div class="info-row">
                                <span class="info-label">ტელეფონი</span>
                                <span class="info-value {{ !$business->accountant_phone ? 'info-empty' : '' }}"
                                    id="view-acc-phone">{{ $business->accountant_phone ?? '—' }}</span>
                                <input class="edit-input" name="accountant_phone"
                                    value="{{ $business->accountant_phone }}" placeholder="5XX XXX XXX"
                                    id="edit-acc-phone">
                            </div>

                            <div class="info-row">
                                <span class="info-label">ელ-ფოსტა</span>
                                <span class="info-value {{ !$business->accountant_email ? 'info-empty' : '' }}"
                                    id="view-acc-email">{{ $business->accountant_email ?? '—' }}</span>
                                <input class="edit-input" type="email" name="accountant_email"
                                    value="{{ $business->accountant_email }}" id="edit-acc-email">
                            </div>
                        </div>

                        <div class="info-row">
                            <span class="info-label">შენიშვნები</span>
                            <span class="info-value {{ !$business->notes ? 'info-empty' : '' }}"
                                id="view-notes">{{ $business->notes ?? '—' }}</span>
                            <textarea class="edit-input" name="notes" rows="3" style="resize:vertical;" id="edit-notes">{{ $business->notes }}</textarea>
                        </div>

                    </div>
                </div>

            </div>
        </form>

        <form id="drive-create-form" method="POST" action="{{ route('business.drive.create', $business) }}">
            @csrf
        </form>

        <div class="grid">

            {{-- CORRESPONDENCE CARD --}}
            <div class="card" style="grid-column: span 2;">
                <div class="card-header">მიმოწერა</div>
                <div class="card-body" style="max-height:520px;overflow-y:auto;">
                    @forelse($business->correspondence->sortBy('round') as $corr)
                        <div class="corr-item">
                            <div class="corr-header">
                                <span class="corr-title">მიმოწერა-{{ $corr->round }}</span>
                                <span class="badge badge-{{ $corr->status }}">
                                    @switch($corr->status)
                                        @case('received')
                                            მიღებულია
                                        @break

                                        @case('pending')
                                            მოლოდინში
                                        @break

                                        @case('overdue')
                                            ვადაგადაცილებული
                                        @break
                                    @endswitch
                                </span>
                                <div style="display:flex;gap:0.375rem;align-items:center;">
                                    @if ($corr->status === 'pending' || $corr->status === 'overdue')
                                        <form method="POST"
                                            action="{{ route('business.correspondence.received', [$business, $corr]) }}"
                                            style="display:inline;">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="btn btn-edit"
                                                style="font-size:0.75rem;padding:0.25rem 0.625rem;">✅
                                                მიღებულია</button>
                                        </form>
                                    @endif
                                    <button type="button" class="btn btn-edit"
                                        style="font-size:0.75rem;padding:0.25rem 0.625rem;background:var(--slate);"
                                        onclick="toggleCorrEdit({{ $corr->id }})">✏️</button>
                                    <button type="button" class="btn btn-delete"
                                        style="font-size:0.75rem;padding:0.25rem 0.625rem;"
                                        onclick="showCorrDeleteModal({{ $corr->id }}, {{ $corr->round }})">🗑</button>
                                </div>
                            </div>
                            <div class="corr-meta">
                                @if ($corr->sent_at)
                                    <div class="corr-meta-item">
                                        <span>გაგზავნილია:</span>{{ $corr->sent_at->format('d.m.Y') }}
                                    </div>
                                @endif
                                @if ($corr->deadline_at)
                                    <div class="corr-meta-item">
                                        <span>ვადა:</span>{{ $corr->deadline_at->format('d.m.Y') }}
                                    </div>
                                @endif
                                @if ($corr->received_at)
                                    <div class="corr-meta-item">
                                        <span>მიღებულია:</span>{{ $corr->received_at->format('d.m.Y') }}
                                    </div>
                                @endif
                                @if ($corr->drive_folder_url)
                                    <div class="corr-meta-item"><a href="{{ $corr->drive_folder_url }}"
                                            target="_blank" style="color:var(--blue);">📁 Drive</a></div>
                                @endif
                            </div>
                            @if ($corr->notes)
                                <div class="corr-notes">{{ $corr->notes }}</div>
                            @endif

                            <div id="corr-edit-{{ $corr->id }}"
                                style="display:none;margin-top:0.75rem;padding-top:0.75rem;border-top:1px solid var(--border);">
                                <form method="POST"
                                    action="{{ route('business.correspondence.update', [$business, $corr]) }}">
                                    @csrf
                                    @method('PUT')
                                    <div
                                        style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:0.75rem;margin-bottom:0.75rem;">
                                        <div style="display:flex;flex-direction:column;gap:0.375rem;">
                                            <label
                                                style="font-size:0.78rem;font-weight:500;color:var(--text-2);">გაგზავნილია</label>
                                            <input type="date" name="sent_at"
                                                value="{{ $corr->sent_at?->format('Y-m-d') }}"
                                                style="font-family:inherit;font-size:0.875rem;padding:0.55rem 0.75rem;border:1px solid var(--border);border-radius:8px;outline:none;">
                                        </div>
                                        <div style="display:flex;flex-direction:column;gap:0.375rem;">
                                            <label
                                                style="font-size:0.78rem;font-weight:500;color:var(--text-2);">ვადა</label>
                                            <input type="date" name="deadline_at"
                                                value="{{ $corr->deadline_at?->format('Y-m-d') }}"
                                                style="font-family:inherit;font-size:0.875rem;padding:0.55rem 0.75rem;border:1px solid var(--border);border-radius:8px;outline:none;">
                                        </div>
                                        <div style="display:flex;flex-direction:column;gap:0.375rem;">
                                            <label
                                                style="font-size:0.78rem;font-weight:500;color:var(--text-2);">მიღებულია</label>
                                            <input type="date" name="received_at"
                                                value="{{ $corr->received_at?->format('Y-m-d') }}"
                                                style="font-family:inherit;font-size:0.875rem;padding:0.55rem 0.75rem;border:1px solid var(--border);border-radius:8px;outline:none;">
                                        </div>
                                    </div>
                                    <div
                                        style="display:grid;grid-template-columns:1fr 1fr;gap:0.75rem;margin-bottom:0.75rem;">
                                        <div style="display:flex;flex-direction:column;gap:0.375rem;">
                                            <label
                                                style="font-size:0.78rem;font-weight:500;color:var(--text-2);">სტატუსი</label>
                                            <select name="status"
                                                style="font-family:inherit;font-size:0.875rem;padding:0.55rem 0.75rem;border:1px solid var(--border);border-radius:8px;outline:none;background:var(--surface);">
                                                <option value="pending"
                                                    {{ $corr->status === 'pending' ? 'selected' : '' }}>მოლოდინში
                                                </option>
                                                <option value="received"
                                                    {{ $corr->status === 'received' ? 'selected' : '' }}>მიღებულია
                                                </option>
                                                <option value="overdue"
                                                    {{ $corr->status === 'overdue' ? 'selected' : '' }}>
                                                    ვადაგადაცილებული</option>
                                            </select>
                                        </div>
                                        <div style="display:flex;flex-direction:column;gap:0.375rem;">
                                            <label
                                                style="font-size:0.78rem;font-weight:500;color:var(--text-2);">შენიშვნები</label>
                                            <input type="text" name="notes" value="{{ $corr->notes }}"
                                                style="font-family:inherit;font-size:0.875rem;padding:0.55rem 0.75rem;border:1px solid var(--border);border-radius:8px;outline:none;">
                                        </div>
                                    </div>
                                    <div style="display:flex;gap:0.5rem;">
                                        <button type="submit" class="btn btn-edit"
                                            style="font-size:0.82rem;">შენახვა</button>
                                        <button type="button" onclick="toggleCorrEdit({{ $corr->id }})"
                                            class="btn btn-cancel"
                                            style="display:inline-flex;font-size:0.82rem;">გაუქმება</button>
                                    </div>
                                </form>
                            </div>

                        </div>
                        @empty
                            <p style="color:var(--text-3);font-size:0.875rem;">მიმოწერა არ არის</p>
                        @endforelse

                        <div id="corr-form" style="display:none;margin-top:0.75rem;">
                            <form method="POST" action="{{ route('business.correspondence.store', $business) }}">
                                @csrf
                                <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;margin-bottom:1rem;">
                                    <div style="display:flex;flex-direction:column;gap:0.375rem;">
                                        <label style="font-size:0.78rem;font-weight:500;color:var(--text-2);">გაგზავნის
                                            თარიღი <span style="color:var(--red);">*</span></label>
                                        <input type="date" name="sent_at" required
                                            style="font-family:inherit;font-size:0.875rem;padding:0.55rem 0.75rem;border:1px solid var(--border);border-radius:8px;outline:none;">
                                    </div>
                                    <div style="display:flex;flex-direction:column;gap:0.375rem;">
                                        <label style="font-size:0.78rem;font-weight:500;color:var(--text-2);">ვადა
                                            <span style="color:var(--red);">*</span></label>
                                        <input type="date" name="deadline_at" required
                                            style="font-family:inherit;font-size:0.875rem;padding:0.55rem 0.75rem;border:1px solid var(--border);border-radius:8px;outline:none;">
                                    </div>
                                </div>
                                <div style="display:flex;flex-direction:column;gap:0.375rem;margin-bottom:1rem;">
                                    <label
                                        style="font-size:0.78rem;font-weight:500;color:var(--text-2);">შენიშვნები</label>
                                    <textarea name="notes" rows="2"
                                        style="font-family:inherit;font-size:0.875rem;padding:0.55rem 0.75rem;border:1px solid var(--border);border-radius:8px;outline:none;resize:vertical;"></textarea>
                                </div>
                                <div style="display:flex;gap:0.5rem;">
                                    <button type="submit" class="btn btn-edit"
                                        style="font-size:0.82rem;">შენახვა</button>
                                    <button type="button"
                                        onclick="document.getElementById('corr-form').style.display='none';document.getElementById('btn-add-corr').style.display='block';"
                                        class="btn btn-cancel"
                                        style="display:inline-flex;font-size:0.82rem;">გაუქმება</button>
                                </div>
                            </form>
                        </div>

                        <button type="button" class="btn-add-corr" id="btn-add-corr"
                            onclick="document.getElementById('corr-form').style.display='block';this.style.display='none';">+
                            მიმოწერის დამატება</button>
                    </div>
                </div>

            </div>

        </div>

        {{-- UPLOAD MODAL --}}
        <div class="modal-overlay" id="upload-modal" onclick="if(event.target===this)closeUploadModal()">
            <div class="modal">
                <div class="modal-title">📤 ფაილის ატვირთვა</div>
                <div style="font-size:0.85rem;color:var(--text-secondary);margin-bottom:1rem;">
                    {{ $business->registration_code }} · {{ $business->name }}</div>
                <div style="margin-bottom:1rem;">
                    <label
                        style="display:block;font-size:0.82rem;font-weight:500;margin-bottom:0.375rem;">საქაღალდე</label>
                    <select id="upload-folder-select"
                        style="width:100%;padding:0.45rem 0.65rem;border:1px solid var(--border);border-radius:6px;background:var(--bg);color:var(--text);font-size:0.88rem;">
                        <option value="">— ჩატვირთვა... —</option>
                    </select>
                </div>
                <div style="margin-bottom:1.25rem;">
                    <label style="display:block;font-size:0.82rem;font-weight:500;margin-bottom:0.375rem;">ფაილები</label>
                    <input type="file" id="upload-files" multiple
                        style="width:100%;font-size:0.85rem;color:var(--text);">
                </div>
                <div id="upload-status" style="font-size:0.82rem;min-height:1.2em;margin-bottom:0.75rem;"></div>
                <div class="modal-actions">
                    <button type="button" class="btn btn-cancel" style="display:inline-flex;"
                        onclick="closeUploadModal()">გაუქმება</button>
                    <button type="button" class="btn btn-edit" id="upload-submit-btn" onclick="submitUpload()"
                        style="background:var(--blue);">ატვირთვა</button>
                </div>
            </div>
        </div>

        {{-- DELETE MODAL --}}
        <div class="modal-overlay" id="delete-modal">
            <div class="modal">
                <div class="modal-title">ბიზნესის წაშლა</div>
                <div class="modal-text">დარწმუნებული ხართ, რომ გსურთ <strong>{{ $business->name }}</strong> წაშლა? ეს
                    მოქმედება შეუქცევადია.</div>
                <div class="modal-actions">
                    <button class="btn btn-cancel" style="display:inline-flex;"
                        onclick="document.getElementById('delete-modal').classList.remove('active')">გაუქმება</button>
                    <form method="POST" action="{{ route('business.destroy', $business) }}">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-delete">წაშლა</button>
                    </form>
                </div>
            </div>
        </div>

        {{-- CORRESPONDENCE DELETE MODAL --}}
        <div class="modal-overlay" id="corr-delete-modal">
            <div class="modal">
                <div class="modal-title">მიმოწერის წაშლა</div>
                <div class="modal-text">დარწმუნებული ხართ, რომ გსურთ <strong id="corr-delete-label"></strong> წაშლა?</div>
                <div class="modal-actions">
                    <button class="btn btn-cancel" style="display:inline-flex;"
                        onclick="document.getElementById('corr-delete-modal').classList.remove('active')">გაუქმება</button>
                    <form id="corr-delete-form" method="POST">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-delete">წაშლა</button>
                    </form>
                </div>
            </div>
        </div>

        <script>
            const editFields = [
                'name', 'code', 'act', 'lot', 'drive',
                'owner-name', 'owner-phone', 'owner-email',
                'acc-name', 'acc-phone', 'acc-email', 'notes'
            ];
            const editSelects = ['type', 'status'];

            function startEdit() {
                editFields.forEach(f => {
                    document.getElementById('view-' + f).style.display = 'none';
                    document.getElementById('edit-' + f).style.display = 'block';
                });
                editSelects.forEach(f => {
                    document.getElementById('view-' + f).style.display = 'none';
                    document.getElementById('edit-' + f).style.display = 'block';
                });
                document.getElementById('btn-edit').style.display = 'none';
                document.getElementById('btn-save').style.display = 'inline-flex';
                document.getElementById('btn-cancel').style.display = 'inline-flex';
            }

            function cancelEdit() {
                editFields.forEach(f => {
                    document.getElementById('view-' + f).style.display = '';
                    document.getElementById('edit-' + f).style.display = 'none';
                });
                editSelects.forEach(f => {
                    document.getElementById('view-' + f).style.display = '';
                    document.getElementById('edit-' + f).style.display = 'none';
                });
                document.getElementById('btn-edit').style.display = '';
                document.getElementById('btn-save').style.display = 'none';
                document.getElementById('btn-cancel').style.display = 'none';
            }

            function submitEdit() {
                document.getElementById('edit-form').submit();
            }

            function toggleCorrEdit(id) {
                const el = document.getElementById('corr-edit-' + id);
                el.style.display = el.style.display === 'none' ? 'block' : 'none';
            }

            function showCorrDeleteModal(id, round) {
                document.getElementById('corr-delete-label').textContent = 'მიმოწერა-' + round;
                document.getElementById('corr-delete-form').action = '/business/{{ $business->id }}/correspondence/' + id;
                document.getElementById('corr-delete-modal').classList.add('active');
            }

            function openUploadModal() {
                const sel = document.getElementById('upload-folder-select');
                sel.innerHTML = '<option value="">— ჩატვირთვა... —</option>';
                document.getElementById('upload-files').value = '';
                document.getElementById('upload-status').textContent = '';
                document.getElementById('upload-modal').classList.add('active');

                fetch('{{ route('business.upload.folders') }}?business_id={{ $business->id }}')
                    .then(r => r.json())
                    .then(data => {
                        sel.innerHTML = '<option value="">— საქაღალდე —</option>';
                        (data.folders || []).forEach(f => {
                            const o = document.createElement('option');
                            o.value = f;
                            o.textContent = f;
                            sel.appendChild(o);
                        });
                    });
            }

            function closeUploadModal() {
                document.getElementById('upload-modal').classList.remove('active');
            }

            async function submitUpload() {
                const folder = document.getElementById('upload-folder-select').value;
                const files = Array.from(document.getElementById('upload-files').files);
                const status = document.getElementById('upload-status');
                const btn = document.getElementById('upload-submit-btn');

                if (!folder) {
                    status.textContent = '⚠️ აირჩიეთ საქაღალდე';
                    return;
                }
                if (!files.length) {
                    status.textContent = '⚠️ აირჩიეთ ფაილი';
                    return;
                }

                btn.disabled = true;
                const CHUNK = 5;
                const total = files.length;
                let uploaded = [];
                let failed = [];

                for (let i = 0; i < total; i += CHUNK) {
                    const chunk = files.slice(i, i + CHUNK);
                    status.textContent = '⏳ ატვირთვა... ' + Math.min(i + CHUNK, total) + ' / ' + total;

                    const fd = new FormData();
                    fd.append('business_id', '{{ $business->id }}');
                    fd.append('folder', folder);
                    for (const f of chunk) fd.append('files[]', f);

                    try {
                        const res = await fetch('{{ route('business.upload.store') }}', {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                            },
                            body: fd,
                        });
                        const data = await res.json();
                        if (data.uploaded) uploaded = uploaded.concat(data.uploaded);
                        if (data.failed) failed = failed.concat(data.failed);
                    } catch {
                        failed = failed.concat(chunk.map(f => f.name));
                    }
                }

                btn.disabled = false;
                document.getElementById('upload-files').value = '';

                if (failed.length) {
                    status.textContent = '✅ ატვირთულია: ' + uploaded.length + '  ❌ ვერ ატვირთა: ' + failed.length;
                } else {
                    status.textContent = '✅ ატვირთულია: ' + uploaded.length + ' ფაილი';
                }
                setTimeout(() => closeUploadModal(), 1500);
            }
        </script>

    </body>

    </html>
