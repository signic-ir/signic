<?php

declare(strict_types=1);

namespace App\Modules\Registration\Services;

use App\Modules\Registration\Contracts\QRServiceInterface;

class QRService implements QRServiceInterface
{
    public function generate(int $attendeeId): string
    {
        $data = json_encode(['attendee_id' => $attendeeId, 'nonce' => random_int(100000, 999999)]);
        $signature = hash_hmac('sha256', $data, config('app.key'));

        return str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($data)) . '|' . $signature;
    }

    public function validate(string $token): ?int
    {
        if (!str_contains($token, '|')) {
            return null;
        }

        [$encodedData, $signature] = explode('|', $token, 2);

        $expectedData = strtr($encodedData, ['-' => '+', '_' => '/']);
        $decodedData = base64_decode($expectedData, true);

        if ($decodedData === false) {
            return null;
        }

        $expectedSignature = hash_hmac('sha256', $decodedData, config('app.key'));

        if (!hash_equals($expectedSignature, $signature)) {
            return null;
        }

        $data = json_decode($decodedData, true);

        if (!isset($data['attendee_id'])) {
            return null;
        }

        return (int) $data['attendee_id'];
    }

    public function generateQRCode(string $token): string
    {
        return 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=' . urlencode($token);
    }
}