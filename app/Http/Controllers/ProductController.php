<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProductStoreRequest;
use App\Http\Requests\ProductUpdateRequest;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function index(Request $request): View
    {
        /** @var User $user */
        $user = $request->user();
        $search = $request->string('search')->trim()->toString();

        $products = $user->products()
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($subQuery) use ($search): void {
                    $subQuery->where('name', 'like', "%{$search}%")
                        ->orWhere('sku', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            })
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view('products.index', [
            'products' => $products,
            'search' => $search,
        ]);
    }

    public function create(): View
    {
        return view('products.create', [
            'product' => new Product,
        ]);
    }

    public function store(ProductStoreRequest $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        $payload = $request->validated();
        $payload['is_active'] = $request->boolean('is_active', true);

        $user->products()->create($payload);

        return redirect()
            ->route('products.index')
            ->with('status', 'Produto criado com sucesso.');
    }

    public function edit(Product $product): View
    {
        abort_unless($product->user_id === auth()->id(), 404);

        return view('products.edit', [
            'product' => $product,
        ]);
    }

    public function update(ProductUpdateRequest $request, Product $product): RedirectResponse
    {
        abort_unless($product->user_id === auth()->id(), 404);

        $payload = $request->validated();
        $payload['is_active'] = $request->boolean('is_active');

        $product->update($payload);

        return redirect()
            ->route('products.index')
            ->with('status', 'Produto atualizado com sucesso.');
    }

    public function destroy(Product $product): RedirectResponse
    {
        abort_unless($product->user_id === auth()->id(), 404);

        $product->delete();

        return redirect()
            ->route('products.index')
            ->with('status', 'Produto removido com sucesso.');
    }
}
