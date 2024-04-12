<?php
/**
 * ConvertKit API class.
 *
 * @package ConvertKit
 * @author ConvertKit
 */

/**
 * ConvertKit API class
 *
 * @package ConvertKit
 * @author ConvertKit
 */
class ConvertKit_API {

	/**
	 * ConvertKit API Key
	 *
	 * @var bool|string
	 */
	protected $api_key = false;

	/**
	 * ConvertKit API Secret
	 *
	 * @var bool|string
	 */
	protected $api_secret = false;

	/**
	 * ConvertKit OAuth Application Client ID
	 *
	 * @since   2.0.0
	 *
	 * @var bool|string.
	 */
	protected $client_id = false;

	/**
	 * ConvertKit OAuth Redirect URI
	 *
	 * @since   2.0.0
	 *
	 * @var bool|string.
	 */
	protected $redirect_uri = false;

	/**
	 * Access Token
	 *
	 * @since   2.0.0
	 *
	 * @var bool|string
	 */
	protected $access_token = '';

	/**
	 * Refresh Token
	 *
	 * @since   2.0.0
	 *
	 * @var bool|string
	 */
	protected $refresh_token = '';

	/**
	 * Optional context of the request.
	 *
	 * @var     bool|string
	 */
	protected $context = false;

	/**
	 * Save debug data to log
	 *
	 * @var  bool
	 */
	protected $debug = false;

	/**
	 * The plugin name.
	 *
	 * @var bool|string
	 */
	protected $plugin_name;

	/**
	 * The plugin path.
	 *
	 * @var bool|string
	 */
	protected $plugin_path;

	/**
	 * The plugin URL.
	 *
	 * @var bool|string
	 */
	protected $plugin_url;

	/**
	 * The plugin version.
	 *
	 * @var bool|string
	 */
	protected $plugin_version;

	/**
	 * OAuth Authorization URL
	 *
	 * @since   2.0.0
	 *
	 * @var string
	 */
	protected $oauth_authorize_url = 'https://app.convertkit.com/oauth/authorize';

	/**
	 * Version of ConvertKit API
	 *
	 * @var string
	 */
	protected $api_version = 'v4';

	/**
	 * ConvertKit API URL
	 *
	 * @var string
	 */
	protected $api_url_base = 'https://api.convertkit.com/';

	/**
	 * ConvertKit API endpoints that use the /oauth/ namespace
	 * i.e. https://api.convertkit.com/oauth/endpoint
	 *
	 * @since   2.0.0
	 *
	 * @var     array
	 */
	protected $api_endpoints_oauth = array(
		'token',
	);

	/**
	 * ConvertKit API endpoints that use the /wordpress/ namespace
	 * i.e. https://api.convertkit.com/wordpress/endpoint
	 *
	 * @since   1.3.0
	 *
	 * @var     array
	 */
	protected $api_endpoints_wordpress = array(
		'posts',
		'products',
		'profile',
		'recommendations_script',
		'subscriber_authentication/send_code',
		'subscriber_authentication/verify',
	);

	/**
	 * Holds the log class for writing to the log file
	 *
	 * @var bool|ConvertKit_Log|WC_Logger
	 */
	public $log = false;

	/**
	 * Holds an array of error messages, localized to the plugin
	 * using this API class.
	 *
	 * @var bool|array
	 */
	public $error_messages = false;

	/**
	 * Sets up the API with the required credentials.
	 *
	 * @since   1.0.0
	 *
	 * @param   string      $client_id         OAuth Client ID.
	 * @param   string      $redirect_uri      OAuth Redirect URI.
	 * @param   bool|string $access_token      ConvertKit OAuth Access Token.
	 * @param   bool|string $refresh_token     ConvertKit OAuth Refresh Token.
	 * @param   bool|object $debug             Save data to log.
	 * @param   bool|string $context           Context of originating request.
	 */
	public function __construct( $client_id, $redirect_uri, $access_token = false, $refresh_token = false, $debug = false, $context = false ) {

		// Set API credentials, debugging and logging class.
		$this->client_id      = $client_id;
		$this->redirect_uri   = $redirect_uri;
		$this->access_token   = $access_token;
		$this->refresh_token  = $refresh_token;
		$this->debug          = $debug;
		$this->context        = $context;
		$this->plugin_name    = ( defined( 'CONVERTKIT_PLUGIN_NAME' ) ? CONVERTKIT_PLUGIN_NAME : false );
		$this->plugin_path    = ( defined( 'CONVERTKIT_PLUGIN_PATH' ) ? CONVERTKIT_PLUGIN_PATH : false );
		$this->plugin_url     = ( defined( 'CONVERTKIT_PLUGIN_URL' ) ? CONVERTKIT_PLUGIN_URL : false );
		$this->plugin_version = ( defined( 'CONVERTKIT_PLUGIN_VERSION' ) ? CONVERTKIT_PLUGIN_VERSION : false );

		// Setup logging class if the required parameters exist.
		if ( $this->debug && $this->plugin_path !== false ) {
			$this->log = new ConvertKit_Log( $this->plugin_path );
		}

		// Define translatable / localized error strings.
		// WordPress requires that the text domain be a string (e.g. 'woocommerce-convertkit') and not a variable,
		// otherwise localization won't work.
		// phpcs:disable
		$this->error_messages = array(
			// form_subscribe().
			'form_subscribe_form_id_empty'                => __( 'form_subscribe(): the form_id parameter is empty.', 'convertkit' ),
			'form_subscribe_email_empty'                  => __( 'form_subscribe(): the email parameter is empty.', 'convertkit' ),

			// sequence_subscribe().
			'sequence_subscribe_sequence_id_empty'        => __( 'sequence_subscribe(): the sequence_id parameter is empty.', 'convertkit' ),
			'sequence_subscribe_email_empty'              => __( 'sequence_subscribe(): the email parameter is empty.', 'convertkit' ),

			// tag_subscribe().
			'tag_subscribe_tag_id_empty'                  => __( 'tag_subscribe(): the tag_id parameter is empty.', 'convertkit' ),
			'tag_subscribe_email_empty'                   => __( 'tag_subscribe(): the email parameter is empty.', 'convertkit' ),

			// tag_unsubscribe().
			'tag_unsubscribe_tag_id_empty'                => __( 'tag_unsubscribe(): the tag_id parameter is empty.', 'convertkit' ),
			'tag_unsubscribe_email_empty'                 => __( 'tag_unsubscribe(): the email parameter is empty.', 'convertkit' ),
			'tag_unsubscribe_email_invalid'               => __( 'tag_unsubscribe(): the email parameter is not a valid email address.', 'convertkit' ),

			// get_subscriber_by_email().
			'get_subscriber_by_email_email_empty'         => __( 'get_subscriber_by_email(): the email parameter is empty.', 'convertkit' ),
			/* translators: Email Address */
			'get_subscriber_by_email_none'                => __( 'No subscriber(s) exist in ConvertKit matching the email address %s.', 'convertkit' ),

			// get_subscriber_by_id().
			'get_subscriber_by_id_subscriber_id_empty'    => __( 'get_subscriber_by_id(): the subscriber_id parameter is empty.', 'convertkit' ),

			// get_subscriber_tags().
			'get_subscriber_tags_subscriber_id_empty'     => __( 'get_subscriber_tags(): the subscriber_id parameter is empty.', 'convertkit' ),

			// unsubscribe_email().
			'unsubscribe_email_empty'                     => __( 'unsubscribe(): the email parameter is empty.', 'convertkit' ),

			// broadcast_delete().
			'broadcast_delete_broadcast_id_empty'		  => __( 'broadcast_delete(): the broadcast_id parameter is empty.', 'convertkit' ),

			// get_all_posts().
			'get_all_posts_posts_per_request_bound_too_low' => __( 'get_all_posts(): the posts_per_request parameter must be equal to or greater than 1.', 'convertkit' ),
			'get_all_posts_posts_per_request_bound_too_high' => __( 'get_all_posts(): the posts_per_request parameter must be equal to or less than 50.', 'convertkit' ),

			// get_posts().
			'get_posts_page_parameter_bound_too_low'      => __( 'get_posts(): the page parameter must be equal to or greater than 1.', 'convertkit' ),
			'get_posts_per_page_parameter_bound_too_low'  => __( 'get_posts(): the per_page parameter must be equal to or greater than 1.', 'convertkit' ),
			'get_posts_per_page_parameter_bound_too_high' => __( 'get_posts(): the per_page parameter must be equal to or less than 50.', 'convertkit' ),

			// subscriber_authentication_send_code().
			'subscriber_authentication_send_code_email_empty'			=> __( 'subscriber_authentication_send_code(): the email parameter is empty.', 'convertkit' ),
			'subscriber_authentication_send_code_redirect_url_empty'	=> __( 'subscriber_authentication_send_code(): the redirect_url parameter is empty.', 'convertkit' ),
			'subscriber_authentication_send_code_redirect_url_invalid' 	=> __( 'subscriber_authentication_send_code(): the redirect_url parameter is not a valid URL.', 'convertkit' ),
			'subscriber_authentication_send_code_response_token_missing'=> __( 'subscriber_authentication_send_code(): the token parameter is missing from the API response.', 'convertkit' ),
			
			// subscriber_authentication_verify().
			'subscriber_authentication_verify_token_empty'					  => __( 'subscriber_authentication_verify(): the token parameter is empty.', 'convertkit' ),
			'subscriber_authentication_verify_subscriber_code_empty'		  => __( 'subscriber_authentication_verify(): the subscriber_code parameter is empty.', 'convertkit' ),
			'subscriber_authentication_verify_response_error' 				  => __( 'The entered code is invalid. Please try again, or click the link sent in the email.', 'convertkit' ),

			// profile().
			'profiles_signed_subscriber_id_empty' 		  => __( 'profiles(): the signed_subscriber_id parameter is empty.', 'convertkit' ),

			// request().
			/* translators: HTTP method */
			'request_method_unsupported'                  => __( 'API request method %s is not supported in ConvertKit_API class.', 'convertkit' ),
			'request_rate_limit_exceeded'                 => __( 'ConvertKit API Error: Rate limit hit.', 'convertkit' ),
			'request_internal_server_error'               => __( 'ConvertKit API Error: Internal server error.', 'convertkit' ),
			'request_bad_gateway'                 		  => __( 'ConvertKit API Error: Bad gateway.', 'convertkit' ),
			'response_type_unexpected' 					  => __( 'ConvertKit API Error: The response is not of the expected type array.', 'convertkit' ),
		);
		// phpcs:enable

	}

	/**
	 * Generates and stores a code verifier for PKCE authentication flow.
	 *
	 * @since   2.0.0
	 *
	 * @return  string
	 */
	private function generate_and_store_code_verifier() {

		// If a code verifier already exists, use it.
		$code_verifier = $this->get_code_verifier();
		if ( $code_verifier ) {
			return $code_verifier;
		}

		// Generate a random string.
		$code_verifier = random_bytes( 64 );

		// Encode to Base64 string.
		$code_verifier = base64_encode( $code_verifier ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions

		// Convert Base64 to Base64URL by replacing “+” with “-” and “/” with “_”.
		$code_verifier = strtr( $code_verifier, '+/', '-_' );

		// Remove padding character from the end of line.
		$code_verifier = rtrim( $code_verifier, '=' );

		// Store in database for later use.
		update_option( 'ck_code_verifier', $code_verifier );

		// Return.
		return $code_verifier;

	}

	/**
	 * Base64URL the given code verifier, as PHP has no built in function for this.
	 *
	 * @since   2.0.0
	 *
	 * @param   string $code_verifier  Code Verifier.
	 * @return  string                  Code Challenge.
	 */
	public function generate_code_challenge( $code_verifier ) {

		// Hash using S256.
		$code_challenge = hash( 'sha256', $code_verifier, true );

		// Encode to Base64 string.
		$code_challenge = base64_encode( $code_challenge ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions

		// Convert Base64 to Base64URL by replacing “+” with “-” and “/” with “_”.
		$code_challenge = strtr( $code_challenge, '+/', '-_' );

		// Remove padding character from the end of line.
		$code_challenge = rtrim( $code_challenge, '=' );

		// Return.
		return $code_challenge;

	}

	/**
	 * Returns the stored code verifier generated by generate_and_store_code_verifier().
	 *
	 * @since   2.0.0
	 *
	 * @return  bool|string
	 */
	public function get_code_verifier() {

		return get_option( 'ck_code_verifier' );

	}

	/**
	 * Deletes the stored code verifier generated by generate_code_verifier().
	 *
	 * @since   2.0.0
	 *
	 * @return  bool
	 */
	private function delete_code_verifier() {

		return delete_option( 'ck_code_verifier' );

	}

	/**
	 * Returns the URL used to begin the OAuth process
	 *
	 * @since   2.0.0
	 *
	 * @return  string                  OAuth URL
	 */
	public function get_oauth_url() {

		// Generate and store code verifier and challenge.
		$code_verifier  = $this->generate_and_store_code_verifier();
		$code_challenge = $this->generate_code_challenge( $code_verifier );

		// Return OAuth URL.
		return add_query_arg(
			array(
				'client_id'             => $this->client_id,
				'response_type'         => 'code',
				'redirect_uri'          => rawurlencode( $this->redirect_uri ),
				'code_challenge'        => $code_challenge,
				'code_challenge_method' => 'S256',
			),
			$this->oauth_authorize_url
		);

	}

	/**
	 * Exchanges the given code for an access token, refresh token and other data.
	 *
	 * @since   2.0.0
	 *
	 * @param   string $authorization_code     Authorization Code, returned from get_oauth_url() flow.
	 * @return  WP_Error|array
	 */
	public function get_access_token( $authorization_code ) {

		$result = $this->post(
			'token',
			array(
				'client_id'     => $this->client_id,
				'grant_type'    => 'authorization_code',
				'code'          => $authorization_code,
				'redirect_uri'  => $this->redirect_uri,
				'code_verifier' => $this->get_code_verifier(),
			)
		);

		// Delete code verifier, as it's no longer needed.
		// If the access token request fails, the user
		// will begin the process again, which generates a
		// new code verifier.
		$this->delete_code_verifier();

		// If an error occured, log and return it now.
		if ( is_wp_error( $result ) ) {
			$this->log( 'API: Error: ' . $result->get_error_message() );
			return $result;
		}

		/**
		 * Perform any actions with the new access token, such as saving it.
		 *
		 * @since   2.0.0
		 *
		 * @param   array   $result     Access Token, Refresh Token, Expiry, Bearer and Scope.
		 * @param   string  $client_id  OAUth Client ID.
		 */
		do_action( 'convertkit_api_get_access_token', $result, $this->client_id );

		// Return.
		return $result;

	}

	/**
	 * Fetches a new access token using the supplied refresh token.
	 *
	 * @since   2.0.0
	 */
	public function refresh_token() {

		$result = $this->post(
			'token',
			array(
				'client_id'     => $this->client_id,
				'grant_type'    => 'refresh_token',
				'refresh_token' => $this->refresh_token,
			)
		);

		// If an error occured, log and return it now.
		if ( is_wp_error( $result ) ) {
			$this->log( 'API: Error: ' . $result->get_error_message() );
			return $result;
		}

		// Update the access and refresh tokens in this class.
		$this->access_token  = $result['access_token'];
		$this->refresh_token = $result['refresh_token'];

		/**
		 * Perform any actions with the new access token, such as saving it.
		 *
		 * @since   2.0.0
		 *
		 * @param   array   $result     Access Token, Refresh Token, Expiry, Bearer and Scope.
		 * @param   string  $client_id  OAUth Client ID.
		 */
		do_action( 'convertkit_api_refresh_token', $result, $this->client_id );

		// Return.
		return $result;

	}

	/**
	 * Gets the current account
	 *
	 * @see https://developers.convertkit.com/v4.html#get-current-account
	 *
	 * @return false|mixed
	 */
	public function get_account() {
		return $this->get( 'account' );
	}

	/**
	 * Gets the account's colors
	 *
	 * @see https://developers.convertkit.com/v4.html#list-colors
	 *
	 * @return false|mixed
	 */
	public function get_account_colors() {
		return $this->get( 'account/colors' );
	}

	/**
	 * Gets the account's colors
	 *
	 * @param array<string, string> $colors Hex colors.
	 *
	 * @see https://developers.convertkit.com/v4.html#list-colors
	 *
	 * @return false|mixed
	 */
	public function update_account_colors( array $colors ) {
		return $this->put(
			endpoint: 'account/colors',
			args: array( 'colors' => $colors )
		);
	}

	/**
	 * Gets the Creator Profile
	 *
	 * @see https://developers.convertkit.com/v4.html#get-creator-profile
	 *
	 * @return false|mixed
	 */
	public function get_creator_profile() {
		return $this->get( 'account/creator_profile' );
	}

	/**
	 * Gets email stats
	 *
	 * @see https://developers.convertkit.com/v4.html#get-email-stats
	 *
	 * @return false|mixed
	 */
	public function get_email_stats() {
		return $this->get( 'account/email_stats' );
	}

	/**
	 * Gets growth stats
	 *
	 * @param \DateTime $starting Gets stats for time period beginning on this date. Defaults to 90 days ago.
	 * @param \DateTime $ending   Gets stats for time period ending on this date. Defaults to today.
	 *
	 * @see https://developers.convertkit.com/v4.html#get-growth-stats
	 *
	 * @return false|mixed
	 */
	public function get_growth_stats( \DateTime $starting = null, \DateTime $ending = null ) {
		return $this->get(
			'account/growth_stats',
			array(
				'starting' => ( ! is_null( $starting ) ? $starting->format( 'Y-m-d' ) : '' ),
				'ending'   => ( ! is_null( $ending ) ? $ending->format( 'Y-m-d' ) : '' ),
			)
		);
	}

	/**
	 * Get forms.
	 *
	 * @param string  $status              Form status (active|archived|trashed|all).
	 * @param boolean $include_total_count To include the total count of records in the response, use true.
	 * @param string  $after_cursor        Return results after the given pagination cursor.
	 * @param string  $before_cursor       Return results before the given pagination cursor.
	 * @param integer $per_page            Number of results to return.
	 *
	 * @since 1.0.0
	 *
	 * @see https://developers.convertkit.com/v4.html#convertkit-api-forms
	 *
	 * @return false|array<int,\stdClass>
	 */
	public function get_forms(
		string $status = 'active',
		bool $include_total_count = false,
		string $after_cursor = '',
		string $before_cursor = '',
		int $per_page = 100
	) {
		return $this->get(
			endpoint: 'forms',
			args: $this->build_total_count_and_pagination_params(
				params: array(
					'type'   => 'embed',
					'status' => $status,
				),
				include_total_count: $include_total_count,
				after_cursor: $after_cursor,
				before_cursor: $before_cursor,
				per_page: $per_page
			)
		);
	}

	/**
	 * Get landing pages.
	 *
	 * @param string  $status              Form status (active|archived|trashed|all).
	 * @param boolean $include_total_count To include the total count of records in the response, use true.
	 * @param string  $after_cursor        Return results after the given pagination cursor.
	 * @param string  $before_cursor       Return results before the given pagination cursor.
	 * @param integer $per_page            Number of results to return.
	 *
	 * @since 1.0.0
	 *
	 * @see https://developers.convertkit.com/v4.html#convertkit-api-forms
	 *
	 * @return false|array<int,\stdClass>
	 */
	public function get_landing_pages(
		string $status = 'active',
		bool $include_total_count = false,
		string $after_cursor = '',
		string $before_cursor = '',
		int $per_page = 100
	) {
		return $this->get(
			endpoint: 'forms',
			args: $this->build_total_count_and_pagination_params(
				params: array(
					'type'   => 'hosted',
					'status' => $status,
				),
				include_total_count: $include_total_count,
				after_cursor: $after_cursor,
				before_cursor: $before_cursor,
				per_page: $per_page
			)
		);
	}

	/**
	 * Adds a subscriber to a form by email address
	 *
	 * @param integer $form_id       Form ID.
	 * @param string  $email_address Email Address.
	 *
	 * @see https://developers.convertkit.com/v4.html#add-subscriber-to-form-by-email-address
	 *
	 * @return false|mixed
	 */
	public function add_subscriber_to_form_by_email( int $form_id, string $email_address ) {
		return $this->post(
			endpoint: sprintf( 'forms/%s/subscribers', $form_id ),
			args: array( 'email_address' => $email_address )
		);
	}

	/**
	 * Adds a subscriber to a form by subscriber ID
	 *
	 * @param integer $form_id       Form ID.
	 * @param integer $subscriber_id Subscriber ID.
	 *
	 * @see https://developers.convertkit.com/v4.html#add-subscriber-to-form
	 *
	 * @since 2.0.0
	 *
	 * @return false|mixed
	 */
	public function add_subscriber_to_form( int $form_id, int $subscriber_id ) {
		return $this->post( sprintf( 'forms/%s/subscribers/%s', $form_id, $subscriber_id ) );
	}

	/**
	 * List subscribers for a form
	 *
	 * @param integer   $form_id             Form ID.
	 * @param string    $subscriber_state    Subscriber State (active|bounced|cancelled|complained|inactive).
	 * @param \DateTime $created_after       Filter subscribers who have been created after this date.
	 * @param \DateTime $created_before      Filter subscribers who have been created before this date.
	 * @param \DateTime $added_after         Filter subscribers who have been added to the form after this date.
	 * @param \DateTime $added_before        Filter subscribers who have been added to the form before this date.
	 * @param boolean   $include_total_count To include the total count of records in the response, use true.
	 * @param string    $after_cursor        Return results after the given pagination cursor.
	 * @param string    $before_cursor       Return results before the given pagination cursor.
	 * @param integer   $per_page            Number of results to return.
	 *
	 * @see https://developers.convertkit.com/v4.html#list-subscribers-for-a-form
	 *
	 * @return false|mixed
	 */
	public function get_form_subscriptions(
		int $form_id,
		string $subscriber_state = 'active',
		\DateTime $created_after = null,
		\DateTime $created_before = null,
		\DateTime $added_after = null,
		\DateTime $added_before = null,
		bool $include_total_count = false,
		string $after_cursor = '',
		string $before_cursor = '',
		int $per_page = 100
	) {
		// Build parameters.
		$options = array();

		if ( ! empty( $subscriber_state ) ) {
			$options['status'] = $subscriber_state;
		}
		if ( ! is_null( $created_after ) ) {
			$options['created_after'] = $created_after->format( 'Y-m-d' );
		}
		if ( ! is_null( $created_before ) ) {
			$options['created_before'] = $created_before->format( 'Y-m-d' );
		}
		if ( ! is_null( $added_after ) ) {
			$options['added_after'] = $added_after->format( 'Y-m-d' );
		}
		if ( ! is_null( $added_before ) ) {
			$options['added_before'] = $added_before->format( 'Y-m-d' );
		}

		// Send request.
		return $this->get(
			endpoint: sprintf( 'forms/%s/subscribers', $form_id ),
			args: $this->build_total_count_and_pagination_params(
				params: $options,
				include_total_count: $include_total_count,
				after_cursor: $after_cursor,
				before_cursor: $before_cursor,
				per_page: $per_page
			)
		);
	}

	/**
	 * Gets sequences
	 *
	 * @param boolean $include_total_count To include the total count of records in the response, use true.
	 * @param string  $after_cursor        Return results after the given pagination cursor.
	 * @param string  $before_cursor       Return results before the given pagination cursor.
	 * @param integer $per_page            Number of results to return.
	 *
	 * @see https://developers.convertkit.com/v4.html#list-sequences
	 *
	 * @return false|mixed
	 */
	public function get_sequences(
		bool $include_total_count = false,
		string $after_cursor = '',
		string $before_cursor = '',
		int $per_page = 100
	) {
		return $this->get(
			endpoint: 'sequences',
			args: $this->build_total_count_and_pagination_params(
				include_total_count: $include_total_count,
				after_cursor: $after_cursor,
				before_cursor: $before_cursor,
				per_page: $per_page
			)
		);
	}

	/**
	 * Adds a subscriber to a sequence by email address
	 *
	 * @param integer $sequence_id   Sequence ID.
	 * @param string  $email_address Email Address.
	 *
	 * @see https://developers.convertkit.com/v4.html#add-subscriber-to-sequence-by-email-address
	 *
	 * @return false|mixed
	 */
	public function add_subscriber_to_sequence_by_email( int $sequence_id, string $email_address ) {
		return $this->post(
			endpoint: sprintf( 'sequences/%s/subscribers', $sequence_id ),
			args: array( 'email_address' => $email_address )
		);
	}

	/**
	 * Adds a subscriber to a sequence by subscriber ID
	 *
	 * @param integer $sequence_id   Sequence ID.
	 * @param integer $subscriber_id Subscriber ID.
	 *
	 * @see https://developers.convertkit.com/v4.html#add-subscriber-to-sequence
	 *
	 * @since 2.0.0
	 *
	 * @return false|mixed
	 */
	public function add_subscriber_to_sequence( int $sequence_id, int $subscriber_id ) {
		return $this->post( sprintf( 'sequences/%s/subscribers/%s', $sequence_id, $subscriber_id ) );
	}

	/**
	 * List subscribers for a sequence
	 *
	 * @param integer   $sequence_id         Sequence ID.
	 * @param string    $subscriber_state    Subscriber State (active|bounced|cancelled|complained|inactive).
	 * @param \DateTime $created_after       Filter subscribers who have been created after this date.
	 * @param \DateTime $created_before      Filter subscribers who have been created before this date.
	 * @param \DateTime $added_after         Filter subscribers who have been added to the form after this date.
	 * @param \DateTime $added_before        Filter subscribers who have been added to the form before this date.
	 * @param boolean   $include_total_count To include the total count of records in the response, use true.
	 * @param string    $after_cursor        Return results after the given pagination cursor.
	 * @param string    $before_cursor       Return results before the given pagination cursor.
	 * @param integer   $per_page            Number of results to return.
	 *
	 * @see https://developers.convertkit.com/v4.html#list-subscribers-for-a-sequence
	 *
	 * @return false|mixed
	 */
	public function get_sequence_subscriptions(
		int $sequence_id,
		string $subscriber_state = 'active',
		\DateTime $created_after = null,
		\DateTime $created_before = null,
		\DateTime $added_after = null,
		\DateTime $added_before = null,
		bool $include_total_count = false,
		string $after_cursor = '',
		string $before_cursor = '',
		int $per_page = 100
	) {
		// Build parameters.
		$options = array();

		if ( ! empty( $subscriber_state ) ) {
			$options['status'] = $subscriber_state;
		}
		if ( ! is_null( $created_after ) ) {
			$options['created_after'] = $created_after->format( 'Y-m-d' );
		}
		if ( ! is_null( $created_before ) ) {
			$options['created_before'] = $created_before->format( 'Y-m-d' );
		}
		if ( ! is_null( $added_after ) ) {
			$options['added_after'] = $added_after->format( 'Y-m-d' );
		}
		if ( ! is_null( $added_before ) ) {
			$options['added_before'] = $added_before->format( 'Y-m-d' );
		}

		// Send request.
		return $this->get(
			endpoint: sprintf( 'sequences/%s/subscribers', $sequence_id ),
			args: $this->build_total_count_and_pagination_params(
				params: $options,
				include_total_count: $include_total_count,
				after_cursor: $after_cursor,
				before_cursor: $before_cursor,
				per_page: $per_page
			)
		);
	}

	/**
	 * List tags.
	 *
	 * @param boolean $include_total_count To include the total count of records in the response, use true.
	 * @param string  $after_cursor        Return results after the given pagination cursor.
	 * @param string  $before_cursor       Return results before the given pagination cursor.
	 * @param integer $per_page            Number of results to return.
	 *
	 * @see https://developers.convertkit.com/v4.html#list-tags
	 *
	 * @return false|array<int,\stdClass>
	 */
	public function get_tags(
		bool $include_total_count = false,
		string $after_cursor = '',
		string $before_cursor = '',
		int $per_page = 100
	) {
		return $this->get(
			endpoint: 'tags',
			args: $this->build_total_count_and_pagination_params(
				include_total_count: $include_total_count,
				after_cursor: $after_cursor,
				before_cursor: $before_cursor,
				per_page: $per_page
			)
		);
	}

	/**
	 * Creates a tag.
	 *
	 * @param string $tag Tag Name.
	 *
	 * @since 1.0.0
	 *
	 * @see https://developers.convertkit.com/v4.html#create-a-tag
	 *
	 * @return false|mixed
	 */
	public function create_tag( string $tag ) {
		return $this->post(
			endpoint: 'tags',
			args: array( 'name' => $tag )
		);
	}

	/**
	 * Creates multiple tags.
	 *
	 * @param array<int,string> $tags         Tag Names.
	 * @param string            $callback_url URL to notify for large batch size when async processing complete.
	 *
	 * @since 1.1.0
	 *
	 * @see https://developers.convertkit.com/v4.html#bulk-create-tags
	 *
	 * @return false|mixed
	 */
	public function create_tags( array $tags, string $callback_url = '' ) {
		// Build parameters.
		$options = array(
			'tags' => array(),
		);
		foreach ( $tags as $i => $tag ) {
			$options['tags'][] = array(
				'name' => (string) $tag,
			);
		}

		if ( ! empty( $callback_url ) ) {
			$options['callback_url'] = $callback_url;
		}

		// Send request.
		return $this->post(
			endpoint: 'bulk/tags',
			args: $options
		);
	}

	/**
	 * Tags a subscriber with the given existing Tag.
	 *
	 * @param integer $tag_id        Tag ID.
	 * @param string  $email_address Email Address.
	 *
	 * @see https://developers.convertkit.com/v4.html#tag-a-subscriber-by-email-address
	 *
	 * @return false|mixed
	 */
	public function tag_subscriber_by_email( int $tag_id, string $email_address ) {
		return $this->post(
			endpoint: sprintf( 'tags/%s/subscribers', $tag_id ),
			args: array( 'email_address' => $email_address )
		);
	}

	/**
	 * Tags a subscriber by subscriber ID with the given existing Tag.
	 *
	 * @param integer $tag_id        Tag ID.
	 * @param integer $subscriber_id Subscriber ID.
	 *
	 * @see https://developers.convertkit.com/v4.html#tag-a-subscriber
	 *
	 * @return false|mixed
	 */
	public function tag_subscriber( int $tag_id, int $subscriber_id ) {
		return $this->post( sprintf( 'tags/%s/subscribers/%s', $tag_id, $subscriber_id ) );
	}

	/**
	 * Removes a tag from a subscriber.
	 *
	 * @param integer $tag_id        Tag ID.
	 * @param integer $subscriber_id Subscriber ID.
	 *
	 * @since 1.0.0
	 *
	 * @see https://developers.convertkit.com/v4.html#remove-tag-from-subscriber
	 *
	 * @return false|mixed
	 */
	public function remove_tag_from_subscriber( int $tag_id, int $subscriber_id ) {
		return $this->delete( sprintf( 'tags/%s/subscribers/%s', $tag_id, $subscriber_id ) );
	}

	/**
	 * Removes a tag from a subscriber by email address.
	 *
	 * @param integer $tag_id        Tag ID.
	 * @param string  $email_address Subscriber email address.
	 *
	 * @since 1.0.0
	 *
	 * @see https://developers.convertkit.com/v4.html#remove-tag-from-subscriber-by-email-address
	 *
	 * @return false|mixed
	 */
	public function remove_tag_from_subscriber_by_email( int $tag_id, string $email_address ) {
		return $this->delete(
			sprintf( 'tags/%s/subscribers', $tag_id ),
			array( 'email_address' => $email_address )
		);
	}

	/**
	 * List subscribers for a tag
	 *
	 * @param integer   $tag_id              Tag ID.
	 * @param string    $subscriber_state    Subscriber State (active|bounced|cancelled|complained|inactive).
	 * @param \DateTime $created_after       Filter subscribers who have been created after this date.
	 * @param \DateTime $created_before      Filter subscribers who have been created before this date.
	 * @param \DateTime $tagged_after        Filter subscribers who have been tagged after this date.
	 * @param \DateTime $tagged_before       Filter subscribers who have been tagged before this date.
	 * @param boolean   $include_total_count To include the total count of records in the response, use true.
	 * @param string    $after_cursor        Return results after the given pagination cursor.
	 * @param string    $before_cursor       Return results before the given pagination cursor.
	 * @param integer   $per_page            Number of results to return.
	 *
	 * @see https://developers.convertkit.com/v4.html#list-subscribers-for-a-tag
	 *
	 * @return false|mixed
	 */
	public function get_tag_subscriptions(
		int $tag_id,
		string $subscriber_state = 'active',
		\DateTime $created_after = null,
		\DateTime $created_before = null,
		\DateTime $tagged_after = null,
		\DateTime $tagged_before = null,
		bool $include_total_count = false,
		string $after_cursor = '',
		string $before_cursor = '',
		int $per_page = 100
	) {
		// Build parameters.
		$options = array();

		if ( ! empty( $subscriber_state ) ) {
			$options['status'] = $subscriber_state;
		}
		if ( ! is_null( $created_after ) ) {
			$options['created_after'] = $created_after->format( 'Y-m-d' );
		}
		if ( ! is_null( $created_before ) ) {
			$options['created_before'] = $created_before->format( 'Y-m-d' );
		}
		if ( ! is_null( $tagged_after ) ) {
			$options['tagged_after'] = $tagged_after->format( 'Y-m-d' );
		}
		if ( ! is_null( $tagged_before ) ) {
			$options['tagged_before'] = $tagged_before->format( 'Y-m-d' );
		}

		// Send request.
		return $this->get(
			endpoint: sprintf( 'tags/%s/subscribers', $tag_id ),
			args: $this->build_total_count_and_pagination_params(
				params: $options,
				include_total_count: $include_total_count,
				after_cursor: $after_cursor,
				before_cursor: $before_cursor,
				per_page: $per_page
			)
		);
	}

	/**
	 * List email templates.
	 *
	 * @param boolean $include_total_count To include the total count of records in the response, use true.
	 * @param string  $after_cursor        Return results after the given pagination cursor.
	 * @param string  $before_cursor       Return results before the given pagination cursor.
	 * @param integer $per_page            Number of results to return.
	 *
	 * @since 2.0.0
	 *
	 * @see https://developers.convertkit.com/v4.html#convertkit-api-email-templates
	 *
	 * @return false|mixed
	 */
	public function get_email_templates(
		bool $include_total_count = false,
		string $after_cursor = '',
		string $before_cursor = '',
		int $per_page = 100
	) {
		// Send request.
		return $this->get(
			endpoint: 'email_templates',
			args: $this->build_total_count_and_pagination_params(
				include_total_count: $include_total_count,
				after_cursor: $after_cursor,
				before_cursor: $before_cursor,
				per_page: $per_page
			)
		);
	}

	/**
	 * List subscribers.
	 *
	 * @param string    $subscriber_state    Subscriber State (active|bounced|cancelled|complained|inactive).
	 * @param string    $email_address       Search susbcribers by email address. This is an exact match search.
	 * @param \DateTime $created_after       Filter subscribers who have been created after this date.
	 * @param \DateTime $created_before      Filter subscribers who have been created before this date.
	 * @param \DateTime $updated_after       Filter subscribers who have been updated after this date.
	 * @param \DateTime $updated_before      Filter subscribers who have been updated before this date.
	 * @param string    $sort_field          Sort Field (id|updated_at|cancelled_at).
	 * @param string    $sort_order          Sort Order (asc|desc).
	 * @param boolean   $include_total_count To include the total count of records in the response, use true.
	 * @param string    $after_cursor        Return results after the given pagination cursor.
	 * @param string    $before_cursor       Return results before the given pagination cursor.
	 * @param integer   $per_page            Number of results to return.
	 *
	 * @since 2.0.0
	 *
	 * @see https://developers.convertkit.com/v4.html#list-subscribers
	 *
	 * @return false|mixed
	 */
	public function get_subscribers(
		string $subscriber_state = 'active',
		string $email_address = '',
		\DateTime $created_after = null,
		\DateTime $created_before = null,
		\DateTime $updated_after = null,
		\DateTime $updated_before = null,
		string $sort_field = 'id',
		string $sort_order = 'desc',
		bool $include_total_count = false,
		string $after_cursor = '',
		string $before_cursor = '',
		int $per_page = 100
	) {
		// Build parameters.
		$options = array();

		if ( ! empty( $subscriber_state ) ) {
			$options['status'] = $subscriber_state;
		}
		if ( ! empty( $email_address ) ) {
			$options['email_address'] = $email_address;
		}
		if ( ! is_null( $created_after ) ) {
			$options['created_after'] = $created_after->format( 'Y-m-d' );
		}
		if ( ! is_null( $created_before ) ) {
			$options['created_before'] = $created_before->format( 'Y-m-d' );
		}
		if ( ! is_null( $updated_after ) ) {
			$options['updated_after'] = $updated_after->format( 'Y-m-d' );
		}
		if ( ! is_null( $updated_before ) ) {
			$options['updated_before'] = $updated_before->format( 'Y-m-d' );
		}
		if ( ! empty( $sort_field ) ) {
			$options['sort_field'] = $sort_field;
		}
		if ( ! empty( $sort_order ) ) {
			$options['sort_order'] = $sort_order;
		}

		// Send request.
		return $this->get(
			endpoint: 'subscribers',
			args: $this->build_total_count_and_pagination_params(
				params: $options,
				include_total_count: $include_total_count,
				after_cursor: $after_cursor,
				before_cursor: $before_cursor,
				per_page: $per_page
			)
		);
	}

	/**
	 * Create a subscriber.
	 *
	 * Behaves as an upsert. If a subscriber with the provided email address does not exist,
	 * it creates one with the specified first name and state. If a subscriber with the provided
	 * email address already exists, it updates the first name.
	 *
	 * @param string                $email_address    Email Address.
	 * @param string                $first_name       First Name.
	 * @param string                $subscriber_state Subscriber State (active|bounced|cancelled|complained|inactive).
	 * @param array<string, string> $fields           Custom Fields.
	 *
	 * @since 2.0.0
	 *
	 * @see https://developers.convertkit.com/v4.html#create-a-subscriber
	 *
	 * @return mixed
	 */
	public function create_subscriber(
		string $email_address,
		string $first_name = '',
		string $subscriber_state = '',
		array $fields = array()
	) {
		// Build parameters.
		$options = array( 'email_address' => $email_address );

		if ( ! empty( $first_name ) ) {
			$options['first_name'] = $first_name;
		}
		if ( ! empty( $subscriber_state ) ) {
			$options['state'] = $subscriber_state;
		}
		if ( count( $fields ) ) {
			$options['fields'] = $fields;
		}

		// Send request.
		return $this->post(
			endpoint: 'subscribers',
			args: $options
		);
	}

	/**
	 * Create multiple subscribers.
	 *
	 * @param array<int,array<string,string>> $subscribers  Subscribers.
	 * @param string                          $callback_url URL to notify for large batch size when async processing complete.
	 *
	 * @since 2.0.0
	 *
	 * @see https://developers.convertkit.com/v4.html#bulk-create-subscribers
	 *
	 * @return mixed
	 */
	public function create_subscribers( array $subscribers, string $callback_url = '' ) {
		// Build parameters.
		$options = array( 'subscribers' => $subscribers );

		if ( ! empty( $callback_url ) ) {
			$options['callback_url'] = $callback_url;
		}

		// Send request.
		return $this->post(
			endpoint: 'bulk/subscribers',
			args: $options
		);
	}

	/**
	 * Get the ConvertKit subscriber ID associated with email address if it exists.
	 * Return false if subscriber not found.
	 *
	 * @param string $email_address Email Address.
	 *
	 * @throws \InvalidArgumentException If the email address is not a valid email format.
	 *
	 * @see https://developers.convertkit.com/v4.html#get-a-subscriber
	 *
	 * @return false|integer
	 */
	public function get_subscriber_id( string $email_address ) {
		$subscribers = $this->get(
			'subscribers',
			array( 'email_address' => $email_address )
		);

		if ( ! count( $subscribers->subscribers ) ) {
			$this->create_log( 'No subscribers' );
			return false;
		}

		// Return the subscriber's ID.
		return $subscribers->subscribers[0]->id;
	}

	/**
	 * Get subscriber by id
	 *
	 * @param integer $subscriber_id Subscriber ID.
	 *
	 * @see https://developers.convertkit.com/v4.html#get-a-subscriber
	 *
	 * @return false|integer
	 */
	public function get_subscriber( int $subscriber_id ) {
		return $this->get( sprintf( 'subscribers/%s', $subscriber_id ) );
	}

	/**
	 * Updates the information for a single subscriber.
	 *
	 * @param integer               $subscriber_id Existing Subscriber ID.
	 * @param string                $first_name    New First Name.
	 * @param string                $email_address New Email Address.
	 * @param array<string, string> $fields        Updated Custom Fields.
	 *
	 * @see https://developers.convertkit.com/v4.html#update-a-subscriber
	 *
	 * @return false|mixed
	 */
	public function update_subscriber(
		int $subscriber_id,
		string $first_name = '',
		string $email_address = '',
		array $fields = array()
	) {
		// Build parameters.
		$options = array();

		if ( ! empty( $first_name ) ) {
			$options['first_name'] = $first_name;
		}
		if ( ! empty( $email_address ) ) {
			$options['email_address'] = $email_address;
		}
		if ( ! empty( $fields ) ) {
			$options['fields'] = $fields;
		}

		// Send request.
		return $this->put(
			sprintf( 'subscribers/%s', $subscriber_id ),
			$options
		);
	}

	/**
	 * Unsubscribe an email address.
	 *
	 * @param string $email_address Email Address.
	 *
	 * @see https://developers.convertkit.com/v4.html#unsubscribe-subscriber
	 *
	 * @return false|object
	 */
	public function unsubscribe_by_email( string $email_address ) {
		return $this->post(
			sprintf(
				'subscribers/%s/unsubscribe',
				$this->get_subscriber_id( $email_address )
			)
		);
	}

	/**
	 * Unsubscribe the given subscriber ID.
	 *
	 * @param integer $subscriber_id Subscriber ID.
	 *
	 * @see https://developers.convertkit.com/v4.html#unsubscribe-subscriber
	 *
	 * @return false|object
	 */
	public function unsubscribe( int $subscriber_id ) {
		return $this->post( sprintf( 'subscribers/%s/unsubscribe', $subscriber_id ) );
	}

	/**
	 * Get a list of the tags for a subscriber.
	 *
	 * @param integer $subscriber_id       Subscriber ID.
	 * @param boolean $include_total_count To include the total count of records in the response, use true.
	 * @param string  $after_cursor        Return results after the given pagination cursor.
	 * @param string  $before_cursor       Return results before the given pagination cursor.
	 * @param integer $per_page            Number of results to return.
	 *
	 * @see https://developers.convertkit.com/v4.html#list-tags-for-a-subscriber
	 *
	 * @return false|array<int,\stdClass>
	 */
	public function get_subscriber_tags(
		int $subscriber_id,
		bool $include_total_count = false,
		string $after_cursor = '',
		string $before_cursor = '',
		int $per_page = 100
	) {
		return $this->get(
			endpoint: sprintf( 'subscribers/%s/tags', $subscriber_id ),
			args: $this->build_total_count_and_pagination_params(
				include_total_count: $include_total_count,
				after_cursor: $after_cursor,
				before_cursor: $before_cursor,
				per_page: $per_page
			)
		);
	}

	/**
	 * List broadcasts.
	 *
	 * @param boolean $include_total_count To include the total count of records in the response, use true.
	 * @param string  $after_cursor        Return results after the given pagination cursor.
	 * @param string  $before_cursor       Return results before the given pagination cursor.
	 * @param integer $per_page            Number of results to return.
	 *
	 * @see https://developers.convertkit.com/v4.html#list-broadcasts
	 *
	 * @return false|mixed
	 */
	public function get_broadcasts(
		bool $include_total_count = false,
		string $after_cursor = '',
		string $before_cursor = '',
		int $per_page = 100
	) {
		// Send request.
		return $this->get(
			endpoint: 'broadcasts',
			args: $this->build_total_count_and_pagination_params(
				include_total_count: $include_total_count,
				after_cursor: $after_cursor,
				before_cursor: $before_cursor,
				per_page: $per_page
			)
		);
	}

	/**
	 * Creates a broadcast.
	 *
	 * @param string               $subject           The broadcast email's subject.
	 * @param string               $content           The broadcast's email HTML content.
	 * @param string               $description       An internal description of this broadcast.
	 * @param boolean              $public            Specifies whether or not this is a public post.
	 * @param \DateTime            $published_at      Specifies the time that this post was published (applicable
	 *                                                only to public posts).
	 * @param \DateTime            $send_at           Time that this broadcast should be sent; leave blank to create
	 *                                                a draft broadcast. If set to a future time, this is the time that
	 *                                                the broadcast will be scheduled to send.
	 * @param string               $email_address     Sending email address; leave blank to use your account's
	 *                                                default sending email address.
	 * @param string               $email_template_id ID of the email template to use; leave blank to use your
	 *                                                account's default email template.
	 * @param string               $thumbnail_alt     Specify the ALT attribute of the public thumbnail image
	 *                                                (applicable only to public posts).
	 * @param string               $thumbnail_url     Specify the URL of the thumbnail image to accompany the broadcast
	 *                                                post (applicable only to public posts).
	 * @param string               $preview_text      Specify the preview text of the email.
	 * @param array<string,string> $subscriber_filter Filter subscriber(s) to send the email to.
	 *
	 * @see https://developers.convertkit.com/v4.html#create-a-broadcast
	 *
	 * @return false|object
	 */
	public function create_broadcast(
		string $subject = '',
		string $content = '',
		string $description = '',
		bool $public = false,
		\DateTime $published_at = null,
		\DateTime $send_at = null,
		string $email_address = '',
		string $email_template_id = '',
		string $thumbnail_alt = '',
		string $thumbnail_url = '',
		string $preview_text = '',
		array $subscriber_filter = array()
	) {
		$options = array(
			'email_template_id' => $email_template_id,
			'email_address'     => $email_address,
			'content'           => $content,
			'description'       => $description,
			'public'            => $public,
			'published_at'      => ( ! is_null( $published_at ) ? $published_at->format( 'Y-m-d H:i:s' ) : '' ),
			'send_at'           => ( ! is_null( $send_at ) ? $send_at->format( 'Y-m-d H:i:s' ) : '' ),
			'thumbnail_alt'     => $thumbnail_alt,
			'thumbnail_url'     => $thumbnail_url,
			'preview_text'      => $preview_text,
			'subject'           => $subject,
		);
		if ( count( $subscriber_filter ) ) {
			$options['subscriber_filter'] = $subscriber_filter;
		}

		// Iterate through options, removing blank entries.
		foreach ( $options as $key => $value ) {
			if ( is_string( $value ) && strlen( $value ) === 0 ) {
				unset( $options[ $key ] );
			}
		}

		// If the post isn't public, remove some options that don't apply.
		if ( ! $public ) {
			unset( $options['published_at'], $options['thumbnail_alt'], $options['thumbnail_url'] );
		}

		// Send request.
		return $this->post(
			endpoint: 'broadcasts',
			args: $options
		);
	}

	/**
	 * Retrieve a specific broadcast.
	 *
	 * @param integer $id Broadcast ID.
	 *
	 * @see https://developers.convertkit.com/v4.html#get-a-broadcast
	 *
	 * @return false|object
	 */
	public function get_broadcast( int $id ) {
		return $this->get( sprintf( 'broadcasts/%s', $id ) );
	}

	/**
	 * Get the statistics (recipient count, open rate, click rate, unsubscribe count,
	 * total clicks, status, and send progress) for a specific broadcast.
	 *
	 * @param integer $id Broadcast ID.
	 *
	 * @see https://developers.convertkit.com/v4.html#get-stats
	 *
	 * @return false|object
	 */
	public function get_broadcast_stats( int $id ) {
		return $this->get( sprintf( 'broadcasts/%s/stats', $id ) );
	}

	/**
	 * Updates a broadcast.
	 *
	 * @param integer              $id                Broadcast ID.
	 * @param string               $subject           The broadcast email's subject.
	 * @param string               $content           The broadcast's email HTML content.
	 * @param string               $description       An internal description of this broadcast.
	 * @param boolean              $public            Specifies whether or not this is a public post.
	 * @param \DateTime            $published_at      Specifies the time that this post was published (applicable
	 *                                                only to public posts).
	 * @param \DateTime            $send_at           Time that this broadcast should be sent; leave blank to create
	 *                                                a draft broadcast. If set to a future time, this is the time that
	 *                                                the broadcast will be scheduled to send.
	 * @param string               $email_address     Sending email address; leave blank to use your account's
	 *                                                default sending email address.
	 * @param string               $email_template_id ID of the email template to use; leave blank to use your
	 *                                                account's default email template.
	 * @param string               $thumbnail_alt     Specify the ALT attribute of the public thumbnail image
	 *                                                (applicable only to public posts).
	 * @param string               $thumbnail_url     Specify the URL of the thumbnail image to accompany the broadcast
	 *                                                post (applicable only to public posts).
	 * @param string               $preview_text      Specify the preview text of the email.
	 * @param array<string,string> $subscriber_filter Filter subscriber(s) to send the email to.
	 *
	 * @see https://developers.convertkit.com/#create-a-broadcast
	 *
	 * @return false|object
	 */
	public function update_broadcast(
		int $id,
		string $subject = '',
		string $content = '',
		string $description = '',
		bool $public = false,
		\DateTime $published_at = null,
		\DateTime $send_at = null,
		string $email_address = '',
		string $email_template_id = '',
		string $thumbnail_alt = '',
		string $thumbnail_url = '',
		string $preview_text = '',
		array $subscriber_filter = array()
	) {
		$options = array(
			'email_template_id' => $email_template_id,
			'email_address'     => $email_address,
			'content'           => $content,
			'description'       => $description,
			'public'            => $public,
			'published_at'      => ( ! is_null( $published_at ) ? $published_at->format( 'Y-m-d H:i:s' ) : '' ),
			'send_at'           => ( ! is_null( $send_at ) ? $send_at->format( 'Y-m-d H:i:s' ) : '' ),
			'thumbnail_alt'     => $thumbnail_alt,
			'thumbnail_url'     => $thumbnail_url,
			'preview_text'      => $preview_text,
			'subject'           => $subject,
		);
		if ( count( $subscriber_filter ) ) {
			$options['subscriber_filter'] = $subscriber_filter;
		}

		// Iterate through options, removing blank entries.
		foreach ( $options as $key => $value ) {
			if ( is_string( $value ) && strlen( $value ) === 0 ) {
				unset( $options[ $key ] );
			}
		}

		// If the post isn't public, remove some options that don't apply.
		if ( ! $public ) {
			unset( $options['published_at'], $options['thumbnail_alt'], $options['thumbnail_url'] );
		}

		// Send request.
		return $this->put(
			endpoint: sprintf( 'broadcasts/%s', $id ),
			args: $options
		);
	}

	/**
	 * Deletes an existing broadcast.
	 *
	 * @param integer $id Broadcast ID.
	 *
	 * @since 1.0.0
	 *
	 * @see https://developers.convertkit.com/v4.html#delete-a-broadcast
	 *
	 * @return false|object
	 */
	public function delete_broadcast( int $id ) {
		return $this->delete( sprintf( 'broadcasts/%s', $id ) );
	}

	/**
	 * List webhooks.
	 *
	 * @param boolean $include_total_count To include the total count of records in the response, use true.
	 * @param string  $after_cursor        Return results after the given pagination cursor.
	 * @param string  $before_cursor       Return results before the given pagination cursor.
	 * @param integer $per_page            Number of results to return.
	 *
	 * @since 2.0.0
	 *
	 * @see https://developers.convertkit.com/v4.html#list-webhooks
	 *
	 * @return false|mixed
	 */
	public function get_webhooks(
		bool $include_total_count = false,
		string $after_cursor = '',
		string $before_cursor = '',
		int $per_page = 100
	) {
		// Send request.
		return $this->get(
			endpoint: 'webhooks',
			args: $this->build_total_count_and_pagination_params(
				include_total_count: $include_total_count,
				after_cursor: $after_cursor,
				before_cursor: $before_cursor,
				per_page: $per_page
			)
		);
	}

	/**
	 * Creates a webhook that will be called based on the chosen event types.
	 *
	 * @param string $url       URL to receive event.
	 * @param string $event     Event to subscribe to.
	 * @param string $parameter Optional parameter depending on the event.
	 *
	 * @since 1.0.0
	 *
	 * @see https://developers.convertkit.com/v4.html#create-a-webhook
	 *
	 * @throws \InvalidArgumentException If the event is not supported.
	 *
	 * @return false|object
	 */
	public function create_webhook( string $url, string $event, string $parameter = '' ) {
		// Depending on the event, build the required event array structure.
		switch ( $event ) {
			case 'subscriber.subscriber_activate':
			case 'subscriber.subscriber_unsubscribe':
			case 'subscriber.subscriber_bounce':
			case 'subscriber.subscriber_complain':
			case 'purchase.purchase_create':
				$eventData = array( 'name' => $event );
				break;

			case 'subscriber.form_subscribe':
				$eventData = array(
					'name'    => $event,
					'form_id' => $parameter,
				);
				break;

			case 'subscriber.course_subscribe':
			case 'subscriber.course_complete':
				$eventData = array(
					'name'      => $event,
					'course_id' => $parameter,
				);
				break;

			case 'subscriber.link_click':
				$eventData = array(
					'name'            => $event,
					'initiator_value' => $parameter,
				);
				break;

			case 'subscriber.product_purchase':
				$eventData = array(
					'name'       => $event,
					'product_id' => $parameter,
				);
				break;

			case 'subscriber.tag_add':
			case 'subscriber.tag_remove':
				$eventData = array(
					'name'   => $event,
					'tag_id' => $parameter,
				);
				break;

			default:
				throw new \InvalidArgumentException( sprintf( 'The event %s is not supported', $event ) );
		}//end switch

		// Send request.
		return $this->post(
			'webhooks',
			array(
				'target_url' => $url,
				'event'      => $eventData,
			)
		);
	}

	/**
	 * Deletes an existing webhook.
	 *
	 * @param integer $id Webhook ID.
	 *
	 * @since 1.0.0
	 *
	 * @see https://developers.convertkit.com/v4.html#delete-a-webhook
	 *
	 * @return false|object
	 */
	public function delete_webhook( int $id ) {
		return $this->delete( sprintf( 'webhooks/%s', $id ) );
	}

	/**
	 * List custom fields.
	 *
	 * @param boolean $include_total_count To include the total count of records in the response, use true.
	 * @param string  $after_cursor        Return results after the given pagination cursor.
	 * @param string  $before_cursor       Return results before the given pagination cursor.
	 * @param integer $per_page            Number of results to return.
	 *
	 * @since 1.0.0
	 *
	 * @see https://developers.convertkit.com/v4.html#list-custom-fields
	 *
	 * @return false|mixed
	 */
	public function get_custom_fields(
		bool $include_total_count = false,
		string $after_cursor = '',
		string $before_cursor = '',
		int $per_page = 100
	) {
		// Send request.
		return $this->get(
			endpoint: 'custom_fields',
			args: $this->build_total_count_and_pagination_params(
				include_total_count: $include_total_count,
				after_cursor: $after_cursor,
				before_cursor: $before_cursor,
				per_page: $per_page
			)
		);
	}

	/**
	 * Creates a custom field.
	 *
	 * @param string $label Custom Field label.
	 *
	 * @since 1.0.0
	 *
	 * @see https://developers.convertkit.com/v4.html#create-a-custom-field
	 *
	 * @return false|object
	 */
	public function create_custom_field( string $label ) {
		return $this->post(
			endpoint: 'custom_fields',
			args: array( 'label' => $label )
		);
	}

	/**
	 * Creates multiple custom fields.
	 *
	 * @param array<string> $labels       Custom Fields labels.
	 * @param string        $callback_url URL to notify for large batch size when async processing complete.
	 *
	 * @since 1.0.0
	 *
	 * @see https://developers.convertkit.com/v4.html#bulk-create-custom-fields
	 *
	 * @return false|object
	 */
	public function create_custom_fields( array $labels, string $callback_url = '' ) {
		// Build parameters.
		$options = array(
			'custom_fields' => array(),
		);
		foreach ( $labels as $i => $label ) {
			$options['custom_fields'][] = array(
				'label' => (string) $label,
			);
		}

		if ( ! empty( $callback_url ) ) {
			$options['callback_url'] = $callback_url;
		}

		// Send request.
		return $this->post(
			endpoint: 'bulk/custom_fields',
			args: $options
		);
	}

	/**
	 * Updates an existing custom field.
	 *
	 * @param integer $id    Custom Field ID.
	 * @param string  $label Updated Custom Field label.
	 *
	 * @since 1.0.0
	 *
	 * @see https://developers.convertkit.com/v4.html#update-a-custom-field
	 *
	 * @return false|object
	 */
	public function update_custom_field( int $id, string $label ) {
		return $this->put(
			endpoint: sprintf( 'custom_fields/%s', $id ),
			args: array( 'label' => $label )
		);
	}

	/**
	 * Deletes an existing custom field.
	 *
	 * @param integer $id Custom Field ID.
	 *
	 * @since 1.0.0
	 *
	 * @see https://developers.convertkit.com/#destroy-field
	 *
	 * @return false|object
	 */
	public function delete_custom_field( int $id ) {
		return $this->delete( sprintf( 'custom_fields/%s', $id ) );
	}

	/**
	 * List purchases.
	 *
	 * @param boolean $include_total_count To include the total count of records in the response, use true.
	 * @param string  $after_cursor        Return results after the given pagination cursor.
	 * @param string  $before_cursor       Return results before the given pagination cursor.
	 * @param integer $per_page            Number of results to return.
	 *
	 * @since 1.0.0
	 *
	 * @see https://developers.convertkit.com/v4.html#list-purchases
	 *
	 * @return false|mixed
	 */
	public function get_purchases(
		bool $include_total_count = false,
		string $after_cursor = '',
		string $before_cursor = '',
		int $per_page = 100
	) {
		// Send request.
		return $this->get(
			endpoint: 'purchases',
			args: $this->build_total_count_and_pagination_params(
				include_total_count: $include_total_count,
				after_cursor: $after_cursor,
				before_cursor: $before_cursor,
				per_page: $per_page
			)
		);
	}

	/**
	 * Retuns a specific purchase.
	 *
	 * @param integer $purchase_id Purchase ID.
	 *
	 * @see https://developers.convertkit.com/v4.html#get-a-purchase
	 *
	 * @return false|object
	 */
	public function get_purchase( int $purchase_id ) {
		return $this->get( sprintf( 'purchases/%s', $purchase_id ) );
	}

	/**
	 * Creates a purchase.
	 *
	 * @param string                         $email_address    Email Address.
	 * @param string                         $transaction_id   Transaction ID.
	 * @param array<string,int|float|string> $products         Products.
	 * @param string                         $currency         ISO Currency Code.
	 * @param string                         $first_name       First Name.
	 * @param string                         $status           Order Status.
	 * @param float                          $subtotal         Subtotal.
	 * @param float                          $tax              Tax.
	 * @param float                          $shipping         Shipping.
	 * @param float                          $discount         Discount.
	 * @param float                          $total            Total.
	 * @param \DateTime                      $transaction_time Transaction date and time.
	 *
	 * @see https://developers.convertkit.com/v4.html#create-a-purchase
	 *
	 * @return false|object
	 */
	public function create_purchase(
		string $email_address,
		string $transaction_id,
		array $products,
		string $currency = 'USD',
		string $first_name = null,
		string $status = null,
		float $subtotal = 0,
		float $tax = 0,
		float $shipping = 0,
		float $discount = 0,
		float $total = 0,
		\DateTime $transaction_time = null
	) {
		// Build parameters.
		$options = array(
			// Required fields.
			'email_address'    => $email_address,
			'transaction_id'   => $transaction_id,
			'products'         => $products,
			'currency'         => $currency, // Required, but if not provided, API will default to USD.

			// Optional fields.
			'first_name'       => $first_name,
			'status'           => $status,
			'subtotal'         => $subtotal,
			'tax'              => $tax,
			'shipping'         => $shipping,
			'discount'         => $discount,
			'total'            => $total,
			'transaction_time' => ( ! is_null( $transaction_time ) ? $transaction_time->format( 'Y-m-d H:i:s' ) : '' ),
		);

		// Iterate through options, removing blank and null entries.
		foreach ( $options as $key => $value ) {
			if ( is_null( $value ) ) {
				unset( $options[ $key ] );
				continue;
			}

			if ( is_string( $value ) && strlen( $value ) === 0 ) {
				unset( $options[ $key ] );
			}
		}

		return $this->post( 'purchases', $options );
	}

	/**
	 * List segments.
	 *
	 * @param boolean $include_total_count To include the total count of records in the response, use true.
	 * @param string  $after_cursor        Return results after the given pagination cursor.
	 * @param string  $before_cursor       Return results before the given pagination cursor.
	 * @param integer $per_page            Number of results to return.
	 *
	 * @since 2.0.0
	 *
	 * @see https://developers.convertkit.com/v4.html#convertkit-api-segments
	 *
	 * @return false|mixed
	 */
	public function get_segments(
		bool $include_total_count = false,
		string $after_cursor = '',
		string $before_cursor = '',
		int $per_page = 100
	) {
		// Send request.
		return $this->get(
			endpoint: 'segments',
			args: $this->build_total_count_and_pagination_params(
				include_total_count: $include_total_count,
				after_cursor: $after_cursor,
				before_cursor: $before_cursor,
				per_page: $per_page
			)
		);
	}

	/**
	 * Get markup from ConvertKit for the provided $url.
	 *
	 * Supports legacy forms and legacy landing pages.
	 *
	 * Forms and Landing Pages should be embedded using the supplied JS embed script in
	 * the API response when using get_forms() or get_landing_pages().
	 *
	 * @param string $url URL of HTML page.
	 *
	 * @throws \InvalidArgumentException If the URL is not a valid URL format.
	 * @throws \Exception If parsing the legacy form or landing page failed.
	 *
	 * @return false|string
	 */
	public function get_resource( string $url ) {
		if ( ! filter_var( $url, FILTER_VALIDATE_URL ) ) {
			throw new \InvalidArgumentException();
		}

		$resource = '';

		$this->create_log( sprintf( 'Getting resource %s', $url ) );

		// Fetch the resource.
		$request  = new Request(
			method: 'GET',
			uri: $url,
			headers: $this->request_headers(
				type: 'text/html',
				auth: false
			),
		);
		$response = $this->client->send( $request );

		// Fetch HTML.
		$body = $response->getBody()->getContents();

		// Forcibly tell DOMDocument that this HTML uses the UTF-8 charset.
		// <meta charset="utf-8"> isn't enough, as DOMDocument still interprets the HTML as ISO-8859,
		// which breaks character encoding.
		// Use of mb_convert_encoding() with HTML-ENTITIES is deprecated in PHP 8.2, so we have to use this method.
		// If we don't, special characters render incorrectly.
		$body = str_replace(
			'<head>',
			'<head>' . "\n" . '<meta http-equiv="Content-Type" content="text/html; charset=utf-8">',
			$body
		);

		// Get just the scheme and host from the URL.
		$url_scheme_host_only = parse_url( $url, PHP_URL_SCHEME ) . '://' . parse_url( $url, PHP_URL_HOST );

		// Load the HTML into a DOMDocument.
		libxml_use_internal_errors( true );
		$html = new \DOMDocument();
		$html->loadHTML( $body );

		// Convert any relative URLs to absolute URLs in the HTML DOM.
		$this->convert_relative_to_absolute_urls( $html->getElementsByTagName( 'a' ), 'href', $url_scheme_host_only );
		$this->convert_relative_to_absolute_urls( $html->getElementsByTagName( 'link' ), 'href', $url_scheme_host_only );
		$this->convert_relative_to_absolute_urls( $html->getElementsByTagName( 'img' ), 'src', $url_scheme_host_only );
		$this->convert_relative_to_absolute_urls( $html->getElementsByTagName( 'script' ), 'src', $url_scheme_host_only );
		$this->convert_relative_to_absolute_urls( $html->getElementsByTagName( 'form' ), 'action', $url_scheme_host_only );

		// Save HTML.
		$resource = $html->saveHTML();

		// If the result is false, return a blank string.
		if ( ! $resource ) {
			throw new \Exception( sprintf( 'Could not parse %s', $url ) );
		}

		// Remove some HTML tags that DOMDocument adds, returning the output.
		// We do this instead of using LIBXML_HTML_NOIMPLIED in loadHTML(), because Legacy Forms
		// are not always contained in a single root / outer element, which is required for
		// LIBXML_HTML_NOIMPLIED to correctly work.
		$resource = $this->strip_html_head_body_tags( $resource );

		return $resource;
	}

	/**
	 * Converts any relative URls to absolute, fully qualified HTTP(s) URLs for the given
	 * DOM Elements.
	 *
	 * @param \DOMNodeList<\DOMElement> $elements  Elements.
	 * @param string                    $attribute HTML Attribute.
	 * @param string                    $url       Absolute URL to prepend to relative URLs.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	private function convert_relative_to_absolute_urls( \DOMNodeList $elements, string $attribute, string $url ) { // phpcs:ignore Squiz.Commenting.FunctionComment.IncorrectTypeHint, Generic.Files.LineLength.TooLong
		// Anchor hrefs.
		foreach ( $elements as $element ) {
			// Skip if the attribute's value is empty.
			if ( empty( $element->getAttribute( $attribute ) ) ) {
				continue;
			}

			// Skip if the attribute's value is a fully qualified URL.
			if ( filter_var( $element->getAttribute( $attribute ), FILTER_VALIDATE_URL ) ) {
				continue;
			}

			// Skip if this is a Google Font CSS URL.
			if ( strpos( $element->getAttribute( $attribute ), '//fonts.googleapis.com' ) !== false ) {
				continue;
			}

			// If here, the attribute's value is a relative URL, missing the http(s) and domain.
			// Prepend the URL to the attribute's value.
			$element->setAttribute( $attribute, $url . $element->getAttribute( $attribute ) );
		}
	}

	/**
	 * Strips <html>, <head> and <body> opening and closing tags from the given markup,
	 * as well as the Content-Type meta tag we might have added in get_html().
	 *
	 * @param string $markup HTML Markup.
	 *
	 * @since 1.0.0
	 *
	 * @return string              HTML Markup
	 */
	private function strip_html_head_body_tags( string $markup ) {
		$markup = str_replace( '<html>', '', $markup );
		$markup = str_replace( '</html>', '', $markup );
		$markup = str_replace( '<head>', '', $markup );
		$markup = str_replace( '</head>', '', $markup );
		$markup = str_replace( '<body>', '', $markup );
		$markup = str_replace( '</body>', '', $markup );
		$markup = str_replace( '<meta http-equiv="Content-Type" content="text/html; charset=utf-8">', '', $markup );

		return $markup;
	}

	/**
	 * Adds total count and pagination parameters to the given array of existing API parameters.
	 *
	 * @param array<string, string|integer|bool> $params              API parameters.
	 * @param boolean                            $include_total_count Return total count of records.
	 * @param string                             $after_cursor        Return results after the given pagination cursor.
	 * @param string                             $before_cursor       Return results before the given pagination cursor.
	 * @param integer                            $per_page            Number of results to return.
	 *
	 * @since 2.0.0
	 *
	 * @return array<string, string|integer|bool>
	 */
	private function build_total_count_and_pagination_params(
		array $params = array(),
		bool $include_total_count = false,
		string $after_cursor = '',
		string $before_cursor = '',
		int $per_page = 100
	) {
		$params['include_total_count'] = $include_total_count;
		if ( ! empty( $after_cursor ) ) {
			$params['after'] = $after_cursor;
		}
		if ( ! empty( $before_cursor ) ) {
			$params['before'] = $before_cursor;
		}
		if ( ! empty( $per_page ) ) {
			$params['per_page'] = $per_page;
		}

		return $params;
	}

	/**
	 * Gets all posts from the API.
	 *
	 * @since   1.0.0
	 *
	 * @param   int $posts_per_request   Number of Posts to fetch in each request.
	 * @return  WP_Error|array
	 */
	public function get_all_posts( $posts_per_request = 50 ) {

		$this->log( 'API: get_all_posts()' );

		// Sanitize some parameters.
		$posts_per_request = absint( $posts_per_request );

		// Sanity check that parameters aren't outside of the bounds as defined by the API.
		if ( $posts_per_request < 1 ) {
			return new WP_Error( 'convertkit_api_error', $this->get_error_message( 'get_all_posts_posts_per_request_bound_too_low' ) );
		}
		if ( $posts_per_request > 50 ) {
			return new WP_Error( 'convertkit_api_error', $this->get_error_message( 'get_all_posts_posts_per_request_bound_too_high' ) );
		}

		// Define an array to store the posts in.
		$posts = array();

		// Mock the response to start the while loop.
		$response = array(
			'page'        => 0, // Start on page zero, as the below loop will add 1 to this.
			'total_pages' => 1, // We always know there will be one page of posts.
		);

		// Iterate through each page of posts.
		while ( absint( $response['total_pages'] ) >= absint( $response['page'] ) + 1 ) {
			// Fetch posts.
			$response = $this->get_posts( absint( $response['page'] ) + 1, $posts_per_request );

			// Bail if an error occured.
			if ( is_wp_error( $response ) ) {
				return $response;
			}

			// Exit loop if no posts exist.
			if ( ! count( $response ) ) {
				break;
			}

			// Append posts to array.
			foreach ( $response['posts'] as $post ) {
				$posts[ $post['id'] ] = $post;
			}
		}

		// If no posts exist, log an error.
		if ( ! count( $posts ) ) {
			$this->log( 'API: get_posts(): Error: No broadcasts exist in ConvertKit.' );
		}

		// Return posts.
		return $posts;

	}

	/**
	 * Gets posts from the API.
	 *
	 * @since   1.0.0
	 *
	 * @param   int $page       Page number.
	 * @param   int $per_page   Number of Posts to return.
	 * @return  WP_Error|array
	 */
	public function get_posts( $page = 1, $per_page = 10 ) {

		$this->log( 'API: get_posts()' );

		// Sanitize some parameters.
		$page     = absint( $page );
		$per_page = absint( $per_page );

		// Sanity check that parameters aren't outside of the bounds as defined by the API.
		if ( $page < 1 ) {
			return new WP_Error( 'convertkit_api_error', $this->get_error_message( 'get_posts_page_parameter_bound_too_low' ) );
		}
		if ( $per_page < 1 ) {
			return new WP_Error( 'convertkit_api_error', $this->get_error_message( 'get_posts_per_page_parameter_bound_too_low' ) );
		}
		if ( $per_page > 50 ) {
			return new WP_Error( 'convertkit_api_error', $this->get_error_message( 'get_posts_per_page_parameter_bound_too_high' ) );
		}

		$posts = array();

		// Send request.
		$response = $this->get(
			'posts',
			array(
				'api_key'    => $this->api_key,
				'api_secret' => $this->api_secret,
				'page'       => $page,
				'per_page'   => $per_page,
			)
		);

		// If an error occured, return WP_Error.
		if ( is_wp_error( $response ) ) {
			$this->log( 'API: get_posts(): Error: ' . $response->get_error_message() );
			return $response;
		}

		// If the response isn't an array as we expect, log that no posts exist and return a blank array.
		if ( ! is_array( $response['posts'] ) ) {
			$this->log( 'API: get_posts(): Error: No broadcasts exist in ConvertKit.' );
			return new WP_Error( 'convertkit_api_error', $this->get_error_message( 'response_type_unexpected' ) );
		}

		// If no posts exist, log that no posts exist and return a blank array.
		if ( ! count( $response['posts'] ) ) {
			$this->log( 'API: get_posts(): Error: No broadcasts exist in ConvertKit.' );
			return $posts;
		}

		return $response;

	}

	/**
	 * Gets a specific post.
	 *
	 * @since   1.3.8
	 *
	 * @param   int $post_id   Post ID.
	 * @return  WP_Error|array
	 */
	public function get_post( $post_id ) {

		$this->log( 'API: get_post(): [ post_id: ' . $post_id . ']' );

		// Send request.
		$response = $this->get(
			sprintf( 'posts/%s', $post_id ),
			array(
				'api_secret' => $this->api_secret,
			)
		);

		// If an error occured, return WP_Error.
		if ( is_wp_error( $response ) ) {
			$this->log( 'API: get_posts(): Error: ' . $response->get_error_message() );
			return $response;
		}

		// If the response contains a message, an error occured.
		// Log and return it now.
		if ( array_key_exists( 'message', $response ) ) {
			$error = new WP_Error(
				'convertkit_api_error',
				$response['message']
			);

			$this->log( 'API: get_post(): Error: ' . $error->get_error_message() );
			return $error;
		}

		return $response['post'];

	}

	/**
	 * Fetches all products from the API.
	 *
	 * @since   1.1.0
	 *
	 * @return  WP_Error|array
	 */
	public function get_products() {

		$this->log( 'API: get_products()' );

		$products = array();

		// Send request.
		$response = $this->get(
			'products',
			array(
				'api_key'    => $this->api_key,
				'api_secret' => $this->api_secret,
			)
		);

		// If an error occured, log and return it now.
		if ( is_wp_error( $response ) ) {
			$this->log( 'API: get_products(): Error: ' . $response->get_error_message() );
			return $response;
		}

		// If the response isn't an array as we expect, log that no products exist and return a blank array.
		if ( ! is_array( $response['products'] ) ) {
			$this->log( 'API: get_products(): Error: No products exist in ConvertKit.' );
			return new WP_Error( 'convertkit_api_error', $this->get_error_message( 'response_type_unexpected' ) );
		}

		// If no products exist, log that no products exist and return a blank array.
		if ( ! count( $response['products'] ) ) {
			$this->log( 'API: get_products(): Error: No products exist in ConvertKit.' );
			return $products;
		}

		foreach ( $response['products'] as $product ) {
			$products[ $product['id'] ] = $product;
		}

		return $products;

	}

	/**
	 * Sends an email to the given email address, which will contain a ConvertKit link
	 * which the subscriber can click to authenticate themselves.
	 *
	 * Upon successful authentication, the subscriber will be redirected from the ConvertKit
	 * link to the given redirect URL.
	 *
	 * @since   1.3.0
	 *
	 * @param   string $email          Email Address.
	 * @param   string $redirect_url   Redirect URL.
	 * @return  WP_Error|string
	 */
	public function subscriber_authentication_send_code( $email, $redirect_url ) {

		$this->log( 'API: subscriber_authentication_send_code(): [ email: ' . $email . ', redirect_url: ' . $redirect_url . ']' );

		// Sanitize some parameters.
		$email        = trim( $email );
		$redirect_url = trim( $redirect_url );

		// Return error if no email address or redirect URL is specified.
		if ( empty( $email ) ) {
			return new WP_Error( 'convertkit_api_error', $this->get_error_message( 'subscriber_authentication_send_code_email_empty' ) );
		}
		if ( empty( $redirect_url ) ) {
			return new WP_Error( 'convertkit_api_error', $this->get_error_message( 'subscriber_authentication_send_code_redirect_url_empty' ) );
		}

		// Return error if an invalid redirect URL is specified.
		if ( ! filter_var( $redirect_url, FILTER_VALIDATE_URL ) ) {
			return new WP_Error( 'convertkit_api_error', $this->get_error_message( 'subscriber_authentication_send_code_redirect_url_invalid' ) );
		}

		// Send request.
		$response = $this->post(
			'subscriber_authentication/send_code',
			array(
				'api_key'       => $this->api_key,
				'api_secret'    => $this->api_secret,
				'email_address' => $email,
				'redirect_url'  => $redirect_url,
			)
		);

		// If an error occured, log and return it now.
		if ( is_wp_error( $response ) ) {
			$this->log( 'API: subscriber_authentication_send_code(): Error: ' . $response->get_error_message() );
			return $response;
		}

		// Confirm that a token was supplied in the response.
		if ( ! isset( $response['token'] ) ) {
			$this->log( 'API: ' . $this->get_error_message( 'subscriber_authentication_send_code_response_token_missing' ) );
			return new WP_Error( 'convertkit_api_error', $this->get_error_message( 'subscriber_authentication_send_code_response_token_missing' ) );
		}

		// Return token, which is used with the subscriber code (sent by email) when subsequently calling subscriber_authentication_verify().
		return $response['token'];

	}

	/**
	 * Verifies the given token and subscriber code, which are included in the link
	 * sent by email in the subscriber_authentication_send_code() step.
	 *
	 * @since   1.3.0
	 *
	 * @param   string $token              Token.
	 * @param   string $subscriber_code    Subscriber Code.
	 * @return  WP_Error|string
	 */
	public function subscriber_authentication_verify( $token, $subscriber_code ) {

		$this->log( 'API: subscriber_authentication_verify(): [ token: ' . $this->mask_string( $token ) . ', subscriber_code: ' . $this->mask_string( $subscriber_code ) . ']' );

		// Sanitize some parameters.
		$token           = trim( $token );
		$subscriber_code = trim( $subscriber_code );

		// Return error if no email address or redirect URL is specified.
		if ( empty( $token ) ) {
			return new WP_Error( 'convertkit_api_error', $this->get_error_message( 'subscriber_authentication_verify_token_empty' ) );
		}
		if ( empty( $subscriber_code ) ) {
			return new WP_Error( 'convertkit_api_error', $this->get_error_message( 'subscriber_authentication_verify_subscriber_code_empty' ) );
		}

		// Send request.
		$response = $this->post(
			'subscriber_authentication/verify',
			array(
				'api_key'         => $this->api_key,
				'api_secret'      => $this->api_secret,
				'token'           => $token,
				'subscriber_code' => $subscriber_code,
			)
		);

		// If an error occured, log and return it now.
		if ( is_wp_error( $response ) ) {
			$this->log( 'API: subscriber_authentication_verify(): Error: ' . $response->get_error_message() );
			return new WP_Error( 'convertkit_api_error', $this->get_error_message( 'subscriber_authentication_verify_response_error' ) );
		}

		// Confirm that a subscriber ID was supplied in the response.
		if ( ! isset( $response['subscriber_id'] ) ) {
			$this->log( 'API: ' . $this->get_error_message( 'subscriber_authentication_verify_response_error' ) );
			return new WP_Error( 'convertkit_api_error', $this->get_error_message( 'subscriber_authentication_verify_response_error' ) );
		}

		// Return subscriber ID.  This is a signed ID valid for 90 days, instead of the subscriber ID integer.
		// This can be used when calling profile().
		return $response['subscriber_id'];

	}

	/**
	 * Returns the subscriber's ID and products they are subscribed to for the given
	 * signed subscriber ID.
	 *
	 * @since   1.3.0
	 *
	 * @param   string $signed_subscriber_id   Signed Subscriber ID (i.e. from subscriber_authentication_verify()).
	 * @return  WP_Error|array
	 */
	public function profile( $signed_subscriber_id ) {

		$this->log( 'API: profile(): [ signed_subscriber_id: ' . $this->mask_string( $signed_subscriber_id ) . ' ]' );

		// Trim some parameters.
		$signed_subscriber_id = trim( $signed_subscriber_id );

		// Return error if no signed subscribed id is specified.
		if ( empty( $signed_subscriber_id ) ) {
			return new WP_Error( 'convertkit_api_error', $this->get_error_message( 'profiles_signed_subscriber_id_empty' ) );
		}

		// Send request.
		$response = $this->get(
			'profile/' . $signed_subscriber_id,
			array(
				'api_key'    => $this->api_key,
				'api_secret' => $this->api_secret,
			)
		);

		// If an error occured, log and return it now.
		if ( is_wp_error( $response ) ) {
			$this->log( 'API: profile(): Error: ' . $response->get_error_message() );
			return $response;
		}

		// If the response contains a message, an error occured.
		// Log and return it now.
		if ( array_key_exists( 'message', $response ) ) {
			$error = new WP_Error(
				'convertkit_api_error',
				$response['message']
			);

			$this->log( 'API: profile(): Error: ' . $error->get_error_message() );
			return $error;
		}

		// Return profile data (subscriber ID, subscribed products).
		return $response;

	}

	/**
	 * Get HTML from ConvertKit for the given Legacy Form ID.
	 *
	 * This isn't specifically an API function, but for now it's best suited here.
	 *
	 * @param   int $id     Form ID.
	 * @return  WP_Error|string     HTML
	 */
	public function get_form_html( $id ) {

		$this->log( 'API: get_form_html(): [ id: ' . $id . ']' );

		// Define Legacy Form URL.
		$url = add_query_arg(
			array(
				'k' => $this->api_key,
				'v' => 2,
			),
			'https://api.convertkit.com/forms/' . $id . '/embed'
		);

		// Get HTML.
		$body = $this->get_html( $url );

		// Log if an error occured.
		if ( is_wp_error( $body ) ) {
			$this->log( 'API: get_form_html(): Error: ' . $body->get_error_message() );
		}

		return $body;

	}

	/**
	 * Get HTML from ConvertKit for the given Landing Page URL.
	 *
	 * This isn't specifically an API function, but for now it's best suited here.
	 *
	 * @param   string $url     URL of Landing Page.
	 * @param   bool   $debug   Enable debugging.
	 * @return  WP_Error|string HTML
	 */
	public function get_landing_page_html( $url, $debug = false ) {

		$this->log( 'API: get_landing_page_html(): [ url: ' . $url . ']' );

		// Get HTML.
		$body = $this->get_html( $url, false );

		// Log and return WP_Error if an error occured.
		if ( is_wp_error( $body ) ) {
			$this->log( 'API: get_landing_page_html(): Error: ' . $body->get_error_message() );
			return $body;
		}

		// Define convertkit JS object.
		$js_convertkit_object = array(
			'ajaxurl' => admin_url( 'admin-ajax.php' ),
			'debug'   => $debug,
			'nonce'   => wp_create_nonce( 'convertkit' ),
		);

		// Inject JS for subscriber forms to work.
		// wp_enqueue_script() isn't called when we load a Landing Page, so we can't use it.
		// phpcs:disable WordPress.WP.EnqueuedResources
		$script  = "<script type='text/javascript' src='" . $this->plugin_url . 'resources/frontend/js/convertkit.js?ver=' . $this->plugin_version . "'></script>";
		$script .= "<script type='text/javascript'>/* <![CDATA[ */var convertkit = " . wp_json_encode( $js_convertkit_object ) . ';/* ]]> */</script>';
		// phpcs:enable

		$body = str_replace( '</head>', '</head>' . $script, $body );

		return $body;

	}

	/**
	 * Returns the recommendations script URL for this account from the API,
	 * used to display the Creator Network modal when a form is submitted.
	 *
	 * @since   1.3.7
	 *
	 * @return  WP_Error|array
	 */
	public function recommendations_script() {

		$this->log( 'API: recommendations_script()' );

		return $this->get(
			'recommendations_script',
			array(
				'api_secret' => $this->api_secret,
			)
		);

	}

	/**
	 * Adds the given entry to the log file, if debugging is enabled.
	 *
	 * @since   1.0.0
	 *
	 * @param   string $entry  Log Entry.
	 */
	public function log( $entry ) {

		// Don't log this entry if debugging is disabled.
		if ( ! $this->debug ) {
			return;
		}

		// Don't log this entry if the logging class was not initialized.
		if ( ! $this->log ) {
			return;
		}

		// Pass the request to the ConvertKit_Log class.
		$this->log->add( $entry );

	}

	/**
	 * Get HTML for the given URL.
	 *
	 * This isn't specifically an API function, but for now it's best suited here.
	 *
	 * @param   string $url    URL of Form or Landing Page.
	 * @param   bool   $body_only   Return HTML between <body> and </body> tags only.
	 * @return  WP_Error|string
	 */
	private function get_html( $url, $body_only = true ) {

		// Get HTML from URL.
		$result = wp_remote_get(
			$url,
			array(
				'Accept-Encoding' => 'gzip',
				'timeout'         => $this->get_timeout(),
				'user-agent'      => $this->get_user_agent(),
			)
		);

		// If an error occured, log and return it now.
		if ( is_wp_error( $result ) ) {
			return $result;
		}

		// Fetch HTTP response code and body.
		$http_response_code = wp_remote_retrieve_response_code( $result );
		$body               = wp_remote_retrieve_body( $result );

		// If the body appears to be JSON containing an error, the request for a Legacy Form
		// through api.convertkit.com failed, so return a WP_Error now.
		if ( $this->is_json( $body ) ) {
			$json = json_decode( $body );
			return new WP_Error(
				'convertkit_api_error',
				sprintf(
					'ConvertKit: %s',
					$json->error_message
				)
			);
		}

		// If the HTML is missing the <html> tag, it's likely to be a legacy form.
		// Wrap it in <html>, <head> and <body> tags now, so we can inject the UTF-8 Content-Type meta tag.
		if ( strpos( $body, '<html>' ) === false ) {
			$body = '<html><head></head><body>' . $body . '</body></html>';
		}

		// Forcibly tell DOMDocument that this HTML uses the UTF-8 charset.
		// <meta charset="utf-8"> isn't enough, as DOMDocument still interprets the HTML as ISO-8859, which breaks character encoding
		// Use of mb_convert_encoding() with HTML-ENTITIES is deprecated in PHP 8.2, so we have to use this method.
		// If we don't, special characters render incorrectly.
		$body = str_replace( '<head>', '<head>' . "\n" . '<meta http-equiv="Content-Type" content="text/html; charset=utf-8">', $body );

		// Get just the scheme and host from the URL.
		$url_scheme           = wp_parse_url( $url );
		$url_scheme_host_only = $url_scheme['scheme'] . '://' . $url_scheme['host'];

		// Load the HTML into a DOMDocument.
		libxml_use_internal_errors( true );
		$html = new DOMDocument();
		if ( $body_only ) {
			// Prevent DOMDocument from including a doctype on saveHTML().
			// We don't use LIBXML_HTML_NOIMPLIED, as it requires a single root element, which Legacy Forms don't have.
			$html->loadHTML( $body, LIBXML_HTML_NODEFDTD );
		} else {
			$html->loadHTML( $body );
		}

		// Convert any relative URLs to absolute URLs in the HTML DOM.
		$this->convert_relative_to_absolute_urls( $html->getElementsByTagName( 'a' ), 'href', $url_scheme_host_only );
		$this->convert_relative_to_absolute_urls( $html->getElementsByTagName( 'link' ), 'href', $url_scheme_host_only );
		$this->convert_relative_to_absolute_urls( $html->getElementsByTagName( 'img' ), 'src', $url_scheme_host_only );
		$this->convert_relative_to_absolute_urls( $html->getElementsByTagName( 'script' ), 'src', $url_scheme_host_only );
		$this->convert_relative_to_absolute_urls( $html->getElementsByTagName( 'form' ), 'action', $url_scheme_host_only );

		// If the entire HTML needs to be returned, return it now.
		if ( ! $body_only ) {
			return $html->saveHTML();
		}

		// Remove some HTML tags that DOMDocument adds, returning the output.
		// We do this instead of using LIBXML_HTML_NOIMPLIED in loadHTML(), because Legacy Forms are not always contained in
		// a single root / outer element, which is required for LIBXML_HTML_NOIMPLIED to correctly work.
		return $this->strip_html_head_body_tags( $html->saveHTML() );

	}

	/**
	 * Determines if the given string is JSON.
	 *
	 * @since   1.0.0
	 *
	 * @param   string $json_string     Possible JSON String.
	 * @return  bool                    Is JSON String.
	 */
	private function is_json( $json_string ) {

		json_decode( $json_string );
		return json_last_error() === JSON_ERROR_NONE;

	}

	/**
	 * Converts any relative URls to absolute, fully qualified HTTP(s) URLs for the given
	 * DOM Elements.
	 *
	 * @since   1.0.0
	 *
	 * @param   DOMNodeList<DOMElement> $elements   Elements.
	 * @param   string                  $attribute  HTML Attribute.
	 * @param   string                  $url        Absolute URL to prepend to relative URLs.
	 */
	private function convert_relative_to_absolute_urls( $elements, $attribute, $url ) {

		// Anchor hrefs.
		foreach ( $elements as $element ) {
			// Skip if the attribute's value is empty.
			if ( empty( $element->getAttribute( $attribute ) ) ) {
				continue;
			}

			// Skip if the attribute's value is a fully qualified URL.
			if ( filter_var( $element->getAttribute( $attribute ), FILTER_VALIDATE_URL ) ) {
				continue;
			}

			// Skip if this is a Google Font CSS URL.
			if ( strpos( $element->getAttribute( $attribute ), '//fonts.googleapis.com' ) !== false ) {
				continue;
			}

			// If here, the attribute's value is a relative URL, missing the http(s) and domain.
			// Prepend the URL to the attribute's value.
			$element->setAttribute( $attribute, $url . $element->getAttribute( $attribute ) );
		}

	}

	/**
	 * Strips <html>, <head> and <body> opening and closing tags from the given markup,
	 * as well as the Content-Type meta tag we might have added in get_html().
	 *
	 * @since   1.0.0
	 *
	 * @param   string $markup     HTML Markup.
	 * @return  string              HTML Markup
	 * */
	private function strip_html_head_body_tags( $markup ) {

		$markup = str_replace( '<html>', '', $markup );
		$markup = str_replace( '</html>', '', $markup );
		$markup = str_replace( '<head>', '', $markup );
		$markup = str_replace( '</head>', '', $markup );
		$markup = str_replace( '<body>', '', $markup );
		$markup = str_replace( '</body>', '', $markup );
		$markup = str_replace( '<meta http-equiv="Content-Type" content="text/html; charset=utf-8">', '', $markup );

		return $markup;

	}

	/**
	 * Gets all forms and landing pages from the API.
	 *
	 * @since   1.0.0
	 *
	 * @return  WP_Error|array
	 */
	private function get_forms_landing_pages() {

		// Send request.
		$response = $this->get(
			'forms',
			array(
				'api_key' => $this->api_key,
			)
		);

		// If an error occured, log and return it now.
		if ( is_wp_error( $response ) ) {
			return $response;
		}

		// Iterate through forms, determining if each form is a form or landing page.
		$forms         = array();
		$landing_pages = array();
		foreach ( $response['forms'] as $form ) {
			// Skip archived forms.
			if ( isset( $form['archived'] ) && $form['archived'] ) {
				continue;
			}

			switch ( $form['type'] ) {
				case 'hosted':
					$landing_pages[ $form['id'] ] = $form;
					break;

				default:
					$forms[ $form['id'] ] = $form;
					break;
			}
		}

		return array(
			'forms'         => $forms,
			'landing_pages' => $landing_pages,
		);

	}

	/**
	 * Performs a GET request.
	 *
	 * @since   1.0.0
	 *
	 * @param   string $endpoint       API Endpoint.
	 * @param   array  $params         Params.
	 * @return  WP_Error|array|null
	 */
	private function get( $endpoint, $params = array() ) {

		return $this->request( $endpoint, 'get', $params, true );

	}

	/**
	 * Performs a POST request.
	 *
	 * @since  1.0.0
	 *
	 * @param   string $endpoint       API Endpoint.
	 * @param   array  $params         Params.
	 * @return  WP_Error|array|null
	 */
	private function post( $endpoint, $params = array() ) {

		return $this->request( $endpoint, 'post', $params, true );

	}

	/**
	 * Performs a PUT request.
	 *
	 * @since  1.0.0
	 *
	 * @param   string $endpoint       API Endpoint.
	 * @param   array  $params         Params.
	 * @return  WP_Error|array|null
	 */
	private function put( $endpoint, $params = array() ) {

		return $this->request( $endpoint, 'put', $params, true );

	}

	/**
	 * Performs a DELETE request.
	 *
	 * @since  1.3.9
	 *
	 * @param   string $endpoint       API Endpoint.
	 * @param   array  $params         Params.
	 * @return  WP_Error|null
	 */
	private function delete( $endpoint, $params = array() ) {

		return $this->request( $endpoint, 'delete', $params, true );

	}

	/**
	 * Main function which handles sending requests to the API using WordPress functions.
	 *
	 * @since   1.0.0
	 *
	 * @param   string $endpoint                API Endpoint (required).
	 * @param   string $method                  HTTP Method (optional).
	 * @param   mixed  $params                  Params (array|boolean|string).
	 * @param   bool   $retry_if_rate_limit_hit Retry request if rate limit hit.
	 * @return  WP_Error|array|null
	 */
	private function request( $endpoint, $method = 'get', $params = array(), $retry_if_rate_limit_hit = true ) {

		// Send request.
		switch ( $method ) {
			case 'get':
				$result = wp_remote_get(
					$this->add_params_to_url(
						$this->get_api_url( $endpoint ),
						$params
					),
					array(
						'headers'    => $this->get_request_headers(),
						'timeout'    => $this->get_timeout(),
						'user-agent' => $this->get_user_agent(),
					)
				);
				break;

			case 'post':
				$result = wp_remote_post(
					$this->get_api_url( $endpoint ),
					array(
						'headers'    => $this->get_request_headers(),
						'body'       => wp_json_encode( $params ),
						'timeout'    => $this->get_timeout(),
						'user-agent' => $this->get_user_agent(),
					)
				);
				break;

			case 'put':
				$result = wp_remote_request(
					$this->get_api_url( $endpoint ),
					array(
						'method'     => 'PUT',
						'headers'    => $this->get_request_headers(),
						'body'       => wp_json_encode( $params ),
						'timeout'    => $this->get_timeout(),
						'user-agent' => $this->get_user_agent(),
					)
				);
				break;

			case 'delete':
				$result = wp_remote_request(
					$this->get_api_url( $endpoint ),
					array(
						'method'     => 'DELETE',
						'headers'    => $this->get_request_headers(),
						'body'       => wp_json_encode( $params ),
						'timeout'    => $this->get_timeout(),
						'user-agent' => $this->get_user_agent(),
					)
				);
				break;

			default:
				$result = new WP_Error(
					'convertkit_api_error',
					sprintf(
						$this->get_error_message( 'request_method_unsupported' ),
						$method
					)
				);
				break;
		}

		// If an error occured, log and return it now.
		if ( is_wp_error( $result ) ) {
			$this->log( 'API: Error: ' . $result->get_error_message() );
			return $result;
		}

		// Fetch HTTP response code and body.
		$http_response_code = wp_remote_retrieve_response_code( $result );
		$body               = wp_remote_retrieve_body( $result );

		// If the body is null i.e. a 204 No Content, don't attempt to JSON decode it.
		$response = ( ! empty( $body ) ? json_decode( $body, true ) : null );

		// Return a WP_Error if the HTTP response code is a 5xx code.
		// The API respose won't contain an error message, so we use this class' error messages.
		if ( $http_response_code >= 500 ) {
			switch ( $http_response_code ) {
				// Internal server error.
				case 500:
				default:
					$error = $this->get_error_message( 'request_internal_server_error' );
					break;

				// Not implemented.
				case 501:
					$error = $this->get_error_message( 'request_method_unsupported' );
					break;

				// Bad gateway.
				case 502:
					$error = $this->get_error_message( 'request_bad_gateway' );
					break;

				// Service unavailable.
				case 503:
					$error = $this->get_error_message( 'request_service_unavailable' );
					break;

				// Gateway timeout.
				case 504:
					$error = $this->get_error_message( 'request_gateway_timeout' );
					break;

				// HTTP version not supported.
				case 505:
					$error = $this->get_error_message( 'request_http_not_supported' );
					break;
			}

			return new WP_Error(
				'convertkit_api_error',
				$error,
				$http_response_code
			);
		}

		// Return the API error message as a WP_Error if the HTTP response code is a 4xx code.
		if ( $http_response_code >= 400 ) {
			// Define the error description.
			$error = '';
			if ( array_key_exists( 'errors', $response ) ) {
				$error = implode( "\n", $response['errors'] );
			} elseif ( array_key_exists( 'error_description', $response ) ) {
				$error = $response['error_description'];
			}

			$this->log( 'API: Error: ' . $error );

			switch ( $http_response_code ) {
				// If the HTTP response code is 401, and the error matches 'The access token expired', refresh the access token now
				// and re-attempt the request.
				case 401:
					if ( $error !== 'The access token expired' ) {
						break;
					}

					// Refresh the access token.
					$result = $this->refresh_token();

					// If an error occured, bail.
					if ( is_wp_error( $result ) ) {
						return $result;
					}

					// Attempt the request again, now we have a new access token.
					return $this->request( $endpoint, $method, $params, false );

				// If a rate limit was hit, maybe try again.
				case 429:
					// If retry on rate limit hit is disabled, return a WP_Error.
					if ( ! $retry_if_rate_limit_hit ) {
						return new WP_Error(
							'convertkit_api_error',
							$this->get_error_message( 'request_rate_limit_exceeded' ),
							$http_response_code
						);
					}

					// Retry the request a final time, waiting 2 seconds before.
					sleep( 2 );
					return $this->request( $endpoint, $method, $params, false );
			}

			return new WP_Error(
				'convertkit_api_error',
				$error,
				$http_response_code
			);
		}

		return $response;

	}

	/**
	 * Returns the headers to use in an API request.
	 *
	 * @param string  $type Accept and Content-Type Headers.
	 * @param boolean $auth Include authorization header.
	 *
	 * @since 2.0.0
	 *
	 * @return array
	 */
	private function get_request_headers( $type = 'application/json', $auth = true ) {

		$headers = array(
			'Accept'       => $type,
			'Content-Type' => $type . '; charset=utf-8',
		);

		// If no authorization header required, return now.
		if ( ! $auth ) {
			return $headers;
		}

		// Add authorization header and return.
		$headers['Authorization'] = 'Bearer ' . $this->access_token;
		return $headers;

	}

	/**
	 * Returns the maximum amount of time to wait for
	 * a response to the request before exiting.
	 *
	 * @since   1.0.0
	 *
	 * @return  int     Timeout, in seconds.
	 */
	private function get_timeout() {

		$timeout = 10;

		/**
		 * Defines the maximum time to allow the API request to run.
		 *
		 * @since   1.0.0
		 *
		 * @param   int     $timeout    Timeout, in seconds.
		 */
		$timeout = apply_filters( 'convertkit_api_get_timeout', $timeout );

		return $timeout;

	}

	/**
	 * Gets a customized version of the WordPress default user agent; includes WP Version, PHP version, and ConvertKit plugin version.
	 *
	 * @since   1.0.0
	 *
	 * @return string User Agent
	 */
	private function get_user_agent() {

		global $wp_version;

		// Include an unmodified $wp_version.
		require ABSPATH . WPINC . '/version.php';

		// If a context is specified, include it now.
		if ( $this->context !== false ) {
			return sprintf(
				'WordPress/%1$s;PHP/%2$s;%3$s/%4$s;%5$s;context/%6$s',
				$wp_version,
				phpversion(),
				$this->plugin_name,
				$this->plugin_version,
				home_url( '/' ),
				$this->context
			);
		}

		return sprintf(
			'WordPress/%1$s;PHP/%2$s;%3$s/%4$s;%5$s',
			$wp_version,
			phpversion(),
			$this->plugin_name,
			$this->plugin_version,
			home_url( '/' )
		);

	}

	/**
	 * Returns the full API URL for the given endpoint.
	 *
	 * @since   1.0.0
	 *
	 * @param   string $endpoint   Endpoint.
	 * @return  string              API URL
	 */
	private function get_api_url( $endpoint ) {

		// For some specific API endpoints created primarily for the WordPress Plugin, the API base is
		// https://api.convertkit.com/wordpress/$endpoint.
		// We perform a string search instead of in_array(), because the $endpoint might be e.g.
		// profile/{subscriber_id} or subscriber_authentication/send_code.
		foreach ( $this->api_endpoints_wordpress as $wordpress_endpoint ) {
			if ( strpos( $endpoint, $wordpress_endpoint ) !== false ) {
				return path_join( $this->api_url_base . 'wordpress', $endpoint ); // phpcs:ignore WordPress.WP.CapitalPDangit
			}
		}

		// For oAuth API endpoints, the API base is https://api.convertkit.com/oauth/$endpoint.
		foreach ( $this->api_endpoints_oauth as $oauth_endpoint ) {
			if ( strpos( $endpoint, $oauth_endpoint ) !== false ) {
				return path_join( $this->api_url_base . 'oauth', $endpoint );
			}
		}

		// For all other endpoints, it's https://api.convertkit.com/v3/$endpoint.
		return path_join( $this->api_url_base . $this->api_version, $endpoint );

	}

	/**
	 * Adds the supplied array of parameters as query arguments to the URL.
	 *
	 * @since   1.0.0
	 *
	 * @param   string $url        URL.
	 * @param   array  $params     Parameters for request.
	 * @return  string              URL with API Key or API Secret
	 */
	private function add_params_to_url( $url, $params ) {

		return add_query_arg( $params, $url );

	}

	/**
	 * Returns the localized/translated error message for the given error key.
	 *
	 * @since   1.0.0
	 *
	 * @param   string $key    Key.
	 * @return  string          Error message
	 */
	private function get_error_message( $key ) {

		// Return a blank string if no error messages have been defined.
		if ( ! is_array( $this->error_messages ) ) {
			return '';
		}

		// Return a blank string if the error message isn't defined.
		if ( ! array_key_exists( $key, $this->error_messages ) ) {
			return '';
		}

		// Return error message.
		return $this->error_messages[ $key ];

	}

	/**
	 * Helper method to mask all but the last 4 characters of a string.
	 *
	 * @since   1.4.2
	 *
	 * @param   string $str    String to mask.
	 * @return  string          Masked string
	 */
	private function mask_string( $str ) {

		// Don't mask if less than 4 characters.
		if ( strlen( $str ) < 4 ) {
			return $str;
		}

		return str_repeat( '*', ( strlen( $str ) - 4 ) ) . substr( $str, -4 );

	}

}
