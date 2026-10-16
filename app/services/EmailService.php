<?php

namespace App\Services;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

/**
 * Отправка PDF через Email (SMTP)
 */
class EmailService extends MessengerService
{
    public function send(string $pdfContent, string $filename, array $config): MessengerResult
    {
        try {
            $mail = new PHPMailer(true);
            
            // SMTP настройки
            $mail->isSMTP();
            $mail->Host = $config['smtp_host'];
            $mail->Port = $config['smtp_port'];
            $mail->SMTPAuth = true;
            $mail->Username = $config['smtp_user'];
            $mail->Password = $config['smtp_pass'];
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            
            // Отправитель и получатель
            $mail->setFrom(
                $config['from_email'],
                $config['from_name']
            );
            
            // Email получателя берётся из smtp_user (отправляем самому себе)
            $toEmail = $config['smtp_user'];
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
            
            // Attachment (бинарные данные)
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
        return !empty($this->config['smtp_host']) && 
               !empty($this->config['smtp_user']) && 
               !empty($this->config['smtp_pass']);
    }
}
