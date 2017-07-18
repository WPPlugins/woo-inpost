<div class="wrap">
	<div id="icon-users" class="icon32"></div>
	<h2><?php _e( 'InPost Shipments' , EasyPack::$text_domain ); ?></h2>

	<?php  $shipment_manager_list_table->prepare_items(); ?>
	<form method="get">
		<input type="hidden" name="page" value="easypack_shipment">
		<?php if ( EasyPack_API()->api_country() == 'PL' ) : ?>
			<div style="float:left;">
				<?php
					$params = array(
						'type' => 'select',
						'options' => $dispatch_points,
						'class' => array('wc-enhanced-select'),
						'label' => __('Dispatch point ', EasyPack::$text_domain),
					);
					woocommerce_form_field('dispatch_point', $params, $dispatch_point );
				?>
			</div>
			<div style="float:left;">
				<p>&nbsp;
					<span class="tips" data-tip="<?php esc_attr_e( 'From the list, select the packages that you want to be sent by courier.', EasyPack::$text_domain ); ?>">
						<button disabled id="easypack_get_courier" class="button-primary"><?php _e( 'Get courier', EasyPack::$text_domain ); ?></button>&nbsp;
					</span>
				</p>
			</div>
			<div style="float:left;">
				<p><span id="easypack_spinner_get_courier" class="spinner"></span></p>
			</div>
			<div style="clear:both;"></div>
		<?php endif; ?>

		<div style="float:left;">
		<?php
			$params = array(
				'type' => 'select',
				'options' => $send_methods,
				'class' => array('wc-enhanced-select'),
				'label' => __('Send method ', EasyPack::$text_domain ),
			);
			woocommerce_form_field('send_method', $params, $send_method );
		?>
		</div>
		<div style="float:left;">
			<p>&nbsp;<input class="button button-primary" type="submit" value="<?php _e( 'Filter parcels', EasyPack::$text_domain ); ?>" /></p>
		</div>
		<div style="clear:both;"></div>

		<div style="float:left;">
			<p>
				<?php if ( EasyPack_API()->api_country() == 'PL' ) : ?>
					<span>
				<?php else : ?>
					<span class="tips" data-tip="<?php esc_attr_e( 'From the list, select the packages that you want to be collected to be sent. If Courier has been chosen, the collection of your packages by a courier will be arranged.', EasyPack::$text_domain ); ?>">
				<?php endif; ?>
					<button id="easypack_get_stickers" class="button-primary" href=""><?php _e( 'Get stickers', EasyPack::$text_domain ); ?></button>&nbsp;
				</span>
			</p>
		</div>
		<div style="float:left;">
			<p><span id="easypack_spinner_get_stickers" class="spinner"></span></p>
		</div>
		<div style="clear:both;"></div>
	<?php /*
		<div style="float:left;">
			<p>
				<a id="easypack_create_manifest" class="button-primary" href=""><?php _e( 'Create manifest', EasyPack::$text_domain ); ?></a>
			</p>
		</div>
		<div style="float:left;">
			<p><span id="easypack_spinner_create_manifest" class="spinner"></span></p>
		</div>
		<div style="clear:both;"></div>
	*/ ?>
	</form>
	<form id="easypack_shipment_form" method="post">

		<input type="hidden" id="easypack_create_manifest_input" name="easypack_create_manifest_input" value="0" />

		<input type="hidden" id="easypack_dispatch_point" name="easypack_dispatch_point" value="0" />

	    <input type="hidden" name="page" value="easypack_shipment">

	  	<?php  $shipment_manager_list_table->display(); ?>

	</form>

</div>

<script type="text/javascript">

	jQuery('#easypack_get_courier_old').click(function () {
		jQuery(this).attr('disabled',true);
		jQuery("#easypack_spinner_get_courier").addClass("is-active");
		console.log('get_courier');
        var parcels = [];
        var count_parcels = 0;
        jQuery('input.easypack_parcel').each(function(i) {
            if ( jQuery(this).attr('checked') ) {
            	parcels[i] = jQuery(this).val();
            	count_parcels++;
            }
        });
        if ( count_parcels == 0 ) {
            alert('<?php _e( 'No parcels selected.', EasyPack::$text_domain ) ?>');
            jQuery("#easypack_spinner_get_courier").removeClass("is-active");
            jQuery('#easypack_get_courier').attr('disabled',false);
            return false;
        }
		var data = 	{
				action: 'easypack',
				easypack_action: 'easypack_dispatch_order',
				security: easypack_nonce,
				parcels: parcels,
				dispatch_point: jQuery('#dispatch_point').val()
			};
		jQuery.post(ajaxurl, data, function(response) {
			console.log(response);
			if ( response != 0 ) {
				response = JSON.parse(response);
				console.log(response);
				if (response.status == 'ok' ) {

                	return false;
				}
				else {
					alert(response.message);
				}
			}
			else {
				alert('Bad response.');
			}
			jQuery('#easypack_get_courier').attr('disabled',false);
			jQuery("#easypack_spinner_get_courier").removeClass("is-active");
		});

        console.log(parcels);

		return false;
	})

	jQuery('#easypack_get_courier').click(function () {
        var parcels = [];
        var count_parcels = 0;
        jQuery('input.easypack_parcel').each(function(i) {
            if ( jQuery(this).attr('checked') ) {
            	parcels[i] = jQuery(this).val();
            	count_parcels++;
            }
        });
        if ( count_parcels == 0 ) {
            alert('<?php _e( 'No parcels selected.', EasyPack::$text_domain ) ?>');
            jQuery("#easypack_spinner_get_stickers").removeClass("is-active");
            return false;
        }
		jQuery('#easypack_create_manifest_input').val(1);
		jQuery('#easypack_dispatch_point').val(jQuery('#dispatch_point').val());
		jQuery("#easypack_shipment_form").submit();
		return false;
	});


	jQuery('#easypack_get_stickers').click(function () {
		jQuery(this).attr('disabled',true);
		jQuery("#easypack_spinner_get_stickers").addClass("is-active");
		console.log('get_stickers');
        var parcels = [];
        var count_parcels = 0;
        jQuery('input.easypack_parcel').each(function(i) {
            if ( jQuery(this).attr('checked') ) {
            	parcels[i] = jQuery(this).val();
            	count_parcels++;
            }
        });
        if ( count_parcels == 0 ) {
            alert('<?php _e( 'No parcels selected.', EasyPack::$text_domain ) ?>');
            jQuery("#easypack_spinner_get_stickers").removeClass("is-active");
            jQuery('#easypack_get_stickers').attr('disabled',false);
            return false;
        }
		var data = 	{
				action: 'easypack',
				easypack_action: 'easypack_create_stickers',
				security: easypack_nonce,
				parcels: parcels,
			};
		jQuery.post(ajaxurl, data, function(response) {
			console.log(response);
			if ( response != 0 ) {
				response = JSON.parse(response);
				console.log(response);
				if (response.status == 'ok' ) {
					var url = '<?php echo site_url('?easypack_download=1&easypack_file='); ?>' + response.file + '&security=<?php echo wp_create_nonce( 'easypack_nonce' ); ?>';
					console.log(url);
					window.open( url, "_blank");
				}
				else {
					alert(response.message);
				}
			}
			else {
				alert('Bad response.');
			}
			jQuery('#easypack_get_stickers').attr('disabled',false);
			jQuery("#easypack_spinner_get_stickers").removeClass("is-active");
		});

        console.log(parcels);

		return false;
	})

	jQuery('#easypack_create_manifest').click(function () {
        var parcels = [];
        var count_parcels = 0;
        jQuery('input.easypack_parcel').each(function(i) {
            if ( jQuery(this).attr('checked') ) {
            	parcels[i] = jQuery(this).val();
            	count_parcels++;
            }
        });
        if ( count_parcels == 0 ) {
            alert('<?php _e( 'No parcels selected.', EasyPack::$text_domain ) ?>');
            jQuery("#easypack_spinner_get_stickers").removeClass("is-active");
            return false;
        }
		jQuery('#easypack_create_manifest_input').val(1);
		jQuery('#easypack_dispatch_point').val(jQuery('#dispatch_point').val());
		jQuery("#easypack_shipment_form").submit();
		return false;
	});

	jQuery('.easypack_parcel').change(function() {
		var easypack_get_courier_disabled = false;
		var easypack_get_courier_count = 0;
		jQuery('.easypack_parcel').each(function() {
			if ( jQuery(this).is(':checked') ) {
				easypack_get_courier_count++;
				var cb_split = (jQuery(this).val()).split(".");
				if (cb_split[3] != 'courier') easypack_get_courier_disabled = true;
				if (cb_split[4] != 'prepared' && cb_split[4] != 'ReadyToBeSent') easypack_get_courier_disabled = true;
			}
		});
		if (easypack_get_courier_count == 0) easypack_get_courier_disabled = true;
		jQuery('#easypack_get_courier').attr('disabled',easypack_get_courier_disabled);
	});

</script>