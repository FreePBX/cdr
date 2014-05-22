<?php
// vim: set ai ts=4 sw=4 ft=php:
class Cdr implements BMO {
	public function __construct($freepbx = null) {
		if ($freepbx == null) {
			throw new Exception("Not given a FreePBX Object");
		}

		$this->FreePBX = $freepbx;
		$this->db = $freepbx->Database;
		$this->cdrdb = new Database('mysql:host=localhost;dbname=asteriskcdrdb','root','');
	}

	public function doConfigPageInit($page) {
	}

	public function install() {

	}
	public function uninstall() {

	}
	public function backup(){

	}
	public function restore($backup){

	}
	public function genConfig() {

	}

	public function getCalls($extension,$page=1,$limit=100) {
		$start = ($limit * ($page - 1));
		$end = $limit;
		$sql = "SELECT *, UNIX_TIMESTAMP(calldate) As timestamp FROM cdr WHERE src = ? ORDER by timestamp DESC LIMIT $start,$end";
		$sth = $this->cdrdb->prepare($sql);
		$sth->execute(array($extension));
		return $sth->fetchAll(PDO::FETCH_ASSOC);
	}

	/**
	 * Get the Number of Pages by limit for extension
	 * @param {int} $extension The Extension to lookup
	 * @param {int} $limit=100 The limit of results per page
	 */
	public function getPages($extension,$limit=100) {
		$sql = "SELECT count(*) as count FROM cdr WHERE src = ?";
		$sth = $this->cdrdb->prepare($sql);
		$sth->execute(array($extension));
		$res = $sth->fetch(PDO::FETCH_ASSOC);
		$total = $res['count'];
		if(!empty($total)) {
			return ceil($total/$limit);
		} else {
			return false;
		}
	}
}
