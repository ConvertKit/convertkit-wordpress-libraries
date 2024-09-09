<?php
/**
 * Tests for the ConvertKit_API class when WP_ENVIRONMENT_TYPE
 * is set to local.
 *
 * @since   2.0.2
 */
class APITestLocalEnv extends \Codeception\TestCase\WPTestCase
{
	/**
	 * The testing implementation.
	 *
	 * @var \WpunitTester.
	 */
	protected $tester;

	/**
	 * Holds the ConvertKit API class.
	 *
	 * @since   2.0.2
	 *
	 * @var     ConvertKit_API
	 */
	private $api;

	/**
	 * Holds a flag for marking a test as passed when using
	 * WordPress actions.
	 *
	 * @since   2.0.2
	 *
	 * @var     bool
	 */
	private $passed = false;

	/**
	 * Performs actions before each test.
	 *
	 * @since   1.0.0
	 */
	public function setUp(): void
	{
		parent::setUp();

		// Include class from /src to test.
		require_once 'src/class-convertkit-api-traits.php';
		require_once 'src/class-convertkit-api-v4.php';
		require_once 'src/class-convertkit-log.php';

		// Initialize the classes we want to test.
		$this->api = new ConvertKit_API_V4(
			$_ENV['CONVERTKIT_OAUTH_CLIENT_ID'],
			$_ENV['CONVERTKIT_OAUTH_REDIRECT_URI'],
			$_ENV['CONVERTKIT_OAUTH_ACCESS_TOKEN'],
			$_ENV['CONVERTKIT_OAUTH_REFRESH_TOKEN']
		);
	}

	/**
	 * Performs actions after each test.
	 *
	 * @since   1.0.0
	 */
	public function tearDown(): void
	{
		parent::tearDown();
	}

	/**
	 * Test that making a call with an expired access token results in refresh_token()
	 * not being automatically called, when the WordPress site isn't a production site.
	 *
	 * @since   2.0.2
	 *
	 * @return void
	 */
	public function testRefreshTokenWhenAccessTokenExpiredErrorOnNonProductionSite()
	{
		// Mark WordPress site as a non-production site.
		// @TODO.


		// If the refresh token action in the libraries is triggered when calling get_account(), the test failed.
		add_action(
			'convertkit_api_refresh_token',
			function() {
				$this->fail('`convertkit_api_refresh_token` was triggered when calling `get_account` with an expired access token on a non-production site.');
			}
		);

		// Filter requests to mock the token expiry and refreshing the token.
		add_filter( 'pre_http_request', array( $this, 'mockAccessTokenExpiredResponse' ), 10, 3 );
		add_filter( 'pre_http_request', array( $this, 'mockRefreshTokenResponse' ), 10, 3 );

		// Run request, which will trigger the above filters as if the token expired and refreshes automatically.
		$result = $this->api->get_account();
	}
}
