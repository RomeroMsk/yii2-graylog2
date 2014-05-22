<?php
/**
 * @copyright Copyright (c) 2014 Roman Ovchinnikov
 * @link https://github.com/RomeroMsk
 * @version 1.0.0
 */
namespace nex\graylog;

use Yii;
use yii\helpers\ArrayHelper;
use yii\log\Target;
use yii\log\Logger;
use Gelf;
use Psr\Log\LogLevel;

/**
 * GraylogTarget sends log to Graylog2 (in GELF format)
 *
 * @author Roman Ovchinnikov <nex.software@gmail.com>
 * @link https://github.com/RomeroMsk/yii2-chosen
 */
class GraylogTarget extends Target
{
    /**
     * @var string Graylog2 host
     */
    public $host = '127.0.0.1';

    /**
     * @var integer Graylog2 port
     */
    public $port = 12201;

    /**
     * @var string default facility name
     */
    public $facility = 'yii2-logs';

    /**
     * @var array graylog levels
     */
    private $_levels = [
        Logger::LEVEL_TRACE => LogLevel::DEBUG,
        Logger::LEVEL_PROFILE_BEGIN => LogLevel::DEBUG,
        Logger::LEVEL_PROFILE_END => LogLevel::DEBUG,
        Logger::LEVEL_INFO => LogLevel::INFO,
        Logger::LEVEL_WARNING => LogLevel::WARNING,
        Logger::LEVEL_ERROR => LogLevel::ERROR,
    ];

    /**
     * Sends log messages to Graylog2 input
     */
    public function export()
    {
        $transport = new Gelf\Transport\UdpTransport($this->host, $this->port, Gelf\Transport\UdpTransport::CHUNK_SIZE_LAN);
        $publisher = new Gelf\Publisher($transport);
        foreach ($this->messages as $message) {
            $gelfMsg = new Gelf\Message();
            $gelfMsg->setShortMessage($message[0])
                ->setFullMessage($message[0])
                ->setLevel(ArrayHelper::getValue($this->_levels, $message[1]))
                ->setTimestamp($message[3])
                ->setFacility($this->facility);
            if (isset($message[4][0]['file'])) {
                $gelfMsg->setFile($message[4][0]['file']);
            }
            if (isset($message[4][0]['line'])) {
                $gelfMsg->setLine($message[4][0]['line']);
            }
            $publisher->publish($gelfMsg);
        }
    }
}
