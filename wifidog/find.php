<html>
<body>
<h1>This script will find any blank lines and/or characters outside
PHP tags</h1>
<?php
   // Quick and dirty PHP script that will find all files containing
// 2005 - Francois Proulx
   function findBlankLines($dir)
   {
           $dir_handle = opendir($dir);
           while ($file = readdir($dir_handle))
           {
               if(is_dir($file) && $file != "." && $file != "..")
                       findBlankLines($file);
               else if(preg_match("/\.php$/", $file))
               {
                       $path = "{$dir}/{$file}";
                       // match any characters occuring once or + outside PHP tags
                       if(preg_match("/^.+<\?php(.+)?\?>$/s", file_get_contents($path)))
                               echo "File : $path <br>";

                       if(preg_match("/^<\?php(.+)?\?>.+$/s", file_get_contents($path)))
                               echo "File : $path <br>";
               }
           }
           closedir($dir_handle);
           return;
   }

   findBlankLines(".");

?>
</body>
</html>