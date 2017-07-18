<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$field  = $this->get_field_key( $key );

?>

<tr valign="top">
	<th scope="row" class="titledesc">
		<label for="woocommerce_easypack--cross-border-parcel-machines_rates"><?php echo $data['title']; ?></label>
		<img class="help_tip" data-tip=" <?php _e( 'You can edit and set your own prices that will be seen when the customer is placing the order. If the products do not have a defined weight, the cost will be calculated incorrectly.', EasyPack::$text_domain ); ?>" src="<?php echo plugins_url( 'woocommerce/assets/images/help.png' ); ?>" height="16" width="16" />
	</th>
	<td class="forminp">
		<table id="<?php echo esc_attr( $field ); ?>" class="easypack_rates wc_input_table sortable widefat">
			<thead>
				<tr>
					<th class="country"><?php _e( 'Country', EasyPack::$text_domain ); ?></th>
					<th class="price"><?php _e( '1 kg', EasyPack::$text_domain ); ?></th>
					<th class="price"><?php _e( '2 kg', EasyPack::$text_domain ); ?></th>
					<th class="price"><?php _e( '5 kg', EasyPack::$text_domain ); ?></th>
					<th class="price"><?php _e( '10 kg', EasyPack::$text_domain ); ?></th>
					<th class="price"><?php _e( '15 kg', EasyPack::$text_domain ); ?></th>
					<th class="price"><?php _e( '20 kg', EasyPack::$text_domain ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php $count = 0; ?>
				<?php foreach ( $rates as $key => $rate) : $count++; ?>
					<tr>
						<td class="country">
							<strong><?php echo $countries[$key]; ?></strong>
						</td>
						<td class="price">
							<input class="input-text regular-input" type="number" style="" value="<?php echo $rate['kg_1']; ?>" placeholder="0.00" step="any" min="0" name=rates[<?php echo $key; ?>][kg_1]>
						</td>
						<td class="price">
							<input class="input-text regular-input" type="number" style="" value="<?php echo $rate['kg_2']; ?>" placeholder="0.00" step="any" min="0" name=rates[<?php echo $key; ?>][kg_2]>
						</td>
						<td class="price">
							<input class="input-text regular-input" type="number" style="" value="<?php echo $rate['kg_5']; ?>" placeholder="0.00" step="any" min="0" name=rates[<?php echo $key; ?>][kg_5]>
						</td>
						<td class="price">
							<input class="input-text regular-input" type="number" style="" value="<?php echo $rate['kg_10']; ?>" placeholder="0.00" step="any" min="0" name=rates[<?php echo $key; ?>][kg_10]>
						</td>
						<td class="price">
							<input class="input-text regular-input" type="number" style="" value="<?php echo $rate['kg_15']; ?>" placeholder="0.00" step="any" min="0" name=rates[<?php echo $key; ?>][kg_15]>
						</td>
						<td class="price">
							<input class="input-text regular-input" type="number" style="" value="<?php echo $rate['kg_20']; ?>" placeholder="0.00" step="any" min="0" name=rates[<?php echo $key; ?>][kg_20]>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
			<tfoot>
			</tfoot>
		</table>
	</td>
</tr>



