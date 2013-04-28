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
			$phpSmallify = new Orpheus\PHPSmallify($filename);
			$results = $phpSmallify->smallify();

			echo '<textarea name="php">' . htmlentities($results['smallified']) . '</textarea>';
		}
		echo '
		<form method="POST" action="">
			<textarea name="php"></textarea>
			<p><input type="submit" value="Minify" /></p>
		</form>';
		?>
	</body>
</html>
