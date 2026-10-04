<!DOCTYPE html>
<html lang="ka">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vigil · ბიზნესები</title>
    <link rel="icon"
        href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>💼</text></svg>">
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: system-ui, sans-serif;
            background: #f1f5f9;
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

        h1 {
            font-size: 1.25rem;
            font-weight: 600;
            margin-bottom: 1.5rem;
            color: #1e293b;
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
            border-color: #6366f1;
        }

        button {
            width: 100%;
            padding: 0.75rem;
            background: #6366f1;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            cursor: pointer;
        }

        button:hover {
            background: #4f46e5;
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
        @if ($errors->any())
            <div class="error">{{ $errors->first() }}</div>
        @endif
        <form method="POST" action="{{ route('business.login') }}">
            @csrf
            <label>ელ-ფოსტა</label>
            <input type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="email">
            <label>პაროლი</label>
            <input type="password" name="password" required>
            <button type="submit">შესვლა</button>
        </form>
    </div>
</body>

</html>
