@extends('layouts.frontend.index')

@section('content')
    @livewire('frontend.ecommerce.thank-you-page', ['orderNumber' => $orderNumber])
@endsection