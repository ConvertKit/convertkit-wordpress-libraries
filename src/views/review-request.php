<?php
/**
 * Notification output for a review request.
 *
 * @package ConvertKit
 * @author ConvertKit
 */

?>

<div class="notice notice-info is-dismissible review-<?php echo esc_attr( $this->plugin_slug ); ?>">
	<p>
		<?php
		echo esc_html( $this->get_message_text() );
		?>
	</p>
	<p>
		<a href="<?php echo esc_attr( $this->get_review_url() ); ?>" class="button button-primary" rel="noopener" target="_blank">
			<?php echo esc_html( $this->get_leave_review_text() ); ?>
		</a>
		<a href="<?php echo esc_attr( $this->get_support_url() ); ?>" class="button" rel="noopener" target="_blank">
			<?php echo esc_html( $this->get_having_issues_text() ); ?>
		</a>
	</p>

	<script type="text/javascript">
		jQuery( document ).ready( function( $ ) {
			// Dismiss Review Notification.
			$( 'div.review-<?php echo esc_attr( $this->plugin_slug ); ?>' ).on( 'click', 'a, button.notice-dismiss', function( e ) {

				// Do request.
				$.post( 
					ajaxurl, 
					{
						action: '<?php echo esc_attr( str_replace( '-', '_', $this->plugin_slug ) ); ?>_dismiss_review',
					},
					function( response ) {
					}
				);

				// Hide notice.
				$( 'div.review-<?php echo esc_attr( $this->plugin_slug ); ?>' ).hide();

			} );
		} );
	</script>
</div>

