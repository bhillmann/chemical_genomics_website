<?php
	###
	# Mysql php page for chemical genomics
	# Will log in and exit the database as required.
	# Dec 6, 2012
	###
	
	# Connect to the database
	function database_connect(){
		$username="chemgenom_www";
		$password="rg3EF?:wi";
		$database="chemgenome";

		mysql_connect('localhost',$username,$password);
		if(!mysql_select_db($database)){return false;}
		
		return true;
	}
	
	# Disconnect
	function database_disconnect(){
		mysql_close();
	}
	
	# Make sure any function going into here has been escaped, the safe command is readily available to make things safe.
	function database_select_search($search, $table, $where = "", $sortedBy = "", $sort = "DESC"){	
		$query = "SELECT $search FROM $table ";
		if($where != ""){ $query .= "WHERE $where "; }
		if($sortedBy != ""){ $query .= "ORDER BY $sortedBy $sort "; }
		
		return mysql_query($query);
	}
	
	# Make sure any function going into here has been escaped, the safe command is readily available to make things safe.
	function database_select_distinct_search($search, $table, $where = "", $sortedBy = "", $sort = "DESC"){	
		$query = "SELECT DISTINCT $search FROM $table ";
		if($where != ""){ $query .= "WHERE $where "; }
		if($sortedBy != ""){ $query .= "ORDER BY $sortedBy $sort "; }
		
		return mysql_query($query);
	}
	
	# Count the number of rows in query results
	function count_rows($result){
		return mysql_numrows($result);
	}
	
	# make a user submitted value safe for mysql
	function safe($value){
		return mysql_real_escape_string($value);
	}

	function array_safe($array){
		$newArray = array();
		foreach($array as $value){
			array_push($newArray, safe($value));
		}
		return $newArray;
	}

	# Build up a list useful for IN statements
	function build_IN_LIST($array){
		$statement = "( ";
		$needComma = false;
		foreach($array as $value){
			$statement .= ($needComma?", ":"").'"'.$value.'"';
			$needComma = true;
		}
		$statement .= " )";
		return $statement;
	}
	# Build up where statements in the AND or OR case. Real handy.
	function build_OR($array, $prefix = "", $suffix = ""){
		$statement = "( ";		
		$needOr = false;
		foreach($array as $value){
			$statement .= ($needOr?"OR ":"").$prefix.$value.$suffix." ";
			$needOr = true;
		}
		$statement .= ")";
		return $statement;
	}

	function build_AND($array, $prefix = "", $suffix = ""){
		$statement = "( ";		
		$needAnd = false;
		foreach($array as $value){
			$statement .= ($needAnd?"AND ":"").$prefix.$value.$suffix." ";
			$needAnd = true;
		}
		$statement .= ")";
		return $statement;
	}
	
?>
