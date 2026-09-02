<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProductUpdateRequest;
use App\Models\Product;
use App\Services\AdminAuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function __construct(private readonly AdminAuditLogger $auditLogger) {}

    public function index(Request $request): View
    {
        $search = $request->string('search')->trim()->toString();

        $products = Product::query()
            ->with('user:id,name,email')
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($subQuery) use ($search): void {
                    $subQuery->where('name', 'like', "%{$search}%")
                        ->orWhere('sku', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('backend.products.index', [
            'products' => $products,
            'search' => $search,
        ]);
    }

    public function show(Product $product): View
    {
        $product->load('user:id,name,email');

        return view('backend.products.show', [
            'product' => $product,
        ]);
    }

    public function edit(Product $product): View
    {
        return view('backend.products.edit', [
            'product' => $product,
        ]);
    }

    public function update(ProductUpdateRequest $request, Product $product): RedirectResponse
    {
        $before = $product->toArray();

        $payload = $request->validated();
        $payload['is_active'] = $request->boolean('is_active');

        $product->update($payload);

        $this->auditLogger->log('backend.product.update', $product, $before, $product->fresh()?->toArray());

        return redirect()
            ->route('backend.products.show', $product)
            ->with('status', 'Produto atualizado com sucesso.');
    }

    public function destroy(Product $product): RedirectResponse
    {
        $this->auditLogger->log('backend.product.destroy', $product, $product->toArray(), null);

        $product->delete();

        return redirect()
            ->route('backend.products.index')
            ->with('status', 'Produto removido com sucesso.');
    }
}
