<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Laravel\Sanctum\PersonalAccessToken;

class PublicApiDocumentationController extends Controller
{
    private const SESSION_TOKEN_ID = 'api_documentation_token_id';

    public function index(Request $request): Response
    {
        $token = $this->validIntegrationToken(
            $request->session()->get(self::SESSION_TOKEN_ID),
        );

        if (! $token) {
            $request->session()->forget(self::SESSION_TOKEN_ID);
        }

        return Inertia::render('ApiDocumentation/Index', [
            'authorized' => (bool) $token,
            'tokenName' => $token?->name,
            'baseUrl' => url('/api/integration/v1'),
        ]);
    }

    public function authenticate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'token' => ['required', 'string', 'max:255'],
        ]);

        $token = PersonalAccessToken::findToken($validated['token']);

        if (! $this->isUsableIntegrationToken($token)) {
            return response()->json([
                'message' => 'The API token is invalid, expired, revoked, or does not have integration access.',
            ], 422);
        }

        $request->session()->put(self::SESSION_TOKEN_ID, $token->getKey());
        $request->session()->regenerate();

        return response()->json([
            'message' => 'Documentation access granted.',
            'token_name' => $token->name,
        ]);
    }

    public function logout(Request $request): RedirectResponse
    {
        $request->session()->forget(self::SESSION_TOKEN_ID);

        return redirect()->route('api-docs.index');
    }

    private function validIntegrationToken(mixed $tokenId): ?PersonalAccessToken
    {
        if (! is_numeric($tokenId)) {
            return null;
        }

        $token = PersonalAccessToken::query()
            ->with('tokenable')
            ->find($tokenId);

        return $this->isUsableIntegrationToken($token) ? $token : null;
    }

    private function isUsableIntegrationToken(?PersonalAccessToken $token): bool
    {
        if (! $token || ! in_array('integration:read', $token->abilities ?? [], true)) {
            return false;
        }

        if ($token->expires_at?->isPast()) {
            return false;
        }

        $tokenable = $token->tokenable;

        return $tokenable !== null && (bool) $tokenable->is_active;
    }
}
