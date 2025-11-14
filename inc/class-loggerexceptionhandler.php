<?php
/**
 * Altis Cloud Monolog Exception Handler.
 *
 * @package altis/cloud
 */

namespace Altis\Cloud;

use Monolog\Handler\HandlerWrapper;
use Monolog\LogRecord;
use Throwable;

/**
 * Catches exceptions thrown by Monolog and reports them using error_log.
 */
class LoggerExceptionHandler extends HandlerWrapper {

	/**
	 * Check handler for exceptions.
	 *
	 * @param LogRecord $record The structured error log record.
	 *
	 * @return bool
	 */
	public function handle( LogRecord $record ) : bool {
		try {
			return $this->handler->handle( $record );
		} catch ( Throwable $e ) {
			$this->logException( $e );
		}

		return true;
	}

	/**
	 * Catch batch exceptions.
	 *
	 * @param array $records The batch of structured error log records.
	 * @return void
	 */
	public function handleBatch( array $records ) : void {
		try {
			$this->handler->handleBatch( $records );
		} catch ( Throwable $e ) {
			$this->logException( $e );
		}
	}

	/**
	 * Receives a Throwable and logs a fatal error-like message for visibility
	 *
	 * @param Throwable $e Thrown exception.
	 * @return void
	 */
	protected function logException( Throwable $e ) : void {
		error_log( sprintf( "Fluent Bit Exception[%s]: %s in %s:%s\nStack Trace:\n%s", get_class( $e ), $e->getMessage(), $e->getFile(), $e->getLine(), $e->getTraceAsString() ) );
	}
}
