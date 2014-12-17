<?php

namespace Door\RDB\Database;
use Door\RDB\Database;

/**
 * Base class for all classes that depends from database
 */
class Dependent {
	
	/**
	 * @var Database
	 */
	private $_db;
	
	public function __construct(Database $db) {
		$this->set_database($db);
	}
	
	/**
	 * @return Database
	 */
	public function db()
	{
		return $this->_db;
	}
	
	public function set_database(Database $db)
	{
		$this->_db = $db;
	}
	
}
