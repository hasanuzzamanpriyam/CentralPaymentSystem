<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\ProjectGateway;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ProjectController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $project = $request->user()->projects()->create([
            'name' => $validated['name'],
            'api_key' => 'sk_' . Str::random(32),
            'webhook_secret' => 'whsec_' . Str::random(32),
        ]);

        return back()->with('success', 'Project created successfully.');
    }

    public function updateWebhook(Request $request, Project $project)
    {
        if ($project->user_id !== $request->user()->id) abort(403);

        $validated = $request->validate([
            'webhook_url' => 'required|url',
        ]);

        $project->update(['webhook_url' => $validated['webhook_url']]);

        return back()->with('success', 'Webhook URL updated.');
    }

    public function regenerateApiKey(Request $request, Project $project)
    {
        if ($project->user_id !== $request->user()->id) abort(403);

        $newApiKey = 'sk_' . Str::random(32);
        $newWebhookSecret = 'whsec_' . Str::random(32);

        $project->update([
            'api_key' => $newApiKey,
            'webhook_secret' => $newWebhookSecret,
        ]);

        return back()->with([
            'success' => 'Credentials regenerated.',
            'raw_credentials' => [
                'project_id' => $project->id,
                'api_key' => $newApiKey,
                'webhook_secret' => $newWebhookSecret,
            ]
        ]);
    }

    public function configureGateway(Request $request, Project $project)
    {
        if ($project->user_id !== $request->user()->id) abort(403);

        $validated = $request->validate([
            'gateway_name' => 'required|string|in:stripe,bkash,sslcommerz',
            'is_active' => 'required|boolean',
            'credentials' => 'nullable|array',
        ]);

        $project->gateways()->updateOrCreate(
            ['gateway_name' => $validated['gateway_name']],
            [
                'is_active' => $validated['is_active'],
                'credentials' => $validated['credentials'],
            ]
        );

        return back()->with('success', 'Gateway configured successfully.');
    }
}
