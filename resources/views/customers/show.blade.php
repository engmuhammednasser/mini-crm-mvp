<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Customer Details') }}
            </h2>
            <div class="flex space-x-3">
                <a href="{{ route('customers.index') }}" class="text-gray-600 hover:text-gray-900 px-3 py-2">&larr; Back</a>
                <a href="{{ route('customers.edit', $customer) }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:opacity-25 transition ease-in-out duration-150">
                    Edit
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6 text-gray-900 flex items-start space-x-6">
                    <div class="flex-shrink-0 h-24 w-24 bg-indigo-100 rounded-full flex items-center justify-center text-indigo-700 font-bold text-4xl shadow-inner">
                        {{ substr($customer->name, 0, 1) }}
                    </div>
                    <div class="flex-1">
                        <div class="flex justify-between items-start">
                            <div>
                                <h1 class="text-3xl font-bold text-gray-900">{{ $customer->name }}</h1>
                                <p class="text-lg text-gray-500 mt-1">{{ $customer->company ?? 'No Company specified' }}</p>
                            </div>
                            <span class="px-3 py-1 inline-flex text-sm leading-5 font-semibold rounded-full 
                                @if($customer->status == 'Active') bg-green-100 text-green-800 
                                @elseif($customer->status == 'Lead') bg-yellow-100 text-yellow-800
                                @elseif($customer->status == 'Customer') bg-blue-100 text-blue-800
                                @else bg-gray-100 text-gray-800 @endif">
                                {{ $customer->status }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Contact Information -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 border-b border-gray-100">
                        <h3 class="text-lg font-semibold text-gray-800">Contact Information</h3>
                    </div>
                    <div class="p-6 space-y-4">
                        <div>
                            <p class="text-sm font-medium text-gray-500">Email Address</p>
                            <p class="mt-1 text-base text-gray-900"><a href="mailto:{{ $customer->email }}" class="text-indigo-600 hover:text-indigo-900">{{ $customer->email }}</a></p>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-500">Phone Number</p>
                            <p class="mt-1 text-base text-gray-900">{{ $customer->phone ?? 'Not provided' }}</p>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-500">Date Added</p>
                            <p class="mt-1 text-base text-gray-900">{{ $customer->created_at->format('F d, Y h:i A') }}</p>
                        </div>
                    </div>
                </div>

                <!-- Notes -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 border-b border-gray-100">
                        <h3 class="text-lg font-semibold text-gray-800">Notes</h3>
                    </div>
                    <div class="p-6">
                        @if($customer->notes)
                            <div class="prose max-w-none text-gray-700 whitespace-pre-wrap">{{ $customer->notes }}</div>
                        @else
                            <p class="text-gray-500 italic">No notes available for this customer.</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
