<?php
/**
 * @copyright Copyright (c) 2014 Roman Ovchinnikov
 * @link https://github.com/RomeroMsk
 * @version 1.0.0
 */
namespace nex\graylog;

use Yii;
use yii\helpers\ArrayHelper;
use yii\helpers\VarDumper;
use yii\log\Target;
use yii\log\Logger;
use Gelf;
use Psr\Log\LogLevel;

/**
 * GraylogTarget sends log to Graylog2 (in GELF format)
 *
 * @author Roman Ovchinnikov <nex.software@gmail.com>
 * @link https://github.com/RomeroMsk/yii2-graylog2
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
    * @var boolean whether to add authenticated user username to additional fields
    */
    public $addUsername = false;

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
            list($text, $level, $category, $timestamp) = $message;
            $gelfMsg = new Gelf\Message;
            // For string log message set only shortMessage
            if (is_string($text)) {
                $gelfMsg->setShortMessage($text);
            } elseif ($text instanceof \Exception) {
                $gelfMsg->setShortMessage('Exception ' . get_class($text) . ': ' . $text->getMessage());
                $gelfMsg->setFullMessage((string) $text);
                $gelfMsg->setLine($text->getLine());
                $gelfMsg->setFile($text->getFile());
            } else {
                // If log message contains special keys 'short', 'full' or 'add', will use them as shortMessage, fullMessage and additionals respectively
                $short = ArrayHelper::remove($text, 'short');
                $full = ArrayHelper::remove($text, 'full');
                $add = ArrayHelper::remove($text, 'add');
                // If 'short' is set
                if ($short !== null) {
                    $gelfMsg->setShortMessage($short);
                    // All remaining message is fullMessage by default
                    $gelfMsg->setFullMessage(VarDumper::export($text));
                } else {
                    // Will use log message as shortMessage by default (no need to add fullMessage in this case)
                    $gelfMsg->setShortMessage(VarDumper::export($text));
                }
                // If 'full' is set will use it as fullMessage (note that all other stuff in log message will not be logged, except 'short' and 'add')
                if ($full !== null) {
                    $gelfMsg->setFullMessage(VarDumper::export($full));
                }
                // Process additionals array (only with string keys)
                if (is_array($add)) {
                    foreach ($add as $key => $val) {
                        if (is_string($key)) {
                            if (!is_string($val)) {
                                $val = VarDumper::export($val);
                            }
                            $gelfMsg->setAdditional($key, $val);
                        }
                    }
                }
            }
            // Set base parameters
            $gelfMsg->setLevel(ArrayHelper::getValue($this->_levels, $level, LogLevel::INFO))
                ->setTimestamp($timestamp)
                ->setFacility($this->facility)
                ->setAdditional('category', $category);
            // Set 'file', 'line' and additional 'trace', if log message contains traces array
            if (isset($message[4]) && is_array($message[4])) {
                $traces = [];
                foreach ($message[4] as $index => $trace) {
                    $traces[] = "{$trace['file']}:{$trace['line']}";
                    if ($index === 0) {
                        $gelfMsg->setFile($trace['file']);
                        $gelfMsg->setLine($trace['line']);
                    }
                }
                $gelfMsg->setAdditional('trace', implode("\n", $traces));
            }
            // Add username
            if (($this->addUsername) && (Yii::$app->has('user')) && ($user = Yii::$app->get('user')) && ($identity = $user->getIdentity(false))) {
                $gelfMsg->setAdditional('username', $identity->username);
            }
            // Publish message
            $publisher->publish($gelfMsg);
        }
    }
}
