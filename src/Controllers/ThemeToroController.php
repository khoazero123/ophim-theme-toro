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
            $data = Movie::when(!empty($request['filter']['category']), function ($movie) {
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

            if ($data->count()) {
                $tag = Tag::firstOrCreate(['name' => $keyword]);
                $tag->where('id', $tag->id)->incrementEach(['views' => 1, 'views_day' => 1, 'views_week' => 1, 'views_month' => 1]);
            }

            $section_name = "Tìm kiếm phim: $keyword";
            if (($page = request()->query('page')) > 1) {
                $section_name .= " - trang " . $page;
            }

            return view('themes::themetoro.catalog', [
                'movies' => $data,
                'search' => $keyword,
                'section_name' => $section_name
            ]);
        }

        $title = Setting::get('site_homepage_title');

        $home_page_slider_poster = Cache::remember('site.movies.home_page_slider_poster', setting('site_cache_ttl', 5 * 60), function () {
            $list = get_theme_option('home_page_slider_poster') ?: [];
            if(empty($list)) return null;
            $data = null;
            $list = $list[0];
            try {
                if (!isset($list['label']) || empty($list['label']))
                    return null;
                $movies = query_movies($list);
                $data = [
                    'label' => $list['label'],
                    'data' => $movies,
                ];
            } catch (\Exception $e) {
                Log::error(__CLASS__.'::'.__FUNCTION__.':'.__LINE__.': '. $e->getMessage());
            }
            return $data;
        });

        $home_page_slider_thumb = Cache::remember('site.movies.home_page_slider_thumb', setting('site_cache_ttl', 5 * 60), function () {
            $list = get_theme_option('home_page_slider_thumb') ?: [];
            if(empty($list)) return null;
            $data = null;
            $list = $list[0];
            try {
                if (!isset($list['label']) || empty($list['label']))
                    return null;
                $movies = query_movies($list);
                $data = [
                    'label' => $list['label'],
                    'data' => $movies,
                ];
            } catch (\Exception $e) {
                Log::error(__CLASS__.'::'.__FUNCTION__.':'.__LINE__.': '. $e->getMessage());
            }
            return $data;
        });
        $cached_key = 'site.movies.latest_'.Str::slug(http_build_query($request->query()));
        $movies_latest = Cache::remember($cached_key, setting('site_cache_ttl', 5 * 60), function () {
            $lists = get_theme_option('latest');
            $data = [];
            foreach ($lists as $list) {
                try {
                    if (!isset($list['label']) || empty($list['label']))
                        continue;
                    $movies = query_movies($list);
                    $data[] = [
                        'label' => $list['label'],
                        'show_template' => $list['show_template'],
                        'data' => $movies,
                        'link' => $list['show_more_url'] ?: '#',
                    ];
                } catch (\Exception $e) {
                    Log::error(__CLASS__.'::'.__FUNCTION__.':'.__LINE__.': '. $e->getMessage());
                }
            }
            return $data;
        });

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
        $movie = Movie::fromCache()->findByKey('slug', $request->movie);
        if (is_null($movie)) abort(404);
        $movie->generateSeoTags();

        $movie->withoutTimestamps(function() use ($movie) {
            $movie->increment('views', 1);
            $movie->increment('views_day', 1);
            $movie->increment('views_week', 1);
            $movie->increment('views_month', 1);
        });

        // $movie->load('episodes');
        // $total_episodes = $movie->episodes->count();

        $movie_related_cache_key = 'movie_related:' . $movie->id;
        $movie_related = Cache::get($movie_related_cache_key, []);
        if(empty($movie_related) && $movie->categories->count() > 0) {
            $movie_related = $movie->categories[0]->movies()->inRandomOrder()->limit(get_theme_option('movie_related_limit', 10))->get();
            Cache::put($movie_related_cache_key, $movie_related, setting('site_cache_ttl', 5 * 60));
        }

        return view('themes::themetoro.single', [
            'currentMovie' => $movie,
            'title' => $movie->name,
            'movie_related' => $movie_related
        ]);
    }

    public function getEpisode(Request $request)
    {
        $episode_slug = $request->episode;
        $movie = Movie::fromCache()->findByKey('slug', $request->movie);
        if (is_null($movie)) abort(404);
        /** @var Episode */
        $episode_id = $request->id;
        $episode = $movie->episodes->when($episode_id, function ($collection, $episode_id) {
            return $collection->where('id', $episode_id);
        })->firstWhere('slug', $episode_slug);

        if (is_null($episode)) abort(404);

        // Not nessary yet
        // $server_episodes = $movie->episodes()->where('slug', $episode->slug)->get();
        $server_episodes = [$episode];

        $episode->generateSeoTags();

        $is_view_set = $request->cookie('views_episode_'.$episode_id);
        if (!$is_view_set) {
            $movie->withoutTimestamps(function() use ($movie) {
                $movie->increment('views', 1);
                $movie->increment('views_day', 1);
                $movie->increment('views_week', 1);
                $movie->increment('views_month', 1);
            });
            
            if ($video = $episode->video) {
                $video->withoutTimestamps(function() use ($video) {
                    $video->increment('views', 1);
                    $video->increment('views_day', 1);
                    $video->increment('views_week', 1);
                    $video->increment('views_month', 1);
                });
            }
            Cookie::queue(Cookie::make('views_episode_'.$episode_id, '1', 60));
        }
        $movie_related_cache_key = 'movie_related:' . $movie->id;
        $movie_related = Cache::get($movie_related_cache_key) ?: [];
        if(empty($movie_related) && count($movie->categories)) {
            $movie_related = $movie->categories[0]->movies()->inRandomOrder()->limit(get_theme_option('movie_related_limit', 10))->get();
            if ($movie_related->count()) {
                Cache::put($movie_related_cache_key, $movie_related, setting('site_cache_ttl', 5 * 60));
            }
        }

        return view('themes::themetoro.episode', [
            'currentMovie' => $movie,
            'movie_related' => $movie_related,
            'episode' => $episode,
            'server_episodes' => $server_episodes,
            'title' => $episode->name
        ]);
    }

    public function getMovieOfCategory(Request $request)
    {
        /** @var Category */
        $category = Category::fromCache()->findByKey('slug', $request->category);
        if (is_null($category)) abort(404);

        $category->generateSeoTags();

        $movies = $category->movies()->orderBy('created_at', 'desc')->paginate(get_theme_option('per_page_limit', 15));

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
        $region = Region::fromCache()->findByKey('slug', $request->region);
        if (is_null($region)) abort(404);

        $region->generateSeoTags();

        $movies = $region->movies()->orderBy('created_at', 'desc')->paginate(get_theme_option('per_page_limit'));

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
        $actor = Actor::fromCache()->findByKey('slug', $request->actor);
        if (is_null($actor)) abort(404);

        $actor->generateSeoTags();

        $movies = $actor->movies()->orderBy('created_at', 'desc')->paginate(get_theme_option('per_page_limit'));

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
        $director = Director::fromCache()->findByKey('slug', $request->director);
        if (is_null($director)) abort(404);

        $director->generateSeoTags();

        $movies = $director->movies()->orderBy('created_at', 'desc')->paginate(get_theme_option('per_page_limit'));

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
        $tag = Tag::fromCache()->findByKey('slug', $request->tag);

        if (is_null($tag)) abort(404);

        $tag->generateSeoTags();

        $movies = $tag->movies()->orderBy('created_at', 'desc')->paginate(get_theme_option('per_page_limit'));
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
        $catalog = Catalog::fromCache()->findByKey('slug', $request->type);
        $page = $request['page'] ?: 1;
        if (is_null($catalog)) abort(404);

        $catalog->generateSeoTags();

        $catalog_options = $catalog->getOptions();
        @list('list_limit' => $list_limit, 'list_sort_by' => $list_sort_by, 'list_sort_order' => $list_sort_order) = $catalog_options;

        $cache_key = 'catalog:' . $catalog->id . ':page:' . $page;
        $movies = Cache::get($cache_key);
        if(is_null($movies) || !(int)setting('site_cache_enable', 1)) {
            $list_limit = $list_limit ?: get_theme_option('per_page_limit', 15);
            $list_sort_by = $list_sort_by ?: 'id';
            $list_sort_order = $list_sort_order ?: 'DESC';
            $movies = $catalog->movies()->orderBy($list_sort_by, $list_sort_order)->paginate($list_limit);
            if ($movies->total()) {
                Cache::put($cache_key, $movies, setting('site_cache_ttl', 5 * 60));
            } else {
                Cache::put($cache_key, $movies, 10);
            }
        }

        return view('themes::themetoro.catalog', [
            'movies' => $movies,
            'section_name' => "Danh sách $catalog->name"
        ]);
    }

    public function reportEpisode(Request $request, $movie, $slug)
    {
        $movie = Movie::fromCache()->findByKey('slug', $movie)->load('episodes');

        $episode = $movie->episodes->when(request('id'), function ($collection) {
            return $collection->where('id', request('id'));
        })->firstWhere('slug', $slug);

        $episode->update([
            'report_message' => request('message', ''),
            'has_report' => true
        ]);

        return response([], 204);
    }

    public function rateMovie(Request $request, $movie)
    {
        $movie = Movie::fromCache()->find($movie);

        if (!$movie) {
            return response([], 404);
        }

        $movie->refresh()->increment('rating_count', 1, [
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
