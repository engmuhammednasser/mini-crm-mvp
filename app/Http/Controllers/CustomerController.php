<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CustomerController extends Controller
{
    public function dashboard()
    {
        $totalCustomers = Customer::count();
        $newLeads = Customer::where('status', 'Lead')->count();
        $activeCustomers = Customer::where('status', 'Active')->count();
        $latestCustomers = Customer::latest()->take(5)->get();

        return view('dashboard', compact('totalCustomers', 'newLeads', 'activeCustomers', 'latestCustomers'));
    }

    public function index()
    {
        $customers = Customer::latest()->paginate(10);
        return view('customers.index', compact('customers'));
    }

    public function create()
    {
        return view('customers.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:customers',
            'phone' => 'nullable|string|max:20',
            'company' => 'nullable|string|max:255',
            'status' => ['required', Rule::in(['Lead', 'Customer', 'Active', 'Inactive'])],
            'notes' => 'nullable|string',
        ]);

        Customer::create($validated);

        return redirect()->route('customers.index')->with('success', 'Customer created successfully.');
    }

    public function show(Customer $customer)
    {
        return view('customers.show', compact('customer'));
    }

    public function edit(Customer $customer)
    {
        return view('customers.edit', compact('customer'));
    }

    public function update(Request $request, Customer $customer)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('customers')->ignore($customer->id)],
            'phone' => 'nullable|string|max:20',
            'company' => 'nullable|string|max:255',
            'status' => ['required', Rule::in(['Lead', 'Customer', 'Active', 'Inactive'])],
            'notes' => 'nullable|string',
        ]);

        $customer->update($validated);

        return redirect()->route('customers.index')->with('success', 'Customer updated successfully.');
    }

    public function destroy(Customer $customer)
    {
        $customer->delete();
        return redirect()->route('customers.index')->with('success', 'Customer deleted successfully.');
    }
}
