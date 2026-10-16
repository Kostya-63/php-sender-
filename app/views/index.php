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

        .buttons { display: flex; gap: 12px; flex-wrap: wrap; }

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

        .btn-channel {
            padding: 10px 20px;
            border: 2px solid #ddd;
            border-radius: 8px;
            background: #fff;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.2s;
        }

        .btn-channel:hover { border-color: #007bff; }
        .btn-channel.selected { border-color: #007bff; background: #e7f1ff; }
        .btn-channel:disabled { opacity: 0.3; cursor: not-allowed; }

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
            <button class="btn btn-success" id="sendBtn" onclick="sendPdf()" disabled>
                <span class="spinner"></span>
                <span>Отправить отчёт</span>
            </button>
        </div>
        <div style="margin-top: 12px; display: flex; gap: 8px;">
            <button class="btn-channel" data-channel="vk" onclick="selectChannel('vk')" disabled>💬 VK</button>
            <button class="btn-channel" data-channel="max" onclick="selectChannel('max')" disabled>📱 Max</button>
            <button class="btn-channel" data-channel="email" onclick="selectChannel('email')" disabled>📧 Email</button>
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
        let pdfBase64 = '';
        let selectedChannel = '';
        let channels = {};

        // Загрузка доступных каналов
        async function loadChannels() {
            try {
                const response = await fetch('/api/channels');
                channels = await response.json();
                
                document.querySelectorAll('.btn-channel').forEach(btn => {
                    const channel = btn.dataset.channel;
                    btn.disabled = !channels[channel]?.enabled;
                });
            } catch (error) {
                console.error('Ошибка загрузки каналов:', error);
            }
        }

        async function createPdf() {
            const btn = document.getElementById('createBtn');
            const sendBtn = document.getElementById('sendBtn');
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
                    const pdfBlob = base64ToBlob(result.data, 'application/pdf');
                    const pdfUrl = URL.createObjectURL(pdfBlob);

                    viewer.src = pdfUrl;
                    viewer.style.display = 'block';
                    placeholder.style.display = 'none';
                    pdfGenerated = true;
                    pdfBase64 = result.data;
                    sendBtn.disabled = false;

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

        function selectChannel(channel) {
            if (!channels[channel]?.enabled) return;
            
            selectedChannel = channel;
            document.querySelectorAll('.btn-channel').forEach(el => {
                el.classList.toggle('selected', el.dataset.channel === channel);
            });
        }

        async function sendPdf() {
            if (!pdfGenerated || !selectedChannel) {
                showStatus('Выберите канал отправки', 'error');
                return;
            }

            const btn = document.getElementById('sendBtn');
            btn.classList.add('loading');
            btn.disabled = true;

            try {
                const response = await fetch('/api/send', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        channel: selectedChannel,
                        pdf_content: pdfBase64,
                        filename: 'отчёт.pdf'
                    })
                });

                const result = await response.json();

                if (result.success) {
                    showStatus(result.message, 'success');
                } else {
                    showStatus('Ошибка: ' + (result.error || 'Не удалось отправить'), 'error');
                }
            } catch (error) {
                showStatus('Ошибка соединения: ' + error.message, 'error');
            } finally {
                btn.classList.remove('loading');
                btn.disabled = false;
            }
        }

        function base64ToBlob(base64, mimeType) {
            const bytes = atob(base64);
            const ab = new ArrayBuffer(bytes.length);
            const ua = new Uint8Array(ab);
            for (let i = 0; i < bytes.length; i++) {
                ua[i] = bytes.charCodeAt(i);
            }
            return new Blob([ab], { type: mimeType });
        }

        function showStatus(message, type) {
            const status = document.getElementById('status');
            status.textContent = message;
            status.className = 'status ' + type;
        }

        // Загружаем каналы при старте
        loadChannels();
    </script>
</body>
</html>
