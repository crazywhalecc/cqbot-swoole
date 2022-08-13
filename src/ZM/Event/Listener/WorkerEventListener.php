<?php

declare(strict_types=1);

namespace ZM\Event\Listener;

use OneBot\Driver\Process\ProcessManager;
use OneBot\Util\Singleton;
use Throwable;
use ZM\Annotation\AnnotationHandler;
use ZM\Annotation\AnnotationMap;
use ZM\Annotation\AnnotationParser;
use ZM\Annotation\Framework\Init;
use ZM\Container\ContainerServicesProvider;
use ZM\Exception\ZMKnownException;
use ZM\Framework;
use ZM\Process\ProcessStateManager;
use ZM\Utils\ZMUtil;

class WorkerEventListener
{
    use Singleton;

    /**
     * Driver 的 Worker 进程启动后执行的事件
     *
     * @throws Throwable
     */
    public function onWorkerStart()
    {
        // 自注册一下，刷新当前进程的logger进程banner
        ob_logger_register(ob_logger());

        // 如果没有引入参数disable-safe-exit，则监听 Ctrl+C
        if (!Framework::getInstance()->getArgv()['disable-safe-exit'] && PHP_OS_FAMILY !== 'Windows') {
            SignalListener::getInstance()->signalWorker();
        }
        logger()->debug('Worker #' . ProcessManager::getProcessId() . ' started');

        // 设置 Worker 进程的状态和 ID 等信息
        if (($name = Framework::getInstance()->getDriver()->getName()) === 'swoole') {
            /* @phpstan-ignore-next-line */
            $server = Framework::getInstance()->getDriver()->getSwooleServer();
            ProcessStateManager::saveProcessState(ZM_PROCESS_WORKER, $server->worker_pid, ['worker_id' => $server->worker_id]);
        } elseif ($name === 'workerman' && DIRECTORY_SEPARATOR !== '\\' && extension_loaded('posix')) {
            ProcessStateManager::saveProcessState(ZM_PROCESS_WORKER, posix_getpid(), ['worker_id' => ProcessManager::getProcessId()]);
        }

        // 设置容器，注册容器提供商
        resolve(ContainerServicesProvider::class)->registerServices('global');

        // 注册 Worker 进程遇到退出时的回调，安全退出
        register_shutdown_function(function () {
            $error = error_get_last();
            // 下面这段代码的作用就是，不是错误引发的退出时照常退出即可
            if (($error['type'] ?? 0) != 0) {
                logger()->emergency(zm_internal_errcode('E00027') . 'Internal fatal error: ' . $error['message'] . ' at ' . $error['file'] . "({$error['line']})");
            } elseif (!isset($error['type'])) {
                return;
            }
            Framework::getInstance()->stop();
        });

        // TODO: 注册各种池子

        // 加载用户代码资源
        $this->loadUserSources();

        // handle @Init annotation
        $this->handleInit();

        // 回显 debug 日志：进程占用的内存
        $memory_total = memory_get_usage() / 1024 / 1024;
        logger()->debug('Worker process used ' . round($memory_total, 3) . ' MB');
    }

    /**
     * @throws ZMKnownException
     */
    public function onWorkerStop()
    {
        logger()->debug('Worker #' . ProcessManager::getProcessId() . ' stopping');
        ProcessStateManager::removeProcessState(ZM_PROCESS_WORKER, ProcessManager::getProcessId());
    }

    /**
     * 加载用户代码资源，包括普通插件、单文件插件、Composer 插件等
     * @throws Throwable
     */
    private function loadUserSources()
    {
        logger()->debug('Loading user sources');

        // 首先先加载 source 普通插件，相当于内部模块，不算插件的一种
        $parser = new AnnotationParser();
        $composer = ZMUtil::getComposerMetadata();
        // 合并 dev 和 非 dev 的 psr-4 加载目录
        $merge_psr4 = array_merge($composer['autoload']['psr-4'] ?? [], $composer['autoload-dev']['psr-4'] ?? []);
        // 排除 composer.json 中指定需要排除的目录
        $excludes = $composer['extra']['zm']['exclude-annotation-path'] ?? [];
        foreach ($merge_psr4 as $k => $v) {
            // 如果在排除表就排除，否则就解析注解
            if (is_dir(SOURCE_ROOT_DIR . '/' . $v) && !in_array($v, $excludes)) {
                // 添加解析路径，对应Base命名空间也贴出来
                $parser->addRegisterPath(SOURCE_ROOT_DIR . '/' . $v . '/', trim($k, '\\'));
            }
        }

        // TODO: 然后加载插件目录下的插件

        // 解析所有注册路径的文件，获取注解
        $parser->parseAll();
        // 将Parser解析后的注解注册到全局的 AnnotationMap
        AnnotationMap::loadAnnotationByParser($parser);
    }

    private function handleInit()
    {
        $handler = new AnnotationHandler(Init::class);
        $handler->setRuleCallback(function (Init $anno) {
            return $anno->worker === -1 || $anno->worker === ProcessManager::getProcessId();
        });
        $handler->handleAll();
    }
}
