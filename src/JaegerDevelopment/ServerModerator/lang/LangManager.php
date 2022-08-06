<?php

namespace JaegerDevelopment\ServerModerator\lang;

use JaegerDevelopment\ServerModerator\ServerModerator;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat as TF;

class LangManager{

    public const PREFIX = TF::GRAY.'['.TF::MINECOIN_GOLD.'ServerModerator'.TF::GRAY.'] '.TF::WHITE;

    protected ServerModerator $plugin;
    private Config $langFile;

    public function __construct(ServerModerator $plugin){
        $this->plugin = $plugin;
        $this->langFile = new Config($this->getPlugin()->getDataFolder() . "lang.proprieties", Config::PROPERTIES, [
            "server-prefix" => "ServerModerator",
            "execute-in-game" => self::PREFIX . "You can only execute this command in-game",
            "usage-command" => self::PREFIX . "You need to run the command only with: {#0}",
            "no-perm" => self::PREFIX . "You haven\'t the permission to use this command {#0}",
            "player-not-exist" => self::PREFIX . "The player {#0} does not exist on this server",
            "player-banned" => self::PREFIX . "The player {#0} has been banned",
            "player-is-already-banned" => self::PREFIX . "The player {#0} is already banned",
            "ban-message" => "{#0} {#1} Time: {#2} {#1} Reason: {#3} {#1} Staffer: {#4}"
        ]);
    }

    public function getTranslationMessage(string $messageKey, ...$args) : string{
        if(!isset($this->getLangFile()[$messageKey])){
            throw new \RuntimeException("Invalid MessageKey " . $messageKey);
        }
        $message = $this->getLangFile()->get($messageKey, $this->getLangFile()[$messageKey]);
        foreach($args as $key => $arg){
            $message = str_replace('{#'.$key.'}', $arg, $message);
        }
        return str_replace('&', TF::ESCAPE, $message);
    }

    public function getLangFile() : Config{
        return $this->langFile;
    }

    public function getPlugin() : ServerModerator{
        return $this->plugin;
    }
}