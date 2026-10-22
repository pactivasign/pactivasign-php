<?php

declare(strict_types=1);

namespace PactivaSign\Key;

use Jose\Component\Core\JWK as JoseJwk;
use Jose\Component\KeyManagement\JWKFactory;
use Jose\Component\Signature\Algorithm\SignatureAlgorithm;
use Jose\Component\Signature\Algorithm;
use PactivaSign\Exception\ConfigurationException;
use Psr\Log\LoggerInterface;

/**
 * A private key held in this process: from a JWK, a JWK or PEM file, or an environment variable.
 *
 * The key material is never printed, serialized or included in exceptions.
 */
final class LocalKey implements KeyProvider
{
    /** Algorithms PactivaSign accepts by default for client assertions and DPoP proofs. */
    public const ALGORITHMS = ['ES256', 'ES384', 'ES512', 'PS256', 'PS384', 'PS512', 'RS256', 'RS384', 'RS512', 'EdDSA'];

    private const ALGORITHM_CLASSES = [
        'ES256' => Algorithm\ES256::class,
        'ES384' => Algorithm\ES384::class,
        'ES512' => Algorithm\ES512::class,
        'PS256' => Algorithm\PS256::class,
        'PS384' => Algorithm\PS384::class,
        'PS512' => Algorithm\PS512::class,
        'RS256' => Algorithm\RS256::class,
        'RS384' => Algorithm\RS384::class,
        'RS512' => Algorithm\RS512::class,
        'EdDSA' => Algorithm\EdDSA::class,
    ];

    private const EC_CURVES = ['ES256' => 'P-256', 'ES384' => 'P-384', 'ES512' => 'P-521'];

    private readonly JoseJwk $jwk;
    private readonly SignatureAlgorithm|RsaSigner $signer;
    private readonly string $algorithm;
    private readonly ?string $keyId;

    private function __construct(JoseJwk $jwk, ?string $algorithm, ?string $keyId)
    {
        if (!$jwk->has('d')) {
            throw new ConfigurationException('A private key is required, but the key has no private part.');
        }
        $algorithm ??= $jwk->has('alg') ? (string) $jwk->get('alg') : self::defaultAlgorithm($jwk);
        if (!isset(self::ALGORITHM_CLASSES[$algorithm])) {
            throw new ConfigurationException(sprintf(
                'Algorithm "%s" is not supported. Use one of: %s.',
                $algorithm,
                implode(', ', self::ALGORITHMS),
            ));
        }
        self::assertKeyFitsAlgorithm($jwk, $algorithm);

        $this->jwk = $jwk;
        $this->algorithm = $algorithm;
        $this->keyId = $keyId ?? ($jwk->has('kid') ? (string) $jwk->get('kid') : null);
        if ($jwk->get('kty') === 'RSA') {
            $this->signer = RsaSigner::fromJwk($jwk->all());
        } else {
            $class = self::ALGORITHM_CLASSES[$algorithm];
            $this->signer = new $class();
        }
    }

    /**
     * @param array<string, mixed>|string $jwk JWK as an array or a JSON string
     */
    public static function fromJwk(array|string $jwk, ?string $algorithm = null, ?string $keyId = null): self
    {
        if (is_string($jwk)) {
            $decoded = json_decode($jwk, true);
            if (!is_array($decoded)) {
                throw new ConfigurationException('The key is not valid JWK JSON.');
            }
            $jwk = $decoded;
        }

        try {
            return new self(new JoseJwk($jwk), $algorithm, $keyId);
        } catch (ConfigurationException $e) {
            throw $e;
        } catch (\Throwable) {
            throw new ConfigurationException('The JWK could not be loaded.');
        }
    }

    /**
     * Loads a JWK (JSON) or PEM private key file. Logs a warning when the file is readable by group
     * or others; keep it at mode 0600.
     */
    public static function fromFile(
        string $path,
        ?string $algorithm = null,
        ?string $keyId = null,
        ?LoggerInterface $logger = null,
    ): self {
        if (!is_file($path) || !is_readable($path)) {
            throw new ConfigurationException(sprintf('Key file "%s" does not exist or is not readable.', $path));
        }
        $permissions = @fileperms($path);
        if ($logger !== null && $permissions !== false && ($permissions & 0o077) !== 0) {
            $logger->warning('PactivaSign key file is readable by other users; restrict it to mode 0600.', ['path' => $path]);
        }
        $contents = (string) file_get_contents($path);

        if (str_starts_with(ltrim($contents), '{')) {
            return self::fromJwk($contents, $algorithm, $keyId);
        }

        try {
            $jwk = JWKFactory::createFromKey($contents);
        } catch (\Throwable) {
            throw new ConfigurationException(sprintf('Key file "%s" is neither a JWK nor a PEM private key.', $path));
        }

        return new self($jwk, $algorithm, $keyId);
    }

    /**
     * Reads a JWK (JSON) or PEM key from an environment variable. Convenient in development; in
     * production prefer a protected file or a secret manager.
     */
    public static function fromEnvironment(string $name, ?string $algorithm = null, ?string $keyId = null): self
    {
        $value = $_SERVER[$name] ?? $_ENV[$name] ?? getenv($name);
        if (!is_string($value) || trim($value) === '') {
            throw new ConfigurationException(sprintf('Environment variable %s is empty or not set.', $name));
        }
        if (str_starts_with(ltrim($value), '{')) {
            return self::fromJwk($value, $algorithm, $keyId);
        }
        try {
            return new self(JWKFactory::createFromKey(str_replace('\n', "\n", $value)), $algorithm, $keyId);
        } catch (ConfigurationException $e) {
            throw $e;
        } catch (\Throwable) {
            throw new ConfigurationException(sprintf('Environment variable %s does not hold a JWK or PEM private key.', $name));
        }
    }

    /** Generates a new key pair, e.g. for a DPoP key or a client key you will register. */
    public static function generate(string $algorithm = 'ES256', ?string $keyId = null): self
    {
        $jwk = match ($algorithm) {
            'ES256', 'ES384', 'ES512' => JWKFactory::createECKey(self::EC_CURVES[$algorithm]),
            'PS256', 'PS384', 'PS512', 'RS256', 'RS384', 'RS512' => JWKFactory::createRSAKey(3072),
            'EdDSA' => JWKFactory::createOKPKey('Ed25519'),
            default => throw new ConfigurationException(sprintf('Algorithm "%s" is not supported.', $algorithm)),
        };

        return new self($jwk, $algorithm, $keyId);
    }

    public function algorithm(): string
    {
        return $this->algorithm;
    }

    public function keyId(): ?string
    {
        return $this->keyId;
    }

    public function publicJwk(): array
    {
        return Jwk::publicMembers($this->jwk->all());
    }

    /** RFC 7638 thumbprint of the public key. */
    public function thumbprint(): string
    {
        return Jwk::thumbprint($this->publicJwk());
    }

    public function sign(string $signingInput): string
    {
        return $this->signer instanceof RsaSigner
            ? $this->signer->sign($this->algorithm, $signingInput)
            : $this->signer->sign($this->jwk, $signingInput);
    }

    /**
     * The public JWK to register with PactivaSign, with `alg`, `use` and `kid` when known.
     *
     * @return array<string, string>
     */
    public function registrationJwk(): array
    {
        $jwk = $this->publicJwk() + ['alg' => $this->algorithm, 'use' => 'sig'];
        if ($this->keyId !== null) {
            $jwk['kid'] = $this->keyId;
        }

        return $jwk;
    }

    /**
     * The full private JWK as JSON, for saving a generated key. Store it like a password (mode 0600,
     * a secret manager); never send it to PactivaSign.
     */
    public function exportPrivateJwk(): string
    {
        $jwk = $this->jwk->all() + ['alg' => $this->algorithm];
        if ($this->keyId !== null) {
            $jwk['kid'] = $this->keyId;
        }

        return json_encode($jwk, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
    }

    /**
     * @return array<string, mixed>
     */
    public function __debugInfo(): array
    {
        return ['algorithm' => $this->algorithm, 'keyId' => $this->keyId, 'thumbprint' => $this->thumbprint()];
    }

    /**
     * @return array<string, mixed>
     */
    public function __serialize(): array
    {
        throw new \LogicException('LocalKey cannot be serialized; it holds private key material.');
    }

    /**
     * @param array<string, mixed> $data
     */
    public function __unserialize(array $data): void
    {
        throw new \LogicException('LocalKey cannot be unserialized.');
    }

    private static function defaultAlgorithm(JoseJwk $jwk): string
    {
        return match ($jwk->get('kty')) {
            'EC' => array_search($jwk->has('crv') ? $jwk->get('crv') : '', self::EC_CURVES, true) ?: 'ES256',
            'OKP' => 'EdDSA',
            'RSA' => 'RS256',
            default => throw new ConfigurationException('Unsupported key type.'),
        };
    }

    private static function assertKeyFitsAlgorithm(JoseJwk $jwk, string $algorithm): void
    {
        $kty = $jwk->get('kty');
        $fits = match (true) {
            isset(self::EC_CURVES[$algorithm]) => $kty === 'EC' && $jwk->has('crv') && $jwk->get('crv') === self::EC_CURVES[$algorithm],
            $algorithm === 'EdDSA' => $kty === 'OKP' && $jwk->has('crv') && $jwk->get('crv') === 'Ed25519',
            default => $kty === 'RSA',
        };
        if (!$fits) {
            throw new ConfigurationException(sprintf('The key does not match algorithm %s.', $algorithm));
        }
        if ($kty === 'RSA') {
            $bits = strlen(self::base64UrlDecode((string) $jwk->get('n'))) * 8;
            if ($bits < 2048) {
                throw new ConfigurationException('RSA keys must be at least 2048 bits.');
            }
        }
    }

    private static function base64UrlDecode(string $value): string
    {
        return (string) base64_decode(strtr($value, '-_', '+/'), true);
    }
}
