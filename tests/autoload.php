<?php
//输出个头部提示
echo '----------------------'.date('Y-m-d H:i:s').'----------------------'.PHP_EOL;
$files = [
	__DIR__.'/../vendor/autoload.php',
	__DIR__.'/../../autoload.php',
	__DIR__.'/../../../crm/webroot/index.php'

]; 
foreach ($files as  $file) {
	if(is_file($file)){
		require_once $file;
	}
}

