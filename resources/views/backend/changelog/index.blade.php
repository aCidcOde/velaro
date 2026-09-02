@extends('layouts.backend', ['title' => 'Changelog'])

@section('content')
    <div class="grid gap-4 py-4">
        <div>
            <h1 class="text-2xl font-semibold text-slate-900 dark:text-slate-100">Changelog</h1>
            <p class="text-sm text-slate-500 dark:text-slate-400">Registro das releases e extrações feitas nesta base.</p>
        </div>

        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
            @foreach ($versions as $version)
                <a href="{{ route('backend.changelog.show', $version['version']) }}" class="rounded-4 border border-slate-200 bg-white p-6 shadow-sm transition hover:border-sky-300 dark:border-zinc-700 dark:bg-zinc-900">
                    <p class="text-xs uppercase tracking-[0.28em] text-slate-500 dark:text-slate-400">v{{ $version['version'] }}</p>
                    <h2 class="mt-3 text-xl font-semibold text-slate-900 dark:text-slate-100">{{ $version['title'] }}</h2>
                    <p class="mt-3 text-sm leading-7 text-slate-600 dark:text-slate-300">{{ $version['summary'] }}</p>
                </a>
            @endforeach
        </div>
    </div>
@endsection
