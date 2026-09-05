<?php

/*
[Modulo: app/Mail]
@Author: André Gomes ( @acidcode )
@since 2026-09-05
Aviso de boas-vindas do pre-cadastro: confirma o protocolo, diz que a analise ainda vem e entrega o acesso.
*/

namespace App\Mail;

use App\Models\Reseller;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * O lojista escolhe a senha na tela 1.4, entao ele sai do cadastro com um login
 * pronto — este e-mail e o que conta isso para ele. Ate aqui o cadastro
 * terminava em silencio: a tela 1.5 dizia "guarde seu e-mail e senha" e nada
 * chegava na caixa de entrada.
 *
 * `ShouldQueue` e o que a regra 2 da tela 1.7 pede ("aviso transacional sempre
 * via job"): o Mailable vai para a fila e o SMTP nunca segura o POST do
 * formulario publico.
 *
 * O texto nao promete prazo em dias. A tela 1.5 fala em "prazo de analise", mas
 * o protocolo transcrito na secao 5 do doc so promete "assim que houver
 * novidades, entraremos em contato" — numero nenhum. Escrever "48 horas" aqui
 * seria inventar compromisso que o produto nao assumiu.
 */
class ResellerRegistrationReceivedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public Reseller $reseller) {}

    public function envelope(): Envelope
    {
        // O protocolo vai no assunto: e o numero que o lojista precisa ter a mao
        // para falar com a equipe, e a busca da caixa de entrada acha por ele.
        return new Envelope(
            subject: __('Recebemos seu cadastro — protocolo :protocol', [
                'protocol' => (string) $this->reseller->protocol,
            ]),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.reseller-registration-received',
            with: [
                'reseller' => $this->reseller,
                'protocol' => (string) $this->reseller->protocol,
                'contactName' => (string) $this->reseller->contact_name,
                'email' => (string) $this->reseller->email,
                'loginUrl' => route('login'),
                'statusUrl' => route('site.solicitacao.status', ['reseller' => $this->reseller->protocol]),
            ],
        );
    }

    /**
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
