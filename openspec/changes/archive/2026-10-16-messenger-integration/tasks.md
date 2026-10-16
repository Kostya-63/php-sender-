## 1. Инициализация

- [x] 1.1 Создать config/messengers.php с настройками VK, Max, SMTP
- [x] 1.2 Добавить phpmailer/phpmailer в composer.json
- [x] 1.3 Запустить composer install

## 2. Базовый интерфейс

- [x] 2.1 Создать абстрактный класс MessengerService с методом send()
- [x] 2.2 Создать Result DTO (success, message, error)
- [x] 2.3 Обновить PdfController для вызова MessengerService

## 3. VK-отправка

- [x] 3.1 Создать VkService с методом getUploadServer()
- [x] 3.2 Реализовать загрузку PDF на VK uploads-сервер
- [x] 3.3 Реализовать отправку document attachment через POST /messages/send
- [x] 3.4 Обработать ошибку "chat_id required"

## 4. Max-отправка

- [x] 4.1 Создать MaxService с методом uploadFile()
- [x] 4.2 Реализовать POST /uploads?type=file
- [x] 4.3 Реализовать отправку POST /messages с file attachment
- [x] 4.4 Реализовать retry-логику (пауза 2 сек, до 3 попыток)

## 5. Email-отправка

- [x] 5.1 Создать EmailService с методом send()
- [x] 5.2 Настроить PHPMailer с SMTP
- [x] 5.3 Реализовать HTML-шаблон письма с PDF-attachment
- [x] 5.4 Добавить обработку SMTP-ошибок

## 6. Выбор получателя

- [x] 6.1 Создать таблицу recipients в БД (или mock-данные)
- [x] 6.2 Создать API endpoint GET /api/recipients
- [x] 6.3 Добавить модальное окно выбора получателя на фронтенд
- [x] 6.4 Добавить выбор канала (VK / Max / Email)

## 7. Интеграция

- [x] 7.1 Обновить фронтенд: кнопка "Отправить отчёт" → модальное окно
- [x] 7.2 Реализовать AJAX-запрос POST /api/send с параметрами
- [x] 7.3 Показать статус отправки (успех/ошибка)

## 8. Тестирование

- [ ] 8.1 Протестировать отправку PDF в VK
- [ ] 8.2 Протестировать отправку PDF в Max
- [ ] 8.3 Протестировать отправку PDF через Email
- [ ] 8.4 Протестировать обработку ошибок
