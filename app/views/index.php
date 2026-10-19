<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PHP Sender — PDF Creator</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f5f7fa;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .header {
            background: #fff;
            padding: 20px 30px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.06);
        }

        .header h1 {
            font-size: 24px;
            color: #1a1a2e;
            margin-bottom: 16px;
        }

        .buttons { display: flex; gap: 12px; }

        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            font-size: 15px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .btn:disabled { opacity: 0.4; cursor: not-allowed; }

        .btn-primary { background: #007bff; color: white; }
        .btn-primary:hover:not(:disabled) { background: #0056b3; }

        .btn-success { background: #28a745; color: white; }
        .btn-success:hover:not(:disabled) { background: #1e7e34; }

        .spinner {
            display: none;
            width: 16px;
            height: 16px;
            border: 2px solid rgba(255,255,255,0.3);
            border-top-color: white;
            border-radius: 50%;
            animation: spin 0.6s linear infinite;
        }

        .btn.loading .spinner { display: block; }

        @keyframes spin { to { transform: rotate(360deg); } }

        .main {
            flex: 1;
            padding: 20px 30px;
            display: flex;
            justify-content: center;
        }

        .pdf-container {
            width: 100%;
            max-width: 900px;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
            overflow: hidden;
            min-height: 500px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .pdf-placeholder {
            color: #999;
            font-size: 16px;
            text-align: center;
            padding: 40px;
        }

        .pdf-placeholder svg {
            width: 64px;
            height: 64px;
            margin-bottom: 16px;
            opacity: 0.3;
        }

        .pdf-viewer {
            width: 100%;
            height: 700px;
            border: none;
            display: block;
        }

        .status {
            padding: 12px 20px;
            margin-top: 12px;
            border-radius: 8px;
            font-size: 14px;
            display: none;
        }

        .status.success {
            display: block;
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .status.error {
            display: block;
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .footer {
            padding: 16px 30px;
            text-align: center;
            color: #999;
            font-size: 13px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>📄 PHP Sender</h1>
        <div class="buttons">
            <button class="btn btn-primary" id="createBtn" onclick="createPdf()">
                <span class="spinner"></span>
                <span>Создать документ</span>
            </button>
            <button class="btn btn-success" id="copyBtn" onclick="copyToClipboard()" disabled>
                <span class="spinner"></span>
                <span>Скачать PDF</span>
            </button>
        </div>
    </div>

    <div class="main">
        <div style="width: 100%; max-width: 900px;">
            <div class="pdf-container" id="pdfContainer">
                <div class="pdf-placeholder" id="placeholder">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    <p>PDF-документ появится здесь</p>
                    <p style="font-size: 13px; margin-top: 8px;">Нажмите «Создать документ» для генерации</p>
                </div>
                <iframe class="pdf-viewer" id="pdfViewer" style="display: none;"></iframe>
            </div>
            <div class="status" id="status"></div>
        </div>
    </div>

    <div class="footer">PHP Sender &copy; 2026</div>

    <script>
        let pdfGenerated = false;
        let pdfBlob = null;

        async function createPdf() {
            const btn = document.getElementById('createBtn');
            const copyBtn = document.getElementById('copyBtn');
            const placeholder = document.getElementById('placeholder');
            const viewer = document.getElementById('pdfViewer');
            const status = document.getElementById('status');

            btn.classList.add('loading');
            btn.disabled = true;
            status.className = 'status';
            status.style.display = 'none';

            try {
                const response = await fetch('/api/create-pdf', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' }
                });

                const result = await response.json();

                if (result.success && result.data) {
                    const bytes = atob(result.data);
                    const ab = new ArrayBuffer(bytes.length);
                    const ua = new Uint8Array(ab);
                    for (let i = 0; i < bytes.length; i++) {
                        ua[i] = bytes.charCodeAt(i);
                    }
                    pdfBlob = new Blob([ab], { type: 'application/pdf' });
                    const pdfUrl = URL.createObjectURL(pdfBlob);

                    viewer.src = pdfUrl;
                    viewer.style.display = 'block';
                    placeholder.style.display = 'none';
                    pdfGenerated = true;
                    copyBtn.disabled = false;

                    showStatus('Документ успешно создан!', 'success');
                } else {
                    showStatus('Ошибка: ' + (result.error || 'Не удалось создать документ'), 'error');
                }
            } catch (error) {
                showStatus('Ошибка соединения: ' + error.message, 'error');
            } finally {
                btn.classList.remove('loading');
                btn.disabled = false;
            }
        }

        async function copyToClipboard() {
            if (!pdfBlob || !pdfGenerated) {
                showStatus('Сначала создайте документ', 'error');
                return;
            }

            const btn = document.getElementById('copyBtn');

            btn.classList.add('loading');
            btn.disabled = true;

            try {
                downloadPdf();
                showStatus('Файл скачан. Вставьте из папки загрузок (Ctrl+V в проводник)', 'success');
            } catch (error) {
                showStatus('Ошибка: ' + error.message, 'error');
            } finally {
                btn.classList.remove('loading');
                btn.disabled = false;
            }
        }

        function downloadPdf() {
            const url = URL.createObjectURL(pdfBlob);
            const a = document.createElement('a');
            a.href = url;
            a.download = 'отчёт.pdf';
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            URL.revokeObjectURL(url);
        }

        function showStatus(message, type) {
            const status = document.getElementById('status');
            status.textContent = message;
            status.className = 'status ' + type;
        }
    </script>
</body>
</html>
