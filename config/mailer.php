<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once dirname(__DIR__) . '/vendor/autoload.php';

function sendVerificationEmail(string $recipient, string $code): bool
{
    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;

        // Replace these with your own Gmail and App Password.
        $mail->Username   = 'escalajazzmartin@gmail.com';
        $mail->Password   = 'dvte wrxt llge fsei';

        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        $mail->CharSet = 'UTF-8';
        $mail->setFrom($mail->Username, 'ByteBite');
        $mail->addAddress($recipient);

        $mail->isHTML(true);
        $mail->Subject = 'Your ByteBite Verification Code';

        $mail->Body = '
            <div style="font-family:Arial,sans-serif;max-width:500px;margin:auto;padding:25px;color:#303039;">
                <h2 style="color:#15734B;">Welcome to ByteBite!</h2>
                <p>Use the verification code below to verify your email address.</p>
                <div style="background:#EAF5ED;padding:20px;text-align:center;border-radius:10px;">
                    <h1 style="color:#15734B;letter-spacing:8px;margin:0;">'
                    . htmlspecialchars($code, ENT_QUOTES, 'UTF-8') .
                    '</h1>
                </div>
                <p>This code expires in <strong>10 minutes</strong>.</p>
                <p>If you did not create a ByteBite account, you can ignore this email.</p>
            </div>
        ';

        $mail->AltBody = "Your ByteBite verification code is: $code. It expires in 10 minutes.";

        $mail->send();
        return true;

    } catch (Exception $e) {
        error_log('ByteBite email error: ' . $mail->ErrorInfo);
        return false;
    }
}