<?php
include 'GenVcode.php';
session_start();

$message = '';
$messageType = '';
if (isset($_POST['submit_email']) && !empty($_POST['email'])) {
    $email = filter_var($_POST['email'], FILTER_VALIDATE_EMAIL);
    if ($email) {
        $file ='registered_emails.txt';
        $registeredEmails = file_exists($file) ? file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) : [];

        if (!in_array($email, $registeredEmails)) {
            $message = "This email address is not currently subscribed.";
            $messageType = 'error';
        } else {
            $verificationCode = generateVerificationCode();
            $_SESSION['unsubscribe_email'] = $email;
            $_SESSION['unsubscribe_code'] = $verificationCode;
            $_SESSION['unsubscribe_timestamp'] = time(); 

            if (sendVerificationEmail($email, $verificationCode, 'unsubscribe')) {
                $message = "A verification code has been sent to your email address to confirm unsubscription.";
                $messageType = 'success';
            } else {
                $message = "Failed to send verification email. Please try again later.";
                $messageType = 'error';
            }
        }
    } else {
        $message = "Please enter a valid email address.";
        $messageType = 'error';
    }
}

if (isset($_POST['verify_code']) && !empty($_POST['code'])) {
    $enteredCode = trim($_POST['code']);
    $sessionEmail = $_SESSION['unsubscribe_email'] ?? '';
    $sessionCode = $_SESSION['unsubscribe_code'] ?? '';
    $codeTimestamp = $_SESSION['unsubscribe_timestamp'] ?? 0;
    if (time() - $codeTimestamp > 600) { 
        $message = "Your verification code has expired. Please request a new one.";
        $messageType = 'error';
        unset($_SESSION['unsubscribe_email'], $_SESSION['unsubscribe_code'], $_SESSION['unsubscribe_timestamp']);
    } elseif ($enteredCode === $sessionCode && $sessionEmail) {
        if (unsubscribeEmail($sessionEmail)) {
            $message = "You have been successfully unsubscribed from GitHub timeline updates.";
            $messageType = 'success';
            unset($_SESSION['unsubscribe_email'], $_SESSION['unsubscribe_code'], $_SESSION['unsubscribe_timestamp']);
        } else {
            $message = "Failed to unsubscribe or email not found. Please try again.";
            $messageType = 'error';
        }
    } else {
        $message = "Invalid verification code or email. Please try again.";
        $messageType = 'error';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Unsubscribe from GitHub Timeline</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #f4f4f4; margin: 0; padding: 20px; display: flex; justify-content: center; align-items: center; min-height: 100vh; }
        .container { background-color: #fff; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); width: 100%; max-width: 400px; }
        h1 { text-align: center; color: #333; margin-bottom: 25px; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; color: #555; }
        input[type="email"], input[type="text"] { width: calc(100% - 22px); padding: 10px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; }
        button { width: 100%; padding: 10px; background-color: #dc3545; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 16px; }
        button:hover { background-color: #c82333; }
        .message { padding: 10px; margin-bottom: 15px; border-radius: 4px; text-align: center; }
        .message.success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .message.error { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .subscribe-link { text-align: center; margin-top: 20px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Unsubscribe from GitHub Timeline Updates</h1>

        <?php if ($message): ?>
            <div class="message <?php echo $messageType; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <form action="" method="POST">
            <div class="form-group">
                <label for="email">Email Address:</label>
                <input type="email" id="email" name="email" placeholder="Enter your email" required>
            </div>
            <button type="submit" name="submit_email">Get Unsubscribe Code</button>
        </form>

        <hr style="margin: 25px 0;">

        <form action="" method="POST">
            <div class="form-group">
                <label for="code">Verification Code:</label>
                <input type="text" id="code" name="code" placeholder="Enter 6-digit code" maxlength="6" pattern="\d{6}" title="Please enter a 6-digit number" required>
            </div>
            <button type="submit" name="verify_code">Verify and Unsubscribe</button>
        </form>

        <div class="subscribe-link">
            <p>Want to subscribe? <a href="email.php">Click here</a></p>
        </div>
    </div>
</body>
</html>