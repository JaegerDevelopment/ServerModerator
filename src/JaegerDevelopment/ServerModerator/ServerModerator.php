<?php

namespace JaegerDevelopment\ServerModerator;

use JaegerDevelopment\ServerModerator\command\KickCommand;
use JaegerDevelopment\ServerModerator\command\UnbanCommand;
use JaegerDevelopment\ServerModerator\command\SsCommand;
use JaegerDevelopment\ServerModerator\command\TempbanCommand;
use JaegerDevelopment\ServerModerator\listener\ListenerBase;
use JaegerDevelopment\ServerModerator\listener\PlayerListener;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\TextFormat;

class ServerModerator extends PluginBase{

    public function onEnable() : void{
        $this->registerEvents();
        $this->unregisterCommands();
        $this->registerCommands();
        // For next features
        if(!is_dir($this->getDataFolder() . 'players/')){
            mkdir($this->getDataFolder() . 'players/');
        }
    }

    public function registerEvents(){
        $register = $this->getServer()->getPluginManager();
        $register->registerEvents(new ListenerBase($this), $this);
        $register->registerEvents(new PlayerListener($this), $this);
    }

    public function registerCommands() : void {
        $this->getServer()->getCommandMap()->registerAll($this->getName(), [
            new KickCommand($this),
            new TempbanCommand($this),
			new UnbanCommand($this),
			new SsCommand($this)
        ]);
    }

    public function unregisterCommands() : void {
        $this->getServer()->getCommandMap()->unregister($this->getServer()->getCommandMap()->getCommand("kick"));
		$this->getServer()->getCommandMap()->unregister($this->getServer()->getCommandMap()->getCommand("unban"));
    }

    public function onDisable(){}
}