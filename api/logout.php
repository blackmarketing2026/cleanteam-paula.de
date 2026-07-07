<?php

require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth.php';

require_method('POST');

logout_user();
json_response(['ok' => true]);
