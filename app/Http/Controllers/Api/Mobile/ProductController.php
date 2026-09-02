<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Mobile\ProductStoreRequest;
use App\Http\Requests\Api\Mobile\ProductUpdateRequest;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ProductController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $products = $user->products()
            ->orderBy('name')
            ->get();

        return response()->json([
            'data' => $products->map(fn (Product $product): array => $this->payload($product))->values(),
        ]);
    }

    public function store(ProductStoreRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $payload = $request->validated();
        $payload['is_active'] = $request->boolean('is_active', true);

        $product = $user->products()->create($payload);

        return response()->json([
            'data' => $this->payload($product),
        ], Response::HTTP_CREATED);
    }

    public function show(Request $request, Product $product): JsonResponse
    {
        abort_unless($product->user_id === $request->user()?->id, 404);

        return response()->json([
            'data' => $this->payload($product),
        ]);
    }

    public function update(ProductUpdateRequest $request, Product $product): JsonResponse
    {
        abort_unless($product->user_id === $request->user()?->id, 404);

        $payload = $request->validated();
        $payload['is_active'] = $request->boolean('is_active');

        $product->update($payload);

        return response()->json([
            'data' => $this->payload($product->fresh() ?? $product),
        ]);
    }

    public function destroy(Request $request, Product $product): JsonResponse
    {
        abort_unless($product->user_id === $request->user()?->id, 404);

        $product->delete();

        return response()->json([
            'message' => 'Produto removido com sucesso.',
        ]);
    }

    private function payload(Product $product): array
    {
        return [
            'id' => $product->id,
            'name' => $product->name,
            'sku' => $product->sku,
            'description' => $product->description,
            'price' => (float) $product->price,
            'is_active' => (bool) $product->is_active,
            'created_at' => $product->created_at?->toIso8601String(),
            'updated_at' => $product->updated_at?->toIso8601String(),
        ];
    }
}
