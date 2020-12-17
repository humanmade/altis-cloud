<?php

namespace Altis\Cloud\FluentBit;

use Altis\Cloud\FluentBit\Formatter;
use Monolog\Handler\SocketHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;

function get_logger( string $tag_name ) {
	// TODO how do I need to name the file/class to be autoloaded?
	require_once __DIR__ . '/class-formatter.php';

	static $loggers = [];

	if ( $loggers[ $tag_name ] ) {
		return $loggers[ $tag_name ];
	}

	$logger = new Logger( $tag_name );

	// Else we log locally
	$stream = new StreamHandler( '/tmp/logpipe', Logger::DEBUG );
	$logger->pushHandler( $stream );

	// Use Fluent Bit if it's available
	if ( defined( 'FLUENT_HOST' ) && defined( 'FLUENT_PORT' ) ) {
		$socket = new SocketHandler( FLUENT_HOST . ':' . FLUENT_PORT, Logger::DEBUG );
		$socket->setFormatter( new Formatter() );
		$logger->pushHandler( $socket );
	} else {
	}


	$loggers[ $tag_name ] = $logger;
	return $loggers[ $tag_name ];
}
