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


        /* DRAFT CARD */
        .draft-toggle {
            cursor: pointer;
            user-select: none;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .draft-toggle:hover {
            background: var(--border);
        }

        .draft-chevron {
            transition: transform 0.2s;
            font-size: 0.75rem;
            color: var(--text-3);
        }

        .draft-chevron.open {
            transform: rotate(90deg);
        }

        .draft-body {
            display: none;
            padding: 1.25rem;
        }

        .draft-field {
            margin-bottom: 1rem;
        }

        .draft-label {
            font-size: 0.72rem;
            font-weight: 600;
            color: var(--text-3);
            letter-spacing: 0.05em;
            text-transform: uppercase;
            margin-bottom: 0.375rem;
            display: block;
        }

        .draft-input-row {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .draft-input {
            font-family: inherit;
            font-size: 0.875rem;
            padding: 0.5rem 0.75rem;
            border: 1px solid var(--border);
            border-radius: 8px;
            width: 100%;
            color: var(--text);
            outline: none;
            background: var(--surface);
            transition: border-color 0.15s;
        }

        .draft-input:focus {
            border-color: var(--blue);
        }

        .draft-textarea {
            font-family: inherit;
            font-size: 0.875rem;
            padding: 0.5rem 0.75rem;
            border: 1px solid var(--border);
            border-radius: 8px;
            width: 100%;
            color: var(--text);
            outline: none;
            background: var(--surface);
            resize: vertical;
            min-height: 220px;
            line-height: 1.6;
            transition: border-color 0.15s;
        }

        .draft-textarea:focus {
            border-color: var(--blue);
        }

        .btn-copy {
            font-family: inherit;
            font-size: 0.75rem;
            padding: 0.4rem 0.625rem;
            border-radius: 6px;
            border: 1px solid var(--border);
            background: var(--surface);
            color: var(--text-2);
            cursor: pointer;
            white-space: nowrap;
            flex-shrink: 0;
            transition: all 0.15s;
        }

        .btn-copy:hover {
            border-color: var(--text-2);
        }

        .btn-copy.copied {
            border-color: var(--green);
            color: var(--green);
            background: var(--green-bg);
        }

        .btn-copy:disabled {
            opacity: 0.35;
            cursor: not-allowed;
        }

        .cc-chips {
            display: flex;
            flex-wrap: wrap;
            gap: 0.375rem;
            padding: 0.4rem 0.5rem;
            border: 1px solid var(--border);
            border-radius: 8px;
            background: var(--surface);
            align-items: center;
            min-height: 42px;
            flex: 1;
        }

        .cc-chip {
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
            background: var(--blue-bg);
            color: var(--blue);
            font-size: 0.78rem;
            padding: 0.2rem 0.5rem;
            border-radius: 20px;
        }

        .cc-chip.locked {
            background: var(--slate-bg);
            color: var(--slate);
        }

        .cc-chip-remove {
            background: none;
            border: none;
            cursor: pointer;
            color: inherit;
            font-size: 0.75rem;
            padding: 0;
            line-height: 1;
            opacity: 0.6;
        }

        .cc-chip-remove:hover {
            opacity: 1;
        }

        .cc-add-input {
            font-family: inherit;
            font-size: 0.8rem;
            border: none;
            outline: none;
            background: transparent;
            color: var(--text);
            min-width: 140px;
            flex: 1;
        }

        .draft-deadline-hint {
            font-size: 0.78rem;
            color: var(--text-3);
            margin-top: 0.375rem;
        }

        .btn-sent {
            font-family: inherit;
            font-size: 0.875rem;
            font-weight: 600;
            padding: 0.625rem 1.5rem;
            background: var(--green);
            color: #fff;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            transition: background 0.15s;
        }

        .btn-sent:hover {
            background: #047857;
        }

        .btn-sent:disabled {
            opacity: 0.6;
            cursor: not-allowed;
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

        .edit-checkbox-group {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem 1rem;
            padding: 0.2rem 0;
        }

        .edit-checkbox-item {
            display: flex;
            align-items: center;
            gap: 0.4rem;
            font-size: 0.875rem;
            color: var(--text);
            cursor: pointer;
        }

        .edit-checkbox-item input[type="checkbox"] {
            width: 16px;
            height: 16px;
            cursor: pointer;
            accent-color: var(--navy);
        }

        .corr-edit-grid-2 {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0.75rem;
            margin-bottom: 0.75rem;
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

            .draft-input-row {
                flex-wrap: wrap;
            }

            .biz-actions {
                flex-wrap: wrap;
            }

            .draft-deadline-hint {
                width: 100%;
            }

            .corr-edit-grid-2 {
                grid-template-columns: 1fr;
            }

            .draft-textarea {
                min-height: 160px;
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

        {{-- EDIT FORM: info + contacts only --}}
        <form id="edit-form" method="POST" action="{{ route('business.update', $business) }}" novalidate>
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
                            <span class="info-label">საკადასტრო</span>
                            <span class="info-value {{ !$business->cadastral_code ? 'info-empty' : '' }}"
                                id="view-cadastral">{{ $business->cadastral_code ?? '—' }}</span>
                            <input class="edit-input" name="cadastral_code" value="{{ $business->cadastral_code }}"
                                placeholder="მაგ: 05.19.22.026" id="edit-cadastral">
                        </div>

                        <div class="info-row">
                            <span class="info-label">ტიპი</span>
                            <span class="info-value" id="view-type">{{ implode(', ', (array) $business->type) }}</span>
                            <div class="edit-checkbox-group" id="edit-type" style="display:none;">
                                @foreach (['მეპატრონე', 'მოიჯარე', 'მეიჯარე', 'სხვა'] as $t)
                                    <label class="edit-checkbox-item">
                                        <input type="checkbox" name="type[]" value="{{ $t }}"
                                            {{ in_array($t, (array) $business->type) ? 'checked' : '' }}>
                                        {{ $t }}
                                    </label>
                                @endforeach
                            </div>
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
                                <option value="active" {{ $business->status === 'active' ? 'selected' : '' }}>
                                    მიმდინარე
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
                                <input class="edit-input" type='tel' name="owner_phone"
                                    value="{{ $business->owner_phone }}" placeholder="5XX XXX XXX"
                                    id="edit-owner-phone">
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
                                <input class="edit-input" type='tel' name="accountant_phone"
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
                            <span class="info-value {{ !$business->notes ? 'info-empty' : '' }}" id="view-notes"
                                style="white-space: pre-wrap; word-break: break-word;">{{ $business->notes ?? '—' }}</span>
                            <textarea class="edit-input" name="notes" rows="3" style="resize:vertical;" id="edit-notes">{{ $business->notes }}</textarea>
                        </div>

                    </div>
                </div>

            </div>
        </form>

        {{-- DRAFT CARD — standalone, outside every other form --}}
        <div class="card" style="margin-top:1.25rem;">
            <div class="card-header draft-toggle" onclick="toggleDraft()" id="draft-header">
                <span>✉️ წერილის პროექტი</span>
                <span class="draft-chevron" id="draft-chevron">▶</span>
            </div>
            <div class="draft-body" id="draft-body">

                @php
                    $signatures = [
                        'Levan.kalmakhelidze@colliers.ge' =>
                            "\n\nLevan Kalmakhelidze\nValuation & Advisory Services\nLevan.kalmakhelidze@colliers.ge\nDirect:032 222 44 77 | Mobile:+995 557 65 38 73\nFreedom Square|Business Center Tabidze N1|Tbilisi/0105 |Georgia",
                        'ramaz.sharabidze@colliers.ge' =>
                            "\n\nRamaz Sharabidze\nValuation & Advisory Services\nramaz.sharabidze@colliers.ge\nDirect:032 222 44 77 | Mobile:+995 577 30 33 34\nFreedom Square|Business Center Tabidze N1|Tbilisi/0105 |Georgia",
                    ];
                    $userEmail = auth('business')->user()?->email ?? '';
                    $signature = $signatures[$userEmail] ?? '';
                @endphp


                @php
                    $draftWarnings = [];
                    if (!$business->cadastral_code) {
                        $draftWarnings[] = 'საკადასტრო კოდი არ არის — შეიყვანეთ ძირითად ინფოში';
                    }
                    $draftBlocked = count($draftWarnings) > 0;
                @endphp

                @if ($draftBlocked)
                    <div
                        style="background:var(--amber-bg);border:1px solid #fde68a;border-radius:8px;padding:0.75rem 1rem;margin-bottom:1rem;">
                        <div style="font-size:0.78rem;font-weight:600;color:var(--amber);margin-bottom:0.25rem;">⚠️
                            გაგზავნა შეუძლებელია</div>
                        @foreach ($draftWarnings as $w)
                            <div style="font-size:0.82rem;color:var(--amber);">— {{ $w }}</div>
                        @endforeach
                    </div>
                @endif

                {{-- TO --}}
                <div class="draft-field">
                    <label class="draft-label">TO</label>
                    <div class="draft-input-row">
                        <input class="draft-input" id="draft-to"
                            value="{{ collect([$business->owner_email, $business->accountant_email])->filter()->implode('; ') }}"
                            placeholder="მიმღების ელ-ფოსტა">
                        <button type="button" class="btn-copy" onclick="draftCopy('draft-to', this)"
                            {{ $draftBlocked ? 'disabled' : '' }}>📋</button>
                    </div>
                </div>

                {{-- CC --}}
                <div class="draft-field">
                    <label class="draft-label">CC</label>
                    <div class="draft-input-row">
                        <div class="cc-chips" id="cc-chips" onclick="document.getElementById('cc-add').focus()">
                            @foreach (config('services.business.internal_emails') as $email)
                                @if ($email)
                                    <span class="cc-chip" data-email="{{ $email }}">
                                        {{ $email }}
                                        <button type="button" class="cc-chip-remove"
                                            onclick="removeChip(this, event)">✕</button>
                                    </span>
                                @endif
                            @endforeach
                            <span class="cc-chip locked" data-email="{{ config('services.business.own_email') }}"
                                title="Engine — ყოველთვის CC-ში">
                                {{ config('services.business.own_email') }}
                            </span>
                            <input class="cc-add-input" id="cc-add" placeholder="დამატება..."
                                onkeydown="ccInputKeydown(event)">
                        </div>
                        <button type="button" class="btn-copy" onclick="copyCC(this)"
                            {{ $draftBlocked ? 'disabled' : '' }}>📋</button>
                    </div>
                </div>

                {{-- SUBJECT --}}
                <div class="draft-field">
                    <label class="draft-label">Subject</label>
                    <div class="draft-input-row">
                        <input class="draft-input" id="draft-subject"
                            value="{{ $business->name }} / კოდი: {{ $business->registration_code }}">
                        <button type="button" class="btn-copy" onclick="draftCopy('draft-subject', this)"
                            {{ $draftBlocked ? 'disabled' : '' }}>📋</button>
                    </div>
                </div>

                {{-- BODY --}}
                <div class="draft-field">
                    <label class="draft-label">Body</label>
                    <div class="draft-input-row" style="align-items:flex-start;">
                        <textarea class="draft-textarea" id="draft-body-text">მოგესალმებით,

გწერთ კომპანია Colliers-დან. საქართველოს რეგიონული განვითარებისა და ინფრასტრუქტურის სამინისტროს საავტომობილო გზების დეპარტამენტის პროექტის ფარგლებში ვახორციელებთ ბიზნესის მოცდენისთვის საკომპენსაციო თანხის დაანგარიშებას. ამისათვის გთხოვთ, საკადასტრო კოდზე {{ $business->cadastral_code ?? '—' }} არსებულ თქვენს ბიზნეს საქმიანობაზე მოგვაწოდოთ შემდეგი ინფორმაცია და დოკუმენტები:

- საწარმოს იურიდიული დასახელება;

- საიდენტიფიკაციო კოდი;

- საქმიანობის სახე (მოკლე აღწერით);

- საქმიანობის დაწყების წელი;

- იჯარის ხელშეკრულება (ასეთის არსებობის შემთხვევაში) და საიჯარო ქირის გადახდის დამადასტურებელი დოკუმენტები (2025 წლიდან - დღემდე);

- საწარმოს წლიური მოგება 2023, 2024 და 2025 წლებისთვის, თითოეული წლისთვის ცალ-ცალკე. გთხოვთ, თან დაურთოთ შემოსავლების სამსახურის პორტალიდან (rs.ge) ჩამოტვირთული დამადასტურებელი დოკუმენტები (მოგება ზარალის უწყისი);

- დასაქმებულთა სია ყველა თანამშრომლის პირადი ნომრისა და ხელფასის მითითებით. ასევე შემოსავლების სამსახურის პორტალიდან ჩამოტვირთული საშემოსავლო გადასახადის დეკლარაციები 2025 წლის თითოეული თვისთვის;

- ბუღალტერის საკონტაქტო ინფორმაცია;

გთხოვთ, გაითვალისწინოთ:

- მოწოდებული ინფორმაცია უნდა მხოლოდ იმ ობიექტს, რომელიც რეგისტრირებულია ან ბიზნეს საქმიანობას ახორციელებს ზემოთ მითითებულ საკადასტრო კოდზე;

- თანხების მითითებისას დააზუსტეთ, არის თუ არა ისინი გადასახადების ჩათვლით.

მოწოდებული ინფორმაცია, მათ შორის თანამშრომლების პერსონალური მონაცემები, გამოყენებული იქნება მხოლოდ კომპენსაციის დაანგარიშების მიზნით და დაცული იქნება კონფიდენციალურობის მოთხოვნების შესაბამისად.

გთხოვთ, აღნიშნული დოკუმენტები გამოგვიგზავნოთ არაუმეტეს 10 კალენდარულ დღეში და წერილის სათაურში (subject line) მიუთითოთ იურიდიული სახელი და საიდენტიფიკაციო კოდი.

პატივისცემით,{{ $signature }}</textarea>
                        <button type="button" class="btn-copy" onclick="draftCopy('draft-body-text', this)"
                            style="align-self:flex-start;" {{ $draftBlocked ? 'disabled' : '' }}>📋</button>
                    </div>
                </div>

                {{-- DEADLINE HINT + SENT BUTTON --}}
                <div
                    style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:0.75rem;margin-top:0.5rem;">
                    <div class="draft-deadline-hint">
                        ⏱ ვადა: <strong>{{ now()->addDays(10)->format('d.m.Y') }}</strong>
                    </div>
                    <form method="POST" action="{{ route('business.correspondence.store', $business) }}"
                        id="draft-sent-form">
                        @csrf
                        <input type="hidden" name="sent_at" value="{{ now()->format('Y-m-d') }}">
                        <input type="hidden" name="deadline_at" value="{{ now()->addDays(10)->format('Y-m-d') }}">
                        <input type="hidden" name="recipient_email" id="draft-sent-to">
                        <input type="hidden" name="cc_emails" id="draft-sent-cc">
                        <input type="hidden" name="subject" id="draft-sent-subject">
                        <input type="hidden" name="body" id="draft-sent-body">
                        <input type="hidden" name="status" value="active">
                        <button type="button" class="btn-sent" id="draft-sent-btn" onclick="submitDraftSent()"
                            {{ $draftBlocked ? 'disabled' : '' }}>გაგზავნილია ✓</button>
                    </form>
                </div>

            </div>
        </div>
        {{-- /draft card --}}

        <form id="drive-create-form" method="POST" action="{{ route('business.drive.create', $business) }}">
            @csrf
        </form>

        {{-- CORRESPONDENCE GRID --}}
        <div class="grid" style="margin-top:1.25rem;">

            {{-- CORRESPONDENCE CARD --}}
            <div class="card" style="grid-column: span 2;">
                <div class="card-header">მიმოწერა</div>
                <div class="card-body" style="max-height:520px;overflow-y:auto;">
                    @forelse($business->correspondence->sortBy('round') as $corr)
                        <div class="corr-item">
                            <div class="corr-header">
                                <span class="corr-title">
                                    @if ($corr->sent_at && !$corr->received_at)
                                        📤 გაგზავნილია · #{{ $corr->round }}
                                    @else
                                        📥 შემოსული · #{{ $corr->round }}
                                    @endif
                                </span>
                                @if ($corr->status === 'overdue')
                                    <span class="badge badge-overdue">ვადაგადაცილებული</span>
                                @endif
                                <div style="display:flex;gap:0.375rem;align-items:center;">
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

                            {{-- Inline edit for existing correspondence (dates/status/notes only) --}}
                            <div id="corr-edit-{{ $corr->id }}"
                                style="display:none;margin-top:0.75rem;padding-top:0.75rem;border-top:1px solid var(--border);">
                                <form method="POST"
                                    action="{{ route('business.correspondence.update', [$business, $corr]) }}">
                                    @csrf
                                    @method('PUT')
                                    <div class="corr-edit-grid-2">
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
                                                style="font-size:0.78rem;font-weight:500;color:var(--text-2);">სტატუსი</label>
                                            <select name="status"
                                                style="font-family:inherit;font-size:0.875rem;padding:0.55rem 0.75rem;border:1px solid var(--border);border-radius:8px;outline:none;background:var(--surface);">
                                                <option value="active"
                                                    {{ $corr->status === 'active' ? 'selected' : '' }}>მიმდინარე
                                                </option>
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
                                                style="font-size:0.78rem;font-weight:500;color:var(--text-2);">მიღებულია</label>
                                            <input type="date" name="received_at"
                                                value="{{ $corr->received_at?->format('Y-m-d') }}"
                                                style="font-family:inherit;font-size:0.875rem;padding:0.55rem 0.75rem;border:1px solid var(--border);border-radius:8px;outline:none;">
                                        </div>
                                    </div>
                                    <div
                                        style="display:flex;flex-direction:column;gap:0.375rem;margin-bottom:0.75rem;">
                                        <label
                                            style="font-size:0.78rem;font-weight:500;color:var(--text-2);">შენიშვნები</label>
                                        <input type="text" name="notes" value="{{ $corr->notes }}"
                                            style="font-family:inherit;font-size:0.875rem;padding:0.55rem 0.75rem;border:1px solid var(--border);border-radius:8px;outline:none;">
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

                </div>
            </div>

        </div>
        {{-- /correspondence grid --}}

        {{-- DRIVE FILES CARD --}}
        @if ($business->drive_folder_url)
            <div class="card" style="margin-top:1.25rem;">
                <div class="card-header" style="display:flex;align-items:center;justify-content:space-between;">
                    <span>📂 შემომავალი ფაილები</span>
                    <button type="button" class="btn btn-edit"
                        style="font-size:0.75rem;padding:0.25rem 0.75rem;text-transform:none;letter-spacing:0;"
                        onclick="loadDriveFiles(this)">განახლება</button>
                </div>
                <div class="card-body" id="drive-files-body">
                    <p style="color:var(--text-3);font-size:0.875rem;">დააჭირეთ "განახლებას" შემომავალი ფაილების
                        სანახავად.</p>
                </div>
            </div>
        @endif
        {{-- /drive files card --}}

        {{-- DRIVE STATES CARD --}}
        @if ($business->drive_folder_url)
            @php
                $driveStates = $business->driveStates->keyBy('subfolder_name');
                $syncedAt = $business->driveStates->max('synced_at');
            @endphp
            <div class="card" style="margin-top:1.25rem;">
                <div class="card-header" style="display:flex;align-items:center;justify-content:space-between;">
                    <span>🗂 საქაღალდეების სტატუსი</span>
                    @if ($syncedAt)
                        <span style="font-size:0.72rem;font-weight:400;color:var(--text-3);letter-spacing:0;">
                            განახლდა {{ \Carbon\Carbon::parse($syncedAt)->setTimezone('Asia/Tbilisi')->format('H:i') }}
                        </span>
                    @endif
                </div>
                <div class="card-body" style="display:flex;flex-wrap:wrap;gap:0.5rem 1rem;">
                    @foreach ([...config('vigil.business.drive_subfolders'), 'მიმოწერა'] as $subfolder)
                        @php $state = $driveStates->get($subfolder); @endphp
                        <div
                            style="display:flex;align-items:center;gap:0.375rem;font-size:0.82rem;color:var(--text-2);min-width:160px;">
                            <span>{{ $state?->has_files ? '✅' : '⬜' }}</span>
                            <span>{{ $subfolder }}</span>
                        </div>
                    @endforeach
                    @if ($driveStates->isEmpty())
                        <p style="color:var(--text-3);font-size:0.875rem;">სინქრონიზაცია ჯერ არ მომხდარა.</p>
                    @endif
                </div>
            </div>
        @endif
        {{-- /drive states card --}}

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
                    <label
                        style="display:block;font-size:0.82rem;font-weight:500;margin-bottom:0.375rem;">ფაილები</label>
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
                <div class="modal-text">დარწმუნებული ხართ, რომ გსურთ <strong id="corr-delete-label"></strong> წაშლა?
                </div>
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
                'name', 'code', 'act', 'lot', 'cadastral', 'drive',
                'owner-name', 'owner-phone', 'owner-email',
                'acc-name', 'acc-phone', 'acc-email', 'notes'
            ];
            const editSelects = ['status'];
            const editCheckboxGroups = ['type'];

            function startEdit() {
                editFields.forEach(f => {
                    document.getElementById('view-' + f).style.display = 'none';
                    document.getElementById('edit-' + f).style.display = 'block';
                });
                editSelects.forEach(f => {
                    document.getElementById('view-' + f).style.display = 'none';
                    document.getElementById('edit-' + f).style.display = 'block';
                });
                editCheckboxGroups.forEach(f => {
                    document.getElementById('view-' + f).style.display = 'none';
                    document.getElementById('edit-' + f).style.display = 'flex';
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
                editCheckboxGroups.forEach(f => {
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
                document.getElementById('corr-delete-label').textContent = 'მიმოწერა ' + round;
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

            // DRAFT CARD
            function toggleDraft() {
                const body = document.getElementById('draft-body');
                const chevron = document.getElementById('draft-chevron');
                const open = body.style.display === 'block';
                body.style.display = open ? 'none' : 'block';
                chevron.classList.toggle('open', !open);
            }

            function draftCopy(id, btn) {
                const el = document.getElementById(id);
                const text = el.tagName === 'TEXTAREA' ? el.value : el.value;
                navigator.clipboard.writeText(text).then(() => {
                    btn.classList.add('copied');
                    btn.textContent = '✓';
                    setTimeout(() => {
                        btn.classList.remove('copied');
                        btn.textContent = '📋';
                    }, 1500);
                });
            }

            function removeChip(btn, e) {
                e.stopPropagation();
                btn.closest('.cc-chip').remove();
            }

            function ccInputKeydown(e) {
                if (e.key === 'Enter' || e.key === ',') {
                    e.preventDefault();
                    const val = e.target.value.trim().replace(/,$/, '');
                    if (val) addCCChip(val);
                    e.target.value = '';
                }
                if (e.key === 'Backspace' && !e.target.value) {
                    const chips = document.querySelectorAll('#cc-chips .cc-chip:not(.locked)');
                    if (chips.length) chips[chips.length - 1].remove();
                }
            }

            function addCCChip(email) {
                const chip = document.createElement('span');
                chip.className = 'cc-chip';
                chip.dataset.email = email;
                chip.innerHTML = email +
                    ' <button type="button" class="cc-chip-remove" onclick="removeChip(this,event)">✕</button>';
                const input = document.getElementById('cc-add');
                document.getElementById('cc-chips').insertBefore(chip, input);
            }

            function copyCC(btn) {
                const chips = document.querySelectorAll('#cc-chips .cc-chip');
                const emails = Array.from(chips).map(c => c.dataset.email).filter(Boolean).join('; ');
                navigator.clipboard.writeText(emails).then(() => {
                    btn.classList.add('copied');
                    btn.textContent = '✓';
                    setTimeout(() => {
                        btn.classList.remove('copied');
                        btn.textContent = '📋';
                    }, 1500);
                });
            }

            function submitDraftSent() {
                const btn = document.getElementById('draft-sent-btn');
                btn.disabled = true;
                btn.textContent = '⏳ იგზავნება...';

                const to = document.getElementById('draft-to').value.trim();

                const chips = document.querySelectorAll('#cc-chips .cc-chip');
                const ccArr = Array.from(chips).map(c => c.dataset.email).filter(Boolean);

                document.getElementById('draft-sent-to').value = to;
                document.getElementById('draft-sent-cc').value = JSON.stringify(ccArr);
                document.getElementById('draft-sent-subject').value = document.getElementById('draft-subject').value;
                document.getElementById('draft-sent-body').value = document.getElementById('draft-body-text').value;

                document.getElementById('draft-sent-form').submit();
            }

            function validatePhone(input) {
                input.style.borderColor = '';
                const hint = input.parentNode.querySelector('.js-hint');
                if (hint) hint.remove();
                return true;
            }

            function validateEmail(input) {
                const val = input.value.trim();
                let hint = input.parentNode.querySelector('.js-hint');
                if (val && !val.includes('@')) {
                    input.style.borderColor = 'var(--red)';
                    if (!hint) {
                        hint = document.createElement('span');
                        hint.className = 'js-hint';
                        hint.style.cssText = 'font-size:0.72rem;color:var(--red);margin-top:0.2rem;display:block;';
                        input.parentNode.appendChild(hint);
                    }
                    hint.textContent = 'ელ-ფოსტა არასწორია';
                    return false;
                }
                input.style.borderColor = '';
                if (hint) hint.remove();
                return true;
            }

            // re-declare submitEdit with validation
            function submitEdit() {
                const phones = document.querySelectorAll(
                    '#edit-form input[name="owner_phone"], #edit-form input[name="accountant_phone"]');
                const emails = document.querySelectorAll('#edit-form input[type="email"]');
                let ok = true;
                phones.forEach(p => {
                    if (!validatePhone(p)) ok = false;
                });
                emails.forEach(e => {
                    if (!validateEmail(e)) ok = false;
                });
                if (ok) document.getElementById('edit-form').submit();
            }

            // wire up live feedback
            document.addEventListener('DOMContentLoaded', function() {
                document.querySelectorAll(
                    '#edit-form input[name="owner_phone"], #edit-form input[name="accountant_phone"]').forEach(
                    p => {
                        p.addEventListener('input', () => validatePhone(p));
                    });
                document.querySelectorAll('#edit-form input[type="email"]').forEach(e => {
                    e.addEventListener('blur', () => validateEmail(e));
                });
            });

            // DRIVE FILES
            async function loadDriveFiles(btn) {
                btn.disabled = true;
                btn.textContent = '⏳';
                const body = document.getElementById('drive-files-body');
                try {
                    const res = await fetch('{{ route('business.move.files', $business) }}');
                    if (!res.ok) throw new Error('HTTP ' + res.status);
                    const data = await res.json();
                    if (!data.files || !data.files.length) {
                        body.innerHTML = data.has_incoming_rounds ?
                            '<p style="color:var(--text-3);font-size:0.875rem;">📁 ყველა ფაილი გადატანილია.</p>' :
                            '<p style="color:var(--text-3);font-size:0.875rem;">შემომავალი ფაილები არ მოიძებნა.</p>';
                        btn.textContent = 'განახლება';
                        btn.disabled = false;

                        return;
                    }
                    body.dataset.filesLoaded = '1';
                    const opts = data.subfolders.map(s => `<option value="${s}">${s}</option>`).join('');
                    body.innerHTML = data.files.map(f => `
                            <div style="display:flex;flex-direction:column;gap:0.375rem;padding:0.625rem 0;border-bottom:1px solid var(--border);">
                                <span style="font-size:0.875rem;font-weight:500;">
                                    ${f.webViewLink ? `<a href="${f.webViewLink}" target="_blank" style="color:var(--blue);text-decoration:none;">${f.name}</a>` : f.name}
                                    <span style="color:var(--text-3);font-size:0.78rem;">📥 შემოსული · #${f.round ?? ''}</span>
                                </span>
                                <div style="display:flex;gap:0.5rem;align-items:center;">
                                    <select data-file-id="${f.id}" style="flex:1;font-size:0.82rem;padding:0.35rem 0.5rem;border:1px solid var(--border);border-radius:6px;min-width:0;">
                                        <option value="">— საქაღალდე —</option>${opts}
                                    </select>
                                    <button type="button" class="btn btn-edit" style="font-size:0.75rem;padding:0.35rem 0.625rem;white-space:nowrap;flex-shrink:0;" onclick="moveFile('${f.id}', this)">გადატანა</button>
                                </div>
                            </div>
                        `).join('');
                } catch (e) {
                    body.innerHTML = '<p style="color:var(--red);font-size:0.875rem;">შეცდომა — სცადეთ თავიდან.</p>';
                }
                btn.textContent = 'განახლება';
                btn.disabled = false;
            }

            async function moveFile(fileId, btn) {
                const row = btn.closest('div');
                const sel = row.querySelector('select');
                if (!sel.value) {
                    sel.style.borderColor = 'var(--red)';
                    return;
                }
                sel.style.borderColor = '';
                btn.disabled = true;
                btn.textContent = '⏳';
                try {
                    const res = await fetch('{{ route('business.move.store', $business) }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        },
                        body: JSON.stringify({
                            file_id: fileId,
                            subfolder_name: sel.value,
                        }),
                    });
                    const data = await res.json();
                    if (data.ok) {
                        btn.textContent = '✓';
                        btn.style.background = 'var(--green)';
                        setTimeout(() => {
                            row.remove();
                            const body = document.getElementById('drive-files-body');
                            if (body && body.querySelectorAll('[onclick^="moveFile"]').length === 0) {
                                body.innerHTML =
                                    '<p style="color:var(--text-3);font-size:0.875rem;">📁 ყველა ფაილი გადატანილია.</p>';
                            }
                        }, 800);
                    } else {
                        btn.textContent = '❌';
                        btn.disabled = false;
                    }
                } catch (e) {
                    btn.textContent = '❌';
                    btn.disabled = false;
                }
            }
        </script>

</body>

</html>
