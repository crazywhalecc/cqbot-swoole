<?php

declare(strict_types=1);

namespace ZM\Context;

use Closure;
use Exception;
use Stringable;
use Swoole\Coroutine;
use Swoole\Http\Request;
use Swoole\WebSocket\Frame;
use Swoole\WebSocket\Server;
use ZM\API\ZMRobot;
use ZM\Config\ZMConfig;
use ZM\ConnectionManager\ConnectionObject;
use ZM\ConnectionManager\ManagerGM;
use ZM\Console\Console;
use ZM\Event\EventDispatcher;
use ZM\Exception\InterruptException;
use ZM\Exception\InvalidArgumentException;
use ZM\Exception\WaitTimeoutException;
use ZM\Exception\ZMKnownException;
use ZM\Http\Response;
use ZM\Utils\CoMessage;
use ZM\Utils\MessageUtil;

class Context implements ContextInterface
{
    public static $context = [];

    private $cid;

    public function __construct($cid)
    {
        $this->cid = $cid;
    }

    /**
     * @return Server
     */
    public function getServer(): ?Server
    {
        return self::$context[$this->cid]['server'] ?? server();
    }

    public function getFrame(): ?Frame
    {
        return self::$context[$this->cid]['frame'] ?? null;
    }

    public function getFd(): ?int
    {
        return self::$context[$this->cid]['fd'] ?? $this->getFrame()->fd ?? null;
    }

    /**
     * @return mixed
     */
    public function getData()
    {
        return self::$context[$this->cid]['data'] ?? null;
    }

    public function setData($data)
    {
        self::$context[$this->cid]['data'] = $data;
    }

    public function getRequest(): ?Request
    {
        return self::$context[$this->cid]['request'] ?? null;
    }

    public function getResponse(): ?Response
    {
        return self::$context[$this->cid]['response'] ?? null;
    }

    public function getConnection(): ?ConnectionObject
    {
        return ManagerGM::get($this->getFd());
    }

    public function getCid(): ?int
    {
        return $this->cid;
    }

    public function getRobot(): ?ZMRobot
    {
        $conn = ManagerGM::get($this->getFrame()->fd);
        return $conn instanceof ConnectionObject ? new ZMRobot($conn) : null;
    }

    public function getMessage()
    {
        if ((ZMConfig::get('global', 'onebot')['message_convert_string'] ?? true) === true && is_array($msg = $this->getOriginMessage())) {
            return MessageUtil::arrayToStr($msg);
        }
        return self::$context[$this->cid]['data']['message'] ?? null;
    }

    public function setMessage($msg)
    {
        if (is_string($msg) && is_array($this->getOriginMessage())) {
            $msg = MessageUtil::strToArray($msg);
        }
        self::$context[$this->cid]['data']['message'] = $msg;
    }

    public function getUserId()
    {
        return $this->getData()['user_id'] ?? null;
    }

    public function setUserId($id)
    {
        self::$context[$this->cid]['data']['user_id'] = $id;
    }

    public function getGroupId()
    {
        return $this->getData()['group_id'] ?? null;
    }

    public function setGroupId($id)
    {
        self::$context[$this->cid]['data']['group_id'] = $id;
    }

    public function getDiscussId()
    {
        return $this->getData()['discuss_id'] ?? null;
    }

    public function setDiscussId($id)
    {
        self::$context[$this->cid]['data']['discuss_id'] = $id;
    }

    public function getMessageType(): ?string
    {
        return $this->getData()['message_type'] ?? null;
    }

    public function setMessageType($type)
    {
        self::$context[$this->cid]['data']['message_type'] = $type;
    }

    public function getRobotId()
    {
        return $this->getData()['self_id'] ?? null;
    }

    public function getCache($key)
    {
        return self::$context[$this->cid]['cache'][$key] ?? null;
    }

    public function setCache($key, $value)
    {
        self::$context[$this->cid]['cache'][$key] = $value;
    }

    public function getCQResponse()
    {
        return self::$context[$this->cid]['cq_response'] ?? null;
    }

    /**
     * only can used by cq->message event function
     * @param  array|string          $msg   要回复的消息
     * @param  bool|callable|Closure $yield 是否协程挂起（true），是否绑定异步事件（Closure）
     * @return array|bool            返回API调用结果
     */
    public function reply($msg, $yield = false)
    {
        $data = $this->getData();
        $conn = $this->getConnection();
        if (!is_array($msg)) {
            switch ($this->getData()['message_type']) {
                case 'group':
                case 'private':
                case 'discuss':
                    $this->setCache('has_reply', true);
                    $operation['reply'] = $msg;
                    $operation['at_sender'] = false;
                    return (new ZMRobot($conn))->setCallback($yield)->callExtendedAPI('.handle_quick_operation', [
                        'context' => $data,
                        'operation' => $operation,
                    ]);
            }
            return false;
        }
        $operation = $msg;
        return (new ZMRobot($conn))->setCallback(false)->callExtendedAPI('.handle_quick_operation', [
            'context' => $data,
            'operation' => $operation,
        ]);
    }

    /**
     * @param  array|string       $msg   要回复的消息
     * @param  bool               $yield 是否协程挂起（true），是否绑定异步事件（Closure）
     * @throws InterruptException 阻止消息被后续插件处理
     */
    public function finalReply($msg, $yield = false)
    {
        self::$context[$this->cid]['cache']['block_continue'] = true;
        if ($msg != '') {
            $this->reply($msg, $yield);
        }
        EventDispatcher::interrupt();
    }

    /**
     * @param  string                   $prompt
     * @param  int                      $timeout
     * @param  string                   $timeout_prompt
     * @throws WaitTimeoutException
     * @throws InvalidArgumentException
     * @return string                   返回用户输入的内容
     */
    public function waitMessage($prompt = '', $timeout = 600, $timeout_prompt = '')
    {
        if (!isset($this->getData()['user_id'], $this->getData()['message'], $this->getData()['self_id'])) {
            throw new InvalidArgumentException('协程等待参数缺失');
        }

        Console::debug('==== 开始等待输入 ====');
        if ($prompt != '') {
            $this->reply($prompt);
        }

        try {
            $r = CoMessage::yieldByWS($this->getData(), ['user_id', 'self_id', 'message_type', get_onebot_target_id_name($this->getMessageType())], $timeout);
        } catch (Exception $e) {
            $r = false;
        }
        if ($r === false) {
            throw new WaitTimeoutException($this, $timeout_prompt);
        }
        if (is_array($r['message']) && (ZMConfig::get('global', 'onebot')['message_convert_string'] ?? true) === true) {
            return MessageUtil::arrayToStr($r['message']);
        }
        return $r['message'];
    }

    /**
     * 根据选定的模式获取消息参数
     * @param  int|string               $mode       获取的模式
     * @param  string|Stringable        $prompt_msg 提示语回复
     * @throws InvalidArgumentException
     * @throws ZMKnownException
     * @throws WaitTimeoutException
     * @return mixed|string
     */
    public function getArgs($mode, $prompt_msg)
    {
        $arg = ctx()->getCache('match') ?? [];
        switch ($mode) {
            case ZM_MATCH_ALL:
                $p = $arg;
                return trim(implode(' ', $p)) == '' ? $this->waitMessage($prompt_msg) : trim(implode(' ', $p));
            case ZM_MATCH_NUMBER:
                foreach ($arg as $k => $v) {
                    if (is_numeric($v)) {
                        array_splice($arg, $k, 1);
                        ctx()->setCache('match', $arg);
                        return $v;
                    }
                }
                return $this->waitMessage($prompt_msg);
            case ZM_MATCH_FIRST:
                if (isset($arg[0])) {
                    $a = $arg[0];
                    array_splice($arg, 0, 1);
                    ctx()->setCache('match', $arg);
                    return $a;
                }
                return $this->waitMessage($prompt_msg);
        }
        throw new InvalidArgumentException();
    }

    /**
     * 获取下一个参数
     * @param  string                   $prompt_msg 提示语回复
     * @throws InvalidArgumentException
     * @throws ZMKnownException
     * @throws WaitTimeoutException
     * @return int|mixed|string         返回获取的参数
     */
    public function getNextArg($prompt_msg = '')
    {
        return $this->getArgs(ZM_MATCH_FIRST, $prompt_msg);
    }

    /**
     * 获取接下来所有的消息当成一个完整的参数（包含空格）
     * @param  string                   $prompt_msg 提示语回复
     * @throws InvalidArgumentException
     * @throws ZMKnownException
     * @throws WaitTimeoutException
     * @return int|mixed|string         返回获取的参数
     */
    public function getFullArg($prompt_msg = '')
    {
        return $this->getArgs(ZM_MATCH_ALL, $prompt_msg);
    }

    /**
     * 获取下一个数字类型的参数
     * @param  string                   $prompt_msg 提示语回复
     * @throws InvalidArgumentException
     * @throws ZMKnownException
     * @throws WaitTimeoutException
     * @return int|mixed|string         返回获取的参数
     */
    public function getNumArg($prompt_msg = '')
    {
        return $this->getArgs(ZM_MATCH_NUMBER, $prompt_msg);
    }

    /**
     * @throws ZMKnownException
     * @return ContextInterface 返回上下文
     */
    public function cloneFromParent(): ContextInterface
    {
        set_coroutine_params(self::$context[Coroutine::getPcid()] ?? self::$context[$this->cid]);
        return context();
    }

    public function copy()
    {
        return self::$context[$this->cid];
    }

    public function getOption()
    {
        return self::getCache('match');
    }

    public function getOriginMessage()
    {
        return self::$context[$this->cid]['data']['message'] ?? null;
    }

    public function getArrayMessage(): array
    {
        $msg = $this->getOriginMessage();
        if (is_array($msg)) {
            return $msg;
        }
        return MessageUtil::strToArray($msg);
    }

    public function getStringMessage(): string
    {
        $msg = $this->getOriginMessage();
        if (is_string($msg)) {
            return $msg;
        }
        return MessageUtil::arrayToStr($msg);
    }
}
