<?php

namespace App\Http\Controllers;

use App\Http\Requests\CustomerStoreRequest;
use App\Http\Requests\CustomerUpdateRequest;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CustomerController extends Controller
{
    public function index(Request $request): View
    {
        /** @var User $user */
        $user = $request->user();
        $search = $request->string('search')->trim()->toString();

        $customers = $user->customers()
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($subQuery) use ($search): void {
                    $subQuery->where('name', 'like', "%{$search}%")
                        ->orWhere('company_name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('document', 'like', "%{$search}%");
                });
            })
            ->withCount('orders')
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view('customers.index', [
            'customers' => $customers,
            'search' => $search,
        ]);
    }

    public function create(): View
    {
        return view('customers.create', [
            'customer' => new Customer,
        ]);
    }

    public function store(CustomerStoreRequest $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        $user->customers()->create($request->validated());

        return redirect()
            ->route('customers.index')
            ->with('status', 'Cliente criado com sucesso.');
    }

    public function edit(Customer $customer): View
    {
        abort_unless($customer->user_id === auth()->id(), 404);

        return view('customers.edit', [
            'customer' => $customer,
        ]);
    }

    public function update(CustomerUpdateRequest $request, Customer $customer): RedirectResponse
    {
        abort_unless($customer->user_id === auth()->id(), 404);

        $customer->update($request->validated());

        return redirect()
            ->route('customers.index')
            ->with('status', 'Cliente atualizado com sucesso.');
    }

    public function destroy(Customer $customer): RedirectResponse
    {
        abort_unless($customer->user_id === auth()->id(), 404);

        $customer->delete();

        return redirect()
            ->route('customers.index')
            ->with('status', 'Cliente removido com sucesso.');
    }
}
