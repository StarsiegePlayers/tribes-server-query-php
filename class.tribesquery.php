<?
Class TribesQuery
{
	//connection info
	var $server;
	var $port;
	
	//socket pointer
	var $sp;
	
	//info arrayz
	var $serverInfo;
	var $teamInfo;
	var $teamList;
	
	function TribesQuery($server, $port)
	{
		$this->serverInfo	= array();
		$this->teamInfo		= array();
		$this->teamList		= array();
		$this->sp			= "";

		$this->server	= $server;
		$this->port		= $port;
	}

	//socket read functions
	function GetByte() {return Ord(fread($this->sp, 1));}
	function GetInt16() {for($i=0, $s=""; $i<2; $i++) {$s = dechex($this->GetByte()) . $s;} return (hexdec($s));}
	function GetInt32()	{for($i=0, $s=""; $i<4; $i++) {$s = dechex($this->GetByte()) . $s;}return (hexdec($s));}
	function GetPascalString() {$len=$this->GetByte(); return ($len>0)?fread($this->sp,$len):"";}

	//player sort functions
	function CmpNameAsc($a, $b) {$al = strtolower($a["Name"]);$bl = strtolower($b["Name"]);if ($al == $bl) return 0; return ($al > $bl) ? +1 : -1;}
	function CmpPingAsc($a, $b) {if ($a["Ping"] == $b["Ping"]) return 0; return ($a["Ping"] > $b["Ping"]) ? +1 : -1;}
	function CmpScoreAsc($a, $b) {if ($a["Score"] == $b["Score"]) return 0; return ($a["Score"] > $b["Score"]) ? +1 : -1;}

	function CmpNameDesc($a, $b) {$al = strtolower($a["Name"]);$bl = strtolower($b["Name"]);if ($al == $bl) return 0; return ($al < $bl) ? +1 : -1;}
	function CmpPingDesc($a, $b) {if ($a["Ping"] == $b["Ping"]) return 0; return ($a["Ping"] < $b["Ping"]) ? +1 : -1;}
	function CmpScoreDesc($a, $b) {if ($a["Score"] == $b["Score"]) return 0; return ($a["Score"] < $b["Score"]) ? +1 : -1;}

	function SortByName($dir) {$func = ($dir == "desc") ? "CmpNameDesc" : "CmpNameAsc"; for($i=-1;$i<$this->serverInfo["Teams"];$i++) usort($this->teamList[$i], array ("TribesQuery", $func));}
	function SortByPing($dir) {$func = ($dir == "desc") ? "CmpPingDesc" : "CmpPingAsc"; for($i=-1;$i<$this->serverInfo["Teams"];$i++) usort($this->teamList[$i], array ("TribesQuery", $func));}
	function SortByScore($dir) {$func = ($dir == "desc") ? "CmpScoreDesc" : "CmpScoreAsc"; for($i=-1;$i<$this->serverInfo["Teams"];$i++) usort($this->teamList[$i], array ("TribesQuery", $func));}
	
	function Query()
	{
		$result = false;
		
		$this->sp = @fsockopen("udp://".$this->server, $this->port, $errno, $errstr, 1);
		if ($this->sp)
		{
			//time the search
			$time__start = gettimeofday();

			//request server info
			fwrite($this->sp, "\x62\x01\x02", 3);

			//jump over header
			$this->GetInt32();

			$time__end = gettimeofday();
			$time__elapsed = (float)($time__end['sec'] - $time__start['sec']) + ((float)($time__end['usec'] - $time__start['usec'])/1000000);

			$this->serverInfo["Ping"]       = ($time__elapsed*1000)." ms";
			$this->serverInfo["Game"]		= $this->GetPascalString();
			$this->serverInfo["Version"]	= $this->GetPascalString();
			$this->serverInfo["ServerName"]	= $this->GetPascalString();
			
			$this->serverInfo["Dedicated"]	= $this->GetByte();
			$this->serverInfo["Password"]	= $this->GetByte();
			$this->serverInfo["Players"]	= $this->GetByte();
			$this->serverInfo["MaxPlayers"]	= $this->GetByte();
			$this->serverInfo["CpuSpeed"]	= $this->GetInt16();
			
			$this->serverInfo["Mod"]		= $this->GetPascalString();
			$this->serverInfo["Type"]		= $this->GetPascalString();
			$this->serverInfo["Mission"]	= $this->GetPascalString();
			$this->serverInfo["Info"]		= $this->GetPascalString();
			$this->serverInfo["Teams"]		= $this->GetByte();
			
			$this->serverInfo["TeamScoreHeader"]	= $this->GetPascalString();
			$this->serverInfo["PlayerScoreHeader"]	= $this->GetPascalString();
			
			//find which column holds the score (fstat >:|)
			$playerFields = preg_split("/\t/", $this->serverInfo["PlayerScoreHeader"], -1);
			for ($i=0, $playerScoreColumn=2; $i<count($playerFields); $i++)
			{
				$playerFields[$i] = substr($playerFields[$i], 1, strlen($playerFields[$i])-1);
				
				if ($playerFields[$i] == "Score")
					$playerScoreColumn = $i;
			}

			$teamFields = preg_split("/\t/", $this->serverInfo["TeamScoreHeader"], -1);
			for ($i=0, $teamScoreColumn=2; $i<count($teamFields); $i++)
			{
				$teamFields[$i] = substr($teamFields[$i], 1, strlen($teamFields[$i])-1);
				
				if ($teamFields[$i] == "Score")
					$teamScoreColumn = $i;
			}
			

			$this->teamList[-1]          = array();
			$this->teamInfo[-1]["Name"]  = "Observer";
			$this->teamInfo[-1]["Score"] = 0;

			for ($i=0; $i<$this->serverInfo["Teams"]; $i++)
			{
				$this->teamList[$i]			= array();
				$this->teamInfo[$i]["Name"]	= $this->GetPascalString();
				$score						= $this->GetPascalString();
				
				$fields = preg_split("/\t/", $score, -1);
				$this->teamInfo[$i]["Score"] = trim($fields[$teamScoreColumn]);
			}

			for ($i=0; $i< $this->serverInfo["Players"]; $i++)
			{
				$ping		= $this->GetByte()*4;
				$pl			= $this->GetByte();
				$team		= $this->GetByte();
				$name		= $this->GetPascalString();
				$score		= $this->GetPascalString();
				
				$team       = ($team==255) ? -1 : $team;
				
				$fields = preg_split("/\t/", $score, -1, PREG_SPLIT_NO_EMPTY);
				$score = trim($fields[$playerScoreColumn]);
				
				$this->teamList[$team][] = array("Name"=>$name, "Ping"=>$ping, "PL"=>$pl, "Score"=>$score);
			}
			
			$this->SortByScore("desc");
			
			fclose($this->sp);
			$result = true;
		}
		else
		{
			//die("Error $errno: $errstr");
			$result = false;
		}
		
		return $result;
	}
}
?>