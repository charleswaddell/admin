<?php

require_once("MDB2.php");
require_once("Admin/AdminDBField.php");
require_once("Swat/SwatTreeNode.php");

/**
 * Database helper class
 *
 * Static convenience methods for working with a database.
 *
 * @package Admin
 * @copyright silverorange 2004
 */
class AdminDB {
	
	/**
	 * Update a field
	 *
 	 * Convenience method to update a single database field for one or more 
	 * rows. One convenient use of this method is for processing SwatActions
	 * that change a single database field.
	 *
	 * @param MDB2_Driver_Common $db The database connection.
	 *
	 * @param string $table The database table to query.
	 *
	 * @param string $field The name of the database field to update. Can be 
	 *        given in the form type:name where type is a standard MDB2 
	 *        datatype. If type is ommitted, then integer is assummed for this 
	 *        field.
	 *
	 * @param mixed $value The value to store in database field $field. The 
	 *        type should correspond to the type of $field.
	 *
	 * @param string $id_field The name of the database field that contains the
	 *        the id. Can be given in the form type:name where type is a
	 *        standard MDB2 datatype. If type is ommitted, then integer is 
	 *        assummed for this field.
	 *
	 * @param array $ids An array of identifiers corresponding to the database
	 *        rows to be updated. The type of the individual identifiers should 
	 *        correspond to the type of $id_field.
	 */
	public static function update($db, $table, $field, $value, $id_field, $ids) {

		if (count($ids) == 0)
			return;

		$field = new AdminDBField($field, 'integer');
		$id_field = new AdminDBField($id_field, 'integer');

		$sql = 'update %s set %s = %s where %s in (%s)';

		foreach ($ids as &$id)
			$id = $db->quote($id, $id_field->type);

		$id_list = implode(',', $ids);

		$sql = sprintf($sql, 
			$table,
			$field->name,
			$db->quote($value, $field->type),
			$id_field->name,
			$id_list);

		$db->query($sql);
	}

	/**
	 * Update a binding table
	 *
 	 * Convenience method to update rows in a binding table. It will delete 
	 * and insert rows as necessary.
	 *
	 * @param MDB2_Driver_Common $db The database connection.
	 *
	 * @param string $table The binding table to update.
	 *
	 * @param string $id_field The name of the binding table field that contains 
	 *        the fixed value.  Can be given in the form type:name where type is 
	 *        a standard MDB2 datatype. If type is ommitted, then integer is 
	 *        assummed for this field.
	 *
	 * @param mixed $id The value to store in the $id_field. The type should 
	 *        correspond to the type of $id_field.
	 *
	 * @param string $value_field The name of the binding table field that contains 
	 *        the values from the bound table.  Can be given in the form type:name 
	 *        where type is a standard MDB2 datatype. If type is ommitted, then 
	 *        integer is assummed for this field.
	 *
	 * @param array $values An array of values that should be stored in the 
	 *        $value_field. The type of the individual values should 
	 *        correspond to the type of $value_field.
	 *
	 * @param string $table The table bound through the binding table.
	 *
	 * @param string $id_field The database field in the bound table that the 
	 *        binding table references.
	 */
	public static function bindingUpdate($db, $table, $id_field, $id, $value_field, 
		$values, $bound_table, $bound_field) {

		$id_field = new AdminDBField($id_field, 'integer');
		$value_field = new AdminDBField($value_field, 'integer');
		$bound_field = new AdminDBField($bound_field, 'integer');

		$delete_sql = 'delete from %s where %s = %s';

		$delete_sql = sprintf($delete_sql, 
			$table,
			$id_field->name,
			$db->quote($id, $id_field->type));

		if (count($values)) {

			foreach ($values as &$value)
				$value = $db->quote($value, $value_field->type);

			$value_list = implode(',', $values);

			$insert_sql = 'insert into %s (%s, %s) select %s, %s from %s '.
				'where %s not in (select %s from %s where %s = %s) and %s in (%s)';

			$insert_sql = sprintf($insert_sql, 
				$table,
				$id_field->name,
				$value_field->name,
				$db->quote($id, $id_field->type),
				$bound_field->name,
				$bound_table,
				$bound_field->name,
				$value_field->name,
				$table,
				$id_field->name,
				$db->quote($id, $id_field->type),
				$bound_field->name,
				$value_list);

			$delete_sql .= sprintf(' and %s not in (%s)',
				$value_field->name,
				$value_list);
		}

		$db->beginTransaction();

		if (count($values))
			$db->query($insert_sql);

		$db->query($delete_sql);
		$db->commit();

	}

	public static function bindingQuery($db, $table, $id_field, $id, $value_field) {
		$id_field = new AdminDBField($id_field, 'integer');
		$value_field = new AdminDBField($value_field, 'integer');

		$sql = 'select %s from %s where %s = %s';
		$sql = sprintf($sql, $value_field->name, $table, $id_field->name, $id);

		$rs = $db->query($sql, array($value_field->type));

		if (MDB2::isError($rs))
			throw new Exception($rs->getMessage());

		$values = array();

		while ($row = $rs->fetchRow(MDB2_FETCHMODE_OBJECT)) {
			$value_field_name = $value_field->name;
			$values[] = $row->$value_field_name;
		}

		return $values;
	}


	/**
	 * Query for an option array
	 *
 	 * Convenience method to query for a set of options, each consisting of
	 * an id and a title. The returned option array in the form of
	 * $id => $title can be passed directly to other classes, such as 
	 * SwatFlydown for example.
	 *
	 * @param MDB2_Driver_Common $db The database connection.
	 *
	 * @param string $table The database table to query.
	 *
	 * @param string $title_field The name of the database field to query for 
	 *        the title. Can be given in the form type:name where type is a
	 *        standard MDB2 datatype. If type is ommitted, then text is 
	 *        assummed for this field.
	 *
	 * @param string $id_field The name of the database field to query for 
	 *        the id. Can be given in the form type:name where type is a
	 *        standard MDB2 datatype. If type is ommitted, then integer is 
	 *        assummed for this field.
	 *
	 * @param string $order_by_clause Optional comma deliminated list of 
	 *        database field names to use in the <i>order by</i> clause.
	 *        Do not include "order by" in the string; only include the list
	 *        of field names. Pass null to skip over this paramater.
	 *
	 * @param string $where_clause Optional <i>where</i> clause to limit the 
	 *        returned results.  Do not include "where" in the string; only 
	 *        include the conditionals.
	 *
	 * @return array An array in the form of $id => $title.
	 */
	public static function getOptionArray($db, $table, $title_field, $id_field, 
		$order_by_clause = null, $where_clause = null) {

		$title_field = new AdminDBField($title_field, 'text');
		$id_field = new AdminDBField($id_field, 'integer');

		$sql = 'select %s, %s from %s';
		$sql = sprintf($sql, $id_field->name, $title_field->name, $table);

		if ($where_clause != null)
			$sql .= ' where '.$where_clause;

		if ($order_by_clause != null)
			$sql .= ' order by '.$order_by_clause;

		$rs = $db->query($sql, array($id_field->type, $title_field->type));

		if (MDB2::isError($rs))
			throw new Exception($rs->getMessage());

		$options = array();

		while ($row = $rs->fetchRow(MDB2_FETCHMODE_OBJECT)) {
			$title_field_name = $title_field->name;
			$id_field_name = $id_field->name;
			$options[$row->$id_field_name] = $row->$title_field_name;
		}

		return $options;
	}

	/**
	 * Query for an option tree array
	 *
 	 * Convenience method to query for a set of options, each consisting of
	 * an id, levelnum, and a title. The returned option array in the form of
	 * a collection of {@link SwatTreeNode}s to other classes, such as 
	 * SwatFlydown for example.
	 *
	 * @param MDB2_Driver_Common $db The database connection.
	 *
	 * @param string $sp Stored procedure/function to execute. Must return the
	 *        values: id, title, level - in the order of output.
	 *
	 * @param string $title_field The name of the database field to query for 
	 *        the title. Can be given in the form type:name where type is a
	 *        standard MDB2 datatype. If type is ommitted, then text is 
	 *        assummed for this field.
	 *
	 * @param string $id_field The name of the database field to query for 
	 *        the id. Can be given in the form type:name where type is a
	 *        standard MDB2 datatype. If type is ommitted, then integer is 
	 *        assummed for this field.
	 *
	 * @param string $parent_field The name of the database field to query for 
	 *        the parent. Can be given in the form type:name where type is a
	 *        standard MDB2 datatype. If type is ommitted, then integer is 
	 *        assummed for this field.
	 *
	 * @return SwatTreeNode A tree hierarchy of {@link SwatTreeNode}s
	 */
	public static function getOptionArrayTree($db, $sp, $title_field, $id_field,
		$level_field) {

		$id_field = new AdminDBField($id_field, 'integer');
		$title_field = new AdminDBField($title_field, 'text');
		$level_field = new AdminDBField($level_field, 'integer');
		
		$types = array($id_field->type, $title_field->type, $level_field->type);
		
		$rs = $db->executeStoredProc($sp, array(0), $types, true);
		if (MDB2::isError($rs))
			throw new Exception($rs->getMessage());

		$tree = AdminDB::buildOptionArrayTree($rs, $title_field->name, $id_field->name, $level_field->name);
		return $tree;
	}

	private static function buildOptionArrayTree($rs, $title_field_name, $id_field_name,
		$level_field_name) {

		$stack = array();
		$current_parent =  new SwatTreeNode(0, 'root');
		$base_parent = $current_parent;
		array_push($stack, $current_parent);
		$last_node = $current_parent;	

		while ($row = $rs->fetchRow(MDB2_FETCHMODE_OBJECT)) {
			$title = $row->$title_field_name;
			$id = $row->$id_field_name;
			$level = $row->$level_field_name;
			
			if ($level > count($stack)) {
				array_push($stack, $current_parent);
				$current_parent = $last_node;
			} else if ($level < count($stack)) {
				$current_parent = array_pop($stack);
			}
		
			$last_node = new SwatTreeNode(array('title'=>$title));
			$current_parent->children[$id] = $last_node;
		}

		return $base_parent;
	}

	private static function getFieldNameArray($fields) {

		if (count($fields) == 0)
			return;

		$names = array();

		foreach ($fields as &$field)
			$names[] = $field->name;

		return $names;
	}

	private static function getFieldTypeArray($fields) {

		if (count($fields) == 0)
			return;

		$types = array();

		foreach ($fields as &$field)
			$types[] = $field->type;

		return $types;
	}

	private function initFields(&$fields) {
		/* Transforms and array of text field identifiers ('text:title') into
		 * an array of AdminDBField objects.
		 */
		if (count($fields) == 0)
			// TODO: throw exception instead of returning
			return;

		foreach ($fields as &$field)
			$field = new AdminDBField($field, 'text');
	}

	/**
	 * Query a single row
	 *
 	 * Convenience method to query for a single row from a database table.
	 * One convenient use of this method is for loading data on an edit page.
	 *
	 * @param MDB2_Driver_Common $db The database connection.
	 *
	 * @param string $table The database table to query.
	 *
	 * @param array $fields An array of fields to be queried. Can be 
	 *        given in the form type:name where type is a standard MDB2 
	 *        datatype. If type is ommitted, then text is assummed.
	 *
	 * @param string $id_field The name of the database field that contains the
	 *        the id. Can be given in the form type:name where type is a
	 *        standard MDB2 datatype. If type is ommitted, then integer is 
	 *        assummed for this field.
	 *
	 * @param mixed $id The value to look for in the id field column. The 
	 *        type should correspond to the type of $field.
	 */
	public static function rowQuery($db, $table, $fields, $id_field, $id) {

		AdminDB::initFields($fields);
		$id_field = new AdminDBField($id_field, 'integer');
		$sql = 'select %s from %s where %s = %s';

		$field_list = implode(',', AdminDB::getFieldNameArray($fields));

		$sql = sprintf($sql,
			$field_list,
			$table,
			$id_field->name,
			$db->quote($id, $id_field->type));

		$rs = $db->query($sql, AdminDB::getFieldTypeArray($fields));
		$row = $rs->fetchRow(MDB2_FETCHMODE_OBJECT);

		return $row;
	}

	/**
	 * Insert a row
	 *
 	 * Convenience method to insert a single database row. One convenient use
	 * of this method is for saving data on an edit page.
	 *
	 * @param MDB2_Driver_Common $db The database connection.
	 *
	 * @param string $table The database table to update.
	 *
	 * @param array $fields An array of fields to be updated. Can be 
	 *        given in the form type:name where type is a standard MDB2 
	 *        datatype. If type is ommitted, then text is assummed.
	 *
	 * @param array $values An associative array of values to store in the
	 *        database.  The array keys should correspond to field names.
	 *        The type of the individual values should correspond to the 
	 *        field type.
	 *
	 * @param string $id_field The name of the database field that contains an
	 *        identifier of row to be updated. Can be given in the form 
	 *        type:name where type is a standard MDB2 datatype. If type is 
	 *        ommitted, then integer is assummed for this field.
	 *		  If $id_field is set, the value in the $id_field column of
	 *        the inserted row is returned.
	 *
	 * @return mixed If $id_field is set, the value in the $id_field column of
	 *        the inserted row is returned.
	 */
	public static function rowInsert($db, $table, $fields, $values, $id_field = NULL) {

		AdminDB::initFields($fields);

		$ret = null;

		if ($id_field != NULL)
			$db->beginTransaction();

		$sql = 'insert into %s (%s) values (%s)';
		$field_list = implode(',', AdminDB::getFieldNameArray($fields));

		foreach ($fields as &$field)
			$values[$field->name] = $db->quote($values[$field->name], $field->type);

		$value_list = implode(',', $values);

		$sql = sprintf($sql,
			$table,
			$field_list,
			$value_list);

		$rs = $db->query($sql);

		if ($id_field != NULL) {
			$ret = AdminDB::getFieldMax($db, $table, $id_field);						
			$db->commit();
		}

		return $ret;
	}

	/**
	 * Update a row
	 *
 	 * Convenience method to update multiple fields of a single database row. 
	 * One convenient use of this method is for save data on an edit page.
	 *
	 * @param MDB2_Driver_Common $db The database connection.
	 *
	 * @param string $table The database table to update.
	 *
	 * @param array $fields An array of fields to be updated. Can be 
	 *        given in the form type:name where type is a standard MDB2 
	 *        datatype. If type is ommitted, then text is assummed.
	 *
	 * @param array $values An associative array of values to store in the
	 *        database.  The array keys should correspond to field names.
	 *        The type of the individual values should correspond to the 
	 *        field type.
	 *
	 * @param string $id_field The name of the database field that contains an
	 *        identifier of row to be updated. Can be given in the form 
	 *        type:name where type is a standard MDB2 datatype. If type is 
	 *        ommitted, then integer is assummed for this field.
	 *
	 * @param mixed $id The value to look for in the $id_field column. The 
	 *        type should correspond to the type of $field.
	 */
	public static function rowUpdate($db, $table, $fields, $values, $id_field, $id) {

		AdminDB::initFields($fields);
		$id_field = new AdminDBField($id_field, 'integer');
		$sql = 'update %s set %s where %s = %s';
		$updates = array();

		foreach ($fields as &$field)
			$updates[] = $field->name.' = '.$db->quote($values[$field->name], $field->type);

		$update_list = implode(',', $updates);

		$sql = sprintf($sql,
			$table,
			$update_list,
			$id_field->name,
			$db->quote($id, $id_field->type));

		$rs = $db->query($sql);
	}


	/**
	 * Get max field value
	 *
 	 * Convenience method to grab the max value from a single field.
	 *
	 * @param MDB2_Driver_Common $db The database connection.
	 *
	 * @param string $table The database table to update.
	 *
	 * @param string $field The field to be return the max value of. Can be 
	 *        given in the form type:name where type is a standard MDB2 
	 *        datatype. If type is ommitted, then text is assummed.
	 *
	 * @return mixed The max value of field specified.
	 */
	public static function getFieldMax($db, $table, $field) {
		$field = new AdminDBField($field, 'integer');
			
		$sql = sprintf('select max(%s) as %s from %s',
			$field->name, $field->name, $table);

		$rs = $db->query($sql, array($field->type));
		
		$row = $rs->fetchRow(MDB2_FETCHMODE_OBJECT);
		$field_name = $field->name;
		return $row->$field_name;
	}
}
