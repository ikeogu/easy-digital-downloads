<?php
namespace EDD\Reports\Data;

use EDD\Reports;

if ( ! class_exists( '\EDD\Reports\Init' ) ) {
	require_once( EDD_PLUGIN_DIR . 'includes/reports/class-init.php' );
}

/**
 * Tests for the Report object.
 *
 * @group edd_reports
 * @group edd_reports_endpoints
 * @group edd_objects
 */
class Report_Tests extends \EDD_UnitTestCase {

	/**
	 * Reports fixture.
	 *
	 * @var \EDD\Reports\Init
	 * @static
	 */
	protected static $reports;

	/**
	 * Report fixture with foo endpoint.
	 *
	 * @var Report
	 */
	protected static $report;

	/**
	 * Copy of the original report object for reset purposes.
	 *
	 * @var Report
	 */
	protected static $_original_report;

	/**
	 * Set up static fixtures.
	 */
	public static function wpSetUpBeforeClass() {
		self::$reports = new \EDD\Reports\Init();

		Reports\register_endpoint( 'foo', array(
			'label' => 'Foo',
			'views' => array(
				'tile' => array(
					'data_callback'    => '__return_false',
					'display_callback' => '__return_false',
				),
			),
		) );

		Reports\add_report( 'foo', array(
			'label'      => 'Foo',
			'capability' => 'exist',
			'endpoints'  => array(
				'tiles' => array( 'foo' ),
			)
		) );

		self::$_original_report = self::$report = Reports\get_report( 'foo' );
	}

	/**
	 * Runs after every test.
	 */
	public function tearDown() {
		// Reset $report after every test.
		self::$report = self::$_original_report;

		parent::tearDown();
	}

	/**
	 * @covers \EDD\Reports\Data\Report
	 * @group edd_errors
	 */
	public function test_Report_with_empty_endpoints_should_flag_WP_Error() {
		$report = new Report( array(
			'id'         => 'foo',
			'label'      => 'Foo',
			'capability' => 'view_shop_reports',
			'endpoints'  => array(),
		) );

		$this->assertTrue( $report->has_errors() );
	}

	/**
	 * @covers \EDD\Reports\Data\Report::$endpoints
	 * @group edd_errors
	 */
	public function test_Report_with_empty_endpoints_should_flag_WP_Error_including_code_missing_endpoints() {
		$report = new Report( array(
			'id'         => 'foo',
			'label'      => 'Foo',
			'capability' => 'view_shop_reports',
			'endpoints'  => array(),
		) );

		$this->assertContains( 'missing_endpoints', $report->get_errors()->get_error_codes() );
	}

	/**
	 * @covers \EDD\Reports\Data\Report::$capability
	 * @group edd_errors
	 */
	public function test_Report_with_empty_capability_should_flag_WP_Error_including_code_missing_capability() {
		$report = new Report( array(
			'id'         => 'foo',
			'label'      => 'Foo',
			'endpoints'  => array(),
			'capability' => '',
		) );

		$this->assertContains( 'missing_capability', $report->get_errors()->get_error_codes() );
	}

	/**
	 * @covers \EDD\Reports\Data\Report::parse_endpoints()
	 * @throws \EDD_Exception
	 */
	public function test_parse_endpoints_with_empty_array_should_add_no_new_endpoints() {
		$report = new Report( array() );

		$report->parse_endpoints( array() );

		$this->assertEqualSets( array(), $report->get_endpoints() );
	}

	/**
	 * @covers \EDD\Reports\Data\Report::parse_endpoints()
	 * @throws \EDD_Exception
	 */
	public function test_parse_endpoints_with_invalid_view_group_should_throw_exception() {
		$this->setExpectedException( '\EDD_Exception', "The 'fake' view group does not correspond to a known endpoint view type." );

		self::$report->parse_endpoints( array( 'fake' => array() ) );
	}

	/**
	 * @covers \EDD\Reports\Data\Report::parse_view_groups()
	 */
	public function test_parse_view_groups_should_return_group_view_key_value_pairs() {
		$expected = array(
			'tiles'  => 'tile',
			'charts' => 'chart',
			'tables' => 'table',
			'graphs' => 'graph',
		);

		$this->assertEqualSetsWithIndex( $expected, self::$report->parse_view_groups() );
	}

	/**
	 * @covers \EDD\Reports\Data\Report::validate_endpoint()
	 */
	public function test_validate_endpoint_passed_a_WP_Error_object_should_add_a_new_error_to_errors() {
		self::$report->validate_endpoint( 'tiles', new \WP_Error( 'foo' ) );

		$this->assertContains( 'foo', self::$report->get_errors()->get_error_codes() );
	}

	/**
	 * @covers \EDD\Reports\Data\Report::validate_endpoint()
	 */
	public function test_validate_endpoint_passed_an_endpoint_with_errors_should_add_that_error() {
		$endpoint = new Tile_Endpoint( array(
			'id'    => 'foo',
			'label' => 'Foo',
			'views' => array()
		) );

		self::$report->validate_endpoint( 'tiles', $endpoint );

		$this->assertContains( 'invalid_endpoint', self::$report->get_errors()->get_error_codes() );
	}

	/**
	 * @covers \EDD\Reports\Data\Report::validate_endpoint()
	 */
	public function test_validate_endpoint_passed_a_legitimate_endpoint_should_add_it_to_the_endpoints_array() {
		$endpoint = new Tile_Endpoint( array(
			'id'    => 'bar',
			'label' => 'Bar',
			'views' => array(
				'tile' => array(
					'display_callback' => '__return_false',
					'data_callback'    => '__return_false',
				),
			),
		) );

		self::$report->validate_endpoint( 'tiles', $endpoint );

		$this->assertArrayHasKey( 'bar', self::$report->get_endpoints( 'tiles' ) );
	}

	/**
	 * @covers \EDD\Reports\Data\Report::get_endpoints()
	 */
	public function test_get_endpoints_with_empty_view_group_should_return_all_endpoints() {

		Reports\register_endpoint( 'foo', array(
			'label' => 'Foo',
			'views' => array(
				'tile' => array(
					'data_callback'    => '__return_false',
					'display_callback' => '__return_false',
				),
			),
		) );

		Reports\register_endpoint( 'bar', array(
			'label' => 'Bar',
			'views' => array(
				'tile' => array(
					'data_callback'    => '__return_false',
					'display_callback' => '__return_false',
				),
			),
		) );

		Reports\add_report( 'foo', array(
			'id'         => 'foo',
			'label'      => 'Foo',
			'capability' => 'exist',
			'endpoints'  => array(
				'tiles' => array( 'foo', 'bar' ),
			)
		) );

		$report = Reports\get_report( 'foo' );

		$all_endpoints = $report->get_endpoints();

		$actual = array();

		foreach ( $all_endpoints as $view_group => $endpoints ) {
			foreach ( $endpoints as $endpoint_id => $endpoint ) {
				$actual[] = $endpoint_id;
			}
		}

		$this->assertEqualSets( array( 'foo', 'bar' ), $actual );
	}

	/**
	 * @covers \EDD\Reports\Data\Report::get_endpoints()
	 */
	public function test_get_endpoints_with_invalid_view_group_should_return_all_endpoints() {
		Reports\register_endpoint( 'foo', array(
			'id'    => 'foo',
			'label' => 'Foo',
			'views' => array(
				'tile' => array(
					'data_callback'    => '__return_false',
					'display_callback' => '__return_false',
				),
			),
		) );

		Reports\register_endpoint( 'bar', array(
			'id'    => 'bar',
			'label' => 'Bar',
			'views' => array(
				'tile' => array(
					'data_callback'    => '__return_false',
					'display_callback' => '__return_false',
				),
			),
		) );

		Reports\add_report( 'foo', array(
			'label'      => 'Foo',
			'capability' => 'exist',
			'endpoints'  => array(
				'tiles' => array( 'foo', 'bar' ),
			)
		) );

		$report = Reports\get_report( 'foo' );

		$all_endpoints = $report->get_endpoints( 'fake' );

		$actual = array();

		foreach ( $all_endpoints as $view_group => $endpoints ) {
			foreach ( $endpoints as $endpoint_id => $endpoint ) {
				$actual[] = $endpoint_id;
			}
		}

		$this->assertEqualSets( array( 'foo', 'bar' ), $actual );
	}

	/**
	 * @covers \EDD\Reports\Data\Report::get_endpoints()
	 */
	public function test_get_endpoints_with_valid_view_group_should_return_all_endpoints() {
		Reports\register_endpoint( 'foo', array(
			'id'    => 'foo',
			'label' => 'Foo',
			'views' => array(
				'tile' => array(
					'data_callback'    => '__return_false',
					'display_callback' => '__return_false',
				),
			),
		) );

		Reports\add_report( 'foo', array(
			'label'      => 'Foo',
			'capability' => 'exist',
			'endpoints'  => array(
				'tiles' => array( 'foo' ),
			)
		) );

		$report = Reports\get_report( 'foo' );

		$tiles = $report->get_endpoints( 'tiles' );

		$actual = array();

		foreach ( $tiles as $endpoint_id => $endpoint ) {
			$actual[] = $endpoint_id;
		}

		$this->assertEqualSets( array( 'foo' ), $actual );
	}

	/**
	 * @covers \EDD\Reports\Data\Report::get_endpoint()
	 */
	public function test_get_endpoint_with_valid_endpoint_valid_view_group_should_return_that_endpoint() {
		$endpoint = self::$report->get_endpoint( 'foo', 'tiles' );

		$this->assertSame( 'foo', $endpoint->get_id() );
	}

	/**
	 * @covers \EDD\Reports\Data\Report::get_endpoint()
	 * @group edd_errors
	 */
	public function test_get_endpoint_with_valid_endpoint_invalid_view_group_should_return_WP_Error() {
		$endpoint_or_error = self::$report->get_endpoint( 'foo', 'fake' );

		$this->assertWPError( $endpoint_or_error );
	}

	/**
	 * @covers \EDD\Reports\Data\Report::get_endpoint()
	 * @group edd_errors
	 */
	public function test_get_endpoint_with_valid_endpoint_invalid_view_group_should_return_WP_Error_including_code_invalid_report_endpoint() {
		$endpoint_or_error = self::$report->get_endpoint( 'foo', 'fake' );

		$this->assertContains( 'invalid_report_endpoint', $endpoint_or_error->get_error_codes() );
	}

	/**
	 * @covers \EDD\Reports\Data\Report::get_endpoint()
	 * @group edd_errors
	 */
	public function test_get_endpoint_with_invalid_endpoint_valid_view_group_should_return_WP_Error() {
		$endpoint_or_error = self::$report->get_endpoint( 'fake', 'tiles' );

		$this->assertWPError( $endpoint_or_error );
	}

	/**
	 * @covers \EDD\Reports\Data\Report::get_endpoint()
	 * @group edd_errors
	 */
	public function test_get_endpoint_with_invalid_endpoint_valid_view_group_should_return_WP_Error_including_code_invalid_report_endpoint() {
		$endpoint_or_error = self::$report->get_endpoint( 'fake', 'tiles' );

		$this->assertContains( 'invalid_report_endpoint', $endpoint_or_error->get_error_codes() );
	}

	/**
	 * @covers \EDD\Reports\Data\Report::get_endpoint()
	 * @group edd_errors
	 */
	public function test_get_endpoint_with_invalid_endpoint_invalid_view_group_should_return_WP_Error() {
		$endpoint_or_error = self::$report->get_endpoint( 'fake', 'fake' );

		$this->assertWPError( $endpoint_or_error );
	}

	/**
	 * @covers \EDD\Reports\Data\Report::get_endpoint()
	 * @group edd_errors
	 */
	public function test_get_endpoint_with_invalid_endpoint_invalid_view_group_should_return_WP_Error_including_code_invalid_report_endpoint() {
		$endpoint_or_error = self::$report->get_endpoint( 'fake', 'fake' );

		$this->assertContains( 'invalid_report_endpoint', $endpoint_or_error->get_error_codes() );
	}

}