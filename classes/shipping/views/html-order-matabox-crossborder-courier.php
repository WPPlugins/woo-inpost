<?php 
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<?php
$custom_attributes = array( 'style' => 'width:100%;' );
if ( $disabled ) {
	$custom_attributes['disabled'] = 'disabled';
}
?>
<?php foreach ( $parcels as $key => $parcel ) : ?>
	<div>
	<?php if ( $status == 'new' ) : ?>
		<?php
			$params = array(
				'type'		 	=> 'select',
				'options' 		=> $package_weights,
				'class' 		=> array('easypack_parcel'),
				'input_class'	=> array('easypack_parcel_weight'),
				'label' 		=> __( 'Parcel', EasyPack::$text_domain ) . ' ',
			);
			woocommerce_form_field('parcel[' . $key . '][weight]', $params, $parcel['package_weight'] );
			
			_e( 'Dimensions (max 60x40x40)', EasyPack::$text_domain );
			
			?>
			<div>
				<?php
					$params = array(
						'type'		 		=> 'number',
						'class' 			=> 'easypack_parcel_width',
						'input_class'		=> 'easypack_parcel_width',
						'label' 			=> __( 'Width', EasyPack::$text_domain ) . ' ',
						'custom_attributes'	=> array( 'min' => '1', 'max' => '60' ),
						'style'				=> 'max-width: 70px;',
						'id'				=> 'parcel[' . $key . '][width]',
						'value'				=> $parcel['package_width'],
						'description'		=> 'cm'
					);
					woocommerce_wp_text_input( $params )
				?>
				<?php
					$params = array(
						'type'		 		=> 'number',
						'class' 			=> 'easypack_parcel_height',
						'input_class'		=> 'easypack_parcel_height',
						'label' 			=> __( 'Height', EasyPack::$text_domain ) . ' ',
						'custom_attributes'	=> array( 'min' => '1', 'max' => '40' ),
						'style'				=> 'max-width: 70px;',
						'id'				=> 'parcel[' . $key . '][height]',
						'value'				=> $parcel['package_height'],
						'description'		=> 'cm'
					);
					woocommerce_wp_text_input( $params )
				?>
				<?php
					$params = array(
						'type'		 		=> 'number',
						'class' 			=> 'easypack_parcel_length',
						'input_class'		=> 'easypack_parcel_length',
						'label' 			=> __( 'Length', EasyPack::$text_domain ) . ' ',
						'custom_attributes'	=> array( 'min' => '1', 'max' => '40' ),
						'style'				=> 'max-width: 70px;',
						'id'				=> 'parcel[' . $key . '][length]',
						'value'				=> $parcel['package_length'],
						'description'		=> 'cm'
					);
					woocommerce_wp_text_input( $params )
				?>
			</div>
			<?php 			
		?>
	<?php else : ?>
		<?php if ( $status == "Processing" ) : ?>
			<?php _e( 'Processing', EasyPack::$text_domain ); ?>
		<?php else : ?>
			<?php _e( 'Weight', EasyPack::$text_domain ); ?> <?php echo $parcel['package_weight']; ?> <?php _e( 'kg', EasyPack::$text_domain ); ?> 
			, <?php _e( 'dimensions', EasyPack::$text_domain ); ?>  <?php echo $parcel['package_width']; ?>x<?php echo $parcel['package_height']; ?>x<?php echo $parcel['package_length']; ?>
			<?php _e( 'cm', EasyPack::$text_domain ); ?>:
			<?php echo $parcel['crossborder_data']['tracking_number']; ?>
		<?php endif; ?>
	<?php endif; ?>
	</div>
<?php endforeach; ?>

<?php
$custom_attributes = array( 'style' => 'width:100%;' );
if ( $disabled ) {
	$custom_attributes['disabled'] = 'disabled';
}
$params = array(
		'type' => 'select',
		'options' => $send_methods,
		'class' => array('wc-enhanced-select'),
		'custom_attributes' => $custom_attributes,
		'label' => __('Send method', EasyPack::$text_domain ),
);
woocommerce_form_field('easypack_send_method', $params, $send_method );
?>

<p>
	<?php if ( $status == 'new' ) : ?>
		<button id="easypack_send_parcels" class="button button-primary"><?php _e('Send parcel', EasyPack::$text_domain ); ?></button>
	<?php endif; ?>
	<?php if ( $status == 'created' ) : ?>
		<button id="easypack_cancel_parcels" class="button"><?php _e('Cancel parcels', EasyPack::$text_domain ); ?></button>
	<?php endif; ?>
	<?php if ( $status == 'ReadyToBeSent' || $status == 'prepared' ) : ?>
		<a href="<?php echo $stickers_url; ?>" id="easypack_get_stickers" class="button button-primary" target="_blank"><?php _e('Get sticker(s)', EasyPack::$text_domain ); ?></a>
	<?php endif; ?>
	<?php if ( $tracking_url ) : ?>
		<a href="<?php echo $tracking_url; ?>" class="button" target="_blank"><?php _e('Track shipment', EasyPack::$text_domain ); ?></a>
	<?php endif; ?>
	<?php if ( $status == 'Processing' ) : ?>
		<?php _e( 'Processing', EasyPack::$text_domain ); ?>
	<?php endif; ?>
	<span id="easypack_spinner" class="spinner"></span>
</p>

<p id="easypack_error"></p>

<a href="#" download id="easypack_download" hidden></a>

<script type="text/javascript">
	if ( jQuery().select2 ) {
    	jQuery("#parcel_machine_id").select2();
    }

    jQuery('#easypack_send_parcels').click(function () {
        jQuery('#easypack_error').html('');
        jQuery(this).attr('disabled',true);
        jQuery("#easypack_spinner").addClass("is-active");
        var parcels = [];
        jQuery('select.easypack_parcel_weight').each(function(i) {
            var parent_div = jQuery(this).parent().parent();
            parcels[i] = { 
                    'weight'	: jQuery(this).val(), 
                    'width'		: jQuery(parent_div).find('.easypack_parcel_width').val(),   
                    'height'	: jQuery(parent_div).find('.easypack_parcel_height').val(),
                    'length'	: jQuery(parent_div).find('.easypack_parcel_length').val(),
            };
        })
		var data = 	{
				action: 			'easypack',
				easypack_action: 	'crossborder_courier_create_package',
				security: 			easypack_nonce,
				order_id: 			<?php echo $order_id; ?>,
				parcels: 			parcels,
				send_method: 		jQuery('#easypack_send_method').val(),
			};
		console.log(data);		
		jQuery.post(ajaxurl, data, function(response) {
			console.log(response);
			if ( response != 0 ) {
				response = JSON.parse(response);
				console.log(response);
				if (response.status == 'ok' ) {
                	jQuery("#easypack_parcel_machines .inside").html(response.content);
                	return false;
				}
				else {
					//alert(response.message);
					jQuery('#easypack_error').html(response.message);
				}
			}
			else {
				jQuery('#easypack_error').html('Invalid response.');
			}
			jQuery("#easypack_spinner").removeClass("is-active");
			jQuery('#easypack_send_parcels').attr('disabled',false);
		});
		return false;
	});

	function crossborder_processing() {
		console.log(jQuery('#easypack_status').val());
		if ( jQuery('#easypack_status').val() == 'Processing' ) {
			jQuery("#easypack_spinner").addClass("is-active");
			var data = 	{
					action: 			'easypack',
					easypack_action: 	'crossborder_courier_processing',
					security: 			easypack_nonce,
					order_id: 			<?php echo $order_id; ?>,
				};
			jQuery.post(ajaxurl, data, function(response) {
				console.log(response);
				if ( response != 0 ) {
					response = JSON.parse(response);
					console.log(response);
					if (response.status == 'ok' ) {
	                	jQuery("#easypack_parcel_machines .inside").html(response.content);
	                	return false;
					}
					else {
						jQuery("#easypack_parcel_machines .inside").html(response.content);
						//alert(response.message);
						jQuery('#easypack_error').html(response.message);
					}
				}
				else {
					//alert('Bad response.');
					jQuery('#easypack_error').html('Invalid response.');
				}
				jQuery("#easypack_spinner").removeClass("is-active");
				jQuery('#easypack_cancel_parcels').attr('disabled',false);
			});
		}
	}

	crossborder_processing();

</script>
