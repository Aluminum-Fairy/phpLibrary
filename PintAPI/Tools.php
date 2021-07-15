<?php
function console_log($data)																								//Consoleで出力できる便利関数
{
	echo '<script>';
	echo 'console.log(' . json_encode($data) . ')';
	echo '</script>';
}
