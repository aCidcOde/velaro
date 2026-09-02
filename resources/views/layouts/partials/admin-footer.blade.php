<footer class="rounded-[2rem] border border-white/45 bg-white/65 px-6 py-6 text-sm text-[#284191] shadow-[0_24px_70px_-44px_rgba(37,99,235,0.38)] backdrop-blur dark:border-white/10 dark:bg-white/8 dark:text-blue-100/75 dark:shadow-[0_30px_80px_-44px_rgba(2,6,23,0.92)]">
    <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
        <div>
            <p class="font-medium text-[#173a9b] dark:text-white">{{ config('app.name') }}</p>
            <p>Admin com ACL, auditoria, monitoramento e base pronta para novos projetos.</p>
        </div>
        <div class="text-right">
            <p>&copy; {{ date('Y') }} {{ config('app.name') }}</p>
            <p class="text-xs uppercase tracking-[0.22em] text-blue-700/65 dark:text-blue-100/55">{{ config('app.version') }}</p>
        </div>
    </div>
</footer>
