<?php
/**
 * Email After Order Table
 *
 * @author
 * @package 	EasyPack/Templates
 * @version
 */
?>
<br/>
<p>
	<?php printf( __( 'Package(s) %s was sent.' ), $package_numbers); ?>
	<a href="<?php echo $tracking_url; ?>"><?php _e( 'Track shipment', EasyPack::$text_domain ); ?></a>
	<img style="height"20px;" height="20" src="<?php echo $logo; ?>">
</p>
