<!DOCTYPE html>
<html lang="ka">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vigil · შესვლა</title>
    <link rel="icon"
        href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='%232563eb'><path d='M12 2L3 7v5c0 5.25 3.75 10.15 9 11.35C17.25 22.15 21 17.25 21 12V7L12 2z'/></svg>">
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: system-ui, sans-serif;
            background: #f0f2f5;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
        }

        @media (max-width: 480px) {
            .card {
                margin: 1rem;
                border-radius: 12px;
            }
        }

        .card {
            background: white;
            border-radius: 12px;
            padding: 2rem;
            width: 100%;
            max-width: 400px;
            box-shadow: 0 4px 24px rgba(0, 0, 0, 0.08);
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 1.5rem;
        }

        .logo-title {
            font-size: 1.1rem;
            font-weight: 600;
            color: #1a1f36;
        }

        .logo-badge {
            font-size: 10px;
            color: #8892a4;
            background: #f0f2f5;
            padding: 2px 8px;
            border-radius: 10px;
        }

        label {
            display: block;
            font-size: 0.875rem;
            color: #475569;
            margin-bottom: 0.25rem;
        }

        input {
            width: 100%;
            padding: 0.625rem 0.875rem;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            font-size: 0.95rem;
            margin-bottom: 1rem;
            outline: none;
        }

        input:focus {
            border-color: #2563eb;
        }

        button {
            width: 100%;
            padding: 0.75rem;
            background: #1a1f36;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            cursor: pointer;
        }

        button:hover {
            background: #2d3448;
        }

        .error {
            color: #ef4444;
            font-size: 0.875rem;
            margin-bottom: 1rem;
        }
    </style>
</head>

<body>
    <div class="card">
        <div class="logo">
            <span style="font-size:1.4rem;">🛡️</span>
            <span class="logo-title">Vigil</span>
            <span class="logo-badge">Dashboard</span>
        </div>

        @if ($errors->any())
            <div class="error">{{ $errors->first() }}</div>
        @endif

        <form method="POST" action="{{ route('acts.login') }}">
            @csrf
            <label>მომხმარებელი</label>
            <input type="text" name="username" value="{{ old('username') }}" required autofocus
                autocomplete="username">
            <label>პაროლი</label>
            <input type="password" name="password" required autocomplete="current-password">
            <button type="submit">შესვლა</button>
        </form>
    </div>
</body>

</html>
