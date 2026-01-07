<?php
/**
 * EchoDoc - Logout Handler
 */

require_once 'includes/auth.php';

// Clear user session
clearUserSession();

// Redirect to home page
header('Location: index.php?logged_out=1');
exit;
