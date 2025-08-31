<?php

return [
    'page_title' => "Journal Demo",
    'page_description' => 'A lightweight SQLite journal with Markdown support.',
	'auth_user' => 'your-username',
    'auth_pass' => 'your-password-hash',
    'timezone' => 'Europe/London',
	'db_path' => __DIR__ . '/data/journal.db',
];

// To use demo/demo as the un/pw, use this config:
    // 	'auth_user' => 'demo',
    //  'auth_pass' => '$2a$12$u/8fgF.qac86HZVm4LwMkucmg9/ic67nQqyJZN8W2c5eDVK77bbYi',