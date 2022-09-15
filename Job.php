<?php

namespace FreePBX\modules\Cdr;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputInterface;
class Job implements \FreePBX\Job\TaskInterface {
	public static function run(InputInterface $input, OutputInterface $output) {
		\FreePBX::Cdr()->cleanupData();
		return true;
	}
}