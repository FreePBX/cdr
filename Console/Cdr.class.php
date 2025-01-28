<?php
//Namespace should be FreePBX\Console\Command
namespace FreePBX\Console\Command;

//Symfony stuff all needed add these
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\LockableTrait;
class Cdr extends Command {
	use LockableTrait;
	protected function configure() {
		$this->setName('cdr')
			->setDescription(_('Cdr module '))
			->setDefinition(array(
				new InputOption('purnedata', null,  InputOption::VALUE_NONE,  _('Remove Data older than retention days')),
			));
	}
	protected function execute(InputInterface $input, OutputInterface $output){

		set_time_limit(0);
		ini_set('memory_limit', '-1');
		if (function_exists('proc_nice')) {
			@proc_nice(10);
		}
		
		if($input->getOption('purnedata')){
			$output->writeln("Clear Old data");
			$dataRetentionInDays = \FreePBX::Config()->get("TRANSIENTCDRDATA");
			$date = Date('Y-m-d', strtotime("- $dataRetentionInDays days"));
			\FreePBX::Cdr()->cleanTransientCDRData($date);
			$output->writeln("Done");
			exit(-1);
		}

	}

	/**
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 * @return int
	 * @throws \Symfony\Component\Console\Exception\ExceptionInterface
	 */
	protected function outputHelp(InputInterface $input, OutputInterface $output)	 {
		$help = new HelpCommand();
		$help->setCommand($this);
		return $help->run($input, $output);
	}
}
