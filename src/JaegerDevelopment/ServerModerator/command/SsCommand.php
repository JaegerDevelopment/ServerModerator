<?php

namespace JaegerDevelopment\ServerModerator\command;

use JaegerDevelopment\ServerModerator\ServerModerator;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\command\PluginIdentifiableCommand;
use pocketmine\command\utils\CommandException;
use pocketmine\level\Position;
use pocketmine\Player;
use pocketmine\plugin\Plugin;
use pocketmine\utils\TextFormat as TF;

class SsCommand extends Command implements PluginIdentifiableCommand {

    private ServerModerator $plugin;

    public function __construct(ServerModerator $plugin){
        $this->plugin = $plugin;
        parent::__construct("ss", $this->getPlugin()->getConfig()->get("ss-command-description"), "/ss <mode> <player>", ["controlhack"]);
    }

    /**
     * @param string[] $args
     *
     * @return mixed
     * @throws CommandException
     */
    public function execute(CommandSender $sender, string $commandLabel, array $args){
        $data = $this->getPlugin()->getConfig();
        if($sender instanceof Player){
            if($sender->hasPermission("servermoderator.ss")){

                if(!isset($args[0])){
                    $sender->sendMessage($this->getPlugin()->getConfig()->get("prefix") . " " . $this->getPlugin()->getConfig()->get("ss-empty-mode"));
                    return true;
                }

                switch($args[0]){
                    case "start":
                        if(!isset($args[1])){
                            $sender->sendMessage($this->getPlugin()->getConfig()->get("prefix") . " " . $this->getPlugin()->getConfig()->get("player-not-found"));
                            return true;
                        }
                        $target = $this->getPlugin()->getServer()->getPlayer($args[1]);
                        if($target->isOnline()){
                            $sender->teleport(new Position($data->getNested("ss.staffer.x"), $data->getNested("ss.staffer.y"), $data->getNested("ss.staffer.z"), $this->getPlugin()->getServer()->getLevelByName($data->getNested("ss.staffer.world"))));
                            $target->teleport(new Position($data->getNested("ss.target.x"), $data->getNested("ss.target.y"), $data->getNested("ss.target.z"), $this->getPlugin()->getServer()->getLevelByName($data->getNested("ss.target.world"))));
                            $target->sendTitle("You are gay", 300, 100);
                            $target->setImmobile(true);
                            $message = str_replace("{staffer}", $sender->getName(), $this->getPlugin()->getConfig()->get("ss-start-message"));

                            if($this->getPlugin()->getConfig()->get("ss-start-title") === "on"){
                                $target->sendTitle($this->getPlugin()->getConfig()->get("prefix") . " " . $message, "", 100);
                            }
                            if($this->getPlugin()->getConfig()->get("ss-start-popup") === "on"){
                                $target->sendPopup($this->getPlugin()->getConfig()->get("prefix") . " " . $message, "");
                            }
                        }
                        $sender->sendMessage($this->getPlugin()->getConfig()->get("prefix") . " " . $this->getPlugin()->getConfig()->get("player-not-exist"));
                    break;

                    case "setposition":

                        if(!$sender->isOp()){
                            $sender->sendMessage($this->getPlugin()->getConfig()->get("prefix") . " " . $this->getPlugin()->getConfig()->get("not-permission-message"));
                            return;
                        }

                        if(!isset($args[1])){
                            $sender->sendMessage($this->getPlugin()->getConfig()->get("prefix") . " " . $this->getPlugin()->getConfig()->get("ss-choose-position"));
                            return true;
                        }
                        switch($args[1]){
                            case "target":
                                $data->setNested("ss.target.x", $sender->getX());
                                $data->setNested("ss.target.y", $sender->getY());
                                $data->setNested("ss.target.z", $sender->getZ());
                                $data->setNested("ss.target.world", $sender->getLevel()->getName());
                                $data->save();
                                $sender->sendMessage($this->getPlugin()->getConfig()->get("prefix") . " " . $this->getPlugin()->getConfig()->get("ss-set-position-success"));
                            break;

                            case "staffer":
                                $data->setNested("ss.staffer.x", $sender->getX());
                                $data->setNested("ss.staffer.y", $sender->getY());
                                $data->setNested("ss.staffer.z", $sender->getZ());
                                $data->setNested("ss.staffer.world", $sender->getLevel()->getName());
                                $data->save();
                                $sender->sendMessage($this->getPlugin()->getConfig()->get("prefix") . " " . $this->getPlugin()->getConfig()->get("ss-set-position-success"));
                            break;
                        }
                    break;

                    case "finish":
                        if(!isset($args[1])){
                            $sender->sendMessage($this->getPlugin()->getConfig()->get("prefix") . " " . $this->getPlugin()->getConfig()->get("player-not-found"));
                        }
                        $target = $this->getPlugin()->getServer()->getPlayer($args[1]);
                        if($target->isOnline()){
                            $target->setImmobile(false);
                            $target->teleport($this->getPlugin()->getServer()->getLevelByName($this->getPlugin()->getConfig()->getNested("ss-lobbyworld"))->getSpawnLocation());
                            $target->sendMessage($this->getPlugin()->getConfig()->get("prefix") . " " . $this->getPlugin()->getConfig()->get("ss-finish-message"));
                        }
                    break;
                }
            } else {
                $sender->sendMessage($this->getPlugin()->getConfig()->get("prefix") . " " . $this->getPlugin()->getConfig()->get("not-permission-message"));
            }
        }
    }

    public function getPlugin(): Plugin{
        return $this->plugin;
    }
}