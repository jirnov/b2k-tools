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

add_filter('xmlrpc_enabled', '__return_false');

remove_action('wp_head', 'rsd_link');
remove_action('wp_head', 'wp_shortlink_wp_head', 10, 0);

function b2k_remove_pingback($headers) {
  unset($headers['X-Pingback']);
  return $headers;
}
add_filter('wp_headers', 'b2k_remove_pingback');


function remove_jquery_migrate($scripts) {
  if (empty($scripts->registered['jquery']) || is_admin()) {
    return;
  }

  $deps = & $scripts->registered['jquery']->deps;
  $deps = array_diff($deps, [ 'jquery-migrate']);
}
add_filter('wp_default_scripts', 'remove_jquery_migrate');


function b2k_is_mobile() {
  if (!function_exists( 'jetpack_is_mobile')) {
    return false;
  }
  if ( isset( $_COOKIE['akm_mobile'] ) && $_COOKIE['akm_mobile'] == 'false' ) {
    return false;
  }
  return jetpack_is_mobile();
}  


function b2k_spoiler($atts, $content) {
  if (!isset($atts['name'])) {
    $sp_name = 'Спойлер';
  }
  else {
    $sp_name = $atts['name'];
  }
  return '<div class="spoiler-wrap"><div class="spoiler-head folded">'.$sp_name.'</div><div class="spoiler-body">'.$content.'</div></div>';
}
add_shortcode('spoiler', 'b2k_spoiler');


function b2k_enqueue_styles() {
  if (!is_admin()) {
    wp_deregister_style('dashicons');
  }

  wp_register_style(
    'social-likes',
    plugins_url('css/social-likes_flat.css', B2K_PLUGIN_FILE));

  wp_register_style(
    'spoiler',
    plugins_url('css/spoiler.css', B2K_PLUGIN_FILE));

  wp_register_style(
    'b2k_tools',
    plugins_url('css/b2k_tools.css', B2K_PLUGIN_FILE));

  wp_register_style(
    'spoiler-printer',
    plugins_url('css/spoiler-printer.css', B2K_PLUGIN_FILE),
    array(),
    null,
    'print');

  wp_enqueue_style('social-likes');
  wp_enqueue_style('spoiler');
  wp_enqueue_style('b2k_tools');
  wp_enqueue_style('spoiler-printer');
}
add_action('wp_enqueue_scripts', 'b2k_enqueue_styles');


function b2k_enqueue_scripts() {
  wp_register_script(
    'counters', 
    plugins_url('js/counters.js', B2K_PLUGIN_FILE),
    array('jquery'),
    false,
    true);

  wp_register_script(
    'spoiler', 
    plugins_url('js/spoiler.js', B2K_PLUGIN_FILE),
    array('jquery'), 
    false, 
    true);

  wp_register_script(
    'tools',
    plugins_url('js/tools.js', B2K_PLUGIN_FILE),
    array('jquery'),
    false,
    true);

  if (!is_user_logged_in() && !is_404()) {
    wp_enqueue_script('counters');
  }

  wp_register_script(
    'social-likes',
    plugins_url('js/social-likes.min.js', B2K_PLUGIN_FILE),
    array(),
    false,
    true);

  wp_enqueue_script('spoiler');
  wp_enqueue_script('tools');

  if (!is_404()) {
    wp_enqueue_script('social-likes');
  }
  //  wp_enqueue_script('prism', plugins_url('js/prism.js', B2K_PLUGIN_FILE));
}
add_action('wp_enqueue_scripts', 'b2k_enqueue_scripts');

add_filter('jetpack_implode_frontend_css', '__return_false');

// Добавление ссылки Поделиться с помощью social_likes
function b2k_social_likes($content='') {
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
  $tags['data-title'] = 'data-title="' . $post->post_title . '"';
  $tags['data-url'] = 'data-url="' . get_permalink($post->ID) . '"';
  $tags = implode(' ', $tags);

  $main = '<div ' . $tags . '>';
  $main .= $buttons;
  $main .= '</div>';

  $content .= $main;

  return $content;
}
add_filter('the_content', 'b2k_social_likes');

// Добавление ссылок rel next, rel prev
function b2k_rel_next_prev() {
  global $paged;
  $next_link = "";
  $prev_link = "";

  if (get_previous_posts_link()) {
    $prev_link = get_pagenum_link($paged - 1);
  }
  else if (is_singular()) {
    $prev_post = get_previous_post(true);
    if ($prev_post) {
      $prev_link = get_permalink($prev_post->ID);
    }
  }

  if(get_next_posts_link()) {
    $next_link = get_pagenum_link($paged + 1);
  }
  else if (is_singular()) {
    $next_post = get_next_post(true);
    if ($next_post) {
      $next_link = get_permalink($next_post->ID);
    }
  }

  if ($prev_link) {
    echo '<link rel="prev" test="3" href="'.b2k_remove_args($prev_link).'"/>';
  }

  if ($next_link) {
    echo '<link rel="next" href="'.b2k_remove_args($next_link).'"/>';
  }
}
add_action('wp_head', 'b2k_rel_next_prev');


add_filter('paginate_links', 'b2k_remove_args');

// Если поиском нашёлся один пост, то сразу открываем его, вместо поисковой страницы
function b2k_redirect_single_post() {
  if (is_search() && !is_paged()) {
    global $wp_query;
    if ($wp_query->post_count == 1) {
      wp_redirect( get_permalink( $wp_query->posts['0']->ID ) );
      die;
    }
  }
}
add_action('template_redirect', 'b2k_redirect_single_post');


function b2k_disable_author_page() {
  if (is_author() && !is_404()) {
    global $wp_query;
    $wp_query->set_404();
    status_header(404);
    nocache_headers();
  }
}
add_action('template_redirect', 'b2k_disable_author_page');

// Открываем все ссылки в новом окне
function b2k_target_blank($content) {
  $content = preg_replace_callback(       
    '/<a[^>]*href=^#["|\']([^"|\']*)["|\'][^>]*>([^<]*)<\/a>/i',

    function($m) {
      $url = $m[1];
      $desc = $m[2];
      return '<a href="'.$url.'" target="_blank">'.$desc.'</a>';
    },
    $content);

  return $content;
}
add_filter('the_content', 'b2k_target_blank');

// Добавляем всем ссылкам, кроме избранных, параметр nofollow
function b2k_nofollow_links($content) {
  $content = preg_replace_callback(       
    '/<a[^>]*href=["|\']([^"|\']*)["|\'][^>]*>([^<]*)<\/a>/i',

    function($m) {
      $url = $m[1];
      $desc = $m[2];
      $nofollow = true;

      $ownDomain = $_SERVER['HTTP_HOST'];

      $excludeDomains = array(
        'yowindow.com',
        'yowindow.ru');            

      if (strpos($url, $ownDomain)) {
        $nofollow = false;
      }
      else {
        foreach ($excludeDomains as $domain) {
          if (strpos($url, $domain)) {
            $nofollow = false;
            break;
          }
        }
        unset($domain);
      }

      if ($nofollow) {
        return '<a href="'.$url.'" rel="nofollow" target="_blank">'.$desc.'</a>';
      }
      else {
        return '<a href="'.$url.'" target="_blank">'.$desc.'</a>';
      }
    },
    $content);

  return $content;
}
add_filter('the_content', 'b2k_nofollow_links');

// Добавляем meta теги для верификации в разных сервисах
function b2k_meta_verify() {
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

  b2k_print_meta_tags($tags);
}
add_action('wp_head', 'b2k_meta_verify');


remove_action('wp_enqueue_scripts', 'wp_enqueu_global_styles');
remove_action('wp_body_open','wp_global_styles_render_svg_filters');


// LaTeX colors
global $themecolors;
$themecolors['bg'] = 'transparent';
$themecolors['text'] = '000';


function b2k_redirect_attachment_page() {
  if (is_attachment()) {
    global $post;
    if ($post && $post->post_parent) {
      wp_redirect(esc_url(get_permalink($post->post_parent)), 301);
      exit;
    }
    else {
      global $wp_query;
      $wp_query->set_404();
      status_header(404);
      get_template_part(404);
      exit;
    }
  }
}
add_action('template_redirect', 'b2k_redirect_attachment_page');


function b2k_remove_args($url) {
  $parsed = parse_url($url);

  $fragment = array_key_exists('fragment', $parsed) ? '#' . $parsed['fragment'] : '';

  $query = '';

  if (array_key_exists('query', $parsed)) {
    parse_str($parsed['query'], $params);

    $params = array_filter(
      $params, 
      function ($e) { return in_array($e, array('s', 'page')); }, 
        ARRAY_FILTER_USE_KEY);

    $query_array = array_map(
      function($key, $value) { return $key.'='.$value; }, 
        array_keys($params), 
        array_values($params));

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
    $fragment));
}


function b2k_canonical_link() {
  $url = "";

  if (is_single()) {
    global $page;
    if ($page) {
      $url = get_pagenum_link($page);
    }
    else {
      $url = get_permalink();
    }
  }
  else if (is_paged()) {
    if (!is_search()) {
      $url = get_pagenum_link(get_query_var('paged'));
    }
  }
  else if(is_front_page()) {
    $url = get_home_url();
  }
  else if(is_category() || is_tag()) {
    $url = get_term_link(get_queried_object());
  }

  if ($url) {
    echo '<link rel="canonical" href="'.b2k_remove_args($url).'" />';
  }
}
add_action('wp_head', 'b2k_canonical_link');


function b2k_document_title($title) {
  $sep = ' &#124; ';
  $array = [];
  $term = get_queried_object();

  global $page;

  if (is_singular()) {
    if ($page) {
      array_push($array, sprintf('Часть %d', intval($page)));
    }
    array_push($array, get_the_title($term));
  }
  else if (is_paged()) {
    array_push($array, sprintf('Страница %d', intval(get_query_var('paged'))));
  }

  if (is_month()) {
    $month = get_the_date('F Y');
    array_push($array, sprintf('Архив постов за %s', mb_strtolower($month)));
  }
  else if (is_year()) {
    array_push($array, sprintf('Архив постов за %s год', get_the_date('Y') ));
  }
  else if (is_search()) {
    array_push($array, sprintf('Результат поиска по запросу "%s"', esc_html($_GET['s'])));
  }
  else if (is_category($term)) {
    array_push($array, sprintf('Категория "%s"', $term->name));
  }
  else if (is_tag($term)) {
    array_push($array, sprintf('Метка "%s"', $term->name));
  }

  array_push($array, get_bloginfo('name'));

  return implode($sep, $array);
}
add_filter('document_title', 'b2k_document_title');


function b2k_deregister_yarpp_header_styles() {
  wp_dequeue_style('yarppWidgetCss');
  wp_deregister_style('yarppRelatedCss');
}
add_action('wp_print_styles', 'b2k_deregister_yarpp_header_styles');


function b2k_remove_block_library_css() {
  wp_dequeue_style('wp-block-library');
}
add_action('wp_enqueue_scripts', 'b2k_remove_block_library_css');


function b2k_deregister_yarpp_footer_styles() {
  wp_dequeue_style('yarppRelatedCss');
}
add_action('wp_footer', 'b2k_deregister_yarpp_footer_styles');


function b2k_remove_nofollow($formatted_link, $link, $text) {
  return str_replace('rel="nofollow"', "", $formatted_link);
}
add_filter("cancel_comment_reply_link", "b2k_remove_nofollow", 420, 4);


function b2k_print_meta_tag($name, $content) {
  echo '<meta name="' . $name . '" content="' . $content . '"/>';
}


function b2k_print_meta_tags($tags) {
  foreach($tags as $name => $content) {
    if ($content) {
      b2k_print_meta_tag($name, $content);
    }      
  }
}


function b2k_meta_description() {
  $term = get_queried_object();
  $array = array();

  if (is_front_page()) {
    array_push($array, get_bloginfo('name'));
    array_push($array, get_bloginfo('description'));
  }
  else if (is_singular()) {
    $post = get_post();
    array_push($array, wp_trim_words($post->post_content, 22, ' ...'));
  }
  elseif (is_tag()) {
    array_push($array, sprintf('Список постов с меткой "%s"', $term->name));
  }
  elseif (is_category()) {
    array_push($array, sprintf('Список постов в категории "%s"', $term->name));
  }
  else if (is_month()) {
    $month = get_the_date('F Y');
    array_push($array, sprintf('Архив постов за %s', mb_strtolower($month)));
  }
  else if (is_year()) {
    array_push($array, sprintf('Архив постов за %s год', get_the_date('Y')));
  }
  else {
    return "";
  }

  if (is_paged()) {
    array_push($array, sprintf('страница %d', intval(get_query_var('paged'))));
  }

  return esc_attr(implode(', ', $array));
}


function b2k_og_meta() {
  $tags = array();
  $tags['description'] = b2k_meta_description();

  if (!is_singular()) {
    b2k_print_meta_tags($tags);
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

  b2k_print_meta_tags($tags);

  $images = get_posts(array(
    'post_status'    => null,
    'post_type'      => 'attachment',
    'post_parent'    => get_the_ID(),
    'post_mime_type' => 'image',
    'order'          => 'ASC'
  ));

  foreach ($images as $img) {
    $url = wp_get_attachment_url($img->ID);
    b2k_print_meta_tag('og:image', $url);
  }

  $posttags = get_the_tags();

  if ($posttags) {
    $keywords = array();

    foreach($posttags as $posttag) {
      array_push($keywords, $posttag->name);
    }

    b2k_print_meta_tag('keywords', implode(', ', $keywords));
  }
}
add_action('wp_head', 'b2k_og_meta');

use Automattic\Jetpack\Stats\WPCOM_Stats;

function b2k_get_post_counter($wp_stats, $post_id) {
  $stats = $wp_stats->convert_stats_array_to_object($wp_stats->get_post_views($post_id));
  if (isset($stats->views)) {
    return intval($stats->views);
  };
  return 0;
}

function b2k_update_all_counters() {
  $posts = get_posts(
    array(
      'post_type' => 'any',
      'posts_per_page' => -1,
      'post_status' => 'publish'
    )
  );

  $wp_stats = new WPCOM_Stats();

  foreach ($posts as $post) {
    $counter = b2k_get_post_counter($wp_stats, $post->ID);
    if (get_post_meta($post->ID, '_post_counter', true) != $counter) {
      update_post_meta($post->ID, '_post_counter', $counter);
    }      
  }
}
add_action('b2k_update_counters_event', 'b2k_update_all_counters');

function b2k_cron_activation() {
  wp_clear_scheduled_hook('b2k_update_counters_event');
  wp_schedule_event(time(), 'twicedaily', 'b2k_update_counters_event');
}
register_activation_hook(__FILE__, 'b2k_cron_activation');

function b2k_cron_deactivation() {
  wp_clear_scheduled_hook('b2k_update_counters_event');
}
register_deactivation_hook(__FILE__, 'b2k_cron_deactivation');

?>
