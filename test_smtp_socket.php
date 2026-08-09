<?php
$to = 'shubhamchaurasiya2025@gmail.com';
$otp = '847291';
$host = 'ssl://mail.syonra.life';
$port = 465;
$user = 'noreply@syonra.life';
$pass = '775299@Ss';

$ctx = stream_context_create(['ssl' => [
    'verify_peer' => false,
    'verify_peer_name' => false,
    'allow_self_signed' => true
]]);

echo "Connecting to {$host}:{$port}...\n";
$sock = @stream_socket_client("{$host}:{$port}", $errno, $errstr, 15, STREAM_CLIENT_CONNECT, $ctx);
if (!$sock) {
    echo "SOCKET CONNECT FAILED: {$errno} - {$errstr}\n";
    exit(1);
}

echo "CONNECTED! Reading banner...\n";
echo "BANNER: " . fgets($sock, 512);

fputs($sock, "EHLO syonra.life\r\n");
while ($line = fgets($sock, 512)) {
    echo "EHLO: " . trim($line) . "\n";
    if (preg_match('/^250[ \r\n]/', $line)) break;
}

fputs($sock, "AUTH LOGIN\r\n");
echo "AUTH LOGIN RESP: " . fgets($sock, 512);

fputs($sock, base64_encode($user) . "\r\n");
echo "USER RESP: " . fgets($sock, 512);

fputs($sock, base64_encode($pass) . "\r\n");
$authResp = fgets($sock, 512);
echo "PASS RESP: " . $authResp;

if (strpos($authResp, '235') === false) {
    echo "SMTP AUTH FAILED!\n";
    fclose($sock);
    exit(1);
}

fputs($sock, "MAIL FROM: <{$user}>\r\n");
echo "MAIL FROM RESP: " . fgets($sock, 512);

fputs($sock, "RCPT TO: <{$to}>\r\n");
echo "RCPT TO RESP: " . fgets($sock, 512);

fputs($sock, "DATA\r\n");
echo "DATA RESP: " . fgets($sock, 512);

$body = "From: BRIO World School <{$user}>\r\n";
$body .= "To: <{$to}>\r\n";
$body .= "Subject: Audit Test OTP: {$otp}\r\n";
$body .= "MIME-Version: 1.0\r\n";
$body .= "Content-Type: text/html; charset=UTF-8\r\n\r\n";
$body .= "<div style='font-family: Arial, sans-serif; max-width: 550px; margin: 0 auto; padding: 25px; border: 1px solid #E2E8F0; border-radius: 14px; background-color: #FFFFFF;'>
    <h2 style='color: #0F172A;'>BRIO WORLD SCHOOL</h2>
    <p>Your 6-digit Verification OTP code is: <strong>{$otp}</strong></p>
</div>\r\n.\r\n";

fputs($sock, $body);
echo "DATA COMMIT RESP: " . fgets($sock, 512);

fputs($sock, "QUIT\r\n");
echo "QUIT RESP: " . fgets($sock, 512);
fclose($sock);
echo "SUCCESS! EMAIL DISPATCHED SUCCESSFULLY!\n";
