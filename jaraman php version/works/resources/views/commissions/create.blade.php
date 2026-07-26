@extends('layouts.app')
@section('title', 'Add Commission Tier')

@section('content')
<div class="min-h-screen bg-slate-50">
    <div class="bg-white border-b border-slate-200 sticky top-0 z-30 shadow-sm">
        <div class="w-full px-6 lg:px-8 xl:px-10 py-4 flex items-center justify-between">
            <div>
                <div class="flex items-center gap-1.5 text-xs text-slate-400 mb-0.5">
                    <a href="{{ route('commissions.index') }}" class="hover:text-emerald-600">Commissions</a>
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    <span class="text-slate-600 font-medium">New Tier</span>
                </div>
                <h1 class="text-xl font-bold text-slate-900">Add Commission Tier</h1>
            </div>
            <a href="{{ route('commissions.index') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-white border border-slate-300 hover:bg-slate-50 text-slate-700 text-sm font-medium rounded-lg shadow-sm">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                Back
            </a>
        </div>
    </div>

    <div class="w-full px-6 lg:px-8 xl:px-10 py-6">
        @if($errors->any())
        <div class="mb-5 p-4 bg-red-50 border border-red-200 rounded-xl">
            <div class="flex items-start gap-3">
                <svg class="w-5 h-5 text-red-500 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/></svg>
                <div><p class="text-sm font-semibold text-red-800 mb-1">Fix errors:</p><ul class="text-sm text-red-700 list-disc list-inside">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
            </div>
        </div>
        @endif

        <form action="{{ route('commissions.store') }}" method="POST">
            @csrf
            <div class="grid grid-cols-1 xl:grid-cols-12 gap-6">
                <div class="xl:col-span-8 space-y-5">
                    <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
                        <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/60 flex items-center gap-3">
                            <div class="w-8 h-8 rounded-lg bg-indigo-100 flex items-center justify-center">
                                <svg class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                            </div>
                            <div><h2 class="text-sm font-semibold text-slate-800">Tier Configuration</h2><p class="text-xs text-slate-400">Define the order value range and applicable rate</p></div>
                        </div>
                        <div class="p-6 space-y-5">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Minimum Amount (₦) <span class="text-red-500">*</span></label>
                                    <div class="relative">
                                        <span class="absolute inset-y-0 left-3.5 flex items-center text-slate-400 text-sm pointer-events-none">₦</span>
                                        <input type="number" step="0.01" min="0" name="min_amount" required value="{{ old('min_amount') }}" placeholder="0.00" class="w-full pl-8 pr-4 py-2.5 border border-slate-300 bg-slate-50 rounded-lg text-sm font-mono focus:outline-none focus:ring-2 focus:ring-emerald-500 @error('min_amount') border-red-400 bg-red-50 @enderror">
                                    </div>
                                    @error('min_amount')<p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>@enderror
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Maximum Amount (₦) <span class="text-red-500">*</span></label>
                                    <div class="relative">
                                        <span class="absolute inset-y-0 left-3.5 flex items-center text-slate-400 text-sm pointer-events-none">₦</span>
                                        <input type="number" step="0.01" min="0" name="max_amount" required value="{{ old('max_amount') }}" placeholder="0.00" class="w-full pl-8 pr-4 py-2.5 border border-slate-300 bg-slate-50 rounded-lg text-sm font-mono focus:outline-none focus:ring-2 focus:ring-emerald-500 @error('max_amount') border-red-400 bg-red-50 @enderror">
                                    </div>
                                    @error('max_amount')<p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>@enderror
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-1.5">Commission Rate (%) <span class="text-red-500">*</span></label>
                                <div class="relative max-w-xs">
                                    <input type="number" step="0.01" min="0" max="100" name="percentage" required value="{{ old('percentage') }}" placeholder="e.g. 5.00" class="w-full pr-10 pl-4 py-2.5 border border-slate-300 bg-slate-50 rounded-lg text-sm font-mono focus:outline-none focus:ring-2 focus:ring-emerald-500 @error('percentage') border-red-400 bg-red-50 @enderror">
                                    <span class="absolute inset-y-0 right-3.5 flex items-center text-slate-400 font-semibold pointer-events-none">%</span>
                                </div>
                                @error('percentage')<p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>@enderror
                            </div>
                            {{-- Live Preview --}}
                            <div class="p-4 bg-slate-50 rounded-xl border border-slate-200" id="preview-card">
                                <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">Preview</p>
                                <p class="text-sm text-slate-700">Orders between <strong class="text-slate-900 font-mono" id="prev-min">₦0.00</strong> and <strong class="text-slate-900 font-mono" id="prev-max">₦0.00</strong> will attract a commission of <strong class="text-emerald-700 font-mono text-base" id="prev-pct">0%</strong>.</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="xl:col-span-4 space-y-5">
                    <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
                        <div class="px-5 py-4 border-b border-slate-100 bg-slate-50/60 flex items-center gap-3">
                            <div class="w-8 h-8 rounded-lg bg-blue-100 flex items-center justify-center">
                                <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            </div>
                            <h2 class="text-sm font-semibold text-slate-800">Notes</h2>
                        </div>
                        <div class="p-5 space-y-3">
                            <div class="flex items-start gap-2.5">
                                <div class="w-5 h-5 rounded-full bg-emerald-100 flex items-center justify-center flex-shrink-0 mt-0.5"><svg class="w-3 h-3 text-emerald-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg></div>
                                <p class="text-xs text-slate-500 leading-relaxed">Ranges must not overlap with existing tiers.</p>
                            </div>
                            <div class="flex items-start gap-2.5">
                                <div class="w-5 h-5 rounded-full bg-emerald-100 flex items-center justify-center flex-shrink-0 mt-0.5"><svg class="w-3 h-3 text-emerald-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg></div>
                                <p class="text-xs text-slate-500 leading-relaxed">The rate is a percentage applied to the total order value.</p>
                            </div>
                            <div class="flex items-start gap-2.5">
                                <div class="w-5 h-5 rounded-full bg-emerald-100 flex items-center justify-center flex-shrink-0 mt-0.5"><svg class="w-3 h-3 text-emerald-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg></div>
                                <p class="text-xs text-slate-500 leading-relaxed">Min must be less than Max.</p>
                            </div>
                        </div>
                    </div>
                    <div class="bg-white rounded-xl border border-slate-200 p-5 space-y-3">
                        <button type="submit" class="w-full inline-flex items-center justify-center gap-2 px-5 py-3 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold rounded-lg shadow-sm">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                            Save Tier
                        </button>
                        <a href="{{ route('commissions.index') }}" class="w-full inline-flex items-center justify-center px-5 py-2.5 bg-white border border-slate-300 hover:bg-slate-50 text-slate-700 text-sm font-medium rounded-lg">Cancel</a>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
function fmt(v){return'₦'+parseFloat(v||0).toLocaleString('en-NG',{minimumFractionDigits:2});}
function updatePreview(){
    $('#prev-min').text(fmt($('[name=min_amount]').val()));
    $('#prev-max').text(fmt($('[name=max_amount]').val()));
    const p=$('[name=percentage]').val();
    $('#prev-pct').text(p?p+'%':'0%');
}
$('[name=min_amount],[name=max_amount],[name=percentage]').on('input',updatePreview);
</script>
@endpush
