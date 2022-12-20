<?php

declare(strict_types=1);

class_alias(ZM\Annotation\Framework\BindEvent::class, 'BindEvent');
class_alias(ZM\Annotation\Framework\Init::class, 'Init');
class_alias(ZM\Annotation\Framework\Setup::class, 'Setup');
class_alias(ZM\Annotation\Http\Controller::class, 'Controller');
class_alias(ZM\Annotation\Http\Route::class, 'Route');
class_alias(ZM\Annotation\Middleware\Middleware::class, 'Middleware');
class_alias(ZM\Annotation\OneBot\BotCommand::class, 'BotCommand');
class_alias(ZM\Annotation\OneBot\BotEvent::class, 'BotEvent');
class_alias(ZM\Annotation\OneBot\CommandArgument::class, 'CommandArgument');
class_alias(ZM\Annotation\Closed::class, 'Closed');
class_alias(ZM\Plugin\ZMPlugin::class, 'ZMPlugin');
class_alias(OneBot\V12\Object\OneBotEvent::class, 'OneBotEvent');
class_alias(ZM\Context\BotContext::class, 'BotContext');
class_alias(OneBot\Driver\Event\WebSocket\WebSocketOpenEvent::class, 'WebSocketOpenEvent');
class_alias(OneBot\Driver\Event\WebSocket\WebSocketCloseEvent::class, 'WebSocketCloseEvent');
class_alias(OneBot\Driver\Event\WebSocket\WebSocketMessageEvent::class, 'WebSocketMessageEvent');
class_alias(OneBot\Driver\Event\Http\HttpRequestEvent::class, 'HttpRequestEvent');
