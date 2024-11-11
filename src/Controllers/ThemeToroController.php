<?php

namespace Ophim\ThemeToro\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Backpack\Settings\app\Models\Setting;
use App\Traits\HasSeoTags;
use App\Models\Actor;
use App\Models\Catalog;
use App\Models\Category;
use App\Models\Director;
use App\Models\Episode;
use App\Models\Movie;
use App\Models\Region;
use App\Models\Tag;
use App\Models\Video;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cookie;

class ThemeToroController
{
    use HasSeoTags;

    public function index(Request $request)
    {
        $site_routes_tag_search = setting('site_routes_tag_search', '/?search={tag}');
        $query_str = parse_url($site_routes_tag_search, PHP_URL_QUERY);
        parse_str($query_str, $output);
        $query_key = !empty($output) ? array_keys($output)[0] : 'search';
        $keyword = $request->query($query_key);
        if ($keyword) {
            $keyword = str_replace(['-', '_'], ' ', $keyword);
        }
        if ($keyword || $request['filter']) {
            $query = Movie::query();
            if (setting('movie_video_mode')) {
                $query->isPublished();
            }
            $data = $query->when(!empty($request['filter']['category']), function ($movie) {
                $movie->whereHas('categories', function ($categories) {
                    $categories->where('id', request('filter')['category']);
                });
            })->when(!empty($request['filter']['region']), function ($movie) {
                $movie->whereHas('regions', function ($regions) {
                    $regions->where('id', request('filter')['region']);
                });
            })->when(!empty($request['filter']['year']), function ($movie) {
                $movie->where('publish_year', request('filter')['year']);
            })->when(!empty($request['filter']['type']), function ($movie) {
                $movie->where('type', request('filter')['type']);
            })->when(!empty($keyword), function ($query) use ($keyword) {
                $query->where(function ($query) use ($keyword) {
                    $query->where('name', 'like', '%' . $keyword . '%')
                        ->orWhere('origin_name', 'like', '%' . $keyword  . '%')
                        ->orWhere('ascii_name', 'like', '%' . $keyword  . '%')
                        ->orWhere('content', 'like', '%' . $keyword  . '%');
                })->orderBy('name', 'desc');
            })->when(!empty($request['filter']['sort']), function ($movie) {
                if (request('filter')['sort'] == 'create') {
                    return $movie->orderBy('created_at', 'desc');
                }
                if (request('filter')['sort'] == 'update') {
                    return $movie->orderBy('updated_at', 'desc');
                }
                if (request('filter')['sort'] == 'year') {
                    return $movie->orderBy('publish_year', 'desc');
                }
                if (request('filter')['sort'] == 'view') {
                    return $movie->orderBy('views', 'desc');
                }
            })->paginate(get_theme_option('per_page_limit'));

            $this->generateSeoTags('search');

            $section_name = "Danh sách phim";
            if ($keyword) {
                if ($data->count()) {
                    $tag = Tag::firstOrCreate(['name' => $keyword]);
                    Tag::withoutTimestamps(function() use ($tag) {
                        $tag->where('id', $tag->id)->incrementEach(['views' => 1, 'views_day' => 1, 'views_week' => 1, 'views_month' => 1]);
                    });
                }
                $section_name = "Tìm kiếm phim: $keyword";
                if (($page = request()->query('page')) > 1) {
                    $section_name .= " - trang " . $page;
                }
            }

            return view('themes::themetoro.catalog', [
                'movies' => $data,
                'search' => $keyword,
                'section_name' => $section_name
            ]);
        }

        $title = Setting::get('site_homepage_title');

        $home_page_slider_poster = $home_page_slider_thumb = $movies_latest = [];

        if (($list = get_theme_option('home_page_slider_poster')) && !empty($list) && isset($list[0]['label']) && ($list[0]['label'])) {
            try {
                $movies = query_movies($list[0]);
                $home_page_slider_poster = [
                    'label' => $list[0]['label'],
                    'data' => $movies,
                ];
            } catch (\Exception $e) {
                Log::error(__CLASS__.'::'.__FUNCTION__.':'.__LINE__.': '. $e->getMessage());
            }
        }

        if (($list = get_theme_option('home_page_slider_thumb')) && !empty($list) && isset($list[0]['label']) && ($list[0]['label'])) {
            try {
                $movies = query_movies($list[0]);
                $home_page_slider_thumb = [
                    'label' => $list[0]['label'],
                    'data' => $movies,
                ];
            } catch (\Exception $e) {
                Log::error(__CLASS__.'::'.__FUNCTION__.':'.__LINE__.': '. $e->getMessage());
            }
        }

        if (($lists = get_theme_option('latest')) && !empty($lists)) {
            $movies_latest = [];
            foreach ($lists as $list) {
                try {
                    if (!isset($list['label']) || empty($list['label']))
                        continue;
                    $movies = query_movies($list);
                    $movies_latest[] = [
                        'label' => $list['label'],
                        'show_template' => $list['show_template'],
                        'data' => $movies,
                        'link' => $list['show_more_url'] ?: '#',
                    ];
                } catch (\Exception $e) {
                    Log::error(__CLASS__.'::'.__FUNCTION__.':'.__LINE__.': '. $e->getMessage());
                }
            }
        }

        $page = $this->getPageQueryOnHomePage($request);
        if ($page) {
            // Append page to title
            // $title = config('seotools.meta.defaults.title');
            $title .= " - trang $page";
            config([
                'seotools.meta.defaults.title' => $title,
            ]);
        }

        return view('themes::themetoro.index', compact('title', 'movies_latest', 'home_page_slider_poster', 'home_page_slider_thumb'));
    }

    public function getMovieOverview(Request $request)
    {
        /** @var Movie */
        $movie = Movie::where('slug', $request->movie)->first();
        if (is_null($movie)) abort(404);
        $movie->generateSeoTags();

        $movie_related = $this->getMovieRelated($movie);

        return view('themes::themetoro.single', [
            'currentMovie' => $movie,
            'title' => $movie->name,
            'movie_related' => $movie_related
        ]);
    }

    public function getEpisode(Request $request)
    {
        $movie = Movie::where('slug', $request->movie)->first();
        if (is_null($movie)) abort(404);

        if (setting('movie_video_mode') && $movie->video && env('REDIRECT_TO_VIDEO_URL', false)) {
            $movie_url = $movie->getUrl();
            return redirect($movie_url, 301);
        }
        /** @var Episode */
        $episode_id = $request->id;
        $episode = Episode::find($episode_id);
        if (is_null($episode)) abort(404);

        $video = $episode->video;
        if (is_null($video)) abort(404);

        // Not nessary yet
        // $server_episodes = $movie->episodes()->where('slug', $episode->slug)->get();
        $server_episodes = [$episode];

        if (setting('movie_video_mode')) {
            $movie->generateSeoTags();
        } else {
            $episode->generateSeoTags();
        }

        $movie_related = $this->getMovieRelated($movie);

        return view('themes::themetoro.episode', [
            'currentMovie' => $movie,
            'movie_related' => $movie_related,
            'episode' => $episode,
            'server_episodes' => $server_episodes,
            'title' => $episode->name
        ]);
    }

    public function watchVideo(Request $request)
    {
        $movie = Movie::where('slug', $request->movie)->first();
        $video_id = (int)$request->id ?: ($movie ? $movie->video_id : null);
        $video = $video_id ? Video::find($video_id) : null;
        if (is_null($movie) || is_null($video)) abort(404);

        $movie->generateSeoTags();

        $movie_related = $this->getMovieRelated($movie);

        return view('themes::themetoro.video', [
            'currentMovie' => $movie,
            'movie_related' => $movie_related,
            'video' => $video,
            'title' => $movie->name
        ]);
    }

    public function getMovieRelated($movie) {
        $cache_movie_related = env('CACHE_MOVIE_RELATED', false);
        $movie_related_cache_key = 'movie_related_' . $movie->id;
        $movie_related = $cache_movie_related ? (Cache::get($movie_related_cache_key)?:[]) : [];
        if(!$movie_related && ($first_category = $movie->categories->first())) {
            $movie_related = $first_category->movies()->isPublished()->inRandomOrder()->limit(get_theme_option('movie_related_limit', 10))->get();
            if ($cache_movie_related) {
                Cache::put($movie_related_cache_key, $movie_related, setting('site_cache_ttl', 5 * 60));
            }
        }
        return $movie_related ?: [];
    }

    public function getMovieOfCategory(Request $request)
    {
        /** @var Category */
        $category = Category::where('slug', $request->category)->first();
        if (is_null($category)) abort(404);

        $category->generateSeoTags();

        $query = $category->movies()->isPublished();

        $movies = $query->orderBy('created_at', 'desc')->paginate(get_theme_option('per_page_limit', 15));

        return view('themes::themetoro.catalog', [
            'movies' => $movies,
            'category' => $category,
            'title' => $category->seo_title ?: $category->name,
            'section_name' => "Phim thể loại $category->name"
        ]);
    }

    public function getMovieOfRegion(Request $request)
    {
        /** @var Region */
        $region = Region::where('slug', $request->region)->first();
        if (is_null($region)) abort(404);

        $region->generateSeoTags();

        $query = $region->movies()->isPublished();

        $movies = $query->orderBy('created_at', 'desc')->paginate(get_theme_option('per_page_limit'));

        return view('themes::themetoro.catalog', [
            'movies' => $movies,
            'region' => $region,
            'title' => $region->seo_title ?: $region->name,
            'section_name' => "Phim quốc gia $region->name"
        ]);
    }

    public function getMovieOfActor(Request $request)
    {
        /** @var Actor */
        $actor = Actor::where('slug', $request->actor)->first();
        if (is_null($actor)) abort(404);

        $actor->generateSeoTags();

        $query = $actor->movies()->isPublished();

        $movies = $query->orderBy('created_at', 'desc')->paginate(get_theme_option('per_page_limit'));

        return view('themes::themetoro.catalog', [
            'movies' => $movies,
            'person' => $actor,
            'title' => $actor->name,
            'section_name' => "Diễn viên $actor->name"
        ]);
    }

    public function getMovieOfDirector(Request $request)
    {
        /** @var Director */
        $director = Director::where('slug', $request->director)->first();
        if (is_null($director)) abort(404);

        $director->generateSeoTags();

        $query = $director->movies()->isPublished();

        $movies = $query->orderBy('created_at', 'desc')->paginate(get_theme_option('per_page_limit'));

        return view('themes::themetoro.catalog', [
            'movies' => $movies,
            'person' => $director,
            'title' => $director->name,
            'section_name' => "Đạo diễn $director->name"
        ]);
    }

    public function getMovieOfTag(Request $request)
    {
        /** @var Tag */
        $tag = Tag::where('slug', $request->tag)->first();

        if (is_null($tag)) abort(404);

        $tag->generateSeoTags();

        $query = $tag->movies()->isPublished();

        $movies = $query->orderBy('created_at', 'desc')->paginate(get_theme_option('per_page_limit'));
        return view('themes::themetoro.catalog', [
            'movies' => $movies,
            'tag' => $tag,
            'title' => $tag->name,
            'section_name' => "Tags: $tag->name"
        ]);
    }

    public function getMovieOfType(Request $request)
    {
        /** @var Catalog */
        $catalog = Catalog::where('slug', $request->type)->first();
        $page = $request['page'] ?: 1;
        if (is_null($catalog)) abort(404);

        $catalog->generateSeoTags();

        $catalog_options = $catalog->getOptions();
        @list('list_limit' => $list_limit, 'list_sort_by' => $list_sort_by, 'list_sort_order' => $list_sort_order) = $catalog_options;

        $list_limit = $list_limit ?: get_theme_option('per_page_limit', 15);
        $list_sort_by = $list_sort_by ?: 'id';
        $list_sort_order = $list_sort_order ?: 'DESC';
        $query = $catalog->movies()->isPublished();

        $movies = $query->orderBy($list_sort_by, $list_sort_order)->paginate($list_limit);

        return view('themes::themetoro.catalog', [
            'movies' => $movies,
            'section_name' => "Danh sách $catalog->name"
        ]);
    }

    public function reportEpisode(Request $request, $movie, $slug)
    {
        $movie = Movie::where('slug', $movie)->first()->load('episodes');

        $episode = $movie->episodes->when(request('id'), function ($collection) {
            return $collection->where('id', request('id'));
        })->firstWhere('slug', $slug);

        $episode->update([
            'report_message' => request('message', ''),
            'has_report' => true
        ]);

        return response([], 204);
    }

    public function viewCounter(Request $request, $movie_slug)
    {
        $movie_id = (int) $request->input('movie_id');
        $video_id = (int) $request->input('video_id');
        $episode_id = (int) $request->input('episode_id');

        // $movie = Movie::where('slug', $movie_slug)->first();
        // if (!$movie) {
        //     return response([], 404);
        // }
        // $movie_id = $movie->id;

        $cookie_content = json_decode($request->cookie('views')?:'[]', true);
        $logged_data = $cookie_content && isset($cookie_content[$movie_id]) ? $cookie_content[$movie_id] : false;

        $view_date = $logged_data ? Carbon::createFromFormat('Y-m-d H:i:s', $logged_data['date'], config('app.timezone')) : now(config('app.timezone'));
        // $diffInMinutes_0 = now(config('app.timezone'))->endOfDay()->diffInMinutes($view_date);
        $diffInMinutes = $view_date->diffInMinutes(now(config('app.timezone'))->endOfDay());

        if (!$logged_data || $diffInMinutes > 1440) {
            Movie::withoutTimestamps(function() use ($movie_id) {
                Movie::where('id', $movie_id)->incrementEach(['views' => 1, 'views_day' => 1, 'views_week' => 1, 'views_month' => 1]);
            });

            if ($video_id /* && ($video = Video::find($video_id)) */) {
                Video::withoutTimestamps(function() use ($video_id) {
                    Video::where('id', $video_id)->incrementEach(['views' => 1, 'views_day' => 1, 'views_week' => 1, 'views_month' => 1]);
                });
            }
            $cookie_content[$movie_id] = [
                'date' => now(config('app.timezone'))->format('Y-m-d H:i:s'),
            ];
            Cookie::queue(Cookie::make('views', json_encode($cookie_content), 1440));
        }

        return response([], 204);
    }

    public function rateMovie(Request $request, $movie)
    {
        $movie = Movie::find($movie);

        if (!$movie) {
            return response([], 404);
        }

        $movie->refresh()->incrementQuietly('rating_count', 1, [
            'rating_star' => $movie->rating_star +  ((int) request('rating') - $movie->rating_star) / ($movie->rating_count + 1)
        ]);

        return response([], 204);
    }

    protected function getPageQueryOnHomePage(Request $request) {
        $lists = get_theme_option('latest');
        foreach ($lists as $list) {
            $key = Str::slug($list['label']);
            if($page = (int)$request->query($key)) {
                return $page;
            }
        }

        return 0;
    }
}
