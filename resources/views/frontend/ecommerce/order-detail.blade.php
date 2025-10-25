@extends('layouts.frontend.index')

@section('content')
    @livewire('frontend.ecommerce.order-detail', ['orderNumber' => $orderNumber])
@endsection