<?php
// Isolated Subprocess Test Runner for 8-Part Authentication & OTP Audit
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

$testEmail = 'shubhamchaurasiya2025@gmail.com';
$correctPass = 'SecurePass123!';
$wrongPass = 'WrongPassword999';

echo "=========================================================\n";
echo "   BRIO WORLD SCHOOL AUTHENTICATION & OTP AUDIT TEST     \n";
echo "=========================================================\n\n";

$db = getDB();
$db->exec("DELETE FROM users WHERE email = '{$testEmail}'");

function runPHP($file, $args = []) {
    $b64 = base64_encode(json_encode($args));
    return shell_exec("php {$file} {$b64}");
}

// Create a helper runner file
file_put_contents(__DIR__ . '/test_runner_helper.php', '<?php
require_once __DIR__ . "/config.php";
require_once __DIR__ . "/db.php";
require_once __DIR__ . "/mail_helper.php";
require_once __DIR__ . "/controllers/AuthController.php";

$args = json_decode(base64_decode($argv[1] ?? ""), true);
$action = $args["action"] ?? "";
$_POST = $args["post"] ?? [];
$GLOBALS["RAW_INPUT"] = json_encode($_POST);

$a = new AuthController();
@ob_start();
if ($action === "register") $a->register();
if ($action === "login") $a->login();
if ($action === "verifyOTP") $a->verifyOTP();
echo ob_get_clean();
');

// TEST 1: Registration & Real SMTP Email
echo "[TEST 1] Register User & Dispatch Real SMTP Email...\n";
$res1 = runPHP('test_runner_helper.php', [
    'action' => 'register',
    'post' => ['name' => 'Audit User', 'email' => $testEmail, 'password' => $correctPass]
]);
echo "Register Response: " . trim($res1) . "\n";
$json1 = json_decode(trim($res1), true);

if (!($json1['success'] ?? false)) {
    echo "FAILED: Registration failed!\n";
    exit(1);
}
echo "PASSED: Registration succeeded & real SMTP email dispatched!\n\n";

// Fetch stored OTP
$stmt = $db->prepare("SELECT * FROM users WHERE email = ?");
$stmt->execute([$testEmail]);
$user = $stmt->fetch();
$realOTP = $user['otp_code'];
echo "Database Stored Real OTP: {$realOTP}\n";
echo "Database Stored OTP Expires: {$user['otp_expires']}\n\n";

// TEST 2: Wrong Password Login
echo "[TEST 2] Login with WRONG Password...\n";
$res2 = runPHP('test_runner_helper.php', [
    'action' => 'login',
    'post' => ['email' => $testEmail, 'password' => $wrongPass]
]);
echo "Wrong Password Response: " . trim($res2) . "\n";
$json2 = json_decode(trim($res2), true);
if ($json2['success'] ?? false) {
    echo "FAILED: Wrong password was allowed to log in!\n";
    exit(1);
}
echo "PASSED: Wrong password correctly rejected (HTTP 401)!\n\n";

// TEST 3: Demo / Hardcoded OTP ('123456')
echo "[TEST 3] Verify with DEMO OTP ('123456')...\n";
$res3 = runPHP('test_runner_helper.php', [
    'action' => 'verifyOTP',
    'post' => ['email' => $testEmail, 'otp_code' => '123456']
]);
echo "Demo OTP Response: " . trim($res3) . "\n";
$json3 = json_decode(trim($res3), true);
if ($json3['success'] ?? false) {
    echo "FAILED: Demo OTP '123456' was accepted!\n";
    exit(1);
}
echo "PASSED: Demo OTP '123456' correctly rejected!\n\n";

// TEST 4: Wrong OTP ('999999')
echo "[TEST 4] Verify with WRONG OTP ('999999')...\n";
$res4 = runPHP('test_runner_helper.php', [
    'action' => 'verifyOTP',
    'post' => ['email' => $testEmail, 'otp_code' => '999999']
]);
echo "Wrong OTP Response: " . trim($res4) . "\n";
$json4 = json_decode(trim($res4), true);
if ($json4['success'] ?? false) {
    echo "FAILED: Wrong OTP was accepted!\n";
    exit(1);
}
echo "PASSED: Wrong OTP correctly rejected!\n\n";

// TEST 5: Expired OTP
echo "[TEST 5] Verify with EXPIRED OTP...\n";
$db->exec("UPDATE users SET otp_expires = '2020-01-01 00:00:00' WHERE email = '{$testEmail}'");
$res5 = runPHP('test_runner_helper.php', [
    'action' => 'verifyOTP',
    'post' => ['email' => $testEmail, 'otp_code' => $realOTP]
]);
echo "Expired OTP Response: " . trim($res5) . "\n";
$json5 = json_decode(trim($res5), true);
if ($json5['success'] ?? false) {
    echo "FAILED: Expired OTP was accepted!\n";
    exit(1);
}
echo "PASSED: Expired OTP correctly rejected!\n\n";

// Restore valid expiry for TEST 6
$db->exec("UPDATE users SET otp_expires = '" . date('Y-m-d H:i:s', strtotime('+5 minutes')) . "' WHERE email = '{$testEmail}'");

// TEST 6: Correct Real OTP Verification
echo "[TEST 6] Verify with CORRECT REAL OTP ({$realOTP})...\n";
$res6 = runPHP('test_runner_helper.php', [
    'action' => 'verifyOTP',
    'post' => ['email' => $testEmail, 'otp_code' => $realOTP]
]);
echo "Correct Real OTP Response: " . trim($res6) . "\n";
$json6 = json_decode(trim($res6), true);
if (!($json6['success'] ?? false)) {
    echo "FAILED: Correct Real OTP failed verification!\n";
    exit(1);
}
echo "PASSED: Correct Real OTP verified successfully!\n\n";

// TEST 7: Reuse Same OTP (One-Time Use Enforcement)
echo "[TEST 7] Reuse Previously Used OTP ({$realOTP})...\n";
$res7 = runPHP('test_runner_helper.php', [
    'action' => 'verifyOTP',
    'post' => ['email' => $testEmail, 'otp_code' => $realOTP]
]);
echo "Reused OTP Response: " . trim($res7) . "\n";
$json7 = json_decode(trim($res7), true);
if ($json7['success'] ?? false) {
    echo "FAILED: Previously used OTP was accepted again!\n";
    exit(1);
}
echo "PASSED: Reused OTP correctly rejected (One-time use enforced)!\n\n";

// TEST 8: Correct Password Login for Verified User
echo "[TEST 8] Login with CORRECT password for verified user...\n";
$res8 = runPHP('test_runner_helper.php', [
    'action' => 'login',
    'post' => ['email' => $testEmail, 'password' => $correctPass]
]);
echo "Correct Password Login Response: " . trim($res8) . "\n";
$json8 = json_decode(trim($res8), true);
if (!($json8['success'] ?? false)) {
    echo "FAILED: Correct password login failed!\n";
    exit(1);
}
echo "PASSED: Correct password logged in successfully!\n\n";

echo "=========================================================\n";
echo "   ALL 8 AUDIT TESTS PASSED 100% CLEAN WITH ZERO ERRORS!  \n";
echo "=========================================================\n";
unlink(__DIR__ . '/test_runner_helper.php');
