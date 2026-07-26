@extends('layouts.app')
@section('title', 'Edit Representative')

@section('content')
<div class="min-h-screen bg-slate-50">

    {{-- TOP BAR --}}
    <div class="bg-white border-b border-slate-200 sticky top-0 z-30 shadow-sm">
        <div class="w-full px-6 lg:px-8 xl:px-10 py-4 flex items-center justify-between">
            <div>
                <div class="flex items-center gap-1.5 text-xs text-slate-400 mb-0.5">
                    <a href="{{ route('representatives.index') }}" class="hover:text-emerald-600 transition-colors">State Representatives</a>
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    <span class="text-slate-600 font-medium truncate max-w-xs">{{ $representative->name }}</span>
                </div>
                <h1 class="text-xl font-bold text-slate-900">Edit Representative</h1>
            </div>
            <a href="{{ route('representatives.index') }}"
               class="inline-flex items-center gap-2 px-4 py-2 bg-white border border-slate-300 hover:bg-slate-50 text-slate-700 text-sm font-medium rounded-lg transition-colors shadow-sm">
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

        <form action="{{ route('representatives.update', $representative) }}" method="POST">
            @csrf @method('PUT')
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
                                <input type="text" name="name" id="name" value="{{ old('name', $representative->name) }}"
                                       class="w-full px-4 py-2.5 border rounded-lg text-sm bg-slate-50 focus:outline-none focus:ring-2 focus:ring-emerald-500 transition-colors
                                              @error('name') border-red-400 bg-red-50 @else border-slate-300 @enderror">
                                @error('name')<p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>@enderror
                            </div>

                            <div>
                                <label for="email" class="block text-sm font-medium text-slate-700 mb-1.5">Email Address <span class="text-red-500">*</span></label>
                                <input type="email" name="email" id="email" value="{{ old('email', $representative->email) }}"
                                       class="w-full px-4 py-2.5 border rounded-lg text-sm bg-slate-50 focus:outline-none focus:ring-2 focus:ring-emerald-500 transition-colors
                                              @error('email') border-red-400 bg-red-50 @else border-slate-300 @enderror">
                                @error('email')<p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>@enderror
                            </div>

                            <div>
                                <label for="phone" class="block text-sm font-medium text-slate-700 mb-1.5">Phone Number</label>
                                <input type="text" name="phone" id="phone" value="{{ old('phone', $representative->phone) }}"
                                       class="w-full px-4 py-2.5 border border-slate-300 bg-slate-50 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500 transition-colors">
                                @error('phone')<p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>@enderror
                            </div>

                        </div>
                    </div>

                    {{-- Territory --}}
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
                                <input type="text" name="state" id="state" value="{{ old('state', $representative->state) }}"
                                       class="w-full px-4 py-2.5 border rounded-lg text-sm bg-slate-50 focus:outline-none focus:ring-2 focus:ring-emerald-500 transition-colors
                                              @error('state') border-red-400 bg-red-50 @else border-slate-300 @enderror">
                                @error('state')<p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>@enderror
                            </div>

                            <div>
                                <label for="lga" class="block text-sm font-medium text-slate-700 mb-1.5">Local Government Area</label>
                                <input type="text" name="lga" id="lga" value="{{ old('lga', $representative->lga) }}"
                                       class="w-full px-4 py-2.5 border border-slate-300 bg-slate-50 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500 transition-colors">
                                @error('lga')<p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>@enderror
                            </div>

                            <div class="md:col-span-2">
                                <label for="address" class="block text-sm font-medium text-slate-700 mb-1.5">Office Address</label>
                                <textarea name="address" id="address" rows="3"
                                          class="w-full px-4 py-2.5 border border-slate-300 bg-slate-50 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500 resize-none transition-colors">{{ old('address', $representative->address) }}</textarea>
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
                                <p class="text-xs text-slate-400">Enable or disable this account</p>
                            </div>
                        </div>
                        <div class="p-5">
                            <label class="flex items-center justify-between p-3 bg-slate-50 rounded-lg border border-slate-200 cursor-pointer hover:border-emerald-300 transition-colors">
                                <div>
                                    <p class="text-sm font-medium text-slate-700">Active</p>
                                    <p class="text-xs text-slate-400 mt-0.5">Representative can log in and operate</p>
                                </div>
                                <input type="checkbox" name="is_active" value="1" {{ old('is_active', $representative->is_active) ? 'checked' : '' }}
                                       class="w-5 h-5 rounded text-emerald-600 border-slate-300 focus:ring-emerald-500">
                            </label>
                        </div>
                    </div>

                    {{-- Meta info --}}
                    <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
                        <div class="px-5 py-4 border-b border-slate-100 bg-slate-50/60">
                            <h2 class="text-sm font-semibold text-slate-800">Record Info</h2>
                        </div>
                        <div class="p-5 space-y-3">
                            <div class="flex justify-between text-sm">
                                <span class="text-slate-500">Created</span>
                                <span class="text-slate-700 font-medium">{{ $representative->created_at->format('d M Y') }}</span>
                            </div>
                            <div class="flex justify-between text-sm">
                                <span class="text-slate-500">Last Updated</span>
                                <span class="text-slate-700 font-medium">{{ $representative->updated_at->diffForHumans() }}</span>
                            </div>
                        </div>
                    </div>

                    {{-- Actions --}}
                    <div class="bg-white rounded-xl border border-slate-200 p-5 space-y-3">
                        <button type="submit"
                                class="w-full inline-flex items-center justify-center gap-2 px-5 py-3 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold rounded-lg shadow-sm transition-all hover:shadow">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                            Update Representative
                        </button>
                        <a href="{{ route('representatives.index') }}"
                           class="w-full inline-flex items-center justify-center px-5 py-2.5 bg-white border border-slate-300 hover:bg-slate-50 text-slate-700 text-sm font-medium rounded-lg transition-colors">
                            Cancel
                        </a>
                        <hr class="border-slate-100">
                        <form action="{{ route('representatives.destroy', $representative) }}" method="POST"
                              onsubmit="return confirm('Delete {{ addslashes($representative->name) }}? This cannot be undone.')">
                            @csrf @method('DELETE')
                            <button type="submit"
                                    class="w-full inline-flex items-center justify-center gap-2 px-5 py-2.5 bg-white border border-red-200 hover:bg-red-50 text-red-500 hover:text-red-700 text-sm font-medium rounded-lg transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                Delete Representative
                            </button>
                        </form>
                    </div>

                </div>
            </div>
        </form>
    </div>
</div>
@endsection
