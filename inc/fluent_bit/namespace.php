<?php

namespace Altis\Cloud\Fluent_Bit;

use Altis\Cloud\Fluent_Bit\MsgPackFormatter;
use Monolog\Handler\SocketHandler;
use Monolog\Logger;

function available() {
	return defined( 'FLUENT_HOST' ) && defined( 'FLUENT_PORT' );
}

function get_logger( string $tag_name ) {
	// TODO how do I need to name the file/class to be autoloaded?
	require_once __DIR__ . '/class-msgpackformatter.php';

	static $loggers = [];

	if ( $loggers[ $tag_name ] ) {
		return $loggers[ $tag_name ];
	}

	$logger = new Logger( $tag_name );

	// Use Fluent Bit if it's available
	if ( available() ) {
		$socket = new SocketHandler( FLUENT_HOST . ':' . FLUENT_PORT, Logger::DEBUG );
		$socket->setFormatter( new MsgPackFormatter() );
		$logger->pushHandler( $socket );
	} else {
		trigger_error( 'Fluent Bit is not available. Logs will not be routed anywhere.', E_USER_WARNING );
	}

	$loggers[ $tag_name ] = $logger;
	return $loggers[ $tag_name ];
}
