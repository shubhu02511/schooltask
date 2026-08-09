<?php
// Mail and OTP helper - SSL Socket SMTP (Port 465) with RFC 5321 EHLO multi-line parsing
if (!function_exists('generateOTP')) {
    function generateOTP() {
        return (string)rand(100000, 999999);
    }
}

if (!function_exists('checkOTPCooldown')) {
    function checkOTPCooldown($email) {
        $cleanEmail = strtolower(trim($email));
        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }
        $lastSent = $_SESSION['latest_otp_' . $cleanEmail]['created_at'] ?? 0;
        $elapsed = time() - $lastSent;
        if ($elapsed < 60) {
            return 60 - $elapsed;
        }
        return 0;
    }
}

if (!function_exists('sendOTPEmail')) {
    function sendOTPEmail($toEmail, $otpCode, $subject = "Your BRIO World School Verification OTP") {
        $cleanEmail = strtolower(trim($toEmail));
        
        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }
        // Expire after 5 minutes (300 seconds)
        $_SESSION['latest_otp_' . $cleanEmail] = [
            'code' => $otpCode,
            'expires' => time() + 300,
            'created_at' => time()
        ];

        try {
            $rawHost = defined('SMTP_HOST') ? SMTP_HOST : 'mail.syonra.life';
            $port = defined('SMTP_PORT') ? SMTP_PORT : 465;
            $user = defined('SMTP_USER') ? SMTP_USER : 'noreply@syonra.life';
            $pass = defined('SMTP_PASS') ? SMTP_PASS : '775299@Ss';

            $host = (strpos($rawHost, 'ssl://') === false && $port == 465) ? "ssl://{$rawHost}" : $rawHost;

            $context = stream_context_create([
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true
                ]
            ]);

            $sock = @stream_socket_client("{$host}:{$port}", $errno, $errstr, 12, STREAM_CLIENT_CONNECT, $context);
            if (!$sock) {
                // Fallback to PHP native mail() if socket is unavailable
                $headers = "From: BRIO World School <{$user}>\r\n" .
                           "Reply-To: {$user}\r\n" .
                           "MIME-Version: 1.0\r\n" .
                           "Content-Type: text/html; charset=UTF-8\r\n";
                return @mail($cleanEmail, $subject, getOTPEmailBodyHTML($otpCode), $headers, "-f" . $user);
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
                $headers = "From: BRIO World School <{$user}>\r\n" .
                           "Reply-To: {$user}\r\n" .
                           "MIME-Version: 1.0\r\n" .
                           "Content-Type: text/html; charset=UTF-8\r\n";
                return @mail($cleanEmail, $subject, getOTPEmailBodyHTML($otpCode), $headers, "-f" . $user);
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
            $msg .= getOTPEmailBodyHTML($otpCode) . "\r\n.\r\n";

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

if (!function_exists('getOTPEmailBodyHTML')) {
    function getOTPEmailBodyHTML($otpCode) {
        return "<div style='font-family: Arial, sans-serif; max-width: 550px; margin: 0 auto; padding: 25px; border: 1px solid #E2E8F0; border-radius: 14px; background-color: #FFFFFF; box-shadow: 0 10px 25px rgba(0,0,0,0.05);'>
            <div style='text-align: center; border-bottom: 3px solid #F59E0B; padding-bottom: 18px; margin-bottom: 22px;'>
                <h2 style='color: #0F172A; margin: 0; font-size: 24px; font-weight: 800;'>BRIO WORLD SCHOOL</h2>
                <p style='color: #F59E0B; font-weight: 700; font-size: 14px; margin: 6px 0 0 0; text-transform: uppercase; letter-spacing: 1px;'>Official Account Verification</p>
            </div>
            <p style='color: #334155; font-size: 15px; margin-bottom: 10px;'>Hello,</p>
            <p style='color: #334155; font-size: 15px; line-height: 1.6; margin-bottom: 20px;'>Your 6-digit Email Verification OTP code for BRIO World School portal authentication is:</p>
            <div style='text-align: center; margin: 28px 0;'>
                <span style='font-size: 34px; font-weight: 800; letter-spacing: 10px; color: #0F172A; background-color: #FEF3C7; padding: 14px 28px; border-radius: 10px; border: 2px solid #F59E0B; display: inline-block;'>{$otpCode}</span>
            </div>
            <p style='color: #64748B; font-size: 13px; line-height: 1.5; margin-top: 25px; border-top: 1px solid #E2E8F0; padding-top: 15px;'>
                ⏱️ <strong>Security Notice:</strong> This OTP code expires in <strong>5 minutes</strong> and can only be used once. Please do not share this code with anyone.
            </p>
        </div>";
    }
}
