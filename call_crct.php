<?php
$uploaddir = '/crct/';
$uploadfile = $uploaddir . 'data.txt';

echo '
<html><body>
<!--<body onload="document.crct.submit()">-->
<!-- The data encoding type, enctype, MUST be specified as below -->
<form enctype="multipart/form-data" action="http://192.168.0.12:80/crct/crct.php" method="POST" name="crct">
    <!-- MAX_FILE_SIZE must precede the file input field -->
    <input type="hidden" name="MAX_FILE_SIZE" value="30000" />
    <!-- Name of input element determines name in $_FILES array -->
    <input name="datafile" type="file" value="/crct/data.txt" />
    <input type="submit" value="Send File" />
</form>
</body></html>
' // end echo
?>