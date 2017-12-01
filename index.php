<!DOCTYPE html>
<?php
require(__DIR__."/config/config.php");
$p = "";
if (isset($_POST["problem"])) {
	$p = $_POST["problem"];
}
?>
<html>
<head>
	<meta charset="utf-8">
	<title><?= $C["sitetitle"] ?></title>
</head>
<body>
<h3><?= $C["sitename"] ?></h3>
<script type="text/javascript">
function getmain() {
	var m = code.value.match(/class (.+){/);
	if (m) {
		mainclass.value = m[1];
	}
}
</script>
<form method="post">
	題號：<select name="problem">
	<?php
	foreach ($C["problemname"] as $id => $_) {
		?><option value="<?=$id?>" <?php echo (!isset($C["testdata"][$id])?"disabled":"")?> <?php echo (($id==$p)?"selected":""); ?> ><?=$C['problemname'][$id]?> </option><?php
	}
	?>
	</select><br>
	Main函數所在Class名稱：<input type="text" name="mainclass" id="mainclass" required="" value="<?php echo $_POST["mainclass"]; ?>"><button type="button" onclick="getmain();">自動從程式碼取得</button><br>
	程式碼：<br>
	<textarea name="code" id="code" cols="80" rows="5" required=""><?php echo $_POST["code"]; ?></textarea><br>
	<input type="submit" name="" value="進行評測">
</form>
<hr>
<?php
set_time_limit(10);
if (isset($_POST["code"])) {
	$ok = true;
	if (!isset($C["testdata"][$p])) {
		echo '<span style="color: red;">題號錯誤</span><br>';
		$ok = false;
	}
	if ($ok) {
		$testcnt = $C["testdata"][$p];
		putenv('LC_ALL=en_US.UTF-8');

		exec("isolate --cleanup 2>&1", $output, $return);
		unset($output);
		exec("isolate --init 2>&1", $output);
	}
	if ($ok && (count($output) < 1 || !is_dir($output[0]."/box"))) {
		echo "建立執行環境失敗<br>";
		$ok = false;
	}
	if ($ok) {
		$sandbox = $output[0]."/box";

		file_put_contents($sandbox."/program.java", $_POST["code"]);
		exec("dos2unix ".$sandbox."/program.java");

		for ($i=1; $i <= $testcnt; $i++) {
			exec("dos2unix /home/xiplus/public_html/judge/testdata/$p/$i.in");
			exec("dos2unix /home/xiplus/public_html/judge/testdata/$p/$i.out");
			exec("cp ".__DIR__."/testdata/$p/$i.in ".$sandbox."/");
		}

		chdir($sandbox);

		unset($output);
		exec("/usr/lib/jvm/java-gcj/bin/javac -encoding utf8 program.java 2>&1", $output, $return);
		?>
		編譯器訊息：<br>
		<textarea cols="80" rows="5" readonly><?php echo implode("\n", $output); ?></textarea><br>
		<?php
		if ($return === 255) {
			echo '<span style="color: red;">編譯發生錯誤</span><br>';
		} else {
			if (trim($_POST["mainclass"]) === "") {
				echo '<span style="color: red;">沒有提供Main函數所在Class名稱</span><br>';
			} else {
				?>
				<table>
				<tr>
					<th>輸入</th>
					<th>輸出</th>
					<th>參考輸出</th>
					<th>與參考輸出相似率</th>
				</tr>
				<?php
				for ($i=1; $i <= $testcnt; $i++) { 
					unset($output);
					exec('isolate --env="LC_ALL=en_US.UTF-8" --run /usr/lib/jvm/java-gcj/bin/java '.$_POST["mainclass"]." -i $i.in -o $i.out -r $i.err", $output, $return);
					?>
					<tr>
						<td valign="top">
							<textarea cols="20" rows="4" readonly><?php
								echo file_get_contents("$i.in");
							?></textarea>
						</td>
						<td valign="top">
							<textarea cols="60" rows="4" readonly><?php
								$userout = file_get_contents("$i.out");
								echo $userout;
								echo "\n----------\n";
								echo file_get_contents("$i.err");
							?></textarea>
						</td>
						<td valign="top">
							<textarea cols="60" rows="4" readonly><?php
								echo file_get_contents(__DIR__."/testdata/$p/$i.out"); 
							?></textarea>
						</td>
						<td>
							<?php
							$userout = preg_replace("/\s/", "", $userout);
							$ansout = file_get_contents(__DIR__."/testdata/$p/$i.out");
							$ansout = preg_replace("/\s/", "", $ansout);
							similar_text($userout, $ansout, $percent);
							echo round($percent)."%";
							?>
						</td>
					</tr>
					<?php
				}
				?>
				</table>
				<?php
			}
		}
		exec("isolate --cleanup 2>&1", $output, $return);
	}
}
?>
<div style="height: 200px"><br>Developed by xiplus.</div>
</body>
</html>
