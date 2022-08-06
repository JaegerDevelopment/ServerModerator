<?php

declare(strict_types = 1);

namespace JaegerDevelopment\ServerModerator\provider\tempban;

use JaegerDevelopment\ServerModerator\ServerModerator;
use pocketmine\player\Player;
use poggit\libasynql\DataConnector;

abstract class TempbanProvider {

    public function __construct(protected ServerModerator $plugin){
        $this->createTempbanTable();
    }

    public function getPlugin(): ServerModerator{
        return $this->plugin;
    }

    protected abstract function createTempbanTable() : void;

    public abstract function registerPlayer(Player $player, int $time, string $reason, Player $staffer) : void;

    public abstract function removePlayer(Player $player) : void;

    public abstract function getTime(Player $player, callable $callable) : ?int;

    public abstract function getReason(Player $player, callable $callable) : string;

    public abstract function getStaffer(Player $player, callable $callable) : string;

    public abstract function getDatabase() : DataConnector;
}
