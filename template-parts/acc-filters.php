<?php
$choices = $args['choices']; $state = $args['state']; $compact = ! empty( $args['compact'] );
?>
<form method="get" class="vehicle-filters" action="<?php echo esc_url( igp_acc_url( 'products/' ) ); ?>" data-pairs="<?php echo esc_attr( wp_json_encode( $choices['pairs'] ) ); ?>">
<?php foreach ( array( 'make'=>'Vehicle make','model'=>'Vehicle model','mat'=>'Material' ) as $key=>$label ) : if ( $compact && 'mat' === $key ) { continue; } ?>
<label><?php echo esc_html( $label ); ?><select name="<?php echo esc_attr( $key ); ?>"><option value="">All <?php echo esc_html( array( 'make'=>'makes','model'=>'models','mat'=>'materials' )[ $key ] ); ?></option>
<?php $options = $choices[ $key ]; if ( ! empty( $state[ $key ] ) && ! isset( $options[ $state[ $key ] ] ) ) { $options[] = $state[ $key ]; } foreach ( $options as $option ) : ?>
<option <?php selected( $state[ $key ] ?? '', $option ); ?>><?php echo esc_html( $option ); ?></option><?php endforeach; ?></select></label><?php endforeach; ?>
<?php if ( ! $compact ) : ?><label>Keyword or part number<input type="search" name="q" maxlength="200" value="<?php echo esc_attr( $state['q'] ); ?>"></label><?php endif; ?>
<button class="button button-primary"><?php echo $compact ? 'Find a radiator' : 'Apply filters'; ?> <span aria-hidden="true">→</span></button></form>
