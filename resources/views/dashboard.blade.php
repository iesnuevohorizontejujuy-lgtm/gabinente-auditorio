<x-layouts::app :title="__('Dashboard')">
    <div class="app-page mx-auto flex w-full max-w-7xl flex-col gap-8">
        <div class="grid gap-2">
            <p class="app-eyebrow">Reservas institucionales</p>
            <flux:heading size="xl" level="1">¿Qué espacio necesitás reservar?</flux:heading>
            <flux:text>Elegí un sector para iniciar una nueva solicitud de reserva.</flux:text>
        </div>

        <div class="grid gap-6 md:grid-cols-3">
            @foreach ($spaces as $space)
                <a
                    href="{{ route('calendar', ['sala' => $space['id']]) }}"
                    wire:navigate
                    class="group flex min-h-80 flex-col overflow-hidden rounded-2xl border border-zinc-200 bg-white shadow-sm transition duration-200 hover:-translate-y-1 hover:shadow-lg focus:outline-none focus:ring-2 focus:ring-offset-2 dark:border-zinc-700 dark:bg-zinc-900 dark:focus:ring-offset-zinc-800"
                    style="--space-color: {{ $space['color'] }};"
                >
                    <div class="relative flex h-36 items-end overflow-hidden bg-[var(--space-color)] p-5">
                        <div class="absolute inset-0 bg-[radial-gradient(circle_at_top_right,rgba(255,255,255,.36),transparent_46%)]"></div>
                        <span class="relative rounded-full border border-white/35 bg-white/20 px-3 py-1 text-xs font-semibold tracking-wide text-white">
                            ESPACIO INSTITUCIONAL
                        </span>
                        <flux:icon.calendar-days class="absolute -right-4 -bottom-7 size-36 text-white/20" />
                    </div>

                    <div class="flex flex-1 flex-col gap-4 p-6">
                        <div class="grid gap-2">
                            <h2 class="text-2xl font-semibold tracking-tight text-zinc-950 dark:text-white">{{ $space['title'] }}</h2>
                            <p class="text-sm leading-6 text-zinc-600 dark:text-zinc-300">{{ $space['description'] }}</p>
                        </div>

                        <div class="mt-auto flex items-center justify-between gap-3 border-t border-zinc-100 pt-4 text-sm dark:border-zinc-800">
                            <span class="text-zinc-500 dark:text-zinc-400">Hasta {{ $space['capacity'] }} personas</span>
                            <span class="font-semibold text-[var(--space-color)]">Reservar <span aria-hidden="true">→</span></span>
                        </div>
                    </div>
                </a>
            @endforeach
        </div>
    </div>
</x-layouts::app>
