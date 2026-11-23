<?php
$acc = 'acc' === $args['brand'];
$brand = $acc ? 'acc' : 'igp';
$slug = igp_input( $_GET, 'product' );
$product = $slug ? get_page_by_path( sanitize_title( $slug ), OBJECT, array( 'igp_product' ) ) : null;
if ( $product && ( 'publish' !== $product->post_status || $product->post_password || igp_brand( $product->ID ) !== $brand ) ) { $product = null; }
$fields = array( 'name'=>array( 'Name','text' ), 'email'=>array( 'Email','email' ), 'company'=>array( 'Company','text' ), 'phone'=>array( 'Phone','tel' ), 'product'=>array( $acc ? 'Radiator product' : 'Product or part', 'text' ), 'part_number'=>array( 'Part number','text' ), 'quantity'=>array( 'Quantity','number' ) );
$fields += $acc ? array( 'vehicle_make'=>array( 'Vehicle make','text' ), 'vehicle_model'=>array( 'Vehicle model','text' ), 'vehicle_year'=>array( 'Model year','number' ), 'transmission'=>array( 'Transmission','text' ) ) : array( 'generator_brand'=>array( 'Generator manufacturer','text' ), 'generator_model'=>array( 'Generator model','text' ), 'engine_manufacturer'=>array( 'Engine manufacturer','text' ), 'serial_number'=>array( 'Serial number','text' ) );
$values = array();
if ( $product ) {
	$values['product'] = $product->post_title;
	foreach ( array( 'part_number'=>'_igp_part_number','vehicle_make'=>'_acc_make','vehicle_model'=>'_acc_model','transmission'=>'_acc_transmission' ) as $key=>$meta ) { $values[ $key ] = get_post_meta( $product->ID, $meta, true ); }
}
?>
<div class="shell acc-page quote-page"><div class="quote-heading"><div><p class="eyebrow"><?php echo $acc ? 'ACC Radiators' : 'Innovative Generator Parts'; ?></p><h1><?php echo $acc ? 'Enquire about a radiator.' : 'Tell us what you need.'; ?></h1><p><?php echo $acc ? 'Send your vehicle details and part number so ACC can review fitment and availability.' : 'Send the part markings, generator model or a reference image to help us identify the component.'; ?></p><?php if ( $acc ) : ?><p><a class="text-link" href="mailto:info@accradiators.pk">info@accradiators.pk</a></p><?php endif; ?></div>
<form class="quote-form" method="post" enctype="multipart/form-data" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
<?php if ( igp_receipt_valid( $brand ) ) : ?><p class="form-message success" role="status">Your enquiry has been received.</p><?php endif; if ( $slug && ! $product ) : ?><p role="status">That product is unavailable. Enter the details you have below.</p><?php endif; ?>
<input type="hidden" name="action" value="igp_quote"><input type="hidden" name="brand" value="<?php echo esc_attr( $brand ); ?>"><input type="hidden" name="submission_id" value="<?php echo esc_attr( wp_generate_uuid4() ); ?>"><?php wp_nonce_field( 'igp_quote_' . $brand ); ?>
<?php if ( $product ) : ?><input type="hidden" name="product_id" value="<?php echo esc_attr( $product->ID ); ?>"><?php endif; ?>
<div class="sr-only" aria-hidden="true"><label>Leave blank<input name="website" tabindex="-1" autocomplete="off"></label></div><div class="form-grid">
<?php foreach ( $fields as $key=>$field ) : $required = in_array( $key, array( 'name','email' ), true ); ?><label class="field"><?php echo esc_html( $field[0] . ( $required ? ' *' : '' ) ); ?><span class="field-control"><input type="<?php echo esc_attr( $field[1] ); ?>" name="<?php echo esc_attr( $key ); ?>" value="<?php echo esc_attr( $values[ $key ] ?? '' ); ?>" <?php echo $required ? 'required' : ''; ?> <?php echo 'quantity' === $key ? 'min="1" max="999999" step="1"' : ( 'vehicle_year' === $key ? 'min="1886" max="2199" step="1"' : 'maxlength="254"' ); ?>></span></label><?php endforeach; ?>
<label class="field field-full">Message<span class="field-control"><textarea name="message" rows="4" maxlength="4000"></textarea></span></label><label class="field field-full">Reference photo or PDF<span class="field-control"><input type="file" name="attachment" accept="image/jpeg,image/png,image/webp,application/pdf"><small>JPG, PNG, WebP or PDF, up to 5 MB.</small></span></label></div><p class="form-privacy">Fields marked * are required. Please leave out payment details.</p><button type="submit" class="button button-primary">Send enquiry <span aria-hidden="true">↗</span></button></form></div></div>
