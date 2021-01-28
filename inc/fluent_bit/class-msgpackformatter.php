<?php
/**
 * Altis Cloud Fluent Bit MsgPack formatter.
 *
 * @package altis/cloud
 */

namespace Altis\Cloud\Fluent_Bit;

/*
 * TODO Copies some formatter from Monolog, need to give credit
 * This file is part of the Monolog package.
 *
 * (c) Jordi Boggiano <j.boggiano@seld.be>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Monolog\Formatter\FluentdFormatter;
use RuntimeException;

/**
 * Class MsgPackFormatter
 *
 * Serializes a log message to Fluent Bit socket protocol
 *
 * @author Nathaniel Schweinberg <nathaniel@humanmade.com>
 */
class MsgPackFormatter extends FluentdFormatter {

	/**
	 * Construct the MsgPackFormatter class.
	 *
	 * @param boolean $levelTag whether to append log level to Fluent Bit tag
	 * name or not.
     * @return self
     * @throws RuntimeException
	 */
	public function __construct( bool $levelTag = false ) { // phpcs:ignore WordPress.NamingConventions.ValidVariableName
		if ( ! function_exists( 'msgpack_pack' ) ) {
			throw new \RuntimeException( 'PHP\'s msgpack extension is required to use Monolog\'s MsgPackFormatter' );
		}

		// phpcs:ignore WordPress.NamingConventions.ValidVariableName
		$this->levelTag = $levelTag;
	}

	/**
	 * Normalizes and formats the log record using the msgpack serialization
	 * format.
	 *
	 * @param array $record Monolog log record
	 * @return string The formatted log record
	 */
	public function format( array $record ): string {
		$tag = $record['channel'];

		// phpcs:ignore WordPress.NamingConventions.ValidVariableName
		if ( $this->levelTag ) {
			$tag .= '.' . strtolower( $record['level_name'] );
		}

		$message = [
			'message' => $record['message'],
			'context' => $record['context'],
			'extra' => $record['extra'],
		];

		// phpcs:ignore WordPress.NamingConventions.ValidVariableName
		if ( ! $this->levelTag ) {
			$message['level'] = $record['level'];
			$message['level_name'] = $record['level_name'];
		}

		return msgpack_pack( [ $tag, $record['datetime']->getTimestamp(), $message ] );
	}
}
