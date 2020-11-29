<?php declare(strict_types=1);

namespace Kiboko\Component\ETL\Satellite\Console\Command;

use Kiboko\Component\ETL\Satellite\Adapter\Docker;
use Kiboko\Component\ETL\Satellite\Runtime;
use PhpParser;
use Psr\Log;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Yaml;

final class BuildCommand extends Command
{
    public static $defaultName = 'build';

    protected function configure()
    {
        $this->setDescription('Build the satellite docker image.');
        $this->addArgument('image-name', InputArgument::REQUIRED);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $configuration = Yaml::parse(file_get_contents(getcwd() . '/satellite.yaml'))['satellite'];

        $dockerfile = new Docker\Dockerfile(
            new Docker\Dockerfile\From($configuration['image']),
            new Docker\Dockerfile\Workdir('/var/www/html/'),
        );

        $satellite = new Docker\Satellite(
            $input->getArgument('image-name'),
            $dockerfile,
        );

        if (isset($configuration['include'])) {
            foreach ($configuration['include'] as $path) {
                $dockerfile->push(new Docker\Dockerfile\Copy($path, '/var/www/html/' . $path));
                $satellite->push(new Docker\File($path, new Docker\Asset\File($path)));
            }
        }

        if (isset($configuration['composer'])) {
            $dockerfile->push(
                new Docker\PHP\Composer(),
            );

            if (isset($configuration['composer']['from-local'])) {
                $dockerfile->push(
                    new Docker\Dockerfile\Copy('composer.json', '/var/www/html/composer.json'),
                    new Docker\Dockerfile\Copy('composer.lock', '/var/www/html/composer.lock'),
                    new Docker\Dockerfile\Copy('vendor/', '/var/www/html/vendor/'),
                );
                $satellite->push(
                    new Docker\File('composer.json', new Docker\Asset\File('composer.json')),
                    new Docker\File('composer.lock', new Docker\Asset\File('composer.lock')),
                    ...Docker\File::directory('vendor/'),
                );
                $dockerfile->push(new Docker\PHP\ComposerInstall());
            } else {
                $dockerfile->push(new Docker\PHP\ComposerInit());
            }


            if (isset($configuration['composer']['require'])) {
                $dockerfile->push(new Docker\PHP\ComposerRequire(...$configuration['composer']['require']));
            }
        }

        if ($configuration['runtime']['type'] === 'cli') {
            $runtime = new Runtime\Cli();
        } else if ($configuration['runtime']['type'] === 'api') {
            $runtime = new Runtime\Http\Api();
        } else if ($configuration['runtime']['type'] === 'http-hook') {
            $runtime = new Runtime\Http\Hook();
        }

        $satellite->push(
            new Docker\File('index.php', new Docker\Asset\InMemory(
                '<?php' . PHP_EOL . (new PhpParser\PrettyPrinter\Standard())->prettyPrint($runtime->build())
            ))
        );

        $dockerfile->push(
            new Docker\Dockerfile\Copy('index.php','/var/www/html/index.php')
        );

        $logger = new class implements Log\LoggerInterface {
            public function emergency($message, array $context = array())
            {
                $this->log(Log\LogLevel::EMERGENCY, $message, $context);
            }

            public function alert($message, array $context = array())
            {
                $this->log(Log\LogLevel::ALERT, $message, $context);
            }

            public function critical($message, array $context = array())
            {
                $this->log(Log\LogLevel::CRITICAL, $message, $context);
            }

            public function error($message, array $context = array())
            {
                $this->log(Log\LogLevel::ERROR, $message, $context);
            }

            public function warning($message, array $context = array())
            {
                $this->log(Log\LogLevel::WARNING, $message, $context);
            }

            public function notice($message, array $context = array())
            {
                $this->log(Log\LogLevel::NOTICE, $message, $context);
            }

            public function info($message, array $context = array())
            {
                $this->log(Log\LogLevel::INFO, $message, $context);
            }

            public function debug($message, array $context = array())
            {
                $this->log(Log\LogLevel::DEBUG, $message, $context);
            }

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
