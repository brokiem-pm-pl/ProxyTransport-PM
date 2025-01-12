<?php
declare(strict_types=1);

namespace NetherGames\ProxyTransport;

use libproxy\ProxyNetworkInterface;
use NetherGames\ProxyTransport\tasks\ComposerRegisterAsyncTask;
use pocketmine\event\server\NetworkInterfaceRegisterEvent;
use pocketmine\network\mcpe\raklib\RakLibInterface;
use pocketmine\plugin\PluginBase;
use function date_default_timezone_set;
use function is_file;
use function pocketmine\critical_error;
use const nethergames\COMPOSER_AUTOLOADER_PATH;

require_once 'CoreConstants.php';

class ProxyTransport extends PluginBase implements Listener
{

    public function onLoad(): void
    {
        //date_default_timezone_set('UTC');

        if (is_file(COMPOSER_AUTOLOADER_PATH)) {
            require_once(COMPOSER_AUTOLOADER_PATH);

            $asyncPool = $this->getServer()->getAsyncPool();
            $asyncPool->addWorkerStartHook(function (int $workerId) use ($asyncPool): void {
                $asyncPool->submitTaskToWorker(new ComposerRegisterAsyncTask(), $workerId);
            });
        } else {
            critical_error("Composer autoloader not found at " . COMPOSER_AUTOLOADER_PATH);
            critical_error("Please install/update Composer dependencies or use provided builds.");

            $this->getServer()->shutdown();
        }
    }

    public function onEnable(): void
    {
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
        $server = $this->getServer();
        $server->getNetwork()->registerInterface(
            new ProxyNetworkInterface($this, $server->getPort(), COMPOSER_AUTOLOADER_PATH)
        );
        $this->getLogger()->info('§aProxy interface registered!');
    }

    public function onNetworkInterfaceRegister(NetworkInterfaceRegisterEvent $event): void {
        $interface = $event->getInterface();
        if($interface instanceof RakLibInterface) {
            $event->cancel();
        }
    }
}
