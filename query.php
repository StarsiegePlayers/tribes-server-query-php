<?
	require_once("class.tribesquery.php");

	//query hell
	$query = new TribesQuery("166.70.103.20", "28001");
	$query->Query();
	$query->SortByScore("desc");
	
	$serverinfo	= $query->serverInfo;
	$teaminfo	= $query->teamInfo;
	$teamlist	= $query->teamList;
?>

<b><?= $serverinfo["ServerName"] ?> running <?= $serverinfo["Game"].$serverinfo["Version"] ?></b> <br />
<br/>
<b>Server Settings</b>:<br />
Dedicated? <b><?= ($serverinfo["Dedicated"]) ? "Y" : "N" ?></b><br />
Password? <b><?= ($serverinfo["Password"]) ? "Y" : "N" ?></b><br />
Players: <b><?= $serverinfo["Players"]."/".$serverinfo["MaxPlayers"] ?></b><br />
CPU Speed: <b><?= $serverinfo["CpuSpeed"] ?></b><br />
<br/>
<b>Game Settings</b><br />
Mod: <b><?= $serverinfo["Mod"] ?></b><br />
Type: <b><?= $serverinfo["Type"] ?></b><br />
Mission: <b><?= $serverinfo["Mission"] ?></b><br />
Info: <b><?= $serverinfo["Info"] ?></b><br />
Teams: <b><?= $serverinfo["Teams"] ?></b><br />
<br />

<b>Teams</b><br />
<br />
<?
	for ($i=0; $i<$serverinfo["Teams"]; $i++)
	{
		echo ($teaminfo[$i]["Name"].", score = <b>". $teaminfo[$i]["Score"]. "</b><br />\n");
		echo "<b>Players</b><br />\n";
		
		foreach ($teamlist[$i] as $pl)
		{
			echo ($pl["Name"].", score = <b>".$pl["Score"]."</b>, ping = <b>".$pl["Ping"]."</b><br />\n");
		}
	}
?>

