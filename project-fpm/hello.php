<?php

return function (\Psr\Http\Message\ServerRequestInterface $request): \Psr\Http\Message\ResponseInterface {
    $factory = new \Nyholm\Psr7\Factory\Psr17Factory();

    $pipelineRunner = new \Kiboko\Component\Pipeline\PipelineRunner(new \Psr\Log\NullLogger());
    $pipeline = new \Kiboko\Component\Pipeline\Pipeline($pipelineRunner);

    $interpreter = new \Kiboko\Component\Satellite\ExpressionLanguage\ExpressionLanguage;

    $pipeline->feed(
        $interpreter->evaluate(
            'input["_items"]',
            json_decode($request->getBody()->getContents(), true)
        )
    );

    $pipeline->run();

    return $factory->createResponse(200)
        ->withBody(
            $factory->createStream(
                json_encode([
                    'message' => 'Hello World!',
                    'server' => gethostname(),
                ])
            )
        );
};
