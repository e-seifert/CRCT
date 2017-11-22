<?php
$txt = '';
$myfile = fopen("data.txt", "w") or die("Unable to open file!");

for($i=0;$i<100;$i++){
    $txt .= "Guest".rand(1000,9999);
    $txt .= ",";
    $txt .= date("Y.m.d", mt_rand());
    $txt .= ",";
    $txt .= date("H.m.s", mt_rand());
    $txt .= ",";
    $txt .= date("H.m.s", mt_rand());
    $txt .= "\n";
}

fwrite($myfile, $txt);
fclose($myfile);
?> 