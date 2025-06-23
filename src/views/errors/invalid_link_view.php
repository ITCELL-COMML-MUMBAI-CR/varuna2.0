<?php http_response_code(403); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invalid Link - VARUNA System</title>
    <link rel="stylesheet" href="<?php echo defined('BASE_URL') ? BASE_URL : '../public/css/style.css'; ?>">
    <style>
        .error-container { text-align: center; padding: 40px; }
        .error-heading { font-size: 2rem; color: var(--primary-color); }
    </style>
</head>
<body>
    <header class="main-header">
        <div class="logo-container"><img src="<?php echo defined('BASE_URL') ? BASE_URL : '../public/'; ?>images/indian_railways_logo.png" alt="Logo" class="logo"></div>
        <div class="system-name-container"><h1 class="system-name">VARUNA</h1></div>
    </header>
    <div class="error-container">
        <h1 class="error-heading">Access Link is Invalid or Has Expired</h1>
        <p>Please contact the issuing authority to request a new access link.</p>
    </div>
</body>
</html>