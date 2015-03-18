<?php

	

$array = array('a','A','1');

 $bla = sys_get_temp_dir() . '/f24client.bin';
if (file_put_contents( sys_get_temp_dir() . 'f24client.bin', serialize($array)) )
{echo( 'success');
}


?>