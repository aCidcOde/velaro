<?php

/*
[Modulo: app/Services/Site]
@Author: André Gomes ( @acidcode )
@since 2026-09-05
Persiste o cadastro publico de lojista: protocolo, documentos, aceites de LGPD, usuario e triagem pendente.
*/

namespace App\Services\Site;

use App\Models\Reseller;
use App\Models\ResellerCnae;
use App\Models\ResellerConsent;
use App\Models\ResellerDocument;
use App\Models\ResellerStatusEvent;
use App\Models\ResellerVerification;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ResellerRegistrationService
{
    /**
     * Disco dos documentos: privado, nunca servido direto pela web.
     */
    public const DOCUMENT_DISK = 'local';

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

            $this->storeDocuments($reseller, $documents);
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

            return [$reseller, $this->createPreRegistrationUser($data)];
        });

        [$reseller, $user] = $created;

        // O e-mail de verificacao sai depois do commit: nao pode segurar a
        // conexao do banco durante o SMTP nem avisar sobre um cadastro que um
        // rollback ainda desfaria.
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
     * @param  array<string, UploadedFile>  $documents
     */
    private function storeDocuments(Reseller $reseller, array $documents): void
    {
        foreach ($documents as $type => $file) {
            $path = $file->store('reseller-documents/'.$reseller->protocol, self::DOCUMENT_DISK);

            ResellerDocument::create([
                'reseller_id' => $reseller->id,
                'type' => $type,
                'original_name' => $file->getClientOriginalName(),
                'disk' => self::DOCUMENT_DISK,
                'path' => is_string($path) ? $path : '',
                'size_bytes' => $file->getSize() ?: 0,
                'mime' => $file->getClientMimeType(),
            ]);
        }
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
     * Usuario de pre-cadastro: nasce sem `reseller_id`, que so e amarrado na
     * aprovacao (regra 1 da tela 1.7).
     *
     * @param  array<string, mixed>  $data
     */
    private function createPreRegistrationUser(array $data): User
    {
        return User::create([
            'name' => $this->stringOf($data, 'contact_name'),
            'email' => $this->stringOf($data, 'email'),
            'password' => $this->stringOf($data, 'password'),
            'phone' => $this->stringOf($data, 'whatsapp'),
            'reseller_id' => null,
        ]);
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
