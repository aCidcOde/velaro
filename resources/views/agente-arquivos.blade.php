<!DOCTYPE html>
<html lang="pt-br" class="h-full bg-gray-50">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>CodaFácil IA - Envio de Arquivos</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @include('partials.favicons')
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer">
    @vite(['resources/css/agente.css', 'resources/js/agente.js'])
</head>

<body class="h-full flex overflow-hidden font-sans"
      data-agent-page="files"
      data-agent-endpoint="{{ route('agent.message') }}"
      data-agent-fallback-endpoint="{{ route('agent.debug') }}"
      data-agent-latest-endpoint="{{ route('agent.latest-message') }}"
      data-agent-chat-url="{{ route('agent.dashboard') }}"
      data-agent-files-url="{{ route('agent.files') }}"
      data-agent-file-upload-endpoint="{{ route('agent.uploads.store') }}"
      data-agent-uploads-endpoint="{{ route('agent.uploads.index') }}"
      data-agent-upload-delete-base="{{ url('agente/arquivos/uploads') }}"
      data-agent-upload-max-file-bytes="{{ (int) ($uploadLimits['max_file_bytes'] ?? 0) }}"
      data-agent-upload-max-request-bytes="{{ (int) ($uploadLimits['max_request_bytes'] ?? 0) }}"
      data-agent-upload-max-file-label="{{ $uploadLimits['max_file_label'] ?? '' }}"
      data-agent-upload-max-request-label="{{ $uploadLimits['max_request_label'] ?? '' }}"
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

            <a href="{{ route('agent.dashboard') }}"
               id="newChatBtn"
               class="w-full flex items-center justify-center gap-3 px-4 py-3 border border-gray-700 rounded-lg text-white hover:bg-gray-800 transition text-sm">
                <i class="fa-solid fa-plus"></i> Nova Conversa
            </a>
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
            @php
                $initialUploadsCollection = $initialUploads ?? collect();
            @endphp
            <div class="mx-auto flex w-full max-w-5xl flex-col gap-6">
                <div class="rounded-[2rem] border border-white/60 bg-white/90 p-6 shadow-sm backdrop-blur md:p-8">
                    <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                        <div class="max-w-2xl">
                            <p class="text-xs font-semibold uppercase tracking-[0.28em] text-sky-600">Upload de Documentos</p>
                            <h2 class="mt-2 text-2xl font-semibold text-gray-900 md:text-3xl">Central de Documentos</h2>
                            <p class="mt-3 text-sm leading-6 text-gray-600 md:text-base">
                                Selecione um ou mais PDFs para validar o fluxo de upload local do agente. 
                            </p>
                        </div>
                       
                    </div>
                </div>

                <div class="grid gap-6 lg:grid-cols-[minmax(0,1.5fr)_minmax(22rem,1fr)]">
                    <section class="rounded-[2rem] border border-dashed border-sky-300 bg-white p-6 shadow-sm md:p-8">
                        <div id="fileDropzone" class="flex min-h-[320px] flex-col items-center justify-center rounded-[1.5rem] border border-dashed border-gray-300 bg-gradient-to-br from-sky-50 via-white to-gray-50 px-6 py-10 text-center transition">
                            <div class="flex h-16 w-16 items-center justify-center rounded-2xl bg-gray-900 text-white shadow-lg">
                                <i class="fa-regular fa-file-pdf text-2xl"></i>
                            </div>
                            <h3 class="mt-5 text-xl font-semibold text-gray-900">Arraste PDFs para esta area</h3>
                            <p class="mt-3 max-w-xl text-sm leading-6 text-gray-600">
                                Voce tambem pode selecionar arquivos manualmente. Assim que o envio for aceito pelo servidor, o restante segue em background no Velaro IA.
                            </p>
                            <p class="mt-3 max-w-xl text-xs leading-6 text-gray-500">
                                Limite atual: ate {{ $uploadLimits['max_file_label'] ?? '200 MB' }} por PDF e ate {{ $uploadLimits['max_request_label'] ?? '200 MB' }} por envio.
                            </p>

                            <div class="mt-6 flex flex-wrap justify-center gap-3">
                                <button id="filePickerTrigger" type="button" class="rounded-xl bg-gray-900 px-5 py-3 text-sm font-medium text-white transition hover:bg-black">
                                    Selecionar PDFs
                                </button>
                                <span class="inline-flex items-center rounded-xl border border-gray-200 px-4 py-3 text-sm text-gray-500">
                                    Formato: PDF
                                </span>
                            </div>

                            <p id="fileUploadNotice" class="mt-4 hidden max-w-xl rounded-2xl border px-4 py-3 text-sm"></p>

                            <input id="filePickerInput" type="file" accept="application/pdf,.pdf" multiple class="hidden">
                        </div>
                    </section>

                    <aside class="rounded-[2rem] border border-gray-200 bg-white p-6 shadow-sm md:p-8">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-[0.28em] text-gray-500">Fila local</p>
                                <h3 class="mt-2 text-xl font-semibold text-gray-900">Arquivos</h3>
                            </div>
                        </div>

                        <p id="fileEmptyState" class="mt-6 rounded-2xl border border-dashed border-gray-200 bg-gray-50 px-4 py-5 text-sm text-gray-500">
                            Nenhum PDF selecionado ainda. 
                        </p>

                        <div id="fileList" class="mt-6 flex flex-col gap-3"></div>

                       

                        <button id="fileActionBtn" type="button" disabled class="mt-6 w-full rounded-xl bg-sky-500 px-5 py-3 text-sm font-semibold text-white opacity-60 transition disabled:cursor-not-allowed">
                            Enviar PDFs ao Drive
                        </button>
                    </aside>
                </div>

                <section class="rounded-[2rem] border border-gray-200 bg-white p-6 shadow-sm md:p-8">
                    <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.28em] text-gray-500">Fila local</p>
                            <h3 class="mt-2 text-xl font-semibold text-gray-900">Uploads recentes do agente</h3>
                            <p class="mt-2 text-sm leading-6 text-gray-600">
                                Todos os agentes acompanham daqui os PDFs enviados e o andamento do processamento.
                            </p>
                        </div>

                        <button id="uploadRefreshBtn" type="button" class="inline-flex items-center justify-center gap-2 rounded-xl border border-gray-200 px-4 py-3 text-sm font-medium text-gray-700 transition hover:bg-gray-50">
                            <i class="fa-solid fa-rotate-right text-xs"></i>
                            Atualizar lista
                        </button>
                    </div>

                    <p id="uploadHistoryEmpty" class="mt-6 rounded-2xl border border-dashed border-gray-200 bg-gray-50 px-4 py-5 text-sm text-gray-500 {{ $initialUploadsCollection->isNotEmpty() ? 'hidden' : '' }}">
                        Nenhum upload registrado ainda.
                    </p>

                    <div id="uploadHistoryList" class="mt-6 flex flex-col gap-3">
                        @foreach ($initialUploadsCollection as $upload)
                            @php
                                $status = $upload['status'] ?? 'processing';
                                $statusClasses = match ($status) {
                                    'processed' => 'bg-emerald-50 text-emerald-700',
                                    'failed' => 'bg-red-50 text-red-700',
                                    default => 'bg-sky-50 text-sky-700',
                                };
                                $statusLabel = match ($status) {
                                    'processed' => 'Processado',
                                    'failed' => 'Falhou',
                                    default => 'Processando',
                                };
                            @endphp
                            <article class="rounded-2xl border border-gray-200 bg-white px-4 py-4 shadow-sm" data-upload-item-id="{{ $upload['id'] ?? '' }}">
                                <div class="flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
                                    <div class="min-w-0">
                                        <p class="truncate text-sm font-semibold text-gray-900">{{ $upload['original_name'] ?? 'Arquivo.pdf' }}</p>
                                        <p class="mt-1 text-xs text-gray-500">
                                            Operador: {{ $upload['uploaded_by'] ?? 'Sistema' }}
                                        </p>
                                        <p class="mt-1 text-xs text-gray-500">
                                            Enviado em {{ isset($upload['created_at']) ? \Illuminate\Support\Carbon::parse($upload['created_at'])->timezone(config('app.timezone'))->format('d/m/Y H:i') : '--' }}
                                        </p>
                                    </div>

                                    <span class="inline-flex items-center rounded-full px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.2em] {{ $statusClasses }}">
                                        {{ $statusLabel }}
                                    </span>
                                </div>

                                @if (!empty($upload['error_message']))
                                    <p class="mt-3 rounded-xl border border-red-100 bg-red-50 px-3 py-2 text-xs text-red-700">
                                        {{ $upload['error_message'] }}
                                    </p>
                                @endif

                                @if (($upload['status'] ?? null) === 'failed')
                                    <div class="mt-3 flex justify-end">
                                        <button type="button"
                                                data-upload-delete-id="{{ $upload['id'] }}"
                                                class="inline-flex items-center gap-2 rounded-xl border border-red-200 px-3 py-2 text-xs font-semibold text-red-700 transition hover:bg-red-50">
                                            <i class="fa-solid fa-trash text-[11px]"></i>
                                            Apagar item
                                        </button>
                                    </div>
                                @endif
                            </article>
                        @endforeach
                    </div>
                </section>
            </div>
        </main>
    </div>
</body>
</html>
