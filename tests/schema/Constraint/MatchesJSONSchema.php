<?php declare(strict_types=1);

namespace schema\Kiboko\Component\Satellite\Constraint;

use JsonSchema\Constraints\Factory;
use JsonSchema\Exception as SchemaException;
use JsonSchema\SchemaStorageInterface;
use JsonSchema\Validator;
use PHPUnit\Framework\Constraint\Constraint;

final class MatchesJSONSchema extends Constraint
{
    private array $messages = [];

    public function __construct(
        private readonly SchemaStorageInterface $schemaStorage,
        private \stdClass|array $schema,
    ) {}

    protected function matches($other): bool
    {
        try {
            $validator = new Validator(new Factory($this->schemaStorage));
            $validator->validate($other, $this->schema);
        } catch (SchemaException\InvalidSchemaMediaTypeException $exception) {
            throw new \RuntimeException(\strtr('An %exception% exception occurred: %message%', [
                '%exception%' => \substr(\get_debug_type($exception), false !== ($pos = strrpos(get_debug_type($exception), '\\')) ? $pos + 1 : 0),
                '%message%' => $exception->getMessage(),
            ]), 0, $exception);
        } catch (SchemaException\ExceptionInterface $exception) {
            $this->messages[] = \strtr('An %exception% exception occurred: %message%', [
                '%exception%' => \substr(\get_debug_type($exception), false !== ($pos = strrpos(get_debug_type($exception), '\\')) ? $pos + 1 : 0),
                '%message%' => $exception->getMessage(),
            ]);
            return false;
        }

        $message = '- Property: %s, Constraint: %s, Message: %s';
        $this->messages = array_map(
            fn (array $exception) =>
                sprintf($message, $exception['property'], $exception['constraint'], $exception['message']),
            $validator->getErrors(),
        );

        return $validator->isValid();
    }

    protected function additionalFailureDescription($other): string
    {
        return implode(PHP_EOL, $this->messages);
    }

    public function toString(): string
    {
        return 'matches JSON schema';
    }
}
