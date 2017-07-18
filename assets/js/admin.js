jQuery(document).ready(function(){
	
	function easypack_dispatch_point() {
		if ( jQuery('#easypack_api_country').val() == 'pl' || jQuery('#easypack_api_country').val() == 'test-pl' ) {
			jQuery('#easypack_dispatch_point_name').closest('table').prev().css('display','block');
			jQuery('#easypack_dispatch_point_name').closest('table').css('display','table');
			jQuery('#easypack_crossborder_password').closest('tr').css('display','table-row');
		} 		
		else {
			jQuery('#easypack_dispatch_point_name').closest('table').prev().css('display','none');
			jQuery('#easypack_dispatch_point_name').closest('table').css('display','none');
			jQuery('#easypack_crossborder_api_url').closest('tr').css('display','none');
			jQuery('#easypack_crossborder_password').closest('tr').css('display','none');
			jQuery('#easypack_default_package_size').val('A');
			jQuery('#easypack_default_package_size').closest('tr').css('display','none');
		}
	}
	easypack_dispatch_point();
	
	jQuery('#easypack_api_country').change(function(){
		var url = 'https://api-'+jQuery('#easypack_api_country').val()+'.easypack24.net';
		url = url.replace("api-test","test-api");
		jQuery('#easypack_api_url').val(url);
		easypack_dispatch_point();
		easypack_api_change();
	})
	
	jQuery('#easypack_returns_page_create').click(function() {
		var country = jQuery('#easypack_api_country').val();
		//alert( country );
		var data = 	{
						action: 'easypack',
						easypack_action: 'returns_page_create',
						country: country,
						security: easypack_nonce,
					};
		jQuery.post(ajaxurl, data, function(response) {
			if ( response != 0 ) {
				response = JSON.parse(response);
				jQuery('#easypack_returns_page').append('<option value="'+response.page_id+'">'+response.page_title+'</option>');
				jQuery('#easypack_returns_page').val(response.page_id);
				jQuery('#easypack_returns_page').trigger('change');
				jQuery('#easypack_returns_page_create_message').html(response.message);
			}
		});
	})
	
	jQuery('#easypack_default_dispatch_point').change(function() {
		var data = 	{
						action: 'easypack',
						easypack_action: 'dispatch_point',
						security: easypack_nonce,
						dispatch_point_name: jQuery(this).val(),
					};
		jQuery.post(ajaxurl, data, function(response) {
			if ( response != 0 ) {
				response = JSON.parse(response);
				jQuery('#easypack_dispatch_point_name').val(response.name);
				jQuery('#easypack_dispatch_point_email').val(response.email);
				jQuery('#easypack_dispatch_point_phone').val(response.phone);
				jQuery('#easypack_dispatch_point_office_hours').val(response.office_hours);
				jQuery('#easypack_dispatch_point_street').val(response.address.street);
				jQuery('#easypack_dispatch_point_building_no').val(response.address.building_no);
				jQuery('#easypack_dispatch_point_flat_no').val(response.address.flat_no);
				jQuery('#easypack_dispatch_point_post_code').val(response.address.post_code);
				jQuery('#easypack_dispatch_point_city').val(response.address.city);
			}
		});
	})
	
/*	
	jQuery(".easypack_cancel_dispatch_order").click(function() {
		alert(1);
	})
*/	
})