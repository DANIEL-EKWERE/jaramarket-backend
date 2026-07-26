@extends('layouts.app')
@section('title', 'Create Administrator')

@section('content')
<div class="min-h-screen bg-slate-50">

    <div class="bg-white border-b border-slate-200 sticky top-0 z-30 shadow-sm">
        <div class="w-full px-6 lg:px-8 xl:px-10 py-4 flex items-center justify-between">
            <div>
                <div class="flex items-center gap-1.5 text-xs text-slate-400 mb-0.5">
                    <a href="{{ route('admin.index') }}" class="hover:text-emerald-600 transition-colors">Administrators</a>
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    <span class="text-slate-600 font-medium">New Administrator</span>
                </div>
                <h1 class="text-xl font-bold text-slate-900">Create Administrator</h1>
            </div>
            <a href="{{ route('admin.index') }}"
               class="inline-flex items-center gap-2 px-4 py-2 bg-white border border-slate-300 hover:bg-slate-50 text-slate-700 text-sm font-medium rounded-lg transition-colors shadow-sm">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                Back
            </a>
        </div>
    </div>

    <div class="w-full px-6 lg:px-8 xl:px-10 py-6">

        @if ($errors->any())
        <div class="mb-5 p-4 bg-red-50 border border-red-200 rounded-xl flex items-start gap-3">
            <svg class="w-5 h-5 text-red-500 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/></svg>
            <div>
                <p class="text-sm font-semibold text-red-800 mb-1">Please fix {{ $errors->count() }} error(s):</p>
                <ul class="text-sm text-red-700 list-disc list-inside space-y-0.5">
                    @foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach
                </ul>
            </div>
        </div>
        @endif

        <form action="{{ route('admin.store') }}" method="POST">
            @csrf
            <div class="grid grid-cols-1 xl:grid-cols-12 gap-6">

                <div class="xl:col-span-8 space-y-5">

                    {{-- Identity --}}
                    <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
                        <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/60 flex items-center gap-3">
                            <div class="w-8 h-8 rounded-lg bg-indigo-100 flex items-center justify-center flex-shrink-0">
                                <svg class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                            </div>
                            <div>
                                <h2 class="text-sm font-semibold text-slate-800">Identity</h2>
                                <p class="text-xs text-slate-400">Administrator's name and contact information</p>
                            </div>
                        </div>
                        <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-5">

                            <div>
                                <label for="firstname" class="block text-sm font-medium text-slate-700 mb-1.5">First Name <span class="text-red-500">*</span></label>
                                <input type="text" name="firstname" id="firstname" value="{{ old('firstname') }}"
                                       class="w-full px-4 py-2.5 border rounded-lg text-sm bg-slate-50 focus:outline-none focus:ring-2 focus:ring-emerald-500 transition-colors @error('firstname') border-red-400 bg-red-50 @else border-slate-300 @enderror">
                                @error('firstname')<p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>@enderror
                            </div>

                            <div>
                                <label for="lastname" class="block text-sm font-medium text-slate-700 mb-1.5">Last Name <span class="text-red-500">*</span></label>
                                <input type="text" name="lastname" id="lastname" value="{{ old('lastname') }}"
                                       class="w-full px-4 py-2.5 border rounded-lg text-sm bg-slate-50 focus:outline-none focus:ring-2 focus:ring-emerald-500 transition-colors @error('lastname') border-red-400 bg-red-50 @else border-slate-300 @enderror">
                                @error('lastname')<p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>@enderror
                            </div>

                            <div>
                                <label for="email" class="block text-sm font-medium text-slate-700 mb-1.5">Email Address <span class="text-red-500">*</span></label>
                                <input type="email" name="email" id="email" value="{{ old('email') }}"
                                       class="w-full px-4 py-2.5 border rounded-lg text-sm bg-slate-50 focus:outline-none focus:ring-2 focus:ring-emerald-500 transition-colors @error('email') border-red-400 bg-red-50 @else border-slate-300 @enderror">
                                @error('email')<p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>@enderror
                            </div>

                            <div>
                                <label for="phone" class="block text-sm font-medium text-slate-700 mb-1.5">Phone Number</label>
                                <input type="text" name="phone" id="phone" value="{{ old('phone') }}"
                                       class="w-full px-4 py-2.5 border border-slate-300 bg-slate-50 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500 transition-colors">
                                @error('phone')<p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>@enderror
                            </div>

                        </div>
                    </div>

                    {{-- Security --}}
                    <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
                        <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/60 flex items-center gap-3">
                            <div class="w-8 h-8 rounded-lg bg-red-100 flex items-center justify-center flex-shrink-0">
                                <svg class="w-4 h-4 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                            </div>
                            <div>
                                <h2 class="text-sm font-semibold text-slate-800">Security</h2>
                                <p class="text-xs text-slate-400">Set a strong password for this account</p>
                            </div>
                        </div>
                        <div class="p-6">
                            <div class="max-w-sm">
                                <label for="password" class="block text-sm font-medium text-slate-700 mb-1.5">Password <span class="text-red-500">*</span></label>
                                <input type="password" name="password" id="password"
                                       class="w-full px-4 py-2.5 border rounded-lg text-sm bg-slate-50 focus:outline-none focus:ring-2 focus:ring-emerald-500 transition-colors @error('password') border-red-400 bg-red-50 @else border-slate-300 @enderror">
                                @error('password')<p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>@enderror
                                <p class="mt-1 text-xs text-slate-400">Minimum 8 characters.</p>
                            </div>
                        </div>
                    </div>

                </div>

                <div class="xl:col-span-4 space-y-5">

                    <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
                        <div class="px-5 py-4 border-b border-slate-100 bg-slate-50/60 flex items-center gap-3">
                            <div class="w-8 h-8 rounded-lg bg-violet-100 flex items-center justify-center flex-shrink-0">
                                <svg class="w-4 h-4 text-violet-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            </div>
                            <div>
                                <h2 class="text-sm font-semibold text-slate-800">Account Status</h2>
                            </div>
                        </div>
                        <div class="p-5">
                            <label class="flex items-center justify-between p-3 bg-slate-50 rounded-lg border border-slate-200 cursor-pointer hover:border-emerald-300 transition-colors">
                                <div>
                                    <p class="text-sm font-medium text-slate-700">Active</p>
                                    <p class="text-xs text-slate-400 mt-0.5">Admin can log in immediately</p>
                                </div>
                                <input type="checkbox" name="is_active" value="1" {{ old('is_active', 1) ? 'checked' : '' }}
                                       class="w-5 h-5 rounded text-emerald-600 border-slate-300 focus:ring-emerald-500">
                            </label>
                        </div>
                    </div>

                    <div class="bg-white rounded-xl border border-slate-200 p-5 space-y-3">
                        <button type="submit"
                                class="w-full inline-flex items-center justify-center gap-2 px-5 py-3 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold rounded-lg shadow-sm transition-all hover:shadow">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                            Create Administrator
                        </button>
                        <a href="{{ route('admin.index') }}"
                           class="w-full inline-flex items-center justify-center px-5 py-2.5 bg-white border border-slate-300 hover:bg-slate-50 text-slate-700 text-sm font-medium rounded-lg transition-colors">
                            Cancel
                        </a>
                    </div>

                </div>
            </div>
        </form>
    </div>
</div>
@endsection
