<?php
namespace ZipPluginLoader;
use pocketmine\plugin\PluginBase;
use pocketmine\plugin\PluginLoadOrder;

class MyZipStream {
	// This is needed to work around bugs/incomplete features of the
	// built-in PHP Zip wrapper
	var $fp;
	var $path;
	public function stream_open($path,$mode,$opts,&$opened_path) {
		echo __METHOD__.",".__LINE__."\n";//##DEBUG
		$this->path = $path;
		$zippath = preg_replace('/^myzip:/','zip:',$path);
		$this->fp = @fopen($zippath,$mode);
		if ($this->fp == false) return false;
		return true;
	}
	public function stream_close() {
		echo __METHOD__.",".__LINE__."\n";//##DEBUG
		fclose($this->fp);
	}
	public function stream_read($count) {
		echo __METHOD__.",".__LINE__."\n";//##DEBUG
		return fread($this->fp,$count);
	}
	public function stream_eof() {
		echo __METHOD__.",".__LINE__."\n";//##DEBUG
		return feof($this->fp);
	}
	public function url_stat($path,$flags) {
		echo __METHOD__.",".__LINE__."\n";//##DEBUG
		$ret = [];
		$zippath = preg_replace('/^myzip:\/\//',"",$path);
		$parts = explode('#',$zippath,2);
		if (count($parts)!=2) return false;
		echo __METHOD__.",".__LINE__."\n";//##DEBUG
		list($zippath,$subfile) = $parts;
		echo "zippath=$zippath subfile=$subfile\n";//##DEBUG
		$za = new \ZipArchive();
		if ($za->open($zippath) !== true) return false;
		echo __METHOD__.",".__LINE__."\n";//##DEBUG
		$i = $za->locateName($subfile);
		if ($i === false) return false;
		echo __METHOD__.",".__LINE__."\n";//##DEBUG
		$zst = $za->statIndex($i);
		$za->close();
		unset($za);
		foreach([7=>'size', 8=>'mtime',9=>'mtime',10=>'mtime'] as $a=>$b) {
			if (!isset($zst[$b])) continue;
			$ret[$a] = $zst[$b];
		}
		echo __METHOD__.",".__LINE__."\n";//##DEBUG
		return $ret;
	}
	public function stream_stat() {
		return $this->url_stat($this->path,0);
	}
}

class Main extends PluginBase {
	public function onEnable(){
		if (!in_array("myzip",stream_get_wrappers())) {
			if (!stream_wrapper_register("myzip",__NAMESPACE__."\\MyZipStream")) {
				$this->getLogger()->info("Unable to register Zip wrapper");
				trigger_error("Unable to register ZipWrapper", E_USER_WARNING);
				return;
			}
		}
		$this->getServer()->getPluginManager()->registerInterface("ZipPluginLoader\\ZipPluginLoader");
		$this->getServer()->getPluginManager()->loadPlugins($this->getServer()->getPluginPath(), ["ZipPluginLoader\\ZipPluginLoader"]);
		$this->getServer()->enablePlugins(PluginLoadOrder::STARTUP);
	}
	public function onDisable() {
		if (in_array("myzip",stream_get_wrappers())) {
			stream_wrapper_unregister("myzip");
		}
	}
}
