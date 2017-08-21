<?php

namespace TeamDeathMatch;

use pocketmine\event\Listener;
use pocketmine\event\player\{PlayerQuitEvent,PlayerCommandPreprocessEvent, PlayerInteractEvent, PlayerDropItemEvent, PlayerJoinEvent, PlayerChatEvent, PlayerItemHeldEvent};
use pocketmine\event\block\{BlockPlaceEvent, BlockBreakEvent};
use pocketmine\event\inventory\InventoryPickupItemEvent;
use pocketmine\event\entity\{EntityDamageEvent,EntityDamageByEntityEvent};
use pocketmine\network\protocol\{TransferPacket, InteractPacket};
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\utils\TextFormat;
use pocketmine\Player;
use pocketmine\level\Position;
use pocketmine\item\Item;

class EventListener implements Listener{

	public function __construct(Main $plugin){
		$this->plugin = $plugin;
	}

  public function onBlockPlace(BlockPlaceEvent $event){
    if(!$event->getPlayer()->isOp()){
      $event->setCancelled();
    }
  }

  public function onBlockBreak(BlockBreakEvent $event){
    if(!$event->getPlayer()->isOp()){
      $event->setCancelled();
    }
  }

	public function onCommand(PlayerCommandPreprocessEvent $event){
		$player = $event->getPlayer();
		$msg = $event->getMessage();
		$cmd = "/";
    if(stripos($msg, $cmd) === 0){
			if(!$player->isOp()){
        $event->setCancelled(true);
        $player->sendMessage(TextFormat::RED . "This action is blocked");
        return;
			}
    }
	}

  public function onDrop(PLayerDropItemEvent $event){
    $event->setCancelled();
  }

	public function onChat(PlayerChatEvent $event){
		$player = $event->getPlayer();
		$message = $event->getMessage();
		$message = preg_replace('/[§]/', "", $message);
		$event->setFormat(TextFormat::GRAY . $player->getName() . ": " . strtolower($message));
	}

	public function onJoin(PlayerJoinEvent $event){
		$player = $event->getPlayer();

		$event->setJoinMessage(null);

		if($this->plugin->TDMstatus == TDMgameStates::WAITING){
			$player->teleportImmediate($this->plugin->getServer()->getDefaultLevel()->getSafeSpawn());
		  $player->getInventory()->clearAll();
			foreach($player->getEffects() as $e){
				$player->removeEffect($e->getId());
			}
		  $player->setMaxHealth(20);
		  $player->setHealth(20);
			$player->setFood($player->getMaxFood());
			$this->plugin->spawnNpcs($player);
			return;
		}

		$pk = new TransferPacket();
		$pk->address = "198.100.152.247";
		$pk->port = 19134;
		$player->directDataPacket($pk);

	}

	public function onQuit(PlayerQuitEvent $event){
		$player = $event->getPlayer();
		$event->setQuitMessage(null);
		if(isset($this->plugin->teams[0][$player->getName()]) || isset($this->plugin->teams[1][$player->getName()])){
			$this->plugin->isLess();
		  $this->plugin->unset($player);
		}
	}

	public function onDmg(EntityDamageEvent $event){
		$hit = $event->getEntity();
		if($hit instanceof Player){
			if($event->getCause() == EntityDamageEvent::CAUSE_VOID){
				$event->setCancelled(true);
				$this->plugin->respawn($hit);
			}
			if($event->getCause() == EntityDamageEvent::CAUSE_FALL){
				$event->setCancelled(true);
			}
			if($event instanceof EntityDamageByEntityEvent){
				$killer = $event->getDamager();
				if($event->getCause() === EntityDamageEvent::CAUSE_PROJECTILE){
				  if($killer->getInventory()->getItemInHand()->getId() == 271 || $killer->getInventory()->getItemInHand()->getId() == 291){
					  $event->setDamage(7);
				  }
				  if($killer->getInventory()->getItemInHand()->getId() == 277){
					  $event->setDamage(10);
			    }
				  if($killer->getInventory()->getItemInHand()->getId() == 258){
					  $event->setDamage(6);
				  }
				  if($killer->getInventory()->getItemInHand()->getId() == 293){
					  $event->setDamage(4);
			    }
				}else{
					$event->setCancelled();
				}
				if($killer instanceof Player){
				  if(isset($this->plugin->teams[0][$killer->getName()]) && isset($this->plugin->teams[0][$hit->getName()])){
						$event->setCancelled();
						return;
					}elseif(isset($this->plugin->teams[1][$killer->getName()]) && isset($this->plugin->teams[1][$hit->getName()])){
						$event->setCancelled();
						return;
					}
					if($event->getDamage() >= $hit->getHealth()){
						$event->setCancelled();
						$this->plugin->processKill($killer, $hit);
						$this->plugin->ammoBox($hit, $killer);
						$this->plugin->respawn($hit);
					}
				}
			}
		}
	}

	public function onDpr(DataPacketReceiveEvent $e){
		$player = $e->getPlayer();
		$packet = $e->getPacket();
		if($packet instanceof InteractPacket){
			$action = $packet->action;
			if($action != 4){
				$target = $packet->target;
				if($target == 3000){
					$pk = new TransferPacket();
			    $pk->address = "198.100.152.247";
			    $pk->port = 19136;
			    $player->directDataPacket($pk);
				}
				if($target == 3001){
					$this->plugin->Sniper($player);
				}
				if($target == 3002){
					$this->plugin->Shotgun($player);
				}
				if($target == 3003){
					$this->plugin->Aug($player);
				}
				if($target == 3004){
					$this->plugin->Mgun($player);
				}
				if($target == 3005){
					$this->plugin->voteMap1($player);
				}
				if($target == 3006){
					$this->plugin->voteMap2($player);
				}
			}
		}
	}

	public function onHeld(PlayerItemHeldEvent $event){
	  $player = $event->getPlayer();
		foreach($player->getInventory()->getContents() as $item){
			if($item->getId() == 54){
				$player->getInventory()->removeItem($item);
				$player->getInventory()->addItem(Item::get(351,8,20));
			}
		}
	}
}
