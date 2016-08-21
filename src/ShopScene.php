<?php
declare(static_types=1);

namespace LotGD\Modules\WeaponShop;

use LotGD\Core\Scene;
use LotGD\Modules\SimpleInventory\Module as SimpleInventory;
use LotGD\Modules\SimpleWealth\Module as SimpleWealth;

class ShopScene
{
    public static function getScene(): Scene
    {
        return new Scene([
            'template' => Module::WeaponShopScene,
            'title' => 'MightyE\'s Weapons',
            'description' => "`!MightyE `7stands behind a counter and appears to pay little "
                           . "attention to you as you enter, but you know from experience that "
                           . "he has his eye on every move you make.\n"
                           . "`!MightyE`7 finally nods to you, stroking his goatee and looking "
                           . "like he wished he could have an opportunity to use one of his weapons.\n"
                           . "`7You stroll up the counter and try your best to look like you know "
                           ." what most of these contraptions do.",

        ]);
    }

    private static function addTradeInMessage(Game $g, Scene $scene, CharacterViewpoint $viewpoint)
    {
        $description = $scene->getDescription();

        $inventory = new SimpleInventory($g);
        $weapon = $inventory->getWeapon($user);

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

    private static function addForSaleForm(Game $g, Scene $scene, CharacterViewpoint $viewpoint, int $tradeInValue)
    {
        $user = $viewpoint->getOwner();

        $wealth = new SimpleWealth($g);
        $gold = $wealth->getGold($user);

        $inventory = new SimpleInventory($g);
        $weapons = $inventory->getWeaponsForLevel($user->getLevel());

        $elements = [];
        foreach ($weapons as $w) {
            $id = $w->getId();
            $name = $w->getName();
            $options = ($weapon->cost + $tradeInValue <= $gold)
                ? FormElementOptions::None()
                : FormElementOptions::Disabled();

            $elements[] = new FormElement(Module::ChoiceParameter,
                                          FormElementType::Button(),
                                          "{$name}",
                                          $id,
                                          $options));
        }

        $form = new Form($elements, self::getBuyAction($g);
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

        $viewpoint->save($g->getEntityManager());
    }
}
