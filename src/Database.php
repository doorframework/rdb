<?php

namespace Door\RDB;
use Door\RDB\Database\Query;
use Door\RDB\Database\Expression;

/**
 * Database connection wrapper/helper.
 *
 * You may get a database instance using `Database::instance('name')` where
 * name is the [config](database/config) group.
 *
 * This class provides connection instance management via Database Drivers, as
 * well as quoting, escaping and other related functions. Querys are done using
 * [Database_Query] and [Database_Query_Builder] objects, which can be easily
 * created using the [DB] helper class.
 *
 * @package    Kohana/Database
 * @category   Base
 * @author     Kohana Team
 * @copyright  (c) 2008-2012 Kohana Team
 * @license    http://kohanaphp.com/license
 */
abstract class Database {

	// Query types
	const SELECT =  1;
	const INSERT =  2;
	const UPDATE =  3;
	const DELETE =  4;

	/**
	 * @var  string  the last query executed
	 */
	public $last_query;

	// Character that is used to quote identifiers
	protected $_identifier = '"';

	// Instance name
	protected $_instance;

	// Raw server connection
	protected $_connection;

	// Configuration array
	protected $_config;
	
	private $profiler = null;

	/**
	 * Stores the database configuration locally and name the instance.
	 *
	 * [!!] This method cannot be accessed directly, you must use [Database::instance].
	 *
	 * @return  void
	 */
	public function __construct(array $config)
	{
		// Store the config locally
		$this->_config = $config;

		if (empty($this->_config['table_prefix']))
		{
			$this->_config['table_prefix'] = '';
		}
	}

	/**
	 * Disconnect from the database when the object is destroyed.
	 *
	 *     // Destroy the database instance
	 *     unset(Database::instances[(string) $db], $db);
	 *
	 * [!!] Calling `unset($db)` is not enough to destroy the database, as it
	 * will still be stored in `Database::$instances`.
	 *
	 * @return  void
	 */
	public function __destruct()
	{
		$this->disconnect();
	}

	/**
	 * Connect to the database. This is called automatically when the first
	 * query is executed.
	 *
	 *     $db->connect();
	 *
	 * @throws  Database_Exception
	 * @return  void
	 */
	abstract public function connect();

	/**
	 * Disconnect from the database. This is called automatically by [Database::__destruct].
	 * Clears the database instance from [Database::$instances].
	 *
	 *     $db->disconnect();
	 *
	 * @return  boolean
	 */
	public function disconnect()
	{
		return TRUE;
	}

	/**
	 * Set the connection character set. This is called automatically by [Database::connect].
	 *
	 *     $db->set_charset('utf8');
	 *
	 * @throws  Database_Exception
	 * @param   string   $charset  character set name
	 * @return  void
	 */
	abstract public function set_charset($charset);

	/**
	 * Perform an SQL query of the given type.
	 *
	 *     // Make a SELECT query and use objects for results
	 *     $db->query(Database::SELECT, 'SELECT * FROM groups', TRUE);
	 *
	 *     // Make a SELECT query and use "Model_User" for the results
	 *     $db->query(Database::SELECT, 'SELECT * FROM users LIMIT 1', 'Model_User');
	 *
	 * @param   integer  $type       Database::SELECT, Database::INSERT, etc
	 * @param   string   $sql        SQL query
	 * @param   mixed    $as_object  result object class string, TRUE for stdClass, FALSE for assoc array
	 * @param   array    $params     object construct parameters for result class
	 * @return  object   Database_Result for SELECT queries
	 * @return  array    list (insert id, row count) for INSERT queries
	 * @return  integer  number of affected rows for all other queries
	 */
	abstract public function perform($type, $sql, $as_object = FALSE, array $params = NULL);

	/**
	 * Start a SQL transaction
	 *
	 *     // Start the transactions
	 *     $db->begin();
	 *
	 *     try {
	 *          $db->insert('users')->values($user1)...
	 *          $db->insert('users')->values($user2)...
	 *          // Insert successful commit the changes
	 *          $db->commit();
	 *     }
	 *     catch (Database_Exception $e)
	 *     {
	 *          // Insert failed. Rolling back changes...
	 *          $db->rollback();
	 *      }
	 *
	 * @param string $mode  transaction mode
	 * @return  boolean
	 */
	abstract public function begin($mode = NULL);

	/**
	 * Commit the current transaction
	 *
	 *     // Commit the database changes
	 *     $db->commit();
	 *
	 * @return  boolean
	 */
	abstract public function commit();

	/**
	 * Abort the current transaction
	 *
	 *     // Undo the changes
	 *     $db->rollback();
	 *
	 * @return  boolean
	 */
	abstract public function rollback();

	/**
	 * List all of the tables in the database. Optionally, a LIKE string can
	 * be used to search for specific tables.
	 *
	 *     // Get all tables in the current database
	 *     $tables = $db->list_tables();
	 *
	 *     // Get all user-related tables
	 *     $tables = $db->list_tables('user%');
	 *
	 * @param   string   $like  table to search for
	 * @return  array
	 */
	abstract public function list_tables($like = NULL);

	/**
	 * Lists all of the columns in a table. Optionally, a LIKE string can be
	 * used to search for specific fields.
	 *
	 *     // Get all columns from the "users" table
	 *     $columns = $db->list_columns('users');
	 *
	 *     // Get all name-related columns
	 *     $columns = $db->list_columns('users', '%name%');
	 *
	 *     // Get the columns from a table that doesn't use the table prefix
	 *     $columns = $db->list_columns('users', NULL, FALSE);
	 *
	 * @param   string  $table       table to get columns from
	 * @param   string  $like        column to search for
	 * @param   boolean $add_prefix  whether to add the table prefix automatically or not
	 * @return  array
	 */
	abstract public function list_columns($table, $like = NULL, $add_prefix = TRUE);



	/**
	 * Sanitize a string by escaping characters that could cause an SQL
	 * injection attack.
	 *
	 *     $value = $db->escape('any string');
	 *
	 * @param   string   $value  value to quote
	 * @return  string
	 */
	abstract public function escape($value);
	
	/**
	 * Create a new [Database_Query] of the given type.
	 *
	 *     // Create a new SELECT query
	 *     $query = $db->query(Database::SELECT, 'SELECT * FROM users');
	 *
	 *     // Create a new DELETE query
	 *     $query = $db->query(Database::DELETE, 'DELETE FROM users WHERE id = 5');
	 *
	 * Specifying the type changes the returned result. When using
	 * `Database::SELECT`, a [Database_Query_Result] will be returned.
	 * `Database::INSERT` queries will return the insert id and number of rows.
	 * For all other queries, the number of affected rows is returned.
	 *
	 * @param   integer  $type  type: Database::SELECT, Database::UPDATE, etc
	 * @param   string   $sql   SQL statement
	 * @return  Database\Query
	 */
	public function query($type, $sql)
	{
		return new Database\Query($this, $type, $sql);
	}

	/**
	 * Create a new [Database_Query_Builder_Select]. Each argument will be
	 * treated as a column. To generate a `foo AS bar` alias, use an array.
	 *
	 *     // SELECT id, username
	 *     $query = $db->select('id', 'username');
	 *
	 *     // SELECT id AS user_id
	 *     $query = $db->select(array('id', 'user_id'));
	 *
	 * @param   mixed   $columns  column name or array($column, $alias) or object
	 * @return  Database_Query_Builder_Select
	 */
	public function select($columns = NULL)
	{
		return new Database\Query\Builder\Select($this, func_get_args());
	}

	/**
	 * Create a new [Database_Query_Builder_Select] from an array of columns.
	 *
	 *     // SELECT id, username
	 *     $query = $db->select_array(array('id', 'username'));
	 *
	 * @param   array   $columns  columns to select
	 * @return  Database\Query\Builder\Select
	 */
	public function select_array(array $columns = NULL)
	{
		return new Database\Query\Builder\Select($this, $columns);
	}

	/**
	 * Create a new [Database_Query_Builder_Insert].
	 *
	 *     // INSERT INTO users (id, username)
	 *     $query = $db->insert('users', array('id', 'username'));
	 *
	 * @param   string  $table    table to insert into
	 * @param   array   $columns  list of column names or array($column, $alias) or object
	 * @return  Database\Query\Builder\Insert
	 */
	public function insert($table = NULL, array $columns = NULL)
	{
		return new Database\Query\Builder\Insert($this, $table, $columns);
	}

	/**
	 * Create a new [Database_Query_Builder_Update].
	 *
	 *     // UPDATE users
	 *     $query = $db->update('users');
	 *
	 * @param   string  $table  table to update
	 * @return  Database\Query\Builder\Update
	 */
	public function update($table = NULL)
	{
		return new Database\Query\Builder\Update($this, $table);
	}

	/**
	 * Create a new [Database_Query_Builder_Delete].
	 *
	 *     // DELETE FROM users
	 *     $query = $db->delete('users');
	 *
	 * @param   string  $table  table to delete from
	 * @return  Database\Query\Builder\Delete
	 */
	public function delete($table = NULL)
	{
		return new Database\Query\Builder\Delete($this, $table);
	}

	/**
	 * Create a new [Database_Expression] which is not escaped. An expression
	 * is the only way to use SQL functions within query builders.
	 *
	 *     $expression = $db->expr('COUNT(users.id)');
	 *     $query = $db->update('users')->set(array('login_count' => $db->expr('login_count + 1')))->where('id', '=', $id);
	 *     $users = ORM::factory('user')->where($db->expr("BINARY `hash`"), '=', $hash)->find();
	 *
	 * @param   string  $string  expression
	 * @param   array   parameters
	 * @return  Database\Expression
	 */
	public function expr($string, $parameters = array())
	{
		return new Database\Expression($this, $string, $parameters);
	}	
	
	public function setProfiler(Profiler $profiler)
	{
		$this->profiler = $profiler;
	}
	
	/**
	 * @return Profiler
	 */
	public function profiler()
	{
		if($this->profiler == null)
		{
			$this->profiler = new Profiler();
		}
		return $this->profiler;
	}

} // End Connection
