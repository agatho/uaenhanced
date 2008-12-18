<?
require(dirname(__FILE__)."/microtimer.php");

$microTimer->start();

$T = tmpl_open(dirname(__FILE__)."/t1.html");

for($i=0; $i<50000; $i++)
$A[] = array(
	'cell/data'	=> 'CELL_VALUE'
);

tmpl_set($T, '/row', $A);

$T2 = tmpl_open(dirname(__FILE__).'/t1a.html');
tmpl_set($T2, 'test', 'TEST_VALUE');
tmpl_set($T, 'tag', $T2);


$html = tmpl_parse($T, '/');

echo $html;
$s = tmpl_structure($T);
echo("<pre>\n\n"); print_r($s); echo("\n\n");

echo("50.000+ tags have been processed\nTIME: ".sprintf("%.2f", $microTimer->stop())." seg\n\n");
			
?>