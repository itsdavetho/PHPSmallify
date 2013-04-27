<!DOCTYPE html>
<html>
	<head>
		<title>Minify your PHP</title>
		<style type="text/css">
			body {
				font-family: arial;
				background-color: #EEE;
			}

			textarea[name="php"] {
				width: 100%;
				height: 250px;
			}
		</style>
	</head>

	<body>
		<?php
		if(isset($_POST['php'])) {
			require 'class.PHPSmallify.php';
			$filename = __DIR__ . '/tmp/' . md5($_POST['php'] . time()) . '.tmp.php';
			file_put_contents($filename, $_POST['php']);
			chmod($filename, 0777);
			$iterator = new Orpheus\PHPSmallify($filename);

			$encodeStrings = true;
			$stripWhiteSpace = true;
			$results = $iterator->smallify();

			echo '<textarea name="php">' . htmlentities($results['smallified']) . '</textarea>';
		}
		echo '
		<form method="POST" action="">
			<textarea name="php"></textarea>
			<input type="checkbox" name="stripComments" value="1">Remove comments <input type="checkbox" name="encodeStrings" value="1">Encode strings <input type="checkbox" name="stripWhitespace" value="1">Remove whitespace
			<p><input type="submit" value="Minify" /></p>
		</form>';
		?>
	</body>
</html>