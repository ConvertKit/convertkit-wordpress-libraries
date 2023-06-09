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

		// Mock the data that the resource class would receive from the WordPress options table
		// (and therefore the ConvertKit APIs) to perform tests on.
		$this->resource->resources = [
			2780977 => [
				'id'           => 2780977,
				'name'         => 'Z Name', // 'name' used by forms, landing pages, products, tags.
				'title'        => 'Z Name', // 'title' used by posts.
				'published_at' => '2022-01-24T00:00:00.000Z', // used by posts.
			],
			2765139 => [
				'id'           => 2765139,
				'name'         => 'A Name', // 'name' used by forms, landing pages, products, tags.
				'title'        => 'A Name', // 'title' used by posts.
				'published_at' => '2022-05-03T14:51:50.000Z', // used by posts.
			],
		];

	}

	/**
	 * Performs actions after each test.
	 *
	 * @since   1.3.1
	 */
	public function tearDown(): void
	{
		// Destroy the classes we tested.
		unset($this->resource);

		parent::tearDown();
	}

	/**
	 * Tests that the get() function returns resources in alphabetical ascending order
	 * by default.
	 *
	 * @since   1.3.1
	 */
	public function testGet()
	{
		// Call resource class' get() function.
		$result = $this->resource->get();

		// Assert result is an array.
		$this->assertIsArray($result);

		// Assert array keys are preserved.
		$this->assertArrayHasKey(array_key_first($this->resource->resources), $result);
		$this->assertArrayHasKey(array_key_last($this->resource->resources), $result);

		// Assert order of data is in ascending alphabetical order.
		$this->assertEquals('A Name', reset($result)[ $this->resource->order_by ]);
		$this->assertEquals('Z Name', end($result)[ $this->resource->order_by ]);
	}

	/**
	 * Tests that the get() function returns resources in alphabetical ascending order
	 * when a valid order_by setting is defined.
	 *
	 * @since   1.3.1
	 */
	public function testGetWithValidOrderBy()
	{
		// Define order_by = title.
		$this->resource->order_by = 'title';

		// Call resource class' get() function.
		$result = $this->resource->get();

		// Assert result is an array, and no error occured.
		$this->assertIsArray($result);

		// Assert array keys are preserved.
		$this->assertArrayHasKey(array_key_first($this->resource->resources), $result);
		$this->assertArrayHasKey(array_key_last($this->resource->resources), $result);

		// Assert order of data is in ascending alphabetical order.
		$this->assertEquals('A Name', reset($result)[ $this->resource->order_by ]);
		$this->assertEquals('Z Name', end($result)[ $this->resource->order_by ]);
	}

	/**
	 * Tests that the get() function returns resources in their original order
	 * when populated with Forms and an invalid order_by value is specified.
	 *
	 * @since   1.3.1
	 */
	public function testGetWithInvalidOrderBy()
	{
		// Define order_by with an invalid value (i.e. an array key that does not exist).
		$this->resource->order_by = 'invalid_key';

		// Call resource class' get() function.
		$result = $this->resource->get();

		// Assert result is an array, and no error occured.
		$this->assertIsArray($result);

		// Assert array keys are preserved.
		$this->assertArrayHasKey(array_key_first($this->resource->resources), $result);
		$this->assertArrayHasKey(array_key_last($this->resource->resources), $result);

		// Assert order of data has not changed.
		$this->assertNotEquals('A Name', reset($result)['name']);
		$this->assertNotEquals('Z Name', end($result)['name']);
	}

	/**
	 * Tests that the get() function returns resources in alphabetical descending order
	 * when a valid order_by setting is defined.
	 *
	 * @since   1.3.1
	 */
	public function testGetWithValidOrder()
	{
		// Define order to be descending.
		$this->resource->order_by = 'name';
		$this->resource->order    = 'desc';

		// Call resource class' get() function.
		$result = $this->resource->get();

		// Assert result is an array, and no error occured.
		$this->assertIsArray($result);

		// Assert array keys are preserved.
		$this->assertArrayHasKey(array_key_first($this->resource->resources), $result);
		$this->assertArrayHasKey(array_key_last($this->resource->resources), $result);

		// Assert order of data is in descending alphabetical order.
		$this->assertEquals('Z Name', reset($result)[ $this->resource->order_by ]);
		$this->assertEquals('A Name', end($result)[ $this->resource->order_by ]);
	}

	/**
	 * Tests that the get() function returns resources in date descending order
	 * when a valid order and order_by settings are defined.
	 *
	 * @since   1.3.1
	 */
	public function testGetWithValidOrderByAndOrder()
	{
		// Define order to be descending.
		$this->resource->order_by = 'published_at';
		$this->resource->order    = 'desc';

		// Call resource class' get() function.
		$result = $this->resource->get();

		// Assert result is an array, and no error occured.
		$this->assertIsArray($result);

		// Assert array keys are preserved.
		$this->assertArrayHasKey(array_key_first($this->resource->resources), $result);
		$this->assertArrayHasKey(array_key_last($this->resource->resources), $result);

		// Assert order of data is in descending alphabetical order.
		$this->assertEquals('2022-05-03T14:51:50.000Z', reset($result)['published_at']);
		$this->assertEquals('2022-01-24T00:00:00.000Z', end($result)['published_at']);
	}

	/**
	 * Tests that the get_by() function returns the matching resource when queried
	 * by name.
	 *
	 * @since   1.3.6
	 */
	public function testGetBy()
	{
		// Call resource class' get_by() function.
		$result = $this->resource->get_by('name', 'Z Name');

		// Assert result is an array.
		$this->assertIsArray($result);

		// Assert one item was returned.
		$this->assertCount(1, $result);

		// Assert array keys are preserved.
		$this->assertArrayHasKey(array_key_first($this->resource->resources), $result);

		// Assert resource is the one we requested.
		$this->assertEquals('Z Name', reset($result)[ $this->resource->order_by ]);
	}

	/**
	 * Tests that the get_by() function returns the matching resources when queried
	 * by multiple values.
	 *
	 * @since   1.3.6
	 */
	public function testGetByMultipleValues()
	{
		// Call resource class' get_by() function.
		$result = $this->resource->get_by('name', [ 'A Name', 'Z Name' ]);

		// Assert result is an array.
		$this->assertIsArray($result);

		// Assert two items were returned.
		$this->assertCount(2, $result);

		// Assert array keys are preserved.
		$this->assertArrayHasKey(array_key_first($this->resource->resources), $result);
		$this->assertArrayHasKey(array_key_last($this->resource->resources), $result);

		// Assert order of data is in ascending alphabetical order.
		$this->assertEquals('A Name', reset($result)[ $this->resource->order_by ]);
		$this->assertEquals('Z Name', end($result)[ $this->resource->order_by ]);
	}

}
