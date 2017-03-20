<?php

$s1 = "select id from             testa where a = 1 and b = 2";
$s2 = "select id from testa where a = 1 group by a";
$i1 = "insert into testa ( a, b, c ) value (1,2,3)";
$d1 = "delete from testa where a = 1 and b = 12 or b = 13 and c in (1,2,3) and d between 1 and 2";

function replace_between_and($arr){
    $key = isset($arr[1]) ? $arr[1] : ''; 
    $begin = isset($arr[2]) ? $arr[2] : '';
    $end = isset($arr[3]) ? $arr[3] : '';

    return ' ' . trim($key) . '^' . trim($begin) . '-' . trim($end);
}

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
        $sql = preg_replace('/\s*>\s*/', '>', $sql );
        $sql = preg_replace('/\s*>=\s*/', '>=', $sql );
        $sql = preg_replace('/\s*<\s*/', '<', $sql );
        $sql = preg_replace('/\s*<=\s*/', '<=', $sql );
        $sql = preg_replace('/\s*!=\s*/', '!=', $sql );

        $sql = preg_replace_callback('/[\s]+([\w]+)[\s]+between([\w\s]+)and([\w\s]+)/', "replace_between_and", $sql);
        $sql = preg_replace('/\s*and\s*/', $this->delete_split['and'], $sql);
        $sql = preg_replace('/\s*or\s*/',  $this->delete_split['or'], $sql);
        $sql = preg_replace('/\s*not\s+in\s*/',  $this->delete_split['nin'], $sql);
        $sql = preg_replace('/\s*in\s*/',  $this->delete_split['in'], $sql);
        return trim($sql);
    }

    public function format_delete($sql){
        $tmp_arr = explode(' ', $sql);
        $format_arr['table'] = isset($tmp_arr[2]) ? $tmp_arr[2] : $this->error('Table name error line:' . __LINE__);
        $format_arr['condition'] = isset($tmp_arr[4]) ? $tmp_arr[4] : $this->error('Condition error line:' . __LINE__);
        return $format_arr;
    }

    public function combine_condition($condition){
        if(!$condition){
            $this->error('delete conditon error line:' . __LINE__);
        }
        $co = [];
        $split = [];
        //"a=1@b=12#b=13@c%(1,2,3)@d^1-2"

        if(preg_match('/'. $this->delete_split['or'] . '/', $condition)){
            $or_arr = explode($this->delete_split['or'], $condition);
            if(is_array($or_arr) && count($or_arr)){
                foreach($or_arr as $v){
                    $and = explode($this->delete_split['and'], $v);
                    $split['$or'][] = $and;                    
                }
            }
        }else{
            $and_arr = explode($this->delete_split['and'], $condition);
            $split['$and'] = $and_arr;
        }

        if(isset($split['$or'])){
            if($split['$or']){
                foreach($split['$or'] as $key => $and){
                    if($and){
                        foreach($and as $v){
                            $co['$or'][$key] = $this->do_split($v);
                        } 
                    }
                }
            } 
        }elseif($split['$and']){
            foreach($split['$and'] as $key => $v){
                $co = array_merge($this->do_split($v), $co);
            } 
        }
        return $co;
    }

    private function do_split($v){
        if(stripos($v, '!=')){
            $tmp = explode('!=', $v);
            $key = $tmp[0];
            $val = $tmp[1];
            $ret[$key]['$ne'] = trim($val);
            return $ret;
        } elseif(stripos($v, '>=')){
            $tmp = explode('>=', $v);
            $key = $tmp[0];
            $val = $tmp[1];
            $ret[$key]['$gte'] = trim($val);
            return $ret;
        } elseif(stripos($v, '>')){
            $tmp = explode('>', $v);
            $key = $tmp[0];
            $val = $tmp[1];
            $ret[$key]['$gt'] = trim($val);
            return $ret;
        } elseif(stripos($v, '<=')){
            $tmp = explode('<=', $v);
            $key = $tmp[0];
            $val = $tmp[1];
            $ret[$key]['$lte'] = trim($val);
            return $ret;
        } elseif(stripos($v, '<')){
            $tmp = explode('<', $v);
            $key = $tmp[0];
            $val = $tmp[1];
            $ret[$key]['$lt'] = trim($val);
            return $ret;
        } elseif(stripos($v, '=')){
            $tmp = explode('=', $v);
            $key = $tmp[0];
            $val = $tmp[1];
            return [trim($key) => trim($val)];
        } elseif(stripos($v, '%')){
            $tmp = explode('%', $v);
            $key = $tmp[0];
            $val = trim($tmp[1], '(');
            $val = explode(',', trim($val, ')'));
            return [trim($key) => $val];
        } elseif(stripos($v, '^')){
            preg_match_all('/(\w+)\^(\w+)-(\w+)/', $v, $tmp);
            $field = trim(current($tmp[1]));
            $ret[$field]['$gt'] = trim(current($tmp[2]));
            $ret[$field]['$lt'] = trim(current($tmp[3]));
            return [$field => $ret[$field]];
        }
    }

    public function delete(){
        $sql = $this->format_delete_sql($this->sql);
        $delete_arr = $this->format_delete($sql);
        $collection = $delete_arr['table'];
        $condition = $this->combine_condition($delete_arr['condition']);
        echo 'db.' . $collection . '.remove(' . json_encode($condition) . ')' ."\n";    
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
$d2 = "delete from testa where c <  5";
$stom = new transMongo;
$stom->setSQL($d2);
echo $d1."\n";
$stom->delete();
