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
}