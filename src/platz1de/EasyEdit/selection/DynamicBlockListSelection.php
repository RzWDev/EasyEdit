<?php

namespace platz1de\EasyEdit\selection;

use Closure;
use platz1de\EasyEdit\utils\LoaderManager;
use platz1de\EasyEdit\utils\VectorUtils;
use pocketmine\level\format\Chunk;
use pocketmine\level\Level;
use pocketmine\level\Position;
use pocketmine\math\Vector3;
use pocketmine\utils\Utils;
use UnexpectedValueException;

class DynamicBlockListSelection extends BlockListSelection
{
	/**
	 * @var Vector3
	 */
	private $point;

	/**
	 * DynamicBlockListSelection constructor.
	 * @param string  $player
	 * @param Vector3 $place
	 * @param Vector3 $pos1
	 * @param Vector3 $pos2
	 * @param bool    $piece
	 */
	public function __construct(string $player, Vector3 $place, Vector3 $pos1, Vector3 $pos2, bool $piece = false)
	{
		parent::__construct($player, "", new Vector3(), $pos2->subtract($pos1), $piece);
		$this->point = $place->subtract($pos1);
	}

	/**
	 * @param Position $place
	 * @return Chunk[]
	 */
	public function getNeededChunks(Position $place): array
	{
		$start = $this->getCubicStart()->add($place)->subtract($this->getPoint());
		$end = $this->getCubicEnd()->add($place)->subtract($this->getPoint());

		$chunks = [];
		for ($x = $start->getX() >> 4; $x <= $end->getX() >> 4; $x++) {
			for ($z = $start->getZ() >> 4; $z <= $end->getZ() >> 4; $z++) {
				$chunks[] = LoaderManager::getChunk($this->getLevel(), $x, $z);
			}
		}
		return $chunks;
	}

	/**
	 * @param int     $x
	 * @param int     $z
	 * @param Vector3 $place
	 * @return bool
	 */
	public function isChunkOfSelection(int $x, int $z, Vector3 $place): bool
	{
		$start = $this->getCubicStart()->add($place)->subtract($this->getPoint());
		$end = $this->getCubicEnd()->add($place)->subtract($this->getPoint());

		return $start->getX() >> 4 <= $x && $x <= $end->getX() >> 4 && $start->getZ() >> 4 <= $z && $z <= $end->getZ() >> 4;
	}

	/**
	 * @param Vector3 $place
	 * @param Closure $closure
	 * @return void
	 */
	public function useOnBlocks(Vector3 $place, Closure $closure): void
	{
		Utils::validateCallableSignature(static function (int $x, int $y, int $z): void { }, $closure);
		$min = VectorUtils::enforceHeight($this->pos1->add($place));
		$max = VectorUtils::enforceHeight($this->pos2->add($place));
		for ($x = $min->getX(); $x <= $max->getX(); $x++) {
			for ($z = $min->getZ(); $z <= $max->getZ(); $z++) {
				for ($y = $min->getY(); $y <= $max->getY(); $y++) {
					$closure($x, $y, $z);
				}
			}
		}
	}

	/**
	 * @return Vector3
	 */
	public function getPoint(): Vector3
	{
		return $this->point;
	}

	/**
	 * @return array
	 */
	public function getData(): array
	{
		return array_merge([
			"x" => $this->point->getX(),
			"y" => $this->point->getY(),
			"z" => $this->point->getZ()
		], parent::getData());
	}

	/**
	 * @param array $data
	 */
	public function setData(array $data): void
	{
		$this->point = new Vector3($data["x"], $data["y"], $data["z"]);
		parent::setData($data);
	}

	/**
	 * splits into 3x3 Chunk pieces
	 * @return array
	 */
	public function split(): array
	{
		if ($this->piece) {
			throw new UnexpectedValueException("Pieces are not split able");
		}

		$pieces = [];
		for ($x = $this->pos1->getX() >> 4; $x <= $this->pos2->getX() >> 4; $x += 3) {
			for ($z = $this->pos1->getZ() >> 4; $z <= $this->pos2->getZ() >> 4; $z += 3) {
				$piece = new DynamicBlockListSelection($this->getPlayer(), $this->getPoint(), new Vector3(max($x << 4, $this->pos1->getX()), max($this->pos1->getY(), 0), max($z << 4, $this->pos1->getZ())), new Vector3(min(($x << 4) + 47, $this->pos2->getX()), min($this->pos2->getY(), Level::Y_MASK), min(($z << 4) + 47, $this->pos2->getZ())), true);
				for ($chunkX = 0; $chunkX < 3; $chunkX++) {
					for ($chunkZ = 0; $chunkZ < 3; $chunkZ++) {
						$piece->getManager()->setChunk($chunkX, $chunkZ, ($chunk = $this->getManager()->getChunk($x + $chunkX, $z + $chunkZ)));
						if ($chunk !== null) {
							$chunk->setX($chunkX);
							$chunk->setZ($chunkZ);
						}
					}
				}
				$pieces[] = $piece;
			}
		}
		return $pieces;
	}
}