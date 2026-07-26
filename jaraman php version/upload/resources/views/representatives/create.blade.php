@extends('layouts.app')
@section('title', 'Add State Representative')

@section('content')
<div class="min-h-screen bg-slate-50">

    {{-- TOP BAR --}}
    <div class="bg-white border-b border-slate-200 sticky top-0 z-30 shadow-sm">
        <div class="w-full px-6 lg:px-8 xl:px-10 py-4 flex items-center justify-between">
            <div>
                <div class="flex items-center gap-1.5 text-xs text-slate-400 mb-0.5">
                    <a href="{{ route('representatives.index') }}" class="hover:text-emerald-600 transition-colors">State Representatives</a>
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    <span class="text-slate-600 font-medium">New Representative</span>
                </div>
                <h1 class="text-xl font-bold text-slate-900">Add State Representative</h1>
            </div>
            <a href="{{ route('representatives.index') }}"
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

        <form action="{{ route('representatives.store') }}" method="POST">
            @csrf
            <div class="grid grid-cols-1 xl:grid-cols-12 gap-6">

                {{-- MAIN FORM --}}
                <div class="xl:col-span-8 space-y-5">

                    {{-- Personal Information --}}
                    <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
                        <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/60 flex items-center gap-3">
                            <div class="w-8 h-8 rounded-lg bg-emerald-100 flex items-center justify-center flex-shrink-0">
                                <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                            </div>
                            <div>
                                <h2 class="text-sm font-semibold text-slate-800">Personal Information</h2>
                                <p class="text-xs text-slate-400">Representative's name and contact details</p>
                            </div>
                        </div>
                        <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-5">

                            <div>
                                <label for="name" class="block text-sm font-medium text-slate-700 mb-1.5">Full Name <span class="text-red-500">*</span></label>
                                <input type="text" name="name" id="name" value="{{ old('name') }}" placeholder="e.g. Chukwuemeka Okafor"
                                       class="w-full px-4 py-2.5 border rounded-lg text-sm bg-slate-50 focus:outline-none focus:ring-2 focus:ring-emerald-500 transition-colors
                                              @error('name') border-red-400 bg-red-50 @else border-slate-300 @enderror">
                                @error('name')<p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>@enderror
                            </div>

                            <div>
                                <label for="email" class="block text-sm font-medium text-slate-700 mb-1.5">Email Address <span class="text-red-500">*</span></label>
                                <input type="email" name="email" id="email" value="{{ old('email') }}" placeholder="rep@example.com"
                                       class="w-full px-4 py-2.5 border rounded-lg text-sm bg-slate-50 focus:outline-none focus:ring-2 focus:ring-emerald-500 transition-colors
                                              @error('email') border-red-400 bg-red-50 @else border-slate-300 @enderror">
                                @error('email')<p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>@enderror
                            </div>

                            <div>
                                <label for="phone" class="block text-sm font-medium text-slate-700 mb-1.5">Phone Number</label>
                                <input type="text" name="phone" id="phone" value="{{ old('phone') }}" placeholder="+234 800 000 0000"
                                       class="w-full px-4 py-2.5 border border-slate-300 bg-slate-50 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500 transition-colors">
                                @error('phone')<p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>@enderror
                            </div>

                        </div>
                    </div>

                    {{-- Location --}}
                    <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
                        <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/60 flex items-center gap-3">
                            <div class="w-8 h-8 rounded-lg bg-blue-100 flex items-center justify-center flex-shrink-0">
                                <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                            </div>
                            <div>
                                <h2 class="text-sm font-semibold text-slate-800">Territory Assignment</h2>
                                <p class="text-xs text-slate-400">State, LGA and physical address</p>
                            </div>
                        </div>
                        <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-5">

                            <div>
                                <label for="state" class="block text-sm font-medium text-slate-700 mb-1.5">State <span class="text-red-500">*</span></label>
                                <input type="text" name="state" id="state" value="{{ old('state') }}" placeholder="e.g. Lagos"
                                       class="w-full px-4 py-2.5 border rounded-lg text-sm bg-slate-50 focus:outline-none focus:ring-2 focus:ring-emerald-500 transition-colors
                                              @error('state') border-red-400 bg-red-50 @else border-slate-300 @enderror">
                                @error('state')<p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>@enderror
                            </div>

                            <div>
                                <label for="lga" class="block text-sm font-medium text-slate-700 mb-1.5">Local Government Area</label>
                                <input type="text" name="lga" id="lga" value="{{ old('lga') }}" placeholder="e.g. Ikeja"
                                       class="w-full px-4 py-2.5 border border-slate-300 bg-slate-50 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500 transition-colors">
                                @error('lga')<p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>@enderror
                            </div>

                            <div class="md:col-span-2">
                                <label for="address" class="block text-sm font-medium text-slate-700 mb-1.5">Office Address</label>
                                <textarea name="address" id="address" rows="3" placeholder="Full office address…"
                                          class="w-full px-4 py-2.5 border border-slate-300 bg-slate-50 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500 resize-none transition-colors">{{ old('address') }}</textarea>
                                @error('address')<p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>@enderror
                            </div>

                        </div>
                    </div>

                </div>

                {{-- SIDEBAR --}}
                <div class="xl:col-span-4 space-y-5">

                    {{-- Status --}}
                    <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
                        <div class="px-5 py-4 border-b border-slate-100 bg-slate-50/60 flex items-center gap-3">
                            <div class="w-8 h-8 rounded-lg bg-violet-100 flex items-center justify-center flex-shrink-0">
                                <svg class="w-4 h-4 text-violet-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            </div>
                            <div>
                                <h2 class="text-sm font-semibold text-slate-800">Account Status</h2>
                                <p class="text-xs text-slate-400">Activate or deactivate on creation</p>
                            </div>
                        </div>
                        <div class="p-5">
                            <label class="flex items-center justify-between p-3 bg-slate-50 rounded-lg border border-slate-200 cursor-pointer hover:border-emerald-300 transition-colors">
                                <div>
                                    <p class="text-sm font-medium text-slate-700">Active</p>
                                    <p class="text-xs text-slate-400 mt-0.5">Representative can log in and operate</p>
                                </div>
                                <input type="checkbox" name="is_active" value="1" {{ old('is_active', 1) ? 'checked' : '' }}
                                       class="w-5 h-5 rounded text-emerald-600 border-slate-300 focus:ring-emerald-500">
                            </label>
                        </div>
                    </div>

                    {{-- Actions --}}
                    <div class="bg-white rounded-xl border border-slate-200 p-5 space-y-3">
                        <button type="submit"
                                class="w-full inline-flex items-center justify-center gap-2 px-5 py-3 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold rounded-lg shadow-sm transition-all hover:shadow">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                            Create Representative
                        </button>
                        <a href="{{ route('representatives.index') }}"
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
