<?php

namespace Altis\Cloud\Tests;

use Altis\Cloud\CloudWatch_Logs;
use Aws\CommandInterface;
use Aws\Exception\AwsException;
use Aws\MockHandler;
use Aws\Result;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;

class Test_CloudWatch_Logs extends TestCase {
	/**
	 * SDK mock handler.
	 *
	 * @see https://docs.aws.amazon.com/sdk-for-php/v3/developer-guide/guide_handlers-and-middleware.html
	 *
	 * @var MockHandler
	 */
	protected $sdk_mock;

	public function setUp() {
		$this->sdk_mock = new MockHandler();
		tests_add_filter( 'altis.aws_sdk.params', function ( array $params ) : array {
			$params['handler'] = $this->sdk_mock;
			return $params;
		} );
	}
	public function test_send_events_to_stream_invalid_cached_sequence_token() {
		// Set an initial wrong token in the cache. This will cause the first request to PutLogEvents to fail.
		wp_cache_set( 'test-stack/phpvar', 'wrong-token', CloudWatch_Logs\OBJECT_CACHE_GROUP );
		$this->sdk_mock->append( function ( CommandInterface $cmd, RequestInterface $req ) {
			return new AwsException( 'Mock exception', $cmd, [
				'code' => 'InvalidSequenceTokenException',
				'body' => [
					'expectedSequenceToken' => '1',
				],
			] );
		} );
		// The next expected request to the AWS SDK is a retry PutLogEvents, this time we respond with the
		// next valid sequence token.
		$this->sdk_mock->append( new Result(
			[ 'nextSequenceToken' => '2' ]
		) );
		CloudWatch_Logs\send_events_to_stream( [
			[
				'timestamp' => time() * 1000,
				'message' => 'hello',
			],
		], 'test-stack/php', 'var' );

		$new_token = wp_cache_get( 'test-stack/phpvar', CloudWatch_Logs\OBJECT_CACHE_GROUP );

		$this->assertEquals( '2', $new_token );
	}
}
