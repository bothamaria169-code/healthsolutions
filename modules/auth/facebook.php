<?php
declare(strict_types=1);
// Facebook sign-in: the button sends shoppers here and Facebook sends them back here.
require_once __DIR__ . '/../../includes/functions.php';
oauth_handle('facebook');
