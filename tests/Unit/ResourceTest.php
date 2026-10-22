<?php

declare(strict_types=1);

namespace PactivaSign\Tests\Unit;

use Nyholm\Psr7\Factory\Psr17Factory;
use PactivaSign\Client;
use PactivaSign\Model\Envelope;
use PactivaSign\Model\EnvelopeStatus;
use PactivaSign\Model\Me;
use PactivaSign\Key\LocalKey;
use PactivaSign\Tests\Support\FrozenClock;
use PactivaSign\Tests\Support\SchemaSamples;
use PactivaSign\Tests\Support\ScriptedHttpClient as Http;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;

final class ResourceTest extends TestCase
{
    private Http $http;
    private SchemaSamples $samples;

    protected function setUp(): void
    {
        $this->http = (new Http())->then(Http::token());
        $this->samples = new SchemaSamples();
    }

    public function testCreateEnvelopeSendsJsonWithAGeneratedIdempotencyKey(): void
    {
        $this->http->then(Http::response(201, ['success' => true, 'data' => $this->samples->object('Envelope')]));

        $envelope = $this->client()->envelopes->create([
            'name' => 'NDA',
            'documentIds' => ['doc-1'],
            'signers' => [['email' => 'jo@example.com', 'name' => 'Jo']],
            'expiresAt' => '2026-11-01T12:00:00Z',
        ]);

        self::assertInstanceOf(Envelope::class, $envelope);
        [$call] = $this->http->sentTo('/api/v1/envelopes');
        self::assertSame('POST', $call['request']->getMethod());
        self::assertSame('application/json', $call['request']->getHeaderLine('Content-Type'));
        self::assertMatchesRegularExpression('/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/', $call['request']->getHeaderLine('Idempotency-Key'));
        self::assertSame([
            'name' => 'NDA',
            'documentIds' => ['doc-1'],
            'signers' => [['email' => 'jo@example.com', 'name' => 'Jo']],
            'expiresAt' => '2026-11-01T12:00:00Z',
        ], json_decode($call['body'], true));
    }

    public function testACallerSuppliedIdempotencyKeyIsUsed(): void
    {
        $this->http->then(Http::response(201, ['success' => true, 'data' => $this->samples->object('Envelope')]));

        $this->client()->envelopes->create(['name' => 'NDA', 'signers' => []], idempotencyKey: 'order-42-nda');

        self::assertSame('order-42-nda', $this->http->sentTo('/api/v1/envelopes')[0]['request']->getHeaderLine('Idempotency-Key'));
    }

    public function testListFollowsCursorsLazily(): void
    {
        $first = $this->samples->object('Envelope');
        $second = ['id' => 'env-2'] + $first;
        $third = ['id' => 'env-3'] + $first;
        $this->http
            ->then(Http::response(200, ['success' => true, 'data' => [$first, $second], 'nextCursor' => 'c2']))
            ->then(Http::response(200, ['success' => true, 'data' => [$third], 'nextCursor' => null]));

        $list = $this->client()->envelopes->list(['status' => EnvelopeStatus::Sent, 'limit' => 2, 'createdFrom' => new \DateTimeImmutable('2026-10-01T00:00:00Z')]);
        self::assertCount(0, $this->http->sent, 'nothing is requested until iteration');

        $ids = array_map(static fn (Envelope $e) => $e->id, $list->all());

        self::assertSame([$first['id'], 'env-2', 'env-3'], $ids);
        $calls = $this->http->sentTo('/api/v1/envelopes');
        parse_str($calls[0]['request']->getUri()->getQuery(), $q1);
        parse_str($calls[1]['request']->getUri()->getQuery(), $q2);
        self::assertSame(['status' => 'SENT', 'limit' => '2', 'createdFrom' => '2026-10-01T00:00:00.000+00:00'], $q1);
        self::assertSame(['cursor' => 'c2', 'status' => 'SENT', 'limit' => '2', 'createdFrom' => '2026-10-01T00:00:00.000+00:00'], $q2);
    }

    public function testFirstPageFetchesOnePage(): void
    {
        $this->http->then(Http::response(200, ['success' => true, 'data' => [$this->samples->object('Envelope')], 'nextCursor' => 'next']));

        $page = $this->client()->envelopes->list()->firstPage();

        self::assertCount(1, $page);
        self::assertTrue($page->hasMore());
        self::assertSame('next', $page->nextCursor);
    }

    public function testPathParametersAreEncoded(): void
    {
        $this->http->then(Http::response(200, ['success' => true, 'data' => $this->samples->object('Envelope')]));

        $this->client()->envelopes->get('a/b?c');

        self::assertSame('/api/v1/envelopes/a%2Fb%3Fc', $this->http->sent[1]['request']->getUri()->getPath());
    }

    public function testEmptyPathParametersAreRefused(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->client()->envelopes->get('');
    }

    public function testDownloadsStreamThePdfAndHonourEtags(): void
    {
        $this->http
            ->then(Http::response(200, '%PDF-1.7 signed', ['Content-Type' => 'application/pdf', 'ETag' => '"abc"', 'Content-Disposition' => 'attachment; filename="NDA signed.pdf"']))
            ->then(Http::response(304, null, ['ETag' => '"abc"']));
        $client = $this->client();

        $download = $client->envelopes->downloadSigned('env-1');
        $path = (string) tempnam(sys_get_temp_dir(), 'pdf');
        $bytes = $download->saveTo($path);
        $again = $client->envelopes->downloadSigned('env-1', ifNoneMatch: (string) $download->etag());

        self::assertSame('%PDF-1.7 signed', file_get_contents($path));
        self::assertSame(15, $bytes);
        self::assertSame('NDA signed.pdf', $download->filename());
        self::assertFalse($download->isNotModified());
        self::assertTrue($again->isNotModified());
        $calls = $this->http->sentTo('/combined');
        self::assertSame('application/pdf', $calls[0]['request']->getHeaderLine('Accept'));
        self::assertFalse($calls[0]['request']->hasHeader('If-None-Match'));
        self::assertSame('"abc"', $calls[1]['request']->getHeaderLine('If-None-Match'));
        unlink($path);
    }

    public function testOptionalQueryParametersAreOnlySentWhenGiven(): void
    {
        $this->http->then(Http::response(200, '%PDF', ['Content-Type' => 'application/pdf']))->then(Http::response(200, '%PDF', ['Content-Type' => 'application/pdf']));
        $client = $this->client();

        $client->documents->downloadFile('d1');
        $client->documents->downloadFile('d1', variant: 'signed');

        $calls = $this->http->sentTo('/file/content');
        self::assertSame('', $calls[0]['request']->getUri()->getQuery());
        self::assertSame('variant=signed', $calls[1]['request']->getUri()->getQuery());
    }

    public function testUploadsSendMultipart(): void
    {
        $this->http->then(Http::response(201, ['success' => true, 'data' => $this->samples->object('Document')]));
        $stream = fopen('php://memory', 'r+');
        self::assertNotFalse($stream);
        fwrite($stream, '%PDF-1.4 contract');
        rewind($stream);

        $this->client()->documents->upload('Contract', $stream, 'contract.pdf');

        [$call] = $this->http->sentTo('/documents/upload');
        self::assertStringStartsWith('multipart/form-data; boundary=', $call['request']->getHeaderLine('Content-Type'));
        self::assertStringContainsString("name=\"name\"\r\n\r\nContract\r\n", $call['body']);
        self::assertStringContainsString("name=\"file\"; filename=\"contract.pdf\"\r\nContent-Type: application/pdf\r\n\r\n%PDF-1.4 contract\r\n", $call['body']);
    }

    public function testOptionalBodiesAreSentAsAnEmptyObject(): void
    {
        $this->http->then(Http::response(201, ['success' => true, 'data' => $this->samples->object('Envelope')]));

        $this->client()->envelopes->resend('env-1');

        self::assertSame('{}', $this->http->sentTo('/resend')[0]['body']);
    }

    public function testDeleteReturnsNothing(): void
    {
        $this->http->then(Http::response(200, ['success' => true]));

        $this->client()->webhookSubscriptions->delete('sub-1');

        self::assertSame('DELETE', $this->http->sentTo('/webhook-subscriptions/sub-1')[0]['request']->getMethod());
    }

    public function testListsWithoutPaginationReturnModels(): void
    {
        $this->http->then(Http::response(200, ['success' => true, 'data' => [$this->samples->object('DocumentField'), $this->samples->object('DocumentField')]]));

        self::assertCount(2, $this->client()->documents->listFields('d1'));
    }

    public function testMeUsesTheMeModel(): void
    {
        $this->http->then(Http::response(200, ['success' => true, 'data' => $this->samples->object('Me')]));

        self::assertInstanceOf(Me::class, $this->client()->me->get());
    }

    private function client(): Client
    {
        $factory = new Psr17Factory();

        return new Client(
            environment: 'https://api.pactiva.test',
            clientId: 'pcl_test',
            clientKey: LocalKey::generate('ES256', 'k1'),
            dpopKey: LocalKey::generate('ES256'),
            scopes: ['signing:read'],
            httpClient: $this->http,
            requestFactory: $factory,
            streamFactory: $factory,
            clock: new FrozenClock(),
            sleep: static function (): void {
            },
        );
    }
}
