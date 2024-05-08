<li class="TPostMv">
    <article class="TPost B">
        <a href="{{$movie->getUrl()}}">
            <div class="Image">
                <figure class="Objf TpMvPlay AAIco-play_arrow">
                    <img loading="lazy" src="{{$movie->getThumbUrl()}}"
                         alt="{{$movie->name}}">
                </figure>
                <span class="MvIC"><span class="Qlty Yr">{{$movie->publish_year}}</span></span>
            </div>
            <h2 class="Title">{{$movie->name}}</h2>
        </a>
        <div class="TPMvCn">
            <a href="{{$movie->getUrl()}}">
                <div class="Title">{{$movie->name}}</div>
            </a>
            <p>{{$movie->origin_name}}</p>
            <div class="Info">
                <div class="Vote">
                    <div class="post-ratings">
                        <img src="{{ asset('themes/toro/img/cnt/rating_on.gif') }}" alt="img"><span style="font-size: 12px;">{{$movie->getRatingStar()}}</span>
                    </div>
                </div>
                <span class="Date">{{$movie->publish_year}}</span>
                <span class="Qlty">{{$movie->quality}}</span>
                <span class="Qlty">{{$movie->language}}</span>
                <span class="Time">{{$movie->episode_time}}</span>
                @if ($movie->views)
                <span class="Views AAIco-remove_red_eye">{{$movie->views}}</span>
                @endif
            </div>
            <div class="Description">
                <p>{!! mb_substr(strip_tags($movie->content),0,100, "utf-8") !!}...</p>
                <a href="{{$movie->getUrl()}}" class="TPlay AAIco-play_circle_outline"><strong>Xem phim</strong></a>
            </div>
    </article>
</li>
