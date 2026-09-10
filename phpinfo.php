<?php
	echo '<PRE>';
	print_r("Добро пожаловать!\n");
	print_r("[SERVER_NAME] => $_SERVER[SERVER_NAME]\n");
	print_r("[SERVER_ADDR] => $_SERVER[SERVER_ADDR]\n");
	print_r("[REQUEST_SCHEME] => $_SERVER[REQUEST_SCHEME]\n");
	//print_r($_SERVER);
	phpinfo();
