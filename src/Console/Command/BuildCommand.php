<?php declare(strict_types=1);

namespace Kiboko\Component\Satellite\Console\Command;

use Kiboko\Component\Satellite;
use Kiboko\Plugin\Akeneo;
use Kiboko\Plugin\CSV;
use Kiboko\Plugin\FastMap;
use PhpParser;
use Psr\Log;
use Symfony\Component\Config;
use Symfony\Component\Console;
use Symfony\Component\Yaml;

final class BuildCommand extends Console\Command\Command
{
    protected static $defaultName = 'build';

    protected function configure()
    {
        $this->setDescription('Build the satellite docker image.');
        $this->addArgument('config', Console\Input\InputArgument::REQUIRED);
//        $this->addArgument('output', Console\Input\InputArgument::REQUIRED);
//        $this->addArgument('image-name', Console\Input\InputArgument::REQUIRED);
    }

    protected function execute(Console\Input\InputInterface $input, Console\Output\OutputInterface $output)
    {
        $factory = new Satellite\Service();

        $style = new Console\Style\SymfonyStyle(
            $input,
            $output,
        );

        $filename = $input->getArgument('config');
        if ($filename === null) {
            $filename = getcwd() . '/satellite.yaml';
        }
        $configuration = Yaml\Yaml::parse(input: file_get_contents($filename));

        try {
            $configuration = $factory->normalize($configuration);
        } catch (Config\Definition\Exception\InvalidTypeException|Config\Definition\Exception\InvalidConfigurationException $exception) {
            $style->error($exception->getMessage());
            return 255;
        }

        \chdir(\dirname($filename));

        $factory = new Satellite\Factory();

        $satellite = $factory($configuration)->build();

        if (array_key_exists('pipeline', $configuration)) {
            $runtime = new Satellite\Runtime\Pipeline($configuration);
        } else if (array_key_exists('http_api', $configuration)) {
            $runtime = new Satellite\Runtime\Http\Api($configuration);
        } else if (array_key_exists('http_hook', $configuration)) {
            $runtime = new Satellite\Runtime\Http\Hook($configuration);
        } else {
            throw new \RuntimeException('No matching runtime type was found.');
        }

        $satellite->withFile(
            new Satellite\File('function.php', new Satellite\Asset\InMemory(
                '<?php' . PHP_EOL . (new PhpParser\PrettyPrinter\Standard())->prettyPrint($runtime->build())
            )),
        );

        $logger = new class extends Log\AbstractLogger {
            public function log($level, $message, array $context = array())
            {
                $prefix = sprintf(PHP_EOL . "[%s] ", strtoupper($level));
                fwrite(STDERR, $prefix . str_replace(PHP_EOL, $prefix, rtrim($message, PHP_EOL)));
            }
        };

        $satellite->build($logger);

        return 0;
    }
}
