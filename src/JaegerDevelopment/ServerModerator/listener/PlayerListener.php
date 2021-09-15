<?php

namespace JaegerDevelopment\ServerModerator\listener;

use DateTime;
use JaegerDevelopment\ServerModerator\command\TempbanCommand;
use JaegerDevelopment\ServerModerator\ServerModerator;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerPreLoginEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat;

class PlayerListener extends ListenerBase implements Listener{

    public function onPreLogin(PlayerPreLoginEvent $event): void{
        $player = $event->getPlayer();
        $banList = $player->getServer()->getNameBans();
        if($this->getPlugin()->getServer()->getNameBans()->isBanned(strtolower($player->getName()))){
            $banEntry = $banList->getEntries();
            $entry = $banEntry[strtolower($player->getName())];
            $reason = $entry->getReason();
            $staffer = $entry->getSource();
            $expire = TempbanCommand::expirationTimerToString($entry->getExpires(), new DateTime());
            if($reason !== ""){
                $player->kick($this->getPlugin()->getConfig()->get("prefix") . str_replace(["{target}", "{reason}", "{time}", "{staffer}"], [$player->getName(), $reason, $expire, $staffer], $this->getPlugin()->getConfig()->get("tempban-message-with-reason")), false);
            } else{
                $player->kick($this->getPlugin()->getConfig()->get("prefix") . str_replace(["{target}", "{time}", "{staffer}"], [$player->getName(), $expire, $staffer], $this->getPlugin()->getConfig()->get("tempban-message-without-reason")), false);
            }
            foreach($this->getPlugin()->getServer()->getOnlinePlayers() as $staffer){
                if($staffer->hasPermission("servermoderator.tempban")){
                    $staffer->sendMessage($this->getPlugin()->getConfig()->get("prefix") . $this->getPlugin()->getConfig()->get("prefix") . "Staffer: " . $staffer . " banned " . $player->getName() . "until at " . $expire . (trim($reason) !== "" ? " Reason: " . TextFormat::RESET . $reason : ""));
                }
            }
        }
    }
}
