<!DOCTYPE html>
<html lang="pt-br" class="h-full bg-gray-50">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>CodaFácil IA - CodaFácil</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @include('partials.favicons')
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer">
    @vite(['resources/css/agente.css', 'resources/js/agente.js'])
</head>

<body class="h-full flex overflow-hidden font-sans"
      data-agent-page="chat"
      data-agent-endpoint="{{ route('agent.message') }}"
      data-agent-fallback-endpoint="{{ route('agent.debug') }}"
      data-agent-latest-endpoint="{{ route('agent.latest-message') }}"
      data-agent-chat-url="{{ route('agent.dashboard') }}"
      data-agent-files-url="{{ route('agent.files') }}"
      data-agent-file-upload-endpoint="{{ route('agent.uploads.store') }}"
      data-agent-uploads-endpoint="{{ route('agent.uploads.index') }}"
      data-agent-upload-delete-base="{{ url('agente/arquivos/uploads') }}"
      data-agent-conversation-base="{{ url('agente/conversa') }}"
      data-agent-conversations-endpoint="{{ route('agent.conversations') }}">

    <div id="sidebarOverlay" class="fixed inset-0 bg-black/40 z-40 hidden md:hidden"></div>
    <div id="settingsOverlay" class="fixed inset-0 bg-black/40 z-[60] hidden"></div>

    <div id="settingsModal"
         class="fixed left-1/2 top-1/2 z-[70] hidden w-[92%] max-w-lg -translate-x-1/2 -translate-y-1/2 rounded-2xl bg-white shadow-2xl border border-gray-200">
        <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100">
            <div class="flex items-center gap-3">
                <div class="h-9 w-9 rounded-xl bg-gray-900 text-white flex items-center justify-center">
                    <i class="fa-solid fa-gear"></i>
                </div>
                <div>
                    <p class="text-sm font-semibold text-gray-900">Configurações</p>
                    <p class="text-xs text-gray-500">Preferências da interface (somente UI)</p>
                </div>
            </div>
            <button id="closeSettingsBtn" class="h-9 w-9 rounded-xl hover:bg-gray-100 text-gray-600 transition" aria-label="Fechar configurações">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <div class="px-5 py-4 space-y-4">
            <div class="rounded-xl border border-gray-200 p-4">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="text-sm font-medium text-gray-900">Sugestões rápidas</p>
                        <p class="text-xs text-gray-500 mt-1">Exibir chips acima do campo de mensagem.</p>
                    </div>
                    <label class="inline-flex items-center cursor-pointer">
                        <input id="toggleChips" type="checkbox" class="sr-only" checked>
                        <span class="relative w-10 h-6 bg-sky-500 rounded-full transition">
                            <span id="toggleChipsDot" class="absolute left-1 top-1 w-4 h-4 bg-white rounded-full shadow transition"></span>
                        </span>
                    </label>
                </div>
            </div>

            <div class="rounded-xl border border-gray-200 p-4">
                <p class="text-sm font-medium text-gray-900">Tamanho do texto</p>
                <p class="text-xs text-gray-500 mt-1">Ajusta apenas a visualização local.</p>
                <div class="mt-3 flex flex-wrap gap-2">
                    <button data-font="sm" class="fontBtn px-3 py-2 rounded-xl border border-gray-200 text-sm hover:bg-gray-50 transition">Pequeno</button>
                    <button data-font="base" class="fontBtn px-3 py-2 rounded-xl border border-gray-200 text-sm hover:bg-gray-50 transition">Padrão</button>
                    <button data-font="lg" class="fontBtn px-3 py-2 rounded-xl border border-gray-200 text-sm hover:bg-gray-50 transition">Grande</button>
                </div>
            </div>

            <div class="rounded-xl border border-gray-200 p-4">
                <p class="text-sm font-medium text-gray-900">Atalhos</p>
                <div class="mt-2 text-xs text-gray-600 space-y-1">
                    <p><span class="font-semibold">Enter</span> envia</p>
                    <p><span class="font-semibold">Shift + Enter</span> quebra linha</p>
                    <p><span class="font-semibold">Esc</span> fecha menus</p>
                </div>
            </div>
        </div>

        <div class="px-5 py-4 border-t border-gray-100 flex items-center justify-end gap-2">
            <button id="resetUiBtn" class="px-4 py-2 rounded-xl border border-gray-200 text-sm hover:bg-gray-50 transition">
                Restaurar
            </button>
            <button id="closeSettingsBtn2" class="px-4 py-2 rounded-xl bg-sky-500 text-white text-sm hover:bg-sky-600 transition">
                Fechar
            </button>
        </div>
    </div>

    <aside id="sidebar"
           class="fixed md:static inset-y-0 left-0 z-50 w-80 bg-gray-900 flex flex-col shrink-0
                  transform -translate-x-full md:translate-x-0 transition-transform duration-200 ease-out
                  md:flex">

        <div class="p-4 flex items-center gap-2">
            <button id="closeSidebarBtn"
                    class="md:hidden text-gray-300 hover:text-white transition"
                    aria-label="Fechar menu">
                <i class="fa-solid fa-xmark text-lg"></i>
            </button>

            <button id="newChatBtn"
                    class="w-full flex items-center justify-center gap-3 px-4 py-3 border border-gray-700 rounded-lg text-white hover:bg-gray-800 transition text-sm">
                <i class="fa-solid fa-plus"></i> Nova Conversa
            </button>
        </div>

        <div class="flex-1 overflow-y-auto px-2 custom-scrollbar">
            <p class="px-4 py-2 text-xs font-semibold text-gray-500 uppercase tracking-wider">Recentes</p>
            @include('partials.agent-sidebar-conversations', ['initialConversations' => $initialConversations ?? collect()])
        </div>

        <div class="p-4 border-t border-gray-800 space-y-2">
            <a href="{{ route('agent.files') }}"
               class="w-full flex items-center gap-3 text-sm px-3 py-2 rounded-lg transition {{ request()->routeIs('agent.files') ? 'bg-gray-800 text-white' : 'text-gray-400 hover:text-white hover:bg-gray-800' }}">
                <i class="fa-regular fa-file-pdf"></i> Enviar Arquivos
            </a>

            <button id="openSettingsBtn" class="w-full flex items-center gap-3 text-gray-400 hover:text-white hover:bg-gray-800 transition text-sm px-3 py-2 rounded-lg">
                <i class="fa-solid fa-gear"></i> Configurações
            </button>

            
        </div>
    </aside>

    <div class="flex-1 flex flex-col min-w-0 chat-bg">

        <header class="bg-white border-b border-gray-200 py-4 px-4 md:px-6 flex justify-between items-center z-10">
            <div class="flex items-center gap-3 md:gap-4 min-w-0">
                <button id="openSidebarBtn" class="md:hidden text-gray-600 mr-1" aria-label="Abrir menu">
                    <i class="fa-solid fa-bars text-xl"></i>
                </button>

                <div class="shrink-0 rounded-lg bg-gray-900 px-3 py-2">
                    <img src="{{ asset('logo.webp') }}"
                         alt="Logo CodaFácil" class="h-6 md:h-7 w-auto">
                </div>

                <div class="h-6 w-px bg-gray-300 hidden sm:block mx-2"></div>

                <h1 class="text-lg md:text-xl font-semibold text-gray-700 hidden sm:block">CodaFácil IA</h1>
            </div>

            @auth
                @include('partials.user-menu', ['variant' => 'agent'])
            @else
                <a href="{{ route('login') }}" class="flex items-center gap-2 px-4 py-2 text-sm font-medium text-gray-700 hover:text-gray-900 hover:bg-gray-100 rounded-lg transition">
                    <i class="fa-regular fa-user"></i> Entrar
                </a>
            @endauth
        </header>

        <main class="flex-1 overflow-y-auto p-4 md:p-8">
            <div class="max-w-3xl mx-auto">

                <div id="emptyState" class="py-10 md:py-16 text-center">
                    <img src="{{ asset('logo.webp') }}"
                         alt="Avatar do agente"
                         width="150"
                         height="150"
                         style="width: 150px; height: 150px;"
                         class="mx-auto rounded-2xl object-cover shadow-md">
                    <h2 class="mt-4 text-lg font-semibold text-gray-800">Olá, eu sou o CodaFácil IA.</h2>
                    <p class="mt-2 text-sm text-gray-500">No que posso te ajudar?</p>
                </div>

                <div id="chatStatusNotice" class="mx-auto hidden max-w-3xl rounded-2xl border border-sky-200 bg-sky-50 px-4 py-3 text-sm text-sky-900">
                    O chat está aguardando a resposta do agente. Você poderá enviar outra mensagem quando o processamento for concluído.
                </div>

                <div id="chatList" class="space-y-6"></div>

            </div>
        </main>

        <footer class="p-4 bg-white border-t border-gray-200">
            <div class="max-w-3xl mx-auto">
                <form id="composerForm" class="relative group">
                    <textarea id="composer"
                              rows="1"
                              placeholder="Escreva sua mensagem..."
                              class="w-full pl-4 pr-14 py-3.5 bg-gray-100 border-none rounded-2xl focus:ring-2 focus:ring-sky-500 focus:bg-white transition resize-none shadow-inner text-sm leading-relaxed
                                     max-h-40 overflow-y-auto"></textarea>

                    <button type="submit"
                            class="absolute right-2.5 bottom-2 bg-sky-500 hover:bg-sky-600 text-white w-9 h-9 rounded-xl flex items-center justify-center transition shadow-lg"
                            aria-label="Enviar">
                        <i class="fa-solid fa-paper-plane text-xs"></i>
                    </button>
                </form>

                <div class="text-center mt-2 space-y-1">
                    <p class="text-center text-[9px] text-gray-400 uppercase tracking-widest">
                        Agente local assíncrono da base.
                    </p>
                </div>
            </div>
        </footer>
    </div>
</body>
</html>
