<?php

ini_set('display_errors', 0);

use Bolt\Site\SlackInvites\Bootstrap;

require_once __DIR__.'/../vendor/autoload.php';

$app = Bootstrap::run(false);
$app->run();
