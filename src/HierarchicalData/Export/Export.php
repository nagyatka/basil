<?php
/**
 * Created by PhpStorm.
 * User: nagyatka
 * Date: 2018. 05. 16.
 * Time: 16:00
 */

namespace Basil\Export;

use Basil\Tree;

/**
 * Export Interface
 *
 * The interface has only one method, which is able to convert the input Tree data to an arbitrary structure.
 *
 * @package HierarchicalData\Export
 */
abstract class Export
{
    /**
     * Associative array of export options.
     *
     * @var array
     */
    private $options;

    /**
     * Exporter constructor.
     * @param array $options
     */
    public final function __construct(array $options)
    {
        $this->options = $options;
    }

    /**
     * Executes the export process on the input Tree.
     *
     * @param Tree $tree
     * @return mixed
     */
    public abstract function execute(Tree $tree): mixed;

    /**
     * Returns the value which associates with the input key. If the key does not exist, it returns with null.
     *
     * @param $key
     * @return mixed
     */
    protected function getOption($key): mixed {
        return $this->options[$key];
    }

    /**
     * @param string $tableName
     * @param array $data
     * @param \PDO $pdoObject
     * @return bool
     */
    protected static function pdoMultiInsert(string $tableName, array $data, \PDO $pdoObject){

        //Will contain SQL snippets.
        $rowsSQL = array();

        //Will contain the values that we need to bind.
        $toBind = array();

        //Get a list of column names to use in the SQL statement.
        $columnNames = array_keys($data[0]);

        //Loop through our $data array.
        foreach($data as $arrayIndex => $row){
            $params = array();
            foreach($row as $columnName => $columnValue){
                $param = ":" . $columnName . $arrayIndex;
                $params[] = $param;
                $toBind[$param] = $columnValue;
            }
            $rowsSQL[] = "(" . implode(", ", $params) . ")";
        }

        //Construct our SQL statement
        $sql = "REPLACE INTO `$tableName` (" . implode(", ", $columnNames) . ") VALUES " . implode(", ", $rowsSQL);

        //Prepare our PDO statement.
        $pdoStatement = $pdoObject->prepare($sql);

        //Bind our values.
        foreach($toBind as $param => $val){
            $pdoStatement->bindValue($param, $val);
        }

        //Execute our statement (i.e. insert the data).
        return $pdoStatement->execute();
    }
}