<?php
// Mail and OTP helper with Native Socket SMTP Support
require_once __DIR__ . '/config.php';

function generateOTP() {
    return (string)rand(100000, 999999);
}

function sendSMTPEmail($toEmail, $subject, $body) {
    try {
        $host = SMTP_HOST;
        $port = SMTP_PORT;
        $username = SMTP_USER;
        $password = SMTP_PASS;
        $fromName = SMTP_FROM_NAME;

        $protocol = ($port == 465) ? 'ssl://' : '';
        $socketHost = $protocol . $host;

        $socket = @fsockopen($socketHost, $port, $errno, $errstr, 5);
        if (!$socket) {
            return false;
        }

        $read = function($socket) {
            $response = '';
            while ($str = @fgets($socket, 512)) {
                $response .= $str;
                if (substr($str, 3, 1) == ' ') break;
            }
            return $response;
        };

        $write = function($socket, $cmd) {
            @fputs($socket, $cmd . "\r\n");
        };

        $read($socket); // Banner response

        $write($socket, "EHLO " . $host);
        $read($socket);

        if ($port == 587) {
            $write($socket, "STARTTLS");
            $read($socket);
            @stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
            $write($socket, "EHLO " . $host);
            $read($socket);
        }

        $write($socket, "AUTH LOGIN");
        $read($socket);

        $write($socket, base64_encode($username));
        $read($socket);

        $write($socket, base64_encode($password));
        $authResp = $read($socket);

        if (substr($authResp, 0, 3) != '235') {
            @fclose($socket);
            return false;
        }

        $write($socket, "MAIL FROM: <{$username}>");
        $read($socket);

        $write($socket, "RCPT TO: <{$toEmail}>");
        $read($socket);

        $write($socket, "DATA");
        $read($socket);

        $headers = "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
        $headers .= "From: {$fromName} <{$username}>\r\n";
        $headers .= "To: <{$toEmail}>\r\n";
        $headers .= "Subject: {$subject}\r\n";
        $headers .= "Date: " . date('r') . "\r\n";

        $emailData = $headers . "\r\n" . $body . "\r\n.";
        $write($socket, $emailData);
        $read($socket);

        $write($socket, "QUIT");
        @fclose($socket);
        return true;
    } catch (Exception $e) {
        return false;
    }
}

function sendOTPEmail($toEmail, $otpCode, $subject = "Your BRIO World School Verification OTP") {
    $cleanEmail = strtolower(trim($toEmail));
    
    if (session_status() === PHP_SESSION_NONE) {
        @session_start();
    }
    $_SESSION['latest_otp_' . $cleanEmail] = [
        'code' => $otpCode,
        'expires' => time() + 600
    ];

    $htmlBody = "
    <div style='font-family: Arial, sans-serif; max-width: 550px; margin: 0 auto; padding: 20px; border: 1px solid #E2E8F0; border-radius: 12px; background-color: #F8FAFC;'>
        <div style='text-align: center; border-bottom: 2px solid #F59E0B; padding-bottom: 15px; margin-bottom: 20px;'>
            <h2 style='color: #0F172A; margin: 0;'>BRIO WORLD SCHOOL</h2>
            <p style='color: #F59E0B; font-weight: bold; margin: 5px 0 0 0;'>Official Account Verification</p>
        </div>
        <p style='color: #334155; font-size: 15px;'>Hello,</p>
        <p style='color: #334155; font-size: 15px;'>Your 6-digit Email Verification OTP code is:</p>
        <div style='text-align: center; margin: 25px 0;'>
            <span style='font-size: 32px; font-weight: bold; letter-spacing: 8px; color: #0F172A; background-color: #FEF3C7; padding: 12px 24px; border-radius: 8px; border: 1px solid #F59E0B;'>{$otpCode}</span>
        </div>
        <p style='color: #64748B; font-size: 14px;'>This OTP code is valid for <strong>10 minutes</strong>. Please do not share this code with anyone.</p>
        <hr style='border: none; border-top: 1px solid #E2E8F0; margin: 20px 0;'>
        <p style='color: #94A3B8; font-size: 12px; text-align: center;'>BRIO World School • Admissions & IT Support Team</p>
    </div>
    ";

    $sent = sendSMTPEmail($cleanEmail, $subject, $htmlBody);
    if (!$sent) {
        $headers = "From: " . SMTP_FROM_NAME . " <" . SMTP_USER . ">\r\n";
        $headers .= "Reply-To: " . SMTP_USER . "\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
        @mail($cleanEmail, $subject, $htmlBody, $headers);
    }
    return true;
}
