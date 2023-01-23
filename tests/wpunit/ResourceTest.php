<?php
/**
 * Tests for the ConvertKit_Resource class.
 *
 * @since   1.3.1
 */
class ResourceTest extends \Codeception\TestCase\WPTestCase
{
	/**
	 * The testing implementation.
	 *
	 * @var \WpunitTester.
	 */
	protected $tester;

	/**
	 * Holds the ConvertKit Resource class.
	 *
	 * @since   1.3.1
	 *
	 * @var     ConvertKit_Resource
	 */
	private $resource;

	/**
	 * Performs actions before each test.
	 *
	 * @since   1.3.1
	 */
	public function setUp(): void
	{
		parent::setUp();

		// Include class from /src to test.
		require_once 'src/class-convertkit-resource.php';

		// Initialize the classes we want to test.
		$this->resource = new ConvertKit_Resource();
	}

	/**
	 * Performs actions after each test.
	 *
	 * @since   1.3.1
	 */
	public function tearDown(): void
	{
		// Destroy the classes we tested.
		unset($this->api);
		unset($this->api_no_data);

		parent::tearDown();
	}

	/**
	 * Tests that the get() function returns resources in alphabetical order when
	 * populated with Forms.
	 * 
	 * @since 	1.3.1
	 */
	public function testGetForms()
	{
		// Mock the data that the resource class would fetch from the WordPress options table
		// (and therefore the ConvertKit API), deliberately in a non-alphabetical order.
		$this->resource->resources = [
			2765139 => [
				'id'         => 2765139,
				'name'       => 'Page Form',
			],
			2780977 => [
				'id'         => 2780977,
				'name'       => 'Modal Form',
			]
		];

		// Call resource class' get() function.
		$result = $this->resource->get();

		// Assert result is an array.
		$this->assertIsArray($result);

		// Assert array keys are preserved.
		$this->assertArrayHasKey(array_key_first($this->resource->resources), $result);
		$this->assertArrayHasKey(array_key_last($this->resource->resources), $result);

		// Assert order of data is alphabetical.
		$this->assertEquals(reset($this->resource->resources)['name'], end($result)['name']);
		$this->assertEquals(end($this->resource->resources)['name'], reset($result)['name']);
	}

	/**
	 * Tests that the get() function returns resources in their original order
	 * when populated with Forms and an invalid order_by value is specified.
	 * 
	 * @since 	1.3.1
	 */
	public function testGetFormsWithInvalidOrderBy()
	{
		// Mock the data that the resource class would fetch from the WordPress options table
		// (and therefore the ConvertKit API), deliberately in a non-alphabetical order.
		$this->resource->resources = [
			2765139 => [
				'id'         => 2765139,
				'name'       => 'Page Form',
			],
			2780977 => [
				'id'         => 2780977,
				'name'       => 'Modal Form',
			],
		];

		// Define the sort order with an invalid value.
		$this->resource->order_by = 'invalid_key';

		// Call resource class' get() function.
		$result = $this->resource->get();

		// Assert result is an array, and no error occured.
		$this->assertIsArray($result);

		// Assert array keys are preserved.
		$this->assertArrayHasKey(array_key_first($this->resource->resources), $result);
		$this->assertArrayHasKey(array_key_last($this->resource->resources), $result);
	}

}
