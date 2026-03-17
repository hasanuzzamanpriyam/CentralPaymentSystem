<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Services\Payments\PaymentManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class GatewayCredentialController extends Controller
{
    protected PaymentManager $paymentManager;

    public function __construct(PaymentManager $paymentManager)
    {
        $this->paymentManager = $paymentManager;
    }

    public function index(Request $request, Project $project)
    {
        if ($project->user_id !== $request->user()->id) {
            abort(403, 'Unauthorized access to project gateways.');
        }

        $gateways = $project->gateways()->get()->map(function ($gateway) {
            $driver = $this->paymentManager->resolve($gateway->gateway_name);
            return [
                'name' => $gateway->gateway_name,
                'is_active' => $gateway->is_active,
                'required_credentials' => $driver->getRequiredCredentials(),
                'supports_refunds' => $driver->supportsRefunds(),
            ];
        });

        return response()->json(['gateways' => $gateways]);
    }

    public function show(Request $request, Project $project, string $gatewayName)
    {
        if ($project->user_id !== $request->user()->id) {
            Log::warning("Unauthorized credential access attempt", [
                'user_id' => $request->user()->id,
                'project_id' => $project->id,
                'ip' => $request->ip()
            ]);
            abort(403, 'Unauthorized access to project credentials.');
        }

        $gateway = $project->gateways()->where('gateway_name', $gatewayName)->firstOrFail();

        Log::info("Project credentials accessed via API", [
            'user_id' => $request->user()->id,
            'project_id' => $project->id,
            'gateway' => $gatewayName,
            'ip' => $request->ip()
        ]);

        return response()->json([
            'gateway' => $gateway->gateway_name,
            'is_active' => $gateway->is_active,
            'credentials' => $gateway->credentials,
        ]);
    }
}
