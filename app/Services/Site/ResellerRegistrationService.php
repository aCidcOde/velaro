<?php

/*
[Modulo: app/Services/Site]
@Author: André Gomes ( @acidcode )
@since 2026-09-05
Persiste o cadastro publico de lojista: protocolo, documentos, aceites de LGPD, usuario e triagem pendente.
*/

namespace App\Services\Site;

use App\Mail\ResellerRegistrationReceivedMail;
use App\Models\NotificationLog;
use App\Models\Reseller;
use App\Models\ResellerCnae;
use App\Models\ResellerConsent;
use App\Models\ResellerStatusEvent;
use App\Models\ResellerVerification;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class ResellerRegistrationService
{
    /**
     * Disco dos documentos, mantido como apelido do valor real: quem grava e o
     * {@see ResellerDocumentStorage}, aqui e no reenvio da tela 1.6.
     */
    public const DOCUMENT_DISK = ResellerDocumentStorage::DISK;

    /**
     * Versao do texto legal vigente, gravada em cada aceite para prova de LGPD.
     * Quando o Master ganhar a tela de textos legais, isto vira um `settings`.
     */
    public const CONSENT_DOCUMENT_VERSION = '2026-09';

    /**
     * Aceite marcado na tela 1.4 => tipos gravados em `reseller_consents`. O terceiro
     * aceite cobre dois documentos distintos, entao rende duas linhas: cada documento
     * tem versao propria e precisa de prova separada.
     *
     * @var array<string, array<int, string>>
     */
    private const CONSENT_MAP = [
        'accept_business' => [ResellerConsent::TYPE_BUSINESS_DECLARATION],
        'accept_verification' => [ResellerConsent::TYPE_AUTOMATED_VERIFICATION],
        'accept_terms' => [ResellerConsent::TYPE_TERMS, ResellerConsent::TYPE_PRIVACY_POLICY],
    ];

    public function __construct(private readonly ResellerDocumentStorage $documents) {}

    /**
     * @param  array<string, mixed>  $data  payload ja validado pelo Form Request
     * @param  array<string, UploadedFile>  $documents  tipo de `reseller_documents` => arquivo
     * @param  array<int, array<string, mixed>>  $cnaes  CNAEs informados, quando houver
     */
    public function register(
        array $data,
        array $documents,
        array $cnaes,
        ?string $ipAddress,
        ?string $userAgent,
    ): Reseller {
        /** @var array{0: Reseller, 1: User} $created */
        $created = DB::transaction(function () use ($data, $documents, $cnaes, $ipAddress, $userAgent): array {
            $reseller = Reseller::create([
                'protocol' => $this->nextProtocol(),
                'legal_name' => $data['legal_name'] ?? null,
                'trade_name' => $data['trade_name'] ?? null,
                'cnpj' => $data['cnpj'] ?? null,
                'state_registration' => $data['state_registration'] ?? null,
                'contact_name' => $data['contact_name'] ?? null,
                'contact_cpf' => $data['contact_cpf'] ?? null,
                'email' => $data['email'] ?? null,
                'whatsapp' => $data['whatsapp'] ?? null,
                'postal_code' => $data['postal_code'] ?? null,
                'street' => $data['street'] ?? null,
                'street_number' => $data['street_number'] ?? null,
                'address_complement' => $data['address_complement'] ?? null,
                'district' => $data['district'] ?? null,
                'city' => $data['city'] ?? null,
                'state' => $data['state'] ?? null,
                'contact_source' => $data['contact_source'] ?? null,
                'registration_type' => Reseller::REGISTRATION_TYPE_AUTOMATIC,
                'notes' => $data['notes'] ?? null,
                'status' => Reseller::STATUS_PENDING,
            ]);

            $this->documents->store($reseller, $documents);
            $this->storeCnaes($reseller, $cnaes);
            $this->storeConsents($reseller, $data, $ipAddress, $userAgent);

            ResellerStatusEvent::create([
                'reseller_id' => $reseller->id,
                'from_status' => null,
                'to_status' => Reseller::STATUS_PENDING,
                'actor_id' => null,
                'note' => 'Cadastro recebido pelo site.',
            ]);

            // A triagem de CNPJ/CNAE nasce pendente: a consulta externa e assincrona
            // e nunca roda dentro do request (regra 4 da tela 1.4).
            ResellerVerification::create([
                'reseller_id' => $reseller->id,
                'status' => ResellerVerification::STATUS_PENDING,
                'documentacao_enviada' => $documents !== [],
                'checked_at' => null,
            ]);

            return [$reseller, $this->createPreRegistrationUser($data, $reseller)];
        });

        [$reseller, $user] = $created;

        // Os dois avisos saem depois do commit: nenhum pode segurar a conexao do
        // banco durante o SMTP nem falar de um cadastro que um rollback ainda
        // desfaria.
        //
        // As boas-vindas vao primeiro porque so encostam na fila. O `Registered`
        // dispara a verificacao de e-mail, que ainda fala com o SMTP dentro do
        // request: se o servidor de e-mail cair, o cadastro estoura ali — e o
        // aviso que conta ao lojista que ele ja tem login nao pode morrer junto.
        $this->sendRegistrationReceivedMail($reseller);

        event(new Registered($user));

        return $reseller;
    }

    /**
     * Protocolo no formato VEL-2026-0148: sequencial de quatro digitos por ano.
     */
    public function nextProtocol(): string
    {
        $prefix = 'VEL-'.now()->format('Y').'-';

        $last = Reseller::withTrashed()
            ->where('protocol', 'like', $prefix.'%')
            ->orderByDesc('protocol')
            ->lockForUpdate()
            ->value('protocol');

        $sequence = is_string($last) ? ((int) substr($last, -4)) + 1 : 1;

        return $prefix.str_pad((string) $sequence, 4, '0', STR_PAD_LEFT);
    }

    /**
     * @param  array<int, array<string, mixed>>  $cnaes
     */
    private function storeCnaes(Reseller $reseller, array $cnaes): void
    {
        foreach ($cnaes as $cnae) {
            $code = $cnae['code'] ?? null;

            if (! is_scalar($code) || (string) $code === '') {
                continue;
            }

            ResellerCnae::updateOrCreate(
                ['reseller_id' => $reseller->id, 'code' => (string) $code],
                [
                    'description' => $cnae['description'] ?? null,
                    'is_primary' => (bool) ($cnae['is_primary'] ?? false),
                    // `compatible` fica nulo: quem decide e a triagem automatica.
                    'compatible' => null,
                ],
            );
        }
    }

    /**
     * Grava um aceite por documento legal, sempre com data, IP, agente e versao do
     * texto — e o que a regra 2 da tela 1.4 exige como prova de LGPD.
     *
     * @param  array<string, mixed>  $data
     */
    private function storeConsents(Reseller $reseller, array $data, ?string $ipAddress, ?string $userAgent): void
    {
        foreach (self::CONSENT_MAP as $field => $types) {
            if (! filter_var($data[$field] ?? false, FILTER_VALIDATE_BOOLEAN)) {
                continue;
            }

            foreach ($types as $type) {
                ResellerConsent::create([
                    'reseller_id' => $reseller->id,
                    'type' => $type,
                    'granted' => true,
                    'document_version' => self::CONSENT_DOCUMENT_VERSION,
                    'granted_at' => now(),
                    'ip_address' => $ipAddress,
                    'user_agent' => $userAgent === null ? null : Str::limit($userAgent, 500, ''),
                ]);
            }
        }
    }

    /**
     * Usuario de pre-cadastro, ja amarrado a propria solicitacao.
     *
     * O vinculo `users.reseller_id` nasce aqui, junto com a senha que o lojista
     * escolheu na tela 1.4 — nao na aprovacao. A regra 1 da tela 1.7 fala do que
     * a aprovacao *libera*: o acesso de Parceiro Premium (`status = approved`),
     * nao a existencia do vinculo. O proprio prototipo da tela 1.6 imprime
     * "Login vinculado (contato@tomazelli.com.br)" como campo da solicitacao
     * ainda em pre-cadastro, ou seja, o vinculo ja existe antes da analise.
     *
     * Sem ele o usuario saia do cadastro com um login que nao levava a lugar
     * nenhum, e o acesso a propria solicitacao dependia de os dois e-mails
     * continuarem iguais. O portal segue fechado: `EnsureUserIsReseller` exige
     * `status = approved`, e ter `reseller_id` nao aprova ninguem.
     *
     * @param  array<string, mixed>  $data
     */
    private function createPreRegistrationUser(array $data, Reseller $reseller): User
    {
        return User::create([
            'name' => $this->stringOf($data, 'contact_name'),
            'email' => $this->stringOf($data, 'email'),
            'password' => $this->stringOf($data, 'password'),
            'phone' => $this->stringOf($data, 'whatsapp'),
            'reseller_id' => $reseller->id,
        ]);
    }

    /**
     * Boas-vindas do pre-cadastro, fora da transacao e fora do request: o
     * Mailable implementa `ShouldQueue`, entao `send()` empurra o envio para a
     * fila em vez de abrir SMTP no meio do POST do formulario publico.
     */
    private function sendRegistrationReceivedMail(Reseller $reseller): void
    {
        $recipient = (string) $reseller->email;

        if ($recipient === '') {
            return;
        }

        // A linha nasce em `pending` de proposito: o que aconteceu aqui foi a
        // entrada na fila. Quem carimba `sent`/`failed` e `provider_message_id`
        // e a confirmacao do provedor de envio, que ainda nao esta integrada —
        // gravar `sent_at` agora faria o log afirmar uma entrega que ninguem
        // verificou.
        NotificationLog::create([
            'type' => NotificationLog::TYPE_REGISTRATION_RECEIVED,
            'channel' => NotificationLog::CHANNEL_EMAIL,
            'recipient' => $recipient,
            'recipient_type' => NotificationLog::RECIPIENT_TYPE_RESELLER,
            'reseller_id' => $reseller->id,
            'status' => NotificationLog::STATUS_PENDING,
        ]);

        Mail::to($recipient)->send(new ResellerRegistrationReceivedMail($reseller));
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function stringOf(array $data, string $key): string
    {
        $value = $data[$key] ?? null;

        return is_scalar($value) ? (string) $value : '';
    }
}
