<?php
// Set the HTTP response code to 404
http_response_code(404);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 Not Found - VARUNA System</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>css/style.css">
    <style>
        /* Page-specific styles for the error page */
        .error-container {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            height: 100vh;
            background-color: var(--secondary-color);
            padding: 20px;
        }
        .error-image {
            max-width: 450px;
            width: 100%;
            margin-bottom: 2rem;
        }
        .error-heading {
            font-size: 2.5rem;
            color: var(--primary-color);
            margin-bottom: 1rem;
            font-weight: 700;
        }
        .error-message {
            font-size: 1.1rem;
            color: #555;
            margin-bottom: 2.5rem;
        }
    </style>
</head>
<body>
    <div class="error-container">
        <img src="<?php echo BASE_URL; ?>images/error404.png" alt="Page Not Found" class="error-image">
        
        <h1 class="error-heading">Page Not Found</h1>
        <p class="error-message">Sorry, the page you are looking for does not exist or has been moved.</p>
        <a href="<?php echo BASE_URL; ?>dashboard" class="btn-login">Go to Dashboard</a>
    </div>
</body>
</html>