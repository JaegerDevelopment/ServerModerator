<?php

namespace JaegerDevelopment\ServerModerator\command;

use JaegerDevelopment\ServerModerator\ServerModerator;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\command\ConsoleCommandSender;
use pocketmine\command\PluginIdentifiableCommand;
use pocketmine\command\utils\CommandException;
use pocketmine\command\utils\InvalidCommandSyntaxException;
use pocketmine\Player;
use pocketmine\plugin\Plugin;
use jojoe77777\FormAPI\CustomForm;
use pocketmine\utils\TextFormat;

class KickCommand extends Command implements PluginIdentifiableCommand {

    public $playerList = [];

    private ServerModerator $plugin;

    public function __construct(ServerModerator $plugin){
        $this->plugin = $plugin;
        parent::__construct("kick", $this->plugin->getConfig()->get("kick-command-description"), "/kick <player> <reason>", [""]);
    }

    /**
     * @param string[] $args
     *
     * @return mixed
     * @throws CommandException
     */
    public function execute(CommandSender $sender, string $commandLabel, array $args){
       if(!($sender->hasPermission("servermoderator.kick"))){
           $sender->sendMessage($this->getPlugin()->getConfig()->get("prefix") . " " . $this->getPlugin()->getConfig()->get("not-permission-message"));
           return;
       }

        if($sender instanceof ConsoleCommandSender){
            $sender->sendMessage($this->getPlugin()->getConfig()->get("prefix") . " " . $this->getPlugin()->getConfig()->get("command-not-execute-on-console"));
            return;
        }

       switch($this->getPlugin()->getConfig()->get("type")){
           case "cmd":
               if($sender instanceof Player){
                   if(isset($args[0])){
                       $target = $this->plugin->getServer()->getPlayer($args[0]);
                       if(isset($args[1])){
                           $this->kickCMD($sender, $target, $args[1]);
                       }
                       $this->kickCMD($sender, $target);
                   } else {
                       throw new InvalidCommandSyntaxException();
                   }
               }
           break;

           case "ui":
           default:
               if($sender instanceof Player){
                   $this->kickUI($sender);
               }
           break;
       }
    }

    public function kickUI(Player $player) : void {
        $form = new CustomForm(function(Player $player, ?array $data) : void{
            if($data === null){
                return;
            }

            if($data[1] === null){
                $player->sendMessage($this->getPlugin()->getConfig()->get("prefix") . " " . $this->getPlugin()->getConfig()->get("add-player-error"));
                return;
            }

            $index = $data[1];
            $targetName = $this->playerList[$player->getName()][$index];
            $target = $this->getPlugin()->getServer()->getPlayer($targetName);

            if(!($player->hasPermission("servermoderator.kick"))){
                $player->sendMessage($this->getPlugin()->getConfig()->get("not-permission-message"));
                return;
            }

            if($this->getPlugin()->getConfig()->get("kick-broadcastmessage-mode") === "on"){
                $broadcastmessage = str_replace(["{target}", "{reason}", "{staffer}"], [$target->getName(), trim($data[2]) !== "" ? " Reason: " . $data[2] : "", $player->getName()], $this->getPlugin()->getConfig()->get("kick-broadcastmessage"));
                $this->getPlugin()->getServer()->broadcastMessage($this->getPlugin()->getConfig()->get("prefix") . " " . $broadcastmessage);
            }

            $message = str_replace(["{target}", "{reason}", "{staffer}"], [$target->getName(), trim($data[2]) !== "" ? " Reason: " . $data[2] : "", $player->getName()], $this->getPlugin()->getConfig()->get("kick-message"));
            $target->kick($this->getPlugin()->getConfig()->get("prefix") . " " . $message, false);
            foreach($this->getPlugin()->getServer()->getOnlinePlayers() as $staffers){
                if($staffers->hasPermission("servermoderator.kick")){
                    $message = str_replace(["{target}", "{reason}", "{staffer}"], [$target->getName(), trim($data[2]) !== "" ? " Reason: " . $data[2] : "", $player->getName()], $this->getPlugin()->getConfig()->get("kick-message-for-staffers"));
                    $staffers->sendMessage($this->getPlugin()->getConfig()->get("prefix") . " " . $message);
                }
            }
        });
        $list = [];
        foreach($this->plugin->getServer()->getOnlinePlayers() as $players){
            $list[] = $players->getName();
        }
        $this->playerList[$player->getName()] = $list;
        $form->setTitle($this->getPlugin()->getConfig()->get("kick-ui-title"));
        $form->addLabel($this->getPlugin()->getConfig()->get("kick-ui-content"));
        $form->addDropdown("Players:", $this->playerList[$player->getName()]);
        $form->addInput("Reason:", "...");
        $player->sendForm($form);
    }

    public function kickCMD($player, Player $target, string $reason = null) : bool{
        if(!($player->hasPermission("servermoderator.kick"))){
            $player->sendMessage($this->getPlugin()->getConfig()->get("not-permission-message"));
            return true;
        }
        if($this->getPlugin()->getConfig()->get("kick-broadcastmessage-mode") === "on"){
            $broadcastmessage = str_replace(["{target}", "{reason}", "{staffer}"], [$target->getName(), $reason, $player->getName()], $this->getPlugin()->getConfig()->get("kick-broadcastmessage"));
            $this->getPlugin()->getServer()->broadcastMessage($this->getPlugin()->getConfig()->get("prefix") . " " . $broadcastmessage);
        }

        $message = str_replace(["{target}", "{reason}", "{staffer}"], [$target->getName(), $reason, $player->getName()], $this->getPlugin()->getConfig()->get("kick-message"));
        $target->kick($this->getPlugin()->getConfig()->get("prefix") . $message, false);
        foreach($this->getPlugin()->getServer()->getOnlinePlayers() as $staffers){
            if($staffers->hasPermission("servermoderator.kick")){
                $message = str_replace(["{target}", "{reason}", "{staffer}"], [$target->getName(), $reason, $player->getName()], $this->getPlugin()->getConfig()->get("kick-message-for-staffers"));
                $staffers->sendMessage($this->getPlugin()->getConfig()->get("prefix") . " " . $message);
            }
        }
        return true;
    }

    public function getPlugin(): Plugin{
        return $this->plugin;
    }
}