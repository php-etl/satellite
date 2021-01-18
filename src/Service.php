<?php declare(strict_types=1);

namespace Kiboko\Component\Satellite;

use Kiboko\Component\Config\ArrayBuilder;
use Kiboko\Plugin\CSV;
use Kiboko\Component\Flow\Akeneo;
use Kiboko\Component\Flow\FastMap;
use Kiboko\Contract\Configurator\ConfigurationExceptionInterface;
use Kiboko\Contract\Configurator\FactoryInterface;
use Kiboko\Contract\Configurator\InvalidConfigurationException;
use PhpParser\Node;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\Definition\Exception as Symfony;
use Symfony\Component\Config\Definition\Processor;

final class Service implements FactoryInterface
{
    private Processor $processor;
    private ConfigurationInterface $configuration;

    public function __construct()
    {
        $this->processor = new Processor();
        $this->configuration = new Configuration();
    }

    public function configuration(): ConfigurationInterface
    {
        return $this->configuration;
    }

    /**
     * @throws ConfigurationExceptionInterface
     */
    public function normalize(array $config): array
    {
        try {
            return $this->processor->processConfiguration($this->configuration, $config);
        } catch (Symfony\InvalidTypeException|Symfony\InvalidConfigurationException $exception) {
            throw new InvalidConfigurationException($exception->getMessage(), 0, $exception);
        }
    }

    public function validate(array $config): bool
    {
        try {
            $this->processor->processConfiguration($this->configuration, $config);

            return true;
        } catch (Symfony\InvalidTypeException|Symfony\InvalidConfigurationException $exception) {
            return false;
        }
    }

    /**
     * @throws ConfigurationExceptionInterface
     */
    public function compile(array $config): \PhpParser\Builder
    {
        $pipeline = new Builder\Pipeline();

        foreach ($config['runtime']['steps'] as $step) {
            if (array_key_exists('akeneo', $step)) {
                if (array_key_exists('extractor', $step['akeneo'])) {
                    $pipeline->addExtractor(
                        (new Akeneo\Service())->compile($step['akeneo'])->getNode()
                    );
                } else if (array_key_exists('loader', $step['akeneo'])) {
                    $pipeline->addLoader(
                        (new Akeneo\Service())->compile($step['akeneo'])->getNode()
                    );
                }
            } else if (array_key_exists('csv', $step)) {
                if (array_key_exists('extractor', $step['csv'])) {
                    $pipeline->addExtractor(
                        (new CSV\Service())->compile($step['csv'])->getNode()
                    );
                } else if (array_key_exists('loader', $step['csv'])) {
                    $pipeline->addLoader(
                        (new CSV\Service())->compile($step['csv'])->getNode()
                    );
                }
            } else if (array_key_exists('fastmap', $step)) {
                $pipeline->addTransformer(
                    (new FastMap\Service())->compile($step['fastmap'])->getNode()
                );
            }
        }

        return $pipeline;
    }

    public function build(): array
    {
        return [
            new Node\Stmt\Namespace_(new Node\Name('Foo')),
            new Node\Stmt\Expression(
                new Node\Expr\Include_(
                    new Node\Expr\BinaryOp\Concat(
                        new Node\Scalar\MagicConst\Dir(),
                        new Node\Scalar\String_('/vendor/autoload.php')
                    ),
                    Node\Expr\Include_::TYPE_REQUIRE
                ),
            ),
            new Node\Stmt\Use_([new Node\Stmt\UseUse(new Node\Name('Kiboko\\Component\\Pipeline'))]),

            ...$this->buildPipeline($this->config['steps'])
        ];
    }
}
