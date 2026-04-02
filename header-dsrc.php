<?php
/**
 * Custom DSRC Header — SEO + OG + Navigation
 * VERSION: 4.0 — Security Hardened & Performance Optimized
 *
 * @package DSRC
 * 
 * Fixes Applied:
 * - Sanitized $_SERVER input
 * - Fixed double <main> issue
 * - Hardcoded URLs → dynamic
 * - Schema uses wp_json_encode
 * - Better active menu detection
 * - OG image URL fixed
 * - HTML lang attribute from WP
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/* ═══════════════════════════════════════════════════════
 * SETUP: Variables & SEO Data
 * ═══════════════════════════════════════════════════════ */

// Sanitized slug detection
$slug = '';
if ( isset( $_SERVER['REQUEST_URI'] ) ) {
    $raw_uri = wp_unslash( $_SERVER['REQUEST_URI'] );
    $path    = parse_url( $raw_uri, PHP_URL_PATH );
    $slug    = trim( sanitize_text_field( $path ), '/' );
}

// Logo URL (defined once, used everywhere)
$dsrc_logo_url = esc_url( home_url( '/wp-content/uploads/2024/05/logo.jpg' ) );
$dsrc_site_url = esc_url( home_url( '/' ) );

// Active menu helper
function dsrc_is_active_page( $current_slug, $page_slug ) {
    if ( empty( $current_slug ) && empty( $page_slug ) ) {
        return true; // Homepage
    }
    return ( $current_slug === $page_slug || strpos( $current_slug, $page_slug . '/' ) === 0 );
}

// Default SEO values
$seo_title = 'Sleep Apnea | Dhaka Sleep Research Centre (DSRC)';
$seo_desc  = 'Dhaka Sleep Research Centre offers expert Sleep Apnea diagnosis, sleep study testing, and CPAP/BiPAP treatment in Bangladesh.';
$seo_keys  = 'Sleep Apnea Bangladesh, Sleep Study Dhaka, Snoring Treatment Bangladesh, CPAP Therapy Bangladesh, স্লিপ অ্যাপনিয়া চিকিৎসা বাংলাদেশ, ঘুমের সময় শ্বাস বন্ধ হওয়া, নাক ডাকা সমস্যার চিকিৎসা, স্লিপ স্টাডি পরীক্ষা ঢাকা';
$seo_image = home_url( '/wp-content/uploads/2024/05/logo.jpg' );

// Canonical URL
$canonical_url = is_singular() ? get_permalink() : home_url( '/' );

/* ═══════════════════════════════════════════════════════
 * SEO: Single Post Meta
 * ═══════════════════════════════════════════════════════ */
if ( is_single() ) {

    $post_id = get_queried_object_id();
    $p       = get_post( $post_id );

    // Title
    $seo_title = ( $p && ! empty( $p->post_title ) )
        ? $p->post_title . ' | Dhaka Sleep Research Centre'
        : 'Dhaka Sleep Research Centre (DSRC)';

    // Description: Custom field → Excerpt → Content
    $custom_desc = trim( (string) get_post_meta( $post_id, 'meta_description', true ) );
    if ( ! empty( $custom_desc ) ) {
        $seo_desc = $custom_desc;
    } else {
        $raw_excerpt = $p ? $p->post_excerpt : '';
        if ( ! empty( $raw_excerpt ) ) {
            $seo_desc = wp_trim_words( wp_strip_all_tags( $raw_excerpt ), 25, '...' );
        } else {
            $raw_content = $p ? $p->post_content : '';
            $seo_desc = wp_trim_words( wp_strip_all_tags( $raw_content ), 25, '...' );
        }
    }

    // Keywords: Custom field → Tags → Post Tags CF → Defaults
    $custom_keys = trim( (string) get_post_meta( $post_id, 'meta_keywords', true ) );
    if ( empty( $custom_keys ) ) {
        $wp_tags = wp_get_post_tags( $post_id, array( 'fields' => 'names' ) );
        if ( ! empty( $wp_tags ) ) {
            $custom_keys = implode( ', ', $wp_tags );
        }
    }
    if ( empty( $custom_keys ) ) {
        $post_tags_cf = trim( (string) get_post_meta( $post_id, 'post_tags', true ) );
        if ( ! empty( $post_tags_cf ) ) {
            $custom_keys = $post_tags_cf;
        }
    }
    $seo_keys = ! empty( $custom_keys )
        ? $custom_keys
        : 'Sleep Apnea, স্লিপ অ্যাপনিয়া, Snoring, ঘুমের সমস্যা, DSRC Article';

    // Image
    $thumb     = get_the_post_thumbnail_url( $post_id, 'large' );
    $seo_image = $thumb ? $thumb : $seo_image;

    // Canonical
    $canonical_url = get_permalink( $post_id );

/* ═══════════════════════════════════════════════════════
 * SEO: Static Pages
 * ═══════════════════════════════════════════════════════ */
} else {

    switch ( $slug ) {
        case '':
        case 'home':
            $seo_title = 'Sleep Apnea Doctor | Treatment in Bangladesh - DSRC';
            $seo_desc  = 'Best Sleep Apnea doctor in Bangladesh. Get Sleep study (polysomnography test), CPAP/BiPAP, Snoring (নাক ডাকা) Insomnia (ঘুমের সমস্যা) treatment at DSRC, Dhaka.';
            break;

        case 'about-us':
            $seo_title = 'Sleep Study Test Center | Sleep Apnea Doctor - DSRC';
            $seo_desc  = 'Dhaka Sleep Research Centre (DSRC) — Bangladesh\'s first Sleep Apnea test & treatment centre. Led by pioneer specialist Prof. Dr. Mosharraf Hossain. Book now.';
            $seo_keys .= ', About Dhaka Sleep Research Centre, Sleep Apnea Experts Bangladesh';
            break;

        case 'services':
            $seo_title = 'Sleep Apnea Treatment | Sleep Study Services - DSRC';
            $seo_desc  = 'We offer sleep study (polysomnography test), snoring evaluation, CPAP/BiPAP setup, and long-term follow-up services for Sleep Apnea in Bangladesh.';
            $seo_keys .= ', Sleep Study Bangladesh, CPAP Service Dhaka, Sleep Test Cost Bangladesh';
            break;

        case 'articles':
            $seo_title = 'Sleep Apnea Articles | Snoring & Insomnia - DSRC';
            $seo_desc  = 'Sleep Apnea, নাক ডাকা (snoring), insomnia (ঘুমের সমস্যা) ও CPAP treatment নিয়ে DSRC specialist দের লিখা expert articles পড়ুন।';
            $seo_keys .= ', Sleep Apnea articles Bangladesh, ঘুমের সমস্যা, স্লিপ অ্যাপনিয়া তথ্য';
            break;

        case 'sleep-apnea-news':
            $seo_title = 'Sleep Apnea News | Journal & Research Updates - DSRC';
            $seo_desc  = 'Stay updated with the latest sleep apnea news, research findings, treatment breakthroughs, and expert insights from around the world.';
            break;

        case 'sleep-apnea-faq':
            $seo_title = 'Sleep Apnea FAQ | Symptoms, Causes & Treatment - DSRC';
            $seo_desc  = 'Find answers to common Sleep Apnea questions about symptoms, causes, diagnosis, sleep test, and treatment options. Expert guidance from DSRC specialists.';
            break;

        case 'contact':
            $seo_title = 'Contact Sleep Study Test Centre Dhaka - DSRC';
            $seo_desc  = 'Book your sleep apnea polysomnography test, or CPAP therapy appointment at Dhaka Sleep Research Centre in Dhaka, Bangladesh.';
            $seo_keys .= ', Contact Sleep Apnea Centre, Appointment Sleep Study Dhaka';
            break;
    }

    if ( is_page() ) {
        $canonical_url = get_permalink();
    } else {
        $canonical_url = home_url( '/' . $slug . '/' );
    }
}
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo( 'charset' ); ?>" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />

    <!-- ═══════════════════════════════════════
         SEO Meta Tags
         ═══════════════════════════════════════ -->
    <title><?php echo esc_html( $seo_title ); ?></title>
    <meta name="description" content="<?php echo esc_attr( $seo_desc ); ?>" />
    <meta name="keywords" content="<?php echo esc_attr( $seo_keys ); ?>" />
    <meta name="robots" content="index, follow, max-snippet:-1, max-image-preview:large, max-video-preview:-1" />
    <link rel="canonical" href="<?php echo esc_url( $canonical_url ); ?>" />

    <!-- ═══════════════════════════════════════
         Preconnect & DNS Prefetch
         (BEFORE any font/resource requests)
         ═══════════════════════════════════════ -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="dns-prefetch" href="//fonts.googleapis.com">
    <link rel="dns-prefetch" href="//fonts.gstatic.com">

    <!-- ═══════════════════════════════════════
         Google Font — Async (NOT render-blocking)
         ═══════════════════════════════════════ -->
    <link rel="preload" as="style"
          href="https://fonts.googleapis.com/css2?family=Noto+Sans+Bengali:wght@400;600;700&display=swap"
          onload="this.onload=null;this.rel='stylesheet'">
    <noscript>
        <link rel="stylesheet"
              href="https://fonts.googleapis.com/css2?family=Noto+Sans+Bengali:wght@400;600;700&display=swap">
    </noscript>

    <!-- ═══════════════════════════════════════
         Global Stylesheet — Async
         ═══════════════════════════════════════ -->
    <link rel="preload" as="style"
          href="<?php echo esc_url( home_url( '/css/global-styles.css?v=1.3' ) ); ?>"
          onload="this.onload=null;this.rel='stylesheet'">
    <noscript>
        <link rel="stylesheet" href="<?php echo esc_url( home_url( '/css/global-styles.css?v=1.3' ) ); ?>">
    </noscript>

    <!-- ═══════════════════════════════════════════════════
         CRITICAL CSS — Inlined (Tailwind Replacement)
         Only utility classes actually used across site
         ═══════════════════════════════════════════════════ -->
    <style id="critical-css">
        /* ─── RESET & BASE ─── */
        *,*::before,*::after{box-sizing:border-box}
        html,body{margin:0;padding:0}
        :root{--primary:#0f172a;--secondary:#10b4b2;--light-bg:#f4f7fb}
        body{
            background:var(--light-bg);
            font-family:system-ui,-apple-system,'Noto Sans Bengali',Segoe UI,Roboto,Helvetica,Arial,sans-serif;
            font-size:1.3rem;line-height:1.9;color:#0f172a
        }
        main,#main,.site-content{margin-top:0!important;padding-top:0!important}
        img{max-width:100%;height:auto}
        a{color:inherit}
        svg{display:inline-block;vertical-align:middle}

        /* ─── DISPLAY ─── */
        .flex{display:flex}
        .inline-flex{display:inline-flex}
        .inline-block{display:inline-block}
        .inline{display:inline}
        .block{display:block}
        .hidden{display:none}
        .grid{display:grid}
        .contents{display:contents}
        .table{display:table}

        /* ─── FLEX ─── */
        .flex-col{flex-direction:column}
        .flex-row{flex-direction:row}
        .flex-wrap{flex-wrap:wrap}
        .flex-nowrap{flex-wrap:nowrap}
        .flex-1{flex:1 1 0%}
        .flex-auto{flex:1 1 auto}
        .flex-none{flex:none}
        .flex-shrink-0{flex-shrink:0}
        .flex-grow{flex-grow:1}
        .items-start{align-items:flex-start}
        .items-center{align-items:center}
        .items-end{align-items:flex-end}
        .items-stretch{align-items:stretch}
        .items-baseline{align-items:baseline}
        .justify-start{justify-content:flex-start}
        .justify-center{justify-content:center}
        .justify-end{justify-content:flex-end}
        .justify-between{justify-content:space-between}
        .justify-around{justify-content:space-around}
        .justify-evenly{justify-content:space-evenly}
        .self-start{align-self:flex-start}
        .self-center{align-self:center}
        .self-end{align-self:flex-end}
        .self-auto{align-self:auto}
        .order-1{order:1}.order-2{order:2}.order-first{order:-9999}.order-last{order:9999}

        /* ─── GRID ─── */
        .grid-cols-1{grid-template-columns:repeat(1,minmax(0,1fr))}
        .grid-cols-2{grid-template-columns:repeat(2,minmax(0,1fr))}
        .grid-cols-3{grid-template-columns:repeat(3,minmax(0,1fr))}
        .grid-cols-4{grid-template-columns:repeat(4,minmax(0,1fr))}
        .grid-cols-5{grid-template-columns:repeat(5,minmax(0,1fr))}
        .grid-cols-6{grid-template-columns:repeat(6,minmax(0,1fr))}
        .grid-cols-12{grid-template-columns:repeat(12,minmax(0,1fr))}
        .col-span-1{grid-column:span 1/span 1}
        .col-span-2{grid-column:span 2/span 2}
        .col-span-3{grid-column:span 3/span 3}
        .col-span-4{grid-column:span 4/span 4}
        .col-span-6{grid-column:span 6/span 6}
        .col-span-full{grid-column:1/-1}
        .grid-rows-1{grid-template-rows:repeat(1,minmax(0,1fr))}
        .grid-rows-2{grid-template-rows:repeat(2,minmax(0,1fr))}
        .row-span-2{grid-row:span 2/span 2}

        /* ─── GAP ─── */
        .gap-0{gap:0}.gap-1{gap:.25rem}.gap-2{gap:.5rem}
        .gap-3{gap:.75rem}.gap-4{gap:1rem}.gap-5{gap:1.25rem}
        .gap-6{gap:1.5rem}.gap-8{gap:2rem}.gap-10{gap:2.5rem}.gap-12{gap:3rem}
        .gap-x-2{column-gap:.5rem}.gap-x-4{column-gap:1rem}.gap-x-6{column-gap:1.5rem}
        .gap-y-2{row-gap:.5rem}.gap-y-4{row-gap:1rem}.gap-y-6{row-gap:1.5rem}

        /* ─── SPACING: PADDING ─── */
        .p-0{padding:0}.p-1{padding:.25rem}.p-2{padding:.5rem}
        .p-3{padding:.75rem}.p-4{padding:1rem}.p-5{padding:1.25rem}
        .p-6{padding:1.5rem}.p-8{padding:2rem}.p-10{padding:2.5rem}.p-12{padding:3rem}
        .px-0{padding-left:0;padding-right:0}
        .px-1{padding-left:.25rem;padding-right:.25rem}
        .px-2{padding-left:.5rem;padding-right:.5rem}
        .px-3{padding-left:.75rem;padding-right:.75rem}
        .px-4{padding-left:1rem;padding-right:1rem}
        .px-5{padding-left:1.25rem;padding-right:1.25rem}
        .px-6{padding-left:1.5rem;padding-right:1.5rem}
        .px-8{padding-left:2rem;padding-right:2rem}
        .px-10{padding-left:2.5rem;padding-right:2.5rem}
        .px-12{padding-left:3rem;padding-right:3rem}
        .py-0{padding-top:0;padding-bottom:0}
        .py-0\.5{padding-top:.125rem;padding-bottom:.125rem}
        .py-1{padding-top:.25rem;padding-bottom:.25rem}
        .py-2{padding-top:.5rem;padding-bottom:.5rem}
        .py-3{padding-top:.75rem;padding-bottom:.75rem}
        .py-4{padding-top:1rem;padding-bottom:1rem}
        .py-5{padding-top:1.25rem;padding-bottom:1.25rem}
        .py-6{padding-top:1.5rem;padding-bottom:1.5rem}
        .py-8{padding-top:2rem;padding-bottom:2rem}
        .py-10{padding-top:2.5rem;padding-bottom:2.5rem}
        .py-12{padding-top:3rem;padding-bottom:3rem}
        .py-16{padding-top:4rem;padding-bottom:4rem}
        .py-20{padding-top:5rem;padding-bottom:5rem}
        .pt-0{padding-top:0}.pt-1{padding-top:.25rem}.pt-2{padding-top:.5rem}
        .pt-3{padding-top:.75rem}.pt-4{padding-top:1rem}.pt-5{padding-top:1.25rem}
        .pt-6{padding-top:1.5rem}.pt-8{padding-top:2rem}.pt-10{padding-top:2.5rem}
        .pt-12{padding-top:3rem}.pt-16{padding-top:4rem}
        .pb-0{padding-bottom:0}.pb-1{padding-bottom:.25rem}.pb-2{padding-bottom:.5rem}
        .pb-3{padding-bottom:.75rem}.pb-4{padding-bottom:1rem}.pb-5{padding-bottom:1.25rem}
        .pb-6{padding-bottom:1.5rem}.pb-8{padding-bottom:2rem}.pb-10{padding-bottom:2.5rem}
        .pb-12{padding-bottom:3rem}
        .pl-0{padding-left:0}.pl-2{padding-left:.5rem}
        .pl-4{padding-left:1rem}.pl-6{padding-left:1.5rem}.pl-8{padding-left:2rem}
        .pr-0{padding-right:0}.pr-2{padding-right:.5rem}
        .pr-4{padding-right:1rem}.pr-6{padding-right:1.5rem}

        /* ─── SPACING: MARGIN ─── */
        .m-0{margin:0}.m-1{margin:.25rem}.m-2{margin:.5rem}
        .m-4{margin:1rem}.m-auto{margin:auto}
        .mx-auto{margin-left:auto;margin-right:auto}
        .mx-2{margin-left:.5rem;margin-right:.5rem}
        .mx-4{margin-left:1rem;margin-right:1rem}
        .my-0{margin-top:0;margin-bottom:0}
        .my-2{margin-top:.5rem;margin-bottom:.5rem}
        .my-3{margin-top:.75rem;margin-bottom:.75rem}
        .my-4{margin-top:1rem;margin-bottom:1rem}
        .my-6{margin-top:1.5rem;margin-bottom:1.5rem}
        .my-8{margin-top:2rem;margin-bottom:2rem}
        .mt-0{margin-top:0}.mt-1{margin-top:.25rem}.mt-2{margin-top:.5rem}
        .mt-3{margin-top:.75rem}.mt-4{margin-top:1rem}.mt-5{margin-top:1.25rem}
        .mt-6{margin-top:1.5rem}.mt-8{margin-top:2rem}.mt-10{margin-top:2.5rem}
        .mt-12{margin-top:3rem}.mt-16{margin-top:4rem}.mt-20{margin-top:5rem}
        .mb-0{margin-bottom:0}.mb-1{margin-bottom:.25rem}.mb-2{margin-bottom:.5rem}
        .mb-3{margin-bottom:.75rem}.mb-4{margin-bottom:1rem}.mb-5{margin-bottom:1.25rem}
        .mb-6{margin-bottom:1.5rem}.mb-8{margin-bottom:2rem}.mb-10{margin-bottom:2.5rem}
        .mb-12{margin-bottom:3rem}.mb-16{margin-bottom:4rem}
        .ml-0{margin-left:0}.ml-1{margin-left:.25rem}.ml-2{margin-left:.5rem}
        .ml-3{margin-left:.75rem}.ml-4{margin-left:1rem}.ml-auto{margin-left:auto}
        .mr-0{margin-right:0}.mr-1{margin-right:.25rem}.mr-2{margin-right:.5rem}
        .mr-3{margin-right:.75rem}.mr-4{margin-right:1rem}.mr-auto{margin-right:auto}
        .-mt-1{margin-top:-.25rem}.-mt-2{margin-top:-.5rem}

        /* ─── SPACE BETWEEN ─── */
        .space-x-1>*+*{margin-left:.25rem}
        .space-x-2>*+*{margin-left:.5rem}
        .space-x-3>*+*{margin-left:.75rem}
        .space-x-4>*+*{margin-left:1rem}
        .space-x-6>*+*{margin-left:1.5rem}
        .space-y-1>*+*{margin-top:.25rem}
        .space-y-2>*+*{margin-top:.5rem}
        .space-y-3>*+*{margin-top:.75rem}
        .space-y-4>*+*{margin-top:1rem}
        .space-y-6>*+*{margin-top:1.5rem}
        .space-y-8>*+*{margin-top:2rem}

        /* ─── SIZING ─── */
        .w-0{width:0}.w-1{width:.25rem}.w-2{width:.5rem}
        .w-3{width:.75rem}.w-4{width:1rem}.w-5{width:1.25rem}
        .w-6{width:1.5rem}.w-8{width:2rem}.w-10{width:2.5rem}
        .w-12{width:3rem}.w-16{width:4rem}.w-20{width:5rem}
        .w-24{width:6rem}.w-32{width:8rem}.w-40{width:10rem}
        .w-48{width:12rem}.w-56{width:14rem}.w-64{width:16rem}
        .w-72{width:18rem}.w-80{width:20rem}.w-96{width:24rem}
        .w-full{width:100%}.w-screen{width:100vw}
        .w-auto{width:auto}.w-fit{width:fit-content}
        .w-1\/2{width:50%}.w-1\/3{width:33.333%}.w-2\/3{width:66.667%}
        .w-1\/4{width:25%}.w-3\/4{width:75%}
        .min-w-0{min-width:0}.min-w-full{min-width:100%}
        .max-w-xs{max-width:20rem}.max-w-sm{max-width:24rem}
        .max-w-md{max-width:28rem}.max-w-lg{max-width:32rem}
        .max-w-xl{max-width:36rem}.max-w-2xl{max-width:42rem}
        .max-w-3xl{max-width:48rem}.max-w-4xl{max-width:56rem}
        .max-w-5xl{max-width:64rem}.max-w-6xl{max-width:72rem}
        .max-w-7xl{max-width:80rem}
        .max-w-full{max-width:100%}.max-w-screen-xl{max-width:1280px}
        .max-w-prose{max-width:65ch}.max-w-none{max-width:none}
        .h-0{height:0}.h-1{height:.25rem}.h-2{height:.5rem}
        .h-3{height:.75rem}.h-4{height:1rem}.h-5{height:1.25rem}
        .h-6{height:1.5rem}.h-8{height:2rem}.h-10{height:2.5rem}
        .h-12{height:3rem}.h-16{height:4rem}.h-20{height:5rem}
        .h-24{height:6rem}.h-32{height:8rem}.h-40{height:10rem}
        .h-48{height:12rem}.h-56{height:14rem}.h-64{height:16rem}
        .h-px{height:1px}
        .h-full{height:100%}.h-screen{height:100vh}.h-auto{height:auto}
        .min-h-0{min-height:0}.min-h-full{min-height:100%}.min-h-screen{min-height:100vh}
        .aspect-video{aspect-ratio:16/9}.aspect-square{aspect-ratio:1/1}
        .aspect-\[16\/9\]{aspect-ratio:16/9}
        .aspect-\[4\/3\]{aspect-ratio:4/3}

        /* ─── TYPOGRAPHY ─── */
        .font-sans{font-family:system-ui,-apple-system,'Noto Sans Bengali',sans-serif}
        .font-serif{font-family:Georgia,Cambria,serif}
        .font-mono{font-family:ui-monospace,monospace}
        .text-xs{font-size:.75rem;line-height:1rem}
        .text-sm{font-size:.875rem;line-height:1.25rem}
        .text-base{font-size:1rem;line-height:1.5rem}
        .text-lg{font-size:1.125rem;line-height:1.75rem}
        .text-xl{font-size:1.25rem;line-height:1.75rem}
        .text-2xl{font-size:1.5rem;line-height:2rem}
        .text-3xl{font-size:1.875rem;line-height:2.25rem}
        .text-4xl{font-size:2.25rem;line-height:2.5rem}
        .text-5xl{font-size:3rem;line-height:1}
        .text-6xl{font-size:3.75rem;line-height:1}
        .text-7xl{font-size:4.5rem;line-height:1}
        .text-8xl{font-size:6rem;line-height:1}
        .font-thin{font-weight:100}.font-light{font-weight:300}
        .font-normal{font-weight:400}.font-medium{font-weight:500}
        .font-semibold{font-weight:600}.font-bold{font-weight:700}
        .font-extrabold{font-weight:800}.font-black{font-weight:900}
        .italic{font-style:italic}.not-italic{font-style:normal}
        .text-left{text-align:left}.text-center{text-align:center}
        .text-right{text-align:right}.text-justify{text-align:justify}
        .uppercase{text-transform:uppercase}.lowercase{text-transform:lowercase}
        .capitalize{text-transform:capitalize}.normal-case{text-transform:none}
        .underline{text-decoration:underline}.no-underline{text-decoration:none}
        .line-through{text-decoration:line-through}
        .leading-none{line-height:1}.leading-tight{line-height:1.25}
        .leading-snug{line-height:1.375}.leading-normal{line-height:1.5}
        .leading-relaxed{line-height:1.625}.leading-loose{line-height:2}
        .tracking-tighter{letter-spacing:-.05em}.tracking-tight{letter-spacing:-.025em}
        .tracking-normal{letter-spacing:0}.tracking-wide{letter-spacing:.025em}
        .tracking-wider{letter-spacing:.05em}.tracking-widest{letter-spacing:.1em}
        .truncate{overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
        .line-clamp-1{display:-webkit-box;-webkit-line-clamp:1;-webkit-box-orient:vertical;overflow:hidden}
        .line-clamp-2{display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden}
        .line-clamp-3{display:-webkit-box;-webkit-line-clamp:3;-webkit-box-orient:vertical;overflow:hidden}
        .whitespace-nowrap{white-space:nowrap}
        .whitespace-pre-wrap{white-space:pre-wrap}
        .break-words{overflow-wrap:break-word}
        .fill-current{fill:currentColor}
        .stroke-current{stroke:currentColor}

        /* ─── COLORS: TEXT ─── */
        .text-transparent{color:transparent}
        .text-black{color:#000}.text-white{color:#fff}
        .text-gray-50{color:#f9fafb}.text-gray-100{color:#f3f4f6}
        .text-gray-200{color:#e5e7eb}.text-gray-300{color:#d1d5db}
        .text-gray-400{color:#9ca3af}.text-gray-500{color:#6b7280}
        .text-gray-600{color:#4b5563}.text-gray-700{color:#374151}
        .text-gray-800{color:#1f2937}.text-gray-900{color:#111827}
        .text-slate-300{color:#cbd5e1}.text-slate-400{color:#94a3b8}
        .text-slate-500{color:#64748b}.text-slate-600{color:#475569}
        .text-slate-700{color:#334155}.text-slate-800{color:#1e293b}
        .text-red-500{color:#ef4444}.text-red-600{color:#dc2626}
        .text-green-500{color:#22c55e}.text-green-600{color:#16a34a}
        .text-blue-500{color:#3b82f6}.text-blue-600{color:#2563eb}
        .text-blue-700{color:#1d4ed8}
        .text-cyan-500{color:#06b6d4}.text-cyan-600{color:#0891b2}
        .text-teal-500{color:#14b8a6}.text-teal-600{color:#0d9488}
        .text-yellow-500{color:#eab308}.text-orange-500{color:#f97316}
        .text-indigo-500{color:#6366f1}.text-indigo-600{color:#4f46e5}
        .text-purple-500{color:#a855f7}.text-pink-500{color:#ec4899}
        .text-\[\#073B4C\]{color:#073B4C}
        .text-\[\#118AB2\]{color:#118AB2}
        .text-\[\#0d9488\]{color:#0d9488}
        .text-\[\#10b4b2\]{color:#10b4b2}
        .text-\[\#1e3a5f\]{color:#1e3a5f}

        /* ─── COLORS: BACKGROUND ─── */
        .bg-transparent{background:transparent}
        .bg-black{background:#000}.bg-white{background:#fff}
        .bg-gray-50{background:#f9fafb}.bg-gray-100{background:#f3f4f6}
        .bg-gray-200{background:#e5e7eb}.bg-gray-300{background:#d1d5db}
        .bg-gray-400{background:#9ca3af}.bg-gray-500{background:#6b7280}
        .bg-gray-600{background:#4b5563}.bg-gray-700{background:#374151}
        .bg-gray-800{background:#1f2937}.bg-gray-900{background:#111827}
        .bg-slate-50{background:#f8fafc}.bg-slate-100{background:#f1f5f9}
        .bg-slate-200{background:#e2e8f0}.bg-slate-800{background:#1e293b}
        .bg-red-50{background:#fef2f2}.bg-red-100{background:#fee2e2}
        .bg-red-500{background:#ef4444}.bg-red-600{background:#dc2626}
        .bg-green-50{background:#f0fdf4}.bg-green-100{background:#dcfce7}
        .bg-green-500{background:#22c55e}.bg-green-600{background:#16a34a}
        .bg-blue-50{background:#eff6ff}.bg-blue-100{background:#dbeafe}
        .bg-blue-500{background:#3b82f6}.bg-blue-600{background:#2563eb}
        .bg-cyan-50{background:#ecfeff}.bg-cyan-500{background:#06b6d4}
        .bg-teal-50{background:#f0fdfa}.bg-teal-500{background:#14b8a6}
        .bg-yellow-50{background:#fefce8}.bg-yellow-100{background:#fef9c3}
        .bg-orange-50{background:#fff7ed}
        .bg-indigo-50{background:#eef2ff}.bg-indigo-500{background:#6366f1}
        .bg-\[\#073B4C\]{background:#073B4C}
        .bg-\[\#118AB2\]{background:#118AB2}
        .bg-\[\#1e3a5f\]{background:#1e3a5f}
        .bg-\[\#152a45\]{background:#152a45}
        .bg-\[\#f4f7fb\]{background:#f4f7fb}
        .bg-\[\#f8fafc\]{background:#f8fafc}
        .bg-white\/5{background:rgba(255,255,255,.05)}
        .bg-white\/10{background:rgba(255,255,255,.1)}
        .bg-white\/20{background:rgba(255,255,255,.2)}
        .bg-black\/5{background:rgba(0,0,0,.05)}
        .bg-black\/10{background:rgba(0,0,0,.1)}
        .bg-black\/20{background:rgba(0,0,0,.2)}
        .bg-black\/50{background:rgba(0,0,0,.5)}

        /* ─── BORDERS ─── */
        .border{border:1px solid #e5e7eb}
        .border-0{border:0}.border-2{border-width:2px}
        .border-4{border-width:4px}
        .border-t{border-top:1px solid #e5e7eb}
        .border-b{border-bottom:1px solid #e5e7eb}
        .border-l{border-left:1px solid #e5e7eb}
        .border-r{border-right:1px solid #e5e7eb}
        .border-t-0{border-top:0}.border-b-0{border-bottom:0}
        .border-t-2{border-top-width:2px}.border-b-2{border-bottom-width:2px}
        .border-transparent{border-color:transparent}
        .border-gray-100{border-color:#f3f4f6}.border-gray-200{border-color:#e5e7eb}
        .border-gray-300{border-color:#d1d5db}.border-gray-400{border-color:#9ca3af}
        .border-slate-200{border-color:#e2e8f0}.border-slate-300{border-color:#cbd5e1}
        .border-blue-200{border-color:#bfdbfe}.border-blue-500{border-color:#3b82f6}
        .border-blue-900{border-color:#1e3a8a}
        .border-cyan-500{border-color:#06b6d4}
        .border-teal-500{border-color:#14b8a6}
        .border-green-500{border-color:#22c55e}
        .border-red-500{border-color:#ef4444}
        .border-white\/10{border-color:rgba(255,255,255,.1)}
        .border-white\/20{border-color:rgba(255,255,255,.2)}
        .border-\[\#118AB2\]{border-color:#118AB2}
        .border-\[\#073B4C\]{border-color:#073B4C}
        .rounded-none{border-radius:0}.rounded-sm{border-radius:.125rem}
        .rounded{border-radius:.25rem}.rounded-md{border-radius:.375rem}
        .rounded-lg{border-radius:.5rem}.rounded-xl{border-radius:.75rem}
        .rounded-2xl{border-radius:1rem}.rounded-3xl{border-radius:1.5rem}
        .rounded-full{border-radius:9999px}
        .rounded-t-lg{border-top-left-radius:.5rem;border-top-right-radius:.5rem}
        .rounded-b-lg{border-bottom-left-radius:.5rem;border-bottom-right-radius:.5rem}
        .rounded-t-xl{border-top-left-radius:.75rem;border-top-right-radius:.75rem}
        .rounded-b-xl{border-bottom-left-radius:.75rem;border-bottom-right-radius:.75rem}

        /* ─── SHADOWS ─── */
        .shadow-sm{box-shadow:0 1px 2px rgba(0,0,0,.05)}
        .shadow{box-shadow:0 1px 3px rgba(0,0,0,.1),0 1px 2px rgba(0,0,0,.06)}
        .shadow-md{box-shadow:0 4px 6px -1px rgba(0,0,0,.1),0 2px 4px -1px rgba(0,0,0,.06)}
        .shadow-lg{box-shadow:0 10px 15px -3px rgba(0,0,0,.1),0 4px 6px -2px rgba(0,0,0,.05)}
        .shadow-xl{box-shadow:0 20px 25px -5px rgba(0,0,0,.1),0 10px 10px -5px rgba(0,0,0,.04)}
        .shadow-2xl{box-shadow:0 25px 50px -12px rgba(0,0,0,.25)}
        .shadow-none{box-shadow:none}

        /* ─── OVERFLOW & POSITION ─── */
        .overflow-hidden{overflow:hidden}
        .overflow-auto{overflow:auto}
        .overflow-x-auto{overflow-x:auto}
        .overflow-y-auto{overflow-y:auto}
        .overflow-scroll{overflow:scroll}
        .overflow-visible{overflow:visible}
        .relative{position:relative}
        .absolute{position:absolute}
        .fixed{position:fixed}
        .sticky{position:sticky}
        .static{position:static}
        .inset-0{top:0;right:0;bottom:0;left:0}
        .top-0{top:0}.right-0{right:0}.bottom-0{bottom:0}.left-0{left:0}
        .top-1{top:.25rem}.top-2{top:.5rem}.top-4{top:1rem}
        .right-2{right:.5rem}.right-4{right:1rem}
        .bottom-2{bottom:.5rem}.bottom-4{bottom:1rem}
        .left-2{left:.5rem}
        .z-0{z-index:0}.z-10{z-index:10}.z-20{z-index:20}
        .z-30{z-index:30}.z-40{z-index:40}.z-50{z-index:50}

        /* ─── OBJECT FIT & MISC ─── */
        .object-cover{object-fit:cover}
        .object-contain{object-fit:contain}
        .object-center{object-position:center}
        .cursor-pointer{cursor:pointer}
        .cursor-default{cursor:default}
        .pointer-events-none{pointer-events:none}
        .select-none{user-select:none}
        .resize-none{resize:none}
        .appearance-none{appearance:none;-webkit-appearance:none}
        .outline-none{outline:none}
        .focus\:outline-none:focus{outline:none}
        .ring-0{box-shadow:0 0 0 0 transparent}

        /* ─── OPACITY ─── */
        .opacity-0{opacity:0}.opacity-25{opacity:.25}
        .opacity-50{opacity:.5}.opacity-75{opacity:.75}
        .opacity-100{opacity:1}

        /* ─── TRANSITIONS ─── */
        .transition-all{transition-property:all;transition-timing-function:ease;transition-duration:.15s}
        .transition{transition-property:color,background-color,border-color,fill,stroke,opacity,box-shadow,transform;transition-timing-function:ease;transition-duration:.15s}
        .transition-colors{transition-property:color,background-color,border-color;transition-timing-function:ease;transition-duration:.15s}
        .transition-opacity{transition-property:opacity;transition-timing-function:ease;transition-duration:.15s}
        .transition-transform{transition-property:transform;transition-timing-function:ease;transition-duration:.15s}
        .duration-150{transition-duration:.15s}
        .duration-200{transition-duration:.2s}
        .duration-300{transition-duration:.3s}
        .duration-500{transition-duration:.5s}
        .ease-in-out{transition-timing-function:ease-in-out}

        /* ─── TRANSFORMS ─── */
        .transform{transform:translateX(var(--tw-translate-x,0)) translateY(var(--tw-translate-y,0)) rotate(var(--tw-rotate,0)) scale(var(--tw-scale-x,1),var(--tw-scale-y,1))}
        .scale-100{transform:scale(1)}.scale-105{transform:scale(1.05)}
        .scale-110{transform:scale(1.1)}.scale-95{transform:scale(.95)}
        .rotate-45{transform:rotate(45deg)}.rotate-90{transform:rotate(90deg)}
        .rotate-180{transform:rotate(180deg)}
        .-rotate-45{transform:rotate(-45deg)}.-rotate-90{transform:rotate(-90deg)}
        .translate-y-0{transform:translateY(0)}

        /* ─── GRADIENT ─── */
        .bg-gradient-to-r{background-image:linear-gradient(to right,var(--tw-gradient-stops,transparent,transparent))}
        .bg-gradient-to-l{background-image:linear-gradient(to left,var(--tw-gradient-stops,transparent,transparent))}
        .bg-gradient-to-b{background-image:linear-gradient(to bottom,var(--tw-gradient-stops,transparent,transparent))}
        .bg-gradient-to-t{background-image:linear-gradient(to top,var(--tw-gradient-stops,transparent,transparent))}
        .bg-gradient-to-r.from-transparent.via-gray-600.to-transparent{
            background:linear-gradient(to right,transparent,#4b5563,transparent)!important
        }

        /* ─── LISTS ─── */
        .list-none{list-style:none}.list-disc{list-style-type:disc}
        .list-decimal{list-style-type:decimal}
        .list-inside{list-style-position:inside}

        /* ─── FORMS ─── */
        input,textarea,select{font-family:inherit;font-size:inherit}
        .placeholder-gray-400::placeholder{color:#9ca3af}
        .placeholder-gray-500::placeholder{color:#6b7280}
        .focus\:ring-2:focus{box-shadow:0 0 0 2px rgba(59,130,246,.5)}
        .focus\:ring-cyan-500:focus{box-shadow:0 0 0 2px rgba(6,182,212,.5)}
        .focus\:ring-blue-500:focus{box-shadow:0 0 0 2px rgba(59,130,246,.5)}
        .focus\:border-cyan-500:focus{border-color:#06b6d4}
        .focus\:border-blue-500:focus{border-color:#3b82f6}

        /* ─── HOVER STATES ─── */
        @media(hover:hover){
            .hover\:shadow-md:hover{box-shadow:0 4px 6px -1px rgba(0,0,0,.1)}
            .hover\:shadow-lg:hover{box-shadow:0 10px 15px -3px rgba(0,0,0,.1)}
            .hover\:shadow-xl:hover{box-shadow:0 20px 25px -5px rgba(0,0,0,.1)}
            .hover\:text-white:hover{color:#fff}
            .hover\:text-gray-700:hover{color:#374151}
            .hover\:text-gray-900:hover{color:#111827}
            .hover\:text-blue-600:hover{color:#2563eb}
            .hover\:text-cyan-500:hover{color:#06b6d4}
            .hover\:text-teal-500:hover{color:#14b8a6}
            .hover\:text-\[\#118AB2\]:hover{color:#118AB2}
            .hover\:text-\[\#1e3a5f\]:hover{color:#1e3a5f}
            .hover\:text-\[\#FF0000\]:hover{color:#FF0000}
            .hover\:text-\[\#E1306C\]:hover{color:#E1306C}
            .hover\:text-\[\#0d9488\]:hover{color:#0d9488}
            .hover\:bg-white:hover{background:#fff}
            .hover\:bg-gray-50:hover{background:#f9fafb}
            .hover\:bg-gray-100:hover{background:#f3f4f6}
            .hover\:bg-gray-200:hover{background:#e5e7eb}
            .hover\:bg-blue-600:hover{background:#2563eb}
            .hover\:bg-blue-700:hover{background:#1d4ed8}
            .hover\:bg-cyan-500:hover{background:#06b6d4}
            .hover\:bg-cyan-600:hover{background:#0891b2}
            .hover\:bg-teal-600:hover{background:#0d9488}
            .hover\:bg-\[\#0f9ca0\]:hover{background:#0f9ca0}
            .hover\:bg-\[\#118AB2\]:hover{background:#118AB2}
            .hover\:underline:hover{text-decoration:underline}
            .hover\:no-underline:hover{text-decoration:none}
            .hover\:opacity-80:hover{opacity:.8}
            .hover\:scale-105:hover{transform:scale(1.05)}
            .hover\:border-cyan-500:hover{border-color:#06b6d4}
            .group:hover .group-hover\:scale-\[1\.03\]{transform:scale(1.03)}
            .group:hover .group-hover\:text-\[\#118AB2\]{color:#118AB2}
            .group:hover .group-hover\:text-white{color:#fff}
            .group:hover .group-hover\:bg-cyan-500{background:#06b6d4}
            .group:hover .group-hover\:opacity-100{opacity:1}
            .group:hover .group-hover\:visible{visibility:visible}
        }

        /* ─── NAVIGATION — Desktop ─── */
        .wrap{max-width:1400px;margin:0 auto;padding:0 16px}
        .custom-nav{background:#fff;border-bottom:1px solid #e2e8f0;box-shadow:0 1px 3px rgba(0,0,0,.05);padding:.6rem 0}
        .custom-nav img{width:50px;height:50px;border-radius:50%}
        .nav-link{color:var(--primary);font-weight:500;font-size:1.3rem;padding:0 .75rem;text-decoration:none;transition:color .2s}
        .nav-link:hover{color:var(--secondary)}
        .nav-link.active{font-weight:600;color:#0d9488}
        .pipe{color:#9ca3af;margin:0 .25rem}
        .nav-btn{background:var(--secondary);color:#fff;padding:.6rem 1.1rem;border-radius:.5rem;font-weight:600;text-decoration:none;transition:background .2s}
        .nav-btn:hover{background:#0f9ca0}

        /* ─── NAVIGATION — Mobile ─── */
        .dsrc-nav-mobile{background:#fff;border-bottom:1px solid #e5e7eb;box-shadow:0 1px 4px rgba(0,0,0,.04);display:none}
        .dsrc-nav-mobile .container{max-width:600px;margin:0 auto;padding:1.2rem 1rem 1.5rem;text-align:center}
        .dsrc-nav-mobile .brand img{width:72px!important;height:72px!important;border-radius:50%;margin:0 auto .85rem;display:block}
        .dsrc-nav-mobile .links{display:grid;grid-template-columns:repeat(3,1fr);row-gap:1rem;column-gap:.5rem;list-style:none;padding:0;margin:0}
        .dsrc-nav-mobile .links a{text-decoration:none;font-weight:700;font-size:1.32rem!important;color:#0f172a;padding-bottom:4px;transition:color .2s,transform .12s}
        .dsrc-nav-mobile .links a:hover{color:#0d9488;transform:translateY(-1px)}
        .dsrc-nav-mobile .links a.active{color:#0d9488!important}
        .dsrc-nav-mobile .cta-btn{background:#10b4b2;color:#fff;font-size:1.3rem!important;padding:.75rem 1.35rem;border-radius:.55rem;display:inline-block;font-weight:700;margin-top:1.5rem;transition:background .2s;text-decoration:none}
        .dsrc-nav-mobile .cta-btn:hover{background:#0f9ca0}

        /* ─── ARTICLE SHELL ─── */
        .article-shell{max-width:900px;margin:0 auto}

        /* ─── RESPONSIVE ─── */
        @media(max-width:980px){
            .custom-nav{display:none}
            .dsrc-nav-mobile{display:block}
        }
        @media(min-width:640px){
            .sm\:grid-cols-2{grid-template-columns:repeat(2,minmax(0,1fr))}
            .sm\:flex{display:flex}
            .sm\:block{display:block}
            .sm\:text-left{text-align:left}
            .sm\:px-6{padding-left:1.5rem;padding-right:1.5rem}
        }
        @media(min-width:768px){
            .md\:flex{display:flex}
            .md\:block{display:block}
            .md\:hidden{display:none}
            .md\:grid{display:grid}
            .md\:inline{display:inline}
            .md\:flex-row{flex-direction:row}
            .md\:items-center{align-items:center}
            .md\:items-start{align-items:flex-start}
            .md\:justify-between{justify-content:space-between}
            .md\:justify-start{justify-content:flex-start}
            .md\:justify-end{justify-content:flex-end}
            .md\:gap-3{gap:.75rem}
            .md\:gap-4{gap:1rem}
            .md\:gap-6{gap:1.5rem}
            .md\:gap-8{gap:2rem}
            .md\:p-6{padding:1.5rem}
            .md\:p-8{padding:2rem}
            .md\:px-8{padding-left:2rem;padding-right:2rem}
            .md\:py-12{padding-top:3rem;padding-bottom:3rem}
            .md\:text-left{text-align:left}
            .md\:text-lg{font-size:1.125rem}
            .md\:text-xl{font-size:1.25rem}
            .md\:text-2xl{font-size:1.5rem}
            .md\:text-3xl{font-size:1.875rem}
            .md\:text-4xl{font-size:2.25rem}
            .md\:text-5xl{font-size:3rem}
            .md\:grid-cols-2{grid-template-columns:repeat(2,minmax(0,1fr))}
            .md\:grid-cols-3{grid-template-columns:repeat(3,minmax(0,1fr))}
            .md\:grid-cols-4{grid-template-columns:repeat(4,minmax(0,1fr))}
            .md\:col-span-1{grid-column:span 1/span 1}
            .md\:col-span-2{grid-column:span 2/span 2}
            .md\:mb-0{margin-bottom:0}
            .md\:mt-0{margin-top:0}
            .md\:w-1\/2{width:50%}
            .md\:w-1\/3{width:33.333%}
            .md\:w-2\/3{width:66.667%}
            .md\:max-w-xl{max-width:36rem}
            .md\:max-w-2xl{max-width:42rem}
        }
        @media(min-width:1024px){
            .lg\:flex{display:flex}
            .lg\:block{display:block}
            .lg\:hidden{display:none}
            .lg\:grid-cols-3{grid-template-columns:repeat(3,minmax(0,1fr))}
            .lg\:grid-cols-4{grid-template-columns:repeat(4,minmax(0,1fr))}
            .lg\:grid-cols-5{grid-template-columns:repeat(5,minmax(0,1fr))}
            .lg\:gap-8{gap:2rem}
            .lg\:px-8{padding-left:2rem;padding-right:2rem}
            .lg\:text-xl{font-size:1.25rem}
            .lg\:text-2xl{font-size:1.5rem}
            .lg\:text-3xl{font-size:1.875rem}
            .lg\:text-5xl{font-size:3rem}
            .lg\:w-1\/3{width:33.333%}
            .lg\:max-w-4xl{max-width:56rem}
        }
        @media(min-width:1280px){
            .xl\:grid-cols-4{grid-template-columns:repeat(4,minmax(0,1fr))}
            .xl\:gap-8{gap:2rem}
            .xl\:px-0{padding-left:0;padding-right:0}
            .xl\:max-w-6xl{max-width:72rem}
        }

        /* ─── ADMIN BAR ─── */
        body.admin-bar{margin-top:32px!important}
        @media screen and (max-width:782px){body.admin-bar{margin-top:46px!important}}
        #wpadminbar{display:block!important;position:fixed;top:0;left:0;right:0;z-index:999999}
        html.wp-toolbar{margin-top:32px!important}
        @media screen and (max-width:782px){html.wp-toolbar{margin-top:46px!important}}

        /* ─── VIDEO RESPONSIVE ─── */
        .video-container{position:relative;padding-bottom:56.25%;height:0;overflow:hidden}
        .video-container iframe,.video-container video{position:absolute;top:0;left:0;width:100%;height:100%}
        iframe{max-width:100%}

        /* ─── PRINT ─── */
        @media print{
            .custom-nav,.dsrc-nav-mobile,footer,.nav-btn,.cta-btn,.skip-link{display:none!important}
            body{font-size:12pt;color:#000;background:#fff}
        }
    </style>

    <!-- ═══════════════════════════════════════
         Open Graph Meta Tags
         ═══════════════════════════════════════ -->
    <meta property="og:type" content="<?php echo is_single() ? 'article' : 'website'; ?>" />
    <meta property="og:title" content="<?php echo esc_attr( $seo_title ); ?>" />
    <meta property="og:description" content="<?php echo esc_attr( $seo_desc ); ?>" />
    <meta property="og:url" content="<?php echo esc_url( $canonical_url ); ?>" />
    <meta property="og:image" content="<?php echo esc_url( $seo_image ); ?>" />
    <meta property="og:image:width" content="1200" />
    <meta property="og:image:height" content="630" />
    <meta property="og:site_name" content="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?>" />
    <meta property="og:locale" content="en_US" />

    <!-- Twitter Card -->
    <meta name="twitter:card" content="summary_large_image" />
    <meta name="twitter:title" content="<?php echo esc_attr( $seo_title ); ?>" />
    <meta name="twitter:description" content="<?php echo esc_attr( $seo_desc ); ?>" />
    <meta name="twitter:image" content="<?php echo esc_url( $seo_image ); ?>" />

    <?php wp_head(); ?>

    <?php
    /* ═══════════════════════════════════════════════════════
     * Schema Markup: Article (Single Posts Only)
     * Uses wp_json_encode for proper escaping
     * ═══════════════════════════════════════════════════════ */
    if ( is_single() ) :
        $post_id      = get_queried_object_id();
        $post_title   = get_the_title( $post_id );
        $schema_desc  = trim( (string) get_post_meta( $post_id, 'meta_description', true ) );
        if ( empty( $schema_desc ) ) {
            $schema_desc = wp_strip_all_tags( get_the_excerpt( $post_id ) );
        }
        $post_img     = get_the_post_thumbnail_url( $post_id, 'full' );
        $post_url     = get_permalink( $post_id );
        $post_date    = get_the_date( 'Y-m-d', $post_id );
        $post_updated = get_the_modified_date( 'Y-m-d', $post_id );
        if ( ! $post_img ) {
            $post_img = $dsrc_logo_url;
        }

        $article_schema = array(
            '@context'        => 'https://schema.org',
            '@type'           => 'Article',
            'headline'        => $post_title,
            'inLanguage'      => 'en-US',
            'datePublished'   => $post_date,
            'dateModified'    => $post_updated,
            'description'     => $schema_desc,
            'author'          => array(
                '@type' => 'Organization',
                'name'  => 'Dhaka Sleep Research Centre',
                'url'   => $dsrc_site_url,
            ),
            'publisher'       => array(
                '@type' => 'Organization',
                'name'  => 'Dhaka Sleep Research Centre',
                'url'   => $dsrc_site_url,
                'logo'  => array(
                    '@type'  => 'ImageObject',
                    'url'    => $dsrc_logo_url,
                    'width'  => 60,
                    'height' => 60,
                ),
            ),
            'image'           => array(
                '@type'  => 'ImageObject',
                'url'    => $post_img,
                'width'  => 1200,
                'height' => 675,
            ),
            'mainEntityOfPage' => array(
                '@type' => 'WebPage',
                '@id'   => $post_url,
            ),
        );
        ?>
        <script type="application/ld+json">
        <?php echo wp_json_encode( $article_schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT ); ?>
        </script>
    <?php endif; ?>

    <!-- ═══════════════════════════════════════
         Schema Markup: WebSite (All Pages)
         ═══════════════════════════════════════ -->
    <?php
    $website_schema = array(
        '@context'        => 'https://schema.org',
        '@type'           => 'WebSite',
        'url'             => $dsrc_site_url,
        'name'            => 'Dhaka Sleep Research Centre (DSRC)',
        'potentialAction'  => array(
            '@type'       => 'SearchAction',
            'target'      => home_url( '/' ) . '?s={search_term_string}',
            'query-input' => 'required name=search_term_string',
        ),
    );
    ?>
    <script type="application/ld+json">
    <?php echo wp_json_encode( $website_schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT ); ?>
    </script>

</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<!-- ═══════════════════════════════════════
     Skip Link (Accessibility - WCAG 2.1)
     ═══════════════════════════════════════ -->
<a class="skip-link" 
   href="#main-content"
   style="position:absolute;top:-100%;left:0;background:#073B4C;color:#fff;padding:.75rem 1.5rem;z-index:10000;font-weight:600;text-decoration:none;border-radius:0 0 .5rem 0;transition:top .2s"
   onfocus="this.style.top='0'" 
   onblur="this.style.top='-100%'">
    Skip to content
</a>

<!-- ═══════════════════════════════════════
     DESKTOP NAVIGATION
     ═══════════════════════════════════════ -->
<nav class="custom-nav" role="navigation" aria-label="Main Navigation">
    <div class="wrap">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">

            <!-- Logo -->
            <a href="<?php echo $dsrc_site_url; ?>" 
               class="flex justify-center md:justify-start"
               aria-label="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?> - Home">
                <img src="<?php echo $dsrc_logo_url; ?>"
                     alt="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?> Logo"
                     width="50" height="50"
                     loading="eager"
                     fetchpriority="high">
            </a>

            <!-- Navigation Links -->
            <div class="flex flex-col items-center md:flex-row md:items-center md:gap-3 text-center">
                <a href="<?php echo $dsrc_site_url; ?>" 
                   class="nav-link <?php echo is_front_page() ? 'active' : ''; ?>">Home</a>
                <span class="pipe hidden md:inline" aria-hidden="true">|</span>

                <a href="<?php echo esc_url( home_url( '/about-us' ) ); ?>" 
                   class="nav-link <?php echo dsrc_is_active_page( $slug, 'about-us' ) ? 'active' : ''; ?>">About Us</a>
                <span class="pipe hidden md:inline" aria-hidden="true">|</span>

                <a href="<?php echo esc_url( home_url( '/services' ) ); ?>" 
                   class="nav-link <?php echo dsrc_is_active_page( $slug, 'services' ) ? 'active' : ''; ?>">Services</a>
                <span class="pipe hidden md:inline" aria-hidden="true">|</span>

                <a href="<?php echo esc_url( home_url( '/articles' ) ); ?>" 
                   class="nav-link <?php echo dsrc_is_active_page( $slug, 'articles' ) ? 'active' : ''; ?>">Articles</a>
                <span class="pipe hidden md:inline" aria-hidden="true">|</span>

                <a href="<?php echo esc_url( home_url( '/sleep-apnea-news' ) ); ?>" 
                   class="nav-link <?php echo dsrc_is_active_page( $slug, 'sleep-apnea-news' ) ? 'active' : ''; ?>">News</a>
                <span class="pipe hidden md:inline" aria-hidden="true">|</span>

                <a href="<?php echo esc_url( home_url( '/sleep-apnea-faq' ) ); ?>" 
                   class="nav-link <?php echo dsrc_is_active_page( $slug, 'sleep-apnea-faq' ) ? 'active' : ''; ?>">Sleep Apnea FAQ</a>
                <span class="pipe hidden md:inline" aria-hidden="true">|</span>

                <a href="<?php echo esc_url( home_url( '/contact' ) ); ?>" 
                   class="nav-link <?php echo dsrc_is_active_page( $slug, 'contact' ) ? 'active' : ''; ?>">Contact</a>
            </div>

            <!-- CTA Button -->
            <div class="flex justify-center md:justify-end">
                <a href="<?php echo esc_url( home_url( '/sleep-apnea-assessment' ) ); ?>" 
                   class="nav-btn">
                    Check your Health
                </a>
            </div>
        </div>
    </div>
</nav>

<!-- ═══════════════════════════════════════
     MOBILE NAVIGATION
     ═══════════════════════════════════════ -->
<nav class="dsrc-nav-mobile" role="navigation" aria-label="Mobile Navigation">
    <div class="container">

        <!-- Mobile Logo -->
        <div class="brand mb-3">
            <a href="<?php echo $dsrc_site_url; ?>" 
               aria-label="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?> - Home">
                <img src="<?php echo $dsrc_logo_url; ?>"
                     alt="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?>"
                     width="72" height="72"
                     loading="eager"
                     fetchpriority="high">
            </a>
        </div>

        <!-- Mobile Links -->
        <ul class="links">
            <li>
                <a href="<?php echo $dsrc_site_url; ?>" 
                   class="<?php echo is_front_page() ? 'active' : ''; ?>">Home</a>
            </li>
            <li>
                <a href="<?php echo esc_url( home_url( '/about-us' ) ); ?>" 
                   class="<?php echo dsrc_is_active_page( $slug, 'about-us' ) ? 'active' : ''; ?>">About Us</a>
            </li>
            <li>
                <a href="<?php echo esc_url( home_url( '/services' ) ); ?>" 
                   class="<?php echo dsrc_is_active_page( $slug, 'services' ) ? 'active' : ''; ?>">Services</a>
            </li>
            <li>
                <a href="<?php echo esc_url( home_url( '/articles' ) ); ?>" 
                   class="<?php echo dsrc_is_active_page( $slug, 'articles' ) ? 'active' : ''; ?>">Articles</a>
            </li>
            <li>
                <a href="<?php echo esc_url( home_url( '/sleep-apnea-news' ) ); ?>" 
                   class="<?php echo dsrc_is_active_page( $slug, 'sleep-apnea-news' ) ? 'active' : ''; ?>">News</a>
            </li>
            <li>
                <a href="<?php echo esc_url( home_url( '/sleep-apnea-faq' ) ); ?>" 
                   class="<?php echo dsrc_is_active_page( $slug, 'sleep-apnea-faq' ) ? 'active' : ''; ?>">FAQ</a>
            </li>
            <li>
                <a href="<?php echo esc_url( home_url( '/contact' ) ); ?>" 
                   class="<?php echo dsrc_is_active_page( $slug, 'contact' ) ? 'active' : ''; ?>">Contact</a>
            </li>
        </ul>

        <!-- Mobile CTA -->
        <div class="cta mt-4">
            <a href="<?php echo esc_url( home_url( '/sleep-apnea-assessment' ) ); ?>" 
               class="cta-btn">
                Check your Health
            </a>
        </div>
    </div>
</nav>

<!-- ═══════════════════════════════════════
     Main Content Wrapper
     Page templates provide their own <main>
     ═══════════════════════════════════════ -->
<div id="main-content">
