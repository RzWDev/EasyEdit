<?php

namespace platz1de\EasyEdit\world\blockupdate;

use pocketmine\network\mcpe\convert\TypeConverter;
use pocketmine\network\mcpe\protocol\serializer\PacketSerializer;
use pocketmine\network\mcpe\protocol\types\BlockPosition;
use pocketmine\utils\Binary;

class InjectingData
{
    private PacketSerializer $injection;
    private int $blockCount = 0;
    private BlockPosition $position;
    private int $protocolId;

    public function __construct(int $protocolId)
    {
        $this->protocolId = $protocolId;
        $this->position = new BlockPosition(0, 0, 0);
        $this->injection = PacketSerializer::encoder($protocolId);
    }

    public static function encoder(int $protocolId): self
    {
        return new self($protocolId);
    }

    public static function decoder(int $protocolId, string $buffer, int $offset): self
    {
        $instance = new self($protocolId);
        return $instance;
    }

    public function setPosition(int $x, int $y, int $z): void
    {
        $this->position = new BlockPosition($x, $y, $z);
    }

    public function writeBlock(int $x, int $y, int $z, int $id): void
    {
        $this->blockCount++;
        $this->injection->putVarInt($x);
        $this->injection->putUnsignedVarInt(Binary::unsignInt($y));
        $this->injection->putVarInt($z);
        $this->injection->putUnsignedVarInt(TypeConverter::getInstance()->getBlockTranslator()->internalIdToNetworkId($id));
        $this->injection->putUnsignedVarInt(2);
        $this->injection->putUnsignedVarLong(-1);
        $this->injection->putUnsignedVarInt(0);
    }

    public function toProtocol(): string
    {
        $serializer = PacketSerializer::encoder($this->protocolId);
        $serializer->putBlockPosition($this->position);
        $serializer->putUnsignedVarInt($this->blockCount);
        $serializer->put($this->injection->getBuffer());
        $serializer->putUnsignedVarInt(0);
        return $serializer->getBuffer();
    }
}
