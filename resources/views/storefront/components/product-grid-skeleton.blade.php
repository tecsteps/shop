<div class="grid grid-cols-2 gap-x-3 gap-y-8 sm:gap-x-5 md:grid-cols-3 lg:grid-cols-4" aria-label="Loading products" aria-busy="true">
    @for ($index = 0; $index < $count; $index++)
        <div class="animate-pulse">
            <div class="aspect-square rounded-2xl bg-slate-200 dark:bg-slate-800"></div>
            <div class="mt-4 h-4 w-4/5 rounded bg-slate-200 dark:bg-slate-800"></div>
            <div class="mt-2 h-4 w-2/5 rounded bg-slate-200 dark:bg-slate-800"></div>
        </div>
    @endfor
</div>
