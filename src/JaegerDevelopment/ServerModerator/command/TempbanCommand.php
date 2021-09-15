<?php

namespace JaegerDevelopment\ServerModerator\command;

use DateTime;
use JaegerDevelopment\ServerModerator\ServerModerator;
use jojoe77777\FormAPI\CustomForm;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\command\ConsoleCommandSender;
use pocketmine\command\PluginIdentifiableCommand;
use pocketmine\command\utils\CommandException;
use pocketmine\command\utils\InvalidCommandSyntaxException;
use pocketmine\Player;
use pocketmine\plugin\Plugin;
use pocketmine\utils\TextFormat;

class TempbanCommand extends Command implements PluginIdentifiableCommand{

    public $playerList = [];

    private ServerModerator $plugin;

    public function __construct(ServerModerator $plugin){
        $this->plugin = $plugin;
        parent::__construct("tempban", $this->plugin->getConfig()->get("tempban-command-description"), "/tempban <player> <time> <reason>", ["tban"]);
    }

    /**
     * @param string[] $args
     *
     * @return mixed
     * @throws CommandException
     */
    public function execute(CommandSender $sender, string $commandLabel, array $args){
        if(!($sender->hasPermission("servermoderator.tempban"))){
            $sender->sendMessage($this->getPlugin()->getConfig()->get("prefix") . " " . $this->getPlugin()->getConfig()->get("not-permission-message"));
            return;
        }

        if($sender instanceof ConsoleCommandSender){
            $sender->sendMessage($this->getPlugin()->getConfig()->get("prefix") . " " . $this->getPlugin()->getConfig()->get("command-not-execute-on-console"));
            return;
        }

        if($this->getPlugin()->getConfig()->get("type") == "cmd" and (count($args) < 2)){
            $sender->sendMessage($this->getUsage());
            return;
        }

        switch($this->getPlugin()->getConfig()->get("type")){
            case "cmd":
                if($sender instanceof Player){
                    if(isset($args[0])){
                        $target = $this->plugin->getServer()->getPlayer($args[0]);
                        if(isset($args[1])){
                            if(isset($args[2])){
                                $this->TempbanCMD($sender, $target, $args[1], $args[2]);
                            }
                            $this->TempbanCMD($sender, $target, $args[1]);
                        } else {
                            $sender->sendMessage($this->getPlugin()->getConfig()->get("time-missing"));
                        }
                    } else {
                        throw new InvalidCommandSyntaxException();
                    }

                }
            break;

            case "ui":
            default:
                if($sender instanceof Player){
                    $this->TempbanUI($sender);
                }
            break;
        }
    }

    public function TempbanUI(Player $player) : void {
        $form = new CustomForm(function(Player $player, ?array $data) : void{
            if($data === null){
                return;
            }
            if($data[1] === null){
                $player->sendMessage($this->getPlugin()->getConfig()->get("prefix")  . " " . $this->getPlugin()->getConfig()->get("add-player-error"));
                return;
            }

            $index = $data[1];
            $targetName = $this->playerList[$player->getName()][$index];
            $target = $this->getPlugin()->getServer()->getPlayer($targetName);
            $timeClause = $data[2];
            $this->tempban($target, empty($data[3]) ? "" : $data[3], $timeClause, $player);
        });
        $list = [];
        foreach($this->plugin->getServer()->getOnlinePlayers() as $players){
            $list[] = $players->getName();
        }
        $this->playerList[$player->getName()] = $list;
        $form->setTitle($this->getPlugin()->getConfig()->get("tempban-ui-title"));
        $form->addLabel($this->getPlugin()->getConfig()->get("tempban-ui-content"));
        $form->addDropdown("Players:", $this->playerList[$player->getName()]);
        $form->addInput("Time:", "...");
        $form->addInput("Reason:", "...");
        $player->sendForm($form);
    }

    public function TempbanCMD($player, Player $target, string $time, string $reason = null){
        if(!($player->hasPermission("servermoderator.tempban"))){
            $player->sendMessage($this->getPlugin()->getConfig()->get("not-permission-message"));
            return;
        }
        $this->tempban($target, empty($reason) ? "" : $reason, $time, $player);
    }

    public function tempban(Player $target, string $reason, string $time, Player $staffer) : bool {
        if(!($staffer->hasPermission("servermoderator.tempban"))){
            $staffer->sendMessage($this->getPlugin()->getConfig()->get("not-permission-message"));
            return false;
        }

        if(!($info = $this->stringToTimestamp($time))){
            $staffer->sendMessage($this->getPlugin()->getConfig()->get("not-valid-time"));
            return false;
        }

        /** @var \DateTime $timeplayer */
        $timeplayer = $info[0];

        $this->getPlugin()->getServer()->getNameBans()->addBan($target->getName(), $reason, $timeplayer, $staffer->getName());

        if(($player = $staffer->getServer()->getPlayerExact($target->getName())) instanceof Player){
            $message = str_replace(["{target}", "{date}", "{time}", "{reason}", "{staffer}"], [$target->getName(), $timeplayer->format("l, F j, Y"), $timeplayer->format("h:ia"), trim($reason) !== "" ? " Reason: " . $reason : "", $staffer->getName()], $this->getPlugin()->getConfig()->get("tempban-message"));
            $player->kick($this->getPlugin()->getConfig()->get("prefix") . " " . $message, false);
        }

        if($this->getPlugin()->getConfig()->get("tempban-broadcastmessage-mode") === "on"){
            $message = str_replace(["{target}", "{date}", "{time}", "{reason}", "{staffer}"], [$target->getName(), $timeplayer->format("l, F j, Y"), $timeplayer->format("h:ia"), trim($reason) !== "" ? " Reason: " . $reason : "", $staffer->getName()], $this->getPlugin()->getConfig()->get("tempban-broadcastmessage"));
            $this->plugin->getServer()->broadcastMessage($this->getPlugin()->getConfig()->get("prefix") . " " . $message);
        }

        foreach($this->getPlugin()->getServer()->getOnlinePlayers() as $staffers){
            if($staffers->hasPermission("Servermoderator.tempban")){
                $message = str_replace(["{target}", "{date}", "{time}", "{reason}", "{staffer}"], [$target->getName(), $timeplayer->format("l, F j, Y"), $timeplayer->format("h:ia"), trim($reason) !== "" ? " Reason: " . $reason : "", $staffer->getName()], $this->getPlugin()->getConfig()->get("tempban-message-for-staffers"));
                $staffers->sendMessage($this->getPlugin()->getConfig()->get("prefix") . " " . $message);
            }
        }
        return false;
    }

    public static function stringToTimestamp(string $string): ?array{
        /**
         * Rules:
         * Integers without suffix are considered as seconds
         * "s" is for seconds
         * "m" is for minutes
         * "h" is for hours
         * "d" is for days
         * "w" is for weeks
         * "mo" is for months
         * "y" is for years
         */
        if(trim($string) === ""){
            return null;
        }
        $t = new \DateTime();
        preg_match_all("/[0-9]+(y|mo|w|d|h|m|s)|[0-9]+/", $string, $found);
        if(count($found[0]) < 1){
            return null;
        }
        $found[2] = preg_replace("/[^0-9]/", "", $found[0]);
        foreach($found[2] as $k => $i){
            switch($c = $found[1][$k]){
                case "y":
                case "w":
                case "d":
                    $t->add(new \DateInterval("P" . $i. strtoupper($c)));
                    break;
                case "mo":
                    $t->add(new \DateInterval("P" . $i. strtoupper(substr($c, 0, strlen($c) -1))));
                    break;
                case "h":
                case "m":
                case "s":
                    $t->add(new \DateInterval("PT" . $i . strtoupper($c)));
                    break;
                default:
                    $t->add(new \DateInterval("PT" . $i . "S"));
                    break;
            }
            $string = str_replace($found[0][$k], "", $string);
        }
        return [$t, ltrim(str_replace($found[0], "", $string))];
    }

    public static function expirationTimerToString(DateTime $from, DateTime $to) : string {
        $string = "";
        $second = intval($from->format("s")) - intval($to->format("s"));
        $minute = intval($from->format("i")) - intval($to->format("i"));
        $hour = intval($from->format("H")) - intval($to->format("H"));
        $day = intval($from->format("d")) - intval($to->format("d"));
        $month = intval($from->format("n")) - intval($to->format("n"));
        $year = intval($from->format("Y")) - intval($to->format("Y"));
        if ($second <= -1) {
            $second = 60 + $second;
            $minute--;
        }
        if ($minute <= -1) {
            $minute = 60 + $minute;
            $hour--;
        }
        if ($hour <= -1) {
            $hour = 24 + $hour;
            $day--;
        }
        if ($day <= -1) {
            $day = 30 + $day;
            $month--;
        }
        if ($month <= -1) {
            $month = 12 + $month;
            $year--;
        }
        $string .= $year >= 1 ? strval($year) . " " . ($year >= 2 ? "years " : "year ") : "";
        $string .= $month >= 1 ? strval($month) . " " . ($month >= 2 ? "months " : "month ") : "";
        $string .= $day >= 1 ? strval($day) . " " . ($day >= 2 ? "days " : "days") : "";
        $string .= $hour >= 1 ? strval($hour) . " " . ($hour >= 2 ? "hours " : "hour ") : "";
        $string .= $minute >= 1 ? strval($minute) . " " . ($minute >= 2 ? "minutes " : "minute ") : "";
        $string .= $second >= 1 ? strval($second) . " " . ($second >= 2 ? "seconds " : "second ") : "";
        $string = substr($string, 0, strlen($string) - 1);
        return $string;
    }


    public function getPlugin(): Plugin{
        return $this->plugin;
    }
}