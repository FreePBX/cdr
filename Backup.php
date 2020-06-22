<?php
namespace FreePBX\modules\Cdr;
use Symfony\Component\Process\Process;
use FreePBX\modules\Backup as Base;
use Symfony\Component\Filesystem\Filesystem;
class Backup Extends Base\BackupBase{
  public function runBackup($id,$transaction){
    $fs = new Filesystem();
    $tmpdir = sys_get_temp_dir().'/dbdump';
		$fs->remove($tmpdir);
		$fs->mkdir($tmpdir);

		global $amp_conf;
		$cdrname = $this->FreePBX->Config->get('CDRDBNAME') ? $this->FreePBX->Config->get('CDRDBNAME') : 'asteriskcdrdb';
		$tablename = $this->FreePBX->Config->get('CDRDBTABLENAME') ? $this->FreePBX->Config->get('CDRDBTABLENAME') : 'cdr';
		$cdrhost = $this->FreePBX->Config->get('CDRDBHOST') ? $this->FreePBX->Config->get('CDRDBHOST') : $amp_conf['AMPDBHOST'];
		$cdruser = $this->FreePBX->Config->get('CDRDBUSER') ? $this->FreePBX->Config->get('CDRDBUSER') : $amp_conf['AMPDBUSER'];
		$cdrpass = $this->FreePBX->Config->get('CDRDBPASS') ? $this->FreePBX->Config->get('CDRDBPASS') : $amp_conf['AMPDBPASS'];
		$cdrport = $this->FreePBX->Config->get('CDRDBPORT');

    $command = [fpbx_which('mysqldump')];
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
    $command[] = $cdrname;
    $command[] = '--opt';
    $command[] = '--compact';
    $command[] = '--table';
    $command[] = $tablename;
    $command[] = '--skip-lock-tables';
    $command[] = '--skip-triggers';
    $command[] = '--no-create-info';
    $tmpfile = $tmpdir.'/cdr.sql';
		$command[] = '--result-file='. $tmpfile;
		$command = implode(" ", $command);
		$process= new Process($command);
		$process->disableOutput();
		$process->mustRun();
		$fileObj = new \SplFileInfo($tmpfile);
		$this->addSplFile($fileObj);
    $this->addDirectories([$fileObj->getPath()]);

    $this->addGarbage($tmpdir);
  }
}
