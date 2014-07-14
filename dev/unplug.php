<?php

// Replace the name with a unique hash name.
function replace_name($name) 
{
   return $name . '_' . substr(sha1(uniqid(rand(), true)), 0, 5);
}

// Read the plugin file
$file = file_get_contents('dummy_plugin.php', true);

// Output the original file contents
var_dump($file);

// Class array
$classes = array();

// Get all the classes.
$m = preg_match_all('/class\s+([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)/', $file, $matches);

// Check we have some matches.
if($m > 0)
{
	// Give each of the classes a unique name.
	foreach ($matches[1] as $name) 
	{
		if(strtolower($name) !== 'plugin')
		{
			$classes[$name] = replace_name($name);

			// Replace in the file with new name.
			$file = preg_replace('/class\s+('.$name.')/', '$0'.$classes[$name], $file);
		}
		else
		{
			$classes[$name] = $name;
		}
	}
}

// Output the replaced class names
var_dump($file);

// Get a list of classes that extend/impliment others.
$m = preg_match_all('/class\s+([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)\s+(extends|implements\b)\s+([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)\s*?{/', $file, $matches); 

if($m > 0)
{
	foreach ($matches[3] as $extended) 
	{
		if(array_key_exists (strtolower($extended), $classes))
		{
			echo "FOUND ONE<br>";
			$classes[$name] = replace_name($name);

			// Replace in the file with new name.
			#$file = preg_replace('//', , $file);
		}
	}
}


#var_dump($file);

// Dump the class array.
#var_dump($classes);

#var_dump('----------------------------------------------');

// Find classes that extend
$m = preg_match_all('/class\s+([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)\s+(extends|implements\b)\s+([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)\s*?{/', $file, $matches); 

#var_dump($m);

#$file = preg_replace('/(.*?class\s+)'.$class.'(\s+extends\s+plugin\s*){/', '$1'.$uname.'$2', $file);


// Read the file
$file = file_get_contents('dummy_plugin.php', true);

$all_classes = '/class\s+([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)/';

$base_classes = '/class\s+([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)\s*?{/';

$extend_classes = '/class\s+([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)\s+extends\s+([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)\s*?{/';

$extend_classes_2 = '/class\s+([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)\s+(extends|implements+)\s+([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)\s*?{/';

$plugin_classes = '/class\s+([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)\s+extends\s+plugin\s*?{/';

preg_match_all($extend_classes , $file, $matches);
#var_dump($matches);

?>