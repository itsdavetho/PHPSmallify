<?php
if(isset($_POST['php'])) {
	header('Content-type: text/plain');
	$obfuscateVariables = isset($_POST['obfuscateVariables']) ? true : false;
	$obfuscateFunctions = isset($_POST['obfuscateFunctions']) ? true : false;
	$obfuscateStrings = isset($_POST['obfuscateStrings']) ? true : false;
	require 'class.PHPSmallify.php';
	$phpSmallify = new Orpheus\PHPSmallify(null, $_POST['php']);
	$results = $phpSmallify->smallify(true, true, $obfuscateVariables, $obfuscateFunctions, $obfuscateStrings, false);
	die($results['smallified']);
}
?>
<!DOCTYPE html>
<html>
	<head>
		<title>PHP Smallifier / Obfuscator</title>
	</head>
	<body>	
		<form method="POST" action="smallify.php">
			<textarea name="php" style="width: 100%; height: 480px;"></textarea>
			<p><input type="checkbox" name="obfuscateVariables" /> Obfuscate variables</p>
			<p><input type="checkbox" name="obfuscateFunctions" /> Obfuscate function names</p>
			<p><input type="checkbox" name="obfuscateStrings" /> Obfuscate strings</p>
			<input type="submit" name="submit" value="Go" />
		</form>
	</body>
</html>
