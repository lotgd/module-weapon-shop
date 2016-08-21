<?php
declare(strict_types=1);

namespace LotGD\Modules\WeaponShop;

use LotGD\Core\Action;
use LotGD\Core\Game;
use LotGD\Core\Module as ModuleInterface;
use LotGD\Core\Models\Module as ModuleModel;
use LotGD\Core\Models\CharacterViewpoint;

use LotGD\Modules\Forms\Form;
use LotGD\Modules\Forms\FormElement;
use LotGD\Modules\Forms\FormElementOptions;
use LotGD\Modules\Forms\FormElementType;

use LotGD\Modules\SimpleInventory\Module as SimpleInventory;
use LotGD\Modules\SimpleInventory\Models\Weapon;

class Module implements ModuleInterface {
    const Module = 'lotgd/module-weapon-shop';

    const WeaponShopScene = 'lotgd/module-weapon-shop/shop';
    const WeaponShopSceneIdProperty = 'WeaponShopSceneId';

    const BuyScene = 'lotgd/module-weapon-shop/buy';
    const BuySceneIdProperty = 'BuySceneId';

    const ChoiceParameter = 'choice';

    const TradeInHook = 'h/lotgd/module-weapon-shop/trade-in';

    public static function tradeInValue($item): int
    {
        return $item ? (int)round(($item->getCost() * .75), 0) : 0;
    }

    public static function getBuyAction(Game $g): Action
    {
        $m = $g->getModuleManager()->getModule(self::Module);
        $destinationSceneId = $m->getProperty(self::BuySceneIdProperty);
        return new Action($destinationSceneId);
    }

    public static function handleEvent(Game $g, string $event, array $context)
    {
        switch ($event) {
            case 'e/lotgd/core/navigate-to/lotgd/module-weapon-shop/shop':
                ShopScene::handleNavigation($g, $context);
                break;
            case 'e/lotgd/core/navigate-to/lotgd/module-weapon-shop/buy':
                BuyScene::handleNavigation($g, $context);
                break;
        }
    }

    private static function saveScene(Game $g, ModuleModel $module, Scene $s, string $property)
    {
        $s->save($g->getEntityManager());
        $module->setProperty($property, $s->getId());
    }

    private static function removeSceneAndProperty(Game $g, ModuleModel $module, string $property)
    {
        $id = $module->getProperty($property);
        $s = $g->getEntityManager()->getRepository(Scene::class)->find($id);
        $g->getEntityManager()->remove($s);

        $modele->setProperty($property, null);
    }

    public static function onRegister(Game $g, ModuleModel $module)
    {
        self::saveScene($g, $module, ShopScene::getScene(), self::WeaponShopSceneIdProperty);
        self::saveScene($g, $module, BuyScene::getScene(), self::BuySceneIdProperty);
    }

    public static function onUnregister(Game $g, ModuleModel $module)
    {
        self::removeSceneAndProperty($g, $module, self::WeaponShopSceneIdProperty);
        self::removeSceneAndProperty($g, $module, self::BuySceneIdProperty);
    }
}
