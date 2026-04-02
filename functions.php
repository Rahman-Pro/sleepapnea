<?php
/**
 * DSRC Theme Functions
 * Dhaka Sleep Research Centre - sleepapneabd.com
 *
 * @package DSRC
 * @version 4.0 (Final — Performance + SEO Complete)
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}


/* ═══════════════════════════════════════════════════════
 * 1. THEME SETUP
 * ═══════════════════════════════════════════════════════ */

if ( ! function_exists( 'ssa_theme_setup' ) ) {
    function ssa_theme_setup() {
        add_theme_support( 'title-tag' );
        add_theme_support( 'post-thumbnails' );
    }
}
add_action( 'after_setup_theme', 'ssa_theme_setup' );


/* ═══════════════════════════════════════════════════════
 * 2. TAG ARCHIVE → SINGLE POST REDIRECT
 * ═══════════════════════════════════════════════════════ */

add_action( 'template_redirect', function () {
    if ( ! is_tag() ) {
        return;
    }

    $tag = get_queried_object();
    if ( ! $tag || ! isset( $tag->slug ) ) {
        return;
    }

    $posts = get_posts( array(
        'tag'            => $tag->slug,
        'posts_per_page' => 1,
        'post_type'      => 'post',
        'post_status'    => 'publish',
        'no_found_rows'  => true,
    ) );

    if ( ! empty( $posts ) ) {
        wp_redirect( get_permalink( $posts[0]->ID ), 301 );
        exit;
    }

    wp_redirect( home_url(), 301 );
    exit;
} );


/* ═══════════════════════════════════════════════════════
 * 3. GEMINI AI PROXY (REST API)
 * ═══════════════════════════════════════════════════════ */

add_action( 'rest_api_init', function () {
    register_rest_route(
        'dsrc/v1',
        '/ai-report',
        array(
            'methods'             => 'POST',
            'callback'            => 'dsrc_ai_report_handler',
            'permission_callback' => 'dsrc_ai_report_permission',
        )
    );
} );

function dsrc_ai_report_permission( WP_REST_Request $req ) {
    $nonce = $req->get_header( 'X-WP-Nonce' );
    if ( $nonce && wp_verify_nonce( $nonce, 'dsrc_ai' ) ) {
        return true;
    }
    return new WP_Error( 'forbidden', 'Invalid nonce', array( 'status' => 403 ) );
}

function dsrc_ai_report_handler( WP_REST_Request $req ) {

    $api_key = defined( 'GEMINI_API_KEY' ) ? GEMINI_API_KEY : getenv( 'GEMINI_API_KEY' );
    if ( empty( $api_key ) ) {
        return new WP_REST_Response( array( 'error' => 'Server API key not configured' ), 500 );
    }

    $body  = $req->get_json_params();
    $parts = ( isset( $body['parts'] ) && is_array( $body['parts'] ) ) ? $body['parts'] : array();

    if ( empty( $parts ) ) {
        return new WP_REST_Response( array( 'error' => 'Missing parts' ), 400 );
    }

    $model          = isset( $body['model'] ) ? sanitize_text_field( $body['model'] ) : 'gemini-1.5-flash';
    $allowed_models = array( 'gemini-1.5-flash', 'gemini-1.5-flash-8b', 'gemini-1.5-flash-lite' );

    if ( ! in_array( $model, $allowed_models, true ) ) {
        return new WP_REST_Response( array( 'error' => 'Blocked: Only free Gemini flash models allowed', 'allowed_models' => $allowed_models ), 400 );
    }

    $system = ! empty( $body['system'] )
        ? wp_kses_post( $body['system'] )
        : 'Act as a specialized Sleep Health Advisor, non-diagnostic, structured output.';

    $raw_len = strlen( wp_json_encode( $parts ) );
    if ( $raw_len > 8 * 1024 * 1024 ) {
        return new WP_REST_Response( array( 'error' => 'Payload too large' ), 413 );
    }

    $payload = array(
        'contents'          => array( array( 'role' => 'user', 'parts' => $parts ) ),
        'systemInstruction' => array( 'role' => 'system', 'parts' => array( array( 'text' => $system ) ) ),
    );

    $url = sprintf(
        'https://generativelanguage.googleapis.com/v1beta/models/%s:generateContent?key=%s',
        rawurlencode( $model ),
        rawurlencode( $api_key )
    );

    $res = wp_remote_post( $url, array(
        'headers' => array( 'Content-Type' => 'application/json' ),
        'body'    => wp_json_encode( $payload ),
        'timeout' => 45,
    ) );

    if ( is_wp_error( $res ) ) {
        return new WP_REST_Response( array( 'error' => $res->get_error_message() ), 502 );
    }

    $code = wp_remote_retrieve_response_code( $res );
    $data = json_decode( wp_remote_retrieve_body( $res ), true );

    if ( $code < 200 || $code >= 300 ) {
        $msg = isset( $data['error']['message'] ) ? $data['error']['message'] : 'Upstream API error';
        return new WP_REST_Response( array( 'error' => $msg, 'status' => $code ), $code );
    }

    return new WP_REST_Response( $data, 200 );
}


/* ═══════════════════════════════════════════════════════
 * 4. RELATED POSTS SYSTEM
 * ═══════════════════════════════════════════════════════ */

if ( ! function_exists( 'dsrc_render_related_posts' ) ) {
    function dsrc_render_related_posts( $post_id = 0, $limit = 5, $style = 'cards' ) {
        if ( ! $post_id ) {
            $post_id = get_the_ID();
        }
        if ( ! $post_id ) {
            return;
        }

        $style = in_array( $style, array( 'cards', 'titles' ), true ) ? $style : 'cards';
        $limit = absint( $limit ) ?: 5;

        $cache_key = "dsrc_related_{$post_id}_{$limit}_{$style}";
        $cached    = get_transient( $cache_key );
        if ( false !== $cached ) {
            echo $cached;
            return;
        }

        $tags       = wp_get_post_tags( $post_id, array( 'fields' => 'ids' ) );
        $categories = wp_get_post_categories( $post_id, array( 'fields' => 'ids' ) );

        $args_common = array(
            'post_type'           => 'post',
            'post_status'         => 'publish',
            'posts_per_page'      => $limit,
            'post__not_in'        => array( $post_id ),
            'ignore_sticky_posts' => true,
            'no_found_rows'       => true,
            'orderby'             => 'date',
            'order'               => 'DESC',
        );

        $html     = '';
        $build_fn = ( 'titles' === $style ) ? 'dsrc_build_related_titles_html' : 'dsrc_build_related_cards_html';

        if ( empty( $html ) && ! empty( $tags ) ) {
            $q = new WP_Query( array_merge( $args_common, array(
                'tax_query' => array( array( 'taxonomy' => 'post_tag', 'field' => 'term_id', 'terms' => $tags ) ),
            ) ) );
            if ( $q->have_posts() ) {
                $html = $build_fn( $q, $limit, 'সম্পর্কিত প্রবন্ধ (Related Articles)' );
            }
            wp_reset_postdata();
        }

        if ( empty( $html ) && ! empty( $categories ) ) {
            $q = new WP_Query( array_merge( $args_common, array( 'category__in' => $categories ) ) );
            if ( $q->have_posts() ) {
                $html = $build_fn( $q, $limit, 'সম্পর্কিত প্রবন্ধ (Related Articles)' );
            }
            wp_reset_postdata();
        }

        if ( empty( $html ) ) {
            $q = new WP_Query( $args_common );
            if ( $q->have_posts() ) {
                $html = $build_fn( $q, $limit, 'আরও পড়ুন (More to read)' );
            }
            wp_reset_postdata();
        }

        set_transient( $cache_key, $html, 12 * HOUR_IN_SECONDS );
        echo $html;
    }
}

if ( ! function_exists( 'dsrc_build_related_cards_html' ) ) {
    function dsrc_build_related_cards_html( $wp_query, $limit, $heading = 'Related Articles' ) {
        ob_start();
        ?>
        <section class="mt-10 pt-8 border-t border-slate-200">
            <h3 class="text-xl font-extrabold text-[#073B4C] mb-4"><?php echo esc_html( $heading ); ?></h3>
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
                <?php $i = 0; while ( $wp_query->have_posts() && $i < $limit ) : $wp_query->the_post(); $i++; ?>
                    <a href="<?php the_permalink(); ?>" class="group block rounded-lg border border-slate-200 bg-white hover:shadow-lg transition overflow-hidden">
                        <div class="aspect-[16/9] bg-slate-100 overflow-hidden">
                            <?php if ( has_post_thumbnail() ) : ?>
                                <?php the_post_thumbnail( 'medium', array( 'class' => 'w-full h-full object-cover group-hover:scale-[1.03] transition' ) ); ?>
                            <?php else : ?>
                                <div class="w-full h-full flex items-center justify-center text-slate-400 text-sm">No Image</div>
                            <?php endif; ?>
                        </div>
                        <div class="p-3">
                            <h4 class="text-base font-semibold leading-snug line-clamp-2 group-hover:text-[#118AB2]"><?php echo esc_html( get_the_title() ); ?></h4>
                            <div class="mt-1 text-xs text-slate-500"><?php echo esc_html( get_the_date( 'j M Y' ) ); ?></div>
                        </div>
                    </a>
                <?php endwhile; ?>
            </div>
        </section>
        <?php
        return ob_get_clean();
    }
}

if ( ! function_exists( 'dsrc_build_related_titles_html' ) ) {
    function dsrc_build_related_titles_html( $wp_query, $limit, $heading = 'Related Articles' ) {
        ob_start();
        ?>
        <section class="mt-10 pt-8 border-t border-slate-200">
            <h3 class="text-xl font-extrabold text-[#073B4C] mb-4"><?php echo esc_html( $heading ); ?></h3>
            <ul class="list-disc pl-6 space-y-2">
                <?php $i = 0; while ( $wp_query->have_posts() && $i < $limit ) : $wp_query->the_post(); $i++; ?>
                    <li class="leading-snug">
                        <a href="<?php the_permalink(); ?>" class="text-[#073B4C] hover:text-[#118AB2] font-semibold"><?php echo esc_html( get_the_title() ); ?></a>
                        <span class="ml-2 text-xs text-slate-500"><?php echo esc_html( get_the_date( 'j M Y' ) ); ?></span>
                    </li>
                <?php endwhile; ?>
            </ul>
        </section>
        <?php
        return ob_get_clean();
    }
}

add_action( 'save_post_post', function ( $post_id ) {
    $post_id = absint( $post_id );
    foreach ( array( 3, 5, 10 ) as $limit ) {
        foreach ( array( 'cards', 'titles' ) as $style ) {
            delete_transient( "dsrc_related_{$post_id}_{$limit}_{$style}" );
        }
    }
} );

add_shortcode( 'dsrc_related', function ( $atts ) {
    $atts = shortcode_atts( array( 'limit' => 5, 'style' => 'cards' ), $atts, 'dsrc_related' );
    ob_start();
    if ( function_exists( 'dsrc_render_related_posts' ) ) {
        dsrc_render_related_posts( get_the_ID(), absint( $atts['limit'] ), sanitize_key( $atts['style'] ) );
    }
    return ob_get_clean();
} );


/* ═══════════════════════════════════════════════════════
 * 5. SCRIPT ENQUEUE & OPTIMIZATION
 * ═══════════════════════════════════════════════════════ */

add_action( 'wp_enqueue_scripts', function () {
    if ( ! is_singular( 'post' ) ) {
        return;
    }
    $file_path = get_stylesheet_directory() . '/seo-key-click.js';
    if ( ! file_exists( $file_path ) ) {
        return;
    }
    wp_enqueue_script( 'seo-key-click-js', get_stylesheet_directory_uri() . '/seo-key-click.js', array( 'jquery' ), filemtime( $file_path ), true );
    wp_localize_script( 'seo-key-click-js', 'seo_ajax_obj', array( 'ajax_url' => admin_url( 'admin-ajax.php' ) ) );
} );

add_action( 'wp_enqueue_scripts', function () {
    wp_dequeue_script( 'awpbusinesspress-mobile-menu-js' );
    wp_deregister_script( 'awpbusinesspress-mobile-menu-js' );
    $menu_file = get_template_directory() . '/assets/js/mobile-menu.js';
    if ( file_exists( $menu_file ) ) {
        wp_enqueue_script( 'awpbusinesspress-mobile-menu-js', get_template_directory_uri() . '/assets/js/mobile-menu.js', array(), filemtime( $menu_file ), true );
    }
}, 50 );

add_filter( 'script_loader_tag', function ( $tag, $handle, $src ) {
    if ( is_admin() ) {
        return $tag;
    }
    if ( in_array( $handle, array( 'jquery', 'jquery-core', 'jquery-migrate' ), true ) ) {
        return $tag;
    }
    if ( false !== strpos( $src, 'tailwindcss' ) || false !== strpos( $src, 'tailwind' ) ) {
        return $tag;
    }
    if ( false !== strpos( $tag, ' defer' ) || false !== strpos( $tag, ' async' ) ) {
        return $tag;
    }
    return str_replace( ' src=', ' defer src=', $tag );
}, 10, 3 );


/* ═══════════════════════════════════════════════════════
 * 6. LCP & IMAGE OPTIMIZATION
 * ═══════════════════════════════════════════════════════
 * NOTE: Preconnect & Critical CSS are in header.php
 *       Only image preload & fetchpriority kept here
 * ═══════════════════════════════════════════════════════ */

add_action( 'wp_head', function () {
    if ( is_admin() ) {
        return;
    }
    if ( is_singular() && has_post_thumbnail() ) {
        $preload_url = get_the_post_thumbnail_url( get_the_ID(), 'full' );
        if ( ! empty( $preload_url ) ) {
            echo '<link rel="preload" as="image" href="' . esc_url( $preload_url ) . '">' . "\n";
        }
    }
}, 2 );

add_filter( 'wp_get_attachment_image_attributes', function ( $attr, $attachment, $size ) {
    $src = isset( $attr['src'] ) ? $attr['src'] : '';
    if ( $src && stripos( $src, 'woman-stretching-in-bed' ) !== false ) {
        $attr['fetchpriority'] = 'high';
        $attr['loading']       = 'eager';
    }
    if ( is_singular() && has_post_thumbnail() ) {
        $featured_id = get_post_thumbnail_id( get_the_ID() );
        if ( $attachment && isset( $attachment->ID ) && $attachment->ID === $featured_id ) {
            $attr['fetchpriority'] = 'high';
            $attr['loading']       = 'eager';
        }
    }
    return $attr;
}, 10, 3 );


/* ═══════════════════════════════════════════════════════
 * 7. STRUCTURED DATA (JSON-LD) — HOMEPAGE
 * ═══════════════════════════════════════════════════════ */

add_action( 'wp_head', function () {
    if ( ! is_front_page() ) {
        return;
    }
    ?>
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "MedicalClinic",
        "name": "Dhaka Sleep Research Centre (DSRC)",
        "alternateName": ["ঢাকা স্লিপ রিসার্চ সেন্টার", "DSRC Sleep Clinic", "Sleep Apnea Bangladesh", "Sleep Clinic Dhaka"],
        "url": "https://sleepapneabd.com",
        "logo": "https://sleepapneabd.com/wp-content/uploads/2024/05/logo.jpg",
        "image": "https://sleepapneabd.com/wp-content/uploads/2024/05/logo.jpg",
        "description": "Bangladesh's leading sleep apnea diagnostic and treatment centre. Expert polysomnography (PSG), CPAP therapy, and comprehensive sleep disorder management in Dhaka.",
        "telephone": "+8801708031201",
        "email": "sleepapneahub@gmail.com",
        "priceRange": "$$",
        "hasMap": "https://maps.app.goo.gl/uX3L56WvX5pE9V6M7",
        "sameAs": ["https://share.google/280LWJHl8hiZNUv74"],
        "openingHoursSpecification": [{
            "@type": "OpeningHoursSpecification",
            "dayOfWeek": ["Monday","Tuesday","Wednesday","Thursday","Friday","Saturday","Sunday"],
            "opens": "09:00",
            "closes": "17:00"
        }],
        "address": {
            "@type": "PostalAddress",
            "streetAddress": "House #25, Lake View Road, Aftabnagar (Near Meena Bazar)",
            "addressLocality": "Dhaka",
            "addressRegion": "Dhaka Division",
            "postalCode": "1219",
            "addressCountry": "BD"
        },
        "geo": {
            "@type": "GeoCoordinates",
            "latitude": "23.771120",
            "longitude": "90.443916"
        },
        "medicalSpecialty": ["SleepMedicine", "Pulmonology", "RespiratoryMedicine"],
        "keywords": "sleep apnea Bangladesh, sleep apnea BD, স্লিপ অ্যাপনিয়া, sleep apnea treatment BD, CPAP machine Bangladesh, sleep apnea doctor Dhaka, sleep clinic Dhaka, polysomnography test Bangladesh, নাক ডাকা সমস্যা, ঘুমের সমস্যা, sleep test BD",
        "areaServed": {"@type": "Country", "name": "Bangladesh"},
        "founder": {
            "@type": "Person",
            "name": "Prof. Dr. A.K.M. Mosharraf Hossain",
            "jobTitle": "Pioneer Sleep Apnea Specialist in Bangladesh",
            "description": "MBBS, FCCP, FRCP, Ph.D., FCPS. Former Chairman, Respiratory Medicine Dept., BSMMU.",
            "knowsAbout": ["Sleep Apnea", "Polysomnography", "CPAP Therapy", "Sleep Medicine"]
        },
        "availableService": [
            {"@type": "MedicalProcedure", "name": "Sleep Test - Polysomnography (PSG)", "alternateName": "স্লিপ টেস্ট - পলিসমনোগ্রাফি", "description": "Comprehensive overnight sleep study for diagnosing sleep apnea."},
            {"@type": "MedicalTherapy", "name": "CPAP Therapy", "alternateName": "সিপ্যাপ থেরাপি", "description": "Gold standard treatment for obstructive sleep apnea using CPAP machine."},
            {"@type": "MedicalTest", "name": "Home Sleep Apnea Test (HSAT)", "alternateName": "হোম স্লিপ টেস্ট", "description": "Convenient at-home sleep apnea screening test."},
            {"@type": "MedicalProcedure", "name": "Pediatric Sleep Apnea Diagnosis", "alternateName": "শিশু স্লিপ অ্যাপনিয়া ডায়াগনসিস", "description": "Specialized sleep testing for children."}
        ],
        "knowsAbout": ["Sleep Apnea", "Obstructive Sleep Apnea", "Snoring Treatment", "CPAP Machine", "Polysomnography", "স্লিপ অ্যাপনিয়া", "নাক ডাকা", "ঘুমের সমস্যা"]
    }
    </script>
    <?php
}, 20 );


/* ═══════════════════════════════════════════════════════
 * 8. AI NONCE GENERATION
 * ═══════════════════════════════════════════════════════ */

add_action( 'wp_enqueue_scripts', function () {
    if ( ! is_page( 'sleep-apnea-live' ) && ! is_singular( 'post' ) ) {
        return;
    }
    $ai_script = get_stylesheet_directory() . '/js/ai-report.js';
    if ( file_exists( $ai_script ) ) {
        wp_enqueue_script( 'dsrc-ai-frontend', get_stylesheet_directory_uri() . '/js/ai-report.js', array( 'jquery' ), filemtime( $ai_script ), true );
        wp_localize_script( 'dsrc-ai-frontend', 'dsrc_ai_vars', array( 'nonce' => wp_create_nonce( 'dsrc_ai' ), 'endpoint' => esc_url_raw( rest_url( 'dsrc/v1/ai-report' ) ) ) );
    } else {
        wp_localize_script( 'jquery', 'dsrc_ai_vars', array( 'nonce' => wp_create_nonce( 'dsrc_ai' ), 'endpoint' => esc_url_raw( rest_url( 'dsrc/v1/ai-report' ) ) ) );
    }
}, 20 );


/* ═══════════════════════════════════════════════════════
 * 9. PERFORMANCE OPTIMIZATION
 * ═══════════════════════════════════════════════════════ */

/* 9a. jQuery duplicate + Font Awesome removal (Priority 90) */
add_action( 'wp_enqueue_scripts', function () {
    wp_dequeue_script( 'jquery-min' );
    wp_deregister_script( 'jquery-min' );
    foreach ( array( 'font-awesome', 'fontawesome', 'font-awesome-css', 'font-awesome-min', 'font-awesome-min-css' ) as $handle ) {
        wp_dequeue_style( $handle );
        wp_deregister_style( $handle );
    }
}, 90 );

/* 9b. jQuery Migrate removal */
add_action( 'wp_default_scripts', function ( $scripts ) {
    if ( ! is_admin() && isset( $scripts->registered['jquery'] ) ) {
        $script = $scripts->registered['jquery'];
        if ( $script->deps ) {
            $script->deps = array_diff( $script->deps, array( 'jquery-migrate' ) );
        }
    }
} );

/* 9c. Font cleanup — ONE combined Google Fonts (Priority 100) */
add_action( 'wp_enqueue_scripts', function () {
    foreach ( array(
        'awpbusinesspress-google-fonts', 'awpbusinesspress-google-fo', 'awpbusinesspress-google-fonts-css',
        'awpbusinesspress-default-fonts', 'awpbusinesspress-default-fo', 'awpbusinesspress-default-fonts-css',
        'montserrat-google-fonts', 'montserrat-google-fonts-css',
        'opensans-google-fonts', 'opensans-google-fonts-css',
        'hospital-health-care-google-fonts',
    ) as $handle ) {
        wp_dequeue_style( $handle );
        wp_deregister_style( $handle );
    }
    wp_enqueue_style( 'dsrc-combined-fonts', 'https://fonts.googleapis.com/css2?family=Montserrat:ital,wght@0,300;0,400;0,500;0,600;0,700;1,400&family=Open+Sans:ital,wght@0,300;0,400;0,600;0,700;1,400&display=swap', array(), null );
}, 100 );

/* 9d. Remove render-blocking CSS (Priority 110) */
add_action( 'wp_enqueue_scripts', function () {
    foreach ( array( 'animate', 'animate-css', 'awpbusinesspress-animate', 'awpbusinesspress-animate-css' ) as $h ) {
        wp_dequeue_style( $h ); wp_deregister_style( $h );
    }
    foreach ( array( 'loading-icon', 'loading-icon-css', 'awpbusinesspress-loading-icon' ) as $h ) {
        wp_dequeue_style( $h ); wp_deregister_style( $h );
    }
    /* ⚠️ যদি carousel/slider থাকে, নিচের lines comment করুন */
    foreach ( array( 'owl-carousel', 'owl-carousel-css', 'awpbusinesspress-owl-carousel' ) as $h ) {
        wp_dequeue_style( $h ); wp_deregister_style( $h );
    }
    foreach ( array( 'skin-default', 'skin-default-css', 'awpbusinesspress-skin-default' ) as $h ) {
        wp_dequeue_style( $h ); wp_deregister_style( $h );
    }
}, 110 );

/* 9e. Defer non-critical CSS */
add_filter( 'style_loader_tag', function ( $html, $handle, $href, $media ) {
    if ( is_admin() || empty( $href ) ) {
        return $html;
    }
    $defer_handles = array( 'bootstrap', 'bootstrap-css', 'awpbusinesspress-bootstrap', 'awpbusinesspress-bootstrap-css', 'bootstrap-smartmenus', 'bootstrap-smartmenus-css', 'awpbusinesspress-bootstrap-smartmenus', 'awpbusinesspress-bootstrap-smartmenus-css' );
    $is_autoptimize = ( false !== strpos( $href, '/cache/autoptimize/css/' ) );
    if ( ! in_array( $handle, $defer_handles, true ) && ! $is_autoptimize ) {
        return $html;
    }
    $media   = ! empty( $media ) ? $media : 'all';
    $preload = sprintf( '<link rel="preload" as="style" href="%s" onload="this.onload=null;this.rel=\'stylesheet\'" media="%s">', esc_url( $href ), esc_attr( $media ) );
    return $preload . "\n<noscript>" . $html . "</noscript>";
}, 10, 4 );


/* ═══════════════════════════════════════════════════════
 * 10. WORDPRESS CLEANUP & SECURITY
 * ═══════════════════════════════════════════════════════ */

/* 10a. Remove Emoji, Clean Head, Disable Frontend Heartbeat */
add_action( 'init', function () {
    remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
    remove_action( 'wp_print_styles', 'print_emoji_styles' );
    remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
    remove_action( 'admin_print_styles', 'print_emoji_styles' );
    remove_filter( 'the_content_feed', 'wp_staticize_emoji' );
    remove_filter( 'comment_text_rss', 'wp_staticize_emoji' );
    remove_filter( 'wp_mail', 'wp_staticize_emoji_for_email' );
    add_filter( 'wp_resource_hints', function ( $urls, $relation_type ) {
        if ( 'dns-prefetch' === $relation_type ) {
            $urls = array_diff( $urls, array( apply_filters( 'emoji_svg_url', 'https://s.w.org/images/core/emoji/2/svg/' ) ) );
        }
        return $urls;
    }, 10, 2 );
    add_filter( 'tiny_mce_plugins', function ( $plugins ) {
        return is_array( $plugins ) ? array_diff( $plugins, array( 'wpemoji' ) ) : array();
    } );
    remove_action( 'wp_head', 'wp_generator' );
    remove_action( 'wp_head', 'wlwmanifest_link' );
    remove_action( 'wp_head', 'rsd_link' );
    remove_action( 'wp_head', 'wp_shortlink_wp_head' );
    remove_action( 'wp_head', 'rest_output_link_wp_head', 10 );
    remove_action( 'wp_head', 'wp_oembed_add_discovery_links' );
    global $pagenow;
    if ( 'post.php' !== $pagenow && 'post-new.php' !== $pagenow ) {
        wp_deregister_script( 'heartbeat' );
    }
}, 1 );

/* 10b. Security Headers + CSP */
add_action( 'send_headers', function () {
    if ( is_admin() ) {
        return;
    }
    header( 'X-Content-Type-Options: nosniff' );
    header( 'X-Frame-Options: SAMEORIGIN' );
    header( 'X-XSS-Protection: 1; mode=block' );
    header( 'Referrer-Policy: strict-origin-when-cross-origin' );
    header( 'Permissions-Policy: camera=(), microphone=(), geolocation=()' );
    header( 'Cross-Origin-Opener-Policy: same-origin-allow-popups' );
    header( "Content-Security-Policy-Report-Only: "
        . "default-src 'self'; "
        . "script-src 'self' 'unsafe-inline' 'unsafe-eval' *.googleapis.com *.gstatic.com *.google-analytics.com *.googletagmanager.com *.google.com cdn.tailwindcss.com cdnjs.cloudflare.com static.cloudflareinsights.com generativelanguage.googleapis.com data:; "
        . "script-src-elem 'self' 'unsafe-inline' *.googleapis.com *.gstatic.com *.google-analytics.com *.googletagmanager.com *.google.com cdn.tailwindcss.com cdnjs.cloudflare.com static.cloudflareinsights.com generativelanguage.googleapis.com data:; "
        . "style-src 'self' 'unsafe-inline' *.googleapis.com fonts.googleapis.com cdn.tailwindcss.com cdnjs.cloudflare.com; "
        . "img-src 'self' data: blob: https:; "
        . "font-src 'self' fonts.gstatic.com cdnjs.cloudflare.com data:; "
        . "connect-src 'self' *.google-analytics.com *.google.com *.googleapis.com generativelanguage.googleapis.com; "
        . "frame-src 'self' *.youtube.com *.google.com; "
        . "media-src 'self';"
    );
} );

/* 10c. Lazy Load (LCP-aware) */
add_filter( 'the_content', function ( $content ) {
    if ( is_admin() || is_feed() ) {
        return $content;
    }
    $count = 0;
    $content = preg_replace_callback( '/<img(?!.*loading=)(.*?)>/i', function ( $matches ) use ( &$count ) {
        $count++;
        return ( $count === 1 ) ? '<img loading="eager" fetchpriority="high" ' . $matches[1] . '>' : '<img loading="lazy" ' . $matches[1] . '>';
    }, $content );
    $content = preg_replace( '/<iframe(?!.*loading=)(.*?)>/i', '<iframe loading="lazy" $1>', $content );
    return $content;
} );

/* 10d. Query String Removal (third-party only) */
add_action( 'init', function () {
    if ( ! is_admin() ) {
        add_filter( 'script_loader_src', 'dsrc_remove_query_strings', 15 );
        add_filter( 'style_loader_src', 'dsrc_remove_query_strings', 15 );
    }
} );

function dsrc_remove_query_strings( $src ) {
    if ( empty( $src ) ) {
        return $src;
    }
    foreach ( array( 'sleepapneabd.com', 'fonts.googleapis.com', 'cdnjs.cloudflare.com' ) as $domain ) {
        if ( false !== strpos( $src, $domain ) ) {
            return $src;
        }
    }
    if ( false !== strpos( $src, '?ver' ) ) {
        return explode( '?ver', $src )[0];
    }
    return $src;
}


/* ═══════════════════════════════════════════════════════
 * 11. COMPLETE SEO SYSTEM (NO PLUGIN)
 * ═══════════════════════════════════════════════════════
 * Page Slugs:
 *   FAQ      → sleep-apnea-faq
 *   About    → about-us
 *   Articles → articles
 *   News     → sleep-apnea-news
 *   Services → services
 *   Contact  → contact
 * ═══════════════════════════════════════════════════════ */



/* 11d. Open Graph + Twitter Cards */
add_action( 'wp_head', function () {
    if ( is_admin() ) {
        return;
    }
    $og_title = get_bloginfo( 'name' );
    $og_description = '';
    $og_image = 'https://sleepapneabd.com/wp-content/uploads/2024/05/logo.jpg';
    $og_url   = home_url( '/' );
    $og_type  = 'website';

    if ( is_singular() ) {
        $og_title = get_the_title();
        $og_url   = get_permalink();
        $og_type  = is_singular( 'post' ) ? 'article' : 'website';
        $og_description = has_excerpt() ? get_the_excerpt() : wp_trim_words( wp_strip_all_tags( get_post_field( 'post_content', get_the_ID() ) ), 25, '...' );
        if ( has_post_thumbnail() ) {
            $og_image = get_the_post_thumbnail_url( get_the_ID(), 'full' );
        }
    } elseif ( is_front_page() || is_home() ) {
        $og_description = 'ঢাকা স্লিপ রিসার্চ সেন্টার — স্লিপ অ্যাপনিয়া ডায়াগনসিস ও চিকিৎসা কেন্দ্র।';
    } elseif ( is_category() ) {
        $og_title       = single_cat_title( '', false ) . ' — DSRC';
        $og_description = category_description() ?: single_cat_title( '', false ) . ' সম্পর্কিত আর্টিকেল';
        $og_url         = get_category_link( get_queried_object_id() );
    }
    $og_description = mb_substr( trim( wp_strip_all_tags( $og_description ) ), 0, 160, 'UTF-8' );
    if ( empty( $og_title ) ) { return; }
    ?>
    <meta property="og:type" content="<?php echo esc_attr( $og_type ); ?>">
    <meta property="og:title" content="<?php echo esc_attr( $og_title ); ?>">
    <meta property="og:description" content="<?php echo esc_attr( $og_description ); ?>">
    <meta property="og:url" content="<?php echo esc_url( $og_url ); ?>">
    <meta property="og:image" content="<?php echo esc_url( $og_image ); ?>">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta property="og:site_name" content="Dhaka Sleep Research Centre">
    <meta property="og:locale" content="bn_BD">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?php echo esc_attr( $og_title ); ?>">
    <meta name="twitter:description" content="<?php echo esc_attr( $og_description ); ?>">
    <meta name="twitter:image" content="<?php echo esc_url( $og_image ); ?>">
    <?php
    if ( is_singular( 'post' ) ) {
        echo '<meta property="article:published_time" content="' . esc_attr( get_the_date( 'c' ) ) . '">' . "\n";
        echo '<meta property="article:modified_time" content="' . esc_attr( get_the_modified_date( 'c' ) ) . '">' . "\n";
        echo '<meta property="article:author" content="' . esc_attr( get_the_author() ) . '">' . "\n";
        $cats = get_the_category();
        if ( ! empty( $cats ) ) { echo '<meta property="article:section" content="' . esc_attr( $cats[0]->name ) . '">' . "\n"; }
        $tags = get_the_tags();
        if ( ! empty( $tags ) ) { foreach ( $tags as $tag ) { echo '<meta property="article:tag" content="' . esc_attr( $tag->name ) . '">' . "\n"; } }
    }
}, 4 );

/* 11e. Hreflang */
add_action( 'wp_head', function () {
    if ( is_admin() ) { return; }
    $url = '';
    if ( is_singular() || is_page() ) { $url = get_permalink(); }
    elseif ( is_front_page() || is_home() ) { $url = home_url( '/' ); }
    elseif ( is_category() ) { $url = get_category_link( get_queried_object_id() ); }
    if ( ! empty( $url ) ) {
        echo '<link rel="alternate" href="' . esc_url( $url ) . '" hreflang="bn-BD">' . "\n";
        echo '<link rel="alternate" href="' . esc_url( $url ) . '" hreflang="bn">' . "\n";
        echo '<link rel="alternate" href="' . esc_url( $url ) . '" hreflang="x-default">' . "\n";
    }
}, 4 );

/* 11f. Article Schema */
add_action( 'wp_head', function () {
    if ( ! is_singular( 'post' ) ) { return; }
    $post_id      = get_the_ID();
    $post_content = get_post_field( 'post_content', $post_id );
    $excerpt      = has_excerpt( $post_id ) ? get_the_excerpt( $post_id ) : wp_trim_words( wp_strip_all_tags( $post_content ), 30, '...' );
    $image        = has_post_thumbnail( $post_id ) ? get_the_post_thumbnail_url( $post_id, 'full' ) : 'https://sleepapneabd.com/wp-content/uploads/2024/05/logo.jpg';
    $word_count   = mb_strlen( wp_strip_all_tags( $post_content ), 'UTF-8' );
    $categories   = array();
    $cats = get_the_category( $post_id );
    if ( ! empty( $cats ) ) { foreach ( $cats as $cat ) { $categories[] = $cat->name; } }
    $schema = array(
        '@context' => 'https://schema.org', '@type' => 'MedicalWebPage',
        'mainEntityOfPage' => array( '@type' => 'WebPage', '@id' => get_permalink( $post_id ) ),
        'headline' => get_the_title( $post_id ), 'description' => wp_strip_all_tags( $excerpt ),
        'image' => array( '@type' => 'ImageObject', 'url' => $image ),
        'datePublished' => get_the_date( 'c', $post_id ), 'dateModified' => get_the_modified_date( 'c', $post_id ),
        'inLanguage' => 'bn-BD', 'wordCount' => $word_count, 'timeRequired' => 'PT' . max( 1, ceil( $word_count / 200 ) ) . 'M',
        'author' => array( '@type' => 'Person', 'name' => get_the_author_meta( 'display_name', get_post_field( 'post_author', $post_id ) ), 'url' => home_url( '/' ) ),
        'publisher' => array( '@type' => 'Organization', 'name' => 'Dhaka Sleep Research Centre (DSRC)', 'url' => 'https://sleepapneabd.com', 'logo' => array( '@type' => 'ImageObject', 'url' => 'https://sleepapneabd.com/wp-content/uploads/2024/05/logo.jpg', 'width' => 200, 'height' => 200 ) ),
        'specialty' => 'Sleep Medicine',
    );
    if ( ! empty( $categories ) ) { $schema['articleSection'] = $categories; }
    echo '<script type="application/ld+json">' . wp_json_encode( $schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT ) . '</script>' . "\n";
}, 25 );

/* 11g. Breadcrumb Schema */
add_action( 'wp_head', function () {
    if ( is_front_page() || is_admin() ) { return; }
    $items = array(); $pos = 1;
    $items[] = array( '@type' => 'ListItem', 'position' => $pos++, 'name' => 'হোম', 'item' => home_url( '/' ) );
    if ( is_singular( 'post' ) ) {
        $cats = get_the_category();
        if ( ! empty( $cats ) ) { $items[] = array( '@type' => 'ListItem', 'position' => $pos++, 'name' => $cats[0]->name, 'item' => get_category_link( $cats[0]->term_id ) ); }
        $items[] = array( '@type' => 'ListItem', 'position' => $pos++, 'name' => get_the_title(), 'item' => get_permalink() );
    } elseif ( is_page() ) {
        $ancestors = array_reverse( get_post_ancestors( get_the_ID() ) );
        foreach ( $ancestors as $aid ) { $items[] = array( '@type' => 'ListItem', 'position' => $pos++, 'name' => get_the_title( $aid ), 'item' => get_permalink( $aid ) ); }
        $items[] = array( '@type' => 'ListItem', 'position' => $pos++, 'name' => get_the_title(), 'item' => get_permalink() );
    } elseif ( is_category() ) {
        $items[] = array( '@type' => 'ListItem', 'position' => $pos++, 'name' => single_cat_title( '', false ), 'item' => get_category_link( get_queried_object_id() ) );
    }
    if ( count( $items ) < 2 ) { return; }
    $schema = array( '@context' => 'https://schema.org', '@type' => 'BreadcrumbList', 'itemListElement' => $items );
    echo '<script type="application/ld+json">' . wp_json_encode( $schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . '</script>' . "\n";
}, 25 );

/* 11h. FAQ Schema */
add_action( 'wp_head', function () {
    if ( ! is_page( 'sleep-apnea-faq' ) ) {
        return;
    }

    $faqs = array(
        array( 'q' => 'স্লিপ অ্যাপনিয়া কি?', 'a' => 'স্লিপ অ্যাপনিয়া হলো ঘুমের সময় শ্বাসকষ্টের একটি রোগ যেখানে ঘুমন্ত অবস্থায় বারবার শ্বাস বন্ধ হয়ে যায় এবং আবার শুরু হয়।' ),
        array( 'q' => 'স্লিপ অ্যাপনিয়ার লক্ষণ কি কি?', 'a' => 'প্রধান লক্ষণ: জোরে নাক ডাকা, ঘুমের মধ্যে শ্বাস বন্ধ, সকালে মাথাব্যথা, দিনে অতিরিক্ত ঘুম ঘুম ভাব, মনোযোগের অভাব।' ),
        array( 'q' => 'স্লিপ অ্যাপনিয়ার চিকিৎসা কি?', 'a' => 'CPAP মেশিন সবচেয়ে কার্যকর। এছাড়া ওরাল অ্যাপ্লায়েন্স, জীবনযাত্রার পরিবর্তন, ওজন কমানো এবং কিছু ক্ষেত্রে সার্জারি প্রয়োজন।' ),
        array( 'q' => 'স্লিপ টেস্ট (PSG) কি?', 'a' => 'পলিসমনোগ্রাফি (PSG) হলো রাতব্যাপী ঘুমের পরীক্ষা যেখানে শ্বাস, হৃদস্পন্দন, মস্তিষ্কের তরঙ্গ এবং অক্সিজেন মাত্রা পর্যবেক্ষণ করা হয়।' ),
        array( 'q' => 'স্লিপ অ্যাপনিয়া কি বিপজ্জনক?', 'a' => 'হ্যাঁ, চিকিৎসা না করলে উচ্চ রক্তচাপ, হৃদরোগ, স্ট্রোক, ডায়াবেটিস এর ঝুঁকি বাড়ে।' ),
        array( 'q' => 'CPAP মেশিন কি?', 'a' => 'CPAP মেশিন ঘুমের সময় মাস্কের মাধ্যমে বায়ুচাপ দিয়ে শ্বাসনালী খোলা রাখে, যাতে শ্বাস বন্ধ না হয়।' ),
        array( 'q' => 'বাংলাদেশে স্লিপ টেস্ট কোথায় করা যায়?', 'a' => 'ঢাকা স্লিপ রিসার্চ সেন্টার (DSRC), আফতাবনগরে বিশেষজ্ঞ তত্ত্বাবধানে স্লিপ টেস্ট করা হয়। ফোন: +8801708031201' ),
        array( 'q' => 'শিশুদের কি স্লিপ অ্যাপনিয়া হতে পারে?', 'a' => 'হ্যাঁ। টনসিল বড় হওয়া, অ্যাডেনয়েড এবং স্থূলতা শিশুদের স্লিপ অ্যাপনিয়ার প্রধান কারণ।' ),
    );

    $main_entity = array();
    foreach ( $faqs as $faq ) {
        $main_entity[] = array(
            '@type'          => 'Question',
            'name'           => $faq['q'],
            'acceptedAnswer' => array(
                '@type' => 'Answer',
                'text'  => $faq['a'],
            ),
        );
    }

    $schema = array(
        '@context'   => 'https://schema.org',
        '@type'      => 'FAQPage',
        'mainEntity' => $main_entity,
    );

    echo '<script type="application/ld+json">' . wp_json_encode( $schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT ) . '</script>' . "\n";

}, 25 );


/* 11i. Service Schema */
add_action( 'wp_head', function () {
    if ( ! is_page( 'services' ) ) {
        return;
    }
    ?>
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "MedicalClinic",
        "name": "Dhaka Sleep Research Centre (DSRC)",
        "url": "https://sleepapneabd.com/services/",
        "medicalSpecialty": "SleepMedicine",
        "availableService": [
            {"@type": "MedicalProcedure", "name": "Sleep Test (Polysomnography - PSG)", "description": "রাতব্যাপী ঘুমের পরীক্ষা বিশেষজ্ঞ তত্ত্বাবধানে"},
            {"@type": "MedicalTherapy", "name": "CPAP Therapy", "description": "স্লিপ অ্যাপনিয়ার জন্য CPAP মেশিন থেরাপি"},
            {"@type": "MedicalTherapy", "name": "Oral Appliance Therapy", "description": "মুখের যন্ত্র দিয়ে হালকা স্লিপ অ্যাপনিয়ার চিকিৎসা"},
            {"@type": "MedicalTest", "name": "Home Sleep Testing", "description": "বাড়িতে বসে স্লিপ টেস্ট করার সুবিধা"},
            {"@type": "MedicalProcedure", "name": "Pediatric Sleep Apnea Diagnosis", "description": "শিশুদের স্লিপ অ্যাপনিয়া নির্ণয় ও চিকিৎসা"}
        ]
    }
    </script>
    <?php
}, 25 );


/* 11j. Sitelinks Search Box */
add_action( 'wp_head', function () {
    if ( ! is_front_page() ) {
        return;
    }
    ?>
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "WebSite",
        "name": "Dhaka Sleep Research Centre",
        "alternateName": ["DSRC", "ঢাকা স্লিপ রিসার্চ সেন্টার", "স্লিপ অ্যাপনিয়া বিডি"],
        "url": "https://sleepapneabd.com",
        "inLanguage": "bn-BD",
        "potentialAction": {
            "@type": "SearchAction",
            "target": {
                "@type": "EntryPoint",
                "urlTemplate": "https://sleepapneabd.com/?s={search_term_string}"
            },
            "query-input": "required name=search_term_string"
        }
    }
    </script>
    <?php
}, 25 );


/* 11k. Contact Page Schema */
add_action( 'wp_head', function () {
    if ( ! is_page( 'contact' ) ) {
        return;
    }
    ?>
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "ContactPage",
        "name": "যোগাযোগ — ঢাকা স্লিপ রিসার্চ সেন্টার",
        "url": "https://sleepapneabd.com/contact/",
        "mainEntity": {
            "@type": "MedicalClinic",
            "name": "Dhaka Sleep Research Centre",
            "telephone": "+8801708031201",
            "email": "sleepapneahub@gmail.com",
            "address": {
                "@type": "PostalAddress",
                "streetAddress": "House #25, Lake View Road, Aftabnagar (Near Meena Bazar)",
                "addressLocality": "Dhaka",
                "postalCode": "1219",
                "addressCountry": "BD"
            },
            "openingHoursSpecification": {
                "@type": "OpeningHoursSpecification",
                "dayOfWeek": ["Monday","Tuesday","Wednesday","Thursday","Friday","Saturday","Sunday"],
                "opens": "09:00",
                "closes": "17:00"
            }
        }
    }
    </script>
    <?php
}, 25 );


/* 11l. About Page Schema */
add_action( 'wp_head', function () {
    if ( ! is_page( 'about-us' ) ) {
        return;
    }
    ?>
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "AboutPage",
        "name": "আমাদের সম্পর্কে — ঢাকা স্লিপ রিসার্চ সেন্টার",
        "url": "https://sleepapneabd.com/about-us/",
        "mainEntity": {
            "@type": "MedicalClinic",
            "name": "Dhaka Sleep Research Centre (DSRC)",
            "url": "https://sleepapneabd.com",
            "medicalSpecialty": "SleepMedicine",
            "founder": {
                "@type": "Person",
                "name": "Prof. Dr. A.K.M. Mosharraf Hossain",
                "jobTitle": "Pioneer Sleep Apnea Specialist",
                "description": "MBBS, FCCP, FRCP, Ph.D., FCPS. Former Chairman, Respiratory Medicine Dept., BSMMU."
            }
        }
    }
    </script>
    <?php
}, 25 );


/* 11m. Auto Alt Text */
add_filter( 'the_content', function ( $content ) {
    if ( is_admin() || is_feed() ) {
        return $content;
    }

    $content = preg_replace_callback(
        '/<img((?:(?!alt=)[^>])*?)(\s*\/?)>/i',
        function ( $matches ) {
            $title = get_the_title();
            return '<img' . $matches[1] . ' alt="' . esc_attr( $title ) . '"' . $matches[2] . '>';
        },
        $content
    );

    $content = preg_replace_callback(
        '/<img([^>]*?)alt=["\']\s*["\']([^>]*?)>/i',
        function ( $matches ) {
            $title = get_the_title();
            return '<img' . $matches[1] . 'alt="' . esc_attr( $title ) . '"' . $matches[2] . '>';
        },
        $content
    );

    return $content;
}, 20 );


/* 11n. XML Sitemap + HSTS + Last-Modified + Ping Google */
add_action( 'wp_head', function () {
    if ( ! is_admin() ) {
        echo '<link rel="sitemap" type="application/xml" title="Sitemap" href="' . esc_url( home_url( '/sitemap.xml' ) ) . '">' . "\n";
    }
}, 5 );

add_action( 'send_headers', function () {
    if ( ! is_admin() ) {
        header( 'Strict-Transport-Security: max-age=31536000; includeSubDomains; preload' );
    }
}, 5 );

add_action( 'template_redirect', function () {
    if ( is_singular() && ! is_admin() ) {
        $post_modified = get_the_modified_date( 'D, d M Y H:i:s' );
        if ( ! empty( $post_modified ) ) {
            header( 'Last-Modified: ' . $post_modified . ' GMT' );
        }
    }
} );

add_action( 'publish_post', function ( $post_id ) {
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
        return;
    }
    if ( wp_is_post_revision( $post_id ) ) {
        return;
    }
    wp_remote_get(
        'https://www.google.com/ping?sitemap=' . urlencode( home_url( '/sitemap.xml' ) ),
        array( 'timeout' => 5, 'blocking' => false )
    );
}, 20 );


/* 11o. Pagination SEO */
add_action( 'wp_head', function () {
    if ( is_admin() || ! is_singular() ) {
        return;
    }
    global $page, $numpages;
    if ( $numpages <= 1 ) {
        return;
    }
    $post_url = get_permalink();
    if ( $page > 1 ) {
        $prev_page = ( $page === 2 ) ? $post_url : trailingslashit( $post_url ) . ( $page - 1 ) . '/';
        echo '<link rel="prev" href="' . esc_url( $prev_page ) . '">' . "\n";
    }
    if ( $page < $numpages ) {
        $next_page = trailingslashit( $post_url ) . ( $page + 1 ) . '/';
        echo '<link rel="next" href="' . esc_url( $next_page ) . '">' . "\n";
    }
}, 5 );


/* 11q. Geo Meta Tags */
add_action( 'wp_head', function () {
    if ( is_admin() ) {
        return;
    }
    if ( ! is_front_page() && ! is_page( 'contact' ) && ! is_page( 'services' ) ) {
        return;
    }
    ?>
    <meta name="geo.region" content="BD-13">
    <meta name="geo.placename" content="Dhaka, Bangladesh">
    <meta name="geo.position" content="23.771120;90.443916">
    <meta name="ICBM" content="23.771120, 90.443916">
    <meta name="DC.title" content="Dhaka Sleep Research Centre — Sleep Apnea Treatment Bangladesh">
    <?php
}, 3 );


/* 11r. Language & Content Type Meta */
add_action( 'wp_head', function () {
    if ( is_admin() ) {
        return;
    }
    ?>
    <meta http-equiv="content-language" content="bn-BD">
    <meta name="language" content="Bengali">
    <meta name="author" content="Dhaka Sleep Research Centre (DSRC)">
    <meta name="publisher" content="Dhaka Sleep Research Centre">
    <meta name="copyright" content="<?php echo esc_attr( '© ' . date( 'Y' ) . ' Dhaka Sleep Research Centre. All rights reserved.' ); ?>">
    <meta name="coverage" content="Bangladesh">
    <meta name="distribution" content="global">
    <meta name="rating" content="general">
    <meta name="revisit-after" content="3 days">
    <?php
}, 2 );


/* ═══════════════════════════════════════════════════════
 * NOTE: Add to wp-config.php (NOT here):
 *     define( 'WP_POST_REVISIONS', 5 );
 * ═══════════════════════════════════════════════════════ */
