<?php

namespace Ophim\ThemeToro;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ThemeToroServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->setupDefaultThemeCustomizer();
    }

    public function boot()
    {
        view()->composer('themes::themetoro.inc.header', function ($view) {
            $menu = \App\Models\Menu::getTree();
            $view->with('menu', $menu);
        });
        view()->composer('themes::themetoro.inc.rightbar', function ($view) {
            $tops = Cache::remember('site.movies.tops', setting('site_cache_ttl', 5 * 60), function () {
                $lists = get_theme_option('hotest');
                $data = [];
                foreach ($lists as $list) {
                    try {
                        $movies = query_movies($list);
                        $data[] = [
                            'label' => $list['label'],
                            'template' => $list['show_template'],
                            'data' => $movies,
                        ];
                    } catch (\Exception $e) {
                        Log::error(__CLASS__.'::'.__FUNCTION__.':'.__LINE__.': '. $e->getMessage());
                    }
                }
                return $data;
            });
            
            $view->with('tops', $tops);
        });

        $this->bootSeoDefaults();

        $this->loadViewsFrom(__DIR__ . '/../resources/views/', 'themes');

        $this->publishes([
            __DIR__ . '/../resources/assets' => public_path('themes/toro')
        ], 'toro-assets');
    }

    protected function setupDefaultThemeCustomizer()
    {
        config(['themes' => array_merge(config('themes', []), [
            'toro' => [
                'name' => 'Theme Toro',
                'author' => 'support@megavn.net',
                'package_name' => 'megavnet/ophim-theme-toro',
                'publishes' => ['toro-assets'],
                'preview_image' => '',
                'options' => [
                    [
                        'name' => 'per_page_limit',
                        'label' => 'Pages limit',
                        'type' => 'number',
                        'value' => 40,
                        'wrapperAttributes' => [
                            'class' => 'form-group col-md-6',
                        ],
                        'tab' => 'List'
                    ],
                    [
                        'name' => 'movie_related_limit',
                        'label' => 'Movies related limit',
                        'type' => 'number',
                        'value' => 16,
                        'wrapperAttributes' => [
                            'class' => 'form-group col-md-6',
                        ],
                        'tab' => 'List'
                    ],
                    [
                        'name' => 'home_page_slider_poster',
                        'label' => 'Home page slider poster',
                        'type' => 'table',
                        'tab' => 'List',
                        'columns'         => [
                            'label'  => 'Label',
                            'relation'  => 'Relation',
                            'find_by_field' => 'Find by field',
                            'value' => 'Value',
                            'sort_by_field' => 'Sort by field',
                            'sort_algo' => 'Sort direction',
                            'limit' => 'Limit',
                        ],
                        'default'    => [
                            [
                                'label' => 'Phim đề cử',
                                'relation' => '',
                                'find_by_field' => 'is_recommended',
                                'value' => '1',
                                'sort_by_field' => 'updated_at',
                                'sort_algo' => 'desc',
                                'limit' => '10',
                            ],
                        ],
                        'min' => 1,
                        'max' => 1,
                    ],
                    [
                        'name' => 'home_page_slider_thumb',
                        'label' => 'Home page slider thumb',
                        'type' => 'table',
                        'tab' => 'List',
                        'columns'         => [
                            'label'  => 'Label',
                            'relation'  => 'Relation',
                            'find_by_field' => 'Find by field',
                            'value' => 'Value',
                            'sort_by_field' => 'Sort by field',
                            'sort_algo' => 'Sort direction',
                            'limit' => 'Limit',
                        ],
                        'default'    => [
                            [
                                'label' => 'Phim mới cập nhật',
                                'relation' => '',
                                'find_by_field' => 'is_copyright',
                                'value' => '0',
                                'sort_by_field' => 'updated_at',
                                'sort_algo' => 'desc',
                                'limit' => '24',
                            ],
                        ],
                        'min' => 1,
                        'max' => 1,
                    ],
                    [
                        'name' => 'latest',
                        'label' => 'Home Page Main',
                        'type' => 'table',
                        'tab' => 'List',
                        'columns'         => [
                            'label'  => 'Label',
                            'relation'  => 'Relation',
                            'find_by_field' => 'Find by field',
                            'value' => 'Value',
                            'sort_by_field' => 'Sort by field',
                            'sort_algo' => 'Sort direction',
                            'limit' => 'Limit',
                            'show_more_url' => 'Show more url',
                            'show_template' => 'Show template', // section_thumb|section_poster
                        ],
                        'default'    => [
                            [
                                'label' => 'Phim chiếu rạp mới',
                                'relation' => '',
                                'find_by_field' => 'is_shown_in_theater',
                                'value' => '1',
                                'sort_by_field' => 'created_at',
                                'sort_algo' => 'desc',
                                'limit' => '6',
                                'show_more_url' => '/danh-sach/phim-chieu-rap',
                                'show_template' => 'section_thumb',
                            ],
                            [
                                'label' => 'Phim bộ mới',
                                'relation' => '',
                                'find_by_field' => 'type',
                                'value' => 'series',
                                'sort_by_field' => 'updated_at',
                                'sort_algo' => 'desc',
                                'limit' => '16',
                                'show_more_url' => '/danh-sach/phim-bo',
                                'show_template' => 'section_thumb',
                            ],
                            [
                                'label' => 'Phim lẻ mới',
                                'relation' => '',
                                'find_by_field' => 'type',
                                'value' => 'single',
                                'sort_by_field' => 'updated_at',
                                'sort_algo' => 'desc',
                                'limit' => '16',
                                'show_more_url' => '/danh-sach/phim-le',
                                'show_template' => 'section_thumb',
                            ],
                            [
                                'label' => 'Phim hoạt hình mới',
                                'relation' => 'categories',
                                'find_by_field' => 'slug',
                                'value' => 'hoat-hinh',
                                'sort_by_field' => 'updated_at',
                                'sort_algo' => 'desc',
                                'limit' => '12',
                                'show_more_url' => '/the-loai/hoat-hinh',
                                'show_template' => 'section_thumb',
                            ],
                        ],
                        'min' => 1,
                        'max' => 10,
                    ],
                    [
                        'name' => 'hotest',
                        'label' => 'Rightbar',
                        'type' => 'table',
                        'tab' => 'List',
                        'columns'         => [
                            'label'  => 'Label',
                            'relation'  => 'Relation',
                            'find_by_field' => 'Find by field',
                            'value' => 'Value',
                            'sort_by_field' => 'Sort by field',
                            'sort_algo' => 'Sort direction',
                            'limit' => 'Limit',
                            'show_template' => 'Show template', // rightbar_text|rightbar_thumb|rightbar_thumb_2
                        ],
                        'default'    => [
                            [
                                'label' => 'Sắp chiếu',
                                'relation' => '',
                                'find_by_field' => 'status',
                                'value' => 'trailer',
                                'sort_by_field' => 'publish_year',
                                'sort_algo' => 'desc',
                                'limit' => '10',
                                'show_template' => 'rightbar_text',
                            ],
                            [
                                'label' => 'Top phim lẻ',
                                'relation' => '',
                                'find_by_field' => 'type',
                                'value' => 'single',
                                'sort_by_field' => 'views_week',
                                'sort_algo' => 'desc',
                                'limit' => '5',
                                'show_template' => 'rightbar_thumb',
                            ],
                            [
                                'label' => 'Top phim bộ',
                                'relation' => '',
                                'find_by_field' => 'type',
                                'value' => 'series',
                                'sort_by_field' => 'views_week',
                                'sort_algo' => 'desc',
                                'limit' => '6',
                                'show_template' => 'rightbar_thumb_2',
                            ],
                        ],
                        'min' => 1,
                        'max' => 10,
                    ],
                    [
                        'name' => 'additional_css',
                        'label' => 'Additional CSS',
                        'type' => 'textarea',
                        'value' => "",
                        'tab' => 'Custom CSS'
                    ],
                    [
                        'name' => 'body_attributes',
                        'label' => 'Body attributes',
                        'type' => 'text',
                        'value' => 'id="Tf-Wp" class="home blog BdGradient"',
                        'tab' => 'Custom CSS'
                    ],
                    [
                        'name' => 'additional_header_js',
                        'label' => 'Header JS',
                        'type' => 'textarea',
                        'value' => "",
                        'tab' => 'Custom JS'
                    ],
                    [
                        'name' => 'additional_body_js',
                        'label' => 'Body JS',
                        'type' => 'textarea',
                        'value' => "",
                        'tab' => 'Custom JS'
                    ],
                    [
                        'name' => 'additional_footer_js',
                        'label' => 'Footer JS',
                        'type' => 'textarea',
                        'value' => "",
                        'tab' => 'Custom JS'
                    ],
                    [
                        'name' => 'footer',
                        'label' => 'Footer',
                        'type' => 'textarea',
                        'value' => <<<EOT
                            <footer class="Footer">
                                <div class="Bot">
                                    <div class="Container">
                                        <p>Toroflix is the evolution of toroplay, we put the best of us in this theme that you love, we
                                            promise</p>
                                    </div>
                                </div>
                            </footer>
                        EOT,
                        'tab' => 'Custom HTML'
                    ],
                    [
                        'name' => 'ads_header',
                        'label' => 'Ads header',
                        'type' => 'textarea',
                        'value' => <<<EOT

                        EOT,
                        'tab' => 'Ads'
                    ],
                    [
                        'name' => 'ads_catfish',
                        'label' => 'Ads catfish',
                        'type' => 'textarea',
                        'value' => <<<EOT

                        EOT,
                        'tab' => 'Ads'
                    ]
                ],
            ]
        ])]);
    }

    protected function bootSeoDefaults()
    {
        config([
            'seotools.meta.defaults.title' => setting('site_homepage_title'),
            'seotools.meta.defaults.description' => setting('site_meta_description'),
            'seotools.meta.defaults.keywords' => [setting('site_meta_keywords')],
            'seotools.meta.defaults.canonical' => url("/")
        ]);

        config([
            'seotools.opengraph.defaults.title' => setting('site_homepage_title'),
            'seotools.opengraph.defaults.description' => setting('site_meta_description'),
            'seotools.opengraph.defaults.type' => 'website',
            'seotools.opengraph.defaults.url' => url("/"),
            'seotools.opengraph.defaults.site_name' => setting('site_meta_siteName'),
            'seotools.opengraph.defaults.images' => [setting('site_meta_image')],
        ]);

        config([
            'seotools.twitter.defaults.card' => 'website',
            'seotools.twitter.defaults.title' => setting('site_homepage_title'),
            'seotools.twitter.defaults.description' => setting('site_meta_description'),
            'seotools.twitter.defaults.url' => url("/"),
            'seotools.twitter.defaults.site' => setting('site_meta_siteName'),
            'seotools.twitter.defaults.image' => setting('site_meta_image'),
        ]);

        config([
            'seotools.json-ld.defaults.title' => setting('site_homepage_title'),
            'seotools.json-ld.defaults.type' => 'WebPage',
            'seotools.json-ld.defaults.description' => setting('site_meta_description'),
            'seotools.json-ld.defaults.images' => setting('site_meta_image'),
        ]);
    }
}
