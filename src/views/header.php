<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?php echo generate_csrf_token(); ?>">
    <title>VARUNA System</title>
    
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>css/style.css">
    
    <link rel="stylesheet" type="text/css" href="<?php echo BASE_URL; ?>libs/datatables/datatables.min.css"/>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>libs/sweetalert2/sweetalert2.min.css">
    <link href="<?php echo BASE_URL; ?>libs/select2/select2.min.css" rel="stylesheet" />
    
    <script src="<?php echo BASE_URL; ?>js/jquery-3.7.1.min.js"></script>
    <script type="text/javascript" src="<?php echo BASE_URL; ?>libs/datatables/datatables.min.js"></script>
    <script src="<?php echo BASE_URL; ?>libs/sweetalert2/sweetalert2.min.js"></script>
    <script src="<?php echo BASE_URL; ?>libs/select2/select2.min.js"></script>
    
    <script> const BASE_URL = "<?php echo BASE_URL; ?>"; </script>
    <script src="<?php echo BASE_URL; ?>js/navbar.js" defer></script>
</head>
<body>

    <header class="main-header">
        <div class="logo-container">
            <img src="<?php echo BASE_URL; ?>images/indian_railways_logo.png" alt="Indian Railways Logo" class="logo">
        </div>
        
        <div class="system-name-container">
            <h1 class="system-name">VARUNA</h1>
            <p class="system-subtitle">Vendor Access Regulation and Unified Network Application</p>
        </div>
    </header>