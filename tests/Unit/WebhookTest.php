<?php

declare(strict_types=1);

namespace PactivaSign\Tests\Unit;

use PactivaSign\Exception\ConfigurationException;
use PactivaSign\Exception\SignatureVerificationException;
use PactivaSign\Model\EnvelopeStatus;
use PactivaSign\Model\WebhookEventType;
use PactivaSign\Webhook\Webhook;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * The fixtures were signed by the API's own signatureHeader(), so these tests prove the SDK accepts
 * exactly what the server sends.
 */
final class WebhookTest extends TestCase
{
    /**
     * @return array{tolerance: int, cases: list<array{name: string, body: string, secrets: list<string>, timestamp: int, header: string}>}
     */
    private static function fixtures(): array
    {
        $fixtures = json_decode((string) file_get_contents(__DIR__ . '/../Fixtures/webhook-signatures.json'), true, 512, JSON_THROW_ON_ERROR);
        self::assertIsArray($fixtures);

        /** @var array{tolerance: int, cases: list<array{name: string, body: string, secrets: list<string>, timestamp: int, header: string}>} $fixtures */
        return $fixtures;
    }

    /**
     * @return iterable<string, array{array{name: string, body: string, secrets: list<string>, timestamp: int, header: string}, string}>
     */
    public static function serverSignedDeliveries(): iterable
    {
        foreach (self::fixtures()['cases'] as $case) {
            foreach ($case['secrets'] as $secret) {
                yield $case['name'] . ' / ' . $secret => [$case, $secret];
            }
        }
    }

    /**
     * @param array{name: string, body: string, secrets: list<string>, timestamp: int, header: string} $case
     */
    #[DataProvider('serverSignedDeliveries')]
    public function testAcceptsDeliveriesSignedByTheServerWithAnyActiveSecret(array $case, string $secret): void
    {
        $event = Webhook::verify($case['body'], $case['header'], $secret, now: $case['timestamp'] + 10);

        self::assertStringStartsWith('evt_', $event->id);
    }

    public function testReturnsATypedEventAndKeepsUnknownFields(): void
    {
        $case = self::fixtures()['cases'][0];

        $event = Webhook::verify($case['body'], $case['header'], ['whsec_wrong', 'whsec_current'], now: $case['timestamp']);

        self::assertSame(WebhookEventType::EnvelopeCompleted, $event->type);
        self::assertSame(EnvelopeStatus::Completed, $event->data->status);
        self::assertSame(7, $event->data->version);
        self::assertSame('deal-42', $event->data->externalId);
        self::assertSame(['crm' => 'hubspot'], $event->data->metadata);
        self::assertNull($event->data->recipient);
        self::assertSame('2026-10-22T12:00:00+00:00', $event->occurredAt->format(DATE_ATOM));
        self::assertTrue($event->toArray()['data']['futureField']);
    }

    public function testRejectsATamperedBody(): void
    {
        $case = self::fixtures()['cases'][0];

        $this->expectException(SignatureVerificationException::class);
        Webhook::verify(str_replace('"version":7', '"version":8', $case['body']), $case['header'], 'whsec_current', now: $case['timestamp']);
    }

    public function testRejectsTheWrongSecret(): void
    {
        $case = self::fixtures()['cases'][0];

        $this->expectException(SignatureVerificationException::class);
        $this->expectExceptionMessage('No webhook signature matches');
        Webhook::verify($case['body'], $case['header'], 'whsec_other', now: $case['timestamp']);
    }

    public function testRejectsOldAndFutureTimestamps(): void
    {
        $case = self::fixtures()['cases'][0];

        foreach ([$case['timestamp'] + 301, $case['timestamp'] - 301] as $now) {
            try {
                Webhook::verify($case['body'], $case['header'], 'whsec_current', now: $now);
                self::fail('expected SignatureVerificationException');
            } catch (SignatureVerificationException $e) {
                self::assertStringContainsString('tolerance', $e->getMessage());
            }
        }
        Webhook::verify($case['body'], $case['header'], 'whsec_current', now: $case['timestamp'] + 300);
        $this->addToAssertionCount(1);
    }

    public function testRejectsMissingOrMalformedHeaders(): void
    {
        $case = self::fixtures()['cases'][0];

        foreach (['', 't=abc,v1=00', 'v1=' . str_repeat('0', 64), 't=' . $case['timestamp']] as $header) {
            try {
                Webhook::verify($case['body'], $header, 'whsec_current', now: $case['timestamp']);
                self::fail("expected SignatureVerificationException for '$header'");
            } catch (SignatureVerificationException) {
                $this->addToAssertionCount(1);
            }
        }
    }

    public function testRefusesAnEmptySecretList(): void
    {
        $this->expectException(ConfigurationException::class);
        Webhook::verify('{}', 't=1,v1=00', ['']);
    }

    public function testSignatureHeaderMatchesTheServerFormat(): void
    {
        foreach (self::fixtures()['cases'] as $case) {
            self::assertSame($case['header'], Webhook::signatureHeader($case['body'], $case['secrets'], $case['timestamp']));
        }
    }

    public function testUnknownEventTypesStillParse(): void
    {
        $body = json_encode([
            'id' => 'evt_new',
            'type' => 'envelope.archived',
            'occurredAt' => '2027-01-01T00:00:00Z',
            'organizationId' => 'org',
            'data' => ['envelopeId' => 'e', 'version' => 1, 'status' => 'ARCHIVED', 'externalId' => null, 'metadata' => []],
        ], JSON_THROW_ON_ERROR);

        $event = Webhook::verify($body, Webhook::signatureHeader($body, 's', 100), 's', now: 100);

        self::assertSame(WebhookEventType::Unknown, $event->type);
        self::assertSame(EnvelopeStatus::Unknown, $event->data->status);
        self::assertSame('envelope.archived', $event->toArray()['type']);
    }
}
