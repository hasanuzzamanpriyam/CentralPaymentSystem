<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\Project;
use Illuminate\Support\Facades\Hash;

class AuthenticateProjectKey
{
    /**
     * Handle an incoming request.
     * Authenticates an SDK/MERN request using the Project's API Key.
     */
    public function handle(Request $request, Closure $next)
    {
        // Project ID can come from route model binding, URL param, header, or body.
        $projectId = $request->route('project') ?? $request->header('X-Project-Id') ?? $request->input('project_id');
        
        if ($projectId instanceof Project) {
            $projectId = $projectId->id;
        }

        $apiKey = $request->bearerToken() ?? $request->header('X-API-Key');

        if (!$projectId || !$apiKey) {
            return response()->json(['message' => 'Unauthorized. Missing Project ID or API Key.'], 401);
        }

        $project = Project::find($projectId);

        // API Key is stored as a bcrypt hash, so we use Hash::check
        if (!$project || !Hash::check($apiKey, $project->api_key)) {
            return response()->json(['message' => 'Unauthorized. Invalid API Key for this Project.'], 401);
        }

        // Inject the authenticated project directly into the request
        $request->attributes->set('auth_project', $project);

        return $next($request);
    }
}
