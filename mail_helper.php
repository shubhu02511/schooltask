<?php
// Mail and OTP helper - SSL Socket SMTP with RFC 5321 EHLO multi-line parsing
if (!function_exists('generateOTP')) {
    function generateOTP() {
        return (string)rand(100000, 999999);
    }
}

if (!function_exists('sendOTPEmail')) {
    function sendOTPEmail($toEmail, $otpCode, $subject = "Your BRIO World School Verification OTP") {
        $cleanEmail = strtolower(trim($toEmail));
        
        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }
        $_SESSION['latest_otp_' . $cleanEmail] = [
            'code' => $otpCode,
            'expires' => time() + 600
        ];

        try {
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
                return false;
            }

            fgets($sock, 512);
            fputs($sock, "EHLO syonra.life\r\n");
            while ($line = fgets($sock, 512)) {
                if (preg_match('/^250[ \r\n]/', $line)) {
                    break;
                }
            }

            fputs($sock, "AUTH LOGIN\r\n");
            fgets($sock, 512);
            fputs($sock, base64_encode($user) . "\r\n");
            fgets($sock, 512);
            fputs($sock, base64_encode($pass) . "\r\n");
            $authResp = fgets($sock, 512);

            if (strpos($authResp, '235') === false) {
                fclose($sock);
                return false;
            }

            fputs($sock, "MAIL FROM: <{$user}>\r\n");
            fgets($sock, 512);
            fputs($sock, "RCPT TO: <{$cleanEmail}>\r\n");
            fgets($sock, 512);
            fputs($sock, "DATA\r\n");
            fgets($sock, 512);

            $msg = "From: BRIO World School <{$user}>\r\n";
            $msg .= "To: <{$cleanEmail}>\r\n";
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
            fgets($sock, 512);
            fputs($sock, "QUIT\r\n");
            fclose($sock);
            return true;
        } catch (Throwable $e) {
            return false;
        }
    }
}
