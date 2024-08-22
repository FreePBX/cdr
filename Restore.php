<?php
namespace FreePBX\modules\Cdr;
use FreePBX\modules\Backup as Base;
class Restore Extends Base\RestoreBase{
	public function runRestore(){
		$configs = $this->getConfigs();
		$files = $this->getFiles();
		$tablename = $this->FreePBX->Config->get('CDRDBTABLENAME') ?: 'cdr';
		$dbhandle = $this->FreePBX->Cdr->getCdrDbHandle();
		$dbhandle->query("TRUNCATE $tablename");
		if(isset($configs['settings'])) {
			$this->importAdvancedSettings($configs['settings']);
		}
		if(isset($config['kvstore'])) {
			$this->importKVStore($config['kvstore']);
		}
		$this->FreePBX->Cdr->removeCdrTrigger();
		$dumpres = $this->restoreDataFromDump($tablename, $this->tmpdir, $files);
		if($this->FreePBX->Config()->get('TRANSIENTCDR')){
			$this->FreePBX->Cdr->createCdrTrigger();
		}
		return $dumpres;
	}

	public function processLegacy($pdo, $data, $tables, $unknownTables){

		global $amp_conf;
		$cdrname = $this->FreePBX->Config->get('CDRDBNAME') ?: 'asteriskcdrdb';
		$tablename = $this->FreePBX->Config->get('CDRDBTABLENAME') ?: 'cdr';
		$cdrhost = $this->FreePBX->Config->get('CDRDBHOST') ?: $amp_conf['AMPDBHOST'];
		$cdruser = $this->FreePBX->Config->get('CDRDBUSER') ?: $amp_conf['AMPDBUSER'];
		$cdrpass = $this->FreePBX->Config->get('CDRDBPASS') ?: $amp_conf['AMPDBPASS'];
		$cdrport = $this->FreePBX->Config->get('CDRDBPORT') ?: $amp_conf['AMPDBPORT'];

		$cdrport = empty($cdrport) ? '' :  ';port=' . $cdrport;

		try {
				$connection = new \Database('mysql:dbname='.$cdrname.';host='.$cdrhost.$cdrport, $cdruser,$cdrpass);
		} catch(\Exception $e) {
				return ["status" => false, "message" => $e->getMessage()];
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
