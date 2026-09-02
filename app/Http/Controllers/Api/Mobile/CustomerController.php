<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Mobile\CustomerStoreRequest;
use App\Http\Requests\Api\Mobile\CustomerUpdateRequest;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CustomerController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $customers = $user->customers()
            ->withCount('orders')
            ->orderBy('name')
            ->get();

        return response()->json([
            'data' => $customers->map(fn (Customer $customer): array => $this->payload($customer))->values(),
        ]);
    }

    public function store(CustomerStoreRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $customer = $user->customers()->create($request->validated());

        return response()->json([
            'data' => $this->payload($customer),
        ], Response::HTTP_CREATED);
    }

    public function show(Request $request, Customer $customer): JsonResponse
    {
        abort_unless($customer->user_id === $request->user()?->id, 404);

        return response()->json([
            'data' => $this->payload($customer),
        ]);
    }

    public function update(CustomerUpdateRequest $request, Customer $customer): JsonResponse
    {
        abort_unless($customer->user_id === $request->user()?->id, 404);

        $customer->update($request->validated());

        return response()->json([
            'data' => $this->payload($customer->fresh() ?? $customer),
        ]);
    }

    public function destroy(Request $request, Customer $customer): JsonResponse
    {
        abort_unless($customer->user_id === $request->user()?->id, 404);

        $customer->delete();

        return response()->json([
            'message' => 'Cliente removido com sucesso.',
        ]);
    }

    private function payload(Customer $customer): array
    {
        return [
            'id' => $customer->id,
            'name' => $customer->name,
            'company_name' => $customer->company_name,
            'email' => $customer->email,
            'phone' => $customer->phone,
            'document' => $customer->document,
            'notes' => $customer->notes,
            'orders_count' => $customer->orders_count ?? $customer->orders()->count(),
            'created_at' => $customer->created_at?->toIso8601String(),
            'updated_at' => $customer->updated_at?->toIso8601String(),
        ];
    }
}
