<?php

namespace Altis\Cloud\FluentBit;

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

/**
 * Class MsgPackFormatter
 *
 * Serializes a log message to Fluent Bit socket protocol
 *
 * @author Nathaniel Schweinberg <nathaniel@humanmade.com>
 */
class MsgPackFormatter extends FluentdFormatter
{
    /**
     * @var bool $levelTag should message level be a part of the fluentd tag
     */
    protected $levelTag = false;

    public function __construct(bool $levelTag = false)
    {
        if (!function_exists('msgpack_pack')) {
            throw new \RuntimeException('PHP\'s msgpack extension is required to use Monolog\'s MsgPackFormatter');
        }

        $this->levelTag = $levelTag;
    }

    public function format(array $record): string
    {
        $tag = $record['channel'];
        if ($this->levelTag) {
            $tag .= '.' . strtolower($record['level_name']);
        }

        $message = [
            'message' => $record['message'],
            'context' => $record['context'],
            'extra' => $record['extra'],
        ];

        if (!$this->levelTag) {
            $message['level'] = $record['level'];
            $message['level_name'] = $record['level_name'];
        }

        return msgpack_pack([$tag, $record['datetime']->getTimestamp(), $message]);
    }
}
