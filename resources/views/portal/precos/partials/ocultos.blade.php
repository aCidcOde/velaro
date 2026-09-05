{{--
[Modulo: resources/views/portal/precos/partials]
@Author: André Gomes ( @acidcode )
@since 2026-09-05
Repete no formulario os campos de preco que ele nao mostra, para nao zerar o que a outra metade da tela edita.
--}}
{{--
  A tela 2.7 tem dois formulários de escrita — "Configuração global" na coluna da
  esquerda e "Configuração rápida" na direita — e uma rota só (PUT /portal/precos).
  Formulário aninhado é HTML inválido, então cada um é independente e carrega os
  campos do outro como hidden: sem isso, salvar a margem global apagaria as três
  faixas da configuração rápida, e vice-versa.

  $ocultos = lista dos campos que ESTE formulário não exibe.
--}}
@foreach($ocultos as $campo)
  <input type="hidden" name="{{ $campo }}" value="{{ old($campo, $valores[$campo]) }}">
@endforeach
