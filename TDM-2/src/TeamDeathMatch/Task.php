<?php

namespace TeamDeathMatch;

use pocketmine\Player;
use pocketmine\scheduler\PluginTask;
use pocketmine\utils\TextFormat;
use pocketmine\network\protocol\SetTitlePacket;
use pocketmine\level\Position;

class Task extends PluginTask{

  public $main;

  public function __construct(Main $owner){
    $this->main = $owner;
    parent::__construct($owner);
  }

  public function onRun($currentTick){
    if($this->main->TDMstatus === TDMgameStates::WAITING){
      $onlineCount = 0;
		  foreach($this->main->getServer()->getOnlinePlayers() as $player){
			  if($player->isOnline()){
				  ++$onlineCount;
			  }
		  }
      if($onlineCount >= 2){
        foreach($this->main->getServer()->getOnlinePlayers() as $player){
          $player->sendPopup(TextFormat::YELLOW . "Starting in " . TextFormat::AQUA . $this->main->TDMtime . "\n§6§lChoose A Class!\n§b§lMap 1 Votes: §r§e" . $this->main->votes[0] . " §b§lMap 2 Votes: §r§e" . $this->main->votes[1]);
        }
        $this->main->TDMtime--;
        if($this->main->TDMtime <= 0){
          $this->main->TDMstatus = TDMgameStates::INGAME;
          foreach($this->main->getServer()->getOnlinePlayers() as $player){
            $this->main->pickTeams();
            $this->main->pickWMap();
            $world = $this->main->getServer()->getLevelByName($this->main->map);
            $player->teleport(new Position(10000, 100, 10000, $world));
            $this->main->Respawn($player);
            $pk = new SetTitlePacket();
        		$pk->type = SetTitlePacket::TYPE_TITLE;
        		$pk->title = "§aBegin Game!";
        		$pk->fadeInDuration = 20;
        		$pk->duration = 0;
        		$pk->fadeOutDuration = 20;
        		$player->dataPacket($pk);
        		$pk1 = new SetTitlePacket();
        		$pk1->type = SetTitlePacket::TYPE_SUB_TITLE;
        		$pk1->title = TextFormat::GRAY . "20 Kills to Win!";
        		$pk1->fadeInDuration = 20;
        		$pk1->duration = 0;
        		$pk1->fadeOutDuration = 20;
        		$player->dataPacket($pk1);

          }
        }
      }else{
        foreach($this->main->getServer()->getOnlinePlayers() as $p){
          $p->sendPopup(TextFormat::GRAY . "Waiting for players...");
        }
      }
    }
    if($this->main->TDMstatus === TDMgameStates::INGAME){
      foreach($this->main->getServer()->getOnlinePlayers() as $player){
        $player->sendPopup(TextFormat::YELLOW . "Kills: " . TextFormat::RED . "Red: " . $this->main->teamKills[1] . TextFormat::GRAY . " / " . TextFormat::AQUA . "Blue: " . $this->main->teamKills[0] . "\n§eTime Left: §c" . $this->main->TDMGameTime);
      }
      $onlineCount = 0;
		  foreach($this->main->getServer()->getOnlinePlayers() as $player){
			  if($player->isOnline()){
				  ++$onlineCount;
			  }
		  }
      if($onlineCount <= 1){
        $this->main->isLess();
      }

      $this->main->TDMGameTime--;
      if($this->main->TDMGameTime <= 0){
        if($this->main->teamKills[0] > $this->main->teamKills[1]){
          $this->main->blueWin();
          return;
        }
        if($this->main->teamKills[1] > $this->main->teamKills[0]){
          $this->main->redWin();
          return;
        }
        foreach($this->main->getServer()->getOnlinePlayers() as $player){
          $pk = new SetTitlePacket();
          $pk->type = SetTitlePacket::TYPE_TITLE;
          $pk->title = "§7No Winner!";
          $pk->fadeInDuration = 20;
          $pk->duration = 0;
          $pk->fadeOutDuration = 20;
          $player->dataPacket($pk);
        }
        $this->main->getServer()->getScheduler()->scheduleDelayedTask(new RestartTask($this->main), 20*3);
      }
    }
  }
}
