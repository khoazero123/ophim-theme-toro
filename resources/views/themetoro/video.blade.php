@extends('themes::themetoro.layout')

@push('header')
@endpush

@section('single_top')
    <div class="TPost A D">
        <div class="Container">
        <div class="optns-bx">
                <div class="drpdn">

                </div>
            </div>
            <div class="VideoPlayer">
                <div id="VideoOption01" class="Video on">

                </div>
                <span class="BtnLight AAIco-lightbulb_outline lgtbx-lnk"></span>
                <span class="lgtbx"></span>
                <div class="navepi tagcloud">
                </div>
            </div>
            <div class="Image">
                <figure class="Objf"><img src="{{$currentMovie->getPosterUrl()}}" alt="{{$currentMovie->name}}"></figure>
            </div>
        </div>
    </div>

    @if ($ads_below_player = get_theme_option('ads_below_player'))
    <div class="Main Container ads_below_player">
        {!! $ads_below_player !!}
    </div>
    @endif

    <div class="Main Container">
        <section class="SeasonBx AACrdn">
            <div class="Top AAIco-playlist_play AALink episodes-view episodes-load">
                <div class="Title"><a href="#">Danh sách tập <span></span></a></div>
            </div>
            <ul class="AZList">

            </ul>
        </section>
    </div>

@endsection

@section('content')
    <article class="TPost A">
        <header class="Container">
            <div class="TPMvCn">
                <a href="javascript:void(0)"><h1 class="Title">{{$currentMovie->name}}</h1></a>
                <ul class="ShareList">
                    <li><a href="javascript:void(0)"
                           title="Chia sẻ lên facebook"
                           onclick="window.open ('http://www.facebook.com/sharer.php?u={{$currentMovie->getUrl()}}', 'Facebook', 'toolbar=0, status=0, width=650, height=450');"
                           class="fa-facebook"></a></li>
                    <li><a href="javascript:void(0)"
                           title="Chia sẻ lên twitter"
                           onclick="javascript:window.open('https://twitter.com/intent/tweet?original_referer={{$currentMovie->getUrl()}}&amp;text={{$currentMovie->name}}&amp;tw_p=tweetbutton&amp;url={{$currentMovie->getUrl()}}', 'Twitter', 'toolbar=0, status=0, width=650, height=450');"
                           class="fa-twitter"></a></li>
                </ul>
                <div class="Info">
                    <div class="Vote">
                        <div class="post-ratings">
                            <img src="{{asset('themes/toro/img/cnt/rating_on.gif')}}" alt="img"><span
                                style="font-size: 12px;">{{$currentMovie->getRatingStar()}}</span>
                        </div>
                    </div>
                    <span class="Date">{{ $currentMovie->publish_year }}</span>
                    <span class="Qlty">
                            @switch($currentMovie->status)
                            @case("ongoing")
                                Đang chiếu
                                @break
                            @case("completed")
                                Trọn bộ
                                @break
                            @default
                                Trailer
                        @endswitch
                        </span>
                    <span class="Time">{{$currentMovie->episode_time}}</span>
                    @if ($currentMovie->views)
                    <span class="Views AAIco-remove_red_eye">{{$currentMovie->views}}</span>
                    @endif
                    {!! $currentMovie->renderRegionsListHtml() !!}
                </div>
                <div class="Description">
                    <p>{!! $currentMovie->content !!}</p>
                    <p class="Director">
                        <span>Đạo diễn:</span>
                        {!! $currentMovie->renderDirectorsListHtml() !!}
                    </p>
                    <p class="Genre">
                        <span>Thể loại:</span>
                        {!! $currentMovie->renderCategoriesListHtml() !!}
                    </p>
                    <p class="Genre">
                        <span>Tag:</span>
                        {!! $currentMovie->renderTagsListHtml() !!}
                    </p>
                    <p class="Cast">
                        <span>Diễn viên:</span>
                        {!! $currentMovie->renderActorsListHtml() !!}
                    </p>
                </div>
                @if ($currentMovie->trailer_url && strpos($currentMovie->trailer_url, 'youtube'))
                    <a href="javascript:void(0)" id="watch-trailer" class="Button TPlay AAIco-play_circle_outline"><strong>Xem Trailer</strong></a>
                @endif

                <div class="rating-content">
                    <div id="movies-rating-star" style="height: 18px;"></div>
                    <div>
                        ({{ $currentMovie->getRatingStar() }}
                        sao
                        /
                        {{$currentMovie->getRatingCount()}} đánh giá)
                    </div>
                    <div id="movies-rating-msg"></div>
                </div>

            </div>
        </header>
    </article>

    <section>
        <div class="Top AAIco-chat">
            <div class="Title">Bình luận</div>
        </div>
        <ul class="CommentsList">
            <div style="width: 100%; background-color: #fff">
                <div style="width: 100%; background-color: #fff" class="fb-comments" data-href="{{ $currentMovie->getUrl() }}" data-width="100%"
                     data-colorscheme="light" data-numposts="5" data-order-by="reverse_time" data-lazy="true"></div>
            </div>
        </ul>
    </section>

    @if ($ads_below_comment = get_theme_option('ads_below_comment'))
    <section>
        <div class="ads_below_comment">
            {!! $ads_below_comment !!}
        </div>
    </section>
    @endif

    <section>
        <div class="Top AAIco-star_border">
            <h3 class="Title">Có thể bạn muốn xem?</h3>
        </div>
        <div class="episode-blade MovieListRelated owl-carousel" data-total-item="{{count($movie_related)}}" data-per-row="{{get_theme_option('movie_related_per_row', 5)}}" data-max-row="{{get_theme_option('movie_related_max_row', 2)}}">
            @foreach($movie_related as $i => $movie)
                <div class="TPostMv slide" data-slide-index="{{$i}}">
                    <div class="TPost B">
                        <a href="{{$movie->getUrl()}}">
                            <div class="Image">
                                <figure class="Objf TpMvPlay AAIco-play_arrow">
                                    <img loading="lazy" class="owl-lazy"
                                         data-src="{{$movie->getThumbUrl()}}"
                                         alt="{{$movie->name}}">
                                </figure>
                                <span class="Qlty">{{$movie->episode_current}}</span>
                            </div>
                            <h2 class="Title">{{$movie->name}}</h2>
                        </a>
                    </div>
                </div>
            @endforeach
        </div>
        <style>
            .slide {
                font-size: 50px;
                text-align: center;
                border: 1px solid black;
                margin-bottom: 20px;
            }
        </style>
    </section>

@endsection

@push('scripts')
    <script src="/player/v/8.18.4/jwplayer.js"></script>

    <script>
        var video_id = '{{ $video->id }}';
        var video_ext = '{{ $video->file_ext }}';
        var video_link = '{{ $video->getPlayableUrl() }}';
        var jwplayer_advertising_file = "{{ Setting::get('jwplayer_advertising_file') }}";
        var jwplayer_advertising_skipoffset = "{{ (int) Setting::get('jwplayer_advertising_skipoffset') ?: 5 }}";
        var jwplayer_key = "{{ Setting::get('jwplayer_license') }}";
        var jwplayerOptions = JSON.parse(`{!! Setting::get('jwplayer_options') !!}`) || {};
        if (jwplayer_key) {
            jwplayerOptions.key = jwplayer_key;
        }
        if (jwplayer_advertising_file) {
            jwplayerOptions.advertising = {
                tag: jwplayer_advertising_file,
                client: "vast",
                vpaidmode: "insecure",
                skipoffset: jwplayer_advertising_skipoffset,
                skipmessage: "Bỏ qua sau xx giây",
                skiptext: "Bỏ qua"
            };
        }

        jwplayerOptions = Object.assign({}, {
            // aspectratio: "16:9",
            width: "100%",
            height: 720,
            responsive: true,
            playbackRateControls: true,
            playbackRates: [0.25, 0.75, 1, 1.25],
            sharing: { sites: ["reddit", "facebook", "twitter", "googleplus", "email", "linkedin", ]},
            volume: 100,
            mute: false,
            autostart: false,
            logo: {
                file: "{{ Setting::get('jwplayer_logo_link') }}",
                position: "{{ Setting::get('jwplayer_logo_position') }}",
            },
        }, jwplayerOptions);

        const wrapper = document.getElementById('VideoOption01');

        renderPlayer(video_ext, video_link, video_id);

        function renderPlayer(type, link, id) {
            // console.log('renderPlayer', {type, link, id});
            // if (type == 'm3u8' || type == 'mp4') {}

            jwplayerOptions = Object.assign({}, {
                file: link,
            }, jwplayerOptions);

            wrapper.innerHTML = `<div id="jwplayer"></div>`;
            const player = jwplayer("jwplayer");

            player.setup(jwplayerOptions);

            const resumeData = 'OPCMS-PlayerPosition-' + id;

            player.on('ready', function() {
                if (typeof(Storage) !== 'undefined') {
                    if (localStorage[resumeData] == '' || localStorage[resumeData] == 'undefined') {
                        console.log("No cookie for position found");
                        var currentPosition = 0;
                    } else {
                        if (localStorage[resumeData] == "null") {
                            localStorage[resumeData] = 0;
                        } else {
                            var currentPosition = localStorage[resumeData];
                        }
                        console.log("Position cookie found: " + localStorage[resumeData]);
                    }
                    player.once('play', function() {
                        console.log(Math.abs(player.getDuration() - currentPosition));
                        if (currentPosition > 180 && Math.abs(player.getDuration() - currentPosition) > 5) {
                            console.log('player.seek: ', currentPosition);
                            player.seek(currentPosition);
                        }
                    });
                    window.onunload = function() {
                        localStorage[resumeData] = player.getPosition();
                    }
                } else {
                    console.log('Your browser is too old!');
                }
            });

            player.on('complete', function() {
                if (typeof(Storage) !== 'undefined') {
                    localStorage.removeItem(resumeData);
                } else {
                    console.log('Your browser is too old!');
                }
            });
            player.on('error', function(err) {
                console.log('player on error', err);
            });
        }
    </script>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const video = '{{$video->id}}';
            let playing = document.querySelector(`[data-id="${video}"]`);
            if (playing) {
                playing.click();
                return;
            }

            const servers = document.getElementsByClassName('streaming-server');
            console.log({servers});
            if (servers[0]) {
                servers[0].click();
            }

            setTimeout(() => {
                fetch("{{ route('movie.view-counter', ['movie' => $currentMovie->slug]) }}", {
                    method: 'POST',
                    headers: {
                        "Content-Type": "application/json",
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({
                        movie_id: '{{$currentMovie->id}}',
                        video_id: '{{$video->id}}',
                    }),
                });
            }, 5000);
        });
    </script>

    @if ($currentMovie->trailer_url && strpos($currentMovie->trailer_url, 'youtube'))
        @php
            parse_str( parse_url( $currentMovie->trailer_url, PHP_URL_QUERY ), $my_array_of_vars );
            $video_id = $my_array_of_vars['v'];
        @endphp
        <script>
            toroflixPublic.trailer = "<iframe width=\"560\" height=\"315\" src=\"https:\/\/www.youtube.com\/embed\/{{$video_id}}\" frameborder=\"0\" allow=\"autoplay\" allow=\"encrypted-media\" allowfullscreen><\/iframe>"
        </script>
        <div class="Modal-Box Ttrailer">
            <div class="Modal-Content">
                <span class="Modal-Close Button AAIco-clear"></span>
            </div>
            <i class="AAOverlay"></i>
        </div>
    @endif

    <script src="/themes/toro/plugins/jquery-raty/jquery.raty.js"></script>
    <link href="/themes/toro/plugins/jquery-raty/jquery.raty.css" rel="stylesheet" type="text/css" />

    <script>
        var rated = false;
        $('#movies-rating-star').raty({
            score: '{{ $currentMovie->getRatingStar() }}',
            number: 10,
            numberMax: 10,
            hints: ['quá tệ', 'tệ', 'không hay', 'không hay lắm', 'bình thường', 'xem được', 'có vẻ hay', 'hay',
                'rất hay', 'siêu phẩm'
            ],
            starOff: '/themes/toro/plugins/jquery-raty/images/star-off.png',
            starOn: '/themes/toro/plugins/jquery-raty/images/star-on.png',
            starHalf: '/themes/toro/plugins/jquery-raty/images/star-half.png',
            click: function(score, evt) {
                if (rated) return
                fetch("{{ route('movie.rating', ['movie' => $currentMovie->slug]) }}", {
                    method: 'POST',
                    headers: {
                        "Content-Type": "application/json",
                        'X-CSRF-TOKEN': document.querySelector(
                            'meta[name="csrf-token"]')
                            .getAttribute(
                                'content')
                    },
                    body: JSON.stringify({
                        rating: score
                    })
                });
                rated = true;
                $('#movies-rating-star').data('raty').readOnly(true);
                $('#movies-rating-msg').html(`Bạn đã đánh giá ${score} sao cho phim này!`);
            }
        });
    </script>

    {!! setting('site_scripts_facebook_sdk') !!}

@endpush
