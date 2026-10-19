## 1. Удаление модального окна — CSS

- [x] 1.1 Удалить CSS-стили модального окна (строки 137-218: `.modal-overlay`, `.modal`, `.form-group`, `.channel-options`, `.channel-option`, `.modal-actions`, `.btn-cancel`)

## 2. Удаление модального окна — HTML

- [x] 2.1 Удалить HTML-блок модального окна (строки 253-289: `<div class="modal-overlay" id="sendModal">...</div>`)

## 3. Замена кнопки «Отправить отчёт»

- [x] 3.1 Переименовать кнопку с «Отправить отчёт» на «Копировать в буфер»
- [x] 3.2 Заменить `onclick="openSendModal()"` на `onclick="copyToClipboard()"`
- [x] 3.3 Заменить иконку/emoji кнопки (при необходимости)

## 4. Реализация функции копирования в буфер

- [x] 4.1 Заменить переменную `pdfBase64` на `pdfBlob` (хранить `Blob` вместо base64-строки)
- [x] 4.2 В функции `createPdf()` создавать `Blob` из base64 и сохранять в `pdfBlob`
- [x] 4.3 Удалить функции `openSendModal`, `closeSendModal`, `selectChannel`, `sendPdf`
- [x] 4.4 Создать функцию `copyToClipboard()` с использованием `navigator.clipboard.write([new ClipboardItem({ 'application/pdf': pdfBlob })])`
- [x] 4.5 Удалить переменную `selectedChannel`

## 5. Обратная связь

- [x] 5.1 В `copyToClipboard()` показать зелёный статус «Документ скопирован в буфер обмена» при успехе
- [x] 5.2 В `copyToClipboard()` показать красный статус с сообщением об ошибке при неудаче
- [x] 5.3 Добавить обработку `NotAllowedError` (пользователь отклонил запрос буфера)

## 6. Очистка

- [x] 6.1 Удалить неиспользуемую функцию `base64ToBlob` (переименована/интегрирована в `createPdf`)
- [x] 6.2 Удалить переменные `selectedChannel` и связанные обработчики
- [x] 6.3 Проверить, что файл валиден и все функции корректно работают
