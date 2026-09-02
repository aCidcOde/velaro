<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Http\Requests\CustomerUpdateRequest;
use App\Models\Customer;
use App\Services\AdminAuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CustomerController extends Controller
{
    public function __construct(private readonly AdminAuditLogger $auditLogger) {}

    public function index(Request $request): View
    {
        $search = $request->string('search')->trim()->toString();

        $customers = Customer::query()
            ->with('user:id,name,email')
            ->withCount('orders')
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($subQuery) use ($search): void {
                    $subQuery->where('name', 'like', "%{$search}%")
                        ->orWhere('company_name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('document', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('backend.customers.index', [
            'customers' => $customers,
            'search' => $search,
        ]);
    }

    public function show(Customer $customer): View
    {
        $customer->load(['user:id,name,email', 'orders']);

        return view('backend.customers.show', [
            'customer' => $customer,
        ]);
    }

    public function edit(Customer $customer): View
    {
        return view('backend.customers.edit', [
            'customer' => $customer,
        ]);
    }

    public function update(CustomerUpdateRequest $request, Customer $customer): RedirectResponse
    {
        $before = $customer->toArray();

        $customer->update($request->validated());

        $this->auditLogger->log('backend.customer.update', $customer, $before, $customer->fresh()?->toArray());

        return redirect()
            ->route('backend.customers.show', $customer)
            ->with('status', 'Cliente atualizado com sucesso.');
    }

    public function destroy(Customer $customer): RedirectResponse
    {
        $this->auditLogger->log('backend.customer.destroy', $customer, $customer->toArray(), null);

        $customer->delete();

        return redirect()
            ->route('backend.customers.index')
            ->with('status', 'Cliente removido com sucesso.');
    }
}
