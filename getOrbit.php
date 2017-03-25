<?php


$ori_x = 100;
$ori_y = 100;
$rect = 1;


$p = $argv[1];

$a = 1;
$t = 1;

$jd = [1 => 360 ]; //ceng du shu
$range = [1 => [1]]; //ceng qi shi
$qs = [1 => [0]]; // ceng wei zhi
$qs_value = [1 => [1 => 0]]; //ceng wei zhi jiaodu
while($t < $p){
	$a++;
	$item = ($a-1)*6;
	$jd[$a] = 360/$item;
	$start = $t+1; 
	$t += $item;
	$range[$a] = range($start, $t);
	$rr = 2 - $a;
	for($j = $start; $j<=$t; $j++){
		$qs[$a][$j] = $rr++ ;
		$qs_value[$a][$j] = $qs[$a][$j] > 0 ? $jd[$a] * $qs[$a][$j] : 360 + $jd[$a] * $qs[$a][$j]; 
	}
}

for($i = 1; $i<=$p; $i++){
	foreach($range as $kk => $vv){
		if(in_array($i, $vv)){
			$new  = array_flip($vv);
			$qs_val = $qs_value[$kk];
			$fx = 0;
			if($qs_val[$i] == 0 || $qs_val[$i] == 360){
				$fx = ($kk-1)*2*$rect;
			} elseif($qs_val[$i] ==180){
				$fx = -1*($kk-1)*2*$rect;
			}
			
			if($fx == 0){
				$x = round($ori_x + ($kk-1)*1.7320508075*cos($qs_val[$i] * 3.1415926535 / 180));
			}else{
				$x = round($ori_x + $fx);
			}

			$f = 0;
			if($qs_val[$i] == 90){
				$f = ($kk-1) * tan(60 * 3.1415926535 / 180);
			}elseif($qs_val[$i] ==270){
				$f = -1 * ($kk-1) * tan(60 * 3.1415926535 / 180);
			}
			$y = $ori_y + ($x-100) * tan($qs_val[$i] * 3.1415926535 / 180) + $f;
			echo $i . "-" . $kk . "-" . $new[$i]. "-". $qs_val[$i] . "-x:" . $x. '-y:' . $y . "\n" ;
									
		}
	}
}



