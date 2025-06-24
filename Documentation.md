VARUNA System - Comprehensive Documentation
This document provides a detailed overview of the VARUNA system's architecture, file structure, and the logic behind each component.

1. High-Level Architecture
The VARUNA system is a modern PHP application that follows the Model-View-Controller (MVC) principles and a front-controller pattern.

Front Controller (public/index.php): All web traffic is directed to this single entry point. It acts as a router, deciding which part of the application should handle the request based on the URL.

Security First: The application's core logic, configuration, and libraries are stored outside the public web root (public/), preventing direct web access to sensitive files.

API-Driven Frontend: The user interface is highly interactive, relying on JavaScript (using jQuery and DataTables) to make AJAX calls to a structured set of backend APIs to fetch and submit data without reloading the page.

Role-Based Access Control (RBAC): Access to pages and data is controlled by user roles (ADMIN, SCI, VIEWER) which are checked in the router, controllers, and API endpoints.

2. Root Directory Structure
The varuna/ directory contains the main application folders and configuration files.

varuna/
├── .htaccess
├── composer.json
├── config.php
├── logs/
│   └── error.log
├── public/
├── src/
├── vendor/
└── searchvendor.php (proxy file)

.htaccess: This file (in the root) is kept empty. The server is configured to point the domain directly to the /public directory, making this file unnecessary.

composer.json: Defines the project's single PHP dependency: chillerlan/php-qrcode, used for generating QR codes on ID cards.

config.php: Contains critical configuration constants:

BASE_URL: The absolute base URL of the application, used for all links and assets.

DB_HOST, DB_USER, DB_PASS, DB_NAME: Credentials for the database connection.

logs/: A directory for storing application logs.

error.log: All PHP errors and warnings are logged to this file, as configured in src/init.php.

public/: The web server's document root. This is the only directory directly accessible from the internet.

src/: The main application source code directory.

vendor/: Managed by Composer, this directory contains the php-qrcode library and the Composer autoloader.

searchvendor.php: A special proxy file placed in the project root (public_html/varuna/) to handle requests from the Android app, which expects the API at this location. It simply forwards the request to the real API inside the /public folder.

3. /src Directory (Core Logic)
This directory houses the core application logic, separated from the public-facing files.

src/
├── core/
│   ├── Database.php
│   ├── functions.php
│   └── session.php
├── controllers/
│   ├── ContractController.php
│   ├── LicenseeController.php
│   ├── LoginController.php
│   ├── PortalController.php
│   ├── ProfileController.php
│   └── StaffController.php
├── views/
│   ├── errors/
│   │   ├── 404.php
│   │   └── invalid_link_view.php
│   ├── partials/
│   │   └── toasts.php
│   ├── add_contract_view.php
│   ├── add_licensee_view.php
│   ├── add_staff_view.php
│   ├── admin_panel_view.php
│   ├── approved_staff_view.php
│   ├── bulk_printing_view.php
│   ├── footer.php
│   ├── header.php
│   ├── id_card_admin_view.php
│   ├── login_view.php
│   ├── manage_contracts_view.php
│   ├── manage_licensees_view.php
│   ├── navbar.php
│   ├── portal_dashboard_view.php
│   ├── profile_view.php
│   ├── staff_approval_view.php
│   └── viewer_page_view.php
└── init.php

init.php: The global bootstrap file, included by almost every other file.

Logic:

Defines a PROJECT_ROOT constant for reliable file paths.

Configures PHP error logging and sets the timezone.

Loads all necessary files in the correct order: config.php, Composer's vendor/autoload.php, and all files from src/core/.

Starts the secure session.

Initializes the single database connection object ($pdo) using the Database singleton class, which prevents multiple connections and solves the max_connections_per_hour error.

Ensures a CSRF token is always available in the session.

3.1. /src/core/
Database.php: Implements the Singleton design pattern to manage the database connection. This ensures that only one PDO connection is made per request, making the application efficient and preventing server resource exhaustion.

functions.php: Contains global helper functions:

log_activity(): Logs user or system actions to the database.

process_image_upload(): A robust function for handling file uploads. It validates file type and size, renames files, compresses large images, and handles errors gracefully.

getStaffIdsForBulkPrint(): A reusable function that securely queries the database to get a list of staff IDs for the bulk printing feature.

session.php: Handles all session-related security.

start_secure_session(): Configures session cookies with secure settings.

regenerate_session(): Prevents session fixation attacks by changing the session ID after a successful login.

generate_csrf_token() & validate_csrf_token(): Manage CSRF tokens to protect all POST requests from Cross-Site Request Forgery attacks.

3.2. /src/controllers/
These files handle form submissions using the Post-Redirect-Get (PRG) pattern.

LoginController.php: Verifies user credentials, sets session variables on success, and logs login attempts.

LicenseeController.php, ContractController.php, StaffController.php: Handle the "add new" forms for their respective entities. They perform server-side validation, process file uploads where necessary, insert data into the database, and redirect back with a success or error message.

ProfileController.php: Handles the signature upload on a user's profile page.

PortalController.php: The secure gatekeeper for the licensee portal. It validates the access token from the URL, checks if it's active and not expired, and then creates a limited "guest" session for the licensee before loading the portal view.

3.3. /src/views/
These files are responsible for rendering the HTML structure of the application.

Partials (header.php, footer.php, navbar.php): Reusable HTML components included on most pages to maintain a consistent look and feel. header.php includes all CSS/JS assets, and footer.php contains the hidden modal structures. navbar.php dynamically shows/hides links based on the user's role.

Error Views (errors/): Contains user-friendly pages for 404 Not Found errors and for invalid links to the licensee portal.

Main Views (add_*.php, manage_*.php, etc.): These files contain the primary HTML structure for each page. They often include PHP loops to populate dropdown menus and check for session messages to display alerts (via the toasts.php partial).

4. /public Directory (Web Root)
This is the only folder accessible to the outside world.

public/
├── .htaccess
├── api/
│   ├── admin/
│   ├── portal/
│   └── (numerous .php files)
├── css/
│   ├── id_card_style.css
│   └── style.css
├── images/
├── js/
│   └── pages/
├── libs/
├── uploads/
│   ├── authority/
│   ├── contracts/
│   └── staff/
├── bulk_id_page.php
├── dashboard.php
├── id_card.php
├── id_card_preview.php
├── index.php
└── logout.php

.htaccess: The most important file for routing. It uses mod_rewrite to redirect all requests for non-existent files and folders to index.php, enabling the use of clean URLs (e.g., /dashboard).

index.php: The Front Controller. It receives the clean URL, determines which page the user is requesting, checks permissions, and includes the appropriate controller and/or view file to render the page.

Page Scripts (dashboard.php, id_card.php, etc.): These are the main page files included by the index.php router. id_card.php is a powerful script that generates a complete, printable ID card by fetching all required data and styles.

logout.php: Securely terminates the user's session and redirects to the login page.

4.1. /public/api/
Contains all backend API endpoints. They handle data requests from the frontend JavaScript.

get_*.php files: These scripts primarily handle GET requests to fetch data for DataTables, dropdowns, and modals. They are all role-aware, filtering data based on the logged-in user's session.

get_dashboard_data.php: An aggregate API that fetches all stats, chart data, and tables for the main dashboard.

get_viewer_staff.php: A server-side processing API for the "Master View" DataTable, supporting filtering and pagination.

get_all_staff_for_export.php: A special API that fetches a complete, unfiltered dataset for PDF export. It processes images on the server, converting them to base64 data to ensure the PDF generation is reliable.

update_*.php / add_*.php / delete_*.php / terminate_*.php files: These scripts handle POST requests to modify data. They are all protected by CSRF token validation. The termination scripts include the cascading logic to update the status of related records (e.g., terminating a licensee also terminates their contracts and staff).

/admin/: Contains APIs for administrative tasks (managing users, designations, etc.).

/portal/: Contains a separate set of APIs secured specifically for the licensee portal, ensuring licensees can only access and modify their own data.

searchvendor.php: The special API that responds to QR code scans from the Android app, rendering a mobile-friendly verification page.

4.2. Asset Directories
/css/: Contains the main application stylesheet (style.css) and the specialized stylesheet for ID cards (id_card_style.css).

/js/pages/: Contains a separate JavaScript file for each major page or feature (e.g., dashboard.js, viewer.js, staffApproval.js). This keeps the frontend logic organized and modular.

/libs/: Contains third-party JavaScript libraries like DataTables, SweetAlert2, and Select2.

/uploads/: The destination for all user-uploaded files, separated into subdirectories for staff, contracts, and authority signatures.