#!/usr/bin/php
<?php
require_once("column.php");

class Table
{
    private $_columns = array();
    private $_name;
    private $_records_to_add;
    private $_records_no;
    
    public function add_column($row)
    {
        $class_name = "Column_" . ucfirst($row->datatype);
        if( class_exists($class_name) )
        {
            $this->_columns[] = new $class_name($row);
        }
        else
        {
            throw new Exception("Unable to find class named `{$class_name}`");
        }
    }
    
    public function dump_columns()
    {
        var_dump($this->_columns);
    }
    
    public function get_columns()
    {
        return $this->_columns;
    }
    
    public function get_name()
    {
        return $this->_name;
    }
    
    public function get_records_to_add()
    {
        return $this->_records_to_add;
    }
    
    public function get_records_no()
    {
        return $this->_records_no;
    }
    
    public function __construct($table_name, $records_to_add = 0, $records_no = 0)
    {
        $this->_records_to_add = $records_to_add;
        $this->_records_no = $records_no;
        $this->_name = $table_name;
    }
}

class Main
{
    private $_cfg_filename;
    private $_db_server = "localhost";
    private $_db_user = "mylogin";
    private $_db_pass = "mypass";
    private $_db_name = "mydb";
    private $_db_connection = -1;
    private $_tables = array();	// list of Table objects to be actually filled
    private $_tables_to_fill = array(); // list of tables to fill from as array of strings config file
    private $_display_structure = false; // if true then script will dump database structure in human readable format
    private $_display_queries = false; // if true then script will display SQL queries before add data
    private $_fill = false; // if true then script will execute data sql queries only
    
    public function connect()
    {
        if ( $this->_db_connection==-1 )
        {
            if ( ( $this->_db_connection = mysql_connect( $this->_db_server, $this->_db_user, $this->_db_pass ) ) ==NULL )
            {
                throw new Exception("Cannot connect to {$this->_db_server}; error: `" . mysql_error() . "`");
            }
            if ( !mysql_select_db($this->_db_name, $this->_db_connection) )
            {
                throw new Exception("Cannot select table `{$this->_db_name}`; error: `" . mysql_error() . "`\n");
            }
        }
    }

    public function display_cfg()
    {
        echo("Will display database structure: ");
        echo($this->_display_structure ? "true" : "false");
        echo("\nWill display SQL queries before add data: ");
        echo($this->_display_queries ? "true" : "false");
        echo("\nWill fill database with random-generated data: ");
        echo($this->_fill ? "true" : "false");
        echo("\n");
    }
    
    public function display_db_structure() // done as separate method to be more user-friendly in future
    {
        foreach ($this->_tables as $table)
        {
            $c=0;
            if($this->_fill)
            {
                echo("Let's fill");
            }
            else
            {
                echo("Let's emulate filling of");
            }
            echo(" table `{$table->get_name()}` which already contains `{$table->get_records_no()}` records with `{$table->get_records_to_add()}` new record(s) using following fields:\n");
            foreach ($table->get_columns() as $column)
            {
                echo(" :: [$c]: {$column}\n");
                $c++;
            }
        }
    }
    
    public function get_cfg_filename()
    {
        return $this->_cfg_filename;
    }
    
    public function fill_database()
    {
        if($this->_display_queries)
        {
            echo("List of qheries to execute:\n");
        }
                    
        foreach ($this->_tables as $table)
        {
            for ($c=0; $c<$table->get_records_to_add(); $c++ )
            {
                $v=0;
                $query_string = "INSERT INTO {$table->get_name()} SET ";
                foreach ($table->get_columns() as $column)
                {
                    if($v>0)
                    {
                        $query_string .= ", ";
                    }
                    $query_string .= $column->get_name() . "=" . $column->fill();
                    $v++;
                }
                if($this->_display_queries)
                {
                    echo("{$query_string}\n");
                }
                if($this->_fill)
                {
                    if ( ( $query_result = mysql_query($query_string, $this->_db_connection) ) == NULL )
                        throw new Exception("Cannot execute query {$query_string}\n");
                }
            }
        }
    }
    
    public function load_cfg()
    {
        // loading cfg data from cfg file
        if ( ($configs_json = @file_get_contents($this->_cfg_filename)) == FALSE )
        {
            throw new Exception("Cannot open cfg file `{$this->_cfg_filename}`\n");
        }
        else
        {
            $configs = json_decode($configs_json);
            $this->_db_server = isset($configs->{'database'}->{'db_server'}) ? $configs->{'database'}->{'db_server'} : null;
            $this->_db_user = isset($configs->{'database'}->{'db_user'}) ? $configs->{'database'}->{'db_user'} : null;
            $this->_db_pass = isset($configs->{'database'}->{'db_pass'}) ? $configs->{'database'}->{'db_pass'} : null;
            $this->_db_name = isset($configs->{'database'}->{'db_name'}) ? $configs->{'database'}->{'db_name'} : null;
            $this->_display_structure = $configs->{'common'}->{'display_structure'} == "true" ? true : false;
            $this->_display_queries = $configs->{'common'}->{'display_queries'} == "true" ? true : false;
            $this->_fill = $configs->{'common'}->{'fill'} == "true" ? true : false;
            if ( ( $this->_db_server == null ) || ( $this->_db_user == null ) || ( $this->_db_pass == null ) || ( $this->_db_name == null ) )
            {
                throw new Exception("Error reading parameters from config file `{$this->_cfg_filename}`. Errcode: " . json_last_error());
            }
            if ( !isset($configs->{'tables'}) )
            {
                throw new Exception("No `tables` section in config file `{$this->_cfg_filename}` found");
            }
            else
            {
                foreach ( $configs->{'tables'} as $name => $value )
                {
                    $this->_tables_to_fill[$name] = $value;
                }
            }
        }
    }
    
    public function read_db_tables()
    {
        // get db tables list for given db from informatin_schema
$query_string = "
SELECT
TABLE_NAME AS table_name,
COLUMN_NAME AS column_name,
DATA_TYPE AS datatype,
CHARACTER_MAXIMUM_LENGTH AS character_maximum_length,
NUMERIC_PRECISION AS numeric_precision,
EXTRA AS extra,
COLUMN_TYPE AS column_type
FROM information_schema.columns
WHERE table_schema='{$this->_db_name}'
ORDER BY table_name;";
//        echo $query_string."\n";
        if ( ( $query_result = mysql_query($query_string, $this->_db_connection) ) == NULL )
            throw new Exception("Cannot execute query {$query_string}\n");
        $old_table_name = false;
        $table_to_fill = false;
        while ( $row = mysql_fetch_object( $query_result ))
        {
            if ( array_key_exists($row->table_name, $this->_tables_to_fill) ) // skip unwanted tables
            {
                if ( $old_table_name <> $row->table_name ) // create only new tables
                {
$query_string = "
SELECT
COUNT(*) AS amount
FROM {$row->table_name};";
                    if ( ( $amount_query_result = mysql_query($query_string, $this->_db_connection) ) == NULL )
                        throw new Exception("Cannot execute query {$query_string}\n");
                    if ( $amount_row = mysql_fetch_object( $amount_query_result ))
                    {
                        $table_to_fill = new Table($row->table_name, $this->_tables_to_fill[$row->table_name], $amount_row->amount);
                        $this->_tables[] = $table_to_fill;
                        $old_table_name = $row->table_name;
                    }
                    else
                        throw new Exception("Cannot get records amounbt for table `{$row->table_name}`");
                }
                $table_to_fill->add_column($row);
            }
        }
        if ( empty($this->_tables) )
            throw new Exception("Cannot find any tables to fill. Please ensure that 1. you have correct database set and 2. actual table names present in corresponding section of config file");
    }
    
    public function __construct($cfg_filename)
    {
        $this->_cfg_filename = $cfg_filename;
        $this->load_cfg();
        if ( $this->_display_structure )
        {
            $this->display_cfg();
        }
        $this->connect();
        $this->read_db_tables();
        if ( $this->_display_structure )
        {
            $this->display_db_structure();
        }
        $this->fill_database();
    }
}

echo "Usage: ".$argv[0]." [config.json]\n";
$tf = new Main(count($argv)>1 ? $argv[1] : "tbfill.json"); // last one is default config file name
?>
