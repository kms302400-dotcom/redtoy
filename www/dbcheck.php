<?php
$conn = @mysql_connect("localhost", "setting", "set**0605") or die("DBConnErr");
@mysql_select_db("setting") or die("DBSelectErr");
echo("SettingOK");
mysql_close($conn);
?>
