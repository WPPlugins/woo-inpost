<?php
/**
 * Review Order After Shipping CrossBorder
 *
 * @author
 * @package 	EasyPack/Templates
 * @version
 */

$parcel_machine_selected = false;
$selected = '';

?>
<tr class="easypack-parcel-machine">
	<td class="easypack-parcel-machine-label">
		<?php __( 'Select Parcel Locker', EasyPack::$text_domain ); ?>
	</td>
	<td class="easypack-parcel-machine-select">
        <select id="parcel_machine_id" name="parcel_machine_id">
            <option value="">-- <?php _e('Select parcel locker', EasyPack::$text_domain ); ?> --</option>

            <?php if ( count( $nearly_parcel_machines ) > 0 ): ?>
                <optgroup label="<?php _e('Nearly', EasyPack::$text_domain ); ?>">
                    <?php foreach ($nearly as $key => $value): ?>
                    	<?php $selected = ''; ?>
                    	<?php if ( $key == $parcel_machine_id ) : ?>
                    		<?php $parcel_machine_selected = true; ?>
                    		<?php $selected = 'selected'; ?>
                    	<?php endif; ?>
                        <option <?php echo $selected; ?> value="<?php echo $key ?>"><?php echo $value; ?></option>
                    <?php endforeach; ?>
                </optgroup>

                <optgroup label="<?php _e('Other', EasyPack::$text_domain ); ?>">
            <?php endif; ?>

                <?php foreach ($parcel_machines as $key => $value): ?>
                   	<?php $selected = ''; ?>
                   	<?php if ( !$parcel_machine_selected && $key == $parcel_machine_id ) : ?>
                   		<?php $parcel_machine_selected = true; ?>
                   		<?php $selected = 'selected'; ?>
                   	<?php endif; ?>
                    <option <?php echo $selected; ?> value="<?php echo $key ?>"><?php echo $value; ?></option>
                <?php endforeach; ?>

            <?php if ( count( $nearly_parcel_machines ) > 0 ): ?>
                </optgroup>
            <?php endif; ?>
        </select>

		<?php if ( $geowidget_src ) : ?>
<?php /*
			<link href="<?php echo $geowidget_css; ?>" rel="stylesheet" type="text/css">

	        <script type="text/javascript" src="<?php echo $geowidget_src; ?>"></script>
    	    <a class=".sheepla-map-trigger-element" sheepla-translator="open_map:html" href="#" onclick="return false; console.log(map); sheepla.open(); return false;"><?php _e( 'Map', EasyPack::$text_domain ); ?></a>
*/ ?>
			<span id="firstMap""></span>

			<style>
				.sheepla-widget-fullscreen {
					position: fixed;
				}
				.sheepla-map-trigger-selected-point {
					display: none;
				}
			</style>

        	<script type="text/javascript">
            	if ( jQuery().select2 ) {
                	jQuery("#parcel_machine_id").select2();
                	jQuery( window ).focus(function() {
						jQuery("#parcel_machine_id").change();
						setTimeout( function(){ jQuery("#parcel_machine_id").change(); }, 500 );
        	        })
            	}

            	sheepla.api_key = '<?php echo $sheepla_api_key; ?>';

//            	jQuery('#sheepla_method').checked = false;
				var country = jQuery('#billing_country').val();
				if ( jQuery('#ship-to-different-address-checkbox').is(':checked') ) {
					country = jQuery('#shipping_country').val();
				}
    	        var map = sheepla.widget.call('crossborderMap',{
        		   	country: country,
            		target: "#firstMap",
            		mapTarget: null
            	});

            	jQuery(".sheepla-map-trigger-element").addClass('button');
            	jQuery(".sheepla-map-trigger-element").html('<?php _e( 'Map', EasyPack::$text_domain ); ?>');

    	        map.em.attach('after.pop_select', function(ctx, params) {
    	        	console.log(params);
    	        	jQuery('#parcel_machine_id').val(params.selectedPopId);
    	        	jQuery('#parcel_machine_id').change();
    	        });

				jQuery("body").on("click",".sheepla-map-trigger-element",function(e) {
					//console.log(wc_checkout_form);
					dirtyInput = false;
					e.preventDefault();
				})
        	</script>
        <?php endif; ?>

	</td>
</tr>
