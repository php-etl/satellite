<?php

require __DIR__ . '/vendor/autoload.php';

$context = new \ZMQContext();
spl_autoload_register(function ($class) {
    $prefix = 'Kiboko\\Component\\ETL\\Satellite\\';

    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }
    $relativeClass = substr($class, $len);
    $file = __DIR__ . '/library/' . str_replace('\\', '/', $relativeClass) . '.php';

    if (file_exists($file)) {
        require $file;
    }
});

try {
    $consumer = new Kiboko\Component\ETL\Satellite\ZMQ\Consumer($argv[1], $argv[2]);
    file_put_contents('php://stdout', 'Consumer started.'.PHP_EOL);
} catch (\RuntimeException $exception) {
    file_put_contents('php://stderr', $exception->getMessage());
}

//  Process tasks forever
while (true) {
    file_put_contents('php://stdout', 'Consumer waiting for a message.'.PHP_EOL);
    $message = $consumer->receive();
    file_put_contents('php://stdout', 'Consumer received a message.'.PHP_EOL);

    $function = require __DIR__ . '/function.php';
    $result = $function($message);

    usleep(1000000);

    $consumer->send(new Kiboko\Component\ETL\Satellite\ZMQ\Consumer\Response($message, $result));
    file_put_contents('php://stdout', 'Consumer sent a message.'.PHP_EOL);
}