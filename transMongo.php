<?php

$s1 = "select id from             testa where a = 1 and b = 2";
$s2 = "select id from testa where a = 1 group by a";
$i1 = "insert into testa ( a, b, c ) value (1,2,3)";
$d1 = "delete from testa where a = 1 and b = 12 or b = 13 and c in (1,2,3) and d between 1 and 2";

class transMongo{
    private $sql = '';

    public function setSQL($sql){
        $this->sql = $sql;
    }

    public function getSQL(){
        return $this->sql;
    }
    public function format_select($sql){
    
    }

    public function select(){
        $sql = $this->sql;
        $sql_arr = $this->format_select($sql);
    }

    public function update(){

    }


    //------delete-------
     
    private $delete_split = [
        'and' => '@',
        'or' => '#',
        'nin' => '$',
        'in' => '%',
        'between' => '^', 
    ];

    public function format_delete_sql($sql){
        $sql = preg_replace('/\s+/', ' ', $sql);
        $sql = preg_replace('/\s*=\s*/', '=', $sql);
        $sql = preg_replace('/\s*and\s*/', $this->delete_split['and'], $sql);
        $sql = preg_replace('/\s*or\s*/',  $this->delete_split['or'], $sql);
        $sql = preg_replace('/\s*not\s+in\s*/',  $this->delete_split['nin'], $sql);
        $sql = preg_replace('/\s*in\s*/',  $this->delete_split['in'], $sql);
        $sql = preg_replace('/\s*between\s*/',  $this->delete_split['between'], $sql);
        return trim($sql);
    }

    public function format_delete($sql){
        $tmp_arr = explode(' ', $sql);
        $format_arr['table'] = isset($tmp_arr[2]) ? $tmp_arr[2] : $this->error('Table name error line:' . __LINE__);
        $format_arr['condition'] = isset($tmp_arr[4]) ? $tmp_arr[4] : $this->error('Condition error line:' . __LINE__);
        return $format_arr;
    }

    public function combine_condition($condition){
        //var_dump($condition);exit;
        if(!$condition){
            $this->error('delete conditon error line:' . __LINE__);
        }
        $co = [];
        /*
        $tmp_arr = explode(',', $condition);
        if($tmp_arr){
            foreach($tmp_arr as $c){
                $t = explode('=', $c);
                $co[$t[0]] = $t[1];
            }
        }*/
        
        

        return $co;
    }

    public function delete(){
        $sql = $this->format_delete_sql($this->sql);
        $delete_arr = $this->format_delete($sql);
        $collection = $delete_arr['table'];
        $condition = $this->combine_condition($delete_arr['condition']);
        echo 'db.' . $collection . '.remove(' . json_encode($condition) . ')' ."\n";    
    }

    public function deleteOne(){
        $sql = $this->format_delete_sql($this->sql);
        $delete_arr = $this->format_delete($sql);
        $collection = $delete_arr['table'];
        $condition = $this->combine_condition($delete_arr['condition']);

    }

    //-------end-select----------
    
    //-------insert--------------
    public function format_insert_sql($sql){
        $sql = preg_replace('/\s+/', ' ', $sql);
        $sql = preg_replace('/\(\s*/', '(', $sql);
        $sql = preg_replace('/\s*\)/', ')', $sql);
        $sql = preg_replace('/\s*,\s*/', ',', $sql);
        return trim($sql);
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
        $sql = $this->format_insert_sql($this->sql);
        $insert_arr = $this->format_insert($sql);
        $collection = $insert_arr['table'];
        $insert_kv_arr = $this->combine_insert_kv($insert_arr['filed'], $insert_arr['value']);
        echo 'db.' . $collection . '.insert(' . json_encode($insert_kv_arr) . ')' ."\n";
    }

    //---------end_insert-----------

    public function error($msg){
        echo "\n" . $msg . "\n";exit;
    }
}

$stom = new transMongo;
$stom->setSQL($d1);
$stom->delete();
