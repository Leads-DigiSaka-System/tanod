<?php

use Symfony\Component\Console\Input\ArrayInput;

$app = require __DIR__.'/bootstrap.php';

exit($app->handleCommand(new ArrayInput([
    'command' => 'jimi:sync-service-area',
    '--limit' => 3,
    '--no-interaction' => true,
])));
