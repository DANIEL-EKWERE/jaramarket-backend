@extends('layouts.app')
@section('title', 'Edit Customer')

@section('content')
<div class="min-h-screen bg-slate-50">
    <div class="bg-white border-b border-slate-200 sticky top-0 z-30 shadow-sm">
        <div class="w-full px-6 lg:px-8 xl:px-10 py-4 flex items-center justify-between">
            <div>
                <div class="flex items-center gap-1.5 text-xs text-slate-400 mb-0.5">
                    <a href="{{ route('users.index') }}" class="hover:text-emerald-600 transition-colors">Customers</a>
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    <span class="text-slate-600 font-medium truncate max-w-xs">{{ $user->firstname }} {{ $user->lastname }}</span>
                </div>
                <h1 class="text-xl font-bold text-slate-900">Edit Customer</h1>
            </div>
            <a href="{{ route('users.index') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-white border border-slate-300 hover:bg-slate-50 text-slate-700 text-sm font-medium rounded-lg shadow-sm">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                Back
            </a>
        </div>
    </div>

    <div class="w-full px-6 lg:px-8 xl:px-10 py-6">
        @if(session('success'))
        <div class="mb-5 flex items-center gap-3 px-4 py-3 bg-emerald-50 border border-emerald-200 rounded-xl text-emerald-800 text-sm">
            <svg class="w-4 h-4 flex-shrink-0 text-emerald-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
            {{ session('success') }}
        </div>
        @endif
        @if($errors->any())
        <div class="mb-5 p-4 bg-red-50 border border-red-200 rounded-xl">
            <div class="flex items-start gap-3">
                <svg class="w-5 h-5 text-red-500 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/></svg>
                <div><p class="text-sm font-semibold text-red-800 mb-1">Fix {{ $errors->count() }} error(s):</p><ul class="text-sm text-red-700 list-disc list-inside">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
            </div>
        </div>
        @endif

        <form action="{{ route('users.update', $user) }}" method="POST">
            @csrf @method('PUT')
            <div class="grid grid-cols-1 xl:grid-cols-12 gap-6">
                <div class="xl:col-span-8 space-y-5">

                    {{-- Personal Info --}}
                    <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
                        <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/60 flex items-center gap-3">
                            <div class="w-8 h-8 rounded-lg bg-blue-100 flex items-center justify-center">
                                <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                            </div>
                            <div><h2 class="text-sm font-semibold text-slate-800">Personal Information</h2><p class="text-xs text-slate-400">Name and contact details</p></div>
                        </div>
                        <div class="p-6 space-y-5">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 mb-1.5">First Name</label>
                                    <input type="text" name="firstname" value="{{ old('firstname',$user->firstname) }}" class="w-full px-4 py-2.5 border border-slate-300 bg-slate-50 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500 @error('firstname') border-red-400 bg-red-50 @enderror">
                                    @error('firstname')<p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>@enderror
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Last Name</label>
                                    <input type="text" name="lastname" value="{{ old('lastname',$user->lastname) }}" class="w-full px-4 py-2.5 border border-slate-300 bg-slate-50 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500 @error('lastname') border-red-400 bg-red-50 @enderror">
                                    @error('lastname')<p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>@enderror
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-1.5">Email Address</label>
                                <input type="email" name="email" value="{{ old('email',$user->email) }}" class="w-full px-4 py-2.5 border border-slate-300 bg-slate-50 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500 @error('email') border-red-400 bg-red-50 @enderror">
                                @error('email')<p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>@enderror
                            </div>
                        </div>
                    </div>

                    {{-- Security --}}
                    <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
                        <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/60 flex items-center gap-3">
                            <div class="w-8 h-8 rounded-lg bg-amber-100 flex items-center justify-center">
                                <svg class="w-4 h-4 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                            </div>
                            <div><h2 class="text-sm font-semibold text-slate-800">Security</h2><p class="text-xs text-slate-400">Leave blank to keep current password</p></div>
                        </div>
                        <div class="p-6 space-y-5">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 mb-1.5">New Password</label>
                                    <input type="password" name="password" placeholder="Leave blank to keep current" class="w-full px-4 py-2.5 border border-slate-300 bg-slate-50 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500 @error('password') border-red-400 @enderror">
                                    @error('password')<p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>@enderror
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Confirm Password</label>
                                    <input type="password" name="password_confirmation" placeholder="Repeat new password" class="w-full px-4 py-2.5 border border-slate-300 bg-slate-50 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500">
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Order History --}}
                    <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
                        <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/60 flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-lg bg-violet-100 flex items-center justify-center">
                                    <svg class="w-4 h-4 text-violet-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                                </div>
                                <div><h2 class="text-sm font-semibold text-slate-800">Order History</h2><p class="text-xs text-slate-400">{{ $user->orders->count() }} orders placed</p></div>
                            </div>
                            <span class="inline-block px-2.5 py-0.5 bg-violet-50 text-violet-700 border border-violet-100 rounded-lg text-xs font-semibold">
                                ₦{{ number_format($user->orders->sum('total'),2) }} total
                            </span>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm">
                                <thead><tr class="bg-slate-50 border-b border-slate-100">
                                    <th class="px-5 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Ref</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Date</th>
                                    <th class="px-4 py-3 text-right text-xs font-semibold text-slate-500 uppercase">Total</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Status</th>
                                    <th class="px-5 py-3 text-right text-xs font-semibold text-slate-500 uppercase">View</th>
                                </tr></thead>
                                <tbody class="divide-y divide-slate-50">
                                    @forelse($user->orders as $order)
                                    @php $sMap=['completed'=>'bg-emerald-50 text-emerald-700','processing'=>'bg-blue-50 text-blue-700','cancelled'=>'bg-red-50 text-red-600','pending'=>'bg-amber-50 text-amber-700'];$sCls=$sMap[$order->status]??'bg-slate-100 text-slate-500'; @endphp
                                    <tr class="hover:bg-slate-50/50">
                                        <td class="px-5 py-3.5 font-mono font-semibold text-slate-800 text-xs">{{ $order->reference }}</td>
                                        <td class="px-4 py-3.5 text-slate-400 text-xs">{{ $order->created_at->format('d M Y') }}</td>
                                        <td class="px-4 py-3.5 text-right font-mono font-semibold text-slate-700 text-xs">₦{{ number_format($order->total,2) }}</td>
                                        <td class="px-4 py-3.5"><span class="inline-block px-2 py-0.5 rounded-md text-xs font-medium {{ $sCls }}">{{ ucfirst($order->status) }}</span></td>
                                        <td class="px-5 py-3.5 text-right"><a href="{{ route('orders.show',$order) }}" class="text-xs font-medium text-emerald-600 hover:text-emerald-800">View →</a></td>
                                    </tr>
                                    @empty
                                    <tr><td colspan="5" class="px-5 py-8 text-center text-slate-400 text-sm">No orders yet</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="xl:col-span-4 space-y-5">
                    {{-- Account Status --}}
                    <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
                        <div class="px-5 py-4 border-b border-slate-100 bg-slate-50/60 flex items-center gap-3">
                            <div class="w-8 h-8 rounded-lg bg-emerald-100 flex items-center justify-center">
                                <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                            </div>
                            <div><h2 class="text-sm font-semibold text-slate-800">Account Status</h2><p class="text-xs text-slate-400">Enable or disable access</p></div>
                        </div>
                        <div class="p-5">
                            <select name="is_active" class="w-full px-4 py-2.5 border border-slate-300 bg-slate-50 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500">
                                <option value="1" {{ old('is_active',$user->is_active)?'selected':'' }}>Active</option>
                                <option value="0" {{ old('is_active',$user->is_active)?'':'selected' }}>Inactive</option>
                            </select>
                        </div>
                    </div>

                    {{-- Record Info --}}
                    <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
                        <div class="px-5 py-4 border-b border-slate-100 bg-slate-50/60 flex items-center gap-3">
                            <div class="w-8 h-8 rounded-lg bg-slate-100 flex items-center justify-center">
                                <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            </div>
                            <h2 class="text-sm font-semibold text-slate-800">Record Info</h2>
                        </div>
                        <div class="p-5 space-y-3">
                            <div class="flex justify-between text-xs"><span class="text-slate-500">Customer ID</span><span class="font-mono text-slate-700">#{{ $user->id }}</span></div>
                            <div class="flex justify-between text-xs"><span class="text-slate-500">Registered</span><span class="font-mono text-slate-700">{{ $user->created_at?->format('d M Y') }}</span></div>
                            <div class="flex justify-between text-xs"><span class="text-slate-500">Last Updated</span><span class="font-mono text-slate-700">{{ $user->updated_at?->format('d M Y') }}</span></div>
                            <div class="flex justify-between text-xs"><span class="text-slate-500">Total Orders</span><span class="font-semibold text-slate-700">{{ $user->orders->count() }}</span></div>
                        </div>
                    </div>

                    {{-- Actions --}}
                    <div class="bg-white rounded-xl border border-slate-200 p-5 space-y-3">
                        <button type="submit" class="w-full inline-flex items-center justify-center gap-2 px-5 py-3 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold rounded-lg shadow-sm transition-all">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                            Save Changes
                        </button>
                        <a href="{{ route('users.index') }}" class="w-full inline-flex items-center justify-center px-5 py-2.5 bg-white border border-slate-300 hover:bg-slate-50 text-slate-700 text-sm font-medium rounded-lg transition-colors">Cancel</a>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection
