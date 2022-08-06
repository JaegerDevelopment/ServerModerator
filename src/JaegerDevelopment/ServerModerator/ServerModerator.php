<?php

namespace JaegerDevelopment\ServerModerator;

use JaegerDevelopment\ServerModerator\command\TempbanCommand;
use JaegerDevelopment\ServerModerator\lang\LangManager;
use JaegerDevelopment\ServerModerator\provider\tempban\SQLTempbanProvider;
use JaegerDevelopment\ServerModerator\provider\tempban\TempbanProvider;
use JaegerDevelopment\ServerModerator\utils\TbanManager;
use pocketmine\plugin\PluginBase;

class ServerModerator extends PluginBase {

    private array $commands = [
        "kick",
        "tempban",
        "screenshare",
        "unban",
        "report",
        "mute",
        "unmute"
    ];

    private ?TempbanProvider $tbanDatabase;
    public TbanManager $tbanManager;

    public LangManager $langManager;

    public function onEnable(): void{
        $this->unregisterCommands();
        $this->registerCommands();
        $this->initDatabases();
        $this->langManager = new LangManager($this);
    }

    public function registerCommands() : void{
        $this->getServer()->getCommandMap()->registerAll("ServerModerator", [
            new TempbanCommand($this),
            //new KickCommand(),
            //new ScreenShareCommand(),
            //new UnbanCommand(),
            //new ReportCommand(),
            //new MuteCommand(),
            //new UnmuteCommand()
        ]);
    }

    public function unregisterCommands() : void{
        foreach($this->commands as $command){
            $this->getServer()->getCommandMap()->unregister($this->getCommand($command));
        }
    }

    public function initDatabases(){

        #TODO make files sql
        $tbanDB = $this->getDataFolder() . "tempban/";
        if(!is_dir($tbanDB)){
            mkdir($tbanDB);
        }

        $reportDB = $this->getDataFolder() . "report/";
        if(!is_dir($reportDB)){
            mkdir($reportDB);
        }

        $muteDB = $this->getDataFolder() . "mute/";
        if(!is_dir($muteDB)){
            mkdir($muteDB);
        }

        $this->saveResource("tempban/mysql.sql", true);
        $this->saveResource("tempban/sqlite.sql", true);
        $this->saveResource("report/mysql.sql", true);
        $this->saveResource("report/sqlite.sql", true);
        $this->saveResource("mute/mysql.sql", true);
        $this->saveResource("mute/sqlite.sql", true);

        $this->loadTempbanDatabase();
        #TODO $this->loadReportDatabase();
        #TODO $this->loadMuteDatabase();
    }

    private function loadTempbanDatabase() : void{
        $this->tbanManager = new TbanManager($this);
        switch(strtolower($this->getConfig()->getNested("tban-database.type"))){
            default:
            case "sqlite3":
            case "sqlite":
            case "mysql":
                $this->tbanDatabase = new SQLTempbanProvider($this);
                break;
        }
    }

    public function getTempbanProvider(): TempbanProvider {
        if($this->tbanDatabase === null) {
            throw new \RuntimeException("Tempban provider should never be null");
        }
        return $this->tbanDatabase;
    }

    public function getLangManager() : LangManager{
        return $this->langManager;
    }

    public function getTbanManager() : TbanManager{
        return $this->tbanManager;
    }
}