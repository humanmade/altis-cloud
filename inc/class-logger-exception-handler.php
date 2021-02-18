<?php

/**
 * Altis Cloud Fluent Bit MsgPack formatter.
 *
 * @package altis/cloud
 */

namespace Altis\Cloud;

/*
 * Catches exceptions thrown by Monolog and reports them using error_log.
 *
 */

use Monolog\Handler\HandlerWrapper;
use Throwable;

class LoggerExceptionHandler extends HandlerWrapper {

	/**
	 * {@inheritdoc}
	 */
	public function handle(array $record) : bool {
		try {
			return $this->handler->handle($record);
		} catch ( Throwable $e ) {
			$this->logException( $e );
		}

		return true;
	}

	/**
	 * {@inheritdoc}
	 */
	public function handleBatch(array $records) : void {
		try {
			$this->handler->handleBatch( $records );
		} catch ( Throwable $e ) {
			$this->logException( $e );
		}
	}

	protected function logException( Throwable $e ) {
		error_log( sprintf( "Fluent Bit Exception[%s]: %s in %s:%s\nStack Trace:\n%s", get_class( $e ), $e->getMessage(), $e->getFile(), $e->getLine(), $e->getTraceAsString() ) );
	}
}
