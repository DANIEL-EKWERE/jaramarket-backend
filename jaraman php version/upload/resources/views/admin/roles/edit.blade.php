@extends('layouts.app')
@section('title','Edit Admin: '.$admin->name)

@section('content')
<div class="max-w-4xl mx-auto space-y-6">
    <div class="flex items-center gap-4">
        <a href="{{ route('admin-management.index') }}" class="p-2 rounded-lg text-slate-500 hover:bg-slate-100">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
        </a>
        <div>
            <h1 class="page-title">Edit Admin: {{ $admin->name }}</h1>
            <p class="page-subtitle">Update role, permissions and profile info</p>
        </div>
    </div>

    <form action="{{ route('admin-management.update', $admin) }}" method="POST" class="space-y-6">
        @csrf @method('PUT')

        {{-- Profile --}}
        <div class="card space-y-4">
            <h2 class="text-base font-semibold text-slate-900 border-b border-slate-100 pb-3">Profile Information</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div><label>First Name <span class="text-red-500">*</span></label><input type="text" name="firstname" value="{{ old('firstname', $admin->firstname) }}" required></div>
                <div><label>Last Name</label><input type="text" name="lastname" value="{{ old('lastname', $admin->lastname) }}"></div>
                <div><label>Email <span class="text-red-500">*</span></label><input type="email" name="email" value="{{ old('email', $admin->email) }}" required></div>
                <div><label>Phone Number</label><input type="text" name="phone_number" value="{{ old('phone_number', $admin->phone_number) }}"></div>
                <div class="md:col-span-2"><label>New Password <span class="text-xs text-slate-400">(leave blank to keep current)</span></label><input type="password" name="password"></div>
            </div>
        </div>

        {{-- Role & State --}}
        <div class="card space-y-4">
            <h2 class="text-base font-semibold text-slate-900 border-b border-slate-100 pb-3">Role & Access Scope</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label>Admin Role <span class="text-red-500">*</span></label>
                    <select name="role" id="roleSelect" required>
                        <option value="">Select a role…</option>
                        @foreach(\App\Enums\UserPermissionsEnum::adminRoles() as $role)
                        <option value="{{ $role }}"
                            {{ old('role', $admin->role) === $role ? 'selected' : '' }}
                            data-perms="{{ json_encode(\App\Enums\UserPermissionsEnum::from($role)->defaultPermissions()) }}">
                            {{ \App\Enums\UserPermissionsEnum::from($role)->label() }}
                        </option>
                        @endforeach
                    </select>
                    <div id="roleDesc" class="mt-2 text-xs text-slate-500 p-2 bg-slate-50 rounded-lg hidden"></div>
                </div>
                <div id="stateField">
                    <label>Assigned State <span class="text-xs text-slate-400">(for State Admin)</span></label>
                    <select name="state_id">
                        <option value="">All States</option>
                        @foreach($states as $s)
                        <option value="{{ $s->id }}" {{ old('state_id', $admin->state_id) == $s->id ? 'selected' : '' }}>{{ $s->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

        {{-- Permissions Matrix --}}
        <div class="card space-y-5">
            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                <div>
                    <h2 class="text-base font-semibold text-slate-900">Permissions</h2>
                    <p class="text-xs text-slate-500 mt-0.5">Currently has <strong>{{ $admin->userPermissions->count() }}</strong> permissions assigned.</p>
                </div>
                <div class="flex gap-2">
                    <button type="button" id="applyRoleDefaults" class="px-3 py-1.5 text-xs font-medium text-green-700 bg-green-50 hover:bg-green-100 border border-green-200 rounded-lg">Apply Role Defaults</button>
                    <button type="button" id="selectAll" class="px-3 py-1.5 text-xs font-medium text-blue-700 bg-blue-50 hover:bg-blue-100 border border-blue-200 rounded-lg">All</button>
                    <button type="button" id="clearAll" class="px-3 py-1.5 text-xs font-medium text-slate-600 bg-slate-50 hover:bg-slate-100 border border-slate-200 rounded-lg">None</button>
                </div>
            </div>
            @foreach($permissions as $group => $groupPerms)
            <div>
                <p class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">{{ ucfirst($group) }}</p>
                <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-2">
                    @foreach($groupPerms as $perm)
                    <label class="flex items-center gap-2 px-3 py-2 rounded-lg border border-slate-200 hover:border-green-400 hover:bg-green-50 cursor-pointer transition-colors">
                        <input type="checkbox" name="permissions[]" value="{{ $perm->id }}" data-slug="{{ $perm->slug }}"
                            class="perm-cb w-4 h-4 rounded text-green-600 focus:ring-green-500"
                            {{ in_array($perm->id, $adminPermIds) ? 'checked' : '' }}>
                        <span class="text-xs text-slate-600">{{ $perm->name }}</span>
                    </label>
                    @endforeach
                </div>
            </div>
            @endforeach
        </div>

        <div class="flex items-center gap-3 flex-wrap">
            <button type="submit" class="px-6 py-2.5 bg-green-600 hover:bg-green-700 text-white text-sm font-medium rounded-xl shadow-sm">Save Changes</button>
            <a href="{{ route('admin-management.index') }}" class="px-6 py-2.5 bg-white border border-slate-200 hover:bg-slate-50 text-slate-700 text-sm font-medium rounded-xl">Cancel</a>
            <form action="{{ route('admin-management.reset-permissions', $admin) }}" method="POST" class="ml-auto">
                @csrf
                <button type="submit" class="px-4 py-2.5 text-xs font-medium text-orange-700 bg-orange-50 hover:bg-orange-100 border border-orange-200 rounded-xl" onclick="return confirm('Reset permissions to role defaults?')">Reset to Role Defaults</button>
            </form>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
const roleDescs = {
    super_admin: 'Full access to everything.',
    admin: 'Full access to everything.',
    state_admin: 'All activities within their assigned state.',
    vendor_manager: 'Vendor DB, sales, orders and logistics (no finance).',
    accounts: 'All financial dealings — wallets, transactions, withdrawals.',
    audit: 'Read-only financial audit access.',
    logistics: 'Order list and delivery management only.',
};
$('#roleSelect').on('change', function() {
    const role = $(this).val();
    $('#roleDesc').html(roleDescs[role] || '').toggleClass('hidden', !role);
    $('#stateField').toggleClass('opacity-50', role !== 'state_admin');
});
$('#applyRoleDefaults').on('click', function() {
    const opt = $('#roleSelect').find(':selected');
    const defaults = JSON.parse(opt.attr('data-perms') || '[]');
    const isSuperAdmin = ['super_admin', 'admin'].includes($('#roleSelect').val());
    $('.perm-cb').each(function() {
        $(this).prop('checked', isSuperAdmin || defaults.includes($(this).data('slug')));
    });
});
$('#selectAll').on('click', () => $('.perm-cb').prop('checked', true));
$('#clearAll').on('click', () => $('.perm-cb').prop('checked', false));
$('#roleSelect').trigger('change');
</script>
@endpush
