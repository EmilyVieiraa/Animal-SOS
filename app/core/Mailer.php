<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once ROOT_PATH . 'vendor/autoload.php';

class Mailer
{
    public static function send(string $toEmail, string $toName, string $subject, string $htmlBody): bool
    {
        $mail = new PHPMailer(true);

        try {
            // DEBUG (local) - Descomente a linha abaixo para ver logs SMTP
            // if (APP_ENV !== 'prod') {
            //     $mail->SMTPDebug  = 2;          // 0 desliga | 2 mostra conversa SMTP
            //     $mail->Debugoutput = 'html';
            // }

            $mail->isSMTP();
            $mail->Host       = MAIL_HOST;
            $mail->SMTPAuth   = true;
            $mail->Username   = MAIL_USER;
            $mail->Password   = MAIL_PASS;

            // Gmail STARTTLS
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = MAIL_PORT;

            // Importante para acentos
            $mail->CharSet = 'UTF-8';

            // Remetente deve bater com o usuário
            $mail->setFrom(MAIL_FROM, MAIL_FROM_NAME);
            $mail->addAddress($toEmail, $toName);

            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body    = $htmlBody;

            // Alternativa texto puro (ajuda anti-spam e alguns provedores)
            $mail->AltBody  = strip_tags($htmlBody);

            return $mail->send();
        } catch (Exception $e) {
            // Em dev: mostre erro real
            if (APP_ENV !== 'prod') {
                echo "<pre>Mailer error: " . htmlspecialchars($mail->ErrorInfo) . "</pre>";
            }
            return false;
        }
    }
}
