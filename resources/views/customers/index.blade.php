@extends('layouts.app')

@section('title', 'Customers')

@section('content')
    <h1>Customers</h1>
    <p><a href="{{ route('customers.create') }}">Create Customer</a></p>
    <table>
        <thead><tr><th>Full Name</th><th>Email</th><th>Phone</th><th>Action</th></tr></thead>
        <tbody>
        @forelse ($customers as $customer)
            <tr>
                <td>{{ $customer->full_name }}</td>
                <td>{{ $customer->email }}</td>
                <td>{{ $customer->phone }}</td>
                <td>
                    <a href="{{ route('customers.show', $customer) }}">View</a>
                    <a href="{{ route('customers.edit', $customer) }}">Edit</a>
                    
                    <form method="POST" action="{{ route('customers.destroy', $customer) }}">
                        @csrf
                        @method('DELETE')
                        <button type="submit">Delete</button>
                    </form>
                    
                </td>
            </tr>
        @empty
            <tr><td colspan="4">No customers found.</td></tr>
        @endforelse
        </tbody>
    </table>
@endsection
