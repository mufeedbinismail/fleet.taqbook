@extends('layout.base')

@section('body')
    @include('layout.partials.app.header')
    @yield('content')
    @include('layout.partials.app.footer')
@endsection