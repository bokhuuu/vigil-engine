<!DOCTYPE html>
<html lang="ka">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>ახალი ბიზნესი · Vigil</title>
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
            --red: #dc2626;
            --red-bg: #fef2f2;
            --blue: #2563eb;
            --blue-bg: #eff6ff;
        }

        body {
            font-family: 'Inter', system-ui, sans-serif;
            background: var(--bg);
            color: var(--text);
            min-height: 100vh;
            -webkit-font-smoothing: antialiased;
        }

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

        .page {
            max-width: 800px;
            margin: 0 auto;
            padding: 1.75rem 1.5rem 4rem;
        }

        .page-title {
            font-size: 1.375rem;
            font-weight: 700;
            letter-spacing: -0.02em;
            margin-bottom: 1.75rem;
        }

        .card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 12px;
            overflow: hidden;
            margin-bottom: 1.25rem;
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

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .form-row.full {
            grid-template-columns: 1fr;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 0.375rem;
        }

        label {
            font-size: 0.78rem;
            font-weight: 500;
            color: var(--text-2);
        }

        .required {
            color: var(--red);
            margin-left: 0.2rem;
        }

        input,
        select,
        textarea {
            font-family: inherit;
            font-size: 0.875rem;
            padding: 0.55rem 0.75rem;
            border: 1px solid var(--border);
            border-radius: 8px;
            color: var(--text);
            background: var(--surface);
            outline: none;
            transition: border-color 0.15s;
            width: 100%;
        }

        input:focus,
        select:focus,
        textarea:focus {
            border-color: var(--blue);
        }

        .error-msg {
            font-size: 0.75rem;
            color: var(--red);
            margin-top: 0.2rem;
        }

        .contact-section {
            margin-bottom: 1rem;
        }

        .contact-label {
            font-size: 0.72rem;
            font-weight: 600;
            letter-spacing: 0.04em;
            margin-bottom: 0.75rem;
            padding-left: 0.75rem;
            border-left: 3px solid var(--blue);
        }

        .contact-label.accountant {
            border-color: var(--green);
        }

        .form-actions {
            display: flex;
            gap: 0.75rem;
            justify-content: flex-end;
            margin-top: 1.5rem;
        }

        .btn {
            font-family: inherit;
            font-size: 0.875rem;
            font-weight: 500;
            padding: 0.625rem 1.5rem;
            border-radius: 8px;
            cursor: pointer;
            border: none;
            transition: all 0.15s;
        }

        .btn-primary {
            background: var(--navy);
            color: #fff;
        }

        .btn-primary:hover {
            background: var(--navy-2);
        }

        .btn-secondary {
            background: var(--surface);
            color: var(--text-2);
            border: 1px solid var(--border);
        }

        .btn-secondary:hover {
            border-color: var(--text-2);
        }

        @media (max-width: 600px) {
            .form-row {
                grid-template-columns: 1fr;
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
            <span class="header-title">ახალი ბიზნესი</span>
        </div>
        <div class="header-right">
            <form method="POST" action="{{ route('business.logout') }}">
                @csrf
                <button type="submit" class="btn-logout">გამოსვლა</button>
            </form>
        </div>
    </header>

    <div class="page">

        <h1 class="page-title">ახალი ბიზნესის დამატება</h1>

        <form method="POST" action="{{ route('business.create') }}">
            @csrf

            {{-- BASIC INFO --}}
            <div class="card">
                <div class="card-header">ძირითადი ინფო</div>
                <div class="card-body">

                    <div class="form-row full">
                        <div class="form-group">
                            <label>სახელი <span class="required">*</span></label>
                            <input type="text" name="name" value="{{ old('name') }}"
                                placeholder="შპს კომპანიის სახელი">
                            @error('name')
                                <span class="error-msg">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>საიდენტიფიკაციო კოდი <span class="required">*</span></label>
                            <input type="text" name="registration_code" value="{{ old('registration_code') }}"
                                placeholder="123456789">
                            @error('registration_code')
                                <span class="error-msg">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="form-group">
                            <label>ACT ნომერი</label>
                            <input type="text" name="act_number" value="{{ old('act_number') }}"
                                placeholder="მაგ: 7-1">
                            @error('act_number')
                                <span class="error-msg">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>ლოტი <span class="required">*</span></label>
                            <input type="number" name="lot_number" value="{{ old('lot_number', 2) }}" min="1">
                            @error('lot_number')
                                <span class="error-msg">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="form-group">
                            <label>ტიპი <span class="required">*</span></label>
                            <select name="type">
                                <option value="მეპატრონე" {{ old('type') === 'მეპატრონე' ? 'selected' : '' }}>მეპატრონე
                                </option>
                                <option value="მოიჯარე" {{ old('type') === 'მოიჯარე' ? 'selected' : '' }}>მოიჯარე
                                </option>
                                <option value="მეიჯარე" {{ old('type') === 'მეიჯარე' ? 'selected' : '' }}>მეიჯარე
                                </option>
                                <option value="სხვა" {{ old('type') === 'სხვა' ? 'selected' : '' }}>სხვა</option>
                            </select>
                            @error('type')
                                <span class="error-msg">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <div class="form-row full">
                        <div class="form-group">
                            <label>სტატუსი <span class="required">*</span></label>
                            <select name="status">
                                <option value="pending" {{ old('status', 'pending') === 'pending' ? 'selected' : '' }}>
                                    გასაგზავნი</option>
                                <option value="active" {{ old('status') === 'active' ? 'selected' : '' }}>მიმდინარე
                                </option>
                                <option value="complete" {{ old('status') === 'complete' ? 'selected' : '' }}>
                                    დასრულებული</option>
                            </select>
                            @error('status')
                                <span class="error-msg">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                </div>
            </div>

            {{-- CONTACTS --}}
            <div class="card">
                <div class="card-header">კონტაქტები</div>
                <div class="card-body">

                    <div class="contact-section">
                        <div class="contact-label">👤 მეპატრონე</div>
                        <div class="form-row">
                            <div class="form-group">
                                <label>სახელი გვარი</label>
                                <input type="text" name="owner_name" value="{{ old('owner_name') }}"
                                    placeholder="სახელი გვარი">
                            </div>
                            <div class="form-group">
                                <label>ტელეფონი</label>
                                <input type="text" name="owner_phone" value="{{ old('owner_phone') }}"
                                    placeholder="5XX XXX XXX">
                            </div>
                        </div>
                        <div class="form-row full">
                            <div class="form-group">
                                <label>ელ-ფოსტა</label>
                                <input type="email" name="owner_email" value="{{ old('owner_email') }}">
                            </div>
                        </div>
                    </div>

                    <div class="contact-section">
                        <div class="contact-label accountant">📊 ბუღალტერი</div>
                        <div class="form-row">
                            <div class="form-group">
                                <label>სახელი გვარი</label>
                                <input type="text" name="accountant_name" value="{{ old('accountant_name') }}"
                                    placeholder="სახელი გვარი">
                            </div>
                            <div class="form-group">
                                <label>ტელეფონი</label>
                                <input type="text" name="accountant_phone" value="{{ old('accountant_phone') }}"
                                    placeholder="5XX XXX XXX">
                            </div>
                        </div>
                        <div class="form-row full">
                            <div class="form-group">
                                <label>ელ-ფოსტა</label>
                                <input type="email" name="accountant_email" value="{{ old('accountant_email') }}">
                            </div>
                        </div>
                    </div>

                    <div class="form-row full">
                        <div class="form-group">
                            <label>შენიშვნები</label>
                            <textarea name="notes" rows="3" style="resize:vertical;">{{ old('notes') }}</textarea>
                        </div>
                    </div>

                </div>
            </div>

            <div class="form-actions">
                <a href="{{ route('business.dashboard') }}" class="btn btn-secondary">გაუქმება</a>
                <button type="submit" class="btn btn-primary">შენახვა</button>
            </div>

        </form>

    </div>

</body>

</html>
