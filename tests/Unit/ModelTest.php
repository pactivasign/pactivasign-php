<?php

declare(strict_types=1);

namespace PactivaSign\Tests\Unit;

use PactivaSign\Exception\UnexpectedResponseException;
use PactivaSign\Model\Envelope;
use PactivaSign\Model\EnvelopeStatus;
use PactivaSign\Model\Model;
use PactivaSign\Model\RecipientStatus;
use PactivaSign\Tests\Support\SchemaSamples;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ModelTest extends TestCase
{
    /**
     * @return iterable<string, array{class-string<Model>, string, bool}>
     */
    public static function models(): iterable
    {
        foreach (glob(__DIR__ . '/../../src/Model/*.php') ?: [] as $file) {
            $class = 'PactivaSign\\Model\\' . basename($file, '.php');
            if (is_subclass_of($class, Model::class)) {
                $name = basename($file, '.php');
                yield "$name filled" => [$class, $name, false];
                yield "$name nulls" => [$class, $name, true];
            }
        }
    }

    /**
     * Every model hydrates from a schema-valid object, with nullable fields set and unset,
     * and every declared property is initialised.
     *
     * @param class-string<Model> $class
     */
    #[DataProvider('models')]
    public function testEveryModelHydratesFromASchemaValidObject(string $class, string $schema, bool $nulls): void
    {
        $data = (new SchemaSamples())->object($schema, $nulls);

        $model = $class::fromArray($data);

        self::assertSame($data, $model->toArray());
        foreach ((new \ReflectionClass($model))->getProperties(\ReflectionProperty::IS_PUBLIC) as $property) {
            self::assertTrue($property->isInitialized($model), "$schema::\${$property->getName()} is initialised");
            $value = $property->getValue($model);
            if ($nulls && ($data[$property->getName()] ?? null) === null) {
                self::assertNull($value);
            }
        }
    }

    public function testDatesEnumsAndNestedModelsAreTyped(): void
    {
        $data = (new SchemaSamples())->object('Envelope');
        $data['status'] = 'IN_PROGRESS';
        $data['recipients'][0]['status'] = 'SIGNED';

        $envelope = Envelope::fromArray($data);

        self::assertSame(EnvelopeStatus::InProgress, $envelope->status);
        self::assertSame('2026-10-22T10:30:00.000+00:00', $envelope->createdAt->format('Y-m-d\TH:i:s.vP'));
        self::assertSame(RecipientStatus::Signed, $envelope->recipients[0]->status);
        self::assertSame(['key' => 'value'], $envelope->metadata);
    }

    public function testUnknownEnumValuesMapToUnknownAndKeepTheRawValue(): void
    {
        $data = (new SchemaSamples())->object('Envelope');
        $data['status'] = 'ON_HOLD';

        $envelope = Envelope::fromArray($data);

        self::assertSame(EnvelopeStatus::Unknown, $envelope->status);
        self::assertSame('ON_HOLD', $envelope->toArray()['status']);
    }

    public function testUnknownFieldsAreIgnoredButKept(): void
    {
        $data = (new SchemaSamples())->object('Envelope') + ['addedInV1_3' => ['x' => 1]];

        self::assertSame(['x' => 1], Envelope::fromArray($data)->toArray()['addedInV1_3']);
    }

    public function testMissingRequiredFieldsAreReported(): void
    {
        $data = (new SchemaSamples())->object('Envelope');
        unset($data['status']);

        $this->expectException(UnexpectedResponseException::class);
        $this->expectExceptionMessage('Envelope is missing "status"');
        Envelope::fromArray($data);
    }

    public function testWrongTypesAreReported(): void
    {
        $data = (new SchemaSamples())->object('Envelope');
        $data['sequential'] = 'yes';

        $this->expectException(UnexpectedResponseException::class);
        $this->expectExceptionMessage('"sequential" should be a boolean');
        Envelope::fromArray($data);
    }

    public function testModelsSerializeBackToTheApiJson(): void
    {
        $data = (new SchemaSamples())->object('Envelope');

        self::assertSame(json_encode($data), json_encode(Envelope::fromArray($data)));
    }
}
