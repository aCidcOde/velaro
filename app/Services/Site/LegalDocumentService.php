<?php

/*
[Modulo: app/Services/Site]
@Author: André Gomes ( @acidcode )
@since 2026-09-05
Texto da Politica de Privacidade e dos Termos de Uso, versionado junto com o codigo.
*/

namespace App\Services\Site;

class LegalDocumentService
{
    /**
     * Versao vigente dos dois documentos. E ela que a tela 1.4 grava junto do
     * aceite do lojista, com data, hora e IP — por isso mora no codigo, e nao
     * em `settings`: mudar o texto e um deploy, com rastro no versionamento.
     */
    public const VERSION = '2.1';

    public const EFFECTIVE_FROM = '01/08/2026';

    public const UPDATED_AT = '01/08/2026';

    public const SCOPE = 'Lojistas com CNPJ e visitantes do site';

    public const DPO_EMAIL = 'privacidade@velaro.com.br';

    /**
     * Aviso do hero, igual nos dois documentos.
     */
    public const AUDIENCE_NOTE = 'Este texto se aplica à relação entre a Velaro e o lojista. O consumidor final não possui conta nesta plataforma.';

    public function __construct(private readonly SiteContentService $conteudo) {}

    /**
     * Barra de identificacao do documento (as cinco celulas do topo).
     *
     * @return list<array{icone: string, rotulo: string, valor: string}>
     */
    public function identity(): array
    {
        return [
            ['icone' => 'doc', 'rotulo' => 'Versão do documento', 'valor' => self::VERSION],
            ['icone' => 'calendar', 'rotulo' => 'Vigente desde', 'valor' => self::EFFECTIVE_FROM],
            ['icone' => 'refresh', 'rotulo' => 'Última atualização', 'valor' => self::UPDATED_AT],
            ['icone' => 'users', 'rotulo' => 'Aplica-se a', 'valor' => self::SCOPE],
            ['icone' => 'shield', 'rotulo' => 'Encarregado (DPO)', 'valor' => self::DPO_EMAIL],
        ];
    }

    /**
     * Linha "Versão 2.1 · vigente desde 01/08/2026" do hero.
     */
    public function stamp(): string
    {
        return sprintf('Versão %s · vigente desde %s', self::VERSION, self::EFFECTIVE_FROM);
    }

    /**
     * @return array{titulo: string, resumo: string, secoes: list<array{id: string, titulo: string, corpo: string}>}
     */
    public function privacyPolicy(): array
    {
        return [
            'titulo' => 'Política de Privacidade',
            'resumo' => 'Como a Velaro trata os dados pessoais de quem visita o site, de quem pede cadastro como lojista e dos clientes cadastrados pelo revendedor na plataforma B2B.',
            'secoes' => [
                ['id' => 'p1', 'titulo' => 'Quem somos e o que esta política cobre', 'corpo' => $this->controladora()],
                ['id' => 'p2', 'titulo' => 'Dados que tratamos', 'corpo' => <<<'HTML'
                    <p>Tratamos conjuntos diferentes de dados conforme o seu papel:</p>
                    <ul>
                      <li><strong>Visitante do site</strong> — dados de navegação, páginas visitadas e origem do acesso.</li>
                      <li><strong>Candidato a revendedor</strong> — razão social, nome fantasia, CNPJ, inscrição estadual, nome e CPF do responsável, e-mail, telefone/WhatsApp, endereço completo, origem do contato e os documentos enviados (contrato social, documento do sócio e comprovante de CNPJ).</li>
                      <li><strong>Revendedor aprovado</strong> — dados cadastrais acima, credenciais de acesso, histórico de pedidos, títulos financeiros e registros de suporte.</li>
                      <li><strong>Cliente final do revendedor</strong> — nome, CPF, contato e dados do pedido, cadastrados <em>pelo revendedor</em> na carteira dele.</li>
                    </ul>
                    HTML],
                ['id' => 'p3', 'titulo' => 'Bases legais e finalidades', 'corpo' => <<<'HTML'
                    <ul>
                      <li><strong>Execução de contrato</strong> — analisar o cadastro, liberar o acesso, processar pedidos, emitir documento fiscal e cobrar o lojista.</li>
                      <li><strong>Cumprimento de obrigação legal</strong> — guarda fiscal e contábil dos documentos emitidos.</li>
                      <li><strong>Legítimo interesse</strong> — prevenção a fraude, segurança da plataforma e registro de auditoria das ações sensíveis.</li>
                      <li><strong>Consentimento</strong> — comunicações de marketing e campanhas em datas especiais. É opcional, registrável e revogável a qualquer momento.</li>
                    </ul>
                    HTML],
                ['id' => 'p4', 'titulo' => 'Validação automática de CNPJ e CNAE', 'corpo' => <<<'HTML'
                    <p>Ao enviar o cadastro, o CNPJ informado é consultado em bases públicas para conferir situação cadastral, atividade econômica (CNAE) e compatibilidade com o segmento. O resultado dessa consulta é armazenado junto à sua solicitação.</p>
                    <p>A triagem automatizada apenas <strong>organiza</strong> a análise: a decisão de aprovar ou reprovar é sempre humana e fica registrada com justificativa. Você pode pedir a revisão dessa decisão.</p>
                    HTML],
                ['id' => 'p5', 'titulo' => 'O cliente final do revendedor', 'corpo' => <<<'HTML'
                    <p>Quando o revendedor cadastra um cliente na carteira dele ou registra um pedido de balcão, o <strong>revendedor é o controlador</strong> desses dados e a Velaro atua como <strong>operadora</strong>, tratando-os apenas para viabilizar o pedido, a produção e o aviso de retirada.</p>
                    <p>As mensagens enviadas ao consumidor saem em nome da loja do revendedor. A Velaro não usa a base de clientes de um revendedor para vender a outro nem para campanhas próprias.</p>
                    HTML],
                ['id' => 'p6', 'titulo' => 'Compartilhamento', 'corpo' => <<<'HTML'
                    <p>Compartilhamos dados apenas com quem é necessário para a operação, sempre sob contrato:</p>
                    <ul>
                      <li>provedores de hospedagem e infraestrutura em nuvem;</li>
                      <li>serviços de envio de e-mail e de mensagens por WhatsApp;</li>
                      <li>serviços de consulta cadastral de CNPJ e CNAE;</li>
                      <li>transportadoras, para entrega dos pedidos;</li>
                      <li>contabilidade e autoridades fiscais, no que a lei exige.</li>
                    </ul>
                    <p>Não vendemos dados pessoais e não cedemos sua base de clientes a terceiros.</p>
                    HTML],
                ['id' => 'p7', 'titulo' => 'Cookies', 'corpo' => <<<'HTML'
                    <p>Usamos cookies necessários (sessão, autenticação e segurança), que não podem ser desativados, e cookies de medição de audiência, que dependem do seu aceite no banner de cookies. A preferência pode ser alterada a qualquer momento.</p>
                    HTML],
                ['id' => 'p8', 'titulo' => 'Segurança e retenção', 'corpo' => <<<'HTML'
                    <p>Adotamos criptografia em trânsito, controle de acesso por perfil e registro de auditoria das ações sensíveis, incluindo login, aprovação de cadastro e alteração de preço.</p>
                    <ul>
                      <li>Cadastro reprovado: dados mantidos por 24 meses, para evitar reanálise indevida e comprovar a decisão.</li>
                      <li>Revendedor ativo: dados mantidos durante a relação comercial.</li>
                      <li>Documentos fiscais: mantidos pelo prazo legal de guarda.</li>
                      <li>Consentimento de marketing: revogável a qualquer momento, com registro da revogação.</li>
                    </ul>
                    HTML],
                ['id' => 'p9', 'titulo' => 'Seus direitos', 'corpo' => $this->direitos()],
                ['id' => 'p10', 'titulo' => 'Alterações desta política', 'corpo' => <<<'HTML'
                    <p>Esta política pode ser atualizada. Cada versão recebe número e data de vigência, e as alterações relevantes são comunicadas por e-mail e no primeiro acesso ao Portal. As versões anteriores ficam disponíveis mediante solicitação.</p>
                    HTML],
            ],
        ];
    }

    /**
     * @return array{titulo: string, resumo: string, secoes: list<array{id: string, titulo: string, corpo: string}>}
     */
    public function termsOfUse(): array
    {
        return [
            'titulo' => 'Termos de Uso',
            'resumo' => 'As regras da relação B2B entre a Velaro e o lojista: quem pode se cadastrar, como funcionam pedidos, preços, pagamento, entrega e a vitrine white label.',
            'secoes' => [
                ['id' => 't1', 'titulo' => 'Objeto e aceitação', 'corpo' => $this->objeto()],
                ['id' => 't2', 'titulo' => 'Quem pode usar a plataforma', 'corpo' => <<<'HTML'
                    <p>O acesso é restrito a <strong>pessoas jurídicas com CNPJ ativo</strong> e atividade econômica compatível com o segmento de joias e alianças — joalherias, lojas de alianças e revendedores formalizados.</p>
                    <p>O <strong>consumidor final não possui conta</strong>: ele não compra, não paga e não se cadastra na Velaro. Ele existe na plataforma apenas como cliente vinculado à carteira do revendedor.</p>
                    HTML],
                ['id' => 't3', 'titulo' => 'Cadastro, aprovação e credenciais', 'corpo' => <<<'HTML'
                    <ul>
                      <li>O cadastro passa por validação automática de CNPJ e CNAE e por aprovação final da equipe Velaro.</li>
                      <li>A Velaro pode recusar cadastro sem que isso gere direito a indenização, informando o motivo.</li>
                      <li>As credenciais são pessoais e intransferíveis; o lojista responde pelo uso feito por sua equipe.</li>
                      <li>É obrigação do lojista manter dados cadastrais e documentos atualizados.</li>
                    </ul>
                    HTML],
                ['id' => 't4', 'titulo' => 'Catálogo, preços e condições comerciais', 'corpo' => <<<'HTML'
                    <p>O catálogo público não exibe preço. Preço de fábrica, faixa de desconto, pedido mínimo e prazo de pagamento são exibidos apenas dentro do Portal, para lojistas aprovados, e são <strong>confidenciais</strong>.</p>
                    <p>Fotos e ilustrações do catálogo são referenciais. Pequenas variações de acabamento, peso e tonalidade são próprias da fabricação artesanal e não caracterizam defeito.</p>
                    <p>A Velaro pode alterar preços e condições a qualquer tempo. Pedido já confirmado mantém o preço registrado no momento da confirmação.</p>
                    HTML],
                ['id' => 't5', 'titulo' => 'Pedidos, produção e prazos', 'corpo' => <<<'HTML'
                    <ul>
                      <li>O pedido é confirmado após a aprovação do pagamento ou a liberação de crédito do lojista.</li>
                      <li>Peças de catálogo têm prazo de até 7 dias úteis de produção; peças com gravação ou aro fora da grade, até 12 dias úteis.</li>
                      <li>Prazos de produção não incluem o tempo de transporte, informado no momento do envio.</li>
                      <li>Cancelamento é possível enquanto o pedido não entrar em produção.</li>
                    </ul>
                    HTML],
                ['id' => 't6', 'titulo' => 'Pagamento e faturamento', 'corpo' => <<<'HTML'
                    <p>A relação financeira desta plataforma é <strong>exclusivamente Velaro → lojista</strong>, por Pix, boleto ou transferência, com emissão de nota fiscal contra o CNPJ cadastrado.</p>
                    <p>A plataforma <strong>não processa</strong> pagamento do consumidor final: nem Pix, nem cartão, nem link de pagamento. O recebimento do consumidor é feito diretamente pelo revendedor, fora desta plataforma, e é responsabilidade exclusiva dele.</p>
                    <p>O atraso no pagamento sujeita o lojista a juros e multa contratuais e pode suspender novos pedidos.</p>
                    HTML],
                ['id' => 't7', 'titulo' => 'Entrega e retirada', 'corpo' => <<<'HTML'
                    <p>A entrega é feita no endereço cadastrado do lojista ou por retirada na fábrica, conforme escolhido no pedido. A conferência do volume no recebimento é obrigatória e divergências devem ser comunicadas em até 48 horas, com registro fotográfico.</p>
                    HTML],
                ['id' => 't8', 'titulo' => 'Vitrine white label e responsabilidades do revendedor', 'corpo' => <<<'HTML'
                    <p>A Velaro disponibiliza ao revendedor uma vitrine personalizável com a marca dele. Sobre essa vitrine:</p>
                    <ul>
                      <li>o <strong>preço ao consumidor é definido pelo revendedor</strong>, que responde por sua adequação legal;</li>
                      <li>a venda ao consumidor final é celebrada entre consumidor e revendedor, sem participação da Velaro;</li>
                      <li>o revendedor é o controlador dos dados dos clientes que cadastra e responde pelo consentimento deles;</li>
                      <li>o revendedor não pode apresentar a Velaro como vendedora ao consumidor, nem usar a marca Velaro sem autorização escrita.</li>
                    </ul>
                    HTML],
                ['id' => 't9', 'titulo' => 'Gravação e personalização', 'corpo' => <<<'HTML'
                    <p>Peças com gravação são personalizadas e produzidas sob encomenda. Por isso, salvo defeito de fabricação, <strong>não são passíveis de troca, devolução ou arrependimento</strong>. O texto gravado é de responsabilidade de quem o informa, e o limite de caracteres é o exibido no momento do pedido.</p>
                    HTML],
                ['id' => 't10', 'titulo' => 'Garantia, trocas e assistência', 'corpo' => <<<'HTML'
                    <p>As peças têm 12 meses de garantia contra defeito de fabricação, contados da data de emissão da nota fiscal. A garantia não cobre desgaste natural, amassamento, riscos, contato com produtos químicos, redimensionamento por terceiros ou uso indevido.</p>
                    <p>A solicitação é aberta pelo lojista no Portal, no menu de Suporte, com fotos e número do pedido.</p>
                    HTML],
                ['id' => 't11', 'titulo' => 'Propriedade intelectual', 'corpo' => <<<'HTML'
                    <p>Marca, logotipo, catálogo, fotos, textos e o software desta plataforma pertencem à Velaro. O revendedor aprovado recebe licença limitada e revogável para usar imagens do catálogo na divulgação dos produtos que revende, sem direito a sublicenciar, modificar a marca ou registrar domínio com o nome Velaro.</p>
                    HTML],
                ['id' => 't12', 'titulo' => 'Suspensão e encerramento', 'corpo' => <<<'HTML'
                    <p>A Velaro pode suspender ou encerrar o acesso em caso de inadimplência, informação cadastral falsa, uso indevido da marca, compartilhamento de preço confidencial ou violação destes Termos. O encerramento não afeta pedidos já pagos nem as obrigações financeiras já constituídas.</p>
                    HTML],
                ['id' => 't13', 'titulo' => 'Limitação de responsabilidade', 'corpo' => <<<'HTML'
                    <p>A Velaro não responde por lucros cessantes do revendedor, por indisponibilidade decorrente de caso fortuito ou força maior, nem por atos praticados na relação entre o revendedor e o consumidor final, inclusive preço, prazo prometido no balcão e formas de recebimento.</p>
                    HTML],
                ['id' => 't14', 'titulo' => 'Alterações, lei aplicável e foro', 'corpo' => $this->foro()],
            ],
        ];
    }

    /**
     * Identificacao da controladora — razao social, CNPJ e sede saem de
     * `settings` do grupo `company`, para nao existirem duas versoes do
     * mesmo dado no produto.
     */
    private function controladora(): string
    {
        $empresa = $this->conteudo->company();

        return sprintf(
            <<<'HTML'
                <p>A %s, CNPJ %s, com sede em %s, é a controladora dos dados pessoais tratados neste site e na plataforma B2B. Esta política explica quais dados coletamos, por que coletamos, com quem compartilhamos e como você exerce seus direitos, nos termos da Lei nº 13.709/2018 (LGPD).</p>
                <p>A Velaro é <strong>fábrica e fornecedora</strong>: vendemos exclusivamente a lojistas com CNPJ. O consumidor final não cria conta, não compra e não paga nesta plataforma.</p>
                HTML,
            e($empresa['razao_social'] ?? 'Velaro Alianças Ltda.'),
            e($empresa['cnpj'] ?? ''),
            e($empresa['endereco'] ?? ''),
        );
    }

    private function direitos(): string
    {
        return sprintf(
            <<<'HTML'
                <p>A LGPD garante a você confirmação do tratamento, acesso, correção, anonimização, portabilidade, eliminação dos dados tratados com base em consentimento, informação sobre compartilhamentos e revisão de decisões automatizadas.</p>
                <p>Para exercer qualquer desses direitos, escreva para <strong>%s</strong>. Respondemos em até 15 dias.</p>
                HTML,
            e(self::DPO_EMAIL),
        );
    }

    private function objeto(): string
    {
        $empresa = $this->conteudo->company();

        return sprintf(
            <<<'HTML'
                <p>Estes Termos regem o uso do site institucional, do catálogo público, da plataforma B2B (Portal do Lojista) e da vitrine white label disponibilizada ao revendedor pela %s.</p>
                <p>Ao enviar o cadastro de lojista ou ao acessar o Portal, você declara que leu e aceita estes Termos na versão vigente. O aceite é registrado com data, hora, IP e número da versão.</p>
                HTML,
            e($empresa['razao_social'] ?? 'Velaro Alianças Ltda.'),
        );
    }

    private function foro(): string
    {
        $empresa = $this->conteudo->company();

        return sprintf(
            <<<'HTML'
                <p>Estes Termos podem ser alterados. A nova versão recebe número e data de vigência e é comunicada no primeiro acesso ao Portal. O uso após a comunicação implica aceite.</p>
                <p>Aplica-se a lei brasileira. Fica eleito o foro da comarca de %s para dirimir controvérsias, com renúncia a qualquer outro.</p>
                HTML,
            e($empresa['endereco'] ?? 'Ribeirão Preto/SP'),
        );
    }
}
