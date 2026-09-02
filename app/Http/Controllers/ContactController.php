<?php

namespace App\Http\Controllers;

use App\Http\Requests\ContactRequest;
use App\Mail\ContactMessageMail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

class ContactController extends Controller
{
    public function create(): View
    {
        return view('contact');
    }

    public function store(ContactRequest $request): RedirectResponse
    {
        $payload = $request->validated();

        Mail::to(config('mail.from.address'))
            ->send(new ContactMessageMail(
                name: $payload['name'],
                email: $payload['email'],
                phone: $payload['phone'],
                content: $payload['message'],
            ));

        return redirect()
            ->route('public.contact')
            ->with('status', __('Mensagem enviada com sucesso. Vamos responder em breve.'));
    }
}
