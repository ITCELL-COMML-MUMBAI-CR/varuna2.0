<?php
// Set the HTTP response code to 403 (Forbidden)
http_response_code(403);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Link Expired - VARUNA System</title>
    <link rel="stylesheet" href="<?php echo defined('BASE_URL') ? BASE_URL : '../public/css/style.css'; ?>">
    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: 'Poppins', sans-serif;
            background-color: var(--secondary-color, #f5f5f5);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        .header {
            width: 100%;
            background-color: #fff;
            padding: 0.5rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            display: flex;
            align-items: center;
            justify-content: flex-start;
            box-sizing: border-box;
        }
        .logo {
            height: 40px;
            width: auto;
            margin-right: 1rem;
        }
        .system-name {
            color: var(--primary-color, #1a73e8);
            font-size: 1.5rem;
            font-weight: 600;
            margin: 0;
        }
        .error-container {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 2rem;
            text-align: center;
            max-width: 800px;
            margin: 0 auto;
        }
        .error-heading {
            font-size: 2rem;
            color: var(--primary-color, #1a73e8);
            margin-bottom: 1.5rem;
            font-weight: 600;
        }
        .error-message {
            font-size: 1.1rem;
            color: #555;
            margin-bottom: 2rem;
            line-height: 1.6;
            max-width: 600px;
        }
        .contact-info {
            font-size: 1rem;
            color: #666;
            margin-bottom: 2rem;
            padding: 1.5rem;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            max-width: 500px;
            width: 100%;
        }
        @media (max-width: 768px) {
            .error-container {
                padding: 1rem;
            }
            .error-heading {
                font-size: 1.5rem;
            }
            .error-message {
                font-size: 1rem;
            }
            .contact-info {
                padding: 1rem;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <img src="<?php echo defined('BASE_URL') ? BASE_URL : '../public/'; ?>images/indian_railways_logo.png" alt="Indian Railways Logo" class="logo">
        <h1 class="system-name">VARUNA</h1>
    </div>
    
    <div class="error-container">
        <h1 class="error-heading">Portal Link Expired</h1>
        <p class="error-message">
            The licensee portal access link you are trying to use has expired or is no longer valid. 
            This may happen due to security reasons or if the link has exceeded its validity period.
        </p>
        <div class="contact-info">
            <strong>What to do next?</strong><br>
            Please contact your issuing authority or administrator to request a new access link.
        </div>
    </div>
</body>
</html> 