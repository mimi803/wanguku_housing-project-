<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class AfricasTalkingSms
{
    public function send(string $phone, string $message): void
    {
        $username = (string) config('services.africas_talking.username');
        $apiKey = (string) config('services.africas_talking.api_key');
        if ($username === '' || $apiKey === '') {
            throw new RuntimeException('SMS verification is not configured.');
        }

        $payload = ['username' => $username, 'to' => $phone, 'message' => $message];
        $senderId = config('services.africas_talking.sender_id');
        if (is_string($senderId) && $senderId !== '') $payload['from'] = $senderId;

        try {
            $response = Http::asForm()
                ->acceptJson()
                ->withHeaders(['apiKey' => $apiKey])
                ->timeout(12)
                ->post(rtrim((string) config('services.africas_talking.base_url'), '/').'/version1/messaging', $payload);

            if (! $response->successful() || (string) (string) data_get($response->json(), 'SMSMessageData.Recipients.0.statusCode') !== '101') {
                Log::warning('SMS provider did not accept a Farmers Connect verification message.', ['status' => $response->status()]);
                throw new RuntimeException('SMS delivery failed.');
            }
        } catch (RuntimeException $exception) {
            throw $exception;
        } catch (\Throwable $exception) {
            Log::warning('SMS provider could not be reached for Farmers Connect verification.', ['type' => $exception::class]);
            throw new RuntimeException('SMS delivery failed.');
        }
    }
}
