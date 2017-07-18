<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$field = $this->get_field_key( $key );

?>

<tr valign="top">
	<th scope="row" class="titledesc">
		<label for="woocommerce_easypack_parcel_machines_rates"><?php echo $data['title']; ?></label>
	</th>
	<td class="forminp">
		<table id="<?php echo esc_attr( $field ); ?>" class="easypack_rates wc_input_table sortable widefat">
			<thead>
				<tr>
					<th class="sort">&nbsp;</th>
					<th><?php _e( 'Min', EasyPack::$text_domain ); ?></th>
					<th><?php _e( 'Max', EasyPack::$text_domain ); ?></th>
					<th><?php _e( 'Cost', EasyPack::$text_domain ); ?></th>
					<th><?php _e( 'Action', EasyPack::$text_domain ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php $count = 0; ?>
				<?php foreach ( $rates as $key => $rate) : $count++; ?>
					<tr>
						<td class="sort"></td>
						<td>
							<input class="input-text regular-input" type="number" style="" value="<?php echo $rate['min']; ?>" placeholder="0.00" step="any" min="0" name=rates['<?php echo $count; ?>'][min]>
						</td>
						<td>
							<input class="input-text regular-input" type="number" style="" value="<?php echo $rate['max']; ?>" placeholder="0.00" step="any" min="0" name=rates['<?php echo $count; ?>'][max]>
						</td>
						<td>
							<input class="input-text regular-input" type="number" style="" value="<?php echo $rate['cost']; ?>" placeholder="0.00" step="any" min="0" name=rates['<?php echo $count; ?>'][cost]>
						</td>
						<td>
							<a id="delete_rate_<?php echo $count; ?>" href="#" class="button delete_rate" data-id="<?php echo $count; ?>"><?php _e( 'Delete row', EasyPack::$text_domain ); ?></a>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
			<tfoot>
				<tr>
					<th colspan="5">
						<a id="insert_rate" href="#" class="button plus insert"><?php _e( 'Insert row', EasyPack::$text_domain ); ?></a>
					</th>
				</tr>
			</tfoot>
		</table>
		<script type="text/javascript">
			function append_row( id ) {
				var code = '<tr class="new">\
								<td class="sort"></td>\
								<td>\
									<input id="rates_'+id+'_min" class="input-text regular-input" type="number" style="" value="" placeholder="0.00" step="any" min="0" name=rates[' + id + '][min]>\
								</td>\
								<td>\
									<input class="input-text regular-input" type="number" style="" value="" placeholder="0.00" step="any" min="0" name=rates[' + id + '][max]>\
								</td>\
								<td>\
									<input class="input-text regular-input" type="number" style="" value="" placeholder="0.00" step="any" min="0" name=rates[' + id + '][cost]>\
								</td>\
								<td>\
									<a id="delete_rate_'+id+'" href="#" class="button delete_rate" data-id="'+id+'"><?php _e( 'Delete row', EasyPack::$text_domain ); ?></a>\
								</td>\
							</tr>';
				var $tbody = jQuery('.easypack_rates').find('tbody');
				$tbody.append( code );
			}
			jQuery(document).ready(function() {
				var $tbody = jQuery('.easypack_rates').find('tbody');
				var append_id = $tbody.find('tr').size();
				var size = $tbody.find('tr').size();
				if ( size == 0 ) {
					append_id = append_id+1;
					append_row(append_id);
				}
				jQuery('#insert_rate').click(function() {
					append_id = append_id+1;
					append_row(append_id);
					jQuery('#rates_'+append_id+'_min').focus();
					return false;
				});
				jQuery(document).on('click', '.delete_rate',  function() {
					if (confirm('<?php _e( 'Are you sure?' , EasyPack::$text_domain ); ?>')) {
						jQuery(this).closest('tr').remove();
					}
					return false;
				});
			});
		</script>
	</td>
</tr>



