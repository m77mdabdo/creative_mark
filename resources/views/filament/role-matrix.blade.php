{{-- Read-only table: what each staff role can do (App\Auth\PermissionMatrix). --}}
<div class="overflow-x-auto">
    <table class="w-full border-collapse text-sm">
        <thead>
            <tr class="border-b border-gray-200 dark:border-white/10">
                <th class="p-2 text-start font-semibold"></th>
                @foreach ($roles as $label)
                    <th class="p-2 text-center text-xs font-semibold">{{ $label }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @foreach ($permissions as $permission)
                <tr class="border-b border-gray-100 dark:border-white/5">
                    <td class="p-2 text-start">{{ $permission['label'] }}</td>
                    @foreach (array_keys($roles) as $role)
                        <td class="p-2 text-center">
                            @if (in_array($role, $permission['roles'], true))
                                <span class="font-bold text-success-600" aria-label="✓">✓</span>
                            @else
                                <span class="text-gray-300 dark:text-gray-600" aria-hidden="true">–</span>
                            @endif
                        </td>
                    @endforeach
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
