<?php

declare(strict_types=1);

namespace PactivaSign\Webhook;

use PactivaSign\Exception\ConfigurationException;
use PactivaSign\Exception\SignatureVerificationException;
use PactivaSign\Exception\UnexpectedResponseException;
use PactivaSign\Model\WebhookEvent;

/**
 * Verifies webhook deliveries.
 *
 *     $event = Webhook::verify(
 *         file_get_contents('php://input'),            // the raw body, before any JSON decoding
 *         $_SERVER['HTTP_PACTIVA_SIGNATURE'] ?? '',
 *         [$currentSecret, $previousSecret],            // both during a secret rotation
 *     );
 *
 * The header is `t=<unix time>,v1=<hex HMAC-SHA256 of "<t>.<raw body>">`, with one `v1` per active
 * secret. Then deduplicate on `$event->id` and ignore events whose `$event->data->version` is older
 * than the envelope version you already have.
 */
final class Webhook
{
    public const SIGNATURE_HEADER = 'Pactiva-Signature';
    public const EVENT_ID_HEADER = 'Pactiva-Event-Id';
    public const EVENT_TYPE_HEADER = 'Pactiva-Event-Type';
    public const DELIVERY_ID_HEADER = 'Pactiva-Delivery-Id';

    /** Deliveries signed longer ago than this (or this far in the future) are rejected, against replays. */
    public const DEFAULT_TOLERANCE_SECONDS = 300;

    /**
     * Checks the signature and returns the event.
     *
     * @param string|list<string> $secrets the subscription's signing secret, or several during a rotation
     * @param int|null $now Unix time; for tests
     * @throws SignatureVerificationException when the signature is missing, wrong or too old
     */
    public static function verify(
        string $payload,
        string $signatureHeader,
        string|array $secrets,
        int $tolerance = self::DEFAULT_TOLERANCE_SECONDS,
        ?int $now = null,
    ): WebhookEvent {
        self::verifySignature($payload, $signatureHeader, $secrets, $tolerance, $now);

        $data = json_decode($payload, true);
        if (!is_array($data) || array_is_list($data)) {
            throw new UnexpectedResponseException('The webhook body is not a JSON object.');
        }

        /** @var array<string, mixed> $data */
        return WebhookEvent::fromArray($data);
    }

    /**
     * Checks the signature only, e.g. before queueing the raw body for later processing.
     *
     * @param string|list<string> $secrets
     * @throws SignatureVerificationException
     */
    public static function verifySignature(
        string $payload,
        string $signatureHeader,
        string|array $secrets,
        int $tolerance = self::DEFAULT_TOLERANCE_SECONDS,
        ?int $now = null,
    ): void {
        $secrets = self::secrets($secrets);
        $timestamp = null;
        $signatures = [];
        foreach (explode(',', $signatureHeader) as $part) {
            [$name, $value] = array_map('trim', explode('=', $part, 2)) + [1 => ''];
            if ($name === 't' && ctype_digit($value)) {
                $timestamp = (int) $value;
            } elseif ($name === 'v1' && $value !== '') {
                $signatures[] = strtolower($value);
            }
        }

        if ($timestamp === null || $signatures === []) {
            throw new SignatureVerificationException('The Pactiva-Signature header is missing or malformed.');
        }
        if (abs(($now ?? time()) - $timestamp) > $tolerance) {
            throw new SignatureVerificationException('The webhook signature timestamp is outside the tolerance; possible replay.');
        }

        foreach ($secrets as $secret) {
            $expected = self::hmac($secret, $timestamp, $payload);
            foreach ($signatures as $signature) {
                if (hash_equals($expected, $signature)) {
                    return;
                }
            }
        }

        throw new SignatureVerificationException('No webhook signature matches the configured secret(s).');
    }

    /**
     * Builds a Pactiva-Signature header, e.g. to send test deliveries to your own endpoint.
     *
     * @param string|list<string> $secrets
     */
    public static function signatureHeader(string $payload, string|array $secrets, ?int $timestamp = null): string
    {
        $timestamp ??= time();
        $parts = ['t=' . $timestamp];
        foreach (self::secrets($secrets) as $secret) {
            $parts[] = 'v1=' . self::hmac($secret, $timestamp, $payload);
        }

        return implode(',', $parts);
    }

    private static function hmac(string $secret, int $timestamp, string $payload): string
    {
        return hash_hmac('sha256', $timestamp . '.' . $payload, $secret);
    }

    /**
     * @param string|list<string> $secrets
     * @return non-empty-list<non-empty-string>
     */
    private static function secrets(string|array $secrets): array
    {
        $secrets = array_values(array_filter(is_string($secrets) ? [$secrets] : $secrets, static fn ($s) => $s !== ''));
        if ($secrets === []) {
            throw new ConfigurationException('No webhook signing secret is configured.');
        }

        return $secrets;
    }
}
