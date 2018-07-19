<?php
namespace FreePBX\modules\Cdr;
use FreePBX\modules\Backup as Base;
class Restore Extends Base\RestoreBase{
  public function runRestore($jobid){
    $dump = reset($this->getFiles());
    $dumpfile = $this->tmpdir . '/files/' . $dump['pathto'] . '/' . $dump['filename'];
    $dbhandle = $this->FreePBX->Cdr->getCdrDbHandle();
    $fh = gzopen($dumpfile, "r");
    if($fh){
        $dbhandle->query('TRUNCATE cdr');
        echo _("Attempting to import CDR records, this may take a bit.") . PHP_EOL;
        while (($line = fgets($fh)) !== false) {
            if ('--' == substr($line, 0, 2) || '' == $line) {
                continue;
            }
            if (';' == substr(trim($line), -1, 1)) {
                
                $dbhandle->query($line);
            }
        }
        gzclose($fh);
        return;
    }
    echo _("Couldn't open the database dump.").PHP_EOL;
    return;
  }
}