<?php

namespace LousWiteMC\MineUp;

use pocketmine\plugin\PluginBase;
use pocketmine\plugin\PluginLoader;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\event\Listener;
use pocketmine\math\Vector3;
use pocketmine\Player;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\block\Block;
use pocketmine\utils\Config;
use pocketmine\level\Level;
use jojoe77777\FormAPI\ModalForm;
use jojoe77777\FormAPI\CustomForm;

class MineUp extends PluginBase implements Listener{
	
	public function onEnable(){
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
		$this->getLogger()->Info("§a
			┏━┓┏━┓ ┏━━┓ ┏━┓ ┏┓ ┏━━━┓    ┏┓ ┏┓ ┏━━━┓
			┃┃┗┛┃┃ ┗┫┣┛ ┃┃┗┓┃┃ ┃┏━━┛    ┃┃┃┃┃ ┏━┓ ┃
			┃┏┓┏┓┃  ┃┃  ┃┏┓┗┛┃ ┃┗━━┓    ┃┃ ┃┃ ┃┗━┛┃
			┃┃┃┃┃┃  ┃┃  ┃┃┗┓┃┃ ┃┏━━┛    ┃┃ ┃┃ ┃┏━━┛
			┃┃┃┃┃┃ ┏┫┣┓ ┃┃ ┃┃┃ ┃┗━━┓    ┃┗━┛┃ ┃┃
			┗┛┗┛┗┛ ┗━━┛ ┗┛ ┗━┛ ┗━━━┛    ┗━━━┛ ┗┛ 
			MineUp By LousWiteMC Enabled!\nYou Can Edit On Config!"
		);
		$this->eco = $this->getServer()->getPluginManager()->getPlugin("EconomyAPI");
		$this->loadConfig();
		if($this->eco == null){
			$this->getLogger()->notice("EconomyAPI is requiered, Plugin Will Disable!");
			$this->getPluginLoader()->disablePlugin($this);
		}
	}
	
	public function loadConfig(){
		@mkdir($this->getDataFolder());
        $this->saveResource("settings.yml");
		$this->settings = new Config($this->getDataFolder(). "settings.yml", config::YAML);		
		$this->data = new Config($this->getDataFolder() . "data.yml", Config::YAML);
	}
	
	public function BlockBreakEvent(BlockBreakEvent $ev){
		$player = $ev->getPlayer();
		$block = $ev->getBlock();
		$level = $this->settings->get("Name-World-To-Mine");
		$l = implode(" ", $level);
			if($player->getLevel()->getName() == $l){
				if($player->hasPermission($this->settings->get("Permission-To-Use-Mining-Skills"))){
					if($ev->getBlock()->getId() == Block::STONE or $ev->getBlock()->getId() == Block::COBBLESTONE or $ev->getBlock()->getId() == Block::GOLD_ORE or $ev->getBlock()->getId() == Block::IRON_ORE or $ev->getBlock()->getId() == Block::COAL_ORE or $ev->getBlock()->getId() == Block::LAPIS_BLOCK or $ev->getBlock()->getId() == Block::GOLD_BLOCK or $ev->getBlock()->getId() == Block::IRON_BLOCK or $ev->getBlock()->getId() == Block::DIAMOND_ORE or $ev->getBlock()->getId() == Block::DIAMOND_BLOCK or $ev->getBlock()->getId() == Block::EMERALD_ORE or $ev->getBlock()->getId() == Block::EMERALD_BLOCK or $ev->getBlock()->getId() == Block::COAL_BLOCK){
						$this->addMoney($player);
						$name = $player->getName();
				}	
			}
		}
	}
	
	public function sendUpForm($player){
		$form = new ModalForm(function(Player $player, $data){
			$result = $data;
			if($result == null){
			}
			switch($result){
				case 0:
				$this->eco->reduceMoney($player, $this->getMoneyToUp($player));
				$this->addMiningLevel($player, 1);
				$level = $this->getMiningLevel($player);
				$message = str_replace("{level}", $level, $this->settings->get("Message-When-UpMine"));
				$player->sendMessage($message);	
				break;
				case 1:
				break;
			}
		});
		$nextlevel = $this->getMiningLevel($player) + 1;
		$nextmoney = $this->getMoneyToUp($player);
		$loz = str_replace(["\n", "{nextlevel}", "{needmoney}", "{yourmoney}"], ["\n", $nextlevel, $nextmoney, $this->eco->myMoney($player)] ,$this->settings->get("Content"));
		$form->setTitle($this->settings->get("Title-Form"));
		$form->setContent($loz);
		$form->setButton1($this->settings->get("Button1-Form"));
		$form->setButton2($this->settings->get("Button2-Form"));
		$form->sendToPlayer($player);
	}
	
	public function registerUser(PlayerJoinEvent $event){
		$player = $event->getPlayer();
		$name = strtolower($player->getName());
		if(!($this->data->exists(strtolower($name)))){
			$this->data->set($name, 1);
			$this->data->save();
		}
	}
	
	public function getNextMiningLevel($player){
		$level = $this->getMiningLevel($player) + 1;
		return $level;
	}
	
	public function getMoneyToUp($player){
		if($player instanceof Player){
		}
		$money = $this->settings->get("Money-To-Up-Default")*$this->getMiningLevel($player);
		return $money;
	}
	
	public function getMiningLevel($player){
		$name = strtolower($player->getName());
		$level = $this->data->get($name);
		return $level;
	}

	public function addMiningLevel($player, $int){
		$name = strtolower($player->getName());
		$this->data->set($name, $this->getMiningLevel($player) + $int);
		$this->data->save();
	}
	
	public function addMoney($player){
		$name = strtolower($player->getName());
		$money = $this->eco->addMoney($name, $this->getMiningLevel($player)*$this->settings->get("Reward-Money-Default"));
		$mney = $this->getMiningLevel($player)*$this->settings->get("Reward-Money-Default");
	    $msg = str_replace("{money}",$mney,$this->settings->get("Popup-Add-Reward-When-Mine"));
	    if($this->settings->get("Popup-Add-Reward-When-Mine") == true){
	    	$popup = str_replace("{money}", $mney, $this->settings->get("Popup"));
			$player->sendPopup($popup); 
		}
	}
	
	public function onCommand(CommandSender $player, Command $cmd, string $label, array $args) : bool{
		if($cmd->getName() == "mineup"){
			$name = $player->getName();
			$money = $this->eco->myMoney($name);
			if(!($money >= $this->getMoneyToUp($player))){
				$player->sendMessage($this->settings->get("Message-Not-Enough-Money"));
				return false;
			}
			$this->sendUpForm($player);
			return true;
		}
		if($cmd->getName() == "seemlv"){
			if(isset($args[0])){
				$p = $this->getServer()->getPlayer($args[0]);
				if($player == null){
					$a = str_replace("{name}", $args[0], $this->settings->get("Message-No-Player"));
					$player->sendMessage($a);
				}else{
					$form = new CustomForm(function(Player $player, ?array $data){
					});
					$name = $p->getName();
					$n = strtolower($name);
					$targetlevel = $this->data->get($n);
					$string = str_replace(["{name}", "{level}", "\n"], [$name, $targetlevel, "\n"], $this->settings->get("Content2"));
					$form->setTitle($this->settings->get("Title2-Form"));
					$form->addLabel($string);
					$form->sendToPlayer($player);
					return true;
				}
				return true;
			}
		}
		if($cmd->getName() == "topmininglvl"){
			$max = 0;
			$c = $this->data->getAll();			
			$max = count($c);
			$max = ceil(($max / 5));
			$page = array_shift($args);
			$page = max(1, $page);
			$page = min($max, $page);
			$page = (int)$page;			
			$aa = $this->data->getAll();
			arsort($aa);
			$i = 0;		
				$player->sendMessage("§l§aTop Mining Reward Level §e".$page."§f/§a".$max."§c ⚒");			
			foreach($aa as $b=>$a){
				if(($page - 1) * 5 <= $i && $i <= ($page - 1) * 5 + 4){
					$i1 = $i + 1;				
					$message = "§e".$i1."§b. §c".$b."§b => §aLevel §e".$a."\n";
					$player->sendMessage($message);
				}
				$i++;
			}
		}
		return true;
	}
}	
