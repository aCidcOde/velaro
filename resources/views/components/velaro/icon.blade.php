@props(['name', 'class' => 'ic', 'style' => null])
@php($path = config('velaro-icones.'.$name) ?? config('velaro-icones.info'))
<svg class="{{ $class }}" @if($style) style="{{ $style }}" @endif viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">{!! $path !!}</svg>
