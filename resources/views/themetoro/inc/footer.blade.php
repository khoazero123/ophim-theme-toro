<div class="top-tags-container" style="text-align: center;">
    <div class="top-tags-title" style="text-align: center;">
        <h2>Top tìm kiếm</h2>
    </div>
    <div class="search-history" style="text-align: center;">
        @foreach($tags as $tag)
            <a href="{{$tag->getUrl()}}"><span class="">{{$tag->name}}</span></a>
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
