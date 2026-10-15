<?php

namespace App\Services;

use Mpdf\Mpdf;

class PdfService
{
    /**
     * Генерирует PDF-документ с тестовым контентом
     *
     * @return string PDF в виде бинарной строки
     */
    public function generate(): string
    {
        $date = date('d.m.Y H:i');

        $html = '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <style>
                body {
                    font-family: Arial, sans-serif;
                    padding: 40px;
                    line-height: 1.6;
                }
                h1 {
                    color: #333;
                    border-bottom: 2px solid #007bff;
                    padding-bottom: 10px;
                }
                .meta {
                    color: #666;
                    font-size: 14px;
                    margin-bottom: 30px;
                }
                .content {
                    background: #f8f9fa;
                    padding: 20px;
                    border-left: 4px solid #007bff;
                    margin: 20px 0;
                }
                .footer {
                    margin-top: 40px;
                    font-size: 12px;
                    color: #999;
                    text-align: center;
                }
            </style>
        </head>
        <body>
            <h1>Тестовый документ</h1>
            <div class="meta">Дата создания: ' . htmlspecialchars($date) . '</div>
            
            <div class="content">
                <p>Это тестовый PDF-документ, сгенерированный автоматически.</p>
                <p>Документ содержит:</p>
                <ul>
                    <li>Заголовок</li>
                    <li>Дату создания</li>
                    <li>Текстовое содержимое</li>
                </ul>
            </div>
            
            <p>Приложение «PHP Sender» предназначено для создания PDF-документов и их отправки в мессенджер.</p>
            
            <div class="footer">
                Сгенерировано приложением PHP Sender
            </div>
        </body>
        </html>
        ';

        $mpdf = new Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'default_font' => 'Arial',
        ]);

        $mpdf->WriteHTML($html);

        return $mpdf->Output('', 'S');
    }
}
