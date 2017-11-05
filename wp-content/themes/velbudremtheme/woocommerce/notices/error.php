<?php
/**
 * Show error messages
 *
 * @author 		WooThemes
 * @package 	WooCommerce/Templates
 * @version     1.6.4
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! $messages ){
	return;
}

?>
<ul class="woocommerce-error message-wrapper">
	<?php foreach ( $messages as $message ) : ?>
		<li><div class="message-container container alert-color medium-text-center"><span class="message-icon icon-close"></span> <?php echo wp_kses_post( $message ); ?></div></li>
	<?php endforeach; ?>
</ul>
