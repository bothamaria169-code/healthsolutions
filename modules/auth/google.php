<?php
declare(strict_types=1);
// Google sign-in: the button sends shoppers here and Google sends them back here.
require_once __DIR__ . '/../../includes/functions.php';
oauth_handle('google');
