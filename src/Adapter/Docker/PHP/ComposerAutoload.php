<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite\Adapter\Docker\PHP;

use Kiboko\Component\Satellite\Adapter\Docker\Dockerfile;

final class ComposerAutoload implements Dockerfile\LayerInterface
{
    /**
     * @param array<string, array<string, string|list<string>>> $autoloads
     */
    private array $autoloads;

    public function __construct(array $autoloads)
    {
        $this->autoloads = $autoloads;
    }

    private static function command(string ...$command): string
    {
        return implode(' ', array_map(fn ($argument) => self::escapeArgument($argument), $command));
    }

    /**
     * Escapes a string to be used as a shell argument.
     */
    private static function escapeArgument(?string $argument): string
    {
        if ('' === $argument || null === $argument) {
            return '""';
        }
        if ('\\' !== \DIRECTORY_SEPARATOR) {
            return "'".str_replace("'", "'\\''", $argument)."'";
        }
        if (str_contains($argument, "\0")) {
            $argument = str_replace("\0", '?', $argument);
        }
        if (!preg_match('/[\/()%!^"<>&|\s]/', $argument)) {
            return $argument;
        }
        $argument = preg_replace('/(\\\\+)$/', '$1$1', $argument);

        return '"'.str_replace(['"', '^', '%', '!', "\n"], ['""', '"^^"', '"^%"', '"^!"', '!LF!'], $argument).'"';
    }

    private static function pipe(string ...$commands): string
    {
        return implode(' | ', $commands);
    }

    public function __toString()
    {
        $commands = implode(' \\' . PHP_EOL . '    && ', array_map(fn ($type, $autoload) => match ($type) {
            'psr4' => self::pipe(
                self::command('cat', 'composer.json'),
                self::command('jq', '--indent', '4', sprintf('.autoload."psr-4" |= . + %s', json_encode($autoload))),
                self::command('tee', 'composer.json')
            )
        }, array_keys($this->autoloads), array_values($this->autoloads)));

        $d = $commands;
        return (string) new Dockerfile\Run(
            <<<RUN
            set -ex \\
                && {$commands}
            RUN
        );
    }
}
