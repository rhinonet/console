<?php
function filter($path){
	$file=file_get_contents($path);
	$file=preg_replace('/\/\*[\s|\S]+\*\//','',$file);
	$basename=basename($path);
    $newpath=dirname(preg_replace('/framework/','framework_f',$path));	
	$res=file_put_contents("./".$basename,$file);
	if($res){
	//var_dump($basename);var_dump($newpath."/".$basename);exit;
		if(movefile($basename,$newpath."/".$basename)){
			echo "success<br/>";
		}else{
			$s=file_get_contents($basename);
			echo "faile<br/>";
			exit;
		}
	}else{
		exit($basename."filter filed!<br/>");
	}
}
function eachDir($path){
	$source = opendir($path);
	if(is_resource($source)){
		while($handler=readdir($source)){
			if($handler == '.' || $handler=='..' || $handler=='.htaccess'){
				continue;
			}else{
				if(is_dir($path."/".$handler)){
					$sonPath=$path."/".$handler;
					eachDir($sonPath);
				}elseif(is_file($path."/".$handler) && pathinfo($path."/".$handler,PATHINFO_EXTENSION)=='php'){
					filter($path."/".$handler);
				}
			}
		}
	}else{
		exit("isn't dir");
	}
	if(is_dir($path)){
		
	}else{
        filter($path);
	}
}
function movefile($oldpath,$newpath){
	$file=file_get_contents($oldpath);
	//if(is_file(dirname($newpath))){
	//	mkdir(dirname($newpath));
	//}else{
	//	echo dirname(preg_replace('/framework_f/','framework',$newpath));
	//	eachDir(dirname(preg_replace('/framework_r/','framework',$newpath)));
	//}
	$h=fopen($newpath,'w+');
	$w=fwrite($h,$file);
	if($w){
		fclose($h);
		return true;
	}else{
		return false;
	}
}
eachDir('./framework');