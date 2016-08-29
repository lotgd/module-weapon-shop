<?php
declare(strict_types=1);

namespace LotGD\Modules\WeaponShop;

use LotGD\Core\Action;
use LotGD\Core\Game;
use LotGD\Core\Module as ModuleInterface;
use LotGD\Core\Models\Module as ModuleModel;
use LotGD\Core\Models\CharacterViewpoint;
use LotGD\Core\Models\Scene;

use LotGD\Modules\Forms\Form;
use LotGD\Modules\Forms\FormElement;
use LotGD\Modules\Forms\FormElementOptions;
use LotGD\Modules\Forms\FormElementType;

use LotGD\Modules\SimpleInventory\Module as SimpleInventory;
use LotGD\Modules\SimpleInventory\Models\Weapon;

class Module implements ModuleInterface {
    const Module = 'lotgd/module-weapon-shop';

    const WeaponShopScene = 'lotgd/module-weapon-shop/shop';
    const WeaponShopSceneArrayProperty = 'lotgd/module-weapon-shop/scenes';

    const ChoiceParameter = 'choice';

    const TradeInHook = 'h/lotgd/module-weapon-shop/trade-in';

    public static function tradeInValue($item): int
    {
        return $item ? (int)round(($item->getCost() * .75), 0) : 0;
    }

    public static function handleEvent(Game $g, string $event, array &$context)
    {
        switch ($event) {
            case 'e/lotgd/core/navigate-to/lotgd/module-weapon-shop/shop':
                if (isset($context[self::ChoiceParameter])) {
                    BuySubScene::handleNavigation($g, $context);
                } else {
                    ShopSubScene::handleNavigation($g, $context);
                }
                break;
        }
    }

    private static function getBaseScene(): Scene
    {
        return Scene::create([
            'template' => self::WeaponShopScene,
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

    private static function storeSceneId(ModuleModel $module, string $id)
    {
        $scenes = $module->getProperty(self::WeaponShopSceneArrayProperty);
        if ($scenes === null) {
            $scenes = [];
        }
        $scenes[] = $id;
        $module->setProperty(self::WeaponShopSceneArrayProperty, $scenes);
    }

    public static function onRegister(Game $g, ModuleModel $module)
    {
        // Add a shop scene as a child to every village-like scene.

        // Find the village-like scenes.
        $villages = $g->getEntityManager()->getRepository(Scene::class)->findBy([ 'template' => 'lotgd/module-village/village' ]);
        if ($villages === null || count($villages) == 0) {
            $g->getLogger()->addNotice(sprintf("%s: Couldn't find any villages to add the weapon shop to", self::Module));
        } else {
            foreach ($villages as $v) {
                $g->getLogger()->addNotice(sprintf("%s: Adding a weapon shop to scene id=%i", self::Module, $v->getId()));
                $shop = self::getBaseScene();

                $v->addChild($shop);
                $shop->setParent($v);
                $shop->save($g->getEntityManager());

                // Keep a list of these shop scenes we've added, so we can remove them
                // on unregistration.
                self::storeSceneId($module, $shop->getId());
            }
        }
    }

    public static function onUnregister(Game $g, ModuleModel $module)
    {
        $scenes = $module->getProperty(self::WeaponShopSceneArrayProperty);
        if ($scenes && count($scenes) > 0) {
            foreach ($scenes as $id) {
                $s = $g->getEntityManager()->getRepository(Scene::class)->find($id);
                $g->getEntityManager()->remove($s);
            }
        }

        $module->setProperty(self::WeaponShopSceneArrayProperty, null);
    }
}
