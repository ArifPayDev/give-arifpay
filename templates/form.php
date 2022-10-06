<?php
// Exit if access directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<!doctype html>
<html lang="en">
	<head>
		<title><?php _e( 'Process Donation2222 with Arifpay payment gateways', 'give-arifpay' ); ?></title>
	</head>
	<body>
		<!-- Request -->
		<?php echo Give_Arifpay_API::get_form(); ?>

		<script language='javascript'>
		console.log("Here", document.apayForm)
		window.open(document.apayForm.getAttribute("action"))
		// document.apayForm.submit();
		</script>
	</body>
</html>
