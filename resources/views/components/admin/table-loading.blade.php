@props([
    'rows' => 5,
    'columns' => 5,
])

@for ($row = 0; $row < max(1, (int) $rows); $row++)
    <tr aria-hidden="true">
        @for ($column = 0; $column < max(1, (int) $columns); $column++)
            <td class="px-4 py-4 sm:px-6">
                <span
                    class="block h-4 animate-pulse rounded bg-zinc-200 dark:bg-zinc-800"
                    style="width: {{ [48, 64, 72, 80, 56][($row + $column) % 5] }}%"
                ></span>
            </td>
        @endfor
    </tr>
@endfor
