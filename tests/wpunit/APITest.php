<?php
/**
 * Tests for the ConvertKit_API class.
 *
 * @since   1.0.0
 */
class APITest extends \Codeception\TestCase\WPTestCase
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
	 * @since   1.0.0
	 *
	 * @var     ConvertKit_API
	 */
	private $api;

	/**
	 * Holds the expected WP_Error code.
	 *
	 * @since   1.0.0
	 *
	 * @var     string
	 */
	private $errorCode = 'convertkit_api_error';

	/**
	 * Performs actions before each test.
	 *
	 * @since   1.0.0
	 */
	public function setUp(): void
	{
		parent::setUp();

		// Include class from /src to test.
		require_once 'src/class-convertkit-api.php';
		require_once 'src/class-convertkit-log.php';

		// Initialize the classes we want to test.
		$this->api         = new ConvertKit_API(
			$_ENV['CONVERTKIT_OAUTH_CLIENT_ID'],
			$_ENV['CONVERTKIT_OAUTH_REDIRECT_URI'],
			$_ENV['CONVERTKIT_OAUTH_ACCESS_TOKEN'],
			$_ENV['CONVERTKIT_OAUTH_REFRESH_TOKEN']
		);
		$this->api_no_data = new ConvertKit_API(
			$_ENV['CONVERTKIT_OAUTH_CLIENT_ID'],
			$_ENV['CONVERTKIT_OAUTH_REDIRECT_URI'],
			$_ENV['CONVERTKIT_OAUTH_ACCESS_TOKEN_NO_DATA'],
			$_ENV['CONVERTKIT_OAUTH_REFRESH_TOKEN_NO_DATA']
		);
	}

	/**
	 * Performs actions after each test.
	 *
	 * @since   1.0.0
	 */
	public function tearDown(): void
	{
		// Destroy the classes we tested.
		unset($this->api);
		unset($this->api_no_data);

		parent::tearDown();
	}

	/**
	 * Test that a log directory and file are created in the expected location, with .htaccess
	 * and index.html protection, and that the name and email addresses are masked.
	 *
	 * @since   1.4.2
	 */
	public function testLog()
	{
		$this->markTestIncomplete();

		// Define location for log file.
		define( 'CONVERTKIT_PLUGIN_PATH', $_ENV['WP_ROOT_FOLDER'] . '/wp-content/uploads' );

		// Create a log.txt file.
		$this->tester->writeToFile(CONVERTKIT_PLUGIN_PATH . '/log.txt', 'historical log file');

		// Initialize API.
		$api = new ConvertKit_API( $_ENV['CONVERTKIT_API_KEY'], $_ENV['CONVERTKIT_API_SECRET'], true );

		// Perform actions that will write sensitive data to the log file.
		$api->form_subscribe(
			$_ENV['CONVERTKIT_API_FORM_ID'],
			$_ENV['CONVERTKIT_API_SUBSCRIBER_EMAIL'],
			'First Name',
			array(
				'last_name' => 'Last',
			)
		);
		$api->profile($_ENV['CONVERTKIT_API_SIGNED_SUBSCRIBER_ID']);

		// Confirm the historical log.txt file has been deleted.
		$this->assertFileDoesNotExist(CONVERTKIT_PLUGIN_PATH . '/log.txt');

		// Confirm the .htaccess and index.html files exist.
		$this->assertDirectoryExists(CONVERTKIT_PLUGIN_PATH . '/log');
		$this->assertFileExists(CONVERTKIT_PLUGIN_PATH . '/log/.htaccess');
		$this->assertFileExists(CONVERTKIT_PLUGIN_PATH . '/log/index.html');
		$this->assertFileExists(CONVERTKIT_PLUGIN_PATH . '/log/log.txt');

		// Confirm the contents of the log file have masked the email address, name and signed subscriber ID.
		$this->tester->openFile(CONVERTKIT_PLUGIN_PATH . '/log/log.txt');
		$this->tester->seeInThisFile('API: form_subscribe(): [ form_id: ' . $_ENV['CONVERTKIT_API_FORM_ID'] . ', email: o****@n********.c**, first_name: ******Name ]');
		$this->tester->seeInThisFile('API: profile(): [ signed_subscriber_id: ********************');
		$this->tester->dontSeeInThisFile($_ENV['CONVERTKIT_API_SUBSCRIBER_EMAIL']);
		$this->tester->dontSeeInThisFile('First Name');
		$this->tester->dontSeeInThisFile($_ENV['CONVERTKIT_API_SIGNED_SUBSCRIBER_ID']);

		// Cleanup test.
		$this->tester->cleanDir(CONVERTKIT_PLUGIN_PATH . '/log');
		$this->tester->deleteDir(CONVERTKIT_PLUGIN_PATH . '/log');
	}

	/**
	 * Test that a 401 unauthorized error gracefully returns a WP_Error.
	 *
	 * @since   1.3.2
	 */
	public function test401Unauthorized()
	{
		$this->markTestIncomplete();

		$api    = new ConvertKit_API('fakeApiKey', 'fakeApiSecret');
		$result = $api->account();
		$this->assertInstanceOf(WP_Error::class, $result);
		$this->assertEquals($result->get_error_code(), $this->errorCode);
		$this->assertEquals($result->get_error_message(), 'Authorization Failed: API Key not valid');
		$this->assertEquals($result->get_error_data($result->get_error_code()), 401);
	}

	/**
	 * Test that a 429 internal server error gracefully returns a WP_Error.
	 *
	 * @since   1.0.0
	 */
	public function test429RateLimitHit()
	{
		$this->markTestIncomplete();

		// Force WordPress HTTP classes and functions to return a 429 error.
		$this->mockResponses( 429, 'Rate limit hit.' );
		$result = $this->api->account(); // The API function we use doesn't matter, as mockResponse forces a 429 error.
		$this->assertInstanceOf(WP_Error::class, $result);
		$this->assertEquals($result->get_error_code(), $this->errorCode);
		$this->assertEquals($result->get_error_message(), 'ConvertKit API Error: Rate limit hit.');
		$this->assertEquals($result->get_error_data($result->get_error_code()), 429);
	}

	/**
	 * Test that a 500 internal server error gracefully returns a WP_Error.
	 *
	 * @since   1.0.0
	 */
	public function test500InternalServerError()
	{
		$this->markTestIncomplete();

		// Force WordPress HTTP classes and functions to return a 500 error.
		$this->mockResponses( 500, 'Internal server error.' );
		$result = $this->api->account(); // The API function we use doesn't matter, as mockResponse forces a 500 error.
		$this->assertInstanceOf(WP_Error::class, $result);
		$this->assertEquals($result->get_error_code(), $this->errorCode);
		$this->assertEquals($result->get_error_message(), 'ConvertKit API Error: Internal server error.');
		$this->assertEquals($result->get_error_data($result->get_error_code()), 500);
	}

	/**
	 * Test that a 502 bad gateway gracefully returns a WP_Error.
	 *
	 * @since   1.0.0
	 */
	public function test502BadGateway()
	{
		$this->markTestIncomplete();

		// Force WordPress HTTP classes and functions to return a 502 error.
		$this->mockResponses( 502, 'Bad gateway.' );
		$result = $this->api->account(); // The API function we use doesn't matter, as mockResponse forces a 502 error.
		$this->assertInstanceOf(WP_Error::class, $result);
		$this->assertEquals($result->get_error_code(), $this->errorCode);
		$this->assertEquals($result->get_error_message(), 'ConvertKit API Error: Bad gateway.');
		$this->assertEquals($result->get_error_data($result->get_error_code()), 502);
	}

	/**
	 * Test that a response containing invalid JSON, resulting in json_decode() returning null,
	 * gracefully returns a WP_Error.
	 *
	 * @since   1.2.3
	 */
	public function testNullResponse()
	{
		$this->markTestIncomplete();

		// Force WordPress HTTP classes and functions to return an invalid JSON response.
		$this->mockResponses( 200, '', 'invalid JSON string' );
		$result = $this->api->get_posts(); // The API function we use doesn't matter.
		$this->assertInstanceOf(WP_Error::class, $result);
		$this->assertEquals($result->get_error_code(), $this->errorCode);
		$this->assertEquals($result->get_error_message(), 'ConvertKit API Error: The response is not of the expected type array.');
	}

	/**
	 * Test that the User Agent string is in the expected format when
	 * a context is provided.
	 *
	 * @since   1.2.0
	 */
	public function testUserAgentWithContext()
	{
		$this->markTestIncomplete();

		// When an API call is made, inspect the user-agent argument.
		add_filter(
			'http_request_args',
			function($args, $url) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter
				$this->assertStringContainsString(';context/TestContext', $args['user-agent']);
				return $args;
			},
			10,
			2
		);

		// Perform a request.
		$api    = new ConvertKit_API( $_ENV['CONVERTKIT_API_KEY'], $_ENV['CONVERTKIT_API_SECRET'], false, 'TestContext' );
		$result = $api->account();
	}

	/**
	 * Test that the User Agent string is in the expected format when
	 * no context is provided.
	 *
	 * @since   1.2.0
	 */
	public function testUserAgentWithoutContext()
	{
		$this->markTestIncomplete();

		// When an API call is made, inspect the user-agent argument.
		add_filter(
			'http_request_args',
			function($args, $url) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter
				$this->assertStringNotContainsString(';context/TestContext', $args['user-agent']);
				return $args;
			},
			10,
			2
		);

		// Perform a request.
		$result = $this->api->account();
	}

	/**
	 * Test that supplying invalid API credentials to the API class returns a WP_Error.
	 *
	 * @since   1.0.0
	 */
	public function testNoAPICredentials()
	{
		$this->markTestIncomplete();

		$api    = new ConvertKit_API();
		$result = $api->account();
		$this->assertInstanceOf(WP_Error::class, $result);
		$this->assertEquals($result->get_error_code(), $this->errorCode);
	}

	/**
	 * Test that supplying valid API credentials to the API class returns the expected account information.
	 *
	 * @since   1.0.0
	 */
	public function testAccount()
	{
		$result = $this->api->account();
		$this->assertNotInstanceOf(WP_Error::class, $result);
		$this->assertIsArray($result);
		$this->assertArrayHasKey('user', $result);
		$this->assertArrayHasKey('account', $result);
		$this->assertArrayHasKey('name', $result['account']);
		$this->assertArrayHasKey('plan_type', $result['account']);
		$this->assertArrayHasKey('primary_email_address', $result['account']);
		$this->assertEquals('wordpress@convertkit.com', $result['account']['primary_email_address']);
	}

	/**
	 * Test that the `get_subscription_forms()` function returns expected data.
	 *
	 * @since   1.0.0
	 */
	public function testGetSubscriptionForms()
	{
		$this->markTestIncomplete();

		$result = $this->api->get_subscription_forms();
		$this->assertNotInstanceOf(WP_Error::class, $result);
		$this->assertIsArray($result);
		$this->assertArrayHasKey('id', reset($result));
		$this->assertArrayHasKey('form_id', reset($result));
	}

	/**
	 * Test that the `get_subscription_forms()` function returns a blank array when no data
	 * exists on the ConvertKit account.
	 *
	 * @since   1.0.0
	 */
	public function testGetSubscriptionFormsNoData()
	{
		$this->markTestIncomplete();

		$result = $this->api_no_data->get_subscription_forms();
		$this->assertNotInstanceOf(WP_Error::class, $result);
		$this->assertIsArray($result);
		$this->assertCount(0, $result);
	}

	/**
	 * Test that the `get_forms()` function returns expected data.
	 *
	 * @since   1.0.0
	 */
	public function testGetForms()
	{
		$this->markTestIncomplete();

		$result = $this->api->get_forms();
		$this->assertNotInstanceOf(WP_Error::class, $result);
		$this->assertIsArray($result);
		$this->assertArrayHasKey('id', reset($result));
		$this->assertArrayHasKey('name', reset($result));
		$this->assertArrayHasKey('format', reset($result));
		$this->assertArrayHasKey('embed_js', reset($result));
	}

	/**
	 * Test that the `get_forms()` function returns a blank array when no data
	 * exists on the ConvertKit account.
	 *
	 * @since   1.0.0
	 */
	public function testGetFormsNoData()
	{
		$this->markTestIncomplete();

		$result = $this->api_no_data->get_forms();
		$this->assertNotInstanceOf(WP_Error::class, $result);
		$this->assertIsArray($result);
		$this->assertCount(0, $result);
	}

	/**
	 * Test that the `form_subscribe()` function returns expected data
	 * when valid parameters are provided.
	 *
	 * @since   1.0.0
	 */
	public function testFormSubscribe()
	{
		$this->markTestIncomplete();

		$result = $this->api->form_subscribe(
			$_ENV['CONVERTKIT_API_FORM_ID'],
			$_ENV['CONVERTKIT_API_SUBSCRIBER_EMAIL'],
			'First',
			array(
				'last_name'    => 'Last',
				'phone_number' => '123-456-7890',
			)
		);
		$this->assertNotInstanceOf(WP_Error::class, $result);
		$this->assertIsArray($result);
		$this->assertArrayHasKey('subscription', $result);
	}

	/**
	 * Test that the `form_subscribe()` function returns a WP_Error
	 * when an empty $form_id parameter is provided.
	 *
	 * @since   1.0.0
	 */
	public function testFormSubscribeWithEmptyFormID()
	{
		$this->markTestIncomplete();

		$result = $this->api->form_subscribe( '', $_ENV['CONVERTKIT_API_SUBSCRIBER_EMAIL'], 'First');
		$this->assertInstanceOf(WP_Error::class, $result);
		$this->assertEquals($result->get_error_code(), $this->errorCode);
		$this->assertEquals('form_subscribe(): the form_id parameter is empty.', $result->get_error_message());
	}

	/**
	 * Test that the `form_subscribe()` function returns a WP_Error
	 * when an invalid $form_id parameter is provided.
	 *
	 * @since   1.0.0
	 */
	public function testFormSubscribeWithInvalidFormID()
	{
		$this->markTestIncomplete();

		$result = $this->api->form_subscribe(12345, $_ENV['CONVERTKIT_API_SUBSCRIBER_EMAIL'], 'First');
		$this->assertInstanceOf(WP_Error::class, $result);
		$this->assertEquals($result->get_error_code(), $this->errorCode);
		$this->assertEquals('Not Found: The entity you were trying to find doesn\'t exist', $result->get_error_message());
	}

	/**
	 * Test that the `form_subscribe()` function returns a WP_Error
	 * when an empty $email parameter is provided.
	 *
	 * @since   1.0.0
	 */
	public function testFormSubscribeWithEmptyEmail()
	{
		$this->markTestIncomplete();

		$result = $this->api->form_subscribe($_ENV['CONVERTKIT_API_FORM_ID'], '', 'First');
		$this->assertInstanceOf(WP_Error::class, $result);
		$this->assertEquals($result->get_error_code(), $this->errorCode);
		$this->assertEquals('form_subscribe(): the email parameter is empty.', $result->get_error_message());
	}

	/**
	 * Test that the `form_subscribe()` function returns a WP_Error
	 * when the $email parameter only consists of spaces.
	 *
	 * @since   1.0.0
	 */
	public function testFormSubscribeWithSpacesInEmail()
	{
		$this->markTestIncomplete();

		$result = $this->api->form_subscribe( $_ENV['CONVERTKIT_API_FORM_ID'], '     ', 'First');
		$this->assertInstanceOf(WP_Error::class, $result);
		$this->assertEquals($result->get_error_code(), $this->errorCode);
		$this->assertEquals('form_subscribe(): the email parameter is empty.', $result->get_error_message());
	}

	/**
	 * Test that the `form_subscribe()` function returns a WP_Error
	 * when an invalid email parameter is provided.
	 *
	 * @since   1.0.0
	 */
	public function testFormSubscribeWithInvalidEmail()
	{
		$this->markTestIncomplete();

		$result = $this->api->form_subscribe( $_ENV['CONVERTKIT_API_FORM_ID'], 'invalid-email-address', 'First');
		$this->assertInstanceOf(WP_Error::class, $result);
		$this->assertEquals($result->get_error_code(), $this->errorCode);
		$this->assertEquals('Error updating subscriber: Email address is invalid', $result->get_error_message());
	}

	/**
	 * Test that the `get_landing_pages()` function returns expected data.
	 *
	 * @since   1.0.0
	 */
	public function testGetLandingPages()
	{
		$this->markTestIncomplete();

		$result = $this->api->get_landing_pages();
		$this->assertNotInstanceOf(WP_Error::class, $result);
		$this->assertIsArray($result);
		$this->assertArrayHasKey('id', reset($result));
		$this->assertArrayHasKey('name', reset($result));
		$this->assertArrayHasKey('format', reset($result));
		$this->assertArrayHasKey('embed_js', reset($result));
		$this->assertArrayHasKey('embed_url', reset($result));
	}

	/**
	 * Test that the `get_landing_pages()` function returns a blank array when no data
	 * exists on the ConvertKit account.
	 *
	 * @since   1.0.0
	 */
	public function testGetLandingPagesNoData()
	{
		$this->markTestIncomplete();

		$result = $this->api_no_data->get_landing_pages();
		$this->assertNotInstanceOf(WP_Error::class, $result);
		$this->assertIsArray($result);
		$this->assertCount(0, $result);
	}

	/**
	 * Test that the `get_sequences()` function returns expected data.
	 *
	 * @since   1.0.0
	 */
	public function testGetSequences()
	{
		$this->markTestIncomplete();

		$result = $this->api->get_sequences();
		$this->assertNotInstanceOf(WP_Error::class, $result);
		$this->assertIsArray($result);
		$this->assertArrayHasKey('id', reset($result));
		$this->assertArrayHasKey('name', reset($result));
	}

	/**
	 * Test that the `get_sequences()` function returns a blank array when no data
	 * exists on the ConvertKit account.
	 *
	 * @since   1.0.0
	 */
	public function testGetSequencesNoData()
	{
		$this->markTestIncomplete();

		$result = $this->api_no_data->get_sequences();
		$this->assertNotInstanceOf(WP_Error::class, $result);
		$this->assertIsArray($result);
		$this->assertCount(0, $result);
	}

	/**
	 * Test that the `sequence_subscribe()` function returns expected data
	 * when valid parameters are provided.
	 *
	 * @since   1.0.0
	 */
	public function testSequenceSubscribe()
	{
		$this->markTestIncomplete();

		$result = $this->api->sequence_subscribe(
			$_ENV['CONVERTKIT_API_SEQUENCE_ID'],
			$_ENV['CONVERTKIT_API_SUBSCRIBER_EMAIL'],
			'First',
			array(
				'last_name'    => 'Last',
				'phone_number' => '123-456-7890',
			)
		);
		$this->assertNotInstanceOf(WP_Error::class, $result);
		$this->assertIsArray($result);
		$this->assertArrayHasKey('subscription', $result);
	}

	/**
	 * Test that the `sequence_subscribe()` function returns a WP_Error
	 * when an invalid $sequence_id parameter is provided.
	 *
	 * @since   1.0.0
	 */
	public function testSequenceSubscribeWithInvalidSequenceID()
	{
		$this->markTestIncomplete();

		$result = $this->api->sequence_subscribe(12345, $_ENV['CONVERTKIT_API_SUBSCRIBER_EMAIL'], 'First');
		$this->assertInstanceOf(WP_Error::class, $result);
		$this->assertEquals($result->get_error_code(), $this->errorCode);
		$this->assertEquals('Course not found: ', $result->get_error_message());
	}

	/**
	 * Test that the `sequence_subscribe()` function returns a WP_Error
	 * when an empty $sequence_id parameter is provided.
	 *
	 * @since   1.0.0
	 */
	public function testSequenceSubscribeWithEmptySequenceID()
	{
		$this->markTestIncomplete();

		$result = $this->api->sequence_subscribe('', $_ENV['CONVERTKIT_API_SUBSCRIBER_EMAIL'], 'First');
		$this->assertInstanceOf(WP_Error::class, $result);
		$this->assertEquals($result->get_error_code(), $this->errorCode);
		$this->assertEquals('sequence_subscribe(): the sequence_id parameter is empty.', $result->get_error_message());
	}

	/**
	 * Test that the `sequence_subscribe()` function returns a WP_Error
	 * when an empty $email parameter is provided.
	 *
	 * @since   1.0.0
	 */
	public function testSequenceSubscribeWithEmptyEmail()
	{
		$this->markTestIncomplete();

		$result = $this->api->sequence_subscribe( $_ENV['CONVERTKIT_API_SEQUENCE_ID'], '', 'First');
		$this->assertInstanceOf(WP_Error::class, $result);
		$this->assertEquals($result->get_error_code(), $this->errorCode);
		$this->assertEquals('sequence_subscribe(): the email parameter is empty.', $result->get_error_message());
	}

	/**
	 * Test that the `sequence_subscribe()` function returns a WP_Error
	 * when the $email parameter only consists of spaces.
	 *
	 * @since   1.0.0
	 */
	public function testSequenceSubscribeWithSpacesInEmail()
	{
		$this->markTestIncomplete();

		$result = $this->api->sequence_subscribe($_ENV['CONVERTKIT_API_SEQUENCE_ID'], '     ', 'First');
		$this->assertInstanceOf(WP_Error::class, $result);
		$this->assertEquals($result->get_error_code(), $this->errorCode);
		$this->assertEquals('sequence_subscribe(): the email parameter is empty.', $result->get_error_message());
	}

	/**
	 * Test that the `sequence_subscribe()` function returns a WP_Error
	 * when an invalid $email parameter is provided.
	 *
	 * @since   1.0.0
	 */
	public function testSequenceSubscribeWithInvalidEmail()
	{
		$this->markTestIncomplete();

		$result = $this->api->sequence_subscribe($_ENV['CONVERTKIT_API_SEQUENCE_ID'], 'invalid-email-address', 'First');
		$this->assertInstanceOf(WP_Error::class, $result);
		$this->assertEquals($result->get_error_code(), $this->errorCode);
		$this->assertEquals('Error updating subscriber: Email address is invalid', $result->get_error_message());
	}

	/**
	 * Test that the `get_tags()` function returns expected data.
	 *
	 * @since   1.0.0
	 */
	public function testGetTags()
	{
		$this->markTestIncomplete();

		$result = $this->api->get_tags();
		$this->assertNotInstanceOf(WP_Error::class, $result);
		$this->assertIsArray($result);
		$this->assertArrayHasKey('id', reset($result));
		$this->assertArrayHasKey('name', reset($result));
	}

	/**
	 * Test that the `get_tags()` function returns a blank array when no data
	 * exists on the ConvertKit account.
	 *
	 * @since   1.0.0
	 */
	public function testGetTagsNoData()
	{
		$this->markTestIncomplete();

		$result = $this->api_no_data->get_tags();
		$this->assertNotInstanceOf(WP_Error::class, $result);
		$this->assertIsArray($result);
		$this->assertCount(0, $result);
	}

	/**
	 * Test that the `tag_subscribe()` function returns expected data
	 * when valid parameters are provided.
	 *
	 * @since   1.0.0
	 */
	public function testTagSubscribe()
	{
		$this->markTestIncomplete();

		$result = $this->api->tag_subscribe(
			$_ENV['CONVERTKIT_API_TAG_ID'],
			$_ENV['CONVERTKIT_API_SUBSCRIBER_EMAIL'],
			'First',
			array(
				'last_name'    => 'Last',
				'phone_number' => '123-456-7890',
			)
		);
		$this->assertNotInstanceOf(WP_Error::class, $result);
		$this->assertIsArray($result);
		$this->assertArrayHasKey('subscription', $result);
	}

	/**
	 * Test that the `tag_subscribe()` function returns a WP_Error
	 * when an invalid $tag_id parameter is provided.
	 *
	 * @since   1.0.0
	 */
	public function testTagSubscribeWithInvalidTagID()
	{
		$this->markTestIncomplete();

		$result = $this->api->tag_subscribe(12345, $_ENV['CONVERTKIT_API_SUBSCRIBER_EMAIL'], 'First');
		$this->assertInstanceOf(WP_Error::class, $result);
		$this->assertEquals($result->get_error_code(), $this->errorCode);
		$this->assertEquals('Tag not found: ', $result->get_error_message());
	}

	/**
	 * Test that the `tag_subscribe()` function returns a WP_Error
	 * when an empty $tag_id parameter is provided.
	 *
	 * @since   1.0.0
	 */
	public function testTagSubscribeWithEmptyTagID()
	{
		$this->markTestIncomplete();

		$result = $this->api->tag_subscribe('', $_ENV['CONVERTKIT_API_SUBSCRIBER_EMAIL'], 'First');
		$this->assertInstanceOf(WP_Error::class, $result);
		$this->assertEquals($result->get_error_code(), $this->errorCode);
		$this->assertEquals('tag_subscribe(): the tag_id parameter is empty.', $result->get_error_message());
	}

	/**
	 * Test that the `tag_subscribe()` function returns a WP_Error
	 * when an empty $email parameter is provided.
	 *
	 * @since   1.0.0
	 */
	public function testTagSubscribeWithEmptyEmail()
	{
		$this->markTestIncomplete();

		$result = $this->api->tag_subscribe($_ENV['CONVERTKIT_API_TAG_ID'], '', 'First');
		$this->assertInstanceOf(WP_Error::class, $result);
		$this->assertEquals($result->get_error_code(), $this->errorCode);
		$this->assertEquals('tag_subscribe(): the email parameter is empty.', $result->get_error_message());
	}

	/**
	 * Test that the `tag_subscribe()` function returns a WP_Error
	 * when the $email parameter only consists of spaces.
	 *
	 * @since   1.0.0
	 */
	public function testTagSubscribeWithSpacesInEmail()
	{
		$this->markTestIncomplete();

		$result = $this->api->tag_subscribe($_ENV['CONVERTKIT_API_TAG_ID'], '     ', 'First');
		$this->assertInstanceOf(WP_Error::class, $result);
		$this->assertEquals($result->get_error_code(), $this->errorCode);
		$this->assertEquals('tag_subscribe(): the email parameter is empty.', $result->get_error_message());
	}

	/**
	 * Test that the `tag_subscribe()` function returns a WP_Error
	 * when an invalid $email parameter is provided.
	 *
	 * @since   1.0.0
	 */
	public function testTagSubscribeWithInvalidEmail()
	{
		$this->markTestIncomplete();

		$result = $this->api->tag_subscribe($_ENV['CONVERTKIT_API_TAG_ID'], 'invalid-email-address', 'First');
		$this->assertInstanceOf(WP_Error::class, $result);
		$this->assertEquals($result->get_error_code(), $this->errorCode);
		$this->assertEquals('Error updating subscriber: Email address is invalid', $result->get_error_message());
	}


	/**
	 * Test that the `tag_unsubscribe()` function returns expected data
	 * when valid parameters are provided.
	 *
	 * @since   1.4.0
	 */
	public function testTagUnsubscribe()
	{
		$this->markTestIncomplete();

		// Subscribe the email address to the tag.
		$result = $this->api->tag_subscribe(
			$_ENV['CONVERTKIT_API_TAG_ID'],
			$_ENV['CONVERTKIT_API_SUBSCRIBER_EMAIL']
		);

		// Unsubscribe the email address from the tag.
		$result = $this->api->tag_unsubscribe(
			$_ENV['CONVERTKIT_API_TAG_ID'],
			$_ENV['CONVERTKIT_API_SUBSCRIBER_EMAIL']
		);

		$this->assertNotInstanceOf(WP_Error::class, $result);
		$this->assertIsArray($result);
		$this->assertArrayHasKey('id', $result);
		$this->assertArrayHasKey('name', $result);
		$this->assertArrayHasKey('created_at', $result);
		$this->assertEquals($result['name'], $_ENV['CONVERTKIT_API_TAG_NAME']);
	}

	/**
	 * Test that the `tag_unsubscribe()` function returns a WP_Error
	 * when an invalid $tag_id parameter is provided.
	 *
	 * @since   1.4.0
	 */
	public function testTagUnsubscribeWithInvalidTagID()
	{
		$this->markTestIncomplete();

		$result = $this->api->tag_unsubscribe(12345, $_ENV['CONVERTKIT_API_SUBSCRIBER_EMAIL']);
		$this->assertInstanceOf(WP_Error::class, $result);
		$this->assertEquals($result->get_error_code(), $this->errorCode);
		$this->assertEquals('Not Found: The entity you were trying to find doesn\'t exist', $result->get_error_message());
	}

	/**
	 * Test that the `tag_unsubscribe()` function returns a WP_Error
	 * when an empty $tag_id parameter is provided.
	 *
	 * @since   1.4.0
	 */
	public function testTagUnsubscribeWithEmptyTagID()
	{
		$this->markTestIncomplete();

		$result = $this->api->tag_unsubscribe('', $_ENV['CONVERTKIT_API_SUBSCRIBER_EMAIL']);
		$this->assertInstanceOf(WP_Error::class, $result);
		$this->assertEquals($result->get_error_code(), $this->errorCode);
		$this->assertEquals('tag_unsubscribe(): the tag_id parameter is empty.', $result->get_error_message());
	}

	/**
	 * Test that the `tag_unsubscribe()` function returns a WP_Error
	 * when an empty $email parameter is provided.
	 *
	 * @since   1.4.0
	 */
	public function testTagUnsubscribeWithEmptyEmail()
	{
		$this->markTestIncomplete();

		$result = $this->api->tag_unsubscribe($_ENV['CONVERTKIT_API_TAG_ID'], '');
		$this->assertInstanceOf(WP_Error::class, $result);
		$this->assertEquals($result->get_error_code(), $this->errorCode);
		$this->assertEquals('tag_unsubscribe(): the email parameter is empty.', $result->get_error_message());
	}

	/**
	 * Test that the `tag_unsubscribe()` function returns a WP_Error
	 * when the $email parameter only consists of spaces.
	 *
	 * @since   1.4.0
	 */
	public function testTagUnsubscribeWithSpacesInEmail()
	{
		$this->markTestIncomplete();

		$result = $this->api->tag_unsubscribe($_ENV['CONVERTKIT_API_TAG_ID'], '     ');
		$this->assertInstanceOf(WP_Error::class, $result);
		$this->assertEquals($result->get_error_code(), $this->errorCode);
		$this->assertEquals('tag_unsubscribe(): the email parameter is empty.', $result->get_error_message());
	}

	/**
	 * Test that the `tag_unsubscribe()` function returns a WP_Error
	 * when an invalid $email parameter is provided.
	 *
	 * @since   1.4.0
	 */
	public function testTagUnsubscribeWithInvalidEmail()
	{
		$this->markTestIncomplete();

		$result = $this->api->tag_unsubscribe($_ENV['CONVERTKIT_API_TAG_ID'], 'invalid-email-address');
		$this->assertInstanceOf(WP_Error::class, $result);
		$this->assertEquals($result->get_error_code(), $this->errorCode);
		$this->assertEquals('tag_unsubscribe(): the email parameter is not a valid email address.', $result->get_error_message());
	}

	/**
	 * Test that the `get_subscriber_by_email()` function returns expected data
	 * when valid parameters are provided.
	 *
	 * @since   1.0.0
	 */
	public function testGetSubscriberByEmail()
	{
		$this->markTestIncomplete();

		$result = $this->api->get_subscriber_by_email($_ENV['CONVERTKIT_API_SUBSCRIBER_EMAIL']);
		$this->assertNotInstanceOf(WP_Error::class, $result);
		$this->assertIsArray($result);
		$this->assertArrayHasKey('id', $result);
		$this->assertEquals($result['id'], $_ENV['CONVERTKIT_API_SUBSCRIBER_ID']);
	}

	/**
	 * Test that the `get_subscriber_by_email()` function returns a WP_Error
	 * when an empty $email parameter is provided.
	 *
	 * @since   1.0.0
	 */
	public function testGetSubscriberByEmailWithEmptyEmail()
	{
		$this->markTestIncomplete();

		$result = $this->api->get_subscriber_by_email('');
		$this->assertInstanceOf(WP_Error::class, $result);
		$this->assertEquals($result->get_error_code(), $this->errorCode);
		$this->assertEquals('get_subscriber_by_email(): the email parameter is empty.', $result->get_error_message());
	}

	/**
	 * Test that the `get_subscriber_by_email()` function returns a WP_Error
	 * when an invalid $email parameter is provided.
	 *
	 * @since   1.0.0
	 */
	public function testGetSubscriberByEmailWithInvalidEmail()
	{
		$this->markTestIncomplete();

		$result = $this->api->get_subscriber_by_email('invalid-email-address');
		$this->assertInstanceOf(WP_Error::class, $result);
		$this->assertEquals($result->get_error_code(), $this->errorCode);
		$this->assertEquals('No subscriber(s) exist in ConvertKit matching the email address invalid-email-address.', $result->get_error_message());
	}

	/**
	 * Test that the `get_subscriber_by_id()` function returns expected data
	 * when valid parameters are provided.
	 *
	 * @since   1.0.0
	 */
	public function testGetSubscriberByID()
	{
		$this->markTestIncomplete();

		$result = $this->api->get_subscriber_by_id($_ENV['CONVERTKIT_API_SUBSCRIBER_ID']);
		$this->assertNotInstanceOf(WP_Error::class, $result);
		$this->assertIsArray($result);
		$this->assertArrayHasKey('id', $result);
		$this->assertEquals($result['id'], $_ENV['CONVERTKIT_API_SUBSCRIBER_ID']);
	}

	/**
	 * Test that the `get_subscriber_by_id()` function returns a WP_Error
	 * when an empty ID parameter is provided.
	 *
	 * @since   1.0.0
	 */
	public function testGetSubscriberByIDWithEmptyID()
	{
		$this->markTestIncomplete();

		$result = $this->api->get_subscriber_by_id('');
		$this->assertInstanceOf(WP_Error::class, $result);
		$this->assertEquals($result->get_error_code(), $this->errorCode);
		$this->assertEquals('get_subscriber_by_id(): the subscriber_id parameter is empty.', $result->get_error_message());
	}

	/**
	 * Test that the `get_subscriber_by_id()` function returns a WP_Error
	 * when an invalid ID parameter is provided.
	 *
	 * @since   1.0.0
	 */
	public function testGetSubscriberByIDWithInvalidID()
	{
		$this->markTestIncomplete();

		$result = $this->api->get_subscriber_by_id(12345);
		$this->assertInstanceOf(WP_Error::class, $result);
		$this->assertEquals($result->get_error_code(), $this->errorCode);
		$this->assertEquals('Not Found: The entity you were trying to find doesn\'t exist', $result->get_error_message());
	}

	/**
	 * Test that the `get_subscriber_tags()` function returns expected data
	 * when valid parameters are provided.
	 *
	 * @since   1.0.0
	 */
	public function testGetSubscriberTags()
	{
		$this->markTestIncomplete();

		// Subscribe the email address to the tag.
		$result = $this->api->tag_subscribe(
			$_ENV['CONVERTKIT_API_TAG_ID'],
			$_ENV['CONVERTKIT_API_SUBSCRIBER_EMAIL']
		);

		$result = $this->api->get_subscriber_tags($_ENV['CONVERTKIT_API_SUBSCRIBER_ID']);
		$this->assertNotInstanceOf(WP_Error::class, $result);
		$this->assertIsArray($result);

		// Subscriber may have multiple tags due to API tests running across different
		// Plugins. Check that a matching tag in the array of tags exists.
		$tagMatches = false;
		foreach ($result as $tag) {
			$this->assertArrayHasKey('id', $tag);
			$this->assertArrayHasKey('name', $tag);

			if ($tag['id'] === (int) $_ENV['CONVERTKIT_API_TAG_ID']) {
				$tagMatches = true;
				break;
			}
		}

		$this->assertTrue($tagMatches);
	}

	/**
	 * Test that the `get_subscriber_by_id()` function returns a WP_Error
	 * when an empty $id parameter is provided.
	 *
	 * @since   1.0.0
	 */
	public function testGetSubscriberTagsWithEmptyID()
	{
		$this->markTestIncomplete();

		$result = $this->api->get_subscriber_tags('');
		$this->assertInstanceOf(WP_Error::class, $result);
		$this->assertEquals($result->get_error_code(), $this->errorCode);
		$this->assertEquals('get_subscriber_tags(): the subscriber_id parameter is empty.', $result->get_error_message());
	}

	/**
	 * Test that the `get_subscriber_by_id()` function returns a WP_Error
	 * when an invalid $id parameter is provided.
	 *
	 * @since   1.0.0
	 */
	public function testGetSubscriberTagsWithInvalidID()
	{
		$this->markTestIncomplete();

		$result = $this->api->get_subscriber_tags(12345);
		$this->assertInstanceOf(WP_Error::class, $result);
		$this->assertEquals($result->get_error_code(), $this->errorCode);
		$this->assertEquals('Not Found: The entity you were trying to find doesn\'t exist', $result->get_error_message());
	}

	/**
	 * Test that the `get_subscriber_id()` function returns expected data
	 * when valid parameters are provided.
	 *
	 * @since   1.0.0
	 */
	public function testGetSubscriberID()
	{
		$this->markTestIncomplete();

		$result = $this->api->get_subscriber_id($_ENV['CONVERTKIT_API_SUBSCRIBER_EMAIL']);
		$this->assertNotInstanceOf(WP_Error::class, $result);
		$this->assertEquals($_ENV['CONVERTKIT_API_SUBSCRIBER_ID'], $result);
	}

	/**
	 * Test that the `get_subscriber_id()` function returns a WP_Error
	 * when an empty $email parameter is provided.
	 *
	 * @since   1.0.0
	 */
	public function testGetSubscriberIDWithEmptyEmail()
	{
		$this->markTestIncomplete();

		$result = $this->api->get_subscriber_id('');
		$this->assertInstanceOf(WP_Error::class, $result);
		$this->assertEquals($result->get_error_code(), $this->errorCode);

		// get_subscriber_by_email() is deliberate in this error message, as get_subscriber_id() calls get_subscriber_by_email().
		$this->assertEquals('get_subscriber_by_email(): the email parameter is empty.', $result->get_error_message());
	}

	/**
	 * Test that the `get_subscriber_id()` function returns a WP_Error
	 * when an invalid $email parameter is provided.
	 *
	 * @since   1.0.0
	 */
	public function testGetSubscriberIDWithInvalidEmail()
	{
		$this->markTestIncomplete();

		$result = $this->api->get_subscriber_id('invalid-email-address');
		$this->assertInstanceOf(WP_Error::class, $result);
		$this->assertEquals($result->get_error_code(), $this->errorCode);
		$this->assertEquals('No subscriber(s) exist in ConvertKit matching the email address invalid-email-address.', $result->get_error_message());
	}

	/**
	 * Test that the `unsubscribe()` function returns expected data
	 * when valid parameters are provided.
	 *
	 * @since   1.0.0
	 */
	public function testUnsubscribe()
	{
		$this->markTestIncomplete();

		// We don't use $_ENV['CONVERTKIT_API_SUBSCRIBER_EMAIL'] for this test, as that email is relied upon as being a confirmed subscriber
		// for other tests.

		// Subscribe an email address.
		$emailAddress = 'wordpress-' . date( 'Y-m-d-H-i-s' ) . '-php-' . PHP_VERSION_ID . '@convertkit.com';
		$this->api->form_subscribe($_ENV['CONVERTKIT_API_FORM_ID'], $emailAddress);

		// Unsubscribe the email address.
		$result = $this->api->unsubscribe($emailAddress);
		$this->assertNotInstanceOf(WP_Error::class, $result);
		$this->assertIsArray($result);
		$this->assertArrayHasKey('subscriber', $result);
		$this->assertArrayHasKey('email_address', $result['subscriber']);
		$this->assertEquals($emailAddress, $result['subscriber']['email_address']);
	}

	/**
	 * Test that the `unsubscribe()` function returns a WP_Error
	 * when an empty $email parameter is provided.
	 *
	 * @since   1.0.0
	 */
	public function testUnsubscribeWithEmptyEmail()
	{
		$this->markTestIncomplete();

		$result = $this->api->unsubscribe('');
		$this->assertInstanceOf(WP_Error::class, $result);
		$this->assertEquals($result->get_error_code(), $this->errorCode);
		$this->assertEquals('unsubscribe(): the email parameter is empty.', $result->get_error_message());
	}

	/**
	 * Test that the `unsubscribe()` function returns a WP_Error
	 * when an invalid $email parameter is provided.
	 *
	 * @since   1.0.0
	 */
	public function testUnsubscribeWithInvalidEmail()
	{
		$this->markTestIncomplete();

		$result = $this->api->unsubscribe('invalid-email-address');
		$this->assertInstanceOf(WP_Error::class, $result);
		$this->assertEquals($result->get_error_code(), $this->errorCode);
		$this->assertEquals('Not Found: The entity you were trying to find doesn\'t exist', $result->get_error_message());
	}

	/**
	 * Test that broadcast_create() and broadcast_delete() works when valid parameters are specified.
	 *
	 * We do all tests in a single function, so we don't end up with unnecessary Broadcasts remaining
	 * on the ConvertKit account when running tests, which might impact
	 * other tests that expect (or do not expect) specific Broadcasts.
	 *
	 * @since   1.3.9
	 */
	public function testCreateAndDeleteDraftBroadcast()
	{
		$this->markTestIncomplete();

		// Create a broadcast first.
		$result = $this->api->broadcast_create(
			'Test Subject',
			'Test Content',
			'Test Broadcast from WordPress Libraries',
		);

		// Confirm the Broadcast saved.
		$this->assertArrayHasKey('id', $result);
		$this->assertEquals('Test Subject', $result['subject']);
		$this->assertEquals('Test Content', $result['content']);
		$this->assertEquals('Test Broadcast from WordPress Libraries', $result['description']);
		$this->assertEquals(null, $result['published_at']);
		$this->assertEquals(null, $result['send_at']);

		// Delete the broadcast.
		$this->api->broadcast_delete($result['id']);
	}

	/**
	 * Test that broadcast_create() and broadcast_delete() works when valid published_at and send_at
	 * parameters are specified.
	 *
	 * We do all tests in a single function, so we don't end up with unnecessary Broadcasts remaining
	 * on the ConvertKit account when running tests, which might impact
	 * other tests that expect (or do not expect) specific Broadcasts.
	 *
	 * @since   1.3.9
	 */
	public function testCreateAndDeletePublicBroadcastWithValidDates()
	{
		$this->markTestIncomplete();

		// Create DateTime object.
		$publishedAt = new \DateTime('now');
		$publishedAt->modify('+7 days');
		$sendAt = new \DateTime('now');
		$sendAt->modify('+14 days');

		// Create a broadcast first.
		$result = $this->api->broadcast_create(
			'Test Subject',
			'Test Content',
			'Test Broadcast from WordPress Libraries',
			true,
			$publishedAt,
			$sendAt
		);

		// Confirm the Broadcast saved.
		$this->assertArrayHasKey('id', $result);
		$this->assertEquals('Test Subject', $result['subject']);
		$this->assertEquals('Test Content', $result['content']);
		$this->assertEquals('Test Broadcast from WordPress Libraries', $result['description']);
		$this->assertEquals(
			$publishedAt->format('Y-m-d') . 'T' . $publishedAt->format('H:i:s') . '.000Z',
			$result['published_at']
		);
		$this->assertEquals(
			$sendAt->format('Y-m-d') . 'T' . $sendAt->format('H:i:s') . '.000Z',
			$result['send_at']
		);

		// Delete the broadcast.
		$this->api->broadcast_delete($result['id']);
	}

	/**
	 * Test that the `broadcast_delete()` function returns a WP_Error
	 * when no $broadcast_id parameter is provided.
	 *
	 * @since   1.3.9
	 */
	public function testDeleteBroadcastWithNoBroadcastID()
	{
		$this->markTestIncomplete();

		$result = $this->api->broadcast_delete('');
		$this->assertInstanceOf(WP_Error::class, $result);
		$this->assertEquals($result->get_error_code(), $this->errorCode);
		$this->assertEquals('broadcast_delete(): the broadcast_id parameter is empty.', $result->get_error_message());
	}

	/**
	 * Test that the `broadcast_delete()` function returns a WP_Error
	 * when an invalid $broadcast_id parameter is provided.
	 *
	 * @since   1.3.9
	 */
	public function testDeleteBroadcastWithInvalidBroadcastID()
	{
		$this->markTestIncomplete();

		$result = $this->api->broadcast_delete(12345);
		$this->assertInstanceOf(WP_Error::class, $result);
		$this->assertEquals($result->get_error_code(), $this->errorCode);
		$this->assertEquals('Not Found: The entity you were trying to find doesn\'t exist', $result->get_error_message());
	}

	/**
	 * Test that the `get_custom_fields()` function returns expected data.
	 *
	 * @since   1.0.0
	 */
	public function testGetCustomFields()
	{
		$this->markTestIncomplete();

		$result = $this->api->get_custom_fields();
		$this->assertNotInstanceOf(WP_Error::class, $result);
		$this->assertIsArray($result);
		$this->assertArrayHasKey('id', reset($result));
		$this->assertArrayHasKey('name', reset($result));
	}

	/**
	 * Test that the `get_custom_fields()` function returns a blank array when no data
	 * exists on the ConvertKit account.
	 *
	 * @since   1.0.0
	 */
	public function testGetCustomFieldsNoData()
	{
		$this->markTestIncomplete();

		$result = $this->api_no_data->get_custom_fields();
		$this->assertNotInstanceOf(WP_Error::class, $result);
		$this->assertIsArray($result);
		$this->assertCount(0, $result);
	}

	/**
	 * Test that the `get_posts()` function returns expected data.
	 *
	 * @since   1.0.0
	 */
	public function testGetPosts()
	{
		$this->markTestIncomplete();

		$result = $this->api->get_posts();

		// Test array was returned.
		$this->assertNotInstanceOf(WP_Error::class, $result);
		$this->assertIsArray($result);

		// Test expected response keys exist.
		$this->assertArrayHasKey('total_posts', $result);
		$this->assertArrayHasKey('page', $result);
		$this->assertArrayHasKey('total_pages', $result);
		$this->assertArrayHasKey('posts', $result);

		// Test first post within posts array.
		$this->assertArrayHasKey('id', reset($result['posts']));
		$this->assertArrayHasKey('title', reset($result['posts']));
		$this->assertArrayHasKey('url', reset($result['posts']));
		$this->assertArrayHasKey('published_at', reset($result['posts']));
		$this->assertArrayHasKey('is_paid', reset($result['posts']));
	}

	/**
	 * Test that the `get_posts()` function returns a blank array when no data
	 * exists on the ConvertKit account.
	 *
	 * @since   1.0.0
	 */
	public function testGetPostsNoData()
	{
		$this->markTestIncomplete();

		$result = $this->api_no_data->get_posts();
		$this->assertNotInstanceOf(WP_Error::class, $result);
		$this->assertIsArray($result);
		$this->assertCount(0, $result);
	}

	/**
	 * Test that the `get_posts()` function returns expected data
	 * when valid parameters are included.
	 *
	 * @since   1.0.0
	 */
	public function testGetPostsWithValidParameters()
	{
		$this->markTestIncomplete();

		$result = $this->api->get_posts(1, 2);

		// Test array was returned.
		$this->assertNotInstanceOf(WP_Error::class, $result);
		$this->assertIsArray($result);

		// Test expected response keys exist.
		$this->assertArrayHasKey('total_posts', $result);
		$this->assertArrayHasKey('page', $result);
		$this->assertArrayHasKey('total_pages', $result);
		$this->assertArrayHasKey('posts', $result);

		// Test expected number of posts returned.
		$this->assertCount(2, $result['posts']);

		// Test first post within posts array.
		$this->assertArrayHasKey('id', reset($result['posts']));
		$this->assertArrayHasKey('title', reset($result['posts']));
		$this->assertArrayHasKey('url', reset($result['posts']));
		$this->assertArrayHasKey('published_at', reset($result['posts']));
		$this->assertArrayHasKey('is_paid', reset($result['posts']));
	}

	/**
	 * Test that the `get_posts()` function returns an error
	 * when the page parameter is less than 1.
	 *
	 * @since   1.0.0
	 */
	public function testGetPostsWithInvalidPageParameter()
	{
		$this->markTestIncomplete();

		$result = $this->api->get_posts(0);
		$this->assertInstanceOf(WP_Error::class, $result);
		$this->assertEquals($result->get_error_code(), $this->errorCode);
		$this->assertEquals('get_posts(): the page parameter must be equal to or greater than 1.', $result->get_error_message());
	}

	/**
	 * Test that the `get_posts()` function returns an error
	 * when the per_page parameter is less than 1.
	 *
	 * @since   1.0.0
	 */
	public function testGetPostsWithNegativePerPageParameter()
	{
		$this->markTestIncomplete();

		$result = $this->api->get_posts(1, 0);
		$this->assertInstanceOf(WP_Error::class, $result);
		$this->assertEquals($result->get_error_code(), $this->errorCode);
		$this->assertEquals('get_posts(): the per_page parameter must be equal to or greater than 1.', $result->get_error_message());
	}

	/**
	 * Test that the `get_posts()` function returns an error
	 * when the per_page parameter is greater than 50.
	 *
	 * @since   1.0.0
	 */
	public function testGetPostsWithOutOfBoundsPerPageParameter()
	{
		$this->markTestIncomplete();

		$result = $this->api->get_posts(1, 100);
		$this->assertInstanceOf(WP_Error::class, $result);
		$this->assertEquals($result->get_error_code(), $this->errorCode);
		$this->assertEquals('get_posts(): the per_page parameter must be equal to or less than 50.', $result->get_error_message());
	}

	/**
	 * Test that the `get_all_posts()` function returns expected data.
	 *
	 * @since   1.0.0
	 */
	public function testGetAllPosts()
	{
		$this->markTestIncomplete();

		$result = $this->api->get_all_posts();
		$this->assertNotInstanceOf(WP_Error::class, $result);
		$this->assertIsArray($result);
		$this->assertArrayHasKey('id', reset($result));
		$this->assertArrayHasKey('title', reset($result));
		$this->assertArrayHasKey('url', reset($result));
		$this->assertArrayHasKey('published_at', reset($result));
		$this->assertArrayHasKey('is_paid', reset($result));
	}

	/**
	 * Test that the `get_all_posts()` function returns a blank array when no data
	 * exists on the ConvertKit account.
	 *
	 * @since   1.0.0
	 */
	public function testGetAllPostsNoData()
	{
		$this->markTestIncomplete();

		$result = $this->api_no_data->get_all_posts();
		$this->assertNotInstanceOf(WP_Error::class, $result);
		$this->assertIsArray($result);
		$this->assertCount(0, $result);
	}

	/**
	 * Test that the `get_all_posts()` function returns expected data
	 * when valid parameters are included.
	 *
	 * @since   1.0.0
	 */
	public function testGetAllPostsWithValidParameters()
	{
		$this->markTestIncomplete();

		$result = $this->api->get_all_posts(2); // Number of posts to fetch in each request within the function.
		$this->assertNotInstanceOf(WP_Error::class, $result);
		$this->assertIsArray($result);
		$this->assertCount(4, $result);
		$this->assertArrayHasKey('id', reset($result));
		$this->assertArrayHasKey('title', reset($result));
		$this->assertArrayHasKey('url', reset($result));
		$this->assertArrayHasKey('published_at', reset($result));
		$this->assertArrayHasKey('is_paid', reset($result));
	}

	/**
	 * Test that the `get_all_posts()` function returns an error
	 * when the page parameter is less than 1.
	 *
	 * @since   1.0.0
	 */
	public function testGetAllPostsWithInvalidPostsPerRequestParameter()
	{
		$this->markTestIncomplete();

		// Test with a number less than 1.
		$result = $this->api->get_all_posts(0);
		$this->assertInstanceOf(WP_Error::class, $result);
		$this->assertEquals($result->get_error_code(), $this->errorCode);
		$this->assertEquals('get_all_posts(): the posts_per_request parameter must be equal to or greater than 1.', $result->get_error_message());

		// Test with a number greater than 50.
		$result = $this->api->get_all_posts(51);
		$this->assertInstanceOf(WP_Error::class, $result);
		$this->assertEquals($result->get_error_code(), $this->errorCode);
		$this->assertEquals('get_all_posts(): the posts_per_request parameter must be equal to or less than 50.', $result->get_error_message());
	}

	/**
	 * Test that the `get_post()` function returns expected data.
	 *
	 * @since   1.3.8
	 */
	public function testGetPostByID()
	{
		$this->markTestIncomplete();

		$result = $this->api->get_post($_ENV['CONVERTKIT_API_POST_ID']);
		$this->assertNotInstanceOf(WP_Error::class, $result);
		$this->assertIsArray($result);
		$this->assertArrayHasKey('id', $result);
		$this->assertArrayHasKey('title', $result);
		$this->assertArrayHasKey('description', $result);
		$this->assertArrayHasKey('published_at', $result);
		$this->assertArrayHasKey('is_paid', $result);
		$this->assertArrayHasKey('thumbnail_alt', $result);
		$this->assertArrayHasKey('thumbnail_url', $result);
		$this->assertArrayHasKey('url', $result);
		$this->assertArrayHasKey('product_id', $result);
		$this->assertArrayHasKey('content', $result);
	}

	/**
	 * Test that the `get_post()` function returns a WP_Error when an invalid
	 * Post ID is specified.
	 *
	 * @since   1.3.8
	 */
	public function testGetPostByInvalidID()
	{
		$this->markTestIncomplete();

		$result = $this->api->get_post(12345);
		$this->assertInstanceOf(WP_Error::class, $result);
		$this->assertEquals($result->get_error_code(), $this->errorCode);
		$this->assertEquals('Post not found', $result->get_error_message());
	}

	/**
	 * Test that the `get_products()` function returns expected data.
	 *
	 * @since   1.1.0
	 */
	public function testGetProducts()
	{
		$this->markTestIncomplete();

		$result = $this->api->get_products();
		$this->assertNotInstanceOf(WP_Error::class, $result);
		$this->assertIsArray($result);
		$this->assertArrayHasKey('id', reset($result));
		$this->assertArrayHasKey('name', reset($result));
		$this->assertArrayHasKey('url', reset($result));
		$this->assertArrayHasKey('published', reset($result));
	}

	/**
	 * Test that the `get_products()` function returns a blank array when no data
	 * exists on the ConvertKit account.
	 *
	 * @since   1.1.0
	 */
	public function testGetProductsNoData()
	{
		$this->markTestIncomplete();

		$result = $this->api_no_data->get_forms();
		$this->assertNotInstanceOf(WP_Error::class, $result);
		$this->assertIsArray($result);
		$this->assertCount(0, $result);
	}

	/**
	 * Test that the `subscriber_authentication_send_code()` function returns the expected
	 * response when a valid email subscriber is specified.
	 *
	 * @since   1.3.0
	 */
	public function testSubscriberAuthenticationSendCodeWithSubscribedEmail()
	{
		$this->markTestIncomplete();

		$result = $this->api->subscriber_authentication_send_code(
			$_ENV['CONVERTKIT_API_SUBSCRIBER_EMAIL'],
			$_ENV['TEST_SITE_WP_URL']
		);
		$this->assertNotInstanceOf(WP_Error::class, $result);
	}

	/**
	 * Test that the `subscriber_authentication_send_code()` function returns the expected
	 * response when an email address is specified that is not a subscriber in ConvertKit.
	 *
	 * @since   1.3.0
	 */
	public function testSubscriberAuthenticationSendCodeWithNotSubscribedEmail()
	{
		$this->markTestIncomplete();

		$result = $this->api->subscriber_authentication_send_code(
			'email-not-subscribed@convertkit.com',
			$_ENV['TEST_SITE_WP_URL']
		);
		$this->assertInstanceOf(WP_Error::class, $result);
		$this->assertEquals($result->get_error_code(), $this->errorCode);
		$this->assertEquals($result->get_error_message(), 'invalid: Email address is invalid');
	}

	/**
	 * Test that the `subscriber_authentication_send_code()` function returns the expected
	 * response when no email address is specified.
	 *
	 * @since   1.3.0
	 */
	public function testSubscriberAuthenticationSendCodeWithNoEmail()
	{
		$this->markTestIncomplete();

		$result = $this->api->subscriber_authentication_send_code(
			'',
			$_ENV['TEST_SITE_WP_URL']
		);
		$this->assertInstanceOf(WP_Error::class, $result);
		$this->assertEquals($result->get_error_code(), $this->errorCode);
		$this->assertEquals($result->get_error_message(), 'subscriber_authentication_send_code(): the email parameter is empty.');
	}

	/**
	 * Test that the `subscriber_authentication_send_code()` function returns the expected
	 * response when an invalid email address is specified.
	 *
	 * @since   1.3.0
	 */
	public function testSubscriberAuthenticationSendCodeWithInvalidEmail()
	{
		$this->markTestIncomplete();

		$result = $this->api->subscriber_authentication_send_code(
			'not-an-email-address',
			$_ENV['TEST_SITE_WP_URL']
		);
		$this->assertInstanceOf(WP_Error::class, $result);
		$this->assertEquals($result->get_error_code(), $this->errorCode);
		$this->assertEquals($result->get_error_message(), 'invalid: Email address is invalid');
	}

	/**
	 * Test that the `subscriber_authentication_send_code()` function returns the expected
	 * response when an invalid redirect URL is specified.
	 *
	 * @since   1.3.0
	 */
	public function testSubscriberAuthenticationSendCodeWithInvalidRedirectURL()
	{
		$this->markTestIncomplete();

		$result = $this->api->subscriber_authentication_send_code(
			$_ENV['CONVERTKIT_API_SUBSCRIBER_EMAIL'],
			'not-a-valid-url'
		);
		$this->assertInstanceOf(WP_Error::class, $result);
		$this->assertEquals($result->get_error_code(), $this->errorCode);
		$this->assertEquals($result->get_error_message(), 'subscriber_authentication_send_code(): the redirect_url parameter is not a valid URL.');
	}

	/**
	 * Test that the `subscriber_authentication_verify()` function returns the expected
	 * response when a valid token is specified, but the subscriber code is invalid.
	 *
	 * @since   1.3.0
	 */
	public function testSubscriberAuthenticationVerifyWithValidTokenAndInvalidSubscriberCode()
	{
		$this->markTestIncomplete();

		$result = $this->api->subscriber_authentication_verify(
			$_ENV['CONVERTKIT_API_SUBSCRIBER_TOKEN'],
			'subscriberCode'
		);
		$this->assertInstanceOf(WP_Error::class, $result);
		$this->assertEquals($result->get_error_code(), $this->errorCode);
		$this->assertEquals($result->get_error_message(), 'The entered code is invalid. Please try again, or click the link sent in the email.');
	}

	/**
	 * Test that the `subscriber_authentication_verify()` function returns the expected
	 * response when no token is specified.
	 *
	 * @since   1.3.0
	 */
	public function testSubscriberAuthenticationVerifyWithNoToken()
	{
		$this->markTestIncomplete();

		$result = $this->api->subscriber_authentication_verify(
			'',
			'subscriberCode'
		);
		$this->assertInstanceOf(WP_Error::class, $result);
		$this->assertEquals($result->get_error_code(), $this->errorCode);
		$this->assertEquals($result->get_error_message(), 'subscriber_authentication_verify(): the token parameter is empty.');
	}

	/**
	 * Test that the `subscriber_authentication_verify()` function returns the expected
	 * response when no subscriber code is specified.
	 *
	 * @since   1.3.0
	 */
	public function testSubscriberAuthenticationVerifyWithNoSubscriberCode()
	{
		$this->markTestIncomplete();

		$result = $this->api->subscriber_authentication_verify(
			'token',
			''
		);
		$this->assertInstanceOf(WP_Error::class, $result);
		$this->assertEquals($result->get_error_code(), $this->errorCode);
		$this->assertEquals($result->get_error_message(), 'subscriber_authentication_verify(): the subscriber_code parameter is empty.');
	}

	/**
	 * Test that the `subscriber_authentication_verify()` function returns the expected
	 * response when an invalid token and subscriber code is specified.
	 *
	 * @since   1.3.0
	 */
	public function testSubscriberAuthenticationVerifyWithInvalidTokenAndSubscriberCode()
	{
		$this->markTestIncomplete();

		$result = $this->api->subscriber_authentication_verify(
			'invalidToken',
			'invalidSubscriberCode'
		);
		$this->assertInstanceOf(WP_Error::class, $result);
		$this->assertEquals($result->get_error_code(), $this->errorCode);
		$this->assertEquals($result->get_error_message(), 'The entered code is invalid. Please try again, or click the link sent in the email.');
	}

	/**
	 * Test that the `profile()` function returns the expected
	 * response when a valid signed subscriber ID is specified,
	 * and that the subscriber belongs to the expected product ID.
	 *
	 * @since   1.3.0
	 */
	public function testProfilesWithValidSignedSubscriberID()
	{
		$this->markTestIncomplete();

		$result = $this->api->profile( $_ENV['CONVERTKIT_API_SIGNED_SUBSCRIBER_ID'] );
		$this->assertNotInstanceOf(WP_Error::class, $result);
		$this->assertIsArray($result);
		$this->assertArrayHasKey('id', $result);
		$this->assertArrayHasKey('products', $result);
		$this->assertEquals($_ENV['CONVERTKIT_API_PRODUCT_ID'], $result['products'][0]);
	}

	/**
	 * Test that the `profile()` function returns the expected
	 * response when an invalid signed subscriber ID is specified.
	 *
	 * @since   1.3.0
	 */
	public function testProfilesWithInvalidSignedSubscriberID()
	{
		$this->markTestIncomplete();

		$result = $this->api->profile('fakeSignedID');
		$this->assertInstanceOf(WP_Error::class, $result);
		$this->assertEquals($result->get_error_code(), $this->errorCode);
		$this->assertEquals($result->get_error_message(), 'Subscriber not found');
	}

	/**
	 * Test that the `profile()` function returns the expected
	 * response when no signed subscriber ID is specified.
	 *
	 * @since   1.3.0
	 */
	public function testProfilesWithNoSignedSubscriberID()
	{
		$this->markTestIncomplete();

		$result = $this->api->profile('');
		$this->assertInstanceOf(WP_Error::class, $result);
		$this->assertEquals($result->get_error_code(), $this->errorCode);
		$this->assertEquals($result->get_error_message(), 'profiles(): the signed_subscriber_id parameter is empty.');
	}

	/**
	 * Test that the `purchase_create()` function returns expected data
	 * when valid parameters are provided.
	 *
	 * @since   1.0.0
	 */
	public function testPurchaseCreate()
	{
		$this->markTestIncomplete();

		$result = $this->api->purchase_create(
			array(
				'transaction_id'   => '99999',
				'email_address'    => $_ENV['CONVERTKIT_API_SUBSCRIBER_EMAIL'],
				'first_name'       => 'First',
				'currency'         => 'USD',
				'transaction_time' => date( 'Y-m-d H:i:s' ),
				'subtotal'         => 10,
				'tax'              => 1,
				'shipping'         => 1,
				'discount'         => 0,
				'total'            => 12,
				'status'           => 'paid',
				'products'         => array(
					array(
						'pid'        => 1,
						'lid'        => 1,
						'name'       => 'Test Product',
						'sku'        => '12345',
						'unit_price' => 10,
						'quantity'   => 1,
					),
				),
				'integration'      => 'WooCommerce',
			)
		);
		$this->assertNotInstanceOf(WP_Error::class, $result);
		$this->assertIsArray($result);
		$this->assertArrayHasKey('id', $result);
		$this->assertArrayHasKey('transaction_id', $result);
		$this->assertEquals($result['transaction_id'], '99999');
	}

	/**
	 * Test that the `recommendations_script()` function returns expected data
	 * for a ConvertKit account that has the Creator Network enabled.
	 *
	 * @since   1.3.7
	 */
	public function testRecommendationsScript()
	{
		$this->markTestIncomplete();

		$result = $this->api->recommendations_script();
		$this->assertNotInstanceOf(WP_Error::class, $result);
		$this->assertIsArray($result);
		$this->assertArrayHasKey('enabled', $result);
		$this->assertArrayHasKey('embed_js', $result);
		$this->assertTrue($result['enabled']);
		$this->assertEquals($result['embed_js'], $_ENV['CONVERTKIT_API_RECOMMENDATIONS_JS']);
	}

	/**
	 * Test that the `recommendations_script()` function returns expected data
	 * for a ConvertKit account that has the Creator Network disabled.
	 *
	 * @since   1.3.7
	 */
	public function testRecommendationsScriptWhenCreatorNetworkDisabled()
	{
		$this->markTestIncomplete();

		$api    = new ConvertKit_API($_ENV['CONVERTKIT_API_KEY_NO_DATA'], $_ENV['CONVERTKIT_API_SECRET_NO_DATA']);
		$result = $api->recommendations_script();
		$this->assertNotInstanceOf(WP_Error::class, $result);
		$this->assertIsArray($result);
		$this->assertArrayHasKey('enabled', $result);
		$this->assertArrayHasKey('embed_js', $result);
		$this->assertFalse($result['enabled']);
		$this->assertNull($result['embed_js']);
	}

	/**
	 * Test that the `get_form_html()` function returns expected data
	 * when a valid legacy form ID is specified.
	 *
	 * @since   1.2.2
	 */
	public function testGetLegacyFormHTML()
	{
		$this->markTestIncomplete();

		$result = $this->api->get_form_html($_ENV['CONVERTKIT_API_LEGACY_FORM_ID']);
		$this->assertNotInstanceOf(WP_Error::class, $result);
		$this->assertStringContainsString('<form id="ck_subscribe_form" class="ck_subscribe_form" action="https://api.convertkit.com/landing_pages/' . $_ENV['CONVERTKIT_API_LEGACY_FORM_ID'] . '/subscribe" data-remote="true">', $result);

		// Assert that the API class' manually added UTF-8 Content-Type has been removed prior to output.
		$this->assertStringNotContainsString('<meta http-equiv="Content-Type" content="text/html; charset=utf-8">', $result);

		// Assert that character encoding works, and that special characters are not malformed.
		$this->assertStringContainsString('Vantar þinn ungling sjálfstraust í stærðfræði?', $result);
	}

	/**
	 * Test that the `get_form_html()` function returns a WP_Error
	 * when an invalid legacy form ID is specified.
	 *
	 * @since   1.2.2
	 */
	public function testGetLegacyFormHTMLWithInvalidFormID()
	{
		$this->markTestIncomplete();

		$result = $this->api->get_form_html('11111');
		$this->assertInstanceOf(WP_Error::class, $result);
	}

	/**
	 * Test that the `get_landing_page_html()` function returns expected data
	 * when a valid landing page URL is specified.
	 *
	 * @since   1.2.2
	 */
	public function testGetLandingPageHTML()
	{
		$this->markTestIncomplete();

		$result = $this->api->get_landing_page_html($_ENV['CONVERTKIT_API_LANDING_PAGE_URL']);
		$this->assertNotInstanceOf(WP_Error::class, $result);
		$this->assertStringContainsString('<form method="POST" action="https://app.convertkit.com/forms/' . $_ENV['CONVERTKIT_API_LANDING_PAGE_ID'] . '/subscriptions" data-sv-form="' . $_ENV['CONVERTKIT_API_LANDING_PAGE_ID'] . '" data-uid="99f1db6843" class="formkit-form"', $result);
	}

	/**
	 * Test that the `get_landing_page_html()` function returns expected data
	 * when a valid landing page URL is specified whicih contains special characters.
	 *
	 * @since   1.3.3
	 */
	public function testGetLandingPageWithCharacterEncodingHTML()
	{
		$this->markTestIncomplete();

		$result = $this->api->get_landing_page_html($_ENV['CONVERTKIT_API_LANDING_PAGE_CHARACTER_ENCODING_URL']);
		$this->assertNotInstanceOf(WP_Error::class, $result);
		$this->assertStringContainsString('<form method="POST" action="https://app.convertkit.com/forms/' . $_ENV['CONVERTKIT_API_LANDING_PAGE_CHARACTER_ENCODING_ID'] . '/subscriptions" data-sv-form="' . $_ENV['CONVERTKIT_API_LANDING_PAGE_CHARACTER_ENCODING_ID'] . '" data-uid="cc5eb21744" class="formkit-form"', $result);

		// Assert that character encoding works, and that special characters are not malformed.
		$this->assertStringContainsString('Vantar þinn ungling sjálfstraust í stærðfræði?', $result);
	}

	/**
	 * Test that the `get_landing_page_html()` function returns expected data
	 * when a valid legacy landing page URL is specified.
	 *
	 * @since   1.2.2
	 */
	public function testGetLegacyLandingPageHTML()
	{
		$this->markTestIncomplete();

		$result = $this->api->get_landing_page_html($_ENV['CONVERTKIT_API_LEGACY_LANDING_PAGE_URL']);
		$this->assertNotInstanceOf(WP_Error::class, $result);
		$this->assertStringContainsString('<form id="ck_subscribe_form" class="ck_subscribe_form" action="https://app.convertkit.com/landing_pages/' . $_ENV['CONVERTKIT_API_LEGACY_LANDING_PAGE_ID'] . '/subscribe" data-remote="true">', $result);
	}

	/**
	 * Test that the `get_landing_page_html()` function returns a WP_Error
	 * when an invalid landing page URL is specified.
	 *
	 * @since   1.2.2
	 */
	public function testGetLandingPageHTMLWithInvalidLandingPageURL()
	{
		$this->markTestIncomplete();

		$result = $this->api->get_landing_page_html('http://fake-url');
		$this->assertInstanceOf(WP_Error::class, $result);
	}

	/**
	 * Test that the `get_subscriber()` function is backward compatible.
	 *
	 * @since   1.0.0
	 */
	public function testBackwardCompatGetSubscriber()
	{
		$this->markTestIncomplete();

		$result = $this->api->get_subscriber($_ENV['CONVERTKIT_API_SUBSCRIBER_ID']);
		$this->assertNotInstanceOf(WP_Error::class, $result);
	}

	/**
	 * Test that the `add_tag()` function is backward compatible.
	 *
	 * @since   1.0.0
	 */
	public function testBackwardCompatAddTag()
	{
		$this->markTestIncomplete();

		$result = $this->api->add_tag(
			$_ENV['CONVERTKIT_API_TAG_ID'],
			[
				'email' => $_ENV['CONVERTKIT_API_SUBSCRIBER_EMAIL'],
			]
		);
		$this->assertNotInstanceOf(WP_Error::class, $result);
		$this->assertIsArray($result);
		$this->assertArrayHasKey('subscription', $result);
	}

	/**
	 * Test that the `add_tag()` function is backward compatible and returns a WP_Error
	 * when an empty $tag_id parameter is provided.
	 *
	 * @since   1.0.0
	 */
	public function testBackwardCompatAddTagWithEmptyTagID()
	{
		$this->markTestIncomplete();

		$result = $this->api->add_tag(
			'',
			[
				'email' => $_ENV['CONVERTKIT_API_SUBSCRIBER_EMAIL'],
			]
		);
		$this->assertInstanceOf(WP_Error::class, $result);
		$this->assertEquals($result->get_error_code(), $this->errorCode);
		$this->assertEquals('tag_subscribe(): the tag_id parameter is empty.', $result->get_error_message());
	}

	/**
	 * Test that the `add_tag()` function is backward compatible and returns a WP_Error
	 * when an empty $email parameter is provided.
	 *
	 * @since   1.0.0
	 */
	public function testBackwardCompatAddTagWithEmptyEmail()
	{
		$this->markTestIncomplete();

		$result = $this->api->add_tag(
			$_ENV['CONVERTKIT_API_TAG_ID'],
			[
				'email' => '',
			]
		);
		$this->assertInstanceOf(WP_Error::class, $result);
		$this->assertEquals($result->get_error_code(), $this->errorCode);
		$this->assertEquals('tag_subscribe(): the email parameter is empty.', $result->get_error_message());
	}

	/**
	 * Test that the `unsubscribe()` function is backward compatible.
	 *
	 * @since   1.0.0
	 */
	public function testBackwardCompatFormUnsubscribe()
	{
		$this->markTestIncomplete();

		// We don't use $_ENV['CONVERTKIT_API_SUBSCRIBER_EMAIL'] for this test, as that email is relied upon as being a confirmed subscriber
		// for other tests.

		// Subscribe an email address.
		$emailAddress = 'wordpress-' . date( 'Y-m-d-H-i-s' ) . '-php-' . PHP_VERSION_ID . '@convertkit.com';
		$this->api->form_subscribe($_ENV['CONVERTKIT_API_FORM_ID'], $emailAddress);

		// Unsubscribe the email address.
		$result = $this->api->form_unsubscribe(
			[
				'email' => $emailAddress,
			]
		);
		$this->assertNotInstanceOf(WP_Error::class, $result);
		$this->assertIsArray($result);
		$this->assertArrayHasKey('subscriber', $result);
		$this->assertArrayHasKey('email_address', $result['subscriber']);
		$this->assertEquals($emailAddress, $result['subscriber']['email_address']);
	}

	/**
	 * Test that the `unsubscribe()` function is backward compatible and returns a WP_Error
	 * when an empty $email parameter is provided.
	 *
	 * @since   1.0.0
	 */
	public function testBackwardCompatFormUnsubscribeWithEmptyEmail()
	{
		$this->markTestIncomplete();

		$result = $this->api->form_unsubscribe(
			[
				'email' => '',
			]
		);
		$this->assertInstanceOf(WP_Error::class, $result);
		$this->assertEquals($result->get_error_code(), $this->errorCode);
		$this->assertEquals('unsubscribe(): the email parameter is empty.', $result->get_error_message());
	}

	/**
	 * Forces WordPress' wp_remote_*() functions to return a specific HTTP response code
	 * and message by short circuiting using the `pre_http_request` filter.
	 *
	 * This emulates server responses that the API class has to handle from ConvertKit's API,
	 * which we cannot easily recreate e.g. 500 or 502 errors.
	 *
	 * @since   1.0.0
	 *
	 * @param   int         $httpCode       HTTP Code.
	 * @param   string      $httpMessage    HTTP Message.
	 * @param   null|string $body           Response body.
	 */
	private function mockResponses( $httpCode, $httpMessage, $body = null )
	{
		add_filter(
			'pre_http_request',
			function( $response ) use ( $httpCode, $httpMessage, $body ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter
				return array(
					'headers'       => array(),
					'body'          => $body,
					'response'      => array(
						'code'    => $httpCode,
						'message' => $httpMessage,
					),
					'cookies'       => array(),
					'http_response' => null,
				);
			}
		);
	}
}
