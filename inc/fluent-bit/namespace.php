<?php

namespace Altis\Cloud\FluentBit;

// use Monolog\Formatter\MsgPackFormatter;
use Monolog\Handler\SocketHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;

function get_logger( string $tag_name ) {
	static $loggers = [];

	if ( $loggers[ $tag_name ] ) {
		return $loggers[ $tag_name ];
	}

	$logger = new Logger( $tag_name );

	// Use Fluent Bit if it's available
	if ( defined( 'FLUENT_HOST' ) && defined( 'FLUENT_PORT' ) ) {
		$socket = new SocketHandler( FLUENT_HOST . ':' . FLUENT_PORT, Logger::DEBUG );
		$logger->pushHandler( $socket );
	} else {
		// Else we log locally
		$stream = new StreamHandler( '/tmp/logpipe', Logger::DEBUG );
		$logger->pushHandler( $stream );
	}

	$loggers[ $tag_name ] = $logger;
	return $loggers[ $tag_name ];
}
