<?php

declare(strict_types=1);

namespace DaPigGuy\PiggyFactions\commands\subcommands;

use CortexPE\Commando\args\TextArgument;
use DaPigGuy\PiggyFactions\claims\ClaimsManager;
use DaPigGuy\PiggyFactions\factions\Faction;
use DaPigGuy\PiggyFactions\factions\FactionsManager;
use DaPigGuy\PiggyFactions\language\LanguageManager;
use DaPigGuy\PiggyFactions\players\PlayerManager;
use DaPigGuy\PiggyFactions\utils\FormattedTime;
use DaPigGuy\PiggyFactions\utils\Roles;
use pocketmine\command\CommandSender;
use pocketmine\Player;

class InfoSubCommand extends FactionSubCommand
{
    /** @var bool */
    protected $requiresPlayer = false;

    public function onBasicRun(CommandSender $sender, array $args): void
    {
        $faction = $sender instanceof Player ? PlayerManager::getInstance()->getPlayerFaction($sender->getUniqueId()) : null;
        if (isset($args["faction"])) {
            $faction = FactionsManager::getInstance()->getFactionByName($args["faction"]);
            if ($faction === null) {
                LanguageManager::getInstance()->sendMessage($sender, "commands.invalid-faction", ["{FACTION}" => $args["faction"]]);
                return;
            }
        }
        if ($faction === null) {
            $this->sendUsage();
            return;
        }

        $memberNamesWithRole = [];
        $memberNamesByRole = [];
        foreach ($faction->getMembers() as $m) {
            $memberNamesByRole[$m->getRole()][] = $m->getUsername();
            $memberNamesWithRole[] = $this->plugin->getTagManager()->getPlayerRankSymbol($m) . $m->getUsername();
        }

        LanguageManager::getInstance()->sendMessage($sender, "commands.info.message", [
            "{FACTION}" => $faction->getName(),
            "{DESCRIPTION}" => $faction->getDescription() ?? LanguageManager::getInstance()->getMessage($sender instanceof Player ? PlayerManager::getInstance()->getPlayer($sender->getUniqueId())->getLanguage() : LanguageManager::getInstance()->getDefaultLanguage(), "commands.info.description-not-set"),
            "{CLAIMS}" => count(ClaimsManager::getInstance()->getFactionClaims($faction)),
            "{POWER}" => round($faction->getPower(), 2, PHP_ROUND_HALF_DOWN),
            "{TOTALPOWER}" => $faction->getMaxPower(),
            "{CREATIONDATE}" => date("F j, Y @ g:i a T", $faction->getCreationTime()),
            "{AGE}" => FormattedTime::getLong($faction->getCreationTime()),
            "{SIMPLEAGE}" => FormattedTime::getShort($faction->getCreationTime()),
            "{MONEY}" => round($faction->getMoney(), 2, PHP_ROUND_HALF_DOWN),
            "{LEADER}" => ($leader = $faction->getLeader()) === null ? "" : $leader->getUsername(),
            "{ALLIES}" => implode(", ", array_map(function (Faction $f): string {
                return $f->getName();
            }, $faction->getAllies())),
            "{ENEMIES}" => implode(", ", array_map(function (Faction $f): string {
                return $f->getName();
            }, $faction->getEnemies())),
            "{RELATIONS}" => implode(", ", array_map(function (Faction $f) use ($faction): string {
                $color = ["enemy" => LanguageManager::getInstance()->translateColorTags($this->plugin->getConfig()->getNested("symbols.colors.relations.enemy")), "ally" => LanguageManager::getInstance()->translateColorTags($this->plugin->getConfig()->getNested("symbols.colors.relations.ally"))];
                return $color[$faction->getRelation($f)] . $f->getName();
            }, array_merge($faction->getAllies(), $faction->getEnemies()))),
            "{OFFICERS}" => implode(", ", $memberNamesByRole[Roles::OFFICER] ?? []),
            "{MEMBERS}" => implode(", ", $memberNamesByRole[Roles::MEMBER] ?? []),
            "{RECRUITS}" => implode(", ", $memberNamesByRole[Roles::RECRUIT] ?? []),
            "{PLAYERS}" => implode(", ", $memberNamesWithRole),
            "{TOTALPLAYERS}" => count($faction->getMembers()),
            "{ONLINECOUNT}" => count($faction->getOnlineMembers())
        ]);
    }

    protected function prepare(): void
    {
        $this->registerArgument(0, new TextArgument("faction", true));
    }
}