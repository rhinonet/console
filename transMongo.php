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

    //-----------group--------------

    public function group(){

    }

    //-----------end-group----------


    //-----------select-------------
    public function format_select($sql){
        $sql = preg_replace('/\s+/', ' ', $sql);
        $sql = preg_replace('/\s*=\s*/', '=', $sql);
        $sql = preg_replace('/\s*>\s*/', '>', $sql );
        $sql = preg_replace('/\s*>=\s*/', '>=', $sql );
        $sql = preg_replace('/\s*<\s*/', '<', $sql );
        $sql = preg_replace('/\s*<=\s*/', '<=', $sql );
        $sql = preg_replace('/\s*!=\s*/', '!=', $sql );

        $sql = preg_replace_callback('/[\s]+([\w]+)[\s]+between([\w\s]+)and([\w\s]+)/', function($arr){
            $key = isset($arr[1]) ? $arr[1] : ''; 
            $begin = isset($arr[2]) ? $arr[2] : '';
            $end = isset($arr[3]) ? $arr[3] : '';
            return ' ' . trim($key) . '^' . trim($begin) . '-' . trim($end);
        }, $sql);
        $sql = preg_replace('/\s*and\s*/', $this->delete_split['and'], $sql);
        $sql = preg_replace('/\s*or\s*/',  $this->delete_split['or'], $sql);
        $sql = preg_replace('/\s*not\s+in\s*/',  $this->delete_split['nin'], $sql);
        $sql = preg_replace('/\s*in\s*/',  $this->delete_split['in'], $sql);
        return trim($sql);

    }

    public function format_select_new($sql){
        $limit = $sort = $where = $table = '';

        $pos_limit = stripos($sql, 'limit');
        if($pos_limit !== false){
            $limit = substr($sql, $pos_limit);
            $trim_limit = trim(trim($sql, ';'));
            preg_match_all('/limit[\s]+([\d]+([\s]*,[\s]*[\d]+)?)/', $trim_limit, $arr);
            if(!$arr[1]){
                $this->error("limit error line:" . __LINE__); 
            }else{
                $tmp_str = $arr[1][0];
                if(stripos($tmp_str, ',') !== false){
                    if(preg_match('/[\d]+[\s]*,[\s]*[\d]+/', $tmp_str)){
                        $tmp_arr = explode(',', $tmp_str);
                        $select['limit']['skip'] = isset($tmp_arr[0]) ? intval(trim($tmp_arr[0])) : 0; 
                        $select['limit']['limit'] = isset($tmp_arr[1]) ? intval(trim($tmp_arr[1])) : 0; 
                    }else{
                        $this->error("limit error 1 line:" . __LINE__);
                    }
                }else{
                    if(preg_match('/[\d]+/', $tmp_str)){
                        $select['limit']['limit'] = intval($tmp_str); 
                    }else{
                        $this->error("limit error 2 line:" . __LINE__);
                    }
                }
            } 
        }else{
            $select['limit'] = [];
        }
        if($limit){
            $sql = str_replace($limit, '', $sql);
        }

        $pos_sort = stripos($sql, 'order');
        if($pos_sort !== false){
            $sort = substr($sql, $pos_sort);
            $tmp_sort = $sort;
            //preg_match_all( '/order[\s]+by[\s](\w+\s+(asc|desc)*\s*)(\s*,\s*\w+\s+(asc|desc)?\s*)?/', $tmp_sort, $arr);
            preg_match_all('/order\s+by\s+\w+(\s+(asc|desc)?)?(\s*,\s*\w+(\s+(asc|desc)?)?\s*)?\s+/', $tmp_sort, $arr);
            if(!(isset($arr[0][0]) && $arr[0][0])){
                $this->error("sort error line:" . __LINE__); 
            }else{
                $tmp_str = $arr[0][0];
                $order = str_replace('order', '', $tmp_str);
                $order = str_replace('by', '', $order);
                $order_arr = explode(',', $order);
                if($order_arr){
                    foreach($order_arr as $v){
                        if(!$v){
                            continue;
                        }
                        if(stripos($v, 'asc')){
                            $k = trim(str_replace('asc', '', $v));
                            $select['sort'][$k] = 1;
                        }elseif(stripos($v, 'desc')){
                            $k = trim(str_replace('desc', '', $v));
                            $select['sort'][$k] = -1;
                        }else{
                            $k = trim($v);
                            $select['sort'][$k] = 1;
                        }
                    }
                } 
            } 
        }else{
            $select['sort'] = [];
        }
        if($sort){
            $sql = str_replace($sort, '', $sql);
        }

        //condition
        $pos_condition = stripos($sql, 'where');
        if($pos_condition !== false){
            $condition = substr($sql, $pos_condition);
            $tmp_condition = $condition;
            $pos_between = stripos($tmp_condition, 'between');
            if($pos_between !== false){
                $tmp_condition = preg_replace_callback('/[\s]+([\w]+)[\s]+between\s+([\w]+)\s+and\s+([\w]+)\s*/', function($arr){
                    $key = isset($arr[1]) ? $arr[1] : ''; 
                    $begin = isset($arr[2]) ? $arr[2] : '';
                    $end = isset($arr[3]) ? $arr[3] : '';
                    if(!($begin && $end && trim($begin) < trim($end))){
                        $this->error('select between error line:' . __LINE__);
                    }
                    return ' (' . $key . ' >= ' . trim($begin) . ' && ' .  $key . ' <= ' . trim($end) . ') ';
                }, $tmp_condition);
            }


            if(preg_match('/not\s+in/', $tmp_condition)){
                //$tmp_condition = preg_match_all('/\s+(\w+)\s+not\s+in\s*\(\s*(\w+(\s*,\w+)*)\)/', $tmp_condition, $arr);
                $tmp_condition = preg_replace_callback('/\s+(\w+)\s+not\s+in\s*\((\s*\w+(\s*,\s*\w+)*)\s*\)/', function($arr){

/*array(4) {
  [0]=>
  string(17) " d not in (1,2,3)"
  [1]=>
  string(1) "d"
  [2]=>
  string(5) "1,2,3"
  [3]=>
  string(2) ",3"
}*/
                    $key = isset($arr[1]) ? $arr[1] : ''; 
                    $values = isset($arr[2]) ? $arr[2] : '';
                    if(!($key && $values)){
                        $this->error('select not in error line:' . __LINE__);
                    }
                    $tarr = explode(',', $values);
                    $rets = ' (';
                    if($tarr){
                        foreach($tarr as $v){
                            if($v === ''){
                                $this->error('select not in error line:' . __LINE__);
                            }
                            $rets .= ' ' . $key . ' != ' . $v . ' ||';
                        }
                        $rets = substr($rets, 0, -2);
                        $rets .= ') ';
                    }else{
                        $this->error('select not in error line:' . __LINE__);
                    }
                    return $rets;
                }, $tmp_condition);
            }

            if(preg_match('/in/', $tmp_condition)){
                $tmp_condition = preg_replace_callback('/\s+(\w+)\s+in\s*\(\s*(\w+(\s*,\s*\w+)*)\s*\)/', function($arr){
/*array(4) {
  [0]=>
  string(13) " e in (1,2,3)"
  [1]=>
  string(1) "e"
  [2]=>
  string(5) "1,2,3"
  [3]=>
  string(2) ",3"
}*/
                    $key = isset($arr[1]) ? $arr[1] : ''; 
                    $values = isset($arr[2]) ? $arr[2] : '';
                    if(!($key && $values)){
                        $this->error('select in error line:' . __LINE__);
                    }
                    $tarr = explode(',', $values);
                    $rets = ' (';
                    if($tarr){
                        foreach($tarr as $v){
                            if($v === ''){
                                $this->error('select not in error line:' . __LINE__);
                            }
                            $rets .= ' ' . $key . ' == ' . $v . ' ||';
                        }
                        $rets = substr($rets, 0, -2);
                        $rets .= ') ';
                    }else{
                        $this->error('select not in error line:' . __LINE__);
                    }
                    return $rets;
                }, $tmp_condition);
            }

            $pos_and = stripos($tmp_condition, 'and');
            if($pos_and != false){
                $tmp_condition = str_replace('and', '&&', $tmp_condition);
            }		

            $pos_or = stripos($tmp_condition, 'or');
            if($pos_or != false){
                $tmp_condition = str_replace('or', '||', $tmp_condition);
            }		
            $select['condition'] = str_replace('where', '', $tmp_condition );
        }else{
            $select['condition'] = '';
        }

        if($condition){
            $sql = str_replace($condition, '', $sql);
        }

/*array(4) {
  [0]=>
  array(1) {
    [0]=>
    string(25) "select a, b,c from testa "
  }
  [1]=>
  array(1) {
    [0]=>
    string(6) "a, b,c"
  }
  [2]=>
  array(1) {
    [0]=>
    string(2) ",c"
  }
  [3]=>
  array(1) {
    [0]=>
    string(5) "testa"
  }
}*/
        if(stripos($sql, '*') !== false){
            $reg = "/\s*select\s+[\*]\s+from\s+(\w+)\s+/";
            preg_match_all($reg, $sql, $sinfo);

            $select['filds'] = [];	
            if(isset($sinfo[1][0]) && $sinfo[1][0]){
                $select['table'] = $sinfo[1][0];
            }else{
                $this->error('select table name error line:'.__LINE__);
            }
        } else{
            $reg = "/\s*select\s+(\w+(\s*,\s*\w+)*)\s+from\s+(\w+)\s+/";
            preg_match_all($reg, $sql, $sinfo);
            if(isset($sinfo[1][0]) && $sinfo[1][0]){
                $farr = explode(',', $sinfo[1][0]);
                if($farr){
                    foreach($farr as $fname){
                        $select['fields'][trim($fname)] = 1;
                    }
                }
            }else{
                $this->error('select fields error line:'.__LINE__);
            }
            if(isset($sinfo[3][0]) && $sinfo[3][0]){
                $select['table'] = $sinfo[3][0];
            }else{
                $this->error('select table name error line:'.__LINE__);
            }
        }

        return $select; 
    }

    private $select_split = [
        'and' => '@',
        'or' => '#',
        'nin' => '$',
        'in' => '%',
        'between' => '^',
        'sort' => '&',
        'limit' => '*', 
    ];



    public function select(){
        $sql = $this->sql;
        $sql_arr = $this->format_select_new($sql);

        $sort = '';
        $limit = '';
        $skip = '';
        $field = '';
        $condition = '';

        if(isset($sql_arr['sort']) && $sql_arr['sort']){
            $sort = '.sort(' . json_encode($sql_arr['sort']) . ')';
        }
        if(isset($sql_arr['limit']) && $sql_arr['limit']){
            $limit = isset($sql_arr['limit']['limit']) && $sql_arr['limit']['limit'] ? '.limit('.intval($sql_arr['limit']['limit']). ')' : '';
            $skip = isset($sql_arr['limit']['skip']) && $sql_arr['limit']{'skip'}? '.skip('.intval($sql_arr['limit']['skip']). ')' : '';
        }
        if(isset($sql_arr['condition']) && $sql_arr['condition']){
            $co_arr['$where'] = 'function(){ return ' . $sql_arr['condition'] . ' }';
            $condition = json_encode($co_arr);
        }
	if(isset($sql_arr['condition']) && !$sql_arr['condition']){
	    $condition = '{}';
	}
        if(isset($sql_arr['fields']) && $sql_arr['fields']){
            $field = ", " . json_encode($sql_arr['fields']);
        }
        if(isset($sql_arr['table']) && $sql_arr['table']){
            $collection = $sql_arr['table'];
        }
        echo 'db.' . $collection . '.find(' . $condition . $field . ")" . $sort.$skip.$limit."\n";exit;
    }

    //--------end-select--------

    //---------update-----------

    public function format_update($sql){
	//condition
        $pos_condition = stripos($sql, 'where');
        if($pos_condition !== false){
            $condition = substr($sql, $pos_condition);
            $tmp_condition = $condition;
            $pos_between = stripos($tmp_condition, 'between');
            if($pos_between !== false){
                $tmp_condition = preg_replace_callback('/[\s]+([\w]+)[\s]+between\s+([\w]+)\s+and\s+([\w]+)\s*/', function($arr){
                    $key = isset($arr[1]) ? $arr[1] : ''; 
                    $begin = isset($arr[2]) ? $arr[2] : '';
                    $end = isset($arr[3]) ? $arr[3] : '';
                    if(!($begin && $end && trim($begin) < trim($end))){
                        $this->error('select between error line:' . __LINE__);
                    }
                    return ' (' . $key . ' >= ' . trim($begin) . ' && ' .  $key . ' <= ' . trim($end) . ') ';
                }, $tmp_condition);
            }


            if(preg_match('/not\s+in/', $tmp_condition)){
                //$tmp_condition = preg_match_all('/\s+(\w+)\s+not\s+in\s*\(\s*(\w+(\s*,\w+)*)\)/', $tmp_condition, $arr);
                $tmp_condition = preg_replace_callback('/\s+(\w+)\s+not\s+in\s*\((\s*\w+(\s*,\s*\w+)*)\s*\)/', function($arr){

/*array(4) {
  [0]=>
  string(17) " d not in (1,2,3)"
  [1]=>
  string(1) "d"
  [2]=>
  string(5) "1,2,3"
  [3]=>
  string(2) ",3"
}*/
                    $key = isset($arr[1]) ? $arr[1] : ''; 
                    $values = isset($arr[2]) ? $arr[2] : '';
                    if(!($key && $values)){
                        $this->error('select not in error line:' . __LINE__);
                    }
                    $tarr = explode(',', $values);
                    $rets = ' (';
                    if($tarr){
                        foreach($tarr as $v){
                            if($v === ''){
                                $this->error('select not in error line:' . __LINE__);
                            }
                            $rets .= ' ' . $key . ' != ' . $v . ' ||';
                        }
                        $rets = substr($rets, 0, -2);
                        $rets .= ') ';
                    }else{
                        $this->error('select not in error line:' . __LINE__);
                    }
                    return $rets;
                }, $tmp_condition);
            }

            if(preg_match('/in/', $tmp_condition)){
                $tmp_condition = preg_replace_callback('/\s+(\w+)\s+in\s*\(\s*(\w+(\s*,\s*\w+)*)\s*\)/', function($arr){
/*array(4) {
  [0]=>
  string(13) " e in (1,2,3)"
  [1]=>
  string(1) "e"
  [2]=>
  string(5) "1,2,3"
  [3]=>
  string(2) ",3"
}*/
                    $key = isset($arr[1]) ? $arr[1] : ''; 
                    $values = isset($arr[2]) ? $arr[2] : '';
                    if(!($key && $values)){
                        $this->error('select in error line:' . __LINE__);
                    }
                    $tarr = explode(',', $values);
                    $rets = ' (';
                    if($tarr){
                        foreach($tarr as $v){
                            if($v === ''){
                                $this->error('select not in error line:' . __LINE__);
                            }
                            $rets .= ' ' . $key . ' == ' . $v . ' ||';
                        }
                        $rets = substr($rets, 0, -2);
                        $rets .= ') ';
                    }else{
                        $this->error('select not in error line:' . __LINE__);
                    }
                    return $rets;
                }, $tmp_condition);
            }

            $pos_and = stripos($tmp_condition, 'and');
            if($pos_and != false){
                $tmp_condition = str_replace('and', '&&', $tmp_condition);
            }		

            $pos_or = stripos($tmp_condition, 'or');
            if($pos_or != false){
                $tmp_condition = str_replace('or', '||', $tmp_condition);
            }		
            $select['condition'] = str_replace('where', '', $tmp_condition );
        }else{
            $select['condition'] = '';
        }

        if($condition){
            $sql = str_replace($condition, '', $sql);
        }
	
	$update['condition'] = $select['condition'];
/*
array(4) {
  [0]=>
  array(1) {
    [0]=>
    string(27) "update testa set a = 1,b=2 "
  }
  [1]=>
  array(1) {
    [0]=>
    string(5) "testa"
  }
  [2]=>
  array(1) {
    [0]=>
    string(10) "a = 1,b=2 "
  }
  [3]=>
  array(1) {
    [0]=>
    string(4) ",b=2"
  }
}*/
	$reg = "/\s*update\s+(\w+)\s+set\s+(\w+\s*=\s*\w+(\s*,\s*\w+\s*=\s*\w+)*\s*)/";
	preg_match_all($reg, $sql, $sinfo);
	if(isset($sinfo[1][0]) && $sinfo[2][0]){
		$farr = explode(',', $sinfo[2][0]);
		if($farr){
			foreach($farr as $kv){
				$kv_arr = explode('=', $kv);
				$key = trim($kv_arr[0]);
				$value = trim($kv_arr[1]);
				if($key == '' || $value === ''){
					$this->error('update set error line:'.__LINE__);
				}	
				$update['$set'][$key] = $value;
			}
		}
	}else{
		$this->error('update set error line:'.__LINE__);
	}

	if(isset($sinfo[1][0]) && $sinfo[1][0]){
		$update['table'] = $sinfo[1][0];
	}else{
		$this->error('update table name error line:'.__LINE__);
	}

	return $update;
    }

    public function update(){
	$sql = $this->sql;
        $sql_arr = $this->format_update($sql);

        if(isset($sql_arr['condition']) && $sql_arr['condition']){
            $co_arr['$where'] = 'function(){ return ' . $sql_arr['condition'] . ' }';
            $condition = json_encode($co_arr);
        }
	if(isset($sql_arr['condition']) && !$sql_arr['condition']){
	    $condition = '{}';
	}
	if(isset($sql_arr['$set']) && $sql_arr['$set']){
	    $set = ', ' . json_encode($sql_arr['$set']);
	}else{
	    $this->error('update set error line:'.__LINE__);
	}
        if(isset($sql_arr['table']) && $sql_arr['table']){
            $collection = $sql_arr['table'];
        }
	if(1){
		$multi = ", " . json_encode(['multi' => true]);
	}
        echo 'db.' . $collection . '.update(' . $condition . $set . $multi .")\n";exit;
    }
    //---end-update-------

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

        $sql = preg_replace_callback('/[\s]+([\w]+)[\s]+between([\w\s]+)and([\w\s]+)/', function($arr){
            $key = isset($arr[1]) ? $arr[1] : ''; 
            $begin = isset($arr[2]) ? $arr[2] : '';
            $end = isset($arr[3]) ? $arr[3] : '';
            return ' ' . trim($key) . '^' . trim($begin) . '-' . trim($end);
        }, $sql);
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

    //-------end-delete----------

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
$d2 = "update testa set a = 1,b=2 where a = 3 ";
$stom = new transMongo;
$stom->setSQL($d2);
$stom->update();
