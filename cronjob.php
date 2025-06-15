<?php
if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    echo "Access denied. This script can only be run from the command line.";
    exit;
}

require_once 'GenVcode.php';
$githubHtml = fetchGitHubTimeline();
$formattedContent = formatGitHubData($githubHtml);
sendGitHubUpdatesToRegisteredEmails($formattedContent);

echo "GitHub timeline updates sent.\n";
?>