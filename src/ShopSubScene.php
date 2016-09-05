<?php
declare(static_types=1);

namespace LotGD\Modules\WeaponShop;

use LotGD\Core\Game;
use LotGD\Core\Action;
use LotGD\Core\Models\Character;
use LotGD\Core\Models\CharacterViewpoint;
use LotGD\Core\Models\Scene;
use LotGD\Modules\SimpleInventory\Module as SimpleInventory;
use LotGD\Modules\SimpleWealth\Module as SimpleWealth;
use LotGD\Modules\Forms\Form;

class ShopSubScene
{
    private static function addTradeInMessage(Game $g, Scene $scene, CharacterViewpoint $viewpoint)
    {
        $description = $scene->getDescription();
        $user = $viewpoint->getOwner();

        $inventory = new SimpleInventory($g);
        $weapon = $inventory->getWeaponForUser($user);

        // Get the trade-in value for their existing weapon.
        if (!$weapon) {
            $u_id = $user->getId();
            $g->getLogger()->error("Couldn't find a weapon for user {$u_id}.");
        } else if ($w) {
            $context = [
                'value' => self::tradeInValue($w),
                'weapon' => $weapon
            ];
            $g->getEventManager()->publish(self::TradeInHook, $context);

            $value = $context['value'];
            $name = $weapon->getName();

            $description .= "\n`!MightyE`7 looks at you and says, \"`#I'll give you `^{$value}`# trade-in value for your `5{$name}`#.";
            $viewpoint->setDescription($d);
        }
    }

    private static function addForSaleForm(Game $g, Scene $scene, CharacterViewpoint $viewpoint, int $tradeInValue = 0)
    {
        $user = $viewpoint->getOwner();

        $wealth = new SimpleWealth($g);
        $gold = $wealth->getGoldForUser($user);

        $inventory = new SimpleInventory($g);
        $weapons = $inventory->getWeaponsForLevel($user->getLevel());

        $elements = [];
        foreach ($weapons as $w) {
            $id = $w->getId();
            $name = $w->getName();
            $options = ($weapon->cost + $tradeInValue <= $gold)
                ? FormElementOptions::None()
                : FormElementOptions::Disabled();

            $elements[] = new FormElement(
                Module::ChoiceParameter,
                FormElementType::Button(),
                "{$name}",
                $id,
                $options
            );
        }

        $form = new Form($elements, self::getBuyAction($g));
    }

    private static function addMenu(Game $g, Scene $scene, CharacterViewpoint $viewpoint)
    {
        $actionGroups = $viewpoint->getActions();
        foreach ($actionGroups as $group) {
            if ($group->getId() === ActionGroup::DefaultGroup) {
                $actions = $group->getActions();
                $actions[] = new Action($scene->getParent()->getId());
                $group->setActions($actions);
            }
        }
        $viewpoint->setActions($actionGroups);
    }

    public static function handleNavigation(Game $g, array $context)
    {
        // Prepare the weapon shop viewpoint with the current trade
        // in value, if any, and the list of weapons for the current
        // user's level.

        $scene = $context['scene'];
        $viewpoint = $context['viewpoint'];

        self::addTradeInMessage($g, $scene, $viewpoint);
        self::addForSaleForm($g, $scene, $viewpoint);
        self::addMenu($g, $scene, $viewpoint);

        $viewpoint->save($g->getEntityManager());
    }
}
