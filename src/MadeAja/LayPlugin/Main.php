<?php


namespace MadeAja\LayPlugin;


use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\player\PlayerToggleSneakEvent;
use pocketmine\math\Vector3;
use pocketmine\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat;

class Main extends PluginBase implements Listener
{

    /* @var string */
    private static $layData = [];

    /* @var Config */
    private static $config;

    public function onLoad()
    {
        foreach ($this->getResources() as $file) {
            $this->saveResource($file->getFilename());
        }
        self::$config = yaml_parse(file_get_contents($this->getDataFolder() . "config.yml"));
    }

    public function onEnable()
    {
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
    }


    public function isLay(Player $player): bool
    {
        $name = strtolower($player->getName());
        if (!isset(self::$layData[$name])) {
            return false;
        }
        return true;
    }

    public function unsetLay(Player $player)
    {
        $player->sendMessage($this->translateColors(self::$config['messageUnsetLay']));
        $player->setGenericFlag(Player::DATA_FLAG_SLEEPING, false);
        unset(self::$layData[strtolower($player->getName())]);
        $player->setImmobile(false);
    }

    public function setLay(Player $player)
    {
        $player->sendMessage($this->translateColors(self::$config['messageSetLay']));
        $player->getDataPropertyManager()->setBlockPos(Player::DATA_PLAYER_BED_POSITION, new Vector3($player->getX(), $player->getY(), $player->getZ()));
        $player->setGenericFlag(Player::DATA_FLAG_SLEEPING, true);
        $player->setImmobile(true);
        self::$layData[strtolower($player->getName())] = true;
    }


    public function onCommand(CommandSender $sender, Command $command, string $label, array $args): bool
    {
        switch ($command->getName()) {
            case "lay":
                if ($sender instanceof Player) {
                    if ($this->isLay($sender)) {
                        $this->unsetLay($sender);
                    } else {
                        $this->setLay($sender);
                    }
                    break;
                } else {
                    $sender->sendMessage($this->translateColors(self::$config['messageNotPlayer']));
                    return false;
                }
        }
        return true;
    }

    public function translateColors(string $message): string
    {
        $message = str_replace("&", TextFormat::ESCAPE, $message);
        return $message;
    }

    /**
     * Executes onQuitPlayer actions
     *
     * @param PlayerQuitEvent $event
     */
    public function onQuitPlayer(PlayerQuitEvent $event)
    {
        if ($this->isLay($event->getPlayer())) {
            unset(self::$layData[strtolower($event->getPlayer()->getName())]);
        }
    }

    /**
     * Executes toggleSneakPlayer actions
     *
     * @param PlayerToggleSneakEvent $event
     */
    public function toggleSneakPlayer(PlayerToggleSneakEvent $event)
    {
        if ($this->isLay($event->getPlayer())) {
            $this->unsetLay($event->getPlayer());
        }
    }


}