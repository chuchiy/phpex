<?php
$loader = require __DIR__.'/../vendor/autoload.php';
$loader->add('', __DIR__);
$pex = \Pex\HttpTest\PexServer::setup();
$pex->serve();
