<?php
require_once ('model-osmxml.php');
require_once ('model-filetree.php');
require_once ('model-sqlite.php');
require_once ('model-mysql.php');
require_once ('model-sqlite-opt.php');
require_once ('model-changesets-sqlite.php');
require_once ("model-bbox.php");
require_once ("config.php");

//The backend database is implemented using a strategy software pattern. This makes
//them nice and modular. The specific strategy to use is determined in this factory.

function OsmDatabase()
{
	//$db = new OsmDatabaseOsmXml();
	//$db = new OsmDatabaseByFileTree();
	//$db = new OsmDatabaseSqlite();
	//$db = new OsmDatabaseSqliteOpt();
	$db = new OsmDatabaseMultiplexer();
	//if(BACKEND_DATABASE == "mysql")
	//	$db = new OsmDatabaseMysql();
	//if(BACKEND_DATABASE == "sqlite")
	//	$db = new OsmDatabaseSqliteOpt();

	$checkPermissions = $db->CheckPermissions();
	if($checkPermissions != 1)
	{
		header('HTTP/1.1 500 Internal Server Error');
		echo $checkPermissions.' is not writable';
		exit();
	}

	return $db;
}

function ChangesetDatabase()
{
	//return new ChangesetDatabaseOsmXml();
	return new ChangesetDatabaseSqlite();
}

class OsmDatabaseMultiplexer extends OsmDatabaseCommon
{

	function __construct()
	{
		$this->masterDb = new OsmDatabaseMysql();
		$this->events = fopen("events.txt","wt");
	}

	function __destruct()
	{
		unset($this->masterDb);
		fclose($this->events);
	}

	function GetElementById($type,$id,$version=null)
	{
		return $this->masterDb->GetElementById($type, $id, $version);
	}

	public function CreateElement($type,$id,$el)
	{
		fwrite($this->events, "Create ".$type." ".$id."\n");
		return $this->masterDb->CreateElement($type,$id,$el);
	}

	public function ModifyElement($type,$id,$el)
	{
		fwrite($this->events, "Modify ".$type." ".$id."\n");
		return $this->masterDb->ModifyElement($type,$id,$el);
	}

	public function DeleteElement($type,$id,$el)
	{
		fwrite($this->events, "Delete ".$type." ".$id."\n");
		return $this->masterDb->DeleteElement($type,$id,$el);
	}

	public function Purge()
	{
		fwrite($this->events, "Purge\n");
		return $this->masterDb->Purge();
	}

	/*function FindModifiedElementsIncParents()
	{
		$out = array('node'=>array(),'way'=>array(),'relation'=>array());

		//Add items in changset
		foreach($this->modifiedEls as $elgroup)
		foreach($elgroup as $el)
		{
			$type = $el->GetType();
			$id = $el->attr['id'];
			$out[$type][$id] = $el;
		}

		//Get parent ways
		$parents = $this->GetParentWaysOfNodes($out['node']);
		foreach($parents as $el)
		{
			$type = $el->GetType();
			$id = $el->attr['id'];
			$out[$type][$id] = $el;		
		}

		//Get parent relations
		//TODO should relations be done recursively?
		$parents = $this->GetParentRelations($out['node']);
		foreach($parents as $el) {$type = $el->GetType();$id = $el->attr['id'];$out[$type][$id] = $el;}	
		$parents = $this->GetParentRelations($out['way']);
		foreach($parents as $el) {$type = $el->GetType();$id = $el->attr['id'];$out[$type][$id] = $el;}	
		$parents = $this->GetParentRelations($out['relation']);
		foreach($parents as $el) {$type = $el->GetType();$id = $el->attr['id'];$out[$type][$id] = $el;}
	
		//print_r($out);
		return $out;
	}*/

	function QueryXapi($type=null,$bbox=null,$key,$value=null)
	{
		//Get ids of matching elements
		//$refs = $this->bboxDb->QueryXapi($type,$bbox,$key,$value);
		//return $refs;
	}

	function Dump($callback)
	{
		//return OsmDatabaseMysql::Dump($callback);
		return $this->masterDb->Dump($callback);
	}

	function CheckPermissions()
	{
		return $this->masterDb->CheckPermissions();
	}

	function GetNodesInBbox($bbox)
	{
		return $this->masterDb->GetNodesInBbox($bbox);
	}

	function GetParentWaysOfNodes(&$nodes)
	{
		return $this->masterDb->GetParentWaysOfNodes($nodes);
	}

	function GetParentRelations(&$els)
	{
		return $this->masterDb->GetParentRelations($els);
	}

}

?>
