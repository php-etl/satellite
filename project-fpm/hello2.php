<?php

return function (\Psr\Http\Message\ServerRequestInterface $request): \Psr\Http\Message\ResponseInterface {
    $factory = new \Nyholm\Psr7\Factory\Psr17Factory();

    return $factory->createResponse(200)
        ->withBody(
            $factory->createStream(
                json_encode([
                    'message' => 'Hello World DEUX le RETOUR!',
                    'server' => gethostname(),
                ])
            )
        );
};
