<?php

namespace TeamDeathMatch;

use pocketmine\item\Item;
use pocketmine\level\WeakPosition;
use pocketmine\block\Block;
use pocketmine\level\Position;
use pocketmine\math\Vector3;
use pocketmine\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\{TextFormat, UUID};
use pocketmine\level\Level;
use pocketmine\entity\Effect;
use pocketmine\entity\Entity;
use pocketmine\network\protocol\{SetTitlePacket, AddPlayerPacket};
use pocketmine\entity\Item as ItemEntity;
use pocketmine\nbt\tag\{StringTag, IntTag, CompoundTag, ShortTag, ListTag, DoubleTag, FloatTag};

class Main extends PluginBase{

  public $teamKills = [0,0];
  public $teams = [[],[]];
  public $tem = [0,0];
  public $TDMstatus = TDMgameStates::WAITING;
  public $TDMtime = 60;
  public $TDMGameTime = 600;
  public $timer = [];
  public $map = "world";
  public $map1 = "world";
  public $map2 = "world";
  public $votes = [0,0];
  public $vote1 = [];
  public $vote2 = [];

  public $sniper = [];
  public $shotgun = [];
  public $aug = [];
  public $mgun = [];

  public function onEnable(){
    $this->getServer()->getPluginManager()->registerEvents(new EventListener($this), $this);
    $this->getServer()->getScheduler()->scheduleRepeatingTask(new Task($this), 20);
    $this->chooseMaps();
  }

  public function chooseMaps(){
    switch(mt_rand(1,6)){
      case 1:
      $this->map1 = "Raid";
      break;
      case 2:
      $this->map1 = "Highrise";
      break;
      case 3:
      $this->map1 = "Plaza";
      break;
      case 4:
      $this->map1 = "Meltdown";
      break;
      case 5:
      $this->map1 = "Vertigo";
      break;
      case 6:
      $this->map1 = "Octane";
      break;
    }
    switch(mt_rand(1,6)){
      case 1:
      $this->map2 = "Raid";
      break;
      case 2:
      $this->map2 = "Highrise";
      break;
      case 3:
      $this->map2 = "Plaza";
      break;
      case 4:
      $this->map2 = "Meltdown";
      break;
      case 5:
      $this->map2 = "Vertigo";
      break;
      case 6:
      $this->map2 = "Octane";
      break;
    }
    if($this->map1 == $this->map2){
      $this->chooseMaps();
    }
  }


  public function isLess(){
    if($this->TDMstatus = TDMgameStates::INGAME){
      $onlineCount = 0;
		  foreach($this->getServer()->getOnlinePlayers() as $player){
			  if($player->isOnline()){
				  ++$onlineCount;
			  }
		  }
      if($onlineCount <= 1){
        foreach($this->getServer()->getOnlinePlayers() as $p){
          $pk = new SetTitlePacket();
      		$pk->type = SetTitlePacket::TYPE_TITLE;
      		$pk->title = "§4Ending Game";
      		$pk->fadeInDuration = 20;
      		$pk->duration = 0;
      		$pk->fadeOutDuration = 20;
      		$p->dataPacket($pk);
      		$pk1 = new SetTitlePacket();
      		$pk1->type = SetTitlePacket::TYPE_SUB_TITLE;
      		$pk1->title = TextFormat::GRAY . "Not enough players";
      		$pk1->fadeInDuration = 20;
      		$pk1->duration = 0;
      		$pk1->fadeOutDuration = 20;
      		$p->dataPacket($pk1);

          $this->getServer()->getScheduler()->scheduleDelayedTask(new RestartTask($this), 20*3);
        }
      }
    }
  }

  public function processKill(Player $killer, Player $hit){
    if(isset($this->teams[1][$killer->getName()])){
      $this->teamKills[1] += 1;
      foreach($this->getServer()->getOnlinePlayers() as $player){
        $player->sendMessage(TextFormat::RED . $killer->getName() . " §7-> " . TextFormat::AQUA . $hit->getName());
      }
    }
    if(isset($this->teams[0][$killer->getName()])){
      $this->teamKills[0] += 1;
      foreach($this->getServer()->getOnlinePlayers() as $player){
        $player->sendMessage(TextFormat::AQUA . $killer->getName() . " §7-> " . TextFormat::RED . $hit->getName());
      }
    }
    if($this->teamKills[0] >= 20){
      $this->blueWin();
      return;
    }
    if($this->teamKills[1] >= 20){
      $this->redWin();
    }
  }

  public function respawn(Player $player){
    $this->EquipClass($player);
    $this->tptoMap($player);
  }

  public function unset(Player $player){
    if(isset($this->teams[0][$player->getName()])){
      unset($this->teams[0][$player->getName()]);
    }
    if(isset($this->teams[1][$player->getName()])){
      unset($this->teams[1][$player->getName()]);
    }
    if(isset($this->vote1[$player->getName()])){
      $this->votes[0] -= 1;
      unset($this->vote1[$player->getName()]);
    }
    if(isset($this->vote2[$player->getName()])){
      $this->votes[1] -= 1;
      unset($this->vote2[$player->getName()]);
    }

    $this->unsetClass($player);
  }

  public function unsetClass(Player $player){
    if(isset($this->sniper[$player->getName()])){
      unset($this->sniper[$player->getName()]);
    }
    if(isset($this->shotgun[$player->getName()])){
      unset($this->shotgun[$player->getName()]);
    }
    if(isset($this->aug[$player->getName()])){
      unset($this->aug[$player->getName()]);
    }
    if(isset($this->mgun[$player->getName()])){
      unset($this->mgun[$player->getName()]);
    }
  }

  public function getOtherTeam(Player $player){
    if(isset($this->teams[0][$player->getName()]))
      return 1;
    return 0;
  }

  public function redWin(){
    foreach($this->getServer()->getOnlinePlayers() as $p){
      $pk = new SetTitlePacket();
      $pk->type = SetTitlePacket::TYPE_TITLE;
      $pk->title = "§4Red Team Wins!";
      $pk->fadeInDuration = 20;
      $pk->duration = 0;
      $pk->fadeOutDuration = 20;
      $p->dataPacket($pk);
    }
    $this->getServer()->getScheduler()->scheduleDelayedTask(new RestartTask($this), 20*3);
  }

  public function blueWin(){
    foreach($this->getServer()->getOnlinePlayers() as $p){
      $pk = new SetTitlePacket();
      $pk->type = SetTitlePacket::TYPE_TITLE;
      $pk->title = "§bBlue Team Wins!";
      $pk->fadeInDuration = 20;
      $pk->duration = 0;
      $pk->fadeOutDuration = 20;
      $p->dataPacket($pk);
    }
    $this->getServer()->getScheduler()->scheduleDelayedTask(new RestartTask($this), 20*3);
  }

  public function getTeam(Player $player){
    if(isset($this->teams[0][$player->getName()]))
      return 0;
    return 1;
  }

  public function pickTeam(Player $player){
    if(isset($this->teams[0][$player->getName()]) || isset($this->teams[1][$player->getName()])){
      return;
    }
    if($this->tem[0] <= $this->tem[1]){
      $color = TextFormat::AQUA;
      $this->teams[0][$player->getName()] = $player;
      $player->sendMessage("§1§lYou Joined Blue Team!");
      $player->setNameTag($color . $player->getName());
      $this->tem[0] += 1;
    }else{
      if($this->tem[0] > $this->tem[1]){
        $color = TextFormat::RED;
        $this->teams[1][$player->getName()] = $player;
        $player->sendMessage("§4§lYou Joined Red Team!");
        $player->setNameTag($color . $player->getName());
        $this->tem[1] += 1;
      }
    }
  }

  public function pickTeams(){
    foreach($this->getServer()->getOnlinePlayers() as $player){
      if(isset($this->teams[0][$player->getName()]) || isset($this->teams[1][$player->getName()])){
        return;
      }
      if($this->tem[0] <= $this->tem[1]){
        $color = TextFormat::AQUA;
        $this->teams[0][$player->getName()] = $player;
        $player->sendMessage("§1§lYou Joined Blue Team!");
        $player->setNameTag($color . $player->getName());
        $this->tem[0] += 1;
      }else{
        if($this->tem[0] > $this->tem[1]){
          $color = TextFormat::RED;
          $this->teams[1][$player->getName()] = $player;
          $player->sendMessage("§4§lYou Joined Red Team!");
          $player->setNameTag($color . $player->getName());
          $this->tem[1] += 1;
        }
      }
    }
  }

  public function pickWMap(){
    if($this->votes[0] >= $this->votes[1]){
      $this->map = $this->map1;
    }
    if($this->votes[1] > $this->votes[0]){
      $this->map = $this->map2;
    }
  }



  public function EquipClass(Player $player){
    if(isset($this->sniper[$player->getName()])){
      $this->snEquip($player);
      return;
    }
    if(isset($this->shotgun[$player->getName()])){
      $this->shEquip($player);
      return;
    }
    if(isset($this->aug[$player->getName()])){
      $this->augEquip($player);
      return;
    }
    if(isset($this->mgun[$player->getName()])){
      $this->mgunEquip($player);
      return;
    }

    $this->pEquip($player);
  }

  public function pEquip(Player $player){
    $player->setHealth($player->getMaxHealth());
    $player->setFood($player->getMaxFood());
    $player->getInventory()->clearAll();
    $player->getInventory()->addItem(Item::get(Item::WOODEN_AXE, 0, 1));
    $player->getInventory()->addItem(Item::get(Item::DYE, 8, 48));
    $player->getInventory()->addItem(Item::get(322,0,5));
    $player->getInventory()->setArmorContents(array(
      Item::get(Item::GOLD_HELMET, 0, 1),
      Item::get(Item::GOLD_CHESTPLATE, 0, 1),
      Item::get(Item::GOLD_LEGGINGS, 0, 1),
      Item::get(Item::GOLD_BOOTS, 0, 1)));
  }

  public function snEquip(Player $player){
    $player->setHealth($player->getMaxHealth());
    $player->setFood($player->getMaxFood());
    $player->getInventory()->clearAll();
    $player->getInventory()->addItem(Item::get(277, 0, 1));
    $player->getInventory()->addItem(Item::get(Item::DYE, 8, 48));
    $player->getInventory()->addItem(Item::get(322,0,5));
    $player->getInventory()->setArmorContents(array(
      Item::get(298, 0, 1),
      Item::get(299, 0, 1),
      Item::get(300, 0, 1),
      Item::get(301, 0, 1)));
  }

  public function shEquip(Player $player){
    $player->setHealth($player->getMaxHealth());
    $player->setFood($player->getMaxFood());
    $player->getInventory()->clearAll();
    $player->getInventory()->addItem(Item::get(291, 0, 1));
    $player->getInventory()->addItem(Item::get(Item::DYE, 8, 48));
    $player->getInventory()->addItem(Item::get(322,0,5));
    $player->getInventory()->setArmorContents(array(
      Item::get(302, 0, 1),
      Item::get(303, 0, 1),
      Item::get(304, 0, 1),
      Item::get(305, 0, 1)));
  }

  public function augEquip(Player $player){
    $player->setHealth($player->getMaxHealth());
    $player->setFood($player->getMaxFood());
    $player->getInventory()->clearAll();
    $player->getInventory()->addItem(Item::get(258, 0, 1));
    $player->getInventory()->addItem(Item::get(Item::DYE, 8, 48));
    $player->getInventory()->addItem(Item::get(322,0,5));
    $player->getInventory()->setArmorContents(array(
      Item::get(314, 0, 1),
      Item::get(315, 0, 1),
      Item::get(316, 0, 1),
      Item::get(317, 0, 1)));
  }

  public function mgunEquip(Player $player){
    $player->setHealth($player->getMaxHealth());
    $player->setFood($player->getMaxFood());
    $player->getInventory()->clearAll();
    $player->getInventory()->addItem(Item::get(293, 0, 1));
    $player->getInventory()->addItem(Item::get(Item::DYE, 8, 48));
    $player->getInventory()->addItem(Item::get(322,0,5));
    $player->getInventory()->setArmorContents(array(
      Item::get(306, 0, 1),
      Item::get(307, 0, 1),
      Item::get(308, 0, 1),
      Item::get(309, 0, 1)));
  }

  public function Sniper(Player $player){
    if(isset($this->sniper[$player->getName()])){
      $player->sendMessage(TextFormat::RED . "You have this class equipped already!");
      return;
    }

    $this->unsetClass($player);
    $this->sniper[$player->getName()] = $player;
    $player->sendMessage("§aYou equipped Sniper Class");
  }

  public function Shotgun(Player $player){
    if(isset($this->shotgun[$player->getName()])){
      $player->sendMessage(TextFormat::RED . "You have this class equipped already!");
      return;
    }

    $this->unsetClass($player);
    $this->shotgun[$player->getName()] = $player;
    $player->sendMessage("§aYou equipped Shotgun Class");
  }

  public function Aug(Player $player){
    if(isset($this->aug[$player->getName()])){
      $player->sendMessage(TextFormat::RED . "You have this class equipped already!");
      return;
    }

    $this->unsetClass($player);
    $this->aug[$player->getName()] = $player;
    $player->sendMessage("§aYou equipped AUG Class");
  }

  public function Mgun(Player $player){
    if(isset($this->mgun[$player->getName()])){
      $player->sendMessage(TextFormat::RED . "You have this class equipped already!");
      return;
    }

    $this->unsetClass($player);
    $this->mgun[$player->getName()] = $player;
    $player->sendMessage("§aYou equipped Machine Gun Class");
  }

  public function voteMap1(Player $player){
    if(isset($this->vote1[$player->getName()])){
      $player->sendMessage("§4You already voted for this map");
      return;
    }

    $player->sendMessage("§2You voted for this Map");
    $this->vote1[$player->getName()] = $player;
    $this->votes[0] += 1;
    if(isset($this->vote2[$player->getName()])){
      $this->votes[1] -= 1;
      unset($this->vote2[$player->getName()]);
    }
  }

  public function voteMap2(Player $player){
    if(isset($this->vote2[$player->getName()])){
      $player->sendMessage("§4You already voted for this map");
      return;
    }

    $player->sendMessage("§2You voted for this Map");
    $this->vote2[$player->getName()] = $player;
    $this->votes[1] += 1;
    if(isset($this->vote1[$player->getName()])){
      $this->votes[0] -= 1;
      unset($this->vote1[$player->getName()]);
    }
  }

  public function tptoMap(Player $player){
    if($this->map == "Highrise"){
      $world = $this->getServer()->getLevelByName("Highrise");
      if(isset($this->teams[0][$player->getName()])){
        //$player->teleport(new Position(10000, 100, 10000, $world));
        $player->teleport(new Position(89, 191, -22, $world));
        return;
      }
      if(isset($this->teams[1][$player->getName()])){
        //$player->teleport(new Position(10000, 100, 10000, $world));
        $player->teleport(new Position(-43, 191, 31, $world));
        return;
      }
    }
    if($this->map == "Plaza"){
      $world = $this->getServer()->getLevelByName("Plaza");
      if(isset($this->teams[0][$player->getName()])){
      //  $player->teleport(new Position(10000, 100, 10000, $world));
        $player->teleport(new Position(-1005, 35, -970, $world));
        return;
      }
      if(isset($this->teams[1][$player->getName()])){
        //$player->teleport(new Position(10000, 100, 10000, $world));
        $player->teleport(new Position(-1113, 35, -864, $world));
        return;
      }
    }
    if($this->map == "Meltdown"){
      $world = $this->getServer()->getLevelByName("Meltdown");
      if(isset($this->teams[0][$player->getName()])){
        //$player->teleport(new Position(10000, 100, 10000, $world));
        $player->teleport(new Position(-965, 33, -991, $world));
        return;
      }
      if(isset($this->teams[1][$player->getName()])){
      //  $player->teleport(new Position(10000, 100, 10000, $world));
        $player->teleport(new Position(-1150, 33, -991, $world));
        return;
      }
    }
    if($this->map == "Raid"){
      $world = $this->getServer()->getLevelByName("Raid");
      if(isset($this->teams[0][$player->getName()])){
        //$player->teleport(new Position(10000, 100, 10000, $world));
        $player->teleport(new Position(-916, 46, -1059, $world));
        return;
      }
      if(isset($this->teams[1][$player->getName()])){
        $player->teleport(new Position(10000, 100, 10000, $world));
        $player->teleport(new Position(-1056, 47, -1056, $world));
        return;
      }
    }
    if($this->map == "Vertigo"){
      $world = $this->getServer()->getLevelByName("Vertigo");
      if(isset($this->teams[0][$player->getName()])){
        //$player->teleport(new Position(10000, 100, 10000, $world));
        $player->teleport(new Position(-1223, 162, -1012, $world));
        return;
      }
      if(isset($this->teams[1][$player->getName()])){
        //$player->teleport(new Position(10000, 100, 10000, $world));
        $player->teleport(new Position(-1052, 163, -1004, $world));
        return;
      }
    }
    if($this->map == "Octane"){
      $world = $this->getServer()->getLevelByName("Octane");
      if(isset($this->teams[0][$player->getName()])){
        //$player->teleport(new Position(10000, 100, 10000, $world));
        $player->teleport(new Position(-697, 67, -434, $world));
        return;
      }
      if(isset($this->teams[1][$player->getName()])){
        //$player->teleport(new Position(10000, 100, 10000, $world));
        $player->teleport(new Position(-584, 67, -281, $world));
        return;
      }
    }
  }

  public function spawnNpcs(Player $player){
    $this->spawnOne($player);
    $this->spawnTwo($player);
    $this->spawnThree($player);
    $this->spawnFour($player);
    $this->spawnFive($player);
    $this->spawnMaps($player);
  }

  public function spawnOne(Player $player){
    $pk = new AddPlayerPacket();
    $pk->uuid = UUID::fromRandom();
    //$pk->username = TextFormat::GREEN . "Test";
    $pk->eid = 3001;
    $pk->x = 4.5;
    $pk->y = 8;
    $pk->z = -7.5;
    $pk->speedX = 0;
    $pk->speedY = 0;
    $pk->speedZ = 0;
    $pk->yaw = 10;
    $pk->pitch = 0;
    $pk->item = Item::get(277,0,1);
    $flags = (
      (1 << Entity::DATA_FLAG_CAN_SHOW_NAMETAG) |
      (1 << Entity::DATA_FLAG_ALWAYS_SHOW_NAMETAG) |
      (1 << Entity::DATA_FLAG_IMMOBILE)
    );
    $pk->metadata = [
      Entity::DATA_FLAGS => [Entity::DATA_TYPE_LONG, $flags],
      Entity::DATA_NAMETAG => [Entity::DATA_TYPE_STRING, TextFormat::GREEN . "Sniper Class"],
      Entity::DATA_LEAD_HOLDER_EID => [Entity::DATA_TYPE_LONG, -1]
    ];
    $player->dataPacket($pk);
  }
  public function spawnTwo(Player $player){
    $pk = new AddPlayerPacket();
    $pk->uuid = UUID::fromRandom();
    //$pk->username = TextFormat::GREEN . "Test";
    $pk->eid = 3002;
    $pk->x = 9.5;
    $pk->y = 8;
    $pk->z = -8.5;
    $pk->speedX = 0;
    $pk->speedY = 0;
    $pk->speedZ = 0;
    $pk->yaw = 10;
    $pk->pitch = 0;
    $pk->item = Item::get(291,0,1);
    $flags = (
      (1 << Entity::DATA_FLAG_CAN_SHOW_NAMETAG) |
      (1 << Entity::DATA_FLAG_ALWAYS_SHOW_NAMETAG) |
      (1 << Entity::DATA_FLAG_IMMOBILE)
    );
    $pk->metadata = [
      Entity::DATA_FLAGS => [Entity::DATA_TYPE_LONG, $flags],
      Entity::DATA_NAMETAG => [Entity::DATA_TYPE_STRING, TextFormat::GREEN . "Shotgun Class"],
      Entity::DATA_LEAD_HOLDER_EID => [Entity::DATA_TYPE_LONG, -1]
    ];
    $player->dataPacket($pk);
  }
  public function spawnThree(Player $player){
    $pk = new AddPlayerPacket();
    $pk->uuid = UUID::fromRandom();
    //$pk->username = TextFormat::GREEN . "Test";
    $pk->eid = 3003;
    $pk->x = 14.5;
    $pk->y = 8;
    $pk->z = -7.5;
    $pk->speedX = 0;
    $pk->speedY = 0;
    $pk->speedZ = 0;
    $pk->yaw = 10;
    $pk->pitch = 0;
    $pk->item = Item::get(258,0,1);
    $flags = (
      (1 << Entity::DATA_FLAG_CAN_SHOW_NAMETAG) |
      (1 << Entity::DATA_FLAG_ALWAYS_SHOW_NAMETAG) |
      (1 << Entity::DATA_FLAG_IMMOBILE)
    );
    $pk->metadata = [
      Entity::DATA_FLAGS => [Entity::DATA_TYPE_LONG, $flags],
      Entity::DATA_NAMETAG => [Entity::DATA_TYPE_STRING, TextFormat::GREEN . "AUG Class"],
      Entity::DATA_LEAD_HOLDER_EID => [Entity::DATA_TYPE_LONG, -1]
    ];
    $player->dataPacket($pk);
  }
  public function spawnFour(Player $player){
    $pk = new AddPlayerPacket();
    $pk->uuid = UUID::fromRandom();
    //$pk->username = TextFormat::GREEN . "Test";
    $pk->eid = 3004;
    $pk->x = 18.5;
    $pk->y = 8;
    $pk->z = -5.5;
    $pk->speedX = 0;
    $pk->speedY = 0;
    $pk->speedZ = 0;
    $pk->yaw = 10;
    $pk->pitch = 0;
    $pk->item = Item::get(293,0,1);
    $flags = (
      (1 << Entity::DATA_FLAG_CAN_SHOW_NAMETAG) |
      (1 << Entity::DATA_FLAG_ALWAYS_SHOW_NAMETAG) |
      (1 << Entity::DATA_FLAG_IMMOBILE)
    );
    $pk->metadata = [
      Entity::DATA_FLAGS => [Entity::DATA_TYPE_LONG, $flags],
      Entity::DATA_NAMETAG => [Entity::DATA_TYPE_STRING, TextFormat::GREEN . "Machine Gun Class"],
      Entity::DATA_LEAD_HOLDER_EID => [Entity::DATA_TYPE_LONG, -1]
    ];
    $player->dataPacket($pk);
  }
  public function spawnFive(Player $player){
    $pk = new AddPlayerPacket();
    $pk->uuid = UUID::fromRandom();
    //$pk->username = TextFormat::GREEN . "Test";
    $pk->eid = 3000;
    $pk->x = -2.5;
    $pk->y = 8;
    $pk->z = -2.5;
    $pk->speedX = 0;
    $pk->speedY = 0;
    $pk->speedZ = 0;
    $pk->yaw = 300;
    $pk->pitch = 0;
    $pk->item = Item::get(0,0,1);
    $flags = (
      (1 << Entity::DATA_FLAG_CAN_SHOW_NAMETAG) |
      (1 << Entity::DATA_FLAG_ALWAYS_SHOW_NAMETAG) |
      (1 << Entity::DATA_FLAG_IMMOBILE)
    );
    $pk->metadata = [
      Entity::DATA_FLAGS => [Entity::DATA_TYPE_LONG, $flags],
      Entity::DATA_NAMETAG => [Entity::DATA_TYPE_STRING, TextFormat::GREEN . "Lobby"],
      Entity::DATA_LEAD_HOLDER_EID => [Entity::DATA_TYPE_LONG, -1]
    ];
    $player->dataPacket($pk);
  }
  public function spawnMaps(Player $player){
    $pk = new AddPlayerPacket();
    $pk->uuid = UUID::fromRandom();
    //$pk->username = TextFormat::GREEN . "Test";
    $pk->eid = 3005;
    $pk->x = 21.5;
    $pk->y = 8;
    $pk->z = 3.5;
    $pk->speedX = 0;
    $pk->speedY = 0;
    $pk->speedZ = 0;
    $pk->yaw = 120;
    $pk->pitch = 0;
    $pk->item = Item::get(0,0,1);
    $flags = (
      (1 << Entity::DATA_FLAG_CAN_SHOW_NAMETAG) |
      (1 << Entity::DATA_FLAG_ALWAYS_SHOW_NAMETAG) |
      (1 << Entity::DATA_FLAG_IMMOBILE)
    );
    $pk->metadata = [
      Entity::DATA_FLAGS => [Entity::DATA_TYPE_LONG, $flags],
      Entity::DATA_NAMETAG => [Entity::DATA_TYPE_STRING, TextFormat::GREEN . $this->map1 . " - Map"],
      Entity::DATA_LEAD_HOLDER_EID => [Entity::DATA_TYPE_LONG, -1]
    ];
    $player->dataPacket($pk);

    $pk = new AddPlayerPacket();
    $pk->uuid = UUID::fromRandom();
    //$pk->username = TextFormat::GREEN . "Test";
    $pk->eid = 3006;
    $pk->x = 20.5;
    $pk->y = 8;
    $pk->z = 8.5;
    $pk->speedX = 0;
    $pk->speedY = 0;
    $pk->speedZ = 0;
    $pk->yaw = 120;
    $pk->pitch = 0;
    $pk->item = Item::get(0,0,1);
    $flags = (
      (1 << Entity::DATA_FLAG_CAN_SHOW_NAMETAG) |
      (1 << Entity::DATA_FLAG_ALWAYS_SHOW_NAMETAG) |
      (1 << Entity::DATA_FLAG_IMMOBILE)
    );
    $pk->metadata = [
      Entity::DATA_FLAGS => [Entity::DATA_TYPE_LONG, $flags],
      Entity::DATA_NAMETAG => [Entity::DATA_TYPE_STRING, TextFormat::GREEN . $this->map2 . " - Map"],
      Entity::DATA_LEAD_HOLDER_EID => [Entity::DATA_TYPE_LONG, -1]
    ];
    $player->dataPacket($pk);
  }

  public function ammoBox(Player $player, Player $killer){
    $item = Item::get(54,0,1);
    $entity = $player->getLevel()->dropItem($player, $item);
    $entity->setNameTagVisible(true);
    $entity->setNameTagAlwaysVisible(true);
    $entity->setNameTag(TextFormat::YELLOW . TextFormat::BOLD . "Ammo Box");
    foreach($killer->getLevel()->getPlayers() as $p){
      if($p != $killer){
      $entity->despawnFrom($p);
      }
    }
  }
}
