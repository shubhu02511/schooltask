<?php
// Mail and OTP helper optimized for shared web hosting (SMTP)
require_once __DIR__ . '/config.php';

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
            $host = defined('SMTP_HOST') ? SMTP_HOST : 'mail.syonra.life';
            $port = defined('SMTP_PORT') ? SMTP_PORT : 587;
            $user = defined('SMTP_USER') ? SMTP_USER : 'noreply@syonra.life';
            $pass = defined('SMTP_PASS') ? SMTP_PASS : '775299@Ss';

            $sock = @fsockopen($host, $port, $errno, $errstr, 8);
            if ($sock) {
                fgets($sock, 512);
                fputs($sock, "EHLO syonra.life\r\n");
                while ($line = fgets($sock, 512)) {
                    if (substr($line, 3, 1) == ' ') break;
                }

                fputs($sock, "AUTH LOGIN\r\n");
                fgets($sock, 512);
                fputs($sock, base64_encode($user) . "\r\n");
                fgets($sock, 512);
                fputs($sock, base64_encode($pass) . "\r\n");
                $authRes = fgets($sock, 512);

                if (strpos($authRes, '235') !== false) {
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
                }
                fclose($sock);
            }
        } catch (Throwable $e) {
            // Silently fall back if SMTP socket fails
        }

        // Fallback to PHP mail()
        if (function_exists('mail')) {
            $fromEmail = defined('SMTP_USER') ? SMTP_USER : 'noreply@syonra.life';
            $headers = "From: BRIO School <" . $fromEmail . ">\r\n" .
                       "Reply-To: " . $fromEmail . "\r\n" .
                       "Content-Type: text/html; charset=UTF-8";
            $body = "<p>Your OTP code is: <strong>{$otpCode}</strong></p>";
            @mail($cleanEmail, $subject, $body, $headers);
        }

        return true;
    }
}
