+<?php

namespace DenielWorld;

use pocketmine\block\Block;
use pocketmine\block\BlockFactory;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerItemHeldEvent;
use pocketmine\item\Item;
use pocketmine\item\Tool;
use pocketmine\plugin\PluginBase;
use pocketmine\event\Listener;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat;

class Main extends PluginBase implements Listener{

    public function onEnable()
    {
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
        $this->reloadConfig();
        if(!file_exists("config.yml")){
            $this->saveResource("config.yml");
        }
    }

    public function translateText($string, $blockname, $itemname, $playername){
        $msg = str_replace("{block}", $blockname, $string);
        $msg = str_replace("{item}", $itemname, $msg);
        $msg = str_replace("{player}", $playername, $msg);
        return $msg;
    }

    public function onHold(PlayerItemHeldEvent $event){
        $cfg = new Config($this->getDataFolder() . "config.yml", Config::YAML);
        $player = $event->getPlayer();
        $inv = $player->getInventory();
        $item = $inv->getItemInHand();
        if($item->getId() == $cfg->get("crowbar-id") and $item->getDamage() == $cfg->get("crowbar-meta")) {
            $item->setCustomName(TextFormat::colorize($cfg->get("crowbar-name")));
            $item->setLore([TextFormat::colorize($cfg->get("crowbar-lore-1")), TextFormat::colorize($cfg->get("crowbar-lore-2")), TextFormat::colorize($cfg->get("crowbar-lore-3")), TextFormat::colorize($cfg->get("crowbar-lore-4"))]);
            $inv->setItem($inv->getHeldItemIndex(), $item);
        }
    }

    public function onInteract(PlayerInteractEvent $event){
        $cfg = new Config($this->getDataFolder() . "config.yml", Config::YAML);
        $player = $event->getPlayer();
        $inv = $player->getInventory();
        $block = $event->getBlock();
        $item = $inv->getItemInHand();
        if($item->getId() == $cfg->get("crowbar-id") and $item->getDamage() == $cfg->get("crowbar-meta")){
            $blockname = $block->getName();
            $itemname = $item->getName();
            $playername = $player->getName();
            $blockitem = $block->getPickedItem();
            $blockitem->setCustomName($block->getName());
            $blockitem->setDamage($block->getDamage());
            $player->sendMessage($this->translateText(TextFormat::colorize($cfg->get("broken-block-message")), $blockname, $itemname, $playername));
            $block->getLevel()->dropItem($block->asVector3(), $blockitem);
            $block->getLevel()->setBlock($block->asVector3(), BlockFactory::get(Block::AIR));
            if($cfg->get("tool-mode") == "false") {
                $inv->setItem($inv->getHeldItemIndex(), new Item(0));
            }
            elseif($cfg->get("tool-mode") == "true" and $item instanceof Tool){
                $dur = $item->getMaxDurability();
                $item->applyDamage($dur / $cfg->get("crowbar-uses"));
            }
            else {
                $cfg->set("tool-mode", false);
                $inv->setItem($inv->getHeldItemIndex(), new Item(0));
            }
        }
        else {
            return false;
        }
        return true;
    }

    public function onDisable()
    {
        $this->saveDefaultConfig();
    }
}