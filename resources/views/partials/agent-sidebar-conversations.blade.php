@php
    $initialConversations = $initialConversations ?? collect();
@endphp

<p id="conversationEmpty" class="px-4 py-3 text-xs text-gray-500 {{ $initialConversations->isNotEmpty() ? 'hidden' : '' }}">Nenhuma conversa recente.</p>
<div id="conversationList" class="space-y-1">
    @foreach ($initialConversations as $conversation)
        <div class="group flex items-start gap-1 rounded-lg px-2 py-1 transition hover:bg-gray-800/60">
            <button type="button"
                    data-conversation-id="{{ $conversation['id'] }}"
                    class="min-w-0 flex-1 text-left flex items-start gap-3 px-2 py-2 rounded-lg transition text-sm text-gray-400 hover:text-white">
                <i class="fa-regular fa-message text-gray-500 group-hover:text-white mt-0.5"></i>
                <div class="min-w-0 flex-1">
                    <p class="truncate text-current">{{ $conversation['title'] }}</p>
                    @if ($conversation['preview'])
                        <p class="mt-1 text-[11px] text-gray-500 group-hover:text-gray-300 truncate">{{ $conversation['preview'] }}</p>
                    @endif
                    @if ($conversation['status'] === 'waiting')
                        <span class="mt-1 inline-flex rounded-full bg-sky-500/10 px-2 py-0.5 text-[10px] font-medium uppercase tracking-wider text-sky-300">Aguardando</span>
                    @endif
                </div>
            </button>

            <button type="button"
                    class="mt-1 inline-flex h-8 w-8 flex-none items-center justify-center rounded-lg text-gray-500 transition hover:bg-gray-700 hover:text-white"
                    aria-label="Renomear conversa">
                <i class="fa-solid fa-pen text-[11px]"></i>
            </button>

            <button type="button"
                    class="mt-1 inline-flex h-8 w-8 flex-none items-center justify-center rounded-lg text-gray-500 transition hover:bg-red-500/20 hover:text-red-300"
                    aria-label="Apagar conversa">
                <i class="fa-solid fa-trash text-[11px]"></i>
            </button>
        </div>
    @endforeach
</div>
