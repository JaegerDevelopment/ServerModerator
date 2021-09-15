<?php

namespace JaegerDevelopment\ServerModerator\listener;

use JaegerDevelopment\ServerModerator\ServerModerator;
use pocketmine\event\Listener;
use pocketmine\Server;

class ListenerBase implements Listener{

    private ServerModerator $serverModerator;

    public function __construct(ServerModerator $serverModerator){
        $this->serverModerator = $serverModerator;
    }

    public function getPlugin() : ServerModerator{
        return $this->serverModerator;
    }

    protected function getServer() : Server{
        return $this->serverModerator->getServer();
    }

}