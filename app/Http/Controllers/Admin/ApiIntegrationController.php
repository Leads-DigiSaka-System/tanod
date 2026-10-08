<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreIntegrationTokenRequest;
use App\Services\ActivityLogger;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;
use Laravel\Sanctum\PersonalAccessToken;

class ApiIntegrationController extends Controller
{
    public function index(Request $request): Response
    {
        $tokens = PersonalAccessToken::query()
            ->with('tokenable')
            ->latest()
            ->orderByDesc('id')
            ->get()
            ->filter(fn (PersonalAccessToken $token): bool => in_array('integration:read', $token->abilities ?? [], true))
            ->map(fn (PersonalAccessToken $token): array => [
                'id' => $token->id,
                'name' => $token->name,
                'scope' => 'integration:read',
                'can_reveal' => filled($token->getAttribute('encrypted_secret')),
                'created_by' => $token->tokenable ? [
                    'id' => $token->tokenable->getKey(),
                    'name' => $token->tokenable->name,
                    'email' => $token->tokenable->email,
                ] : null,
                'last_used_at' => $token->last_used_at?->toIso8601String(),
                'expires_at' => $token->expires_at?->toIso8601String(),
                'created_at' => $token->created_at?->toIso8601String(),
                'is_expired' => $token->expires_at?->isPast() ?? false,
            ])
            ->values();

        return Inertia::render('ApiIntegration/Index', [
            'tokens' => $tokens,
            'newToken' => $request->session()->get('newIntegrationToken'),
            'documentationUrl' => route('api-docs.index'),
        ]);
    }

    public function store(StoreIntegrationTokenRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $expiresAt = isset($validated['expires_in_days'])
            ? now()->addDays((int) $validated['expires_in_days'])
            : null;

        $token = $request->user()->createToken(
            $validated['name'],
            ['integration:read'],
            $expiresAt,
        );

        $token->accessToken->forceFill([
            'encrypted_secret' => Crypt::encryptString($token->plainTextToken),
        ])->save();

        ActivityLogger::log('IntegrationToken', $token->accessToken->id, 'created', [
            'name' => $validated['name'],
        ], $request->user());

        return Redirect::back()
            ->with('success', 'Integration token generated. Copy it now; it will not be shown again.')
            ->with('newIntegrationToken', $token->plainTextToken);
    }

    public function destroy(Request $request, int $token): RedirectResponse
    {
        $personalAccessToken = PersonalAccessToken::query()->findOrFail($token);

        abort_unless(in_array('integration:read', $personalAccessToken->abilities ?? [], true), 404);

        $personalAccessToken->delete();

        ActivityLogger::log('IntegrationToken', $personalAccessToken->id, 'deleted', [
            'name' => $personalAccessToken->name,
        ], $request->user());

        return Redirect::back()->with('success', 'Integration token revoked.');
    }

    public function reveal(int $token): JsonResponse
    {
        $personalAccessToken = $this->findIntegrationToken($token);
        $encryptedSecret = $personalAccessToken->getAttribute('encrypted_secret');

        if (! $encryptedSecret) {
            return response()->json([
                'message' => 'This token was created before secure reveal was enabled. Rotate it to generate a viewable replacement.',
            ], 409);
        }

        try {
            $plainTextToken = Crypt::decryptString($encryptedSecret);
        } catch (DecryptException) {
            return response()->json([
                'message' => 'The stored token secret could not be decrypted. Rotate the token to replace it.',
            ], 422);
        }

        return response()->json([
            'token' => $plainTextToken,
        ])->header('Cache-Control', 'no-store, private');
    }

    public function rotate(int $token): RedirectResponse
    {
        $personalAccessToken = $this->findIntegrationToken($token);
        $tokenable = $personalAccessToken->tokenable;

        abort_unless($tokenable && method_exists($tokenable, 'createToken'), 422);

        $replacement = $tokenable->createToken(
            $personalAccessToken->name,
            $personalAccessToken->abilities ?? ['integration:read'],
            $personalAccessToken->expires_at,
        );

        $replacement->accessToken->forceFill([
            'encrypted_secret' => Crypt::encryptString($replacement->plainTextToken),
        ])->save();

        $personalAccessToken->delete();

        return Redirect::back()
            ->with('success', 'Integration token rotated. The old token is no longer valid.')
            ->with('newIntegrationToken', $replacement->plainTextToken);
    }

    private function findIntegrationToken(int $token): PersonalAccessToken
    {
        $personalAccessToken = PersonalAccessToken::query()->findOrFail($token);

        abort_unless(in_array('integration:read', $personalAccessToken->abilities ?? [], true), 404);

        return $personalAccessToken;
    }
}
