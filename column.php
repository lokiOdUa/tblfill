<?php

abstract class Column
{
    protected $_name;
    protected $_length=0;
    protected $_type = "class name";
    protected $_value;
    protected $_skip = false; // used to skip column fill (for auto_increments etc)
    
    abstract public function fill();
    
    public function get_name()
    {
        return($this->_name);
    }
    
    public function get_value()
    {
        return($this->_value);
    }
    
    public function __construct($row)
    {
        $this->_name = $row->column_name;
        $this->_type = $row->datatype;
    }
}

class Column_Char extends Column
{
    private $_chars = "QWERTYUIOPASDFGHJKLZXCVBNMqwertyuiopasdfghjklzxcvbnm"; // array containing letters to fill character strings

    public function fill()
    {
        $this->_value="";
        if($this->_length)
        {
            $length = rand (1, $this->_length);
            for($c=0; $c<$length; $c++)
            {
                $this->_value.=$this->_chars[rand(0, strlen($this->_chars)-1)];
            }
        }
        return("'" . $this->_value . "'");
    }
    
    public function __construct($row)
    {
        $this->_length = intval($row->character_maximum_length);
        parent::__construct($row);
    }
    
    public function __toString()
    {
        return("TYPE: {$this->_type}, NAME: {$this->_name}, LENGTH: {$this->_length}");
    }
}

class Column_Date extends Column
{
    public function fill()
    {
        $this->_value=date("Y-m-d", time()-rand(0, 60*60*24*365)); // filling with a random value from last year
        return("'" . $this->_value . "'");
    }

    public function __construct($row)
    {
        parent::__construct($row);
    }
    
    public function __toString()
    {
        return("TYPE: {$this->_type}, NAME: {$this->_name}, LENGTH: {$this->_length}");
    }
}

class Column_Datetime extends Column
{
    public function fill()
    {
        $this->_value=date("Y-m-d H:i:s", time()-rand(0, 60*60*24*90)); // filling with a random value from last 90 days
        return("'" . $this->_value . "'");
    }
    
    public function __construct($row)
    {
        parent::__construct($row);
    }
    
    public function __toString()
    {
        return("TYPE: {$this->_type}, NAME: {$this->_name}");
    }
}

class Column_Enum extends Column
{
    private $_possible_values = array();
    public function fill()
    {
        $this->_value = $this->_possible_values[array_rand($this->_possible_values)];
        return("'" . $this->_value . "'");
    }
    
    public function __construct($row)
    {
        $patterns[0] = "/enum\('/";
        $patterns[1] = "/','/";
        $patterns[2] = "/'\)/";
        $replace[0] = "";
        $replace[1] = ",";
        $replace[2] = "";
        $this->_possible_values = explode(",", preg_replace($patterns, $replace, $row->column_type));
        $this->fill();
        parent::__construct($row);
    }
    
    public function __toString()
    {
        return("TYPE: {$this->_type}, NAME: {$this->_name}, LENGTH: {$this->_length}");
    }
}

class Column_Int extends Column
{
    public function fill($length = 0)
    {
        $length_use = $length>0 ? $length : $this->_length;
        $this->_value = 0;
        if(!$this->_skip)
        {
            $this->_value=rand(0, pow(10, $length_use));
        }
        return($this->_value);
    }
    
    public function __construct($row)
    {
        if($row->extra == "auto_increment")
        {
            $this->_skip = true;
        }
        else
        {
            $this->_length = $row->numeric_precision;
        }
        $this->fill();
        parent::__construct($row);
    }
    
    public function __toString()
    {
        $string = "TYPE: {$this->_type}, NAME: {$this->_name}, LENGTH: {$this->_length}";
        if($this->_skip)
            $string .= ", SKIPPING AUTO_INCREMENT";
        return($string);
    }
}

class Column_Text extends Column
{
    private $_words;

    public function fill($length = 0)
    {
        $this->_value = "";
        $effect = rand(0,3); // example of quick&dirty
        switch ($effect)
        {
            case 0: $effect="";
                    break;
            case 1: $effect="</b>";
                    $this->_value.="<b>";
                    break;
            case 2: $effect="</i>";
                    $this->_value.="<i>";
                    break;
            case 3: $effect="</u>";
                    $this->_value.="<u>";
                    break;
        }
        $words_no = rand(3,7);
        for($c=0; $c<$words_no; $c++)
        {
            if($c>0)
            {
                $this->_value.=" ";
            }
            $this->_value.=$this->_words[rand(0, count($this->_words)-1)];
        }
        if($effect)
        {
            $this->_value.=$effect;
        }
        return("'" . $this->_value . "'");
    }

    public function __construct($row)
    {
        $this->_words = explode(" ", "Lorem Ipsum Dolor Sit Amet Consectetur Adipiscing Elit Morbi Arcu Sollicitudin Ornare Massa Tempor Neque. Praesent et malesuada augue tempus ultrices diam");
        parent::__construct($row);
    }
    
    public function __toString()
    {
        return("TYPE: {$this->_type}, NAME: {$this->_name}");
    }
}

class Column_Varchar extends Column
{
    private $_words;

    public function fill($length = 0)
    {
        $this->_value = "";
        $words_no = rand(2,4);
        for($c=0; $c<$words_no; $c++)
        {
            if($c>0)
            {
                $this->_value.=" ";
            }
            $this->_value.=$this->_words[rand(0, count($this->_words)-1)];
        }
        return("'" . $this->_value . "'");
    }
    
    public function __construct($row)
    {
        $this->_words = explode(" ", "a orci leo velit non lui quis urna");
        parent::__construct($row);
    }
    
    public function __toString()
    {
        return("TYPE: {$this->_type}, NAME: {$this->_name}");
    }
}
?>
