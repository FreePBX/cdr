<?php
namespace FreePBX\modules\Cdr;
use Symfony\Component\Process\Process;
use FreePBX\modules\Backup as Base;
class Restore Extends Base\RestoreBase{
	public function runRestore($jobid){
		$files = $this->getFiles();
		if(empty($files[0])) {
			return false;
		}
		$dump = $files[0];

		$dumpfile = $this->tmpdir . '/files/' . ltrim($dump->getPathTo(), '/') . '/' . $dump->getFilename();
		if (!file_exists($dumpfile)) {
			return;
		}

		global $amp_conf;
		$cdrname = $this->FreePBX->Config->get('CDRDBNAME') ? $this->FreePBX->Config->get('CDRDBNAME') : 'asteriskcdrdb';
		$tablename = $this->FreePBX->Config->get('CDRDBTABLENAME') ? $this->FreePBX->Config->get('CDRDBTABLENAME') : 'cdr';
		$cdrhost = $this->FreePBX->Config->get('CDRDBHOST') ? $this->FreePBX->Config->get('CDRDBHOST') : $amp_conf['AMPDBHOST'];
		$cdruser = $this->FreePBX->Config->get('CDRDBUSER') ? $this->FreePBX->Config->get('CDRDBUSER') : $amp_conf['AMPDBUSER'];
		$cdrpass = $this->FreePBX->Config->get('CDRDBPASS') ? $this->FreePBX->Config->get('CDRDBPASS') : $amp_conf['AMPDBPASS'];
		$cdrport = $this->FreePBX->Config->get('CDRDBPORT');

		$command = [];
		if(!empty($cdrhost)){
				$command[] = '--host';
				$command[] = $cdrhost;
		}
		if(!empty($cdrport)){
				$command[] = '--port';
				$command[] = $cdrport;
		}
		if(!empty($cdruser)){
				$command[] = '--user';
				$command[] = $cdruser;
		}
		if(!empty($cdrpass)){
				$command[] = '-p'.$cdrpass;
		}

		$dbhandle = $this->FreePBX->Cdr->getCdrDbHandle();
		$dbhandle->query("TRUNCATE $tablename");
		$restore = fpbx_which('mysql').' '.implode(" ", $command).' '.$cdrname.' < '.$dumpfile;
		$sql = new Process($restore);
		$sql->mustRun();
		return true;
	}

	public function processLegacy($pdo, $data, $tables, $unknownTables, $tmpfiledir){

		global $amp_conf;
		$cdrname = $this->FreePBX->Config->get('CDRDBNAME') ? $this->FreePBX->Config->get('CDRDBNAME') : 'asteriskcdrdb';
		$tablename = $this->FreePBX->Config->get('CDRDBTABLENAME') ? $this->FreePBX->Config->get('CDRDBTABLENAME') : 'cdr';
		$cdrhost = $this->FreePBX->Config->get('CDRDBHOST') ? $this->FreePBX->Config->get('CDRDBHOST') : $amp_conf['AMPDBHOST'];
		$cdruser = $this->FreePBX->Config->get('CDRDBUSER') ? $this->FreePBX->Config->get('CDRDBUSER') : $amp_conf['AMPDBUSER'];
		$cdrpass = $this->FreePBX->Config->get('CDRDBPASS') ? $this->FreePBX->Config->get('CDRDBPASS') : $amp_conf['AMPDBPASS'];
		$cdrport = $this->FreePBX->Config->get('CDRDBPORT');

		try {
				$connection = new \Database('mysql:dbname='.$cdrname.';host=localhost', $cdruser,$cdrpass);
		} catch(\Exception $e) {
				return array("status" => false, "message" => $e->getMessage());
		}
		$sth = $connection->query("SHOW TABLES");
		$res = $sth->fetchAll(\PDO::FETCH_ASSOC);

		foreach($res as $loadedTables){
				if ($loadedTables['Tables_in_asteriskcdrdb'] == $tablename){
						$truncate = "DROP TABLE asteriskcdrdb.".$tablename;
						$this->FreePBX->Database->query($truncate);
						$loadedTables = $pdo->query("ALTER TABLE asterisktemp.".$tablename." RENAME TO asteriskcdrdb.".$tablename);
				}
		}
	}
}