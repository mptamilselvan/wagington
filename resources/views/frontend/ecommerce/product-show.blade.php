@extends('layouts.frontend.index')

@section('content')
    @livewire('frontend.ecommerce.product-show', ['slug' => $slug])
@endsection