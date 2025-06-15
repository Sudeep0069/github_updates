<?php

function generateVerificationCode(): string {
    return str_pad(random_int(100000, 999999), 6, '0', STR_PAD_LEFT);
}

function sendEmail(string $email, string $subject, string $body): bool {
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= "From: no-reply@githubtimeline.com" . "\r\n";
    return mail($email, $subject, $body, $headers);
}

function sendVerificationEmail(string $email, string $code, string $type = 'register'): bool {
    $subject = ($type === 'register') ? "Your GitHub Timeline Verification Code" : "Your GitHub Timeline Unsubscribe Code";
    $body = "
        <html>
        <head>
            <title>{$subject}</title>
        </head>
        <body>
            <p>Hello,</p>
            <p>Your <b>{$type}</b> verification code for GitHub Timeline is: <strong>{$code}</strong></p>
            <p>Please use this code to complete your request.</p>
            <p>If you did not request this, please ignore this email.</p>
        </body>
        </html>
    ";
    return sendEmail($email, $subject, $body);
}

function registerEmail(string $email): bool {
    $file ='registered_emails.txt';
    if (file_exists($file)) {
        $registeredEmails = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if (in_array($email, $registeredEmails)) {
            return false; 
        }
    }

    return (bool)file_put_contents($file,"\n$email" . PHP_EOL, FILE_APPEND | LOCK_EX);
}

function unsubscribeEmail(string $email): bool {
    $file ='registered_emails.txt';
    if (!file_exists($file)) {
        return false;
    }

    $registeredEmails = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $initialCount = count($registeredEmails);
    $updatedEmails = array_diff($registeredEmails, [$email]);

    if (count($updatedEmails) === $initialCount) {
        return false;
    }

    return (bool)file_put_contents($file, implode(PHP_EOL, $updatedEmails) . (empty($updatedEmails) ? '' : PHP_EOL), LOCK_EX);
}

function fetchGitHubTimeline(): string {
    $url = 'https://github.com/';
    $context = stream_context_create([
        'http' => [
            'header' => 'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36'
        ]
    ]);
    $htmlContent = @file_get_contents($url, false, $context);
    return $htmlContent !== false ? $htmlContent : '';
}

function formatGitHubData(string $htmlContent): string {
    $title = 'GitHub Updates';
    if (preg_match('/<title>(.*?)<\/title>/is', $htmlContent, $matches)) {
        $title = $matches[1];
    }

    $formattedHtml = "
        <html>
        <head>
            <title>Daily GitHub Timeline Updates</title>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 20px auto; padding: 20px; border: 1px solid #ddd; border-radius: 8px; background-color: #f9f9f9; }
                h1 { color:rgb(38, 208, 174); }
                .update-item { background-color: #fff; border: 1px solid #eee; padding: 15px; margin-bottom: 10px; border-radius: 5px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <h1>Latest GitHub Updates</h1>
                <p>Here's a snapshot of recent activities on GitHub:</p>
                <div class='update-item'>
                    <h2>" . htmlspecialchars($title) . "</h2>
                    <p>This is a placeholder for actual timeline content. In a real scenario, specific data like repository updates, new issues, pull requests, etc., would be extracted and listed here.</p>
                    <p>Visit <a href='https://github.com'>GitHub.com</a> for more details.</p>
                </div>
                <p>To stop receiving these updates, you can <a href='http://your-domain.com/unsubscribe.php'>unsubscribe here</a>.</p>
                <p>Thank you!</p>
            </div>
        </body>
        </html>
    ";
    return $formattedHtml;
}

function sendGitHubUpdatesToRegisteredEmails(string $htmlContent): void {
    $file ='registered_emails.txt';
    if (!file_exists($file)) {
        error_log("registered_emails.txt not found for sending updates.");
        return;
    }

    $registeredEmails = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $subject = "Your Daily GitHub Timeline Updates!";

    foreach ($registeredEmails as $email) {
        $unsubscribeLink = 'http://' . $_SERVER['HTTP_HOST'] . '/unsubscribe.php'; // Adjust this URL based on your deployment
        $emailBody = str_replace("http://your-domain.com/unsubscribe.php", $unsubscribeLink, $htmlContent);
        if (!sendEmail($email, $subject, $emailBody)) {
            error_log("Failed to send GitHub update to: " . $email);
        }
    }
}