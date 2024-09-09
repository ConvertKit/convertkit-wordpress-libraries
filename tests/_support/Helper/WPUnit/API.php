<?php
namespace Helper\WPUnit;

/**
 * Helper methods and actions related to the ConvertKit API,
 * which are then available using $I->{yourFunctionName}.
 */
class API extends \Codeception\Module
{
	/**
	 * Sends a request to the ConvertKit API, typically used to read an endpoint to confirm
	 * that data in an Acceptance Test was added/edited/deleted successfully.
	 *
	 * @param   string $endpoint   Endpoint.
	 * @param   string $method     Method (GET|POST|PUT).
	 * @param   array  $params     Endpoint Parameters.
	 * @return  array
	 */
	public function apiRequest($endpoint, $method = 'GET', $params = array())
	{
		// Build query parameters.
		$params = array_merge(
			$params,
			[
				'api_key'    => $_ENV['CONVERTKIT_API_KEY'],
				'api_secret' => $_ENV['CONVERTKIT_API_SECRET'],
			]
		);

		// Send request.
		try {
			$client = new \GuzzleHttp\Client();
			$result = $client->request(
				$method,
				'https://api.convertkit.com/v3/' . $endpoint . '?' . http_build_query($params),
				[
					'headers' => [
						'Accept-Encoding' => 'gzip',
						'timeout'         => 5,
					],
				]
			);

			// Return JSON decoded response.
			return json_decode($result->getBody()->getContents(), true);
		} catch (\GuzzleHttp\Exception\ClientException $e) {
			return [];
		}
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

	/**
	 * Mocks an API response as if the Access Token expired.
	 *
	 * @since   2.0.2
	 *
	 * @param   mixed  $response       HTTP Response.
	 * @param   array  $parsed_args    Request arguments.
	 * @param   string $url            Request URL.
	 * @return  mixed
	 */
	public function mockAccessTokenExpiredResponse( $response, $parsed_args, $url )
	{
		// Only mock requests made to the /account endpoint.
		if ( strpos( $url, 'https://api.convertkit.com/v4/account' ) === false ) {
			return $response;
		}

		// Remove this filter, so we don't end up in a loop when retrying the request.
		remove_filter( 'pre_http_request', array( $this, 'mockAccessTokenExpiredResponse' ) );

		// Return a 401 unauthorized response with the errors body as if the API
		// returned "The access token expired".
		return array(
			'headers'       => array(),
			'body'          => wp_json_encode(
				array(
					'errors' => array(
						'The access token expired',
					),
				)
			),
			'response'      => array(
				'code'    => 401,
				'message' => 'The access token expired',
			),
			'cookies'       => array(),
			'http_response' => null,
		);
	}

	/**
	 * Mocks an API response as if a refresh token was used to fetch new tokens.
	 *
	 * @since   2.0.2
	 *
	 * @param   mixed  $response       HTTP Response.
	 * @param   array  $parsed_args    Request arguments.
	 * @param   string $url            Request URL.
	 * @return  mixed
	 */
	public function mockRefreshTokenResponse( $response, $parsed_args, $url )
	{
		// Only mock requests made to the /token endpoint.
		if ( strpos( $url, 'https://api.convertkit.com/oauth/token' ) === false ) {
			return $response;
		}

		// Remove this filter, so we don't end up in a loop when retrying the request.
		remove_filter( 'pre_http_request', array( $this, 'mockRefreshTokenResponse' ) );

		// Return a mock access and refresh token for this API request, as calling
		// refresh_token results in a new access and refresh token being provided,
		// which would result in other tests breaking due to changed tokens.
		return array(
			'headers'       => array(),
			'body'          => wp_json_encode(
				array(
					'access_token'  => 'new-' . $_ENV['CONVERTKIT_OAUTH_ACCESS_TOKEN'],
					'refresh_token' => 'new-' . $_ENV['CONVERTKIT_OAUTH_REFRESH_TOKEN'],
					'token_type'    => 'bearer',
					'created_at'    => strtotime( 'now' ),
					'expires_in'    => 10000,
					'scope'         => 'public',
				)
			),
			'response'      => array(
				'code'    => 200,
				'message' => 'OK',
			),
			'cookies'       => array(),
			'http_response' => null,
		);
	}
}
