@php
    $statusLabel = \Illuminate\Support\Str::headline((string) ($status ?? 'unknown'));
    $statusTone = match (strtolower((string) ($status ?? ''))) {
        'active', 'published', 'paid', 'fulfilled', 'complete', 'completed', 'success' => 'bg-emerald-50 text-emerald-800 ring-emerald-600/20 dark:bg-emerald-950/60 dark:text-emerald-200 dark:ring-emerald-400/20',
        'pending', 'draft', 'processing', 'partially_refunded' => 'bg-amber-50 text-amber-800 ring-amber-600/20 dark:bg-amber-950/60 dark:text-amber-200 dark:ring-amber-400/20',
        'cancelled', 'canceled', 'archived', 'failed', 'refunded', 'inactive' => 'bg-rose-50 text-rose-800 ring-rose-600/20 dark:bg-rose-950/60 dark:text-rose-200 dark:ring-rose-400/20',
        default => 'bg-zinc-100 text-zinc-700 ring-zinc-500/20 dark:bg-zinc-800 dark:text-zinc-300 dark:ring-zinc-400/20',
    };
@endphp
<span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-medium ring-1 ring-inset {{ $statusTone }}">{{ $statusLabel }}</span>
