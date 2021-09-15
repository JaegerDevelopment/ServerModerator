<?php

namespace JaegerDevelopment\ServerModerator\command;

use JaegerDevelopment\ServerModerator\listener\ListenerBase;
use JaegerDevelopment\ServerModerator\ServerModerator;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\command\PluginIdentifiableCommand;
use pocketmine\command\utils\CommandException;
use pocketmine\command\utils\InvalidCommandSyntaxException;
use pocketmine\lang\TranslationContainer;
use pocketmine\plugin\Plugin;

class UnbanCommand extends Command implements PluginIdentifiableCommand {

    private ServerModerator $plugin;

    public function __construct(ServerModerator $plugin){
        $this->plugin = $plugin;
        parent::__construct("unban", $this->plugin->getConfig()->get("unban-command-description"), "/unban <player>", ["uban"]);
        $this->setPermission("servermoderator.unban");
    }

    /**
     * @param string[] $args
     *
     * @return mixed
     * @throws CommandException
     */
    public function execute(CommandSender $sender, string $commandLabel, array $args){
        if(!$this->testPermission($sender)){
            return true;
        }

        if(count($args) !== 1){
            throw new InvalidCommandSyntaxException();
        }

        $sender->getServer()->getNameBans()->remove($args[0]);

        foreach($this->getPlugin()->getServer()->getOnlinePlayers() as $staffers){
            if($staffers->hasPermission("servermoderator.unban")){
                $staffers->sendMessage($this->getPlugin()->getConfig()->get("prefix") . "Staffer " . $sender . " unbanned Player " . $args[0]);
            }

        }
        $this->getPlugin()->getServer()->broadcastMessage($this->getPlugin()->getConfig()->get("prefix") . "Player " . $args[0] . "is unbanned.");
    }

    public function getPlugin(): Plugin{
        return $this->plugin;
    }
}