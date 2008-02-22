<?php
// Will parse {x} (must be numeric) from hostname
// and use as X (horizontal) coordinate of nodes
//
// Will parse {y} (must be numeric) from hostname
// and use as Y (vertical) coordinate of nodes
//
// You can use both X and Y, or only one of them
//
$SORTBY_HOSTNAME = "rack{x}node{y}.mydomain.mytld";

// Should we organize the nodes based on above X,Y coordinates
// ascending or descending
//
$SORT_ORDER = "asc";

// What is the label of X and/or Y
//
$SORT_XLABEL = "rack";
$SORT_YLABEL = "node";
?>
