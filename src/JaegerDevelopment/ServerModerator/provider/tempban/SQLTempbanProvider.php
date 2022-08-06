<?php

declare(strict_types=1);

namespace JaegerDevelopment\ServerModerator\provider\tempban;

use pocketmine\player\Player;
use poggit\libasynql\DataConnector;
use poggit\libasynql\libasynql;

class SQLTempbanProvider extends TempbanProvider{

    protected string $type;
    protected DataConnector $database;

    const INITIALIZE_TABLES = "servermoderator.tban.init";
    const REGISTER_PLAYER = "servermoderator.player.register";
    const REMOVE_PLAYER = "servermoderator.player.remove";
    const GET_TIME = "servermoderator.time.get";
    const GET_REASON = "servermoderator.reason.get";
    const GET_STAFFER = "servermoderator.staffer.get";

    protected function createTempbanTable() : void {
        $config = $this->getPlugin()->getConfig();
        $this->type = strtolower($config->getNested("tban-database.type"));
        $mc = $config->getAll();

        $libasynql_friendly_config = [
            "type" => $this->type,
            "sqlite" => [
                "file" => $this->getPlugin()->getDataFolder() . "tban.sqlite3"
            ],
            "mysql" => array_combine(
                ["host", "username", "password", "schema", "port"],
                [$mc["tban-database"]["mysql"]["host"], $mc["tban-database"]["mysql"]["username"], $mc["tban-database"]["mysql"]["password"], $mc["tban-database"]["mysql"]["schema"], $mc["tban-database"]["mysql"]["port"]]
            )
        ];

        $this->database = libasynql::create($this->getPlugin(), $libasynql_friendly_config, [
            "mysql" => "tempban/mysql.sql",
            "sqlite" => "tempban/sqlite.sql"
        ]);

        $this->database->executeGeneric(self::INITIALIZE_TABLES);

        $resource = $this->getPlugin()->getResource("patches/" . $this->type . ".sql");
        if($resource !== null) {
            $this->database->loadQueryFile($resource);//calls fclose($resource)
        }
    }

    public function registerPlayer(Player $player, int $time, ?string $reason, Player $staffer) : void{
        $this->database->executeChange(self::REGISTER_PLAYER, [
            "player" => strtolower($player->getName()),
            "time" => $time,
            "reason" => $reason ?? "",
            "staffer" => strtolower($staffer->getName())
        ]);
    }

    public function removePlayer(Player $player) : void {
        $this->database->executeChange(self::REMOVE_PLAYER, [
            "player" => strtolower($player->getName()),
        ]);
    }

    public function getTime(Player $player, callable $callable) : void {
        $this->database->executeSelect(self::GET_TIME, [
            "player" => strtolower($player->getName())
        ], $callable);
    }

    public function getReason(Player $player, callable $callable) : void {
        $this->database->executeSelect(self::GET_REASON, [
            "player" => strtolower($player->getName())
        ], $callable);
    }

    public function getStaffer(Player $player, callable $callable) : void {
        $this->database->executeSelect(self::GET_STAFFER, [
            "player" => strtolower($player->getName())
        ], $callable);
    }

    public function getDatabase() : DataConnector{
        return $this->database;
    }

}