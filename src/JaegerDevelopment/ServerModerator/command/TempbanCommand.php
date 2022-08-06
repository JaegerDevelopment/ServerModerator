<?php

namespace JaegerDevelopment\ServerModerator\command;

use JaegerDevelopment\ServerModerator\lang\LangManager;
use JaegerDevelopment\ServerModerator\ServerModerator;
use jojoe77777\FormAPI\CustomForm;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\plugin\Plugin;
use pocketmine\plugin\PluginOwned;
use pocketmine\utils\TextFormat;

class TempbanCommand extends Command implements PluginOwned {

    protected ServerModerator $plugin;

    public function __construct(ServerModerator $plugin){
        $this->plugin = $plugin;
        parent::__construct("tempban", "Tempban Command", "/tban", ["tban"]);
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args) : void{
        if(!$sender instanceof Player){
            $sender->sendMessage($this->getOwningPlugin()->getLangManager()->getTranslationMessage("execute-in-game"));
            return;
        }

        if(!$this->testPermission($sender)){
            $sender->sendMessage($this->getOwningPlugin()->getLangManager()->getTranslationMessage("no-perm", [$this->getName()]));
            return;
        }

        if(count($args) >= 1){
            $sender->sendMessage($this->getOwningPlugin()->getLangManager()->getTranslationMessage("usage-command", [$this->getUsage()]));
            return;
        }

        $form = new CustomForm(function(Player $player, ?array $data){
            if($data === null) return;

            var_dump($data);

            $target = $data[1];
            $playerExact = $this->getOwningPlugin()->getServer()->getPlayerExact($data[1]);
            if($playerExact === null){
                $player->sendMessage($this->getOwningPlugin()->getLangManager()->getTranslationMessage("player-not-exist", [$target->getName()]));
                return;
            }

            if($target instanceof Player || $this->getOwningPlugin()->getTbanManager()->isBanned($target)){
                $player->sendMessage($this->getOwningPlugin()->getLangManager()->getTranslationMessage("player-is-already-banned", [$target->getName()]));
                return;
            }

            $timeDays = 0;
            $timeHours = 0;
            $timeMinutes = 0;
            $timeSeconds = 0;
            $timeClause = explode(" ", $data[2]);
            foreach($timeClause as $arr => $time){
                if(strpos($time, "d")){
                    $days = trim($time[$arr]);
                    $days = $days * 86400;
                    $timeDays = $days;
                    var_dump("Days: " . $timeDays);
                }
                if(strpos($time, "h")){
                    $hours = trim($time[$arr]);
                    $hours = $hours * 3600;
                    $timeHours = $hours;
                    var_dump("Hours: " . $timeHours);
                }
                if(strpos($time, "m")){
                    $minutes = trim($time[$arr]);
                    $minutes = $minutes * 60;
                    $timeMinutes = $minutes;
                    var_dump("Minutes: " . $timeMinutes);
                }
                if(strpos($time, "s")){
                    $seconds = trim($time[$arr]);
                    $timeSeconds = $seconds;
                }
            }
            $time = time() + $timeDays + $timeHours + $timeMinutes + $timeSeconds;

            $reason = $data[3] ?? "";

            $this->getOwningPlugin()->getTempbanProvider()->registerPlayer($target, $time, $reason, $player);
            if($target instanceof Player) {
                if($target->isOnline()){
                    $target->kick($reason, $this->getOwningPlugin()->getLangManager()->getTranslationMessage("ban-message", [$this->getOwningPlugin()->getLangManager()->getLangFile()->get("server-prefix"), TextFormat::EOL, $this->getOwningPlugin()->getTbanManager()->getRemainingTimeMsg($time), $reason, $player->getName()]));
                }
            }
            $player->sendMessage($this->getOwningPlugin()->getLangManager()->getTranslationMessage("player-banned", [$target->getName()]));
        });
        $form->setTitle(LangManager::PREFIX);
        $form->addInput("Player:", "...");
        $form->addInput("Time:", "1d 2m 3s");
        $form->addInput("Reason:", "...");
    }

    public function getOwningPlugin(): ServerModerator{
        return $this->plugin;
    }

}