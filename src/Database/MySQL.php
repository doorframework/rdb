<?php

namespace Door\RDB\Database;
use Door\RDB\Database;

/**
 * MySQL database connection.
 *
 * @package    Kohana/Database
 * @category   Drivers
 * @author     Kohana Team
 * @copyright  (c) 2008-2009 Kohana Team
 * @license    http://kohanaphp.com/license
 */
class MySQL extends Database {

	// Identifier for this connection within the PHP driver
	protected $_connection_id;

	// MySQL uses a backtick for identifiers
	protected $_identifier = '`';

	public function connect()
	{
		if ($this->_connection)
			return;
		
		$conn_cfg = $this->_config['connection'];
		
		$database = isset($conn_cfg['database']) ? $conn_cfg['database'] : '';
		$hostname = isset($conn_cfg['hostname']) ? $conn_cfg['hostname'] : '';
		$username = isset($conn_cfg['username']) ? $conn_cfg['username'] : '';
		$password = isset($conn_cfg['password']) ? $conn_cfg['password'] : '';
		$persistent = array_key_exists('persistent', $conn_cfg) ? $conn_cfg['persistent'] : FALSE;

		// Prevent this information from showing up in traces
		unset($this->_config['connection']['username'], $this->_config['connection']['password']);

		try
		{
			if ($persistent)
			{
				// Create a persistent connection
				$this->_connection = mysql_pconnect($hostname, $username, $password);
			}
			else
			{
				// Create a connection and force it to be a new link
				$this->_connection = mysql_connect($hostname, $username, $password, TRUE);
			}
		}
		catch (\Exception $e)
		{
			// No connection exists
			$this->_connection = NULL;
			throw new Exception($e->getMessage(), $e->getCode(), $e);
		}

		// \xFF is a better delimiter, but the PHP driver uses underscore
		$this->_connection_id = sha1($hostname.'_'.$username.'_'.$password);

		$this->_select_db($database);

		if ( ! empty($this->_config['charset']))
		{
			// Set the character set
			$this->set_charset($this->_config['charset']);
		}

		if ( ! empty($this->_config['connection']['variables']))
		{
			// Set session variables
			$variables = array();

			foreach ($this->_config['connection']['variables'] as $var => $val)
			{
				$variables[] = 'SESSION '.$var.' = '.$this->quote($val);
			}

			mysql_query('SET '.implode(', ', $variables), $this->_connection);
		}
	}

	/**
	 * Select the database
	 *
	 * @param   string  $database Database
	 * @return  void
	 */
	protected function _select_db($database)
	{
		if (false == mysql_select_db($database, $this->_connection))
		{
			// Unable to select database
			throw new Exception(mysql_error($this->_connection), mysql_errno($this->_connection));
		}
	}

	public function disconnect()
	{
		try
		{
			// Database is assumed disconnected
			$status = TRUE;

			if (is_resource($this->_connection))
			{
				if ($status = mysql_close($this->_connection))
				{
					// Clear the connection
					$this->_connection = NULL;

					// Clear the instance
					parent::disconnect();
				}
			}
		}
		catch (\Exception $e)
		{
			// Database is probably not disconnected
			$status = ! is_resource($this->_connection);
		}

		return $status;
	}

	public function set_charset($charset)
	{
		// Make sure the database is connected
		$this->_connection or $this->connect();

		$status = mysql_set_charset($charset, $this->_connection);

		if ($status === FALSE)
		{
			throw new Exception(mysql_error($this->_connection), mysql_errno($this->_connection));
		}
	}

	public function perform($type, $sql, $as_object = FALSE, array $params = NULL)
	{
		// Make sure the database is connected
		$this->_connection or $this->connect();

		$benchmark = $this->profiler()->start("Database", $sql);

		if ( ! empty($this->_config['connection']['persistent']) AND $this->_config['connection']['database'] !== Database_MySQL::$_current_databases[$this->_connection_id])
		{
			// Select database on persistent connections
			$this->_select_db($this->_config['connection']['database']);
		}

		// Execute the query
		if (($result = mysql_query($sql, $this->_connection)) === FALSE)
		{
			$this->profiler()->delete($benchmark);
			throw new Exception(mysql_error($this->_connection)."[ $query ]", mysql_errno($this->_connection));
		}

		$this->profiler()->stop($benchmark);

		// Set the last query
		$this->last_query = $sql;

		if ($type === Database::SELECT)
		{
			// Return an iterator of results
			return new MySQL\Result($result, $sql, $as_object, $params);
		}
		elseif ($type === Database::INSERT)
		{
			// Return a list of insert id and rows created
			return array(
				mysql_insert_id($this->_connection),
				mysql_affected_rows($this->_connection),
			);
		}
		else
		{
			// Return the number of rows affected
			return mysql_affected_rows($this->_connection);
		}
	}

	public function datatype($type)
	{	
		return Datatypes::get_mysql(str_replace(' zerofill', '', $type));
	}

	/**
	 * Start a SQL transaction
	 *
	 * @link http://dev.mysql.com/doc/refman/5.0/en/set-transaction.html
	 *
	 * @param string $mode  Isolation level
	 * @return boolean
	 */
	public function begin($mode = NULL)
	{
		// Make sure the database is connected
		$this->_connection or $this->connect();

		if ($mode AND ! mysql_query("SET TRANSACTION ISOLATION LEVEL $mode", $this->_connection))
		{
			throw new Exception(mysql_error($this->_connection), mysql_errno($this->_connection));
		}

		return (bool) mysql_query('START TRANSACTION', $this->_connection);
	}

	/**
	 * Commit a SQL transaction
	 *
	 * @return boolean
	 */
	public function commit()
	{
		// Make sure the database is connected
		$this->_connection or $this->connect();

		return (bool) mysql_query('COMMIT', $this->_connection);
	}

	/**
	 * Rollback a SQL transaction
	 *
	 * @return boolean
	 */
	public function rollback()
	{
		// Make sure the database is connected
		$this->_connection or $this->connect();

		return (bool) mysql_query('ROLLBACK', $this->_connection);
	}

	public function list_tables($like = NULL)
	{
		if (is_string($like))
		{
			// Search for table names
			$result = $this->perform(Database::SELECT, 'SHOW TABLES LIKE '.$this->quote($like), FALSE);
		}
		else
		{
			// Find all table names
			$result = $this->perform(Database::SELECT, 'SHOW TABLES', FALSE);
		}

		$tables = array();
		foreach ($result as $row)
		{
			$tables[] = reset($row);
		}

		return $tables;
	}

	public function list_columns($table, $like = NULL, $add_prefix = TRUE)
	{
		// Quote the table name
		$table = ($add_prefix === TRUE) ? $this->quote_table($table) : $table;

		if (is_string($like))
		{
			// Search for column names
			$result = $this->perform(Database::SELECT, 'SHOW FULL COLUMNS FROM '.$table.' LIKE '.$this->quote($like), FALSE);
		}
		else
		{
			// Find all column names
			$result = $this->perform(Database::SELECT, 'SHOW FULL COLUMNS FROM '.$table, FALSE);
		}

		$count = 0;
		$columns = array();
		foreach ($result as $row)
		{
			list($type, $length) = $this->_parse_type($row['Type']);

			$column = $this->datatype($type);

			$column['column_name']      = $row['Field'];
			$column['column_default']   = $row['Default'];
			$column['data_type']        = $type;
			$column['is_nullable']      = ($row['Null'] == 'YES');
			$column['ordinal_position'] = ++$count;

			switch ($column['type'])
			{
				case 'float':
					if (isset($length))
					{
						list($column['numeric_precision'], $column['numeric_scale']) = explode(',', $length);
					}
				break;
				case 'int':
					if (isset($length))
					{
						// MySQL attribute
						$column['display'] = $length;
					}
				break;
				case 'string':
					switch ($column['data_type'])
					{
						case 'binary':
						case 'varbinary':
							$column['character_maximum_length'] = $length;
						break;
						case 'char':
						case 'varchar':
							$column['character_maximum_length'] = $length;
						case 'text':
						case 'tinytext':
						case 'mediumtext':
						case 'longtext':
							$column['collation_name'] = $row['Collation'];
						break;
						case 'enum':
						case 'set':
							$column['collation_name'] = $row['Collation'];
							$column['options'] = explode('\',\'', substr($length, 1, -1));
						break;
					}
				break;
			}

			// MySQL attributes
			$column['comment']      = $row['Comment'];
			$column['extra']        = $row['Extra'];
			$column['key']          = $row['Key'];
			$column['privileges']   = $row['Privileges'];

			$columns[$row['Field']] = $column;
		}

		return $columns;
	}

	public function escape($value)
	{
		// Make sure the database is connected
		$this->_connection or $this->connect();

		if (($value = mysql_real_escape_string( (string) $value, $this->_connection)) === FALSE)
		{
			throw new Exception(mysql_error($this->_connection), mysql_errno($this->_connection));
		}

		// SQL standard is to use single-quotes for all values
		return "'$value'";
	}

} // End Database_MySQL
