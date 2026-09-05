<?php

/*
[Modulo: tests/Feature/Portal]
@Author: André Gomes ( @acidcode )
@since 2026-09-05
Prova que o painel e um so e muda por estagio, e que a excecao do painel nao abriu nenhuma das 18 rotas de negocio.
*/

namespace Tests\Feature\Portal;

use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderBatch;
use App\Models\Reseller;
use App\Models\ResellerDocument;
use App\Models\ResellerStatusEvent;
use App\Models\SupportTicket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Route as RoutedRoute;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * Um login, um painel.
 *
 * `/portal` passou a aceitar qualquer usuario vinculado a um revendedor, e o
 * conteudo muda conforme o estagio: pre-cadastro ve o acompanhamento da propria
 * solicitacao, reprovado ve o motivo e o caminho de volta, aprovado ve o
 * dashboard de sempre.
 *
 * A mudanca e assimetrica de proposito, e a metade que importa para a seguranca
 * e {@see test_lojista_em_pre_cadastro_recebe_403_em_todas_as_rotas_de_negocio()}:
 * sem ele, "so o painel afrouxou" seria uma afirmacao que nenhum teste consegue
 * derrubar.
 */
class JornadaDoLojistaTest extends TestCase
{
    use RefreshDatabase;

    public function test_pre_cadastro_abre_o_painel_e_ve_o_acompanhamento_da_solicitacao(): void
    {
        $reseller = Reseller::factory()->pending()->create([
            'protocol' => 'VEL-2026-0148',
            'legal_name' => 'Tomazelli Alianças Ltda.',
        ]);

        ResellerStatusEvent::create([
            'reseller_id' => $reseller->id,
            'from_status' => null,
            'to_status' => Reseller::STATUS_PENDING,
            'note' => 'Cadastro recebido pelo site.',
        ]);

        $resposta = $this->actingAs($this->lojista($reseller))->get(route('portal.dashboard'));

        $resposta->assertOk();
        // As etapas, a triagem automatica e a linha do tempo — o conteudo da tela
        // 1.6, agora dentro do painel.
        $resposta->assertSee('Sua solicitação');
        $resposta->assertSee('Etapas da habilitação');
        $resposta->assertSee('Aprovação final Velaro');
        $resposta->assertSee('Validação');
        $resposta->assertSee('Consulta de CNPJ');
        $resposta->assertSee('Linha do tempo da solicitação');
        $resposta->assertSee('Cadastro recebido pelo site.');
        $resposta->assertSee('VEL-2026-0148');
        $resposta->assertSee('Tomazelli Alianças Ltda.');

        // E nao ve o painel de quem opera.
        $resposta->assertDontSee('Dashboard do Lojista');
        $resposta->assertDontSee('Custo Velaro');
    }

    public function test_awaiting_info_abre_o_reenvio_de_documentos_com_o_pedido_da_equipe(): void
    {
        $reseller = Reseller::factory()->create(['status' => Reseller::STATUS_AWAITING_INFO]);

        ResellerStatusEvent::create([
            'reseller_id' => $reseller->id,
            'from_status' => Reseller::STATUS_PENDING,
            'to_status' => Reseller::STATUS_AWAITING_INFO,
            'note' => 'Contrato social ilegível na última página.',
        ]);

        $resposta = $this->actingAs($this->lojista($reseller))->get(route('portal.dashboard'));

        $resposta->assertOk();
        $resposta->assertSee('Reenvio de documentos');
        // A justificativa que o Master escreveu na tela 3.11 chega ao lojista: sem
        // ela ele veria um campo de upload sem saber o que anexar.
        $resposta->assertSee('Contrato social ilegível na última página.');
        // E a linha do tempo nomeia o evento em vez de dizer "Status atualizado".
        $resposta->assertSee('Informações adicionais solicitadas');
        $resposta->assertSee('name="'.ResellerDocument::TYPE_ARTICLES_OF_INCORPORATION.'"', false);
        $resposta->assertSee(route('site.solicitacao.documentos', ['reseller' => $reseller->protocol]), false);
    }

    public function test_pre_cadastro_nao_ve_o_bloco_de_reenvio(): void
    {
        // Regra 4 da tela 1.6: fora de `awaiting_info` o lojista nao reenvia
        // documento por conta propria.
        $reseller = Reseller::factory()->pending()->create();

        $this->actingAs($this->lojista($reseller))
            ->get(route('portal.dashboard'))
            ->assertOk()
            ->assertDontSee('Reenvio de documentos');
    }

    public function test_reprovado_ve_o_motivo_e_o_caminho_para_regularizar(): void
    {
        $reseller = Reseller::factory()->rejected()->create([
            'rejection_reason' => 'CNAE principal incompatível com o comércio de joias.',
        ]);

        $resposta = $this->actingAs($this->lojista($reseller))->get(route('portal.dashboard'));

        $resposta->assertOk();
        $resposta->assertSee('Cadastro reprovado');
        $resposta->assertSee('CNAE principal incompatível com o comércio de joias.');
        $resposta->assertSee('Como regularizar');
        $resposta->assertSee('Peça uma nova análise');
    }

    public function test_inativo_ve_o_caminho_da_reativacao(): void
    {
        $reseller = Reseller::factory()->inactive()->create();

        $resposta = $this->actingAs($this->lojista($reseller))->get(route('portal.dashboard'));

        $resposta->assertOk();
        $resposta->assertSee('Cadastro inativo');
        $resposta->assertSee('Peça a reativação');
        $resposta->assertSee('Seu login continua seu');
    }

    public function test_aprovado_continua_vendo_o_dashboard_de_sempre(): void
    {
        $reseller = Reseller::factory()->approved()->create(['trade_name' => 'Tomazelli Alianças']);

        $resposta = $this->actingAs($this->lojista($reseller))->get(route('portal.dashboard'));

        $resposta->assertOk();
        $resposta->assertSee('Dashboard do Lojista');
        $resposta->assertSee('Pedidos em andamento');
        // E nao ve a moldura do pre-cadastro.
        $resposta->assertDontSee('Etapas da habilitação');
    }

    /**
     * O menu mostra a jornada inteira, com o que ainda nao abriu desabilitado.
     */
    public function test_menu_lateral_mostra_os_itens_de_negocio_desabilitados_com_explicacao(): void
    {
        $reseller = Reseller::factory()->pending()->create();

        $resposta = $this->actingAs($this->lojista($reseller))->get(route('portal.dashboard'));

        $resposta->assertOk();
        // O item continua na lista — o lojista precisa ver o que o espera.
        $resposta->assertSee('Catálogo Revendedor');
        $resposta->assertSee('Financeiro');
        // Mas nao e link, e diz por que.
        $resposta->assertSee('nav__locked', false);
        $resposta->assertSee('Disponível quando seu cadastro for aprovado.');
        $resposta->assertDontSee('href="'.route('portal.catalogo').'"', false);
        $resposta->assertDontSee('href="'.route('portal.financeiro.index').'"', false);

        // O painel, esse, continua clicavel.
        $resposta->assertSee('href="'.route('portal.dashboard').'"', false);
    }

    public function test_menu_do_lojista_aprovado_nao_tranca_nada(): void
    {
        $reseller = Reseller::factory()->approved()->create();

        $resposta = $this->actingAs($this->lojista($reseller))->get(route('portal.dashboard'));

        $resposta->assertOk();
        $resposta->assertDontSee('nav__locked', false);
        $resposta->assertSee('href="'.route('portal.catalogo').'"', false);
    }

    /**
     * A metade da mudanca que nao pode escapar.
     *
     * Afrouxar o middleware do grupo inteiro abriria de uma vez o catalogo com o
     * custo B2B, os pedidos, os clientes finais, o financeiro e as regras de
     * preco a quem ainda nao foi aprovado. A excecao e nominal — vale para o
     * painel e para mais nada —, e este teste percorre TODAS as outras rotas do
     * ambiente, tiradas do proprio roteador, para que uma rota nova nasca coberta
     * sem ninguem lembrar de acrescenta-la aqui.
     */
    public function test_lojista_em_pre_cadastro_recebe_403_em_todas_as_rotas_de_negocio(): void
    {
        $reseller = Reseller::factory()->pending()->create();
        $lojista = $this->lojista($reseller);
        $parametros = $this->registrosDoProprioLojista($reseller);
        $rotas = $this->rotasDeNegocioDoPortal();

        // O numero e o contrato do ambiente: 19 rotas no portal, uma delas o
        // painel. Se ele mudar, alguem acrescentou ou moveu uma rota — e a
        // decisao de expo-la, ou nao, ao lojista em analise precisa ser tomada de
        // propósito, aqui.
        $this->assertCount(18, $rotas);

        foreach ($rotas as $rota) {
            $nome = (string) $rota->getName();
            $metodo = $this->metodoDe($rota);
            $url = route($nome, array_intersect_key($parametros, array_flip($rota->parameterNames())));

            $this->actingAs($lojista)
                ->call($metodo, $url)
                ->assertForbidden(sprintf('%s %s deveria responder 403 para lojista em pré-cadastro.', $metodo, $nome));
        }
    }

    /**
     * Reprovado e inativo passam pela mesma porta fechada.
     */
    public function test_reprovado_e_inativo_tambem_nao_alcancam_o_negocio_do_portal(): void
    {
        foreach ([Reseller::STATUS_REJECTED, Reseller::STATUS_INACTIVE] as $status) {
            $reseller = Reseller::factory()->create(['status' => $status]);
            $lojista = $this->lojista($reseller);

            $this->actingAs($lojista)->get(route('portal.catalogo'))->assertForbidden();
            $this->actingAs($lojista)->get(route('portal.financeiro.index'))->assertForbidden();
            $this->actingAs($lojista)->get(route('portal.dashboard'))->assertOk();
        }
    }

    /**
     * Sem vinculo nao ha painel: o `reseller.linked` pede o `reseller_id`, e uma
     * conta interna sem revendedor nao e um lojista.
     */
    public function test_usuario_sem_vinculo_nao_abre_o_painel(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('portal.dashboard'))
            ->assertForbidden();
    }

    private function lojista(Reseller $reseller): User
    {
        return User::factory()->forReseller($reseller)->create();
    }

    /**
     * Um registro de cada tipo ligado por rota, todos do PROPRIO lojista: assim o
     * route model binding resolve e a resposta que sobra e a do middleware. Com
     * registro de outro revendedor viria 404 pelo escopo, e o teste estaria
     * provando outra coisa.
     *
     * @return array<string, string>
     */
    private function registrosDoProprioLojista(Reseller $reseller): array
    {
        $cliente = Customer::factory()->forReseller($reseller)->create(['user_id' => null]);
        $lote = OrderBatch::factory()->open()->create(['reseller_id' => $reseller->getKey()]);

        $pedido = Order::factory()->forReseller($reseller)->create([
            'customer_id' => $cliente->getKey(),
            'batch_id' => $lote->getKey(),
            'user_id' => null,
        ]);

        $chamado = SupportTicket::factory()->create(['reseller_id' => $reseller->getKey()]);

        return [
            'customer' => (string) $cliente->getRouteKey(),
            'batch' => (string) $lote->getRouteKey(),
            'order' => (string) $pedido->public_number,
            'ticket' => (string) $chamado->code,
        ];
    }

    /**
     * Toda rota `portal.*` menos o painel, direto do roteador.
     *
     * @return list<RoutedRoute>
     */
    private function rotasDeNegocioDoPortal(): array
    {
        $rotas = [];

        foreach (Route::getRoutes()->getRoutesByName() as $nome => $rota) {
            if (str_starts_with($nome, 'portal.') && $nome !== 'portal.dashboard') {
                $rotas[] = $rota;
            }
        }

        return $rotas;
    }

    private function metodoDe(RoutedRoute $rota): string
    {
        foreach ($rota->methods() as $metodo) {
            if ($metodo !== 'HEAD') {
                return $metodo;
            }
        }

        return 'GET';
    }
}
