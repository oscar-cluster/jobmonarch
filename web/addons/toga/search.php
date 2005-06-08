<?php

global $clustername, $tpl;

function makeSearchPage() {
	global $clustername;

	$tr = new TarchRrd();
	$tr->makeJobRrds( $clustername, 'gb-r1n1.irc.sara.nl', 'testje', 1114936341, 1115015563 );
	// bla!
}

?>
