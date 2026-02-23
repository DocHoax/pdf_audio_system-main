<?php
/**
 * EchoDoc - Logout Handler
 */

require_once 'includes/auth.php';

// Clear user session
clearUserSession();

// Ensure session changes are written before redirect
session_write_close();

// Redirect to home page
header('Location: index.php?logged_out=1');
exit;
