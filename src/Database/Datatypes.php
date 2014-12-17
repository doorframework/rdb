<?php

namespace Door\RDB\Database;

/**
 * Description of Types
 *
 * @author serginho
 */
class Datatypes {
	
	protected static $types_global = array
	(
		// SQL-92
		'bit'                           => array('type' => 'string', 'exact' => TRUE),
		'bit varying'                   => array('type' => 'string'),
		'char'                          => array('type' => 'string', 'exact' => TRUE),
		'char varying'                  => array('type' => 'string'),
		'character'                     => array('type' => 'string', 'exact' => TRUE),
		'character varying'             => array('type' => 'string'),
		'date'                          => array('type' => 'string'),
		'dec'                           => array('type' => 'float', 'exact' => TRUE),
		'decimal'                       => array('type' => 'float', 'exact' => TRUE),
		'double precision'              => array('type' => 'float'),
		'float'                         => array('type' => 'float'),
		'int'                           => array('type' => 'int', 'min' => '-2147483648', 'max' => '2147483647'),
		'integer'                       => array('type' => 'int', 'min' => '-2147483648', 'max' => '2147483647'),
		'interval'                      => array('type' => 'string'),
		'national char'                 => array('type' => 'string', 'exact' => TRUE),
		'national char varying'         => array('type' => 'string'),
		'national character'            => array('type' => 'string', 'exact' => TRUE),
		'national character varying'    => array('type' => 'string'),
		'nchar'                         => array('type' => 'string', 'exact' => TRUE),
		'nchar varying'                 => array('type' => 'string'),
		'numeric'                       => array('type' => 'float', 'exact' => TRUE),
		'real'                          => array('type' => 'float'),
		'smallint'                      => array('type' => 'int', 'min' => '-32768', 'max' => '32767'),
		'time'                          => array('type' => 'string'),
		'time with time zone'           => array('type' => 'string'),
		'timestamp'                     => array('type' => 'string'),
		'timestamp with time zone'      => array('type' => 'string'),
		'varchar'                       => array('type' => 'string'),

		// SQL:1999
		'binary large object'               => array('type' => 'string', 'binary' => TRUE),
		'blob'                              => array('type' => 'string', 'binary' => TRUE),
		'boolean'                           => array('type' => 'bool'),
		'char large object'                 => array('type' => 'string'),
		'character large object'            => array('type' => 'string'),
		'clob'                              => array('type' => 'string'),
		'national character large object'   => array('type' => 'string'),
		'nchar large object'                => array('type' => 'string'),
		'nclob'                             => array('type' => 'string'),
		'time without time zone'            => array('type' => 'string'),
		'timestamp without time zone'       => array('type' => 'string'),

		// SQL:2003
		'bigint'    => array('type' => 'int', 'min' => '-9223372036854775808', 'max' => '9223372036854775807'),

		// SQL:2008
		'binary'            => array('type' => 'string', 'binary' => TRUE, 'exact' => TRUE),
		'binary varying'    => array('type' => 'string', 'binary' => TRUE),
		'varbinary'         => array('type' => 'string', 'binary' => TRUE),
	);	
	
	protected static $types_mysql = array
	(
		'blob'                      => array('type' => 'string', 'binary' => TRUE, 'character_maximum_length' => '65535'),
		'bool'                      => array('type' => 'bool'),
		'bigint unsigned'           => array('type' => 'int', 'min' => '0', 'max' => '18446744073709551615'),
		'datetime'                  => array('type' => 'string'),
		'decimal unsigned'          => array('type' => 'float', 'exact' => TRUE, 'min' => '0'),
		'double'                    => array('type' => 'float'),
		'double precision unsigned' => array('type' => 'float', 'min' => '0'),
		'double unsigned'           => array('type' => 'float', 'min' => '0'),
		'enum'                      => array('type' => 'string'),
		'fixed'                     => array('type' => 'float', 'exact' => TRUE),
		'fixed unsigned'            => array('type' => 'float', 'exact' => TRUE, 'min' => '0'),
		'float unsigned'            => array('type' => 'float', 'min' => '0'),
		'geometry'                  => array('type' => 'string', 'binary' => TRUE),
		'int unsigned'              => array('type' => 'int', 'min' => '0', 'max' => '4294967295'),
		'integer unsigned'          => array('type' => 'int', 'min' => '0', 'max' => '4294967295'),
		'longblob'                  => array('type' => 'string', 'binary' => TRUE, 'character_maximum_length' => '4294967295'),
		'longtext'                  => array('type' => 'string', 'character_maximum_length' => '4294967295'),
		'mediumblob'                => array('type' => 'string', 'binary' => TRUE, 'character_maximum_length' => '16777215'),
		'mediumint'                 => array('type' => 'int', 'min' => '-8388608', 'max' => '8388607'),
		'mediumint unsigned'        => array('type' => 'int', 'min' => '0', 'max' => '16777215'),
		'mediumtext'                => array('type' => 'string', 'character_maximum_length' => '16777215'),
		'national varchar'          => array('type' => 'string'),
		'numeric unsigned'          => array('type' => 'float', 'exact' => TRUE, 'min' => '0'),
		'nvarchar'                  => array('type' => 'string'),
		'point'                     => array('type' => 'string', 'binary' => TRUE),
		'real unsigned'             => array('type' => 'float', 'min' => '0'),
		'set'                       => array('type' => 'string'),
		'smallint unsigned'         => array('type' => 'int', 'min' => '0', 'max' => '65535'),
		'text'                      => array('type' => 'string', 'character_maximum_length' => '65535'),
		'tinyblob'                  => array('type' => 'string', 'binary' => TRUE, 'character_maximum_length' => '255'),
		'tinyint'                   => array('type' => 'int', 'min' => '-128', 'max' => '127'),
		'tinyint unsigned'          => array('type' => 'int', 'min' => '0', 'max' => '255'),
		'tinytext'                  => array('type' => 'string', 'character_maximum_length' => '255'),
		'year'                      => array('type' => 'string'),
	);			
	
	public static function get($type)
	{
		return isset(self::$types_global[$type]) ? self::$types_global[$type] : array();
	}
	
	public static function get_mysql($type)
	{
		return isset(self::$types_mysql[$type]) ? self::$types_mysql[$type] : self::get($type);
	}
}
