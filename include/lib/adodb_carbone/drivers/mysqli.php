<?php

class ADODB_mysqli extends ADOConnection{
    var $link;

    function ADODB_mysqli(){
    }

    function Connect($hostname, $username, $password, $database_name){
        if(!($this->link = mysqli_connect($hostname, $username, $password, $database_name))){
            exit($this->ErrorMsg());
        }
    }

    function Execute($sql){
        $rs = new ADORecordSet_mysqli();

        $rs->result = mysqli_query($this->link, $sql);
        if(!$rs->result){
            if(CFG_DEBUG)
                echo($this->ErrorMsg().' [SQL Query] '.$sql);
            else
                echo($this->ErrorMsg().' [SQL Query] ');
        }
        
        parent::LogSQL($sql);

        return $rs;
    }

    function SelectLimit($sql, $numrow, $offset = 0){
        $sql.= ' LIMIT '.$offset.','.$numrow;
        return $this->Execute($sql);
    }

    function Insert_ID($table = null, $column = null){
        return mysqli_insert_id($this->link);
    }

    function qstr($str){
        $str = '\''.mysqli_real_escape_string($this->link, $str).'\'';
        return str_replace('\"', '"', $str);
    }

    function Close(){
        if(!mysqli_close($this->link)){
            exit($this->ErrorMsg());
        }
        parent::LogSQL('---');
    }

    function ErrorMsg(){
        return '['.mysqli_errno($this->link).'] '.mysqli_error($this->link);
    }
}

class ADORecordSet_mysqli extends ADORecordSet{
    var $result;

    function ADORecordSet_mysqli(){
    }

    function FetchRow(){
        return mysqli_fetch_assoc($this->result);
    }

    function RecordCount(){
        return mysqli_num_rows($this->result);
    }

    function Close(){
        mysqli_free_result($this->result);
    }
}

?>