<?php

global $db;

echo "dropping table cidpopup..";
sql("DROP TABLE IF EXISTS `cidpopup`");
echo "done<br>\n";

echo "dropping table cidpopup_instance..";
sql("DROP TABLE IF EXISTS `cidpopup_instance`");
echo "done<br>\n";
?>
