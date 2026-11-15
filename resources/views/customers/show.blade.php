@extends('layouts.app')
@section('title', 'Customer Details')
@section('content')
    <h1>Customer Details</h1>
    <dl>
        <dt>Full Name</dt><dd>{{ $customer->full_name }}</dd>
        <dt>Email</dt><dd>{{ $customer->email }}</dd>
        <dt>Phone</dt><dd>{{ $customer->phone }}</dd>
    </dl>
    <a href="{{ route('customers.edit', $customer) }}">Edit</a>
    <a href="{{ route('customers.index') }}">Back to list</a>
@endsection
