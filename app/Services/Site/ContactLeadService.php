<?php

/*
[Modulo: app/Services/Site]
@Author: André Gomes ( @acidcode )
@since 2026-09-05
Regra do Fale Conosco: grava lead com aceite LGPD, sem criar revendedor nem liberar acesso.
*/

namespace App\Services\Site;

use App\Models\ContactLead;
use Illuminate\Support\Str;

class ContactLeadService
{
    public function __construct(private readonly SiteContentService $conteudo) {}

    /**
     * Status inicial da fila comercial. A migration ja nasce com este default;
     * a gravacao repete o valor para nao depender dele.
     */
    public const STATUS_NEW = 'new';

    /**
     * Versao do texto de consentimento vigente, gravada junto do aceite como
     * prova — a mesma exigencia do cadastro de lojista.
     */
    public const CONSENT_DOCUMENT_VERSION = 'privacidade-2026-09';

    /**
     * Pagina de partida do lead. O default e a propria tela de contato.
     */
    public const DEFAULT_ORIGIN = 'contato';

    /**
     * Paginas do site que levam ao formulario. `origin` guarda de onde o lead veio
     * para a triagem saber com o que o contato comecou.
     *
     * @var list<string>
     */
    public const ORIGINS = ['home', 'sobre', 'catalogo', 'produto', 'cadastro', 'contato'];

    /**
     * Opcoes do select "Assunto" — chave estavel para a URL e o rotulo que vai
     * para o banco, na forma em que a fila comercial le.
     *
     * @var array<string, string>
     */
    public const SUBJECTS = [
        'condicoes-comerciais' => 'Condições comerciais e catálogo',
        'acompanhar-cadastro' => 'Acompanhar solicitação de cadastro',
        'suporte-lojista' => 'Suporte a lojista já aprovado',
        'prazo-producao' => 'Prazo de produção e entrega',
        'imprensa-parcerias' => 'Imprensa e parcerias',
        'outro' => 'Outro assunto',
    ];

    /**
     * @return array<string, string>
     */
    public function subjects(): array
    {
        return self::SUBJECTS;
    }

    /**
     * Canais diretos do topo da tela. Sao os mesmos valores do rodape do site,
     * lidos do grupo `contact` de `settings` com `is_public = true` — pelo mesmo
     * leitor que as demais telas usam, para nao haver duas formas do grupo. Sem
     * a chave propria de WhatsApp a celula cai no telefone comercial.
     *
     * @return array{telefone: string, whatsapp: string, email: string, horario: string}
     */
    public function channels(): array
    {
        $contato = $this->conteudo->contact();
        $telefone = $contato['telefone'] ?? '';

        return [
            'telefone' => $telefone,
            'whatsapp' => $contato['whatsapp'] ?? $telefone,
            'email' => $contato['email'] ?? '',
            'horario' => $contato['horario'] ?? '',
        ];
    }

    /**
     * Pagina de partida so entra no banco se estiver na lista; qualquer outra
     * coisa vira o default, porque `origin` alimenta a triagem e nao o visitante.
     */
    public function resolveOrigin(?string $origin): string
    {
        $origin = Str::of((string) $origin)->trim()->lower()->toString();

        return in_array($origin, self::ORIGINS, true) ? $origin : self::DEFAULT_ORIGIN;
    }

    /**
     * Assunto pre-selecionado por link (as chamadas das telas 1.1, 1.2 e 1.3
     * chegam com ele). Vazio quando o visitante ainda precisa escolher.
     */
    public function resolveSubject(?string $subject): string
    {
        $subject = Str::of((string) $subject)->trim()->lower()->toString();

        return array_key_exists($subject, self::SUBJECTS) ? $subject : '';
    }

    /**
     * Grava o contato como lead da fila comercial.
     *
     * O lead NAO cria revendedor, NAO cria usuario e NAO abre chamado: quem quer
     * revender vai para a tela 1.4 e quem ja e lojista abre chamado no Portal.
     *
     * @param  array{name: string, email: string, phone: string, company: string|null, subject: string, message: string, origin: string|null}  $payload
     */
    public function register(array $payload, ?string $ipAddress = null, ?string $userAgent = null): ContactLead
    {
        return ContactLead::create([
            'name' => $payload['name'],
            'email' => $payload['email'],
            'phone' => self::formatPhone($payload['phone']),
            'company' => $payload['company'],
            'subject' => self::SUBJECTS[$payload['subject']],
            'message' => $payload['message'],
            'origin' => $this->resolveOrigin($payload['origin']),
            'status' => self::STATUS_NEW,
            'handled_by' => null,
            'handled_at' => null,
            'consent_granted_at' => now(),
            'consent_document_version' => self::CONSENT_DOCUMENT_VERSION,
            'consent_ip_address' => $ipAddress,
            'consent_user_agent' => $userAgent,
        ]);
    }

    /**
     * Normaliza o telefone para a mascara (00) 00000-0000 da tela. O que nao
     * couber em 10 ou 11 digitos fica como veio, so aparado.
     */
    public static function formatPhone(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone) ?? '';

        // O visitante costuma colar o numero com o +55 do rodape junto.
        if (str_starts_with($digits, '55') && in_array(strlen($digits), [12, 13], true)) {
            $digits = substr($digits, 2);
        }

        if (strlen($digits) === 11) {
            return sprintf('(%s) %s-%s', substr($digits, 0, 2), substr($digits, 2, 5), substr($digits, 7, 4));
        }

        if (strlen($digits) === 10) {
            return sprintf('(%s) %s-%s', substr($digits, 0, 2), substr($digits, 2, 4), substr($digits, 6, 4));
        }

        return Str::limit(trim($phone), 30, '');
    }
}
