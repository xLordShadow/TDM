<?php

namespace TeamDeathMatch;

use pocketmine\scheduler\PluginTask;
use pocketmine\network\protocol\TransferPacket;

class RestartTask extends PluginTask{

	public function __construct(Main $plugin){
		parent::__construct($plugin);
		$this->plugin = $plugin;
	}

	public function onRun($currentTick){
		foreach($this->plugin->getServer()->getOnlinePlayers() as $p){
			$pk = new TransferPacket();
      $pk->address = "198.100.152.247";
      $pk->port = 19136;
      $p->directDataPacket($pk);
		}
    $this->plugin->getServer()->shutdown(true, "Ending Game");
	}
}
