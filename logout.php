<?php
/**
 * EchoDoc - Logout Handler
 */

require_once 'includes/auth.php';

// Clear user session
clearUserSession();

// Redirect to home page
redirectWithSessionFlush('index.php?logged_out=1');
