<?php

namespace App\Http\Controllers\Payment;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Throwable;

class BpkadCallbackController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $payload = $request->all();
        $rawBody = $request->getContent();
        $signatureValid = $this->isSignatureValid($request, $rawBody);
        $externalId = trim((string) data_get($payload, 'external_id', data_get($payload, 'reference_id', '')));
        $method = strtoupper(trim((string) data_get($payload, 'method', data_get($payload, 'payment_method', ''))));
        $status = strtoupper(trim((string) data_get($payload, 'status', '')));
        $amount = data_get($payload, 'amount');
        $payment = $externalId !== ''
            ? DB::table('pembayaran')->where('external_id', $externalId)->orderByDesc('id')->first()
            : null;

        $logId = DB::table('payment_callback_logs')->insertGetId([
            'provider' => 'BPKAD',
            'external_id' => $externalId !== '' ? $externalId : null,
            'payment_method' => $method !== '' ? $method : null,
            'status' => $status !== '' ? $status : null,
            'amount' => is_numeric($amount) ? (float) $amount : null,
            'headers' => json_encode($request->headers->all(), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            'payload' => $rawBody !== '' ? $rawBody : json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            'signature_valid' => $signatureValid,
            'process_status' => 'received',
            'payment_id' => $payment?->id,
            'reservation_id' => $payment?->reservation_id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        if (!$signatureValid) {
            $this->finishLog($logId, 'rejected', 'Signature callback tidak valid.');

            return response()->json(['ok' => false, 'message' => 'Invalid signature'], 401);
        }

        if ($payment === null) {
            $this->finishLog($logId, 'ignored', 'Payment external_id tidak ditemukan.');

            return response()->json(['ok' => true, 'message' => 'Payment not found']);
        }

        try {
            DB::transaction(function () use ($payload, $rawBody, $status, $amount, $payment, $logId): void {
                $current = DB::table('pembayaran')->where('id', $payment->id)->lockForUpdate()->first();
                if ($current === null) {
                    $this->finishLog($logId, 'ignored', 'Payment hilang saat proses.');
                    return;
                }

                if (is_numeric($amount) && (float) $current->amount !== (float) $amount) {
                    $this->finishLog($logId, 'rejected', 'Nominal callback tidak sesuai.');
                    return;
                }

                $mappedStatus = $this->mapProviderStatus($status);
                $update = [
                    'callback_payload' => $rawBody !== '' ? $rawBody : json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                    'callback_received_at' => now(),
                    'updated_at' => now(),
                ];

                if ($mappedStatus !== '') {
                    $update['status'] = $mappedStatus;
                }

                if ($mappedStatus === 'PAID' && empty($current->paid_at)) {
                    $update['paid_at'] = now();
                }

                DB::table('pembayaran')->where('id', $current->id)->update($update);
                $this->finishLog($logId, 'processed', 'Callback diproses.');
            });
        } catch (Throwable $exception) {
            $this->finishLog($logId, 'failed', $exception->getMessage());

            return response()->json(['ok' => false, 'message' => 'Callback failed'], 500);
        }

        return response()->json(['ok' => true]);
    }

    private function isSignatureValid(Request $request, string $rawBody): bool
    {
        $secret = trim((string) config('payment.bpkad.callback_secret', ''));
        if ($secret === '') {
            return false;
        }

        $signature = trim((string) ($request->header('X-Signature') ?? $request->header('X-BPKAD-Signature') ?? ''));
        if ($signature === '') {
            return false;
        }

        return hash_equals(hash_hmac('sha256', $rawBody, $secret), $signature);
    }

    private function mapProviderStatus(string $status): string
    {
        return match ($status) {
            'PAID', 'SUCCESS', 'SETTLED', 'LUNAS' => 'PAID',
            'EXPIRED', 'KEDALUWARSA' => 'EXPIRED',
            'FAILED', 'FAIL', 'GAGAL' => 'FAILED',
            'CANCELLED', 'CANCELED', 'BATAL' => 'CANCELLED',
            default => '',
        };
    }

    private function finishLog(int $logId, string $status, string $message): void
    {
        DB::table('payment_callback_logs')->where('id', $logId)->update([
            'process_status' => $status,
            'process_message' => $message,
            'updated_at' => now(),
        ]);
    }
}
