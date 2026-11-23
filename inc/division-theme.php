<?php
defined( 'ABSPATH' ) || exit;
function igp_acc_screen() {
	if ( is_singular( 'igp_product' ) && 'acc' === igp_brand( get_queried_object_id() ) ) { return 'detail'; }
	if ( is_page() ) {
		$path = get_page_uri( get_queried_object_id() );
		$routes = array( 'acc-radiators' => 'home', 'acc-radiators/products' => 'products', 'acc-radiators/about' => 'about', 'acc-radiators/contact' => 'contact' );
		return $routes[ $path ] ?? '';
	}
	return '';
}
function igp_acc_url( $path = '' ) { return home_url( '/acc-radiators/' . $path ); }
add_filter( 'template_include', function ( $template ) {
	if ( ! defined( 'IGP_CORE_ACTIVE' ) || ! function_exists( 'igp_brand' ) ) { return get_template_directory() . '/template-parts/setup.php'; }
	return igp_acc_screen() ? get_template_directory() . '/template-parts/acc.php' : $template;
} );
function igp_division_menu() {
	if ( ! igp_acc_screen() && has_nav_menu( 'primary' ) ) {
		wp_nav_menu( array( 'theme_location'=>'primary','container'=>false,'items_wrap'=>'<ul class="primary-links">%3$s</ul>' ) ); return;
	}
	$acc = igp_acc_screen();
	$items = $acc ? array( ''=>'Home','products/'=>'Radiators','about/'=>'About','contact/'=>'Contact' ) : array( ''=>'Home','products/'=>'Products','categories/'=>'Categories','about/'=>'About','contact/'=>'Contact' );
	foreach ( $items as $path => $label ) {
		$url = $acc ? igp_acc_url( $path ) : igp_url( $path );
		$current = untrailingslashit( wp_parse_url( $url, PHP_URL_PATH ) ) === untrailingslashit( wp_parse_url( $_SERVER['REQUEST_URI'], PHP_URL_PATH ) );
		echo '<a href="' . esc_url( $url ) . '"' . ( $current ? ' aria-current="page"' : '' ) . '>' . esc_html( $label ) . '</a>';
	}
}
add_action( 'wp_enqueue_scripts', function () { wp_enqueue_style( 'igp-divisions', IGP_URI . '/assets/css/divisions.css', array( 'igp-site' ), '2.1.1' ); } );
add_filter( 'body_class', function ( $classes ) { if ( function_exists( 'igp_brand' ) && igp_acc_screen() ) { $classes[] = 'division-acc'; } return $classes; } );
function igp_seo_data() {
	$id = get_queried_object_id();
	$acc = igp_acc_screen();
	$brand = $acc ? 'ACC Radiators' : 'Innovative Generator Parts';
	$name = is_front_page() ? 'Generator parts catalogue' : ( is_post_type_archive( 'igp_product' ) ? 'Generator parts' : ( is_tax() ? single_term_title( '', false ) : get_the_title( $id ) ) );
	$desc = is_tax() ? term_description() : '';
	$image = $acc ? IGP_URI . '/assets/acc/background.webp' : IGP_URI . '/assets/images/generator-hero.webp';
	if ( is_singular( 'igp_product' ) ) {
		$name = get_post_meta( $id, '_igp_seo_title', true ) ?: $name;
		$desc = get_post_meta( $id, '_igp_seo_description', true ) ?: ( $acc ? $name . ' radiator. Review the listed material, core and transmission and enquire about fitment.' : get_the_excerpt( $id ) );
		$image = igp_product_image( $id ) ?: $image;
	}
	if ( ! $desc ) { $desc = $acc ? array( 'home'=>'Browse automotive radiators by vehicle make, model and core construction.', 'products'=>'Search the ACC radiator catalogue by vehicle, material or part number.', 'about'=>'Explore the radiator constructions and vehicle information in the ACC catalogue.', 'contact'=>'Send ACC Radiators a vehicle fitment or quotation enquiry.' )[ $acc ] : $name . ' from Innovative Generator Parts. Find generator components and request part identification or a quotation.'; }
	$url = is_singular() ? get_permalink( $id ) : ( is_tax() ? get_term_link( get_queried_object() ) : get_post_type_archive_link( 'igp_product' ) );
	if ( is_front_page() ) { $url = home_url( '/' ); }
	if ( is_paged() ) { $url = strtok( get_pagenum_link( max( 1, get_query_var( 'paged' ) ) ), '?' ); }
	return array( 'title'=>$name === $brand ? $brand : $name . ' | ' . $brand, 'desc'=>wp_strip_all_tags( $desc ),'url'=>$url,'image'=>$image,'brand'=>$brand );
}
function igp_seo_screen() { return function_exists( 'igp_brand' ) && ( is_front_page() || is_page() || is_singular( 'igp_product' ) || is_tax( 'igp_category' ) || is_post_type_archive( 'igp_product' ) ); }
add_filter( 'pre_get_document_title', function ( $title ) { return igp_seo_screen() ? igp_seo_data()['title'] : $title; } );
add_action( 'wp', function () { if ( igp_seo_screen() ) { remove_action( 'wp_head', 'rel_canonical' ); } } );
add_action( 'wp_head', function () {
	if ( ! igp_seo_screen() ) { return; }
	$d = igp_seo_data();
	echo '<link rel="canonical" href="' . esc_url( $d['url'] ) . '"><meta name="description" content="' . esc_attr( $d['desc'] ) . '">';
	foreach ( array( 'og:title'=>$d['title'],'og:description'=>$d['desc'],'og:url'=>$d['url'],'og:image'=>$d['image'],'og:type'=>'website','og:site_name'=>$d['brand'] ) as $prop=>$value ) { echo '<meta property="' . esc_attr( $prop ) . '" content="' . esc_attr( $value ) . '">'; }
	$data = array( '@context'=>'https://schema.org','@type'=>is_singular( 'igp_product' ) ? 'Product' : 'WebPage','name'=>is_singular( 'igp_product' ) ? get_the_title() : $d['title'],'description'=>$d['desc'],'url'=>$d['url'],'image'=>$d['image'] );
	if ( is_singular( 'igp_product' ) && get_post_meta( get_queried_object_id(), '_igp_part_number', true ) ) { $data['sku'] = get_post_meta( get_queried_object_id(), '_igp_part_number', true ); }
	echo '<script type="application/ld+json">' . wp_json_encode( $data, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT ) . '</script>';
} );
add_filter( 'wp_robots', function ( $robots ) { if ( igp_seo_screen() && $_GET ) { $robots['noindex'] = true; } return $robots; } );
