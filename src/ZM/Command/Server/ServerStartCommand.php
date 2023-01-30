<?php

declare(strict_types=1);

namespace ZM\Command\Server;

use OneBot\Driver\Process\ProcessManager;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use ZM\Exception\InitException;
use ZM\Exception\ZMKnownException;
use ZM\Framework;
use ZM\Process\ProcessStateManager;

#[AsCommand(name: 'server', description: '启动服务器', aliases: ['server:start'])]
class ServerStartCommand extends ServerCommand
{
    public static function exportOptionArray(): array
    {
        $cmd = new self();
        $cmd->configure();
        return array_map(fn ($x) => $x->getDefault(), $cmd->getDefinition()->getOptions());
    }

    protected function configure()
    {
        $this->setDefinition([
            new InputOption('config-dir', null, InputOption::VALUE_REQUIRED, '指定其他配置文件目录'),
            new InputOption('driver', null, InputOption::VALUE_REQUIRED, '指定驱动类型'),
            new InputOption('log-level', null, InputOption::VALUE_REQUIRED, '调整消息等级到debug (log-level=4)'),
            new InputOption('daemon', null, null, '以守护进程的方式运行框架'),
            new InputOption('worker-num', null, InputOption::VALUE_REQUIRED, '启动框架时运行的 Worker 进程数量'),
            new InputOption('watch', null, null, '监听 plugins/ 目录下各个插件的文件变化并热更新（还不能用）'),
            new InputOption('env', null, InputOption::VALUE_REQUIRED, '设置环境类型 (production, development, staging)'),
            new InputOption('disable-safe-exit', null, null, '关闭安全退出（关闭后按CtrlC时直接杀死进程）'),
            new InputOption('no-state-check', null, null, '关闭启动前框架运行状态检查'),
            new InputOption('private-mode', null, null, '启动时隐藏MOTD和敏感信息'),
            new InputOption('print-process-pid', null, null, '打印所有进程的PID'),
        ]);
        $this->setHelp('直接运行可以启动');
    }

    /**
     * @throws ZMKnownException
     * @throws \Exception
     * @noinspection PhpComposerExtensionStubsInspection
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // 如果是支持多进程模式的，那么就检查框架进程的状态
        if (ProcessManager::isSupportedMultiProcess()) {
            $state = ProcessStateManager::getProcessState(ZM_PROCESS_MASTER);
            if (!$input->getOption('no-state-check')) {
                if (is_array($state) && posix_getsid($state['pid'] ?? -1) !== false) {
                    $output->writeln("<error>检测到已经在 pid: {$state['pid']} 进程启动了框架！</error>");
                    $output->writeln('<error>不可以同时启动两个框架！</error>');
                    return 1;
                }
            }
        }
        // 框架启动的入口
        (new Framework($input->getOptions()))->init()->start();
        return 0;
    }
}
