<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite\Console\Command\Cloud;

use Gyroscops\Api\Client;
use Kiboko\Component\Satellite;
use Psr\Log;
use Symfony\Component\Config\Exception\LoaderLoadException;
use Symfony\Component\Config;
use Symfony\Component\Console;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpClient\Psr18Client;

final class RemoveCommand extends Console\Command\Command
{
    protected static $defaultName = 'remove';

    protected function configure(): void
    {
        $this->setDescription('Removes a part of configuration.');
        $this->addArgument('config', Console\Input\InputArgument::REQUIRED);
    }

    protected function execute(Console\Input\InputInterface $input, Console\Output\OutputInterface $output): int
    {
        $style = new Console\Style\SymfonyStyle(
            $input,
            $output,
        );

        $filename = $input->getArgument('config');
        if ($filename !== null) {
            $configuration = (new Satellite\ConfigLoader(getcwd()))->loadFile($filename);
        } else {
            $possibleFiles = ['satellite.yaml', 'satellite.yml', 'satellite.json'];

            foreach ($possibleFiles as $filename) {
                try {
                    $configuration = (new Satellite\ConfigLoader(getcwd()))->loadFile($filename);
                    break;
                } catch (LoaderLoadException) {
                }
            }

            if (!isset($configuration)) {
                throw new \RuntimeException('Could not find configuration file.');
            }
        }

        $directory = getcwd();
        if (file_exists($directory . '/.gyro.php')) {
            $service = require $directory . '/.gyro.php';
        } else {
            $service = new Satellite\Service();
        }

        try {
            $configuration = $service->normalize($configuration);
        } catch (Config\Definition\Exception\InvalidTypeException | Config\Definition\Exception\InvalidConfigurationException $exception) {
            $style->error($exception->getMessage());
            return 255;
        }

        $token = json_decode(file_get_contents(getcwd() . '/.gyroscops/auth.json'), true)["token"];
        $httpClient = HttpClient::createForBaseUri(
            $configuration["cloud"]["url"],
            [
                'verify_peer' => false,
                'auth_bearer' => $token
            ]
        );

        $psr18Client = new Psr18Client($httpClient);
        $client = Client::create($psr18Client);

        $factory = new Satellite\Adapter\Cloud\Factory($client);
        $factory->remove($configuration["satellite"]);

//        if ($response->getStatusCode() === 200) {
//            $style->error('The satellite configuration cannot be sent.');
//            return Console\Command\Command::FAILURE;
//        }

        $style->success('The satellite configuration has been sent correctly.');

        return Console\Command\Command::SUCCESS;
    }
}
