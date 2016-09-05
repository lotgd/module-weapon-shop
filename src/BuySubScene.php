<?php
declare(static_types=1);

namespace LotGD\Modules\WeaponShop;

use LotGD\Core\Models\CharacterViewpoint;
use LotGD\Core\Models\Scene;
use LotGD\Modules\SimpleInventory\Module as SimpleInventory;

class BuySubScene
{
    private static function getChoiceWeapon(Game $g, array $parameters)
    {
        if (isset($parameters[Module::ChoiceParameter])) {
            $inventory = new SimpleInventory($g);
            $id = $parameters[Module::ChoiceParameter];
            return $inventory->getWeaponById($id);
        } else {
            return null;
        }
    }

    private static function addDescription(Game $g, Scene $scene, CharacterViewpoint $viewpoint, array $parameters)
    {
        $user = $viewpoint->getOwner();
        $description = $viewpoint->getDescription();

        $choiceWeapon = self::getChoiceWeapon($g, $parameters);

        $inventory = new SimpleInventory($g);
        $currentWeapon = $inventory->getWeaponForUser($user);
        $tradeInValue = Module::tradeInValue($currentWeapon);

        $wealth = new SimpleWealth($g);
        $gold = $wealth->getGoldForUser($user);

        if ($choiceWeapon === null) {
            $description .= "`!MightyE`7 looks at you, confused for a second, then realizes that you've apparently "
                ."taken one too many bonks on the head, and nods and smiles.";
        } else {
            $newWeaponName = $choiceWeapon->name;
            if ($gold + $tradeInValue < $choiceWeapon->cost) {
                $description .= "Waiting until `!MightyE`7 looks away, you reach carefully for the `5{$newWeaponName}`7, "
                    ."which you silently remove from the rack upon which it sits. Secure in your theft, you turn around "
                    ."and head for the door, swiftly, quietly, like a ninja, only to discover that upon reaching the door, "
                    ."the ominous `!MightyE`7 stands, blocking your exit. You execute a flying kick. Mid flight, you hear "
                    ."the \"SHING\" of a sword leaving its sheath.... your foot is gone. You land on your stump, and "
                    ."`!MightyE`7 stands in the doorway, claymore once again in its back holster, with no sign that it "
                    ."had been used, his arms folded menacingly across his burly chest.  \"`#Perhaps you'd like to "
                    ."pay for that?`7\" is all he has to say as you collapse at his feet, lifeblood staining the planks "
                    ."under your remaining foot.`n`nYou wake up some time later, having been tossed unconscious into "
                    ."the street.";
            } else {
                $wealth->setGoldForUser($user, $gold - ($w->cost - $tradeInValue));
                $inventory->setWeaponForUser($user, $choiceWeapon);
                $user->save($em);

                $description .= "`!MightyE`7 takes your `5{$currentWeaponName}`7 and promptly puts a price on it, "
                    . "setting it out for display with the rest of his weapons.`n`nIn return, he hands you a shiny "
                    . "new `5{$newWeaponName}`7 which you swoosh around the room, nearly taking off `!MightyE`7's "
                    . "head, which he deftly ducks; you're not the first person to exuberantly try out a new weapon.";
            }
        }
        $viewpoint->setDescription($description);
    }

    private static function addMenu(Game $g, Scene $scene, CharacterViewpoint $viewpoint)
    {
        $actionGroups = $viewpoint->getActions();
        foreach ($actionGroups as $g) {
            if ($g->getId() === ActionGroup::DefaultGroup) {
                $actions = $g->getActions();
                $actions[] = new Action($scene->getParent()->getId());
                $g->setActions($actions);
            }
        }
    }

    public static function handleNavigation(Game $g, array $context)
    {
        $em = $g->getEntityManager();

        $scene = $context['scene'];
        $viewpoint = $context['viewpoint'];
        $parameters = $context['parameters'];

        self::addDescription($g, $scene, $viewpoint, $parameters);
        self::addMenu($g, $scene, $viewpoint);

        $viewpoint->save($em);
    }
}
