<?php
return [
    'host'       => 'localhost',
    'rdms'       => 'mysql',
    'db'         => 'expeditions_db',
    'guest_user' => getenv('DB_GUEST_USER') ?: 'guest',
    'guest_pass' => getenv('DB_GUEST_PASS') ?: 'guest',
];
