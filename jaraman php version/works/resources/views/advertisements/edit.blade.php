@extends('layouts.app')

@section('title', 'Edit Advertisement')
    Edit Ingredient
@endsection

@section('content')
<div class="bg-white shadow overflow-hidden sm:rounded-lg">
    <div class="px-4 py-5 sm:px-6">
        <h3 class="text-lg leading-6 font-medium text-gray-900">
            Edit Ingredient
        </h3>
        <p class="mt-1 max-w-2xl text-sm text-gray-500">
            Update ingredient details.
        </p>
    </div>

    <div class="border-t border-gray-200 px-4 py-5 sm:px-6">
        <form action="{{ route('ingredients.update', $ingredient) }}" method="POST" enctype="multipart/form-data" class="space-y-6">
            @csrf
            @method('PUT')

            <div class="col-span-6 sm:col-span-4">
                <label for="name" class="block text-sm font-medium text-gray-700">Name</label>
                <input type="text" name="name" id="name" value="{{ old('name', $ingredient->name) }}" class="mt-1 focus:ring-green-500 focus:border-green-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
            </div>

            <div class="col-span-6 sm:col-span-4">
                <label for="price" class="block text-sm font-medium text-gray-700">Price</label>
                <input type="number" step="0.01" min="0" name="price" id="price" value="{{ old('price', $ingredient->price) }}" class="mt-1 focus:ring-green-500 focus:border-green-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
            </div>

            <div class="col-span-6 sm:col-span-4">
                <label for="discounted_price" class="block text-sm font-medium text-gray-700">Discounted Price (Optional)</label>
                <input type="number" step="0.01" min="0" name="discounted_price" id="discounted_price" value="{{ old('discounted_price', $ingredient->discounted_price) }}" class="mt-1 focus:ring-green-500 focus:border-green-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
            </div>

            <div class="col-span-6 sm:col-span-4">
                <label for="category_id" class="block text-sm font-medium text-gray-700">Category</label>
                <select name="category_id" id="category_id" class="mt-1 focus:ring-green-500 focus:border-green-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
                    <option value="">select</option>
                    @foreach($categories as $row)
                        <option {{ $ingredient->category_id === $row->id ? 'selected' : ''}} value="{{$row->id}}">{{$row->name}}</option>
                    @endforeach
                </select>
            </div>


            <div class="col-span-6 sm:col-span-4">
                <label for="unit" class="block text-sm font-medium text-gray-700">Unit</label>
                <select name="unit" id="unit" class="mt-1 focus:ring-green-500 focus:border-green-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
                    <option value="">select</option>
                    @foreach($units as $row)
                        <option {{ $ingredient->unit === $row->code ? 'selected' : ''}} value="{{$row->code}}">{{$row->name}}</option>
                    @endforeach
                </select>
            </div>

            <div class="col-span-6 sm:col-span-4">
                <label for="stock" class="block text-sm font-medium text-gray-700">Stock</label>
                <input type="number" min="0" name="stock" id="stock" value="{{ old('stock', $ingredient->stock) }}" class="mt-1 focus:ring-green-500 focus:border-green-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
            </div>

            <div class="col-span-6">
                <label for="description" class="block text-sm font-medium text-gray-700">Description</label>
                <textarea name="description" id="description" rows="3" class="mt-1 focus:ring-green-500 focus:border-green-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">{{ old('description', $ingredient->description) }}</textarea>
            </div>

            <div class="col-span-6">
                <label for="image" class="block text-sm font-medium text-gray-700">Image</label>
                <div class="mt-1 flex items-center">
                    <img id="image-preview" class="h-32 w-32 object-cover rounded-lg" src="{{ $ingredient->image_url }}" alt="Current image">
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
                <a href="{{ route('ingredients.index') }}" class="bg-white py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                    Cancel
                </a>
                <button type="submit" class="ml-3 inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                    Update
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
        }
        reader.readAsDataURL(input.files[0]);
    }
}
</script>
@endpush
@endsection 