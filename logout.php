<?php
require_once __DIR__ . '/includes/security.php';
gloy_destroy_session();
header("Location: index.php");
exit;
