@extends('themes::themetoro.layout')

@section('home_page_slider_poster')
    @if(count($home_page_slider_poster))
        @include('themes::themetoro.inc.home_page_slider_poster')
    @endif
@endsection

@section('home_page_slider_thumb')
    @if(count($home_page_slider_thumb))
        @include('themes::themetoro.inc.home_page_slider_thumb')
    @endif
@endsection

@section('content')
    @foreach($data as $item)
        @include("themes::themetoro.inc.section." . $item["show_template"])
    @endforeach
@endsection

@push('scripts')
@endpush
