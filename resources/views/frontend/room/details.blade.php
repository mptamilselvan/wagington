@extends('layouts.frontend.index')

@section('content')
    @livewire('frontend.room.details', ['slug' => $slug])
@endsection