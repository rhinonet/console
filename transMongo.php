<?php

$s1 = "select id from             testa where a = 1 and b = 2";
$s2 = "select id from testa where a = 1 group by a";
$i1 = "insert into testa ( a, b, c ) value (1,2,3)";


class transMongo{
    private $sql = '';

    public function setSQL($sql){
        $this->sql = $this->format_sql($sql);
    }

    public function getSQL(){
        return $this->sql;
    }

    public function format_sql($sql){
        $sql = preg_replace('/\s+/', ' ', $sql);
        $sql = preg_replace('/\(\s*/', '(', $sql);
        $sql = preg_replace('/\s*\)/', ')', $sql);
        $sql = preg_replace('/\s*,\s*/', ',', $sql);
        return trim($sql);
    }

    public function format_select($sql){
    
    }

    public function select(){
        $sql = $this->sql;
        $sql_arr = $this->format_select($sql);
    }

    public function update(){

    }

    public function delete(){

    }

    public function format_insert($sql){
        $tmp_arr = explode(' ', $sql);
        if(count($tmp_arr) != 6){
            $this->error('insert sql error line:' . __LINE__);
        }
        $format_arr['table'] = isset($tmp_arr[2]) ? $tmp_arr[2] : $this->error('Table name error line:' . __LINE__);
        $format_arr['filed'] = isset($tmp_arr[3]) ? $tmp_arr[3] : $this->error('Filed error line:' . __LINE__);
        $format_arr['value'] = isset($tmp_arr[5]) ? $tmp_arr[5] : $this->error('Value error line:' . __LINE__);
        return $format_arr;
    }

    public function combine_insert_kv($field, $value){
        $field = trim($field, '(');
        $field = trim($field, ')');
        $fields = explode(',', $field);

        $value = trim($value, '(');
        $value = trim($value, ')');
        $values = explode(',', $value);
        if(count($fields) != count($values)){
            $this->error('insert key_value num error line:' . __LINE__);
        }

        return (array_combine($fields, $values));
    }

    public function insert(){
        $sql = $this->sql;
        $insert_arr = $this->format_insert($sql);
        $collection = $insert_arr['table'];
        $insert_kv_arr = $this->combine_insert_kv($insert_arr['filed'], $insert_arr['value']);
        echo 'db.' . $collection . '.insert(' . json_encode($insert_kv_arr) . ')' ."\n";
    }

    public function error($msg){
        echo "\n" . $msg . "\n";exit;
    }
}

$stom = new transMongo;
$stom->setSQL($i1);
$stom->insert();
