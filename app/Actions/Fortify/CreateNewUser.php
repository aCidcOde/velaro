<?php

namespace App\Actions\Fortify;

use App\Mail\WelcomeMail;
use App\Models\User;
use App\Support\DocumentInput;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules;

    /**
     * Validate and create a newly registered user.
     *
     * @param  array<string, string>  $input
     */
    public function create(array $input): User
    {
        $input = array_merge($input, [
            'document' => DocumentInput::normalize($input['document'] ?? null),
            'phone' => trim((string) ($input['phone'] ?? '')),
        ]);

        Validator::make($input, [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique(User::class),
            ],
            'phone' => ['required', 'string', 'max:20'],
            'document' => DocumentInput::documentRules(),
            'password' => $this->passwordRules(),
        ])->validate();

        $user = User::create([
            'name' => $input['name'],
            'email' => $input['email'],
            'phone' => $input['phone'],
            'document' => $input['document'],
            'password' => $input['password'],
        ]);

        Mail::to($user)->send(new WelcomeMail($user));

        return $user;
    }
}
