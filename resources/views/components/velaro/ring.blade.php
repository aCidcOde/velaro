{{-- Imagem de par de alianças por feitio, como arquivo estático em
     public/images/aliancas (gerado de docs/mockups/_gen_rings.py). Até chegar a
     fotografia real, é o mesmo placeholder vetorial dos mockups. --}}
@props(['variant' => 'classica', 'thumb' => false, 'alt' => 'Par de alianças'])
@php($v = in_array($variant, ['classica','diamond','premium','urbana','personaliz','cravejada','bicolor','rose','branco','ouro','black','diamantada','fosca','trabalhada','conforto','quadrado','tricolor'], true) ? $variant : 'classica')
<img src="{{ asset('images/aliancas/'.$v.($thumb ? '-thumb' : '').'.svg') }}" alt="{{ $alt }}" loading="lazy" {{ $attributes }}>
