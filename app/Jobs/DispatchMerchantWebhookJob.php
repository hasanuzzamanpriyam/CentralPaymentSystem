<?php

namespace App\Jobs;

use App\Models\Project;
use App\Models\Transaction;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DispatchMerchantWebhookJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $transaction;
    public $project;
    public $standardizedPayload;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 4;

    /**
     * Calculate the number of seconds to wait before retrying the job.
     *
     * @return array<int, int>
     */
    public function backoff(): array
    {
        return [60, 300, 1800]; // 1 minute, 5 minutes, 30 minutes
    }

    /**
     * Create a new job instance.
     */
    public function __construct(Transaction $transaction, Project $project, array $standardizedPayload = null)
    {
        $this->transaction = $transaction;
        $this->project = $project;
        $this->standardizedPayload = $standardizedPayload;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        if (empty($this->project->webhook_url)) {
            Log::warning('Webhook URL not configured for project ID: ' . $this->project->id);
            return;
        }

        // Fallback to basic representation if no standard payload is passed
        $basePayload = [
            'transaction_id' => $this->transaction->id,
            'gateway_transaction_id' => $this->transaction->gateway_transaction_id,
            'amount' => $this->transaction->amount,
            'currency' => 'BDT', // Adjust accordingly if you stored currency in Transaction
            'status' => $this->transaction->status,
            'type' => $this->transaction->type,
            'metadata' => $this->transaction->metadata,
            'timestamp' => now()->toIso8601String(),
        ];

        $payload = array_merge($basePayload, $this->standardizedPayload ?? []);
        $jsonPayload = json_encode($payload);

        // Compute HMAC SHA-256 signature
        $signature = hash_hmac('sha256', $jsonPayload, $this->project->webhook_secret);

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'X-Orchestrator-Signature' => $signature,
            'User-Agent' => 'CentralPaymentSystem-WebhookDispatcher/1.0',
        ])
        ->timeout(10)
        ->post($this->project->webhook_url, $payload);

        // If the merchant server does not respond with a 2xx status, fail the job and trigger a retry
        if ($response->failed()) {
            $status = $response->status();
            $body = $response->body();
            Log::error("Webhook failed for Transaction ID: {$this->transaction->id}. Status: {$status}. Response: {$body}");
            $this->release($this->backoff()[$this->attempts() - 1] ?? 1800);
        } else {
            Log::info("Webhook delivered successfully for Transaction ID: {$this->transaction->id}");
        }
    }
}
