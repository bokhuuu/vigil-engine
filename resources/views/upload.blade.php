<!DOCTYPE html>
<html lang="ka">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vigil · ატვირთვა</title>
    <link rel="icon"
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

        .header {
            background: #1a1f36;
            padding: 12px 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 100;
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

        .header-link {
            font-size: 11px;
            color: #7c9df7;
            text-decoration: none;
        }

        .header-link:hover {
            color: #adc4ff;
        }

        .wrap {
            max-width: 560px;
            margin: 40px auto;
            padding: 0 16px 80px;
        }

        .card {
            background: #fff;
            border: 1px solid #e4e8f0;
            border-radius: 10px;
            padding: 28px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.06);
        }

        .card-title {
            font-size: 15px;
            font-weight: 600;
            margin-bottom: 20px;
            color: #1a1f36;
        }

        label {
            display: block;
            font-size: 11px;
            font-weight: 600;
            color: #8892a4;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            margin-bottom: 5px;
        }

        select,
        input[type=file] {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid #e4e8f0;
            border-radius: 6px;
            font-size: 13px;
            font-family: 'IBM Plex Sans', sans-serif;
            color: #1a1f36;
            background: #fff;
            outline: none;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.04);
        }

        select:focus,
        input[type=file]:focus {
            border-color: #2563eb;
        }

        .field {
            margin-bottom: 16px;
        }

        #folder-select {
            display: none;
        }

        .btn {
            width: 100%;
            padding: 10px;
            border-radius: 6px;
            border: none;
            background: #2563eb;
            color: #fff;
            font-size: 13px;
            font-weight: 600;
            font-family: 'IBM Plex Sans', sans-serif;
            cursor: pointer;
            margin-top: 8px;
        }

        .btn:hover:not(:disabled) {
            background: #1d4ed8;
        }

        .btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        .alert {
            padding: 12px 16px;
            border-radius: 6px;
            font-size: 12px;
            margin-top: 16px;
            display: none;
        }

        .alert.ok {
            background: #d1fae5;
            color: #065f46;
        }

        .alert.err {
            background: #fee2e2;
            color: #991b1b;
        }

        .progress-wrap {
            margin-top: 12px;
            display: none;
        }

        .progress-bg {
            height: 4px;
            background: #e4e8f0;
            border-radius: 2px;
            overflow: hidden;
        }

        .progress-fill {
            height: 100%;
            background: #2563eb;
            border-radius: 2px;
            width: 0;
            transition: width 0.3s;
        }

        .progress-label {
            font-size: 11px;
            color: #8892a4;
            margin-top: 4px;
        }

        @media (max-width: 480px) {
            .wrap {
                margin: 20px auto;
            }

            .card {
                padding: 20px;
            }
        }
    </style>
</head>

<body>
    <div class="header">
        <div class="header-left">
            <span class="header-title">Vigil 🛡️</span>
            <span class="header-badge">ატვირთვა</span>
        </div>
        @if ($role === 'manager')
            <a href="/dashboard" class="header-link">← Dashboard</a>
        @endif
    </div>

    <div class="wrap">
        <div class="card">
            <div class="card-title">📤 ფაილების ატვირთვა</div>

            <div class="field">
                <label>ACT ნომერი</label>
                <select id="act-select">
                    <option value="">— აირჩიეთ ACT —</option>
                    @foreach ($acts as $act)
                        <option value="{{ $act['id'] }}">{{ $act['number'] }}</option>
                    @endforeach
                </select>
            </div>

            <div class="field" id="folder-select">
                <label>საქაღალდე</label>
                <select id="folder-select-input">
                    <option value="">— იტვირთება... —</option>
                </select>
            </div>

            <div class="field" id="file-field" style="display:none;">
                <label>ფაილები</label>
                <input type="file" id="file-input" multiple>
            </div>

            <button class="btn" id="upload-btn" disabled>ატვირთვა</button>

            <div class="progress-wrap" id="progress-wrap">
                <div class="progress-bg">
                    <div class="progress-fill" id="progress-fill"></div>
                </div>
                <div class="progress-label" id="progress-label"></div>
            </div>

            <div class="alert" id="alert"></div>
        </div>
    </div>

    <script>
        const actSelect = document.getElementById('act-select');
        const folderWrap = document.getElementById('folder-select');
        const folderInput = document.getElementById('folder-select-input');
        const fileField = document.getElementById('file-field');
        const fileInput = document.getElementById('file-input');
        const uploadBtn = document.getElementById('upload-btn');
        const alert = document.getElementById('alert');
        const progressWrap = document.getElementById('progress-wrap');
        const progressFill = document.getElementById('progress-fill');
        const progressLabel = document.getElementById('progress-label');

        actSelect.addEventListener('change', async function() {
            const actId = this.value;
            folderWrap.style.display = 'none';
            fileField.style.display = 'none';
            uploadBtn.disabled = true;
            if (!actId) return;

            folderInput.innerHTML = '<option>— იტვირთება... —</option>';
            folderWrap.style.display = 'block';

            const res = await fetch('/upload/folders?act=' + encodeURIComponent(actId));
            const data = await res.json();

            folderInput.innerHTML = '<option value="">— აირჩიეთ საქაღალდე —</option>' +
                data.folders.map(f => `<option value="${f}">${f}</option>`).join('');

            folderInput.dispatchEvent(new Event('change'));
        });

        folderInput.addEventListener('change', function() {
            const ready = this.value !== '';
            fileField.style.display = ready ? 'block' : 'none';
            checkReady();
        });

        fileInput.addEventListener('change', checkReady);

        function checkReady() {
            uploadBtn.disabled = !(actSelect.value && folderInput.value && fileInput.files.length > 0);
        }

        function showAlert(msg, type) {
            alert.textContent = msg;
            alert.className = 'alert ' + type;
            alert.style.display = 'block';
        }

        uploadBtn.addEventListener('click', async function() {
            uploadBtn.disabled = true;
            alert.style.display = 'none';

            const files = Array.from(fileInput.files);
            progressWrap.style.display = 'block';
            progressFill.style.width = '0%';
            progressLabel.textContent = '0 / ' + files.length + ' ფაილი';

            let done = 0;
            const failed = [];

            for (const file of files) {
                const fd = new FormData();
                fd.append('act_id', actSelect.value);
                fd.append('folder', folderInput.value);
                fd.append('files[]', file);
                fd.append('_token', '{{ csrf_token() }}');

                try {
                    const res = await fetch('/upload', {
                        method: 'POST',
                        body: fd
                    });
                    if (!res.ok) failed.push(file.name);
                } catch {
                    failed.push(file.name);
                }

                done++;
                const pct = Math.round(done / files.length * 100);
                progressFill.style.width = pct + '%';
                progressLabel.textContent = done + ' / ' + files.length + ' ფაილი';
            }

            if (failed.length === 0) {
                showAlert('✅ ' + files.length + ' ფაილი წარმატებით აიტვირთა!', 'ok');
                fileInput.value = '';
            } else {
                showAlert('⚠️ ' + failed.length + ' ფაილი ვერ აიტვირთა: ' + failed.join(', '), 'err');
            }

            uploadBtn.disabled = false;
        });
    </script>
</body>

</html>
