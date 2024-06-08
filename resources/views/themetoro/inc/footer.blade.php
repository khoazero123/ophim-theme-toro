
<div class="top-tags-container" style="text-align: center;">
    @if ($ads_footer = get_theme_option('ads_footer'))
        {!! $ads_footer !!}
    @endif
    <div class="top-tags-title" style="text-align: center;">
        <h2>Top tìm kiếm</h2>
    </div>
    <div class="search-history" style="text-align: center;">
        @foreach($tags as $tag)
            <a href="{{$tag['search_url']}}"><span class="">{{$tag['name']}}</span></a>
        @endforeach
    </div>
</div>

<style>

.search-history a {
    display: inline-block;
    font-size: 14px;
    padding: 5px 10px;
    margin-top: 5px;
    white-space: nowrap;
    background: #2b2b2b;
    border-radius: 4px;
}

</style>
