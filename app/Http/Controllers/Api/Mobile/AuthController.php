<?php

/*
[App/Http/Controllers/Api/Mobile]
@Author: André Gomes ( @acidcode )
@since 2026-02-09
Gerencia autenticacao mobile (cadastro, login, recuperacao e encerramento de sessao token).
*/

namespace App\Http\Controllers\Api\Mobile;

use App\Actions\Fortify\CreateNewUser;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Mobile\Auth\ForgotPasswordRequest;
use App\Http\Requests\Api\Mobile\Auth\LoginRequest;
use App\Http\Requests\Api\Mobile\Auth\RegisterRequest;
use App\Http\Requests\Api\Mobile\Auth\ResetPasswordRequest;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function register(RegisterRequest $request, CreateNewUser $createNewUser): JsonResponse
    {
        /** @var array{name: string, email: string, phone: string, document: string, password: string, password_confirmation: string} $validated */
        $validated = $request->validated();
        $user = $createNewUser->create($validated);

        [$plainToken, $expiresAt] = $this->issueAccessToken($user);

        return response()->json([
            'message' => 'Cadastro realizado com sucesso.',
            'access_token' => $plainToken,
            'token_type' => 'Bearer',
            'expires_at' => $expiresAt?->toIso8601String(),
            'user' => $this->userPayload($user),
        ], Response::HTTP_CREATED);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        /** @var array{email: string, password: string, device_name?: string} $validated */
        $validated = $request->validated();

        $user = User::query()->where('email', $validated['email'])->first();

        if (! $user || ! Hash::check($validated['password'], $user->password)) {
            return response()->json([
                'message' => 'Credenciais invalidas.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        if ($user->isBlocked()) {
            return response()->json([
                'message' => User::BLOCKED_MESSAGE,
            ], Response::HTTP_FORBIDDEN);
        }

        [$plainToken, $expiresAt] = $this->issueAccessToken($user);

        return response()->json([
            'message' => 'Login realizado com sucesso.',
            'access_token' => $plainToken,
            'token_type' => 'Bearer',
            'expires_at' => $expiresAt?->toIso8601String(),
            'user' => $this->userPayload($user),
        ]);
    }

    public function forgotPassword(ForgotPasswordRequest $request): JsonResponse
    {
        /** @var array{email: string} $validated */
        $validated = $request->validated();

        Password::broker()->sendResetLink([
            'email' => $validated['email'],
        ]);

        return response()->json([
            'message' => 'Se o e-mail existir, o link de recuperacao foi enviado.',
        ]);
    }

    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        /** @var array{email: string, token: string, password: string, password_confirmation: string} $validated */
        $validated = $request->validated();

        $status = Password::reset(
            [
                'email' => $validated['email'],
                'token' => $validated['token'],
                'password' => $validated['password'],
                'password_confirmation' => $validated['password_confirmation'],
            ],
            function (User $user, string $password): void {
                $user->forceFill([
                    'password' => Hash::make($password),
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($user));
            }
        );

        if ($status !== Password::PASSWORD_RESET) {
            return response()->json([
                'message' => __($status),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return response()->json([
            'message' => 'Senha redefinida com sucesso.',
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        return response()->json([
            'user' => $this->userPayload($user),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $user->forceFill([
            'api_token_hash' => null,
            'api_token_expires_at' => null,
        ])->save();

        return response()->json([
            'message' => 'Sessao encerrada com sucesso.',
        ]);
    }

    /**
     * @return array{0: string, 1: Carbon|null}
     */
    protected function issueAccessToken(User $user): array
    {
        $plainToken = Str::random(80);
        $expiresAt = now()->addDays(30);

        $user->forceFill([
            'api_token_hash' => hash('sha256', $plainToken),
            'api_token_expires_at' => $expiresAt,
        ])->save();

        return [$plainToken, $expiresAt];
    }

    /**
     * @return array{
     *     id: int,
     *     name: string,
     *     email: string,
     *     phone: string|null,
     *     document: string|null,
     *     is_admin: bool,
     *     email_verified_at: string|null
     * }
     */
    protected function userPayload(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'document' => $user->document,
            'is_admin' => (bool) $user->is_admin,
            'email_verified_at' => $user->email_verified_at?->toIso8601String(),
        ];
    }
}
