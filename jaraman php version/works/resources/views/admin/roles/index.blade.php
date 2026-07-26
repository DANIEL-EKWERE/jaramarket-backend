@extends('layouts.app')
@section('title','Admin Management')
@section('content')
<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div><h1 class="page-title">Admin Management</h1><p class="page-subtitle">Manage admin users, roles and permissions</p></div>
        <a href="{{ route('admin-management.create') }}" class="inline-flex items-center gap-2 px-4 py-2.5 bg-green-600 hover:bg-green-700 text-white text-sm font-medium rounded-xl shadow-sm transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>Add Admin
        </a>
    </div>

    {{-- Role cards --}}
    @php
    $roleColors=['super_admin'=>'bg-purple-50 border-purple-200 text-purple-700','admin'=>'bg-purple-50 border-purple-200 text-purple-700','state_admin'=>'bg-blue-50 border-blue-200 text-blue-700','vendor_manager'=>'bg-teal-50 border-teal-200 text-teal-700','accounts'=>'bg-amber-50 border-amber-200 text-amber-700','audit'=>'bg-orange-50 border-orange-200 text-orange-700','logistics'=>'bg-slate-50 border-slate-200 text-slate-700'];
    $roleCounts=$admins->getCollection()->groupBy('role');
    @endphp
    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-3">
        @foreach(\App\Enums\UserPermissionsEnum::adminRoles() as $role)
        <div class="rounded-xl border p-3 {{ $roleColors[$role]??'bg-slate-50 border-slate-200 text-slate-700' }}">
            <p class="text-xs font-medium opacity-70">{{ \App\Enums\UserPermissionsEnum::from($role)->label() }}</p>
            <p class="text-2xl font-bold mt-0.5">{{ ($roleCounts[$role]??collect())->count() }}</p>
        </div>
        @endforeach
    </div>

    {{-- Filters --}}
    <form method="GET" class="flex flex-wrap gap-3 card">
        <input type="text" name="search" value="{{ request('search') }}" placeholder="Search name or email…" class="flex-1 min-w-48">
        <select name="role">
            <option value="">All Roles</option>
            @foreach(\App\Enums\UserPermissionsEnum::adminRoles() as $role)
            <option value="{{ $role }}" {{ request('role')===$role?'selected':'' }}>{{ \App\Enums\UserPermissionsEnum::from($role)->label() }}</option>
            @endforeach
        </select>
        <select name="state_id">
            <option value="">All States</option>
            @foreach($states as $s)<option value="{{ $s->id }}" {{ request('state_id')==$s->id?'selected':'' }}>{{ $s->name }}</option>@endforeach
        </select>
        <button type="submit" class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white text-sm font-medium rounded-lg">Filter</button>
        <a href="{{ route('admin-management.index') }}" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 text-sm font-medium rounded-lg">Reset</a>
    </form>

    {{-- Table --}}
    <div class="card p-0 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead><tr class="bg-slate-50 border-b border-slate-200">
                    <th class="px-4 py-3 text-left font-semibold text-slate-600 text-xs uppercase tracking-wider">Admin</th>
                    <th class="px-4 py-3 text-left font-semibold text-slate-600 text-xs uppercase tracking-wider">Role</th>
                    <th class="px-4 py-3 text-left font-semibold text-slate-600 text-xs uppercase tracking-wider">State</th>
                    <th class="px-4 py-3 text-left font-semibold text-slate-600 text-xs uppercase tracking-wider">Permissions</th>
                    <th class="px-4 py-3 text-left font-semibold text-slate-600 text-xs uppercase tracking-wider">Status</th>
                    <th class="px-4 py-3 text-left font-semibold text-slate-600 text-xs uppercase tracking-wider">Actions</th>
                </tr></thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($admins as $admin)
                    @php $rc=$roleColors[$admin->role]??'bg-slate-100 text-slate-700'; $rcb=str_replace(['bg-','border-'],['','text-'],$rc); @endphp
                    <tr class="hover:bg-slate-50">
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-full bg-green-600 flex items-center justify-center text-white font-bold text-xs">{{ strtoupper(substr($admin->firstname,0,1)) }}</div>
                                <div><p class="font-medium text-slate-900">{{ $admin->name }}</p><p class="text-xs text-slate-400">{{ $admin->email }}</p></div>
                            </div>
                        </td>
                        <td class="px-4 py-3"><span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold {{ $roleColors[$admin->role]??'' }}">{{ \App\Enums\UserPermissionsEnum::from($admin->role)->label() }}</span></td>
                        <td class="px-4 py-3 text-slate-600 text-xs">{{ $admin->state?->name??'—' }}</td>
                        <td class="px-4 py-3"><span class="text-xs text-slate-500">{{ $admin->userPermissions->count() }} permissions</span></td>
                        <td class="px-4 py-3">@if($admin->is_active)<span class="badge-success">Active</span>@else<span class="badge-danger">Inactive</span>@endif</td>
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-1.5">
                                <a href="{{ route('admin-management.edit', $admin) }}" class="btn-xs-secondary">Edit</a>
                                @if($admin->id!==auth()->id())
                                <form action="{{ route('admin-management.toggle-status',$admin) }}" method="POST" class="inline">
                                    @csrf @method('PATCH')
                                    <button type="submit" class="{{ $admin->is_active?'btn-xs-secondary text-amber-600':'btn-xs-secondary text-emerald-600' }}">{{ $admin->is_active?'Deactivate':'Activate' }}</button>
                                </form>
                                <button onclick="confirmDelete('{{ route('admin-management.destroy',$admin) }}')" class="btn-xs-danger">Delete</button>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="6" class="px-4 py-10 text-center text-slate-400">No admin users found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($admins->hasPages())<div class="px-4 py-3 border-t border-slate-100">{{ $admins->links() }}</div>@endif
    </div>
</div>
<form id="deleteForm" method="POST" style="display:none">@csrf @method('DELETE')</form>
@endsection
@push('scripts')
<script>
function confirmDelete(url){Swal.fire({title:'Delete Admin?',text:'This cannot be undone.',icon:'warning',showCancelButton:true,confirmButtonColor:'#dc2626',confirmButtonText:'Delete'}).then(r=>{if(r.isConfirmed){document.getElementById('deleteForm').action=url;document.getElementById('deleteForm').submit();}});}
</script>
@endpush
