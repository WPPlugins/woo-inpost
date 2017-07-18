<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$first_parcel = true;

?>

<?php
$custom_attributes = array( 'style' => 'width:100%;' );
if ( $disabled ) {
	$custom_attributes['disabled'] = 'disabled';
}
$params = array(
		'type' 				=> 'select',
		'options' 			=> $parcel_machines,
		'class' 			=> array('wc-enhanced-select'),
		'custom_attributes' => $custom_attributes,
		'label' 			=> __('Selected parcel locker', EasyPack::$text_domain ),
);
woocommerce_form_field('parcel_machine_id', $params, $parcel_machine_id );
?>

<p><?php _e( 'Parcels', EasyPack::$text_domain ); ?>
	<ol id="easypack_parcels">
		<?php foreach ( $parcels as $parcel ) : ?>
			<li>
				<?php if ( $status == 'new' ) : ?>
					<?php
						$params = array(
							'type'		 	=> 'select',
							'options' 		=> $package_sizes,
							'class' 		=> array('easypack_parcel'),
							'input_class'	=> array('easypack_parcel'),
							'label' 		=> '',
						);
						woocommerce_form_field('parcel[]', $params, $parcel['package_size'] );
					?>
					<?php if ( $status == 'new' && ! $first_parcel ) : ?>
						<button class="button easypack_remove_parcel"><?php _e( 'Remove', EasyPack::$text_domain ); ?></button>
					<?php endif; ?>
				<?php else : ?>
					<?php _e( 'Size', EasyPack::$text_domain ); ?> <?php echo $package_sizes_display[$parcel['package_size']]; ?>:
					<?php echo $parcel['easypack_data']['id']; ?>
				<?php endif; ?>
			</li>
			<?php $first_parcel = false; ?>
		<?php endforeach; ?>
	</ol>
	<?php if ( $status == 'new' ) : ?>
		<button class="button easypack_add_parcel"><?php _e( 'Add parcel', EasyPack::$text_domain ); ?></button>
	<?php endif; ?>
</p>

<?php
$custom_attributes = array( 'style' => 'width:100%;' );
if ( $disabled || $send_method_disabled ) {
	$custom_attributes['disabled'] = 'disabled';
}
$params = array(
		'type' 				=> 'select',
		'options' 			=> $send_methods,
		'class' 			=> array('wc-enhanced-select'),
		'custom_attributes' => $custom_attributes,
		'label'		 		=> __( 'Send method', EasyPack::$text_domain ),
);
woocommerce_form_field('easypack_send_method', $params, $send_method );
?>

<p>
	<?php if ( $status == 'new' ) : ?>
		<button id="easypack_send_parcels" class="button button-primary"><?php _e('Send parcels', EasyPack::$text_domain ); ?></button>
	<?php endif; ?>
	<?php if ( EasyPack_API()->api_country() == 'PL' ) : ?>
		<?php if ( $status == 'created' ) : ?>
			<button id="easypack_cancel_parcels" class="button"><?php _e('Cancel parcels', EasyPack::$text_domain ); ?></button>
		<?php endif; ?>
	<?php endif; ?>
	<?php if ( EasyPack_API()->api_country() == 'PL' ) : ?>
		<?php if ( $status == 'created' || $status == 'prepared' ) : ?>
			<a href="<?php echo $stickers_url; ?>" id="easypack_get_stickers" class="button button-primary" target="_blank"><?php _e('Get sticker(s)', EasyPack::$text_domain ); ?></a>
		<?php endif; ?>
	<?php endif; ?>
	<?php if ( $tracking_url ) : ?>
		<a href="<?php echo $tracking_url; ?>" class="button" target="_blank"><?php _e('Track shipment', EasyPack::$text_domain ); ?></a>
	<?php endif; ?>
	<span id="easypack_spinner" class="spinner"></span>
</p>

<p id="easypack_error"></p>

<a href="#" download id="easypack_download" hidden></a>

<script type="text/javascript">
	if ( jQuery().select2 ) {
    	jQuery("#parcel_machine_id").select2();
    }

	jQuery(".easypack_add_parcel").click(function() {
		var ul = jQuery('#easypack_parcels');
		var li = ul.find('li:first').clone(true)
		li.append('<button class="button easypack_remove_parcel"><?php _e( 'Remove', EasyPack::$text_domain ); ?></button>');
		li.appendTo(ul);
		return false;
	});

    jQuery("#easypack_parcels").on("click", ".easypack_remove_parcel", function() {
        jQuery(this).parent().remove();
        return false;
    })

    jQuery('#easypack_send_parcels').click(function () {
        jQuery('#easypack_error').html('');
        jQuery(this).attr('disabled',true);
        jQuery("#easypack_spinner").addClass("is-active");
        var parcels = [];
        jQuery('select.easypack_parcel').each(function(i) {
            parcels[i] = jQuery(this).val();
        })
		var data = 	{
				action				: 'easypack',
				easypack_action		: 'parcel_machines_create_package',
				security			: easypack_nonce,
				order_id			: <?php echo $order_id; ?>,
				parcel_machine_id	: jQuery('#parcel_machine_id').val(),
				parcels				: parcels,
				send_method			: jQuery('#easypack_send_method').val(),
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
					//alert(response.message);
					jQuery('#easypack_error').html(response.message);
				}
			}
			else {
				//alert('Bad response.');
				jQuery('#easypack_error').html('Invalid response.');
			}
			jQuery("#easypack_spinner").removeClass("is-active");
			jQuery('#easypack_send_parcels').attr('disabled',false);
		});
		return false;

	});

    jQuery('#easypack_cancel_parcels').click(function () {
        jQuery('#easypack_error').html('');
        jQuery(this).attr('disabled',true);
        jQuery("#easypack_spinner").addClass("is-active");
		var data = 	{
				action			: 'easypack',
				easypack_action	: 'parcel_machines_cancel_package',
				security		: easypack_nonce,
				order_id		: <?php echo $order_id; ?>,
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
		return false;
	});

</script>
