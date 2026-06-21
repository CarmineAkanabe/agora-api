<?php

namespace App\Http\Controllers;

use App\Http\Requests\Admin\StoreCategoryRequest;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Str;

class CategoryController extends Controller
{
    public function index(): JsonResponse
    {
        $categories = cache()->remember('categories', now()->addDay(), fn() =>
            Category::all()
        );

        return response()->json(CategoryResource::collection($categories));
    }

    public function store(StoreCategoryRequest $request): JsonResponse
    {
        $category = Category::create([
            'name' => $request->validated()['name'],
            'slug' => Str::slug($request->validated()['name']),
        ]);

        cache()->forget('categories');

        return response()->json(new CategoryResource($category), 201);
    }

    public function update(StoreCategoryRequest $request, Category $category): JsonResponse
    {
        $category->update([
            'name' => $request->validated()['name'],
            'slug' => Str::slug($request->validated()['name']),
        ]);

        cache()->forget('categories');

        return response()->json(new CategoryResource($category));
    }

    public function destroy(Category $category): JsonResponse
    {
        if ($category->listings()->exists()) {
            return response()->json([
                'message' => 'Cannot delete a category that has listings.'
            ], 409);
        }

        $category->delete();
        cache()->forget('categories');

        return response()->json(['message' => 'Category deleted.']);
    }
}
