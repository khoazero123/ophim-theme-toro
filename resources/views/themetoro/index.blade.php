@extends('themes::themetoro.layout')

@section('home_page_slider_poster')
    @if($home_page_slider_poster)
        @include('themes::themetoro.inc.home_page_slider_poster')
    @endif
@endsection

@section('home_page_slider_thumb')
    @if($home_page_slider_thumb)
        @include('themes::themetoro.inc.home_page_slider_thumb')
    @endif
@endsection

@section('content')
    @foreach($movies_latest as $item)
        @include("themes::themetoro.inc.section." . $item["show_template"])
        @if ($item['data']->hasPages() && $item['data']->lastPage() > 1)
        {{ $item['data']->links('themes::themetoro.inc.pagination') }}
        @endif
    @endforeach
@endsection

@push('scripts')
@endpush
