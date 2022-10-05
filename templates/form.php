<?php
// Exit if access directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<!doctype html>
<html lang="en">
	<head>
		<title><?php _e( 'Process Donation with Arifpay payment gateways', 'give-arifpay' ); ?></title>
	</head>
	<body>
		<!-- Request -->
		<?php echo Give_Arifpay_API::get_form(); ?>

		<script language='javascript'>document.apayForm.submit();</script>
	</body>
</html>
