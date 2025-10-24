<?php
/*
Plugin Name: Tools for blog2k
Plugin URI: http://blog2k.ru/plugins/b2k-tools
Author: Evgenii Zhirnov <jirnov@gmail.com>
Author URI: http://blog2k.ru/
Description: Утилиты для blog2k.ru
 */

//Path to this file
if ( !defined('B2K_PLUGIN_FILE') ){
  define('B2K_PLUGIN_FILE', __FILE__);
}

//Path to the plugin's directory
if ( !defined('B2K_DIRECTORY') ){
  define('B2K_DIRECTORY', dirname(__FILE__));
}

use Automattic\Jetpack\Stats\WPCOM_Stats;

class B2K_Tools {

    public function __construct() {
        $this->init_hooks();
    }

    private function init_hooks() {
        // Безопасность и оптимизация
        add_filter('xmlrpc_enabled', '__return_false');
        remove_action('wp_head', 'rsd_link');
        remove_action('wp_head', 'wp_shortlink_wp_head', 10, 0);
        add_filter('wp_headers', array($this, 'remove_pingback'));
        add_filter('wp_default_scripts', array($this, 'remove_jquery_migrate'));
        add_filter('jetpack_implode_frontend_css', '__return_false');

        // Стили и скрипты
        add_action('wp_enqueue_scripts', array($this, 'enqueue_styles'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));

        // Шорткоды
        add_shortcode('spoiler', array($this, 'spoiler_shortcode'));

        // Фильтры контента
        add_filter('the_content', array($this, 'social_likes'));
        add_filter('the_content', array($this, 'target_blank'));
        add_filter('the_content', array($this, 'nofollow_links'));
        add_filter('document_title', array($this, 'document_title'));

        // HTML head
        add_action('wp_head', array($this, 'rel_next_prev'));
        add_action('wp_head', array($this, 'meta_verify'));
        add_action('wp_head', array($this, 'canonical_link'));
        add_action('wp_head', array($this, 'og_meta'));

        // Редиректы
        add_action('template_redirect', array($this, 'redirect_single_post'));
        add_action('template_redirect', array($this, 'disable_author_page'));
        add_action('template_redirect', array($this, 'redirect_attachment_page'));

        // Оптимизация стилей
        add_action('wp_print_styles', array($this, 'deregister_yarpp_header_styles'));
        add_action('wp_enqueue_scripts', array($this, 'remove_block_library_css'));
        add_action('wp_footer', array($this, 'deregister_yarpp_footer_styles'));
        add_filter('cancel_comment_reply_link', array($this, 'remove_nofollow'), 420, 4);

        // Дополнительные фильтры
        add_filter('paginate_links', array($this, 'remove_args'));

        // Отключение глобальных стилей
        remove_action('wp_enqueue_scripts', 'wp_enqueu_global_styles');
        remove_action('wp_body_open','wp_global_styles_render_svg_filters');
    }

    public function remove_pingback($headers) {
        unset($headers['X-Pingback']);
        return $headers;
    }

    public function remove_jquery_migrate($scripts) {
        if (empty($scripts->registered['jquery']) || is_admin()) {
            return;
        }

        $deps = & $scripts->registered['jquery']->deps;
        $deps = array_diff($deps, ['jquery-migrate']);
    }

    public function spoiler_shortcode($atts, $content) {
        $sp_name = isset($atts['name']) ? sanitize_text_field($atts['name']) : 'Спойлер';
        return '<div class="spoiler-wrap"><div class="spoiler-head folded">'.esc_html($sp_name).'</div><div class="spoiler-body">'.do_shortcode($content).'</div></div>';
    }

    public function enqueue_styles() {
        if (!is_admin()) {
            wp_deregister_style('dashicons');
        }

        wp_register_style(
            'social-likes',
            plugins_url('css/social-likes_flat.css', B2K_PLUGIN_FILE)
        );

        wp_register_style(
            'spoiler',
            plugins_url('css/spoiler.css', B2K_PLUGIN_FILE)
        );

        wp_register_style(
            'b2k_tools',
            plugins_url('css/b2k_tools.css', B2K_PLUGIN_FILE)
        );

        wp_register_style(
            'spoiler-printer',
            plugins_url('css/spoiler-printer.css', B2K_PLUGIN_FILE),
            array(),
            null,
            'print'
        );

        wp_enqueue_style('social-likes');
        wp_enqueue_style('spoiler');
        wp_enqueue_style('b2k_tools');
        wp_enqueue_style('spoiler-printer');
    }

    public function enqueue_scripts() {
        wp_register_script(
            'counters',
            plugins_url('js/counters.js', B2K_PLUGIN_FILE),
            array('jquery'),
            false,
            true
        );

        wp_register_script(
            'spoiler',
            plugins_url('js/spoiler.js', B2K_PLUGIN_FILE),
            array('jquery'),
            false,
            true
        );

        wp_register_script(
            'tools',
            plugins_url('js/tools.js', B2K_PLUGIN_FILE),
            array('jquery'),
            false,
            true
        );

        if (!is_user_logged_in() && !is_404()) {
            wp_enqueue_script('counters');
        }

        wp_register_script(
            'social-likes',
            plugins_url('js/social-likes.min.js', B2K_PLUGIN_FILE),
            array(),
            false,
            true
        );

        wp_enqueue_script('spoiler');
        wp_enqueue_script('tools');

        if (!is_404()) {
            wp_enqueue_script('social-likes');
        }
    }

    public function social_likes($content = '') {
        global $post;

        if (!$post) {
            return $content;
        }

        $buttons = array();
        $buttons['vk'] = '<div data-service="vkontakte" title="Поделиться ВКонтакте"></div>';
        $buttons['fb'] = '<div data-service="facebook" title="Поделиться в Facebook"></div>';
        $buttons['twitter'] = '<div data-service="twitter" data-via="evgenii_zhirnov" title="Поделиться в Twitter"></div>';
        $buttons['ok'] = '<div data-service="odnoklassniki" title="Поделиться в Одноклассниках"></div>';
        $buttons['telegram'] = '<div data-service="telegram" title="Поделиться в Telegram"></div>';
        $buttons['linkedin'] = '<div data-service="linkedin" title="Поделиться в LinkeIn"></div>';
        $buttons = implode('', $buttons);

        $tags = array();
        $tags['class'] = 'class="social-likes"';
        $tags['data-title'] = 'data-title="' . esc_attr($post->post_title) . '"';
        $tags['data-url'] = 'data-url="' . esc_url(get_permalink($post->ID)) . '"';
        $tags = implode(' ', $tags);

        $main = '<div ' . $tags . '>';
        $main .= $buttons;
        $main .= '</div>';

        $content .= $main;

        return $content;
    }

    public function rel_next_prev() {
        global $paged;
        $next_link = "";
        $prev_link = "";

        if (get_previous_posts_link()) {
            $prev_link = get_pagenum_link($paged - 1);
        } else if (is_singular()) {
            $prev_post = get_previous_post(true);
            if ($prev_post) {
                $prev_link = get_permalink($prev_post->ID);
            }
        }

        if (get_next_posts_link()) {
            $next_link = get_pagenum_link($paged + 1);
        } else if (is_singular()) {
            $next_post = get_next_post(true);
            if ($next_post) {
                $next_link = get_permalink($next_post->ID);
            }
        }

        if ($prev_link) {
            echo '<link rel="prev" href="'.esc_url($this->remove_args($prev_link)).'"/>';
        }

        if ($next_link) {
            echo '<link rel="next" href="'.esc_url($this->remove_args($next_link)).'"/>';
        }
    }

    public function redirect_single_post() {
        if (is_search() && !is_paged()) {
            global $wp_query;
            if ($wp_query->post_count == 1) {
                wp_redirect(get_permalink($wp_query->posts['0']->ID));
                die;
            }
        }
    }

    public function disable_author_page() {
        if (is_author() && !is_404()) {
            global $wp_query;
            $wp_query->set_404();
            status_header(404);
            nocache_headers();
        }
    }

    public function target_blank($content) {
        $content = preg_replace_callback(
            '/<a[^>]*href=^#["|\']([^"|\']*)["|\'][^>]*>([^<]*)<\/a>/i',
            function($m) {
                $url = esc_url($m[1]);
                $desc = esc_html($m[2]);
                return '<a href="'.$url.'" target="_blank">'.$desc.'</a>';
            },
            $content
        );

        return $content;
    }

    public function nofollow_links($content) {
        $content = preg_replace_callback(
            '/<a[^>]*href=["|\']([^"|\']*)["|\'][^>]*>([^<]*)<\/a>/i',
            function($m) {
                $url = esc_url($m[1]);
                $desc = esc_html($m[2]);
                $nofollow = true;

                $ownDomain = $_SERVER['HTTP_HOST'];
                $excludeDomains = array('yowindow.com', 'yowindow.ru');

                if (strpos($url, $ownDomain)) {
                    $nofollow = false;
                } else {
                    foreach ($excludeDomains as $domain) {
                        if (strpos($url, $domain)) {
                            $nofollow = false;
                            break;
                        }
                    }
                }

                if ($nofollow) {
                    return '<a href="'.$url.'" rel="nofollow" target="_blank">'.$desc.'</a>';
                } else {
                    return '<a href="'.$url.'" target="_blank">'.$desc.'</a>';
                }
            },
            $content
        );

        return $content;
    }

    public function meta_verify() {
        if (!is_front_page()) {
            return;
        }

        $tags = array();
        $tags['wmail-verification'] = '9ebfe98ff63e45ab70051236411602b3';
        $tags['yandex-verification'] = 'f85143c2d64ffa73';
        $tags['msvalidate.01'] = 'D2830D11907D8498A7DF6ED82E4A6A99';
        $tags['majestic-site-verification'] = 'MJ12_2ff0f9c5-dfa6-4456-822d-c787e3ef4b3e';
        $tags['google-site-verification'] = '5F-RqyTjWAVNggrtayKNqdeqDb1sYrP42Zr2uJGn1yc';
        $tags['p:domain_verify'] = '1773e39ecb1be4050e57676b9a758bdb';
        $tags['ruweb-verification'] = '699d6c86f00af764d434a4c8bc369c63b84fe822';

        $this->print_meta_tags($tags);
    }

    public function redirect_attachment_page() {
        if (is_attachment()) {
            global $post;
            if ($post && $post->post_parent) {
                wp_redirect(esc_url(get_permalink($post->post_parent)), 301);
                exit;
            } else {
                global $wp_query;
                $wp_query->set_404();
                status_header(404);
                get_template_part(404);
                exit;
            }
        }
    }

    public function remove_args($url) {
        $parsed = parse_url($url);

        $fragment = array_key_exists('fragment', $parsed) ? '#' . $parsed['fragment'] : '';

        $query = '';

        if (array_key_exists('query', $parsed)) {
            parse_str($parsed['query'], $params);

            $params = array_filter(
                $params,
                function ($e) { return in_array($e, array('s', 'page')); },
                ARRAY_FILTER_USE_KEY
            );

            $query_array = array_map(
                function($key, $value) { return $key.'='.$value; },
                array_keys($params),
                array_values($params)
            );

            if (!empty($query_array)) {
                $query = '?'.implode('&&', $query_array);
            }
        }

        $scheme = '';
        if (array_key_exists('scheme', $parsed)) {
            $scheme = $parsed['scheme'].'://';
        }

        return implode('', array(
            $scheme,
            $parsed['host'],
            $parsed['path'] ?? '',
            $query,
            $fragment
        ));
    }

    public function canonical_link() {
        $url = "";

        if (is_single()) {
            global $page;
            if ($page) {
                $url = get_pagenum_link($page);
            } else {
                $url = get_permalink();
            }
        } else if (is_paged()) {
            if (!is_search()) {
                $url = get_pagenum_link(get_query_var('paged'));
            }
        } else if(is_front_page()) {
            $url = get_home_url();
        } else if(is_category() || is_tag()) {
            $url = get_term_link(get_queried_object());
        }

        if ($url) {
            echo '<link rel="canonical" href="'.esc_url($this->remove_args($url)).'" />';
        }
    }

    public function document_title($title) {
        $sep = ' &#124; ';
        $array = [];
        $term = get_queried_object();

        global $page;

        if (is_singular()) {
            if ($page) {
                array_push($array, sprintf('Часть %d', intval($page)));
            }
            array_push($array, get_the_title($term));
        } else if (is_paged()) {
            array_push($array, sprintf('Страница %d', intval(get_query_var('paged'))));
        }

        if (is_month()) {
            $month = get_the_date('F Y');
            array_push($array, sprintf('Архив постов за %s', mb_strtolower($month)));
        } else if (is_year()) {
            array_push($array, sprintf('Архив постов за %s год', get_the_date('Y')));
        } else if (is_search()) {
            array_push($array, sprintf('Результат поиска по запросу "%s"', esc_html($_GET['s'])));
        } else if (is_category($term)) {
            array_push($array, sprintf('Категория "%s"', $term->name));
        } else if (is_tag($term)) {
            array_push($array, sprintf('Метка "%s"', $term->name));
        }

        array_push($array, get_bloginfo('name'));

        return implode($sep, $array);
    }

    public function deregister_yarpp_header_styles() {
        wp_dequeue_style('yarppWidgetCss');
        wp_deregister_style('yarppRelatedCss');
    }

    public function remove_block_library_css() {
        wp_dequeue_style('wp-block-library');
    }

    public function deregister_yarpp_footer_styles() {
        wp_dequeue_style('yarppRelatedCss');
    }

    public function remove_nofollow($formatted_link, $link, $text) {
        return str_replace('rel="nofollow"', "", $formatted_link);
    }

    public function print_meta_tag($name, $content) {
        echo '<meta name="' . esc_attr($name) . '" content="' . esc_attr($content) . '"/>';
    }

    public function print_meta_tags($tags) {
        foreach($tags as $name => $content) {
            if ($content) {
                $this->print_meta_tag($name, $content);
            }
        }
    }

    public function meta_description() {
        $term = get_queried_object();
        $array = array();

        if (is_front_page()) {
            array_push($array, get_bloginfo('name'));
            array_push($array, get_bloginfo('description'));
        } else if (is_singular()) {
            $post = get_post();
            if (has_excerpt()) {
                array_push($array, get_the_excerpt());
            } else {
                array_push($array, wp_trim_words($post->post_content, 22, ' ...'));
            }
        } elseif (is_tag()) {
            array_push($array, sprintf('Список постов с меткой "%s"', $term->name));
        } elseif (is_category()) {
            array_push($array, sprintf('Список постов в категории "%s"', $term->name));
        } else if (is_month()) {
            $month = get_the_date('F Y');
            array_push($array, sprintf('Архив постов за %s', mb_strtolower($month)));
        } else if (is_year()) {
            array_push($array, sprintf('Архив постов за %s год', get_the_date('Y')));
        } else {
            return "";
        }

        if (is_paged()) {
            array_push($array, sprintf('страница %d', intval(get_query_var('paged'))));
        }

        return esc_attr(implode(', ', $array));
    }

    public function og_meta() {
        $tags = array();
        $tags['description'] = $this->meta_description();

        if (!is_singular()) {
            $this->print_meta_tags($tags);
            return;
        }

        $tags['og:title'] = get_the_title();
        $tags['og:description'] = $tags['description'];
        $tags['og:type'] = "article";
        $tags['og:url'] = get_the_permalink();
        $tags['og:locale'] = get_locale();

        $tags['twitter:card'] = "summary";
        $tags['twitter:title'] = get_the_title();
        $tags['twitter:description'] = $tags['description'];
        $tags['twitter:image'] = '';

        $this->print_meta_tags($tags);

        $images = get_posts(array(
            'post_status'    => null,
            'post_type'      => 'attachment',
            'post_parent'    => get_the_ID(),
            'post_mime_type' => 'image',
            'order'          => 'ASC'
        ));

        foreach ($images as $img) {
            $url = wp_get_attachment_url($img->ID);
            $this->print_meta_tag('og:image', $url);
        }

        $posttags = get_the_tags();

        if ($posttags) {
            $keywords = array();

            foreach($posttags as $posttag) {
                array_push($keywords, $posttag->name);
            }

            $this->print_meta_tag('keywords', implode(', ', $keywords));
        }
    }
}

// Инициализация плагина
new B2K_Tools();

// LaTeX colors
global $themecolors;
$themecolors['bg'] = 'transparent';
$themecolors['text'] = '000';

// Внешние функции
function b2k_is_mobile() {
    if (!function_exists('jetpack_is_mobile')) {
        return false;
    }
    if (isset($_COOKIE['akm_mobile']) && $_COOKIE['akm_mobile'] == 'false') {
        return false;
    }
    return jetpack_is_mobile();
}

function b2k_get_post_counter($wp_stats, $post_id) {
    $stats = $wp_stats->convert_stats_array_to_object($wp_stats->get_post_views($post_id));
    if (isset($stats->views)) {
        return intval($stats->views);
    };
    return 0;
}

function b2k_update_all_counters() {
    if (!class_exists('Automattic\Jetpack\Stats\WPCOM_Stats')) {
        return;
    }

    $wp_stats = new WPCOM_Stats();

    // Получаем текущую страницу из опций
    $page = get_option('b2k_counters_update_page', 1);
    $posts_per_page = 50;

    $posts = get_posts(array(
        'post_type' => 'any',
        'posts_per_page' => $posts_per_page,
        'post_status' => 'publish',
        'offset' => ($page - 1) * $posts_per_page,
        'orderby' => 'ID',
        'order' => 'ASC'
    ));

    if (empty($posts)) {
        // Если записи закончились, сбрасываем счётчик
        update_option('b2k_counters_update_page', 1);
        return;
    }

    foreach ($posts as $post) {
        $counter = b2k_get_post_counter($wp_stats, $post->ID);
        $current_counter = get_post_meta($post->ID, 'b2k_post_counter', true);

        if ($current_counter != $counter) {
            update_post_meta($post->ID, 'b2k_post_counter', $counter);
        }
    }

    // Увеличиваем страницу для следующего запуска
    update_option('b2k_counters_update_page', $page + 1);
}
add_action('b2k_update_counters_event', 'b2k_update_all_counters');

function b2k_cron_activation() {
    wp_clear_scheduled_hook('b2k_update_counters_event');
    wp_schedule_event(time(), 'hourly', 'b2k_update_counters_event');
    // Инициализируем опцию для пагинации
    add_option('b2k_counters_update_page', 1);
}
register_activation_hook(__FILE__, 'b2k_cron_activation');

function b2k_cron_deactivation() {
    wp_clear_scheduled_hook('b2k_update_counters_event');
    // Удаляем опцию пагинации при деактивации
    delete_option('b2k_counters_update_page');
}
register_deactivation_hook(__FILE__, 'b2k_cron_deactivation');

?>
