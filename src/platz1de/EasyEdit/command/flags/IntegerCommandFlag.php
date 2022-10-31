<?php

namespace platz1de\EasyEdit\command\flags;

use platz1de\EasyEdit\command\EasyEditCommand;
use platz1de\EasyEdit\command\exception\InvalidUsageException;
use platz1de\EasyEdit\session\Session;

class IntegerCommandFlag extends CommandFlag
{
	private int $argument;

	/**
	 * @param int         $argument
	 * @param string      $name
	 * @param string[]    $aliases
	 * @param string|null $id
	 * @return IntegerCommandFlag
	 */
	public static function with(int $argument, string $name, array $aliases = null, string $id = null): self
	{
		$flag = new self($name, $aliases, $id);
		$flag->hasArgument = true;
		$flag->argument = $argument;
		return $flag;
	}

	/**
	 * @param int $argument
	 */
	public function setArgument(int $argument): void
	{
		$this->argument = $argument;
	}

	/**
	 * @return int
	 */
	public function getArgument(): int
	{
		return $this->argument;
	}

	/**
	 * @param EasyEditCommand $command
	 * @param Session         $session
	 * @param string          $argument
	 * @return IntegerCommandFlag
	 */
	public function parseArgument(EasyEditCommand $command, Session $session, string $argument): self
	{
		if (!is_numeric($argument)) {
			throw new InvalidUsageException($command);
		}
		$this->setArgument((int) $argument);
		return $this;
	}
}