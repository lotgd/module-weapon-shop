<?php
declare(strict_types=1);

namespace LotGD\Modules\WeaponShop;

use LotGD\Core\Action;
use LotGD\Core\ActionGroup;
use LotGD\Core\Game;
use LotGD\Core\Models\Scene;
use LotGD\Core\Models\CharacterViewpoint;
use LotGD\Modules\Forms\ {
    Form,
    FormElement,
    FormElementOptions,
    FormElementType
};
use LotGD\Modules\SimpleInventory\Module as SimpleInventory;
use LotGD\Modules\SimpleWealth\Module as SimpleWealth;

class ShopScene
{
    public static function getScene()
    {
        return Scene::create([
            'template' => Module::WeaponShopScene,
            'title' => 'MightyE\'s Weapons',
            'description' => "`!MightyE `7stands behind a counter and appears to pay little "
                           . "attention to you as you enter, but you know from experience that "
                           . "he has his eye on every move you make.\n"
                           . "`!MightyE`7 finally nods to you, stroking his goatee and looking "
                           . "like he wished he could have an opportunity to use one of his weapons.\n"
                           . "`7You stroll up the counter and try your best to look like you know "
                           . "what most of these contraptions do.",

        ]);
    }

    private static function getTradeInValue(Game $g): int
    {
        $inventory = new SimpleInventory($g);
        $weapon = $inventory->getWeaponForUser($g->getCharacter());

        // Get the trade-in value for their existing weapon.
        if (!$weapon) {
            $u_id = $user->getId();
            $g->getLogger()->error("Couldn't find a weapon for user {$u_id}.");
            return 0;
        } else if ($weapon) {
            $context = [
                'value' => Module::tradeInValue($weapon),
                'weapon' => $weapon
            ];
            $g->getEventManager()->publish(Module::TradeInHook, $context);

            return $context['value'];
        }
    }

    private static function addTradeInMessage(Game $g, Scene $scene, CharacterViewpoint $viewpoint)
    {
        $value = self::getTradeInValue($g);

        if ($value > 0) {
            $inventory = new SimpleInventory($g);
            $weapon = $inventory->getWeaponForUser($g->getCharacter());
            $name = $weapon->getName();
            $description = $scene->getDescription();
            $description .= "\n`!MightyE`7 looks at you and says, \"`#I'll give you `^{$value}`# trade-in value for your `5{$name}`#.";
            $viewpoint->setDescription($description);
        }
    }

    private static function getBuyAction(Game $g, Scene $scene): Action
    {
        // Find the child w/ the right template.
        foreach ($scene->getChildren() as $child) {
            if ($child->getTemplate() === Module::WeaponShopBuyScene) {
                return new Action($child->getId());
            }
        }
        $id = $scene->getId();
        throw new Exception("Can't find a buy scene that's a child of scene id={$id}");
    }

    private static function addForSaleForm(Game $g, Scene $scene, CharacterViewpoint $viewpoint, int $tradeInValue)
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
            $options = ($w->getCost() + $tradeInValue <= $gold)
                ? FormElementOptions::None()
                : FormElementOptions::Disabled();

            $elements[] = new FormElement(Module::ChoiceParameter,
                                          FormElementType::Button(),
                                          "{$name}",
                                          $id,
                                          $options);
        }

        $form = new Form($elements, self::getBuyAction($g, $scene));

        $attachments = $viewpoint->getAttachments();
        $attachments[] = $form;
        $viewpoint->setAttachments($attachments);
    }

    private static function addMenu(Game $g, Scene $scene, CharacterViewpoint $viewpoint)
    {
        $actionGroups = $viewpoint->getActionGroups();
        foreach ($actionGroups as $group) {
            if ($group->getId() === ActionGroup::DefaultGroup) {
                $actions = $group->getActions();
                $actions[] = new Action($scene->getParent()->getId());
                $group->setActions($actions);
                break;
            }
        }
        $viewpoint->setActionGroups($actionGroups);
    }

    public static function handleViewpoint(Game $g, array $context)
    {
        // Prepare the weapon shop viewpoint with the current trade
        // in value, if any, and the list of weapons for the current
        // user's level.

        $scene = $context['scene'];
        $viewpoint = $context['viewpoint'];

        self::addTradeInMessage($g, $scene, $viewpoint);
        self::addForSaleForm($g, $scene, $viewpoint, self::getTradeInValue($g));
        self::addMenu($g, $scene, $viewpoint);

        $viewpoint->save($g->getEntityManager());
    }
}
