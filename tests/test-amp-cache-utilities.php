<?php

class AMP_Cache_Utilities_Test extends WP_UnitTestCase {

	public function get_amp_cache_path_for_url_data() {
		return array(
			'http_content_success' => array(
				array(
					'url' => 'http://example.com/path/to/resource.ext',
					'content_type' => 'c',
					'scheme' => null,
				),
				'/c/example.com/path/to/resource.ext',
			),
			'http_content_with_port_success' => array(
				array(
					'url' => 'http://example.com:8080/path/to/resource.ext',
					'content_type' => 'c',
					'scheme' => null,
				),
				'/c/example.com:8080/path/to/resource.ext',
			),
			'http_content_with_query_success' => array(
				array(
					'url' => 'http://example.com/path/to/resource.ext?query=value&query2=value2',
					'content_type' => 'c',
					'scheme' => null,
				),
				'/c/example.com/path/to/resource.ext?query=value&query2=value2',
			),
			'http_content_with_fragment_success' => array(
				array(
					'url' => 'http://example.com/path/to/resource.ext#frag',
					'content_type' => 'c',
					'scheme' => null,
				),
				'/c/example.com/path/to/resource.ext#frag',
			),
			'http_content_with_everything_success' => array(
				array(
					'url' => 'http://example.com:8888/path/to/resource.ext?query1=val1&query2=val2#frag',
					'content_type' => 'c',
					'scheme' => null,
				),
				'/c/example.com:8888/path/to/resource.ext?query1=val1&query2=val2#frag',
			),
			'https_content_success' => array(
				array(
					'url' => 'https://example.com/path/to/resource.ext',
					'content_type' => 'c',
					'scheme' => null,
				),
				'/c/s/example.com/path/to/resource.ext',
			),
			'http_content_specify_scheme_success' => array(
				array(
					'url' => 'http://example.com/path/to/resource.ext',
					'content_type' => 'c',
					'scheme' => 'http',
				),
				'/c/example.com/path/to/resource.ext',
			),
			'http_content_specify_different_scheme_https_success' => array(
				array(
					'url' => 'http://example.com/path/to/resource.ext',
					'content_type' => 'c',
					'scheme' => 'https',
				),
				'/c/s/example.com/path/to/resource.ext',
			),
			'https_content_specify_scheme_success' => array(
				array(
					'url' => 'https://example.com/path/to/resource.ext',
					'content_type' => 'c',
					'scheme' => 'https',
				),
				'/c/s/example.com/path/to/resource.ext',
			),
			'https_content_specify_different_scheme_success' => array(
				array(
					'url' => 'https://example.com/path/to/resource.ext',
					'content_type' => 'c',
					'scheme' => 'http',
				),
				'/c/example.com/path/to/resource.ext',
			),
			'no_scheme_content_fail' => array(
				array(
					'url' => '//example.com/path/to/resource.ext',
					'content_type' => 'c',
					'scheme' => null,
				),
				false,
			),
			'no_host_scheme_http_fail' => array(
				array(
					'url' => '/path/to/resource.ext',
					'content_type' => 'c',
					'scheme' => 'http',
				),
				false,
			),
			'no_host_scheme_https_fail' => array(
				array(
					'url' => '/path/to/resource.ext',
					'content_type' => 'c',
					'scheme' => 'https',
				),
				false,
			),
			'content_scheme_http_success' => array(
				array(
					'url' => '//example.com/path/to/resource.ext',
					'content_type' => 'c',
					'scheme' => 'http',
				),
				'/c/example.com/path/to/resource.ext',
			),
			'content_scheme_https_success' => array(
				array(
					'url' => '//example.com/path/to/resource.ext',
					'content_type' => 'c',
					'scheme' => 'https',
				),
				'/c/s/example.com/path/to/resource.ext',
			),
			'content_scheme_bad_fail' => array(
				array(
					'url' => '//example.com/path/to/resource.ext',
					'content_type' => 'c',
					'scheme' => 'bad',
				),
				false,
			),
			'content_bad_fail' => array(
				array(
					'url' => '//example.com/path/to/resource.ext',
					'content_type' => 'zzz',
					'scheme' => 'https',
				),
				false,
			),
			'no_scheme_no_host_fail' => array(
				array(
					'url' => '/path/to/resource.ext',
					'content_type' => 'c',
					'scheme' => null,
				),
				false,
			),
			'bad_url_fail' => array(
				array(
					'url' => '!@#$%^&*()!@#$%^&*()',
					'content_type' => 'c',
					'scheme' => 'http',
				),
				false,
			),
		);
	}

	/**
	 * @dataProvider get_amp_cache_path_for_url_data
	 * @group amp-cache-path-test	
	 */
	public function test_get_amp_cache_path_for_url( $data, $cexpected_ache_path ) {
		$cache_path = AMP_Cache_Utilities::get_amp_cache_path_for_url( $data['url'], $data['content_type'] , $data['scheme'] );
		$this->assertEquals( $cache_path, $cexpected_ache_path );
	}
}
?>