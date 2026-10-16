<?php

namespace App\Services;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

/**
 * Отправка PDF через Email (SMTP)
 */
class EmailService extends MessengerService
{
    public function send(string $pdfContent, string $filename, mixed $recipient): MessengerResult
    {
        try {
            $mail = new PHPMailer(true);
            
            // SMTP настройки
            $mail->isSMTP();
            $mail->Host = $this->config['smtp_host'];
            $mail->Port = $this->config['smtp_port'];
            $mail->SMTPAuth = true;
            $mail->Username = $this->config['smtp_user'];
            $mail->Password = $this->config['smtp_pass'];
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            
            // Отправитель
            $mail->setFrom(
                $this->config['from_email'],
                $this->config['from_name']
            );
            
            // Получатель
            $toEmail = (string) $recipient;
            if (!$toEmail) {
                return MessengerResult::error('Не указан email получателя');
            }
            $mail->addAddress($toEmail);
            
            // Содержимое
            $mail->isHTML(true);
            $mail->Subject = "Отчёт: {$filename}";
            $mail->Body = "
                <h2>Отчёт прикреплен</h2>
                <p>Добрый день!</p>
                <p>К этому письму прикреплён отчёт: <strong>{$filename}</strong></p>
                <p>С уважением,<br>Студенческий портал</p>
            ";
            
            // Attachment
            $mail->addStringAttachment($pdfContent, $filename, 'base64', 'application/pdf');
            
            // Отправка
            $mail->send();
            
            return MessengerResult::success("PDF отправлен на {$toEmail}");
            
        } catch (Exception $e) {
            return MessengerResult::error("SMTP ошибка: " . $e->getMessage());
        } catch (\Exception $e) {
            return MessengerResult::error($e->getMessage());
        }
    }

    public function isEnabled(): bool
    {
        return (!empty($this->config['enabled']) && $this->config['enabled']) &&
               !empty($this->config['smtp_host']) && 
               !empty($this->config['smtp_user']) && 
               !empty($this->config['smtp_pass']);
    }
}
