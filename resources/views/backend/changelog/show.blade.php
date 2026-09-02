@extends('layouts.backend', ['title' => 'Changelog '.$changelog['version']])

@section('content')
    <div class="grid gap-4 py-4">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <p class="text-xs uppercase tracking-[0.28em] text-slate-500 dark:text-slate-400">v{{ $changelog['version'] }}</p>
                <h1 class="mt-2 text-2xl font-semibold text-slate-900 dark:text-slate-100">{{ $changelog['title'] }}</h1>
                <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">{{ $changelog['summary'] }}</p>
            </div>
            <a href="{{ route('backend.changelog.index') }}" class="rounded-full border border-slate-200 px-5 py-3 text-sm font-semibold dark:border-zinc-700">Voltar</a>
        </div>

        <div class="rounded-4 border border-slate-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <ul class="mb-6 grid gap-2 text-sm text-slate-700 dark:text-slate-200">
                @foreach ($changelog['highlights'] as $highlight)
                    <li>{{ $highlight }}</li>
                @endforeach
            </ul>

            <article class="prose max-w-none dark:prose-invert">
                {!! $changelogHtml !!}
            </article>
        </div>
    </div>
@endsection
