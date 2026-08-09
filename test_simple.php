<?php
// SSL Socket SMTP Mailer with RFC 5321 compliant multi-line EHLO handling
error_reporting(E_ALL);
ini_set('display_errors', 1);

function sendSSLSMTP($toEmail, $otpCode) {
    $host = 'ssl://mail.syonra.life';
    $port = 465;
    $user = 'noreply@syonra.life';
    $pass = '775299@Ss';

    $context = stream_context_create([
        'ssl' => [
            'verify_peer' => false,
            'verify_peer_name' => false,
            'allow_self_signed' => true
        ]
    ]);

    $sock = @stream_socket_client("{$host}:{$port}", $errno, $errstr, 10, STREAM_CLIENT_CONNECT, $context);
    if (!$sock) {
        return ['success' => false, 'error' => "SSL Socket connection failed: $errstr ($errno)"];
    }

    $log = [];
    $log[] = fgets($sock, 512);

    fputs($sock, "EHLO syonra.life\r\n");
    while ($line = fgets($sock, 512)) {
        $log[] = $line;
        if (preg_match('/^250[ \r\n]/', $line)) {
            break;
        }
    }

    fputs($sock, "AUTH LOGIN\r\n");
    $log[] = fgets($sock, 512);
    fputs($sock, base64_encode($user) . "\r\n");
    $log[] = fgets($sock, 512);
    fputs($sock, base64_encode($pass) . "\r\n");
    $authResp = fgets($sock, 512);
    $log[] = $authResp;

    if (strpos($authResp, '235') === false) {
        fclose($sock);
        return ['success' => false, 'error' => "SMTP Auth failed: " . trim($authResp), 'log' => $log];
    }

    fputs($sock, "MAIL FROM: <{$user}>\r\n");
    $log[] = fgets($sock, 512);
    fputs($sock, "RCPT TO: <{$toEmail}>\r\n");
    $log[] = fgets($sock, 512);
    fputs($sock, "DATA\r\n");
    $log[] = fgets($sock, 512);

    $subject = "Your BRIO World School Verification OTP: " . $otpCode;
    $msg = "From: BRIO World School <{$user}>\r\n";
    $msg .= "To: <{$toEmail}>\r\n";
    $msg .= "Subject: {$subject}\r\n";
    $msg .= "MIME-Version: 1.0\r\n";
    $msg .= "Content-Type: text/html; charset=UTF-8\r\n\r\n";
    $msg .= "<div style='font-family: Arial, sans-serif; max-width: 550px; margin: 0 auto; padding: 20px; border: 1px solid #E2E8F0; border-radius: 12px; background-color: #F8FAFC;'>
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
    </div>\r\n.\r\n";

    fputs($sock, $msg);
    $log[] = fgets($sock, 512);
    fputs($sock, "QUIT\r\n");
    fclose($sock);

    return ['success' => true, 'message' => "OTP Email successfully sent to {$toEmail}!", 'otp' => $otpCode, 'log' => $log];
}

$to = isset($_GET['email']) ? trim($_GET['email']) : 'shubhamchaurasiya2025@gmail.com';
$otp = strval(rand(100000, 999999));
$res = sendSSLSMTP($to, $otp);

header('Content-Type: application/json');
echo json_encode($res, JSON_PRETTY_PRINT);
