<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Door\RDB\Database;

/**
 * Description of Util
 *
 * @author serginho
 */
abstract class Util {
	
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
	 * Extracts the text between parentheses, if any.
	 *
	 *     // Returns: array('CHAR', '6')
	 *     list($type, $length) = $db->_parse_type('CHAR(6)');
	 *
	 * @param   string  $type
	 * @return  array   list containing the type and length, if any
	 */
	protected function _parse_type($type)
	{
		if (($open = strpos($type, '(')) === FALSE)
		{
			// No length specified
			return array($type, NULL);
		}

		// Closing parenthesis
		$close = strrpos($type, ')', $open);

		// Length without parentheses
		$length = substr($type, $open + 1, $close - 1 - $open);

		// Type without the length
		$type = substr($type, 0, $open).substr($type, $close + 1);

		return array($type, $length);
	}

	/**
	 * Return the table prefix defined in the current configuration.
	 *
	 *     $prefix = $db->table_prefix();
	 *
	 * @return  string
	 */
	public function table_prefix()
	{
		return $this->_config['table_prefix'];
	}

	/**
	 * Quote a value for an SQL query.
	 *
	 *     $db->quote(NULL);   // 'NULL'
	 *     $db->quote(10);     // 10
	 *     $db->quote('fred'); // 'fred'
	 *
	 * Objects passed to this function will be converted to strings.
	 * [Database_Expression] objects will be compiled.
	 * [Database_Query] objects will be compiled and converted to a sub-query.
	 * All other objects will be converted using the `__toString` method.
	 *
	 * @param   mixed   $value  any value to quote
	 * @return  string
	 * @uses    Database::escape
	 */
	public function quote($value)
	{
		if ($value === NULL)
		{
			return 'NULL';
		}
		elseif ($value === TRUE)
		{
			return "'1'";
		}
		elseif ($value === FALSE)
		{
			return "'0'";
		}
		elseif (is_object($value))
		{
			if ($value instanceof Query)
			{
				// Create a sub-query
				return '('.$value->compile($this).')';
			}
			elseif ($value instanceof Expression)
			{
				// Compile the expression
				return $value->compile($this);
			}
			else
			{
				// Convert the object to a string
				return $this->quote( (string) $value);
			}
		}
		elseif (is_array($value))
		{
			return '('.implode(', ', array_map(array($this, __FUNCTION__), $value)).')';
		}
		elseif (is_int($value))
		{
			return (int) $value;
		}
		elseif (is_float($value))
		{
			// Convert to non-locale aware float to prevent possible commas
			return sprintf('%F', $value);
		}

		return $this->escape($value);
	}

	/**
	 * Quote a database column name and add the table prefix if needed.
	 *
	 *     $column = $db->quote_column($column);
	 *
	 * You can also use SQL methods within identifiers.
	 *
	 *     $column = $db->quote_column(DB::expr('COUNT(`column`)'));
	 *
	 * Objects passed to this function will be converted to strings.
	 * [Database_Expression] objects will be compiled.
	 * [Database_Query] objects will be compiled and converted to a sub-query.
	 * All other objects will be converted using the `__toString` method.
	 *
	 * @param   mixed   $column  column name or array(column, alias)
	 * @return  string
	 * @uses    Database::quote_identifier
	 * @uses    Database::table_prefix
	 */
	public function quote_column($column)
	{
		// Identifiers are escaped by repeating them
		$escaped_identifier = $this->_identifier.$this->_identifier;

		if (is_array($column))
		{
			list($column, $alias) = $column;
			$alias = str_replace($this->_identifier, $escaped_identifier, $alias);
		}

		if ($column instanceof Query)
		{
			// Create a sub-query
			$column = '('.$column->compile($this).')';
		}
		elseif ($column instanceof Expression)
		{
			// Compile the expression
			$column = $column->compile($this);
		}
		else
		{
			// Convert to a string
			$column = (string) $column;

			$column = str_replace($this->_identifier, $escaped_identifier, $column);

			if ($column === '*')
			{
				return $column;
			}
			elseif (strpos($column, '.') !== FALSE)
			{
				$parts = explode('.', $column);

				if ($prefix = $this->table_prefix())
				{
					// Get the offset of the table name, 2nd-to-last part
					$offset = count($parts) - 2;

					// Add the table prefix to the table name
					$parts[$offset] = $prefix.$parts[$offset];
				}

				foreach ($parts as & $part)
				{
					if ($part !== '*')
					{
						// Quote each of the parts
						$part = $this->_identifier.$part.$this->_identifier;
					}
				}

				$column = implode('.', $parts);
			}
			else
			{
				$column = $this->_identifier.$column.$this->_identifier;
			}
		}

		if (isset($alias))
		{
			$column .= ' AS '.$this->_identifier.$alias.$this->_identifier;
		}

		return $column;
	}

	/**
	 * Quote a database table name and adds the table prefix if needed.
	 *
	 *     $table = $db->quote_table($table);
	 *
	 * Objects passed to this function will be converted to strings.
	 * [Expression] objects will be compiled.
	 * [Query] objects will be compiled and converted to a sub-query.
	 * All other objects will be converted using the `__toString` method.
	 *
	 * @param   mixed   $table  table name or array(table, alias)
	 * @return  string
	 * @uses    Database::quote_identifier
	 * @uses    Database::table_prefix
	 */
	public function quote_table($table)
	{
		// Identifiers are escaped by repeating them
		$escaped_identifier = $this->_identifier.$this->_identifier;

		if (is_array($table))
		{
			list($table, $alias) = $table;
			$alias = str_replace($this->_identifier, $escaped_identifier, $alias);
		}

		if ($table instanceof Query)
		{
			// Create a sub-query
			$table = '('.$table->compile($this).')';
		}
		elseif ($table instanceof Expression)
		{
			// Compile the expression
			$table = $table->compile($this);
		}
		else
		{
			// Convert to a string
			$table = (string) $table;

			$table = str_replace($this->_identifier, $escaped_identifier, $table);

			if (strpos($table, '.') !== FALSE)
			{
				$parts = explode('.', $table);

				if ($prefix = $this->table_prefix())
				{
					// Get the offset of the table name, last part
					$offset = count($parts) - 1;

					// Add the table prefix to the table name
					$parts[$offset] = $prefix.$parts[$offset];
				}

				foreach ($parts as & $part)
				{
					// Quote each of the parts
					$part = $this->_identifier.$part.$this->_identifier;
				}

				$table = implode('.', $parts);
			}
			else
			{
				// Add the table prefix
				$table = $this->_identifier.$this->table_prefix().$table.$this->_identifier;
			}
		}

		if (isset($alias))
		{
			// Attach table prefix to alias
			$table .= ' AS '.$this->_identifier.$this->table_prefix().$alias.$this->_identifier;
		}

		return $table;
	}

	/**
	 * Quote a database identifier
	 *
	 * Objects passed to this function will be converted to strings.
	 * [Expression] objects will be compiled.
	 * [Query] objects will be compiled and converted to a sub-query.
	 * All other objects will be converted using the `__toString` method.
	 *
	 * @param   mixed   $value  any identifier
	 * @return  string
	 */
	public function quote_identifier($value)
	{
		// Identifiers are escaped by repeating them
		$escaped_identifier = $this->_identifier.$this->_identifier;

		if (is_array($value))
		{
			list($value, $alias) = $value;
			$alias = str_replace($this->_identifier, $escaped_identifier, $alias);
		}

		if ($value instanceof Query)
		{
			// Create a sub-query
			$value = '('.$value->compile($this).')';
		}
		elseif ($value instanceof Expression)
		{
			// Compile the expression
			$value = $value->compile($this);
		}
		else
		{
			// Convert to a string
			$value = (string) $value;

			$value = str_replace($this->_identifier, $escaped_identifier, $value);

			if (strpos($value, '.') !== FALSE)
			{
				$parts = explode('.', $value);

				foreach ($parts as & $part)
				{
					// Quote each of the parts
					$part = $this->_identifier.$part.$this->_identifier;
				}

				$value = implode('.', $parts);
			}
			else
			{
				$value = $this->_identifier.$value.$this->_identifier;
			}
		}

		if (isset($alias))
		{
			$value .= ' AS '.$this->_identifier.$alias.$this->_identifier;
		}

		return $value;
	}	
	
	/**
	 * Count the number of records in a table.
	 *
	 *     // Get the total number of records in the "users" table
	 *     $count = $db->count_records('users');
	 *
	 * @param   mixed    $table  table name string or array(query, alias)
	 * @return  integer
	 */
	public function count_records($table)
	{
		// Quote the table name
		$table = $this->quote_table($table);

		return $this->perfrorm(Database::SELECT, 'SELECT COUNT(*) AS total_row_count FROM '.$table, FALSE)
			->get('total_row_count');
	}
	
	/**
	 * Returns a normalized array describing the SQL data type
	 *
	 *     $db->datatype('char');
	 *
	 * @param   string  $type  SQL data type
	 * @return  array
	 */
	public function datatype($type)
	{
		return Datatypes::get($type);
	}	
	
}
