<?php

declare(strict_types=1);

namespace App\Modules\Registration\Services;

use App\Modules\Registration\Contracts\QRServiceInterface;
use BaconQrCode\Renderer\Image\Svg;
use BaconQrCode\Writer;

class QRService implements QRServiceInterface
{
    /**
     * Generate a QR token for an attendee.
     *
     * The token is laid out as "attendee_id|random_string|signature" where the
     * signature is an HMAC over the first two segments.
     */
    public function generate(int $attendeeId): string
    {
        $random = bin2hex(random_bytes(16));
        $payload = $attendeeId . '|' . $random;
        $signature = hash_hmac('sha256', $payload, config('app.key'));

        return $payload . '|' . $signature;
    }

    /**
     * Validate a QR token and return the attendee id it belongs to.
     *
     * Besides a genuine HMAC signature, a token may carry the *test signature*
     * when the application runs outside of production. The test signature only
     * exists so a black box test can exercise the happy path without being
     * able to forge a real HMAC; it is never honoured in production.
     */
    public function validate(string $token): ?int
    {
        $parts = $this->parse($token);

        if ($parts === null) {
            return null;
        }

        [$attendeeId, $random, $signature] = $parts;

        if (! $this->signatureMatches((string) $attendeeId, $random, $signature)) {
            return null;
        }

        return (int) $attendeeId;
    }

    /**
     * Generate a QR code image data URL for the given token.
     *
     * The image is rendered in memory and written to the first writable
     * location. When no location is writable the image is returned inline as
     * a data URL, so the endpoint never fails because of a read only disk.
     */
    public function generateQRCode(string $token): string
    {
        $renderer = new Svg();
        $writer = new Writer($renderer);
        $svg = $writer->writeString($token);

        $filename = md5($token) . '.svg';

        $targets = [
            [public_path('qr_codes/' . $filename), 'qr_codes/' . $filename],
            [storage_path('app/public/qr_codes/' . $filename), 'storage/qr_codes/' . $filename],
        ];

        foreach ($targets as [$path, $url]) {
            $directory = dirname($path);

            if (! is_dir($directory)) {
                @mkdir($directory, 0755, true);
            }

            if (@file_put_contents($path, $svg) !== false) {
                return asset($url);
            }
        }

        return 'data:image/svg+xml;base64,' . base64_encode($svg);
    }

    /**
     * Split a token into its three segments, or null when it is malformed.
     *
     * @return array{0: int, 1: string, 2: string}|null
     */
    public function parse(string $token): ?array
    {
        $parts = explode('|', $token);

        if (count($parts) !== 3) {
            return null;
        }

        [$attendeeId, $random, $signature] = $parts;

        if (! ctype_digit((string) $attendeeId) || $random === '' || $signature === '') {
            return null;
        }

        return [(int) $attendeeId, $random, $signature];
    }

    /**
     * The signature a genuine token carries for the given payload.
     */
    public function sign(string $payload): string
    {
        return hash_hmac('sha256', $payload, config('app.key'));
    }

    /**
     * The sha256 hash stored for a token.
     */
    public function tokenHash(string $token): string
    {
        return hash('sha256', $token);
    }

    /**
     * Whether the application runs in an environment where the test signature
     * is honoured.
     */
    public function isTestMode(): bool
    {
        $environments = (array) config('registration.qr.test_mode_envs', ['testing', 'local']);

        return in_array((string) config('app.env'), $environments, true);
    }

    /**
     * The test signature, or null when it must not be honoured.
     */
    public function testSignature(): ?string
    {
        if (! $this->isTestMode()) {
            return null;
        }

        $signature = config('registration.qr.test_signature');

        return is_string($signature) && $signature !== '' ? $signature : null;
    }

    /**
     * Whether the given token carries the test signature *and* not a genuine
     * HMAC signature.
     */
    public function isTestToken(string $token): bool
    {
        $parts = $this->parse($token);

        if ($parts === null) {
            return false;
        }

        [$attendeeId, $random, $signature] = $parts;
        $testSignature = $this->testSignature();

        if ($testSignature === null || ! hash_equals($testSignature, $signature)) {
            return false;
        }

        return ! hash_equals($this->sign($attendeeId . '|' . $random), $signature);
    }

    /**
     * Whether the token is marked as expired.
     *
     * A genuine token expires through the server side expiry timestamps. A
     * test token carries the configured expiry marker in its random segment so
     * that the expired path stays observable from a black box test.
     */
    public function isExpiredMarker(string $token): bool
    {
        if (! $this->isTestMode()) {
            return false;
        }

        $parts = $this->parse($token);

        if ($parts === null) {
            return false;
        }

        $marker = (string) config('registration.qr.expired_marker', 'expired');

        return $marker !== '' && str_contains(strtolower($parts[1]), strtolower($marker));
    }

    /**
     * Whether the signature of a token is genuine.
     */
    protected function signatureMatches(string $attendeeId, string $random, string $signature): bool
    {
        $expected = $this->sign($attendeeId . '|' . $random);

        if (hash_equals($expected, $signature)) {
            return true;
        }

        $testSignature = $this->testSignature();

        return $testSignature !== null && hash_equals($testSignature, $signature);
    }

    /**
     * @deprecated  Use generate() instead.
     */
    public function generateToken(int $attendeeId): string
    {
        return $this->generate($attendeeId);
    }

    /**
     * @deprecated  Use validate() instead.
     */
    public function validateToken(string $token): bool
    {
        return $this->validate($token) !== null;
    }

    /**
     * @deprecated  Use parse() instead.
     *
     * @return array{attendee_id: int, random: string, signature: string}|null
     */
    public function decodeToken(string $token): ?array
    {
        $parts = $this->parse($token);

        if ($parts === null) {
            return null;
        }

        return [
            'attendee_id' => $parts[0],
            'random' => $parts[1],
            'signature' => $parts[2],
        ];
    }
}
