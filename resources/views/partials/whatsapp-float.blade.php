{{--
/*
[Modulo: resources/views/partials]
@Author: André Gomes ( @acidcode )
@since 2026-02-05
Botao flutuante de suporte via WhatsApp.
*/
--}}
<a
    class="whatsapp-float"
    href="https://wa.me/5511976019022"
    target="_blank"
    rel="noopener"
    aria-label="Falar com atendimento no WhatsApp"
>
    <picture>
        <source srcset="{{ asset('whatsapp-float.webp') }}" type="image/webp">
        <img
            src="{{ asset('whatsapp-float.png') }}"
            alt="WhatsApp"
            class="whatsapp-float__icon"
            loading="lazy"
            decoding="async"
            fetchpriority="low"
        >
    </picture>
</a>
