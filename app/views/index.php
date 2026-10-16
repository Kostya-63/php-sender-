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

        /* Modal */
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }

        .modal-overlay.active { display: flex; }

        .modal {
            background: #fff;
            border-radius: 12px;
            padding: 30px;
            width: 90%;
            max-width: 500px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }

        .modal h2 {
            margin-bottom: 20px;
            color: #1a1a2e;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #333;
        }

        .form-group select,
        .form-group input {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
        }

        .channel-options {
            display: flex;
            gap: 12px;
        }

        .channel-option {
            flex: 1;
            padding: 12px;
            border: 2px solid #ddd;
            border-radius: 8px;
            text-align: center;
            cursor: pointer;
            transition: all 0.2s;
        }

        .channel-option:hover { border-color: #007bff; }
        .channel-option.selected { border-color: #007bff; background: #e7f1ff; }

        .channel-option .icon { font-size: 24px; margin-bottom: 4px; }
        .channel-option .name { font-size: 13px; font-weight: 500; }

        .modal-actions {
            display: flex;
            gap: 12px;
            justify-content: flex-end;
            margin-top: 24px;
        }

        .btn-cancel {
            background: #6c757d;
            color: white;
        }

        .btn-cancel:hover { background: #5a6268; }
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
            <button class="btn btn-success" id="sendBtn" onclick="openSendModal()" disabled>
                <span class="spinner"></span>
                <span>Отправить отчёт</span>
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

    <!-- Modal -->
    <div class="modal-overlay" id="sendModal">
        <div class="modal">
            <h2>📤 Отправить отчёт</h2>

            <div class="form-group">
                <label id="recipientLabel">Адресат</label>
                <input type="text" id="recipientInput" value="kostikpetrov1986@yandex.ru" placeholder="Введите email или ID">
            </div>

            <div class="form-group">
                <label>Канал</label>
                <div class="channel-options">
                    <div class="channel-option" data-channel="vk" onclick="selectChannel('vk')">
                        <div class="icon">💬</div>
                        <div class="name">VK</div>
                    </div>
                    <div class="channel-option" data-channel="max" onclick="selectChannel('max')">
                        <div class="icon">📱</div>
                        <div class="name">Max</div>
                    </div>
                    <div class="channel-option" data-channel="email" onclick="selectChannel('email')">
                        <div class="icon">📧</div>
                        <div class="name">Email</div>
                    </div>
                </div>
            </div>

            <div class="modal-actions">
                <button class="btn btn-cancel" onclick="closeSendModal()">Отмена</button>
                <button class="btn btn-success" id="sendModalBtn" onclick="sendPdf()">
                    <span class="spinner"></span>
                    <span>Отправить</span>
                </button>
            </div>
        </div>
    </div>

    <script>
        let pdfGenerated = false;
        let pdfBase64 = '';
        let selectedChannel = '';

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

        async function openSendModal() {
            if (!pdfGenerated) return;

            const modal = document.getElementById('sendModal');
            const input = document.getElementById('recipientInput');

            modal.classList.add('active');
            input.value = 'kostikpetrov1986@yandex.ru';
            input.placeholder = 'example@mail.ru';

            // Email выбран по умолчанию
            selectedChannel = 'email';
            document.querySelectorAll('.channel-option').forEach(el => el.classList.remove('selected'));
            document.querySelector('[data-channel="email"]').classList.add('selected');
        }

        function closeSendModal() {
            document.getElementById('sendModal').classList.remove('active');
        }

        function selectChannel(channel) {
            selectedChannel = channel;
            document.querySelectorAll('.channel-option').forEach(el => el.classList.remove('selected'));
            document.querySelector(`[data-channel="${channel}"]`).classList.add('selected');
            
            const input = document.getElementById('recipientInput');
            const label = document.getElementById('recipientLabel');
            switch(channel) {
                case 'email':
                    label.textContent = 'Email';
                    input.placeholder = 'example@mail.ru';
                    break;
                case 'vk':
                    label.textContent = 'VK chat_id';
                    input.placeholder = '123456789';
                    break;
                case 'max':
                    label.textContent = 'Max user_id';
                    input.placeholder = '123456';
                    break;
            }
        }

        async function sendPdf() {
            const recipient = document.getElementById('recipientInput').value.trim();

            if (!recipient || !selectedChannel) {
                showStatus('Введите адресат и выберите канал', 'error');
                return;
            }

            const btn = document.getElementById('sendModalBtn');

            btn.classList.add('loading');
            btn.disabled = true;

            try {
                const response = await fetch('/api/send', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        channel: selectedChannel,
                        recipient: recipient,
                        pdf_content: pdfBase64,
                        filename: 'отчёт.pdf'
                    })
                });

                const result = await response.json();

                if (result.success) {
                    showStatus(result.message, 'success');
                    closeSendModal();
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
    </script>
</body>
</html>
