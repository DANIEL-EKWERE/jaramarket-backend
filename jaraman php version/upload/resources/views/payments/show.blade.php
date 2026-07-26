<pre class="overflow-x-auto text-sm">
                                        {{ json_encode($payment->metadata, JSON_PRETTY_PRINT) }}
                                    </pre>
</div>
</div>
@endif
</div>

<div class="mt-6 flex justify-end">
    <a href="{{ route('payments.index') }}"
        class="bg-white py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 hover:bg-gray-50">
        Back to Payments
    </a>
</div>
</div>
</div>
</div>
</div>
@endsection
