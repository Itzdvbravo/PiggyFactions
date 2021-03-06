<?php

declare(strict_types=1);

namespace DaPigGuy\PiggyFactions\commands\subcommands\admin;

use CortexPE\Commando\args\FloatArgument;
use CortexPE\Commando\args\RawStringArgument;
use DaPigGuy\PiggyFactions\commands\subcommands\FactionSubCommand;
use DaPigGuy\PiggyFactions\event\member\PowerChangeEvent;
use DaPigGuy\PiggyFactions\language\LanguageManager;
use DaPigGuy\PiggyFactions\players\PlayerManager;
use pocketmine\command\CommandSender;

class AddPowerSubCommand extends FactionSubCommand
{
    /** @var bool */
    protected $requiresPlayer = false;

    public function onBasicRun(CommandSender $sender, array $args): void
    {
        $player = PlayerManager::getInstance()->getPlayerByName($args["player"]);
        if ($player === null) {
            LanguageManager::getInstance()->sendMessage($sender, "commands.invalid-player", ["{PLAYER}" => $args["player"]]);
            return;
        }
        $ev = new PowerChangeEvent($player, PowerChangeEvent::CAUSE_ADMIN, $player->getPower() + (float)$args["power"]);
        $ev->call();
        if ($ev->isCancelled()) return;

        $player->setPower($ev->getPower());
        LanguageManager::getInstance()->sendMessage($sender, "commands.addpower.success", ["{PLAYER}" => $player->getUsername(), "{POWER}" => $args["power"]]);
        $player->sendMessage("commands.addpower.power-add", ["{ADDEDPOWER}" => $args["power"], "{POWER}" => round($player->getPower(), 2, PHP_ROUND_HALF_DOWN)]);
    }

    protected function prepare(): void
    {
        $this->registerArgument(0, new RawStringArgument("player"));
        $this->registerArgument(1, new FloatArgument("power"));
    }
}