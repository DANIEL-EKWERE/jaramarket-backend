@extends('layouts.app')

@section('title', 'Create Advertisement')
    Add New Adverts
@endsection

@section('content')
<div class="bg-white shadow overflow-hidden sm:rounded-lg">
    <div class="px-4 py-5 sm:px-6">
        <h3 class="text-lg leading-6 font-medium text-gray-900">
            Create New Adverts
        </h3>
        <p class="mt-1 max-w-2xl text-sm text-gray-500">
            Add a new Adverts.
        </p>
    </div>
    @php
        use App\Enums\AdvertTypeEnum;
    @endphp
    <div class="border-t border-gray-200 px-4 py-5 sm:px-6">
        <form action="{{ route('advertisements.store') }}" method="POST" enctype="multipart/form-data" class="space-y-6">
            @csrf

            <div class="col-span-6 sm:col-span-4">
                <label for="type" class="block text-sm font-medium text-gray-700">Type</label>
                    <select name="type" id="type" class="mt-1 focus:ring-green-500 focus:border-green-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
                           <option value="">select</option>
                            @foreach(AdvertTypeEnum::cases() as $type)
                                <option value="{{ $type->value }}">{{ ucfirst(str_replace('_', ' ', $type->name)) }}</option>
                            @endforeach
                    </select>
            </div>

            <div class="col-span-6 sm:col-span-4">
                <label for="value" class="block text-sm font-medium text-gray-700">Value</label>
                <input type="number" step="0.01" min="0" name="value" id="value" class="mt-1 focus:ring-green-500 focus:border-green-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
            </div>
        
            <div class="col-span-6 sm:col-span-4">
                <label for="ingredients" class="block text-sm font-medium text-gray-700">Ingredients</label>
                    <select name="ingredient_ids[]" id="ingredient_ids" multiple class="mt-1 focus:ring-green-500 focus:border-green-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
                           <option value="">select</option>
                            @foreach($ingredients as $row)
                                <option value="{{$row->id}}">{{$row->name}}</option>
                            @endforeach
                    </select>
            </div>

            <div class="col-span-6">
                <label for="image" class="block text-sm font-medium text-gray-700">Image</label>
                <div class="mt-1 flex items-center">
                    <img id="image-preview" class="hidden h-32 w-32 object-cover rounded-lg" src="#" alt="Image preview">
                    <div class="ml-4">
                        <input type="file" name="image" id="image" accept="image/*" 
                            class="shadow-sm focus:ring-green-500 focus:border-green-500 block w-full sm:text-sm border-gray-300 rounded-md @error('image') border-red-500 @enderror"
                            onchange="previewImage(this)">
                    </div>
                </div>
                @error('image')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex justify-end">
                <a href="{{ route('advertisements.index') }}" class="bg-white py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                    Cancel
                </a>
                <button type="submit" class="ml-3 inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                    Save
                </button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
function previewImage(input) {
    const preview = document.getElementById('image-preview');
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            preview.src = e.target.result;
            preview.classList.remove('hidden');
        }
        reader.readAsDataURL(input.files[0]);
    }
}
</script>
@endpush
@endsection 