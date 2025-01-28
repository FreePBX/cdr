<?php
namespace FreePBX\modules\Cdr;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputInterface;
class Job implements \FreePBX\Job\TaskInterface {
	public static function run(InputInterface $input, OutputInterface $output) {
		$tz = @date_default_timezone_get();
		date_default_timezone_set($tz);
		$dataRetentionInDays = \FreePBX::Config()->get("TRANSIENTCDRDATA");
		dbug($dataRetentionInDays);
		$date = Date('Y-m-d', strtotime("- $dataRetentionInDays days"));
		\FreePBX::Cdr()->cleanTransientCDRData($date);
		return true;
	}
}
