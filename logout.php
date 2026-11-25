<?php
require_once 'includes/functions.php';

// Remove all session data, then start a fresh session just to show a message
session_destroy();
session_start();
set_flash('success', 'You have been logged out.');
redirect('index.php');
