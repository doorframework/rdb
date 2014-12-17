Fork of kohana/database module. Maked it for using standalone.

Usage example:

	//creating connection
	$database = new Door\RDB\Database\MySQL(array(
		'connection' => array(
			'hostname'   => 'localhost',
			'database'   => 'databasename',
			'username'   => 'username',
			'password'   => 'password',
			'persistent' => FALSE,
		),
		'table_prefix' => '',
		'charset'      => 'utf8',
		'caching'      => FALSE,
	));

	//performing queries
	$database->list_tables();
	$database->list_columns('users');
	$database->query(Door\RDB\Database::SELECT, "select * from roles")
		->execute()
		->as_array();
	$database->select("id,name,email")
		->from('users')
		->where('registered',">","2014-01-01")
		->execute()
		->as_array();
	$database->delete('users')->where('id', '=', 25)->execute();
