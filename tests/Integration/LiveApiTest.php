<?php

declare(strict_types=1);

namespace PactivaSign\Tests\Integration;

use PactivaSign\Cache\ArrayCache;
use PactivaSign\Client;
use PactivaSign\Exception\NotFoundException;
use PactivaSign\Exception\PermissionException;
use PactivaSign\Http\Multipart;
use PactivaSign\Key\LocalKey;
use PactivaSign\SystemClock;
use PactivaSign\Tests\Support\RecordingHttpClient;
use PHPUnit\Framework\TestCase;
use Psr\SimpleCache\CacheInterface;
use Symfony\Component\HttpClient\Psr18Client;

/**
 * Runs the SDK against a real PactivaSign API. Skipped unless these are set:
 *
 *   PACTIVASIGN_BASE_URL         e.g. https://pactiva-api.duckdns.org
 *   PACTIVASIGN_CLIENT_ID        pcl_...
 *   PACTIVASIGN_CLIENT_KEY_FILE  private JWK or PEM registered for the client
 *   PACTIVASIGN_DPOP_KEY_FILE    private JWK or PEM for DPoP (not registered)
 *   PACTIVASIGN_SCOPES           space-separated; must include documents:create, documents:read,
 *                                documents:download and signing:read, and not signing:create
 */
final class LiveApiTest extends TestCase
{
    private Psr18Client $http;
    private RecordingHttpClient $recorder;

    protected function setUp(): void
    {
        foreach (['PACTIVASIGN_BASE_URL', 'PACTIVASIGN_CLIENT_ID', 'PACTIVASIGN_CLIENT_KEY_FILE', 'PACTIVASIGN_DPOP_KEY_FILE', 'PACTIVASIGN_SCOPES'] as $name) {
            if ((string) getenv($name) === '') {
                self::markTestSkipped("$name is not set");
            }
        }
        $this->http = new Psr18Client();
        $this->recorder = new RecordingHttpClient($this->http);
    }

    public function testAuthenticatesWithTheRequestedScopes(): void
    {
        $granted = explode(' ', $this->client()->authenticate());
        sort($granted);
        $requested = $this->scopes();
        sort($requested);

        self::assertSame($requested, $granted);
    }

    public function testTheFirstTokenRequestAnswersTheNonceChallenge(): void
    {
        $this->client()->authenticate();

        self::assertSame(['/api/v1/oauth/token', '/api/v1/oauth/token'], $this->recorder->paths(), 'challenge, then success');
    }

    public function testReadsTheCallerIdentity(): void
    {
        $response = $this->client()->request('GET', '/api/v1/me');

        self::assertSame(200, $response->statusCode());
        self::assertNotNull($response->requestId());
        self::assertIsArray($response->data());
    }

    public function testUploadsReadsAndDownloadsADocument(): void
    {
        $client = $this->client();
        $pdf = self::minimalPdf();

        $created = $client->request('POST', '/api/v1/documents/upload', [
            'multipart' => (new Multipart())
                ->field('name', 'PHP SDK integration test')
                ->file('file', $pdf, 'php-sdk-test.pdf'),
        ]);
        self::assertSame(201, $created->statusCode());
        $document = $created->data();
        self::assertIsArray($document);
        self::assertIsString($document['id'] ?? null);

        $read = $client->request('GET', '/api/v1/documents/' . $document['id'])->data();
        self::assertIsArray($read);
        self::assertSame('PHP SDK integration test', $read['name'] ?? null);

        $download = $client->request('GET', '/api/v1/documents/' . $document['id'] . '/file/content', [
            'headers' => ['Accept' => 'application/pdf'],
        ]);
        self::assertSame('application/pdf', strtok((string) $download->header('Content-Type'), ';'));
        self::assertStringStartsWith('%PDF-', (string) $download->body());
    }

    public function testMissingDocumentsRaiseNotFoundWithARequestId(): void
    {
        try {
            $this->client()->request('GET', '/api/v1/documents/00000000-0000-4000-8000-000000000000');
            self::fail('expected NotFoundException');
        } catch (NotFoundException $e) {
            self::assertSame(404, $e->statusCode);
            self::assertSame('NOT_FOUND', $e->errorCode);
            self::assertNotNull($e->requestId);
        }
    }

    public function testAnUngrantedScopeRaisesPermissionException(): void
    {
        try {
            $this->client()->request('POST', '/api/v1/envelopes', [
                'json' => ['name' => 'should not be sent', 'signers' => [['email' => 'nobody@example.com', 'name' => 'Nobody']]],
                'idempotencyKey' => bin2hex(random_bytes(16)),
            ]);
            self::fail('expected PermissionException');
        } catch (PermissionException $e) {
            self::assertSame(403, $e->statusCode);
            self::assertSame('insufficient_scope', $e->errorCode);
        }
    }

    public function testWorkersSharingACacheReuseOneToken(): void
    {
        $cache = new ArrayCache(new SystemClock());
        $this->client($cache)->request('GET', '/api/v1/me');
        $this->recorder->reset();

        $this->client($cache)->request('GET', '/api/v1/me');

        self::assertSame(['/api/v1/me'], $this->recorder->paths());
    }

    public function testARejectedTokenIsReplacedTransparently(): void
    {
        $cache = new ArrayCache(new SystemClock());
        $this->client($cache)->authenticate();
        // Corrupt the cached token's signature, as if it had been revoked.
        foreach ((fn () => $this->items)->call($cache) as $key => [$value]) {
            if (is_array($value) && isset($value['value'])) {
                $value['value'] = substr($value['value'], 0, -4) . 'AAAA';
                $cache->set($key, $value, 600);
            }
        }
        $this->recorder->reset();

        $response = $this->client($cache)->request('GET', '/api/v1/me');

        self::assertSame(200, $response->statusCode());
        $paths = $this->recorder->paths();
        self::assertSame('/api/v1/me', $paths[0] ?? null, 'the corrupted token is tried first');
        self::assertContains('/api/v1/oauth/token', $paths);
        self::assertSame('/api/v1/me', end($paths));
    }

    private function client(?CacheInterface $cache = null): Client
    {
        return new Client(
            environment: (string) getenv('PACTIVASIGN_BASE_URL'),
            clientId: (string) getenv('PACTIVASIGN_CLIENT_ID'),
            clientKey: LocalKey::fromFile((string) getenv('PACTIVASIGN_CLIENT_KEY_FILE')),
            dpopKey: LocalKey::fromFile((string) getenv('PACTIVASIGN_DPOP_KEY_FILE')),
            scopes: $this->scopes(),
            cache: $cache,
            httpClient: $this->recorder,
            requestFactory: $this->http,
            streamFactory: $this->http,
        );
    }

    /**
     * @return list<string>
     */
    private function scopes(): array
    {
        return array_values(array_filter(explode(' ', trim((string) getenv('PACTIVASIGN_SCOPES'), " \"'"))));
    }

    /** A one-page PDF with valid cross-reference offsets. */
    private static function minimalPdf(): string
    {
        $objects = [
            '<< /Type /Catalog /Pages 2 0 R >>',
            '<< /Type /Pages /Kids [3 0 R] /Count 1 >>',
            '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Contents 4 0 R /Resources << /Font << /F1 5 0 R >> >> >>',
            "<< /Length 58 >>\nstream\nBT /F1 18 Tf 72 760 Td (PactivaSign PHP SDK test) Tj ET\nendstream",
            '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>',
        ];
        $pdf = "%PDF-1.4\n";
        $offsets = [];
        foreach ($objects as $i => $object) {
            $offsets[] = strlen($pdf);
            $pdf .= ($i + 1) . " 0 obj\n$object\nendobj\n";
        }
        $xref = strlen($pdf);
        $pdf .= 'xref' . "\n0 " . (count($objects) + 1) . "\n0000000000 65535 f \n";
        foreach ($offsets as $offset) {
            $pdf .= sprintf("%010d 00000 n \n", $offset);
        }

        return $pdf . 'trailer' . "\n<< /Size " . (count($objects) + 1) . " /Root 1 0 R >>\nstartxref\n$xref\n%%EOF\n";
    }
}
