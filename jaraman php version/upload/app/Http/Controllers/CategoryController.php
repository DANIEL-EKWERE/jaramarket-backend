<?php

namespace App\Http\Controllers;

use Exception;
use Illuminate\Support\Facades\DB;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Http\Controllers\Controller;
use App\Models\CategoryType;
use Yajra\DataTables\Facades\DataTables;

class CategoryController extends Controller
{
    public function index()
    {
        return view('categories.index');
    }

    public function getData()
    {
        $sub = DB::table('category_product')
        ->select('category_id', DB::raw('COUNT(product_id) as products_count'))
        ->groupBy('category_id');

    $query = DB::table('categories')
        ->leftJoinSub($sub, 'cp', function($join){
            $join->on('categories.id', '=', 'cp.category_id');
        })
        ->leftJoin('category_types', 'categories.category_type_id', '=', 'category_types.id')
        ->select(
            'categories.id',
            'categories.name',
            'categories.description',
            'categories.sort_by',
            'categories.created_at',
            'category_types.name as type_name',
            DB::raw('COALESCE(cp.products_count, 0) as products_count')
        );

    return DataTables::of($query)
        ->addIndexColumn()
        ->addColumn('actions', function($row) {
            return '
                <a href="'.route('categories.edit', $row->id).'" class="text-green-600 hover:text-green-900 mr-3">Edit</a>
                <button type="button" data-id="'.$row->id.'" class="text-red-600 hover:text-red-900 delete-category">Delete</button>
            ';
        })
        ->rawColumns(['actions'])
        ->make(true);
    }

    public function show($id)
    {
        $category = Category::with(['products','category_type'])->findOrFail($id);
        
        if (request()->wantsJson()) {
            return response()->json($category);
        }

        return view('categories.show', compact('category'));
    }
    
    public function store(Request $request)
    {
        try{
            $validated = $request->validate([
                'name' => [
                    'required',
                    'string',
                    'max:255',
                    Rule::unique('categories')->whereNull('deleted_at'),
                ],
                'description' => 'nullable|string',
                'category_type_id' => 'required|exists:category_types,id',
                'sort_by' => 'nullable|integer',
            ]);

            $category = Category::create($validated);

            if ($request->wantsJson()) {
                return response()->json($category, 201);
            }

            return redirect()->back()
                ->with('success', 'Category created successfully.');
        }catch (Exception $e) {      
            return redirect()->back()
                ->with('error', $e->getMessage());
        }
    }

    
    public function update(Request $request, $id)
    {
        try{
            $category = Category::findOrFail($id);

            $validated = $request->validate([
                'name' => 'required|string|max:255|unique:categories,name,' . $category->id,
                'description' => 'nullable|string',
                'category_type_id' => 'required|exists:category_types,id',
                'sort_by' => 'nullable|integer'
            ]);

            $category->update($validated);

            if ($request->wantsJson()) {
                return response()->json(['message' => 'Category updated successfully']);
            }

            return redirect()->back()
                ->with('success', 'Category updated successfully.');
        }catch (Exception $e) {      
            return redirect()->back()
                ->with('error', $e->getMessage());
        }
    }

    public function destroy($id)
    {
        try{
            $category = Category::findOrFail($id);
            $category->delete();

            if (request()->wantsJson()) {
                return response()->json(['message' => 'Category deleted successfully']);
            }

            return redirect()->back()
                ->with('success', 'Category deleted successfully.');
        } catch (Exception $e) {      
            return redirect()->back()
                ->with('error', $e->getMessage());
        }
    }

    public function create()
    {
        $category_types = CategoryType::all();
        return view('categories.create', compact('category_types'));
    }

    public function edit($id)
    {
        $category = Category::findOrFail($id);
        $category_types = CategoryType::all();
        return view('categories.edit', compact(['category', 'category_types']));
    }
}
