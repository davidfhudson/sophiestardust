<?php
// Sophie's Stardust — Server Configuration
//
// HOW TO USE:
//   1. Copy this file to api/config.php  (it is gitignored — never commit config.php)
//   2. Fill in your 123reg MySQL credentials below
//   3. Upload api/config.php to the server via FTP or the 123reg file manager
//   4. config.php will NOT be overwritten by future GitHub deployments

define('DB_HOST', 'localhost');      // usually localhost on 123reg shared hosting
define('DB_NAME', 'your_db_name');   // the database name from your 123reg control panel
define('DB_USER', 'your_db_user');   // database username
define('DB_PASS', 'your_db_pass');   // database password

// Admin password for the quiz editor (checked server-side on every save)
define('ADMIN_PASSWORD', 'stardust');  // change this before going live
