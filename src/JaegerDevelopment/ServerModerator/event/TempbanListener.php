<?php

namespace JaegerDevelopment\ServerModerator\event;

use JaegerDevelopment\ServerModerator\ServerModerator;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerPreLoginEvent;
use pocketmine\utils\TextFormat;

class TempbanListener implements Listener {

    protected ServerModerator $plugin;

    public function __construct(ServerModerator $plugin){
        $this->plugin = $plugin;
    }

    public function onPreLogin(PlayerPreLoginEvent $event) : void{
        $player = $event->getPlayerInfo()->getUsername();
        if($this->getPlugin()->getTbanManager()->isBanned(strtolower($player))){
           $event->setKickReason(
               PlayerPreLoginEvent::KICK_REASON_BANNED, $this->getPlugin()->getLangManager()->getTranslationMessage("ban-message", [
                   $this->getPlugin()->getLangManager()->getLangFile()->get("server-prefix"),
                   TextFormat::EOL,
                   $this->getPlugin()->getTbanManager()->getRemainingTimeMsg($this->getPlugin()->getTbanManager()->getTime($player)),
                   $this->getPlugin()->getTbanManager()->getReason($player),
                   $this->getPlugin()->getTbanManager()->getStaffer($player)
               ]
           ));
        }
    }

    public function getPlugin(): ServerModerator{
        return $this->plugin;
    }

}