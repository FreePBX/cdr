<?php
namespace FreePBX\modules\Cdr;
use Symfony\Component\Process\Process;
use FreePBX\modules\Backup as Base;
class Restore Extends Base\RestoreBase{
  public function runRestore($jobid){
    $dump = reset($this->getFiles());
    $dumpfile = $this->tmpdir . '/files/' . ltrim($dump['pathto'], '/') . '/' . $dump['filename'];
    $dbhandle = $this->FreePBX->Cdr->getCdrDbHandle();
    if (file_exists($dumpfile)) {
        $dbhandle->query('TRUNCATE cdr');
        $command = sprintf('/usr/bin/gunzip -d %s', $dumpfile);
        $gunzip = new Process($command);
        $gunzip->mustRun();
        $newfilename = substr($dumpfile, 0, -3);
        $restore = 'mysql asteriskcdrdb -e "LOAD DATA INFILE \''.$newfilename.'\' INTO TABLE cdr;"';
        $sql = new Process($restore);
        $sql->mustRun();
        return true;
    }
    return false;
  }

  public function processLegacy($pdo, $data, $tables, $unknownTables, $tmpfiledir){
    $data['modname'] = "cdr";
    try {
        $connection = new \Database('mysql:dbname=asteriskcdrdb;host=localhost', 'root','');
    } catch(\Exception $e) {
        return array("status" => false, "message" => $e->getMessage());
    }
    $sth = $connection->query("SHOW TABLES");
    $res = $sth->fetchAll(\PDO::FETCH_ASSOC);

    foreach($res as $loadedTables){
        if ($loadedTables['Tables_in_asteriskcdrdb'] == $data['modname']){
            $truncate = "DROP TABLE asteriskcdrdb.".$data['modname'];
            $this->FreePBX->Database->query($truncate);
            $loadedTables = $pdo->query("ALTER TABLE asterisktemp.".$data['modname']." RENAME TO asteriskcdrdb.".$data['modname']);
        }
    }
  }
}