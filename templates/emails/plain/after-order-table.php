<?php
/**
 * Email After Order Table Plain text
 *
 * @author
 * @package 	EasyPack/Templates
 * @version
 */
?>
<?php
	echo "\n\n";
	printf( __( 'Package(s) %s was sent.' ), $package_numbers);
	_e( 'Track shipment: ', EasyPack::$text_domain );
	echo $tracking_url;
	echo "\n\n";
	?>
