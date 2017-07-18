<?php
/**
 * Review Order After Shipping EasyPack  - for Avada Theme
 *
 * @author
 * @package 	EasyPack/Templates
 * @version
 */

$parcel_machine_selected = false;
$selected = '';

?>
<tr class="easypack-parcel-machine">
	<th class="easypack-parcel-machine-label">
		<?php __( 'Select Parcel Locker', EasyPack::$text_domain ); ?>
	</th>
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

        <script type="text/javascript" src="<?php echo $geowidget_src; ?>"></script>
        <a href="#" onclick="openMap(); return false;"><?php _e( 'Map', EasyPack::$text_domain ); ?></a>

        <script type="text/javascript">
            if ( jQuery().select2 ) {
                jQuery("#parcel_machine_id").select2();
                jQuery( window ).focus(function() {
					jQuery("#parcel_machine_id").change();
					setTimeout( function(){ jQuery("#parcel_machine_id").change(); }, 500 );
                })
            }
        </script>

	</td>
</tr>
