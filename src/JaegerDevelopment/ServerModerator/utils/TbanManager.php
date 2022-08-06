<?php

namespace JaegerDevelopment\ServerModerator\utils;

use JaegerDevelopment\ServerModerator\ServerModerator;
use pocketmine\player\Player;

class TbanManager {

    protected ServerModerator $plugin;

    private array $playersBanned;

    public function __construct(ServerModerator $plugin){
        $this->plugin = $plugin;
        $this->getBannedPlayers();
    }

    public function getTime(string|Player $player) : ?int{
        if(is_string($player)){
            $player = $this->getPlugin()->getServer()->getPlayerExact($player);
        }
        $this->getPlugin()->getTempbanProvider()->getTime(
            $player,
            function(array $rows) use($player): ?\DateInterval {
                $time = 0;
                foreach($rows as ["player" => $player, "time" => $timeBan, "reason" => $reason, "staffer" => $staffer]){
                    $time .= $timeBan;
                }
                $dateBan = date_create();
                $dateBan->setTimestamp($time);
                $dateNow = date_create();
                return date_diff($dateNow, $dateBan);
            }
        );
    }

    public function getRemainingTimeMsg(string|int|\DateInterval $remainingTime) : ?string{
        return ($remainingTime->invert ? "Os"
                : ($remainingTime->days ? $remainingTime->days . "d " : "")
                . ($remainingTime->h ? $remainingTime->h . "h " : "")
                . ($remainingTime->i ? $remainingTime->i . "m " : "")
                . $remainingTime->s . "s"
        );
    }

    public function getBannedPlayers() : void{
        $this->getPlugin()->getTempbanProvider()->getDatabase()->executeSelect(
            "servermoderator.playerbanned.get",
            [],
            function(array $rows) : void{
                $this->playersBanned = [];
                foreach($rows as $row){
                    $this->playersBanned[$row["player"]] = true;
                }
            }
        );
    }

    public function isBanned(string|Player $player) : bool{
        if(is_string($player)){
            $player = $this->getPlugin()->getServer()->getPlayerExact($player);
        }

        if($this->playersBanned[strtolower($player->getName())]){
            return true;
        } else {
            return false;
        }
    }

    public function getReason(string|Player $player) : string{
        if(is_string($player)){
            $player = $this->getPlugin()->getServer()->getPlayerExact($player);
        }
        $this->getPlugin()->getTempbanProvider()->getReason(
            $player,
            function(array $rows) use($player) {
                $reason = "";
                foreach($rows as ["player" => $player, "time" => $timeBan, "reason" => $reasonBan, "staffer" => $staffer]){
                    $reason .= $reasonBan ?? "";
                }
                return $reason;
            }
        );
    }

    public function getStaffer(string|Player $player) : string{
        if(is_string($player)){
            $player = $this->getPlugin()->getServer()->getPlayerExact($player);
        }
        $this->getPlugin()->getTempbanProvider()->getTime(
            $player,
            function(array $rows) use($player) {
                $staffer = "";
                foreach($rows as ["player" => $player, "time" => $timeBan, "reason" => $reasonBan, "staffer" => $stafferBan]){
                    $staffer .= $stafferBan ?? "";
                }
                return $staffer;
            }
        );
    }

    public function onPlayerUnban() : void{

    }

    public function getPlugin(): ServerModerator{
        return $this->plugin;
    }

}