<?php
// Database settings (Laravel Herd defaults). Change them if yours are different.
define('DB_HOST', '127.0.0.1');
define('DB_NAME', 'blood_donations');
define('DB_USER', 'root');
define('DB_PASS', 'admin');

define('SITE_NAME', 'Blood Care');

// Donor must wait this many days between donations
define('DONATION_GAP_DAYS', 90);

// Status numbers (same numbers as the database comments)
define('REQ_PENDING', 1);
define('REQ_ONGOING', 2);
define('REQ_ACCEPTED', 3);
define('REQ_COMPLETED', 4);
define('REQ_CANCELLED', 5);

define('TX_ACCEPTED', 1);
define('TX_CANCELLED', 2);
define('TX_COMPLETED', 3);

define('USER_ACTIVE', 1);
define('USER_UNAVAILABLE', 2);
define('USER_INACTIVE', 3);
