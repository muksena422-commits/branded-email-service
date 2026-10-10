<?php
require_once __DIR__ . '/includes/lib/mailer.php';
$result = sendMail('student@example.com', 'Test email', '<h1>Hello from my project</h1>');
echo '<pre>'; print_r($result); echo '</pre>';