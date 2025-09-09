<?php
error_reporting(E_ALL);
if(!extension_loaded('pdo_pgsql')) die('Install pdo_pgsql extension!');
session_name('PG');
session_start();
$bg=2;
$step=20;
$version="1.0";
$bbs=['False','True'];
$deny=['information_schema','pg_catalog','temp_tables','pg_toast'];
class DBT {
	private $_cnx,$_query,$_fetch=[],$_num_col,$dbty;
	private static $instance=NULL;
	public static function factory($host,$user,$pwd,$db=''){
		if(!isset(self::$instance))
		try {
		self::$instance=new DBT($host,$user,$pwd,$db);
		} catch(Exception $ex){
		return false;
		}
		return self::$instance;
	}
	public function __construct($host,$user,$pwd,$db){
		$host=explode(":",$host);
		$dsn="pgsql:host={$host[0]};port=".(empty($host[1])?5432:$host[1]).($db==""?"":";dbname={$db}");
		$this->_cnx=new PDO($dsn,$user,$pwd);
		$this->_cnx->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);
		$this->_cnx->query("SET NAMES 'UTF8'");
	}
	public function query($sql){
		try {
		$this->_query=$this->_cnx->query($sql);
		return $this;
		} catch(Exception $e){
		return false;
		}
	}
	public function begin(){
		return $this->_cnx->beginTransaction();
	}
	public function commit(){
		return $this->_cnx->commit();
	}
	public function fetch($mode=0){
		if($mode==1 || $mode==2){
		switch($mode){
		case 1: $this->_query->setFetchMode(PDO::FETCH_NUM); break;
		case 2: $this->_query->setFetchMode(PDO::FETCH_ASSOC); break;
		}
		return $this->_query->fetchAll();
		}else{
		return $this->_query->fetch(PDO::FETCH_NUM);
		}
	}
	public function num_row(){
		return $this->_query->rowCount();
	}
	public function num_col(){
		return $this->_query->columnCount();
	}
}
class ED {
	public $con,$path,$sg,$u_db,$fieldtype;
	public function __construct(){
	$pi=(isset($_SERVER['PATH_INFO']) ? $_SERVER['PATH_INFO']:@getenv('PATH_INFO'));
	$this->sg=preg_split('!/!',$pi,-1,PREG_SPLIT_NO_EMPTY);
	$scheme='http'.(empty($_SERVER['HTTPS'])===true || $_SERVER['HTTPS']==='off' ? '':'s').'://';
	$r_uri=isset($_SERVER['PATH_INFO'])===true ? $_SERVER['REQUEST_URI']:$_SERVER['PHP_SELF'];
	$script=$_SERVER['SCRIPT_NAME'];
	$this->path=$scheme.$_SERVER['HTTP_HOST'].(strpos($r_uri,$script)===0 ? $script:rtrim(dirname($script),'/.\\')).'/';
	$this->fieldtype=["serial","bigserial","\"any\"","\"char\"","abstime","aclitem","anyarray","anyelement","anyenum","anynonarray","anyrange","bigint","bit","bit varying","boolean","box","bytea","character","character varying","cid","cidr","circle","cstring","date","daterange","double precision","event_trigger","fdw_handler","gtsvector","index_am_handler","inet","information_schema.cardinal_number","information_schema.character_data","information_schema.sql_identifier","information_schema.time_stamp","information_schema.yes_or_no","int2vector","int4range","int8range","integer","internal","interval","json","jsonb","language_handler","line","lseg","macaddr","money","name","numeric","numrange","oid","oidvector","opaque","path","pg_ddl_command","pg_lsn","pg_node_tree","point","polygon","real","record","refcursor","regclass","regconfig","regdictionary","regnamespace","regoper","regoperator","regproc","regprocedure","regrole","regtype","reltime","smallint","smgr","text","tid","time with time zone","time without time zone","timestamp with time zone","timestamp without time zone","tinterval","trigger","tsm_handler","tsquery","tsrange","tstzrange","tsvector","txid_snapshot","unknown","uuid","void","xid","xml"];
	}
	public function sanitize($el){
		return preg_replace(['/[^A-Za-z0-9]/'],'_',trim($el));
	}
	public function utf($fi){
		if(function_exists("iconv") && preg_match("~^\xFE\xFF|^\xFF\xFE~",$fi)) $fi=iconv("utf-16","utf-8",$fi);
		return $fi;
	}
	public function form($url,$enc=''){
		return "<form action='".$this->path.$url."' method='post'".($enc==1 ? " enctype='multipart/form-data'":"").">";
	}
	public function fieldtypes($slt=''){
		$ft='';
		foreach($this->fieldtype as $fty){
		$ft.="<option value='$fty'".(($slt!='' && $fty==$slt)?" selected":"").">$fty</option>";
		}
		return $ft;
	}
	public function post($idxk='',$op=''){
		if($idxk==='' && !empty($_POST)) return ($_SERVER['REQUEST_METHOD']==='POST' ? TRUE:FALSE);
		if(!isset($_POST[$idxk])) return FALSE;
		if(is_array($_POST[$idxk])){
			if(isset($op) && is_numeric($op)){
			return $_POST[$idxk][$op];
			}else{
			$aout=[];
			foreach($_POST[$idxk] as $key=>$val){
			if($val !='') $aout[$key]=$val;
			}
			}
		} else $aout=$_POST[$idxk];
		if($op=='i') return isset($aout);
		if($op=='e') return empty($aout);
		if($op=='!i') return !isset($aout);
		if($op=='!e') return !empty($aout);
		return $aout;
	}
	public function redir($way='',$msg=[]){
		if(count($msg) > 0){
		foreach($msg as $ks=>$ms) $_SESSION[$ks]=$ms;
		}
		header('Location: '.$this->path.$way);exit;
	}
	public function enco($str){
		$salt=$_SERVER['HTTP_USER_AGENT'];
		$count=strlen($str);
		$str=(string)$str;
		$kount=strlen($salt);
		$x=0;$y=0;
		$eStr="";
		while($x < $count){
			$char=ord($str[$x]);
			$keyS=is_numeric($salt[$y]) ? $salt[$y]:ord($salt[$y]);
			$encS=$char + $keyS;
			$eStr.=chr($encS);
			++$x;++$y;
			if($y==$kount) $y=0;
		}
		return base64_encode(base64_encode($eStr));
	}
	public function deco($str){
		$salt=$_SERVER['HTTP_USER_AGENT'];
		$str=base64_decode(base64_decode($str));
		$count=strlen($str);
		$str=(string)$str;
		$kount=strlen($salt);
		$x=0;$y=0;
		$eStr="";
		while($x < $count){
			$char=ord($str[$x]);
			$keyS=is_numeric($salt[$y]) ? $salt[$y]:ord($salt[$y]);
			$decS=$char - $keyS;
			$eStr.=chr($decS);
			++$x;++$y;
			if($y==$kount) $y=0;
		}
		return $eStr;
	}
	public function check($level=[],$param=[]){
		if(isset($_SESSION['token']) && !empty($_SESSION['user'])){//check login
			$usr=$_SESSION['user'];
			$pwd=$this->deco($_SESSION['token']);
			$ho=$_SESSION['host'];
			$this->con=DBT::factory($ho,$usr,$pwd,isset($param['db'])?$param['db']:($this->sg[1]??''));
			if(!$this->con) $this->redir("50",['err'=>"Can't connect to the server"]);
			$h='HTTP_X_REQUESTED_WITH';
			if(isset($_SERVER[$h]) && !empty($_SERVER[$h]) && strtolower($_SERVER[$h]) == 'xmlhttprequest') session_regenerate_id(true);
		}else{
			$this->redir("50");
		}
		//list DBs
		$u_db=$this->con->query("SELECT datname FROM pg_database WHERE has_database_privilege('$usr',datname,'CONNECT') AND datistemplate=FALSE")->fetch(1);
		$this->u_db=call_user_func_array('array_merge',$u_db);
		//check db
		if(isset($this->sg[1])) $db=$this->sg[1];
		if(in_array('1',$level)){
			if(!in_array($db,$this->u_db)) $this->redir();
		}
		if(in_array('2',$level)){//check table
			$tb=$this->sg[2];
			$q_com=$this->con->query("SELECT 1 FROM information_schema.tables WHERE table_schema='public' AND table_catalog='$db' AND table_name='$tb'");
			if(!$q_com->num_row()) $this->redir("5/$db");
			$q_=$this->con->query("SELECT COUNT(*) FROM $tb");
			if(!$q_) $this->redir("5/$db",['err'=>"No records"]);
		}
		if(in_array('3',$level)){//check field
			$field=$this->sg[3];
			$sql1="SELECT 1 FROM pg_attribute WHERE attrelid='{$tb}'::regclass AND attname='{$field}' AND NOT attisdropped";
			$qr=$this->con->query($sql1);
			if(!$qr || $qr->num_row()==0) $this->redir($param['redir']."/$db/$tb");
			if(isset($this->sg[5])){
			$field2=$this->sg[5];
			$sql2="SELECT 1 FROM pg_attribute WHERE attrelid='{$tb}'::regclass AND attname='{$field2}' AND NOT attisdropped";
			$qr2=$this->con->query($sql2);
			if(!$qr2 || $qr2->num_row()==0) $this->redir($param['redir']."/$db/$tb");
			}
		}
		if(in_array('4',$level)){//check paginate
			if(!is_numeric($param['pg']) || $param['pg'] > $param['total'] || $param['pg'] < 1) $this->redir($param['redir']);
		}
		if(in_array('5',$level)){//check spp
			$tb=$this->sg[2];
			$sp=$this->sg[3];
			switch($sp){
			case 'view':
				$q=$this->con->query("SELECT viewname FROM pg_views WHERE schemaname='public' AND viewname='$tb'");
				if(!$q || $q->num_row()==0) $this->redir("5/$db");
				break;
			case 'trigger':
				$q=$this->con->query("SELECT 1 FROM pg_trigger WHERE tgname='$tb'");
				if(!$q || $q->num_row()==0) $this->redir("5/$db");
				break;
			case 'procedure': case 'function':
				$q=$this->con->query("SELECT 1 FROM information_schema.routines WHERE routine_schema='public' AND routine_type='".strtoupper($sp)."' AND routine_name='$tb'");
				if(!$q || $q->num_row()==0) $this->redir("5/$db");
				break;
			default: $this->redir("5/$db");
			}
		}
		if(in_array('6',$level)){//check user
			$u1=base64_decode($this->sg[1]);
			$q_e=$this->con->query("SELECT 1 FROM pg_roles WHERE rolname='{$u1}'");
			if(!$q_e || $q_e->num_row()==0) $this->redir("52");
		}
	}
	public function menu($db='',$tb='',$left='',$sp=[]){
		$str='';
		if($db==1 || $db!='') $str.="<div class='l2'><ul><li><a href='{$this->path}'>Databases</a></li>";
		if($db!='' && $db!=1) $str.="<li><a href='{$this->path}31/$db'>Export</a></li><li><a href='{$this->path}5/$db'>Tables</a></li>";

		$dv="<li class='divider'>---</li>";
		if($tb!="") $str.=$dv."<li><a href='{$this->path}10/$db/$tb'>Structure</a></li><li><a href='{$this->path}20/$db/$tb'>Browse</a></li><li><a href='{$this->path}21/$db/$tb'>Insert</a></li><li><a href='{$this->path}24/$db/$tb'>Search</a></li><li><a class='del' href='{$this->path}25/$db/$tb'>Empty</a></li><li><a class='del' href='{$this->path}26/$db/$tb'>Drop</a></li>";//table
		if(!empty($sp[1]) && $sp[0]=='view') $str.=$dv."<li><a href='{$this->path}40/$db/".$sp[1]."/view'>Structure</a></li><li><a href='{$this->path}20/$db/".$sp[1]."'>Browse</a></li><li><a class='del' href='{$this->path}49/$db/".$sp[1]."/view'>Drop</a></li>";//view
		if($db!='') $str.="</ul></div>";

		if($db!="" && $db!=1){//db select
		$str.="<div class='l3 auto'><select onchange='location=this.value;'><optgroup label='Databases'>";
		foreach($this->u_db as $udb) $str.="<option value='{$this->path}{$this->sg[0]}/$udb'".($udb==$db?" selected":"").">$udb</option>";
		$str.="</optgroup></select>";

		$q_tbs=[]; $c_sp=empty($sp) ? "":count($sp);
		if($tb!="" || $c_sp >1){//table select
		$q_tbs=$this->con->query("SELECT table_name,table_type FROM information_schema.tables WHERE table_schema='public' AND table_catalog='$db' ORDER BY table_type")->fetch(1);
		$sl2="<select onchange='location=this.value;'>";
		$qtype='';
		foreach($q_tbs as $r_tbs){
		if($qtype !=$r_tbs[1]){
		if($qtype !='') $sl2.='</optgroup>';
		$sl2.='<optgroup label="'.$r_tbs[1].'s">';
		}
		$in=($r_tbs[1]=='VIEW'?[20,40]:[10,20,21,24]);
		$sl2.="<option value='{$this->path}".(in_array($this->sg[0],$in)?$this->sg[0]:20)."/$db/".$r_tbs[0]."'".($r_tbs[0]==$tb || ($c_sp >1 && $r_tbs[0]==$sp[1])?" selected":"").">".$r_tbs[0]."</option>";
		$qtype=$r_tbs[1];
		}
		if($qtype!='') $sl2.='</optgroup>';
		if($c_sp <1 || $sp[0]=='view') $str.=$sl2."</select>".((!empty($_SESSION['_sqlsearch_'.$db.'_'.$tb]) && $this->sg[0]==20) ? " [<a href='{$this->path}24/$db/$tb/reset'>reset search</a>]":"");
		}
		$str.="</div>";
		}

		$str.="<div class='container'>";
		if($left==2) $str.="<div class='col3'>";
		$f=1;$nrf_op='';
		while($f<50){
		$nrf_op.="<option value='$f'>$f</option>";
		++$f;
		}
		if($left==1) $str.="<div class='col1'>".
		$this->form("30/$db")."<textarea name='qtxt'></textarea><br/><button type='submit'>Run sql</button></form>
		<h3>Import</h3><small>sql, csv, json, xml, gz, zip</small>".$this->form("30/$db",1)."<input type='file' name='importfile'/>
		<input type='hidden' name='send' value='ja'/><br/><button type='submit'>Upload (&lt;".ini_get("upload_max_filesize")."B)</button></form>
		<h3>Create Table</h3>".$this->form("6/$db")."<input type='text' name='ctab'/><br/>
		Number of fields<br/><select name='nrf'>$nrf_op</select><br/><button type='submit'>Create</button></form>
		<h3>Rename DB</h3>".$this->form("3/$db")."<input type='text' name='rdb'/><br/><button type='submit'>Rename</button></form>
		<h3>Create</h3><a href='{$this->path}40/$db'>View</a><a href='{$this->path}41/$db'>Trigger</a><a href='{$this->path}42/$db'>Routine</a></div><div class='col2'>";
		return $str;
	}
	public function pg_number($pg,$totalpg){
		if($totalpg > 1){
		if($this->sg[0]==20) $link=$this->path."20/".$this->sg[1]."/".$this->sg[2];
		elseif($this->sg[0]==5) $link=$this->path."5/".$this->sg[1];
		$pgs='';$k=1;
		while($k <= $totalpg){
		$pgs.="<option ".(($k==$pg) ? "selected>":"value='$link/$k'>")."$k</option>";
		++$k;
		}
		$lft=($pg>1?"<a href='$link/1'>First</a><a href='$link/".($pg-1)."'>Prev</a>":"");
		$rgt=($pg < $totalpg?"<a href='$link/".($pg+1)."'>Next</a><a href='$link/$totalpg'>Last</a>":"");
		return "<div class='pg'>$lft<select onchange='location=this.value;'>$pgs</select>$rgt</div>";
		}
	}
	public function imp_csv($fname,$body){
		$exist=$this->con->query("SELECT 1 FROM $fname");
		if(!$exist) $this->redir("5/".$this->sg[1],['err'=>"Table not exist"]);
		$fname=$this->sanitize($fname);
		$e=[];
		if(@is_file($body)) $body=file_get_contents($body);
		$body=$this->utf($body);
		$body=preg_replace('/^\xEF\xBB\xBF|^\xFE\xFF|^\xFF\xFE/','',$body);
		//delimiter
		$delims=[';'=>0,','=>0];
		foreach($delims as $dl=> &$cnt) $cnt=count(str_getcsv($body,$dl));
		$mark=array_search(max($delims),$delims);
		//data
		$data=explode("\n",str_replace(["\r\n","\n\r","\r"],"\n",$body));
		$row=null;
		foreach($data as $item){
			$row.=$item;
			if(trim($row)===''){
			$row=null;
			continue;
			}elseif(substr_count($row,'"') % 2 !==0){
			$row.=PHP_EOL;
			continue;
			}
			$rows[]=str_getcsv($row,$mark,'"','"');
			$row=null;
		}
		foreach($rows as $k=>$rw){
		if($k>0){
		$e1="INSERT INTO $fname(".implode(',',$rows[0]).") VALUES(";
		foreach($rw as $r){
		if($r=='NULL') $e1.='NULL,';
		else $e1.=(is_numeric($r)?$r:"'".str_replace("'","''",$r)."'").',';
		}
		$e[]=substr($e1,0,-1).");";
		}
		}
		if(empty($e)) $this->redir("5/".$this->sg[1],['err'=>"Query failed"]);
		return $e;
	}
	public function imp_json($fname,$body){
		$exist=$this->con->query("SELECT 1 FROM $fname");
		if(!$exist) $this->redir("5/".$this->sg[1],['err'=>"Table not exist"]);
		$e=[];
		if(@is_file($body)) $body=file_get_contents($body);
		$body=$this->utf($body);
		$rgxj="~^\xEF\xBB\xBF|^\xFE\xFF|^\xFF\xFE|(\/\/).*\n*|(\/\*)*.*(\*\/)\n*|((\"*.*\")*('*.*')*)(*SKIP)(*F)~";
		$ex=preg_split($rgxj,$body,-1,PREG_SPLIT_NO_EMPTY);
		$lines=json_decode($ex[0],true);
		$jr='';
		foreach($lines[0] as $k=>$li) $jr.=$k.",";
		foreach($lines as $line){
		$jv='';
		foreach($line as $ky=>$el){
		if($el=='NULL') $jv.='NULL,';
		else $jv.=(is_numeric($el)?$el:"'".$el."'").",";
		}
		$e[]="INSERT INTO $fname(".substr($jr,0,-1).") VALUES (".substr($jv,0,-1).")";
		}
		return $e;
	}
	public function imp_xml($body){
		$e=[];
		if(@is_file($body)) $body=file_get_contents($body);
		$body=$this->utf($body);
		libxml_use_internal_errors(false);
		$xml=simplexml_load_string($body,"SimpleXMLElement",LIBXML_COMPACT);
		$nspace=$xml->getNameSpaces(true);
		$ns=key($nspace);
		//structure
		$sq[]=[];
		if(isset($nspace[$ns]) && isset($xml->children($nspace[$ns])->{'structure_schemas'}->{'database'}->{'table'})){
			$strs=$xml->children($nspace[$ns])->{'structure_schemas'}->{'database'}->{'table'};
			foreach($strs as $st){
			$sq[]=explode(";",str_replace("\t\t\t","",(string)$st));
			}
		}
		$sq=(empty($sq) ? $sq:call_user_func_array('array_merge',$sq));
		//data
		$data=$xml->xpath('//database/table');
		foreach($data as $dt){
			$tt=$dt->attributes();
			$co='';$va='';
			foreach($dt as $dt2){
			$tv=$dt2->attributes();
			$co.=(string)$tv['name'].",";
			$va.=($dt2=='NULL')?"NULL,":"'".$dt2."',";
			}
			if($co!='' && $va!='') $e[]="INSERT INTO ".(string)$tt['name']."(".substr($co,0,-1).") VALUES(".substr($va,0,-1).");";
		}
		return array_merge($sq,$e);
	}
	public function tb_structure($tb,$fopt,$tab){
		$sql="";
		if(in_array('drop',$fopt)){//option drop
			$sql.="\n{$tab}DROP TABLE IF EXISTS $tb;\n";
		}
		$ifnot='';
		if(in_array('ifnot',$fopt)){//option if not exist
			$ifnot.="IF NOT EXISTS ";
		}
		$q_ex=$this->con->query("SELECT a.attname AS column_name,pg_catalog.format_type(a.atttypid,a.atttypmod) AS data_type,a.attnotnull AS is_nullable,pg_get_expr(ad.adbin,ad.adrelid) AS column_default FROM pg_catalog.pg_attribute a JOIN pg_catalog.pg_class c ON a.attrelid=c.oid JOIN pg_catalog.pg_namespace n ON c.relnamespace=n.oid LEFT JOIN pg_catalog.pg_attrdef ad ON ad.adrelid=c.oid AND ad.adnum=a.attnum WHERE n.nspname='public' AND c.relname='$tb' AND a.attnum>0 AND NOT a.attisdropped");
		if($q_ex){
		$sq="\n{$tab}CREATE TABLE ".$ifnot."".$tb." (";
		foreach($q_ex->fetch(2) as $r_ex){
			$dty=$r_ex['data_type'];$def=$r_ex['column_default'];
			if(!empty($def) && strpos($def,'nextval')!==false){
			$def='';
			if($dty=='integer') $dty='serial';
			elseif($dty=='bigint') $dty='bigserial';
			elseif($dty=='smallint') $dty='smallserial';
			}
			$nul=($r_ex['is_nullable']==1 ? "NOT NULL":"NULL");
			if($def!='') $def=" default ".$def;
			$sq.="\n{$tab}".$r_ex['column_name']." $dty ".$nul.$def.",";
		}
		$sql.=substr($sq,0,-1)."\n{$tab});\n\n";

		$indexes=[];
		$q_constraints=$this->con->query("SELECT conname,contype,conkey,pg_get_constraintdef(oid) AS def FROM pg_constraint WHERE conrelid='$tb'::regclass AND contype IN('p','u','c','f')");
		if($q_constraints && $q_constraints->num_row()>0){
			foreach($q_constraints->fetch(2) as $r_con){
				$key_name=$r_con['conname'];
				$def=$r_con['def'];
				switch($r_con['contype']){
				case 'p':$sql.=$tab."ALTER TABLE $tb ADD $def;\n";break;
				case 'u':case 'c':case 'f':$sql.=$tab."ALTER TABLE $tb ADD CONSTRAINT $key_name $def;\n";break;
				}
				$indexes[$key_name]=[];
			}
		}
		$q_idx=$this->con->query("SELECT indexname,indexdef FROM pg_indexes WHERE schemaname='public' AND tablename='$tb'");
		if($q_idx && $q_idx->num_row()>0){
			foreach($q_idx->fetch(1) as $r_idx){
				$index=$r_idx[0];
				if(!isset($indexes[$index])){
					$sql.=$tab.$r_idx[1].";\n";
				}
			}
		}
		$q_comm=$this->con->query("SELECT obj_description('{$tb}'::regclass,'pg_class')")->fetch();
		if($q_comm && $q_comm[0]) $sql.=$tab."COMMENT ON TABLE $tb IS '".addslashes($q_comm[0])."';\n";
		}
		return $sql;
	}
	public function getTables($db){
		$tbs=[];$vws=[];
		$q_tb=$this->con->query("SELECT * FROM information_schema.tables WHERE table_schema='public' AND table_catalog='$db'")->fetch(2);
		foreach($q_tb as $tb){
			$tbn=$tb['table_name'];
			if(in_array($tbn,$this->post('tbs'))){
			if($tb['table_type']=='VIEW'){
				array_push($vws,$tbn);
			}else{
				array_push($tbs,$tbn);
			}
			}
		}
		return [$tbs,$vws];
	}
}
$ed=new ED;
$head='<!DOCTYPE html><html lang="en"><head>
<meta charset="utf-8"><title>EdPgAdmin</title>
<style>
*{margin:0;padding:0;font-size:14px;color:#333;font-family:Arial}
html{-ms-text-size-adjust:100%;-webkit-text-size-adjust:100%;background:#fff}
html,textarea{overflow:auto}
.container{overflow:auto;overflow-y:hidden;-ms-overflow-y:hidden;white-space:nowrap;scrollbar-width:thin}
[hidden],.mn ul{display:none}
.m1{position:absolute;right:0;top:0}
.mn li:hover ul{display:block;position:absolute}
.ce{text-align:center}
.link{float:right;padding:3px 0}
.pg *{margin:0 2px;width:auto}
caption{font-weight:bold;border:2px solid #9be}
.l1 ul,.l2 ul{list-style:none}
.left{float:left}
.left button{margin:0 1px}
h3{margin:2px 0 1px;padding:2px 0}
a{color:#842;text-decoration:none}
a:hover{text-decoration:underline}
a,a:active,a:hover{outline:0}
table a,.l1 a,.l2 a,.col1 a{padding:0 2px}
table{border-collapse:collapse;border-spacing:0;border-bottom:1px solid #555}
td,th{padding:4px;vertical-align:top}
input[type=checkbox],input[type=radio]{position:relative;vertical-align:middle;bottom:1px}
input[type=text],input[type=password],input[type=file],textarea,button,select{width:100%;padding:2px;border:1px solid #9be;outline:none;border-radius:3px;box-sizing:border-box}
optgroup option{padding-left:8px}
textarea,select[multiple]{min-height:90px}
textarea{white-space:pre-wrap;min-width:180px}
.msg{position:fixed;top:0;right:0;z-index:9}
.ok,.err{padding:8px;font-weight:bold}
.ok{background:#efe;color:#080;border-bottom:2px solid #080}
.err{background:#fee;color:#f00;border-bottom:2px solid #f00}
.l1,th,button{background:#9be}
.l2,.c1,.col1,h3{background:#cdf}
.c2,.mn ul{background:#fff}
.l3,tr:hover.r,button:hover{background:#fe3 !important}
.ok,.err,.l2 li,.mn>li{display:inline-block;zoom:1}
.col1,.col2{display:table-cell}
.col1{vertical-align:top;padding:3px}
.col1,.dw{width:180px}
.col2 table{margin:3px}
.col3 table,.dw{margin:3px auto}
.auto button,.auto input,.auto select{width:auto}
.l3.auto select{border:0;padding:0;background:#fe3}
.l1,.l2,.l3,.wi{width:100%}
.msg,.a{cursor:pointer}
</style>
</head><body>'.(empty($_SESSION['ok'])?'':'<div class="msg ok">'.$_SESSION['ok'].'</div>').(empty($_SESSION['err'])?'':'<div class="msg err">'.$_SESSION['err'].'</div>').'<div class="l1"><b><a href="https://github.com/edmondsql/edpgadmin">EdPgAdmin '.$version.'</a></b>'.(isset($ed->sg[0]) && $ed->sg[0]==50 ? "":'<ul class="mn m1"><li>More <small>&#9660;</small><ul><li><a href="'.$ed->path.'60">Info</a></li><li><a href="'.$ed->path.'60/var">Variables</a></li><li><a href="'.$ed->path.'60/status">Status</a></li><li><a href="'.$ed->path.'60/process">Processes</a></li></ul></li><li><a href="'.$ed->path.'52">Users</a></li><li><a href="'.$ed->path.'51">Logout ['.(isset($_SESSION['user']) ? $_SESSION['user']:"").']</a></li></ul>').'</div>';
$stru="<table><caption>Structure</caption><tr><th>Field</th><th colspan='2'>Type</th><th>Value</th><th>Null</th><th>Default</th></tr>";

if(!isset($ed->sg[0])) $ed->sg[0]=0;
switch($ed->sg[0]){
default:
case ""://show DBs
	$ed->check();
	echo $head.$ed->menu()."<div class='col1'>Create Database".$ed->form("2")."<input type='text' name='dbc'/><br/><button type='submit'>Create</button></form></div><div class='col2'><table><tr><th>Databases</th><th>Actions</th></tr>";
	foreach($ed->u_db as $udb){
	$bg=($bg==1)?2:1;
	echo "<tr class='r c$bg'><td>$udb</td><td>
	<a href='{$ed->path}31/$udb'>Exp</a><a class='del' href='{$ed->path}4/$udb'>Drop</a>
	<a href='{$ed->path}5/$udb'>Browse</a></td></tr>";
	}
	echo "</table>";
break;

case "2"://created DB
	$ed->check();
	if($ed->post('dbc','!e')){
	$db=$ed->sanitize($ed->post('dbc'));
	$q_cc=$ed->con->query("CREATE DATABASE $db");
	if($q_cc) $ed->redir("",['ok'=>"Created DB"]);
	$ed->redir("",['err'=>"Create DB failed"]);
	}
	$ed->redir("",['err'=>"DB name must not be empty"]);
break;

case "3"://rename DB
	$ed->check([],['db'=>'']);
	$db=$ed->sg[1];
	if($ed->post('rdb','!e')){
	$ndb=$ed->sanitize($ed->post('rdb'));
	$dbs=$ed->con->query("SELECT datname FROM pg_database")->fetch(1);
	$dbs=call_user_func_array('array_merge',$dbs);
	if(!in_array($ndb,$dbs) && in_array($db,$dbs)){
	$ed->con->query("SELECT pg_terminate_backend(pid) FROM pg_stat_activity WHERE datname='$db'");
	$ed->con->query("ALTER DATABASE $db RENAME TO $ndb");
	$ed->redir("",['ok'=>"Renamed database"]);
	}else $ed->redir("5/$db",['err'=>"Not valid DB name"]);
	}else $ed->redir("5/$db",['err'=>"Name must not be empty"]);
break;

case "4"://Drop DB
	$ed->check([],['db'=>'']);
	$db=$ed->sg[1];
	if(!in_array($db,$deny) && in_array($db,$ed->u_db)){
	$q_drodb=$ed->con->query("DROP DATABASE $db");
	if($q_drodb) $ed->redir("",['ok'=>"Succeful deleted DB"]);
	}
	$ed->redir('',['err'=>"Delete DB failed"]);
break;

case "5"://Show Tables
	$ed->check([1]);
	$db=$ed->sg[1];
	$q_tbs=$ed->con->query("SELECT * FROM information_schema.tables WHERE table_schema='public' AND table_catalog='$db'");
	$ttalr=$q_tbs->num_row();
	$tables=[];
	if($ttalr >0){
	foreach($q_tbs->fetch(2) as $r_tbs) $tables[]=[0=>$r_tbs['table_name'],1=>$r_tbs['table_schema'],2=>$r_tbs['table_type']];
	}
	//paginate
	if($ttalr > 0){
	$ttalpg=ceil($ttalr/$step);
	if(empty($ed->sg[2])){
		$pg=1;
	}else{
		$pg=$ed->sg[2];
		$ed->check([4],['pg'=>$pg,'total'=>$ttalpg,'redir'=>"5/$db"]);
	}
	}
	echo $head.$ed->menu($db,'',1);
	if($ttalr > 0){//start rows
	echo "<table><tr><th>Table</th><th>Rows</th><th>Schema</th><th>Type</th><th>Actions</th></tr>";
	$ofset=($pg - 1) * $step;
	$max=$step + $ofset;
	while($ofset < $max){
		if(!empty($tables[$ofset][0])){
		$bg=($bg==1)?2:1;
		$tbs=$tables[$ofset][0];
		$_vl="/$db/".$tbs;
		if($tables[$ofset][2]=='VIEW'){
			$lnk="40{$_vl}/view";$dro="49{$_vl}/view";
		}else{
			$lnk="10".$_vl;$dro="26".$_vl;
		}
		$q_rows[0]=0;
		$q_t=$ed->con->query("SELECT COUNT(*) FROM $tbs");
		if($q_t) $q_rows=$q_t->fetch();
		echo "<tr class='r c$bg'><td>$tbs</td><td>".$q_rows[0]."</td><td>".$tables[$ofset][1]."</td><td>".$tables[$ofset][2]."</td><td><a href='".$ed->path.$lnk."'>Structure</a><a class='del' href='".$ed->path.$dro."'>Drop</a><a href='".$ed->path."20/$db/$tbs'>Browse</a></td></tr>";
		}
		++$ofset;
	}
	echo "</table>".$ed->pg_number($pg,$ttalpg);
	}//end rows
	//triggers
	$q_trg=$ed->con->query("SELECT tgname,relname FROM pg_trigger t JOIN pg_class c ON t.tgrelid=c.oid WHERE NOT tgisinternal ORDER BY tgname");
	if($q_trg && $q_trg->num_row()>0){
		echo "<table><tr><th>Trigger</th><th>Table</th><th>Actions</th></tr>";
		foreach($q_trg->fetch(1) as $r_tg){
			$bg=($bg==1)?2:1;
			echo "<tr class='r c$bg'><td>".$r_tg[0]."</td><td>".$r_tg[1]."</td><td><a href='{$ed->path}41/$db/".$r_tg[0]."/trigger'>Edit</a><a class='del' href='{$ed->path}49/$db/".$r_tg[0]."/trigger'>Drop</a></td></tr>";
		}
		echo "</table>";
	}
	//spp
	$spps=['PROCEDURE','FUNCTION'];
	$q_sp=[];
	foreach($spps as $spp){
		$q_spp=$ed->con->query("SELECT routine_name,routine_type FROM information_schema.routines WHERE routine_schema='public' AND routine_type='$spp' ORDER BY routine_name");
		if($q_spp){
			foreach($q_spp->fetch(1) as $r_spp){
				$q_sp[]=[$db,$r_spp[0],$r_spp[1]];
			}
		}
	}
	if(!empty($q_sp)){
		echo "<table><tr><th>Routine</th><th>Type</th><th>Actions</th></tr>";
		foreach($q_sp as $r_sp){
			$bg=($bg==1)?2:1;
			echo "<tr class='r c$bg'><td>".$r_sp[1]."</td><td>".$r_sp[2]."</td><td><a href='{$ed->path}42/".$r_sp[0]."/".$r_sp[1]."/".strtolower($r_sp[2])."'>Edit</a><a href='{$ed->path}48/".$r_sp[0]."/".$r_sp[1]."/".strtolower($r_sp[2])."'>Execute</a><a class='del' href='{$ed->path}49/".$r_sp[0]."/".$r_sp[1]."/".strtolower($r_sp[2])."'>Drop</a></td></tr>";
		}
		echo "</table>";
	}
break;

case "6"://create table
	$ed->check([1]);
	$db=$ed->sg[1];
	if($ed->post('ctab','!e') && !is_numeric(substr($ed->post('ctab'),0,1)) && $ed->post('nrf','!e') && $ed->post('nrf')>0 ){
	echo $head.$ed->menu($db,'',2);
	if($ed->post('crtb','i')){
		$tb=$ed->sanitize($ed->post('ctab'));
		$qry1="CREATE TABLE $tb (";
		$nf=0;
		while($nf<$ed->post('nrf')){
			$c1=$ed->post('fi'.$nf);
			$c2=$ed->post('ty'.$nf);
			$c3=($ed->post('va'.$nf,'!e') ? "(".$ed->post('va'.$nf).")":"").$ed->post('ar'.$nf);
			$c4=$ed->post('nc'.$nf);
			$c5=($ed->post('de'.$nf,'!e') ? " default '".$ed->post('de'.$nf)."'":"");
			$qry1.=$c1." ".$c2.$c3." ".$c4.$c5.",";
			++$nf;
		}
		$qry2=substr($qry1,0,-1);
		$qry=$qry2.");";
		echo "<p>".($ed->con->query($qry) ? "<b>OK!</b> $qry":"<b>FAILED!</b> $qry")."</p>";
		if($ed->post('tc')!=""){
		$ed->con->query("COMMENT ON TABLE $tb IS '".$ed->post('tc')."'");
		echo "<p>Comment created!</p>";
		}
	}else{
		echo $ed->form("6/$db")."
		<input type='hidden' name='ctab' value='".$ed->sanitize($ed->post('ctab'))."'/>
		<input type='hidden' name='nrf' value='".$ed->post('nrf')."'/>".$stru;
		$nf=0;
		while($nf<$ed->post('nrf')){
			$bg=($bg==1)?2:1;
			echo "<tr class='c$bg'><td><input type='text' name='fi".$nf."'/></td>
			<td><select name='ty".$nf."'>".$ed->fieldtypes()."</select></td>
			<td><select name='ar".$nf."'><option value=''></option><option value='[]'>[ ]</option></select></td>
			<td><input type='text' name='va".$nf."'/></td>
			<td><select name='nc".$nf."'><option value='NOT NULL'>NOT NULL</option><option value='NULL'>NULL</option></select></td>
			<td><input type='text' name='de".$nf."'/></td></tr>";
			++$nf;
		}
		echo "<tr><td colspan='7'>Table Comment:<br/><input type='text' name='tc'/></td></tr>
		<tr><td colspan='8'><button type='submit' name='crtb'>Create Table</button></td></tr></table></form>";
	}
	}else{
		$ed->redir("5/$db",['err'=>"Create table failed"]);
	}
break;

case "9":
	$ed->check([1,2]);
	$db=$ed->sg[1];
	$tb=$ed->sg[2];
	if($ed->post('changeb','i') && $ed->post('changec','i')){//table comment
		$ed->con->query("COMMENT ON TABLE $tb IS '".addslashes($ed->post('changec'))."'");
		$ed->redir("10/$db/$tb",['ok'=>"Changed table comment"]);
	}
	if($ed->post('rtab','!e')){//rename table
		$ntb=$ed->sanitize($ed->post('rtab'));
		if(is_numeric(substr($ntb,0,1))) $ed->redir("5/$db",['err'=>"Not a valid table name"]);
		$q_creatt=$ed->con->query("SELECT 1 FROM pg_tables WHERE schemaname='public' AND tablename='$ntb'");
		if($q_creatt && $q_creatt->num_row()>0) $ed->redir("5/$db",['err'=>"Table already exist"]);
		$q_ren=$ed->con->query("ALTER TABLE $tb RENAME TO $ntb");
		if($q_ren) $ed->redir("5/$db",['ok'=>"Successfully renamed"]);
		else $ed->redir("5/$db",['err'=>"Rename table failed"]);
	}
	if($ed->post('idx','!e') && is_array($ed->post('idx'))){//create index
		$idx=''.implode(',',$ed->post('idx')).'';
		$idxn=implode('_',$ed->post('idx'));
		if($ed->post('primary','i')){
		$ed->con->query("ALTER TABLE $tb ADD PRIMARY KEY ($idx)");
		}elseif($ed->post('unique','i')){
		$ed->con->query("ALTER TABLE $tb ADD CONSTRAINT unq_$idxn UNIQUE ($idx)");
		}elseif($ed->post('index','i')){
		$ed->con->query("CREATE INDEX idx_$idxn ON $tb ($idx)");
		}
		$ed->redir("10/$db/$tb",['ok'=>"Successfully created"]);
	}
	if(isset($ed->sg[3])){//drop index
		$idx=$ed->sg[3];
		$q_alt=$ed->con->query("ALTER TABLE $tb DROP CONSTRAINT $idx");
		$q_alt=$ed->con->query("DROP INDEX $idx");
		if($q_alt) $ed->redir("10/$db/$tb",['ok'=>"Successfully dropped"]);
		else $ed->redir("10/$db/$tb",['err'=>"Drop failed"]);
	}
	$ed->redir("5/$db",['err'=>"Action failed"]);
break;

case "10"://structure
	$ed->check([1,2]);
	$db=$ed->sg[1];
	$tb=$ed->sg[2];
	echo $head.$ed->menu($db,$tb,1);
	echo $ed->form("9/$db/$tb")."<table><caption>Structure</caption><thead><tr><th><input type='checkbox' onclick='toggle(this,\"idx[]\")'/></th><th>Field</th><th>Type</th><th>Null</th><th>Default</th><th>Actions</th></tr></thead><tbody class='sort'>";
$q_fi=$ed->con->query("SELECT a.attname AS column_name,pg_catalog.format_type(a.atttypid,a.atttypmod) AS data_type,a.attnotnull AS is_nullable,pg_get_expr(ad.adbin,ad.adrelid) AS column_default FROM pg_catalog.pg_attribute a JOIN pg_catalog.pg_class c ON a.attrelid=c.oid JOIN pg_catalog.pg_namespace n ON c.relnamespace=n.oid LEFT JOIN pg_catalog.pg_attrdef ad ON ad.adrelid=c.oid AND ad.adnum=a.attnum WHERE n.nspname='public' AND c.relname='$tb' AND a.attnum>0 AND NOT a.attisdropped");
	foreach($q_fi->fetch(2) as $r_fi){
		$bg=($bg==1)?2:1;
		$dty=$r_fi['data_type'];$def=$r_fi['column_default'];
		if(!empty($def) && strpos($def,'nextval')!==false){
		if($dty=='integer') $dty='serial';
		elseif($dty=='bigint') $dty='bigserial';
		elseif($dty=='smallint') $dty='smallserial';
		}
		echo "<tr class='r c$bg' id='{$r_fi['column_name']}'><td><input type='checkbox' name='idx[]' value='{$r_fi['column_name']}'/></td><td>{$r_fi['column_name']}</td><td>$dty</td><td>".($r_fi['is_nullable']==1?"NO":"YES")."</td><td>$def</td><td><a href='{$ed->path}12/$db/$tb/{$r_fi['column_name']}'>change</a><a class='del' href='{$ed->path}13/$db/$tb/{$r_fi['column_name']}'>drop</a><a href='{$ed->path}11/$db/$tb/{$r_fi['column_name']}'>add</a></td></tr>";
	}
	$q_comm=$ed->con->query("SELECT obj_description('{$tb}'::regclass,'pg_class')")->fetch();
	$tb_comment=$q_comm ? $q_comm[0]:'';
	echo "</tbody><tfoot><tr><td colspan='3'><button type='submit' name='changeb'>Change Comment</button></td><td colspan='5'><input type='text' name='changec' value=\"".$tb_comment."\"/></td></tr>
	<tr><td class='auto' colspan='8'><div class='left'><button type='submit' name='primary'>Primary</button><button type='submit' name='index'>Index</button><button type='submit' name='unique'>Unique</button></div><div class='link'><a href='{$ed->path}27/$db/$tb/vacuum'>Vacuum</a><a href='{$ed->path}27/$db/$tb/analyze'>Analyze</a><a href='{$ed->path}27/$db/$tb/reindex'>Reindex</a></div></td></tr></tfoot></table></form>
	<table><caption>Index</caption><tr><th>Key name</th><th>Field</th><th>Type</th><th>Actions</th></tr>";
	$indexes=[];
	$q_constraints=$ed->con->query("SELECT conname,contype,conkey FROM pg_constraint WHERE conrelid='$tb'::regclass AND contype IN('p','u','f','c','t')");
	if($q_constraints && $q_constraints->num_row()>0){
		foreach($q_constraints->fetch(2) as $r_con){
			$col_numbers=explode(',',trim($r_con['conkey'],'{}'));
			$col_names=[];
			foreach($col_numbers as $col_num){
				$col_name_query=$ed->con->query("SELECT attname FROM pg_attribute WHERE attrelid='$tb'::regclass AND attnum={$col_num}");
				if($col_name_query && $col_name_query->num_row()>0) $col_names[]=$col_name_query->fetch()[0];
			}
			$key_name=$r_con['conname'];
			switch($r_con['contype']){
			case 'p':$type='PRIMARY';break;
			case 'u':$type='UNIQUE';break;
			case 'f':$type='FK';break;
			case 'c':$type='CHECK';break;
			}
			$indexes[$key_name]=['type'=>$type,'column'=>$col_names];
		}
	}
	$q_idx=$ed->con->query("SELECT indexname,indexdef FROM pg_indexes WHERE schemaname='public' AND tablename='$tb'");
	if($q_idx && $q_idx->num_row()>0){
		foreach($q_idx->fetch(2) as $r_idx){
			$index=$r_idx['indexname'];
			if(!isset($indexes[$index])){
				preg_match('/USING\s*\w+\s*\(([^)]+)\)/',$r_idx['indexdef'],$match);
				$cols=isset($match[1]) ? array_map('trim',explode(',',str_replace('"','',$match[1]))):[];
				$indexes[$index]=['type'=>'INDEX','column'=>$cols];
			}
		}
	}
	if(count($indexes)>0){
		foreach($indexes as $iNam=>$iCol){
			$bg=($bg==1)?2:1;
			echo "<tr class='r c$bg'><td>$iNam</td><td>";
			foreach($iCol['column'] as $col) echo $col."<br/>";
			echo "</td><td>".$iCol['type'];
			echo "</td><td><a class='del' href='{$ed->path}9/$db/$tb/$iNam'>drop</a></td></tr>";
		}
	}
	echo "</table><table class='c1'><tr><td>Rename Table<br/>".$ed->form("9/$db/$tb")."<input type='text' name='rtab'/><br/><button type='submit'>Rename</button></form></td></tr></table>";
break;

case "11"://Add field
	$ed->check([1,2,3],['redir'=>10]);
	$db=$ed->sg[1];
	$tb=$ed->sg[2];
	$id=$ed->sg[3];
	if($ed->post('fi','!e') && $ed->post('ty','!e') && !is_numeric(substr($ed->post('fi'),0,1))){
		$ty=$ed->post('ty').($ed->post('va','e') ? "":"(".$ed->post('va').")").($ed->post('ar','e') ? "":"[]");
		$nc=$ed->post('nc');
		$de=($ed->post('de','e') ? "":" DEFAULT ".$ed->post('de'));
		$e=$ed->con->query("ALTER TABLE $tb ADD COLUMN ".$ed->sanitize($ed->post('fi'))." $ty ".$nc.$de);
		if($e) $ed->redir("10/$db/$tb",['ok'=>"Successfully added"]);
		else $ed->redir("10/$db/$tb",['err'=>"Add field failed"]);
	}else{
		echo $head.$ed->menu($db,$tb,2).$ed->form("11/$db/$tb/$id").$stru.
		"<tr><td><input type='text' name='fi'/></td><td><select name='ty'>".$ed->fieldtypes()."</select></td>
		<td><select name='ar'><option value=''></option><option value='[]'>[ ]</option></select></td>
		<td><input type='text' name='va'/></td>
		<td><select name='nc'><option value='NOT NULL'>NOT NULL</option><option value='NULL'>NULL</option></select></td>
		<td><input type='text' name='de'/></td>
		</tr><tr><td colspan='6'><button type='submit'>Add</button></td></tr></table></form>";
	}
break;

case "12"://structure change
	$ed->check([1,2,3],['redir'=>10]);
	$db=$ed->sg[1];
	$tb=$ed->sg[2];
	if($ed->post('fi','!e') && $ed->post('ty','!e') && !is_numeric(substr($ed->post('fi'),0,1))){
		$fi=$ed->sanitize($ed->post('fi'));
		$fi_=$ed->post('fi_');
		$ty=$ed->post('ty').($ed->post('va','e') ? "":"(".$ed->post('va').")").($ed->post('ar','e') ? "":"[]");
		$nc=$ed->post('nc');
		$de=($ed->post('de','e') ? "":" DEFAULT ".$ed->post('de'));
		$ed->con->query("ALTER TABLE $tb RENAME COLUMN {$fi_} TO ".$ed->sanitize($ed->post('fi')));
		$ed->con->query("ALTER TABLE $tb ALTER COLUMN $fi TYPE $ty");
		$ed->con->query("ALTER TABLE $tb ALTER COLUMN $fi ".($nc=='NULL' ? "DROP NOT NULL":"SET NOT NULL"));
		$ed->con->query("ALTER TABLE $tb ALTER COLUMN $fi ".(empty($de) ? "DROP DEFAULT":"SET $de"));
		$ed->redir("10/$db/$tb",['ok'=>"Successfully changed"]);
	}else{//structure form
	echo $head.$ed->menu($db,$tb,2);
	echo $ed->form("12/$db/$tb/".$ed->sg[3]).$stru;
	$r_fe=$ed->con->query("SELECT a.attname AS column_name,pg_catalog.format_type(a.atttypid,a.atttypmod) AS data_type,a.attnotnull AS is_nullable,pg_get_expr(ad.adbin,ad.adrelid) AS column_default FROM pg_catalog.pg_attribute a JOIN pg_catalog.pg_class c ON a.attrelid=c.oid JOIN pg_catalog.pg_namespace n ON c.relnamespace=n.oid LEFT JOIN pg_catalog.pg_attrdef ad ON ad.adrelid=c.oid AND ad.adnum=a.attnum WHERE n.nspname='public' AND c.relname='$tb' AND a.attnum>0 AND NOT a.attisdropped AND a.attname='{$ed->sg[3]}'")->fetch();
	$fe_type=preg_split("/[()]+/",$r_fe[1],-1,PREG_SPLIT_NO_EMPTY);
	$dty=$fe_type[0];
	if(strpos($r_fe[3],'nextval')!==false){
	if($dty=='integer') $dty='serial';
	elseif($dty=='bigint') $dty='bigserial';
	elseif($dty=='smallint') $dty='smallserial';
	}
	echo "<tr><td><input type='hidden' name='fi_' value='{$r_fe[0]}'/><input type='text' name='fi' value='{$r_fe[0]}' /></td>
	<td><select name='ty'>".$ed->fieldtypes($dty)."</select></td>
	<td><select name='ar'><option value=''></option><option value='[]'".(empty($fe_type[2])?"":" selected").">[ ]</option></select></td>
	<td><input type='text' name='va' value='".(isset($fe_type[1])?$fe_type[1]:"")."' /></td><td><select name='nc'>";
	$cc=['NOT NULL','NULL'];
	foreach($cc as $c) echo("<option value='$c'".(($r_fe[2]!=1 && $c=="NULL")?" selected":"").">$c</option>");
	echo "</select></td><td><input type='text' name='de' value=\"{$r_fe[3]}\"/></td>
	</tr><tr><td colspan='8'><button type='submit'>Change field</button></td></tr></table></form>";
	}
break;

case "13"://drop field
	$ed->check([1,2,3],['redir'=>10]);
	$db=$ed->sg[1];
	$tb=$ed->sg[2];
	$fi=$ed->sg[3];
	$q_drop=$ed->con->query("ALTER TABLE $tb DROP COLUMN ".$fi);
	if($q_drop) $ed->redir("10/$db/$tb",['ok'=>"Successfully deleted"]);
	$ed->redir("10/$db/$tb",['err'=>"Field delete failed"]);
break;

case "20"://table browse
	$ed->check([1,2]);
	$db=$ed->sg[1];
	$tb=$ed->sg[2];
	$where=(empty($_SESSION['_sqlsearch_'.$db.'_'.$tb])?"":" ".$_SESSION['_sqlsearch_'.$db.'_'.$tb]);
	$q_cnt=$ed->con->query("SELECT COUNT(*) FROM $tb".$where)->fetch();
	$totalr=$q_cnt[0];
	$totalpg=ceil($totalr/$step);
	if(empty($ed->sg[3])){
	$pg=1;
	}else{
	$pg=$ed->sg[3];
	$ed->check([1,4],['pg'=>$pg,'total'=>$totalpg,'redir'=>"20/$db/$tb"]);
	}
	$offset=($pg - 1) * $step;

	$q_vic=$ed->con->query("SELECT relkind FROM pg_class WHERE relname='$tb'")->fetch();
	echo $head.$ed->menu($db,($q_vic[0]=='v'?'':$tb),1,($q_vic[0]=='v'?['view',$tb]:''));
	echo "<table><tr>";
	if($q_vic[0]!='v'){echo "<th>Actions</th>";}
	$q_bro=$ed->con->query("SELECT column_name,data_type FROM information_schema.columns WHERE table_schema='public' AND table_name='$tb' ORDER BY ordinal_position");
	$r_cl=$q_bro->num_row();
	$coln=[];//field
	$colt=[];//type
	foreach($q_bro->fetch(2) as $r_bro){
		$col_name=$r_bro['column_name'];
		$col_type=$r_bro['data_type'];
		$coln[]=$col_name;
		$colt[]=$col_type;
		echo "<th>".$col_name."</th>";
	}
	echo "</tr>";
	$q_res=$ed->con->query("SELECT ".implode(",",$coln)." FROM {$tb}$where LIMIT $step OFFSET $offset");
	foreach($q_res->fetch(1) as $r_rw){
		$bg=($bg==1)?2:1;
		$nu=$coln[0]."/".($r_rw[0]==""?"isnull":base64_encode($r_rw[0])).(isset($colt[1]) && ($colt[1]=="int" || $colt[1]=="varchar") && $colt[1]=="bytea" && !empty($coln[1]) && !empty($r_rw[1]) ? "/".$coln[1]."/".base64_encode($r_rw[1]):"");
		echo "<tr class='r c$bg'>";
		if($q_vic[0]!='v'){
		echo "<td><a href='".$ed->path."22/$db/$tb/$nu'>Edit</a><a class='del' href='".$ed->path."23/$db/$tb/$nu'>Delete</a></td>";
		}
		$i=0;
		while($i<$r_cl){
			echo "<td>";
			if($colt[$i]=="bytea"){
				$le=empty($r_rw[$i])?0:strlen(stream_get_contents($r_rw[$i]));
				echo "[bytea] ";
				if($le > 4){
				echo "<a href='".$ed->path."33/$db/$tb/$nu/".$coln[$i]."'>".number_format(($le/1024),2)." KB</a>";
				}else{
				echo number_format(($le/1024),2)." KB";
				}
			}elseif(strlen($r_rw[$i]) > 70){
				echo substr($r_rw[$i],0,70)."[...]";
			}else{
				echo empty($r_rw[$i])?'':htmlentities($r_rw[$i]);
			}
			echo "</td>";
			++$i;
		}
		echo "</tr>";
	}
	echo "</table>";
	echo $ed->pg_number($pg,$totalpg);
break;

case "21"://table insert
	$ed->check([1,2]);
	$db=$ed->sg[1];
	$tb=$ed->sg[2];
	$q_col=$ed->con->query("SELECT column_name,data_type,is_nullable FROM information_schema.columns WHERE table_schema='public' AND table_name='{$tb}' ORDER BY ordinal_position");
	$coln=[];//field
	$colt=[];//type
	$colu=[];//null
	foreach($q_col->fetch(2) as $r_brw){
		$coln[]=$r_brw['column_name'];
		$colt[]=$r_brw['data_type'];
		$colu[]=$r_brw['is_nullable'];
	}
	if($ed->post('save','i') || $ed->post('save2','i')){
		$qr1="INSERT INTO $tb (";
		$qr2="";
		$qr3="VALUES(";
		$qr4="";
		$n=0;
		while($n<count($coln)){
			if($ed->post('r'.$n,'!e') || !empty($_FILES["r".$n]['tmp_name'])){
			$qr2.=$coln[$n].",";
			if($colt[$n]=="bytea"){
				if(!empty($_FILES["r".$n]['tmp_name'])){
				$qr4.="'\x".bin2hex(file_get_contents($_FILES["r".$n]['tmp_name']))."',";
				}else{
				$qr4.="'',";
				}
			}elseif($colt[$n]=='boolean'){
				$qr4.=$ed->post('r'.$n,0).",";
			}else{
				if(!empty($_FILES['r'.$n]['tmp_name'])){
				$blb="'\x".bin2hex(file_get_contents($_FILES['r'.$n]['tmp_name']))."',";
				$qr4.="'{$blb}',";
				}else{
				$qr4.=(($ed->post('r'.$n,'e') && $colu[$n]==1)? "NULL":"'".addslashes($ed->post('r'.$n))."'").",";
				}
			}
			}
			++$n;
		}
		$qr2=substr($qr2,0,-1).") ";
		$qr4=substr($qr4,0,-1).")";
		$q_rins=$ed->con->query($qr1.$qr2.$qr3.$qr4);
		if($ed->post('save2','i')) $rr=21;
		else $rr=20;
		if($q_rins) $ed->redir("$rr/$db/$tb",['ok'=>"Successfully inserted"]);
		else $ed->redir("$rr/$db/$tb",['err'=>"Insert failed"]);
	}else{
		echo $head.$ed->menu($db,$tb,1).$ed->form("21/$db/$tb",1)."<table><caption>Insert Row</caption>";
		$j=0;
		while($j<count($coln)){
			echo "<tr><td>".$coln[$j]."</td><td>";
			if($colt[$j]=='boolean'){//boolean
			foreach($bbs as $bb) echo "<input type='radio' name='r{$j}[]' value='$bb'/> $bb ";
			}elseif($colt[$j]=="bytea" && !in_array($db,$deny)){
			echo "<input type='file' name='r{$j}'/>";
			}elseif($colt[$j]=="text"){//text
			echo "<textarea name='r{$j}'></textarea>";
			}else{
			echo "<input type='text' name='r{$j}'/>";
			}
			++$j;
		}
		echo "<tr><td><button type='submit' name='save'>Save</button></td><td><button type='submit' name='save2'>Save &amp; Insert Next</button></td></tr></table></form>";
	}
break;

case "22"://table edit row
	$ed->check([1,2,3],['redir'=>'20']);
	$db=$ed->sg[1];
	$tb=$ed->sg[2];
	$nu=$ed->sg[3];
	if(empty($nu)) $ed->redir("20/$db/$tb",['err'=>"Can't edit empty field"]);
	$id=($ed->sg[4]=="isnull"?"":base64_decode($ed->sg[4]));
	$nu1=(empty($ed->sg[5])?"":$ed->sg[5]); $id1=(empty($ed->sg[6])?"":base64_decode($ed->sg[6]));
	$q_col=$ed->con->query("SELECT column_name,data_type,is_nullable FROM information_schema.columns WHERE table_schema='public' AND table_name='{$tb}' ORDER BY ordinal_position");
	$coln=[];//field
	$colt=[];//type
	$colu=[];//null
	foreach($q_col->fetch(2) as $r_brw){
		$coln[]=$r_brw['column_name'];
		$colt[]=$r_brw['data_type'];
		$colu[]=$r_brw['is_nullable'];
	}
	$nul=("(".$nu." IS NULL OR ".$nu."='')");
	if($ed->post('edit','i')){//update
		$qr1="UPDATE $tb SET ";
		$qr2="";
		$p=0;
		while($p<count($coln)){
			if($colt[$p]=="bytea"){
				if(!empty($_FILES["te".$p]['tmp_name'])){
				$blb="'\x".bin2hex(file_get_contents($_FILES["te".$p]['tmp_name']))."'";
				$qr2.=$coln[$p]."=".$blb.",";
				}
			}elseif($colt[$p]=='boolean'){
				$qr2.=$coln[$p]."=".$ed->post("te".$p,0).",";
			}else{
				$qr2.=$coln[$p]."=".(($ed->post('te'.$p,'e') && !is_numeric($ed->post('te'.$p)) && $colu[$p]==1)? "NULL":"'".addslashes($ed->post('te'.$p))."'").",";
			}
			++$p;
		}
		$qr2=substr($qr2,0,-1);
		$qr3=" WHERE ".($id==""?$nul:$nu."='".addslashes($id)."'").(!empty($nu1) && !empty($id1)?" AND $nu1='".addslashes($id1)."'":"");
		$q_upd=$ed->con->query($qr1.$qr2.$qr3);
		if($q_upd) $ed->redir("20/$db/$tb",['ok'=>"Successfully updated"]);
		else $ed->redir("20/$db/$tb",['err'=>"Update failed"]);
	}else{//edit form
		$q_rst=$ed->con->query("SELECT ".implode(",",$coln)." FROM $tb WHERE ".($id==""?$nul:$nu."='".addslashes($id)."'").(!empty($colt[1]) && $colt[1]=="bytea" && !empty($nu1) && !empty($id1)?" AND $nu1='".addslashes($id1)."'":""));
		if($q_rst->num_row() < 1) $ed->redir("20/$db/$tb",['err'=>"Edit failed"]);
		$r_rx=$q_rst->fetch();
		echo $head.$ed->menu($db,$tb,1).$ed->form("22/$db/$tb/$nu/".($id==""?"isnull":base64_encode($id)).(!empty($colt[1]) && $colt[1]=="bytea" && !empty($nu1) && !empty($id1)?"/$nu1/".base64_encode($r_rx['1']):""),1)."<table><caption>Edit Row</caption>";
		$k=0;
		while($k<count($coln)){
			echo "<tr><td>".$coln[$k]."</td><td>";
			if($colt[$k]=='boolean'){//boolean
			foreach($bbs as $kk=>$bb) echo "<input type='radio' name='te{$k}[]' value='$bb'".($r_rx[$k]==$kk ? " checked":"")." /> $bb ";
			}elseif($colt[$k]=="bytea" && !in_array($db,$deny)){
			$v=empty($r_rx[$k])?0:strlen(stream_get_contents($r_rx[$k]));
			echo number_format(($v/1024),2)." KB<br/><input type='file' name='te{$k}'/>";
			}elseif($colt[$k]=="text"){//text
			echo "<textarea name='te{$k}'>".($r_rx[$k]==''?'':htmlentities($r_rx[$k],ENT_QUOTES))."</textarea>";
			}else{
			echo "<input type='text' name='te{$k}' value='".($r_rx[$k]==''?'':htmlentities($r_rx[$k],ENT_QUOTES))."'/>";
			}
			echo "</td></tr>";
			++$k;
		}
	echo "<tr><td><a class='del link' href='".$ed->path."23/$db/$tb/$nu/".($id==""?"isnull":base64_encode($id)).(!empty($nu1) && !empty($id1)?"/$nu1/".base64_encode($id1):"")."'>Delete</a></td><td><button type='submit' name='edit'>Update</button></td></tr></table></form>";
	}
break;

case "23"://table delete row
	$ed->check([1,2,3],['redir'=>'20']);
	$db=$ed->sg[1];
	$tb=$ed->sg[2];
	$nu=$ed->sg[3];
	$id=$ed->sg[4];
	$nul=("(".$nu." IS NULL OR ".$nu."='')");
	$q_delro=$ed->con->query("DELETE FROM $tb WHERE ".($id=="isnull"?$nul:$nu."='".addslashes(base64_decode($id))."'").(!empty($ed->sg[5]) && !empty($ed->sg[6])?" AND ".$ed->sg[5]."='".addslashes(base64_decode($ed->sg[6]))."'":""));
	if($q_delro && $q_delro->num_row()) $ed->redir("20/$db/$tb",['ok'=>"Successfully deleted"]);
	else $ed->redir("20/$db/$tb",['err'=>"Delete row failed"]);
break;

case "24"://search
	$ed->check([1,2]);
	$db=$ed->sg[1];
	$tb=$ed->sg[2];
	unset($_SESSION["_sqlsearch_{$db}_{$tb}"]);
	if(!empty($ed->sg[3]) && $ed->sg[3]=='reset'){
	$ed->redir("20/$db/$tb",['ok'=>"Reset search"]);
	}
	$q_se=$ed->con->query("SELECT column_name FROM information_schema.columns WHERE table_schema='public' AND table_name='$tb' ORDER BY ordinal_position")->fetch(2);
	$cond1=['=','&lt;','&gt;','&lt;=','&gt;=','!=','LIKE','NOT LIKE','SIMILAR TO','NOT SIMILAR TO'];
	$cond2=['BETWEEN','NOT BETWEEN'];
	$cond3=['IN','NOT IN'];
	$cond4=['IS NULL','IS NOT NULL'];
	$cond=array_merge($cond1,$cond2,$cond3,$cond4);
	if($ed->post('search','i')){//post
	$search_cond=[];
	foreach($q_se as $r_se){
		if($ed->post($r_se['column_name'],'!e') || in_array($ed->post('cond__'.$r_se['column_name']),$cond4)){
		$fd=$r_se['column_name'];
		$cd=$ed->post('cond__'.$fd);
		$po=$ed->post($fd);
		if(in_array($cd,$cond2)){
		$sl=preg_split("/[,]+/",$po);
		$sl2=(!empty($sl[1])?$sl[1]:$sl[0]);
		$search_cond[]=$fd." ".$cd." '".$sl[0]."' AND '".$sl2."'";
		}
		elseif(in_array($cd,$cond3)) $search_cond[]=$fd." ".$cd." ('".$po."')";
		elseif(in_array($cd,$cond4)) $search_cond[]=$fd." ".$cd;
		else $search_cond[]=$fd." ".html_entity_decode($ed->post('cond__'.$fd))." '$po'";
		}
	}
	$se_str=($search_cond?"WHERE ":"").implode(" AND ",$search_cond).($ed->post('order_field','!e')?" ORDER BY ".$ed->post('order_field')." ".$ed->post('order_ord')." ":"");
	$_SESSION["_sqlsearch_{$db}_{$tb}"]=$se_str;
	$ed->redir("20/$db/$tb");
	}

	echo $head.$ed->menu($db,$tb,1).$ed->form("24/$db/$tb")."<table><caption>Search</caption>";
	$conds="";
	foreach($cond as $cnd) $conds.="<option value='$cnd'>$cnd</option>";
	$fields="<option value=''>&nbsp;</option>";
	foreach($q_se as $r_se){
	$fl=$r_se['column_name'];
	$fields.="<option value='$fl'>$fl</option>";
	echo "<tr><td>$fl</td><td><select name='cond__".$fl."'>$conds</select></td><td><input type='text' name='$fl'/></td></tr>";
	}
	echo "<tr class='c1'><td>Order</td><td><select name='order_field'>$fields</select></td><td><select name='order_ord'><option value='ASC'>ASC</option><option value='DESC'>DESC</option></select></td></tr>
	<tr><td colspan='3'><button type='submit' name='search'>Search</button></td></tr></table></form>";
break;

case "25"://table empty
	$ed->check([1,2]);
	$db=$ed->sg[1];
	$tb=$ed->sg[2];
	$ed->con->query("TRUNCATE TABLE $tb");
	$ed->redir("20/$db/$tb",['ok'=>"Table is empty"]);
break;

case "26"://table drop
	$ed->check([1,2]);
	$db=$ed->sg[1];
	$tb=$ed->sg[2];
	$ed->con->query("DROP TABLE \"{$tb}\" CASCADE");
	$ed->redir("5/$db",['ok'=>"Successfully dropped"]);
break;

case "27"://vacuum,analyze,reindex
	$ed->check([1,2]);
	$db=$ed->sg[1];
	$tb=$ed->sg[2];
	$op=$ed->sg[3];
	if(!in_array($db,deny)){
	if(!empty($op)){
		$sql_op='';
		switch(strtolower($op)){
		case 'vacuum': $sql_op="VACUUM FULL $tb"; break;
		case 'analyze': $sql_op="ANALYZE $tb"; break;
		case 'reindex': $sql_op="REINDEX TABLE $tb"; break;
		default: $ed->redir("10/$db/$tb",['err'=>"Action $op failed"]);
		}
		if(!empty($sql_op)){
			$q_op=$ed->con->query($sql_op);
			if($q_op) $ed->redir("10/$db/$tb",['ok'=>"Successfully executed"]);
			else $ed->redir("10/$db/$tb",['err'=>"Action $op failed"]);
		}
	}else{
		$ed->redir("10/$db/$tb",['err'=>"Action $op failed"]);
	}
	}else{
		$ed->redir("10/$db/$tb",['err'=>"Action restricted on this table"]);
	}
break;

case "30"://import
	$ed->check([1]);
	$db=$ed->sg[1];
	$out="";
	$q=0;
	set_time_limit(7200);
	if($ed->post()){
	$e='';
	$rgex='~(?:\s*--[^\r\n]*|\s*#[^\r\n]*|\s*/\*.*?\*/|"(?:[^"\\\\]|\\\\.)*"|\'(?:[^\'\\\\]|\\\\.)*\'|\$\$.*?\$\$)(*SKIP)(*F)|;~s';
	if($ed->post('qtxt','!e')){//in textarea
		$qtxt=$ed->post('qtxt');
		if(preg_match('/^\b(select|show)\b/is',$qtxt)){
			$q_sel=$ed->con->query($qtxt);
			if($q_sel){
			$q_sel=$q_sel->fetch(2);
			echo $head.$ed->menu($db,'',1)."<table><tr>";
			foreach($q_sel[0] as $k=>$r_sel) echo "<th>$k</th>";
			echo "</tr>";
			foreach($q_sel as $r_sel){
			$bg=($bg==1)?2:1;
			echo "<tr class='r c$bg'>";
			foreach($r_sel as $r_se) echo "<td>$r_se</td>";
			echo "</tr>";
			}
			echo "</table>";
			} else $ed->redir("5/$db",['err'=>"Wrong query"]);
		}else{
			$e=preg_split($rgex,$qtxt,-1,PREG_SPLIT_NO_EMPTY);
		}
	}elseif($ed->post('send','i') && $ed->post('send')=="ja"){//from file
		if(empty($_FILES['importfile']['tmp_name'])){
		$ed->redir("5/$db",['err'=>"No file to upload"]);
		}else{
		$tmp=$_FILES['importfile']['tmp_name'];
		$file=$_FILES['importfile']['name'];
		preg_match("/^(.*)\.(sql|csv|json|xml|gz|zip)$/i",$file,$ext);
		if($ext[2]=='sql'){
			$fi=$ed->utf(file_get_contents($tmp));
			$e=preg_split($rgex,$fi,-1,PREG_SPLIT_NO_EMPTY);
		}elseif($ext[2]=='csv'){
			$e=$ed->imp_csv($ext[1],$tmp);
		}elseif($ext[2]=='json'){
			$e=$ed->imp_json($ext[1],$tmp);
		}elseif($ext[2]=='xml'){
			$e=$ed->imp_xml($tmp);
		}elseif($ext[2]=='gz'){
			if(($fgz=fopen($tmp,'r')) !==FALSE){
				if(@fread($fgz,3) !="\x1F\x8B\x08"){
				$ed->redir("5/$db",['err'=>"Not a valid GZ file"]);
				}
				fclose($fgz);
			}
			if(@function_exists('gzopen')){
				preg_match("/^(.*)\.(sql|csv|json|xml|tar)$/i",$ext[1],$ex);
				$gzfile=@gzopen($tmp,'rb');
				if(!$gzfile){
				$ed->redir("5/$db",['err'=>"Open GZ failed"]);
				}
				$e='';
				while(!gzeof($gzfile)){
				$e.=gzgetc($gzfile);
				}
				gzclose($gzfile);
				if($ex[2]=='sql') $e=preg_split($rgex,$ed->utf($e),-1,PREG_SPLIT_NO_EMPTY);
				elseif($ex[2]=='csv') $e=$ed->imp_csv($ex[1],$e);
				elseif($ex[2]=='json') $e=$ed->imp_json($ex[1],$e);
				elseif($ex[2]=='xml') $e=$ed->imp_xml($e);
				elseif($ex[2]=='tar'){
					$fh=gzopen($tmp,'rb');
					$fsize=strlen($e);
					$total=0;$e=[];
					while(false !== ($block=gzread($fh,512))){
					$total+=512;
					$t=unpack("a100name/a8mode/a8uid/a8gid/a12size/a12mtime",$block);
					$file=['name'=>$t['name'],'mode'=>@octdec($t['mode']),'uid'=>@octdec($t['uid']),'size'=>@octdec($t['size']),'mtime'=>@octdec($t['mtime'])];
					$file['bytes']=($file['size'] + 511) & ~511;
					if($file['bytes'] > 0){
					$block=gzread($fh,$file['bytes']);
					$buf=substr($block,0,$file['size']);
					$fi=trim($file['name']);
					preg_match("/^(.*)\.(sql|csv|json|xml)$/i",$fi,$tx);
					if($tx[2]=='sql') $e[]=preg_split($rgex,$ed->utf($buf),-1,PREG_SPLIT_NO_EMPTY);
					elseif($tx[2]=='csv') $e[]=$ed->imp_csv($tx[1],$buf);
					elseif($tx[2]=='json') $e[]=$ed->imp_json($tx[1],$buf);
					elseif($tx[2]=='xml') $e[]=$ed->imp_xml($buf);
					$total+=$file['bytes'];
					}
					if($total >= $fsize-1024) break;
					}
					gzclose($fh);
					$e=call_user_func_array('array_merge',$e);
				}
			}else{
				$ed->redir("5/$db",['err'=>"Open GZ failed"]);
			}
		}elseif($ext[2]=='zip'){
			if(($fzip=fopen($tmp,'r')) !==FALSE){
				if(@fread($fzip,4) !="\x50\x4B\x03\x04"){
				$ed->redir("5/$db",['err'=>"Not a valid ZIP file"]);
				}
				fclose($fzip);
			}
			$e=[];
			$zip=new ZipArchive;
			$res=$zip->open($tmp);
			if($res === TRUE){
				$i=0;
				while($i < $zip->numFiles){
				$zentry=$zip->getNameIndex($i);
				$buf=$zip->getFromName($zentry);
				preg_match("/^(.*)\.(sql|csv|json|xml)$/i",$zentry,$zn);
				if(!empty($zn[2])){
				if($zn[2]=='sql') $e[]=preg_split($rgex,$ed->utf($buf),-1,PREG_SPLIT_NO_EMPTY);
				elseif($zn[2]=='csv') $e[]=$ed->imp_csv($zn[1],$buf);
				elseif($zn[2]=='json') $e[]=$ed->imp_json($zn[1],$buf);
				elseif($zn[2]=='xml') $e[]=$ed->imp_xml($buf);
				}
				++$i;
				}
				$zip->close();
				if(count($e) != count($e,COUNT_RECURSIVE)) $e=call_user_func_array('array_merge',$e);
			}
		}else{
			$ed->redir("5/$db",['err'=>"Disallowed extension"]);
		}
		}
	}else{
		$ed->redir("5/$db",['err'=>"Query failed"]);
	}
	if(!empty($e) && is_array($e)){
		$ed->con->begin();
		foreach($e as $qry){
			$qry=trim($qry);
			if(!empty($qry)){
				$exc=$ed->con->query($qry);
				$op=['insert','update','delete'];
				$p_qry=strtolower(substr($qry,0,6));
				if(in_array($p_qry,$op) && $exc) $exc=$exc->num_row();
				if($exc) ++$q;
				else $out.="<p><b>FAILED!</b> $qry</p>";
			}
		}
		$ed->con->commit();
		echo $head.$ed->menu($db)."<div class='col2'><p>Successfully executed: <b>$q quer".($q>1?'ies':'y')."</b></p>$out";
	}
	} else $ed->redir("5/$db",[]);
break;

case "31"://export form
	$ed->check([1]);
	$db=$ed->sg[1];
	$q_tts=$ed->con->query("SELECT table_name FROM information_schema.tables WHERE table_catalog='$db' AND table_schema NOT IN ('pg_catalog','information_schema')");
	if($q_tts->num_row()>0){
	echo $head.$ed->menu($db,'',2).$ed->form("32/$db")."<div class='dw'><h3 class='l1'>Export</h3><h3>Select table(s)</h3>
	<p><input type='checkbox' onchange='selectall(this,\"tbs\");dbx(\"tbs\")'/> All/None</p>
	<select id='tbs' name='tbs[]' multiple='multiple' onchange='dbx(\"tbs\")'>";
	foreach($q_tts->fetch(1) as $r_tt) echo "<option value='".$r_tt[0]."'>".$r_tt[0]."</option>";
	echo "</select>";
	}else{
	$ed->redir("5/$db",["err"=>"No export empty DB"]);
	}
	echo "<h3><input type='checkbox' onclick='toggle(this,\"fopt[]\");fmt()'/> Options</h3>";
	$opts=['structure'=>'Structure','data'=>'Data','drop'=>'Drop if exist','ifnot'=>'If not exist','trigger'=>'Triggers','procfunc'=>'Routines'];
	foreach($opts as $k=> $opt) echo "<p><input type='checkbox' name='fopt[]' value='$k' /> $opt</p>";
	echo "<h3>File format</h3>";
	$ffo=['sql'=>'SQL','xls'=>'Spreadsheet','xml'=>'XML','doc'=>'Word','json'=>'JSON','csv1'=>'CSV,','csv2'=>'CSV;'];
	foreach($ffo as $k=> $ff) echo "<p><input type='radio' name='ffmt[]' onclick='fmt()' value='$k'".($k=='sql' ? ' checked':'')." /> $ff</p>";
	echo "<h3>File compression</h3><p><select name='ftype'>";
	$fty=['plain'=>'None','zip'=>'Zip','gz'=>'GZ'];
	foreach($fty as $k=> $ft) echo "<option value='$k'>$ft</option>";
	echo "</select></p><button type='submit' name='exp'>Export</button></div></form>";
break;

case "32"://export
	if($ed->post('exp','i')){
	$ed->check([1]);
	$ffmt=$ed->post('ffmt'); $ftype=$ed->post('ftype');
	$db=$ed->sg[1];
	if(!empty($ed->sg[1]) && $ed->post('tbs')==''){
		$ed->redir("31".(empty($ed->sg[1])?'':'/'.$ed->sg[1]),['err'=>"You didn't selected any Table"]);
	}
	if($ed->post('fopt')=='' && in_array($ffmt[0],['sql','xml'])){//export options
		$ed->redir("31".(empty($ed->sg[1])?'':'/'.$ed->sg[1]),['err'=>"You didn't selected any option"]);
	}else{
		$fopt=$ed->post('fopt');
	}
	if($ffmt[0]=='sql'){//sql format
		$ffty="text/plain"; $ffext=".sql"; $fname=$db.$ffext;
		$sq="-- EdPgAdmin $version SQL Dump\n\n";
		list($tbs,$vws)=$ed->getTables($db);
		foreach($tbs as $tb){
			if(in_array('structure',$fopt)){//structure
				$sq.=$ed->tb_structure($tb,$fopt,'');
			}
			if(in_array('data',$fopt)){//option data
				$q_fil=$ed->con->query("SELECT column_name,data_type,is_nullable FROM information_schema.columns WHERE table_schema='public' AND table_name='$tb'");
				$cols=$q_fil->num_row();
				$r_fil=$q_fil->fetch(1);
				$q_rx=$ed->con->query("SELECT * FROM $tb");
				if($q_rx){
					$sq.="\n";
					foreach($q_rx->fetch(1) as $r_rx){
						$ins="INSERT INTO $tb VALUES (";
						$inn="";
						$e=0;
						while($e<$cols){
							$bi=$r_fil[$e][1];//bytea
							if($bi=="bytea"){
								$by=stream_get_contents($r_rx[$e]);
								if(empty($by)){
								$inn.="'', ";
								}elseif(strpos($by,"\0")==true){
								$inn.="0x".bin2hex($by).", ";
								}else{
								$inn.="'".addslashes($by)."', ";
								}
							}elseif(is_numeric($r_rx[$e])){
							$inn.=$r_rx[$e].", ";
							}elseif(empty($r_rx[$e]) && $r_fil[$e][2]==1){
							$inn.="'', ";
							}elseif(empty($r_rx[$e]) && $r_fil[$e][2]!=1){
							$inn.="NULL, ";
							}else{
							$inn.=($r_rx[$e]==''?'':"'".preg_replace(["/\r\n|\r|\n/","/'/"],["\\n","\'"],$r_rx[$e])."', ");
							}
							++$e;
						}
						$ins.=substr($inn,0,-2);
						$sq.=$ins.");\n";
					}
					$sq.="\n";
				}
			}
		}
		if($vws !='' && in_array('structure',$fopt)){//export views
		foreach($vws as $vw){
			$q_rw=$ed->con->query("SELECT definition FROM pg_views WHERE viewname='$vw'");
			if($q_rw && $q_rw->num_row()>0){
			if(in_array('drop',$fopt)){//option drop
			$sq.="\nDROP VIEW IF EXISTS $vw;\n";
			}
			foreach($q_rw->fetch(1) as $r_rr){
			$sq.="CREATE VIEW $vw AS ".trim($r_rr[0])."\n";
			}
			$sq.="\n";
			}
		}
		}
		if(in_array('procfunc',$fopt)){//option routine
			$q_pr=$ed->con->query("SELECT routine_name,routine_type,external_language,routine_definition FROM information_schema.routines r JOIN pg_proc p ON r.routine_name=p.proname WHERE r.routine_schema='public'");
			if($q_pr && $q_pr->num_row()>0){
			$sq.="\n";
			foreach($q_pr->fetch(1) as $r_pr){
				$sq.="\n";
				if(in_array('drop',$fopt)){//option drop
				$q_ty=$ed->con->query("SELECT oid::regprocedure FROM pg_proc WHERE proname='{$r_pr[0]}'");
				$pr=$q_ty->fetch()[0];
				$sq.="DROP {$r_pr[1]} IF EXISTS $pr;\n";
				}
				$q_type=$ed->con->query("SELECT pg_get_function_arguments(p.oid) AS params,pg_get_function_result(p.oid) AS returns FROM pg_proc p JOIN pg_namespace n ON p.pronamespace=n.oid WHERE p.proname='{$r_pr[0]}' AND n.nspname='public'");
				$ty=$q_type->fetch();
				$sq.="\nCREATE OR REPLACE {$r_pr[1]} {$r_pr[0]}({$ty[0]}) ".($r_pr[1]=="FUNCTION"?"RETURNS {$ty[1]} ":"")."AS\n$$\n".trim($r_pr[3])."\n$$\nLANGUAGE {$r_pr[2]};\n";
			}
			$sq.="\n";
			}
		}
		if(in_array('trigger',$fopt)){//option trigger
			$q_trg=$ed->con->query("SELECT tgname,relname,pg_get_triggerdef(t.oid) FROM pg_trigger t JOIN pg_class c ON t.tgrelid=c.oid WHERE NOT tgisinternal");
			if($q_trg && $q_trg->num_row()>0){
			$sq.="\n";
			foreach($q_trg->fetch(1) as $r_trg){
				if(in_array('drop',$fopt)){//option drop
				$sq.="DROP TRIGGER IF EXISTS {$r_trg[0]} ON {$r_trg[1]};\n";
				}
				$sq.=$r_trg[2].";\n";
			}
			}
		}
		$sql=$sq;
	}elseif($ffmt[0]=='csv1' || $ffmt[0]=='csv2'){//csv format
		list($tbs)=$ed->getTables($db);
		$ffty="text/csv"; $ffext=".csv"; $fname=$db.$ffext;
		$sql=[];
		$sign=($ffmt[0]=='csv1'?',':';');
		if(empty($tbs[0])) $ed->redir("31/".$db,['err'=>"Select a table"]);
		foreach($tbs as $tb){
			$sq='';
			$q_csv=$ed->con->query("SELECT column_name,data_type,is_nullable FROM information_schema.columns WHERE table_schema='public' AND table_name='$tb'")->fetch(1);
			foreach($q_csv as $r_csv) $sq.=$r_csv[0].$sign;
			$sq=substr($sq,0,-1)."\n";
			$q_rs=$ed->con->query("SELECT * FROM $tb")->fetch(1);
			foreach($q_rs as $r_rs){
			$x=0;
			foreach($r_rs as $r_r){
			if(empty($r_r) && $q_csv[$x][2]!=1)$sq.='NULL';
			elseif($q_csv[$x][1]=='bytea') $sq.="\x".bin2hex(stream_get_contents($r_r));
			elseif(is_numeric($r_r)) $sq.=$r_r;
			else $sq.="\"".preg_replace(["/\r\n|\r|\n/","/'/","/\"/"],["\\n","\'","\"\""],$r_r)."\"";
			$sq=$sq.$sign;
			++$x;
			}
			$sq=substr($sq,0,-1)."\n";
			}
			$sql[$tb.$ffext]=$sq;
		}
		if($ftype=="plain" || count($tbs)<2){
		$fname=$tbs[0].$ffext;
		$sql=$sql[$fname];
		}
	}elseif($ffmt[0]=='json'){//json format
		list($tbs)=$ed->getTables($db);
		$ffty="text/json"; $ffext=".json"; $fname=$db.$ffext;
		$sql=[];
		foreach($tbs as $tb){
			$sq='';
			$q_jst=$ed->con->query("SELECT column_name,data_type,is_nullable FROM information_schema.columns WHERE table_schema='public' AND table_name='$tb'")->fetch(1);
			$q_jso=$ed->con->query("SELECT * FROM $tb");
			if($q_jso->num_row()>0){
			$sq.='[';
			foreach($q_jso->fetch(2) as $k_jso=>$r_jso){
			$jh='{';
			$x=0;
			foreach($r_jso as $k_jo=>$r_jo){
			if(empty($r_jo) && $q_jst[$x][2]!=1)$jh.='"'.$k_jo.'":"NULL",';
			else $jh.='"'.$k_jo.'":'.(is_numeric($r_jo)?$r_jo:($q_jst[$x][1]=='bytea'?'"\x'.bin2hex(stream_get_contents($r_jo)).'"':'"'.preg_replace(["/\r\n|\r|\n/","/\t/","/'/","/\"/"],["\\n","\\t","''","\\\""],$r_jo).'"')).',';
			++$x;
			}
			$sq.=substr($jh,0,-1).'},';
			}
			$sq=substr($sq,0,-1).']';
			}
			$sql[$tb.$ffext]=$sq;
		}
		if($ftype=="plain" || count($tbs)<2){
		$fname=$tbs[0].$ffext;
		$sql=$sql[$fname];
		}
	}elseif($ffmt[0]=='doc'){//doc format
		$ffty="application/msword"; $ffext=".doc"; $fname=$db.$ffext;
		list($tbs)=$ed->getTables($db);
		$sq='<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:word" 	xmlns="http://www.w3.org/TR/REC-html40"><!DOCTYPE html><html><head><meta http-equiv="Content-type" content="text/html;charset=utf-8"></head><body>';
		foreach($tbs as $tb){
			$q_doc=$ed->con->query("SELECT column_name,data_type FROM information_schema.columns WHERE table_schema='public' AND table_name='$tb'")->fetch(1);
			$wb='<table border=1 cellpadding=0 cellspacing=0 style="border-collapse: collapse"><caption>'.$tb.'</caption><tr>';
			foreach($q_doc as $r_dc) $wb.='<th>'.$r_dc[0].'</th>';
			$wb.="</tr>";
			$q_dc2=$ed->con->query("SELECT * FROM $tb")->fetch(1);
			foreach($q_dc2 as $r_dc2){
			$wb.="<tr>";
			$x=0;
			foreach($r_dc2 as $r_d2){
			$wb.='<td>'.($q_doc[$x][1]=='bytea'?"\x".bin2hex(stream_get_contents($r_d2)):htmlentities($r_d2)).'</td>';
			++$x;
			}
			$wb.="</tr>";
			}
			$wb.='</table><br>';
			$sq.=$wb;
		}
		$sq.='</body></html>';
		if($ftype!="plain" && count($tbs)>1) $sql[$db.$ffext]=$sq;
		else $sql=$sq;
	}elseif($ffmt[0]=='xls'){//xls format
		$ffty="application/excel"; $ffext=".xls"; $fname=$db.$ffext;
		list($tbs)=$ed->getTables($db);
		$sq='<?xml version="1.0"?><Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet" xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet" xmlns:html="http://www.w3.org/TR/REC-html40">';
		foreach($tbs as $tb){
			$xh='<Worksheet ss:Name="'.$tb.'"><Table><Row>';
			$q_xl1=$ed->con->query("SELECT column_name,data_type FROM information_schema.columns WHERE table_schema='public' AND table_name='$tb'")->fetch(1);
			foreach($q_xl1 as $r_xl1) $xh.='<Cell><Data ss:Type="String">'.$r_xl1[0].'</Data></Cell>';
			$xh.='</Row>';
			$q_xl2=$ed->con->query("SELECT * FROM $tb")->fetch(1);
			foreach($q_xl2 as $r_xl2){
			$xh.='<Row>';
			$x=0;
			foreach($r_xl2 as $r_x2){
			$xh.='<Cell><Data ss:Type="'.(is_numeric($r_x2)?'Number':'String').'">'.($q_xl1[$x][1]=='bytea'?"\x".bin2hex(stream_get_contents($r_x2)):htmlentities($r_x2)).'</Data></Cell>';
			++$x;
			}
			$xh.='</Row>';
			}
			$sq.=$xh.'</Table></Worksheet>';
		}
		$sq.='</Workbook>';
		if($ftype!="plain" && count($tbs)>1) $sql[$db.$ffext]=$sq;
		else $sql=$sq;
	}elseif($ffmt[0]=='xml'){//xml format
		$ffty="application/xml"; $ffext=".xml"; $fname=$db.$ffext;
		list($tbs)=$ed->getTables($db);
		$sq="<?xml version=\"1.0\" encoding=\"utf-8\"?>\n<!-- EdPgAdmin $version XML Dump -->\n<export version=\"1.0\" xmlns:ed=\"https://github.com/edmondsql\">";
		if(in_array('structure',$fopt)){
		$sq.="\n\t<ed:structure_schemas>";
		$sq.="\n\t\t<ed:database name=\"$db\">";
		foreach($tbs as $tb){
			$sq.="\n\t\t\t<ed:table name=\"$tb\">";
			$sq.=$ed->tb_structure($tb,$fopt,"\t\t\t");
			$sq.="\n\t\t\t</ed:table>";
		}
		$sq.="\n\t\t</ed:database>\n\t</ed:structure_schemas>";
		}
		$sq2='';
		if(in_array('data',$fopt)){
		$sq2="\n\t<database name=\"$db\">";
		foreach($tbs as $tb){
		$q_xm1=$ed->con->query("SELECT column_name,data_type FROM information_schema.columns WHERE table_schema='public' AND table_name='$tb'")->fetch(1);
		$q_xm2=$ed->con->query("SELECT * FROM $tb")->fetch(1);
		foreach($q_xm2 as $r_=>$r_xm2){
			$sq2.="\n\t\t<table name=\"$tb\">";
			$x=0;
			foreach($r_xm2 as $r_x2){
			$sq2.="\n\t\t\t<column name=\"".$q_xm1[$x][0]."\">".($q_xm1[$x][1]=='bytea'?"\x".bin2hex(stream_get_contents($r_x2)):htmlspecialchars($r_x2))."</column>";
			++$x;
			}
			$sq2.="\n\t\t</table>";
		}
		}
		$sq2.="\n\t</database>";
		}
		$sq.=$sq2."\n</export>";
		if($ftype!="plain" && count($tbs)>1) $sql[$db.$ffext]=$sq;
		else $sql=$sq;
	}

	if($ftype=="gz"){//gz
		$zty="application/x-gzip"; $zext=".gz";
		ini_set('zlib.output_compression','Off');
		if(is_array($sql) && count($sql)>1){
		$sq='';
		foreach($sql as $qname=>$sqa){
			$tmpf=tmpfile();
			$len=strlen($sqa);
			$ctxt=pack("a100a8a8a8a12a12",$qname,644,0,0,decoct($len),decoct(time()));
			$checksum=8*32;
			for($i=0; $i < strlen($ctxt); $i++) $checksum +=ord($ctxt[$i]);
			$ctxt.=sprintf("%06o",$checksum)."\0 ";
			$ctxt.=str_repeat("\0",512 - strlen($ctxt));
			$ctxt.=$sqa;
			$ctxt.=str_repeat("\0",511 - ($len + 511) % 512);
			fwrite($tmpf,$ctxt);
			fseek($tmpf,0);
			$fs=fstat($tmpf);
			$sq.=fread($tmpf,$fs['size']);
			fclose($tmpf);
		}
		$fname=$db.".tar";
		$sql=$sq.pack('a1024','');
		}
		$sql=gzencode($sql,9);
		header('Accept-Encoding: gzip;q=0,deflate;q=0');
	}elseif($ftype=="zip"){//zip
		$zty="application/x-zip";
		$zext=".zip";
		$info=[];
		$ctrl_dir=[];
		$eof="\x50\x4b\x05\x06\x00\x00\x00\x00";
		$old_offset=0;
		if(is_array($sql)) $sqlx=$sql;
		else $sqlx[$fname]=$sql;
		foreach($sqlx as $qname=>$sqa){
		$ti=getdate();
		if($ti['year'] < 1980){
		$ti['year']=1980;$ti['mon']=1;$ti['mday']=1;$ti['hours']=0;$ti['minutes']=0;$ti['seconds']=0;
		}
		$time=(($ti['year'] - 1980) << 25) | ($ti['mon'] << 21) | ($ti['mday'] << 16) | ($ti['hours'] << 11) | ($ti['minutes'] << 5) | ($ti['seconds'] >> 1);
		$dtime=substr("00000000".dechex($time),-8);
		$hexdtime='\x'.$dtime[6].$dtime[7].'\x'.$dtime[4].$dtime[5].'\x'.$dtime[2].$dtime[3].'\x'.$dtime[0].$dtime[1];
		eval('$hexdtime="'.$hexdtime.'";');
		$fr="\x50\x4b\x03\x04\x14\x00\x00\x00\x08\x00".$hexdtime;
		$unc_len=strlen($sqa);
		$crc=crc32($sqa);
		$zdata=gzcompress($sqa);
		$zdata=substr(substr($zdata,0,strlen($zdata) - 4),2);
		$c_len=strlen($zdata);
		$fr.=pack('V',$crc).pack('V',$c_len).pack('V',$unc_len).pack('v',strlen($qname)).pack('v',0).$qname.$zdata;
		$info[]=$fr;
		$cdrec="\x50\x4b\x01\x02\x00\x00\x14\x00\x00\x00\x08\x00".$hexdtime.
		pack('V',$crc).pack('V',$c_len).pack('V',$unc_len).pack('v',strlen($qname)).
		pack('v',0).pack('v',0).pack('v',0).pack('v',0).pack('V',32).pack('V',$old_offset);
		$old_offset +=strlen($fr);
		$cdrec.=$qname;
		$ctrl_dir[]=$cdrec;
		}
		$ctrldir=implode('',$ctrl_dir);
		$end=$ctrldir.$eof.pack('v',sizeof($ctrl_dir)).pack('v',sizeof($ctrl_dir)).pack('V',strlen($ctrldir)).pack('V',$old_offset)."\x00\x00";
		$datax=implode('',$info);
		$sql=$datax.$end;
	}
	header("Cache-Control: no-store,no-cache,must-revalidate,pre-check=0,post-check=0,max-age=0");
	header("Content-Type: ".($ftype=="plain" ? $ffty."; charset=utf-8":$zty));
	header("Content-Length: ".strlen($sql));
	header("Content-Disposition: attachment; filename=".$fname.($ftype=="plain" ? "":$zext));
	die($sql);
	}
break;

case "33"://bytea download
	$ed->check([1,2,3],['redir'=>'20']);
	$db=$ed->sg[1];
	$tb=$ed->sg[2];
	$nu=$ed->sg[3];
	$id=base64_decode($ed->sg[4]);
	if(empty($ed->sg[7])){
	$ph=$ed->sg[5];$nu1="";
	}else{
	$ph=$ed->sg[7];$nu1=" AND ".$ed->sg[5]."='".base64_decode($ed->sg[6])."'";
	}
	$r_ph=$ed->con->query("SELECT $ph FROM $tb WHERE $nu='$id'$nu1 LIMIT 1")->fetch();
	$r_ph=stream_get_contents($r_ph[0]);$len=strlen($r_ph);
	$tp='application/octet-stream';$xt='bin';
	if($len>3){
	if(substr($r_ph,0,3)=="\xFF\xD8\xFF"){$tp='image/jpg';$xt='jpg';}
	elseif(substr($r_ph,0,3)=="GIF"){$tp='image/gif';$xt='gif';}
	elseif(substr($r_ph,0,4)=="\x89PNG"){$tp='image/png';$xt='png';}
	elseif(substr($r_ph,0,4)=="RIFF"){$tp='image/webp';$xt='webp';}
	}
	header("Content-type: $tp");
	header("Content-Length: $len");
	header("Content-Disposition: attachment; filename={$tb}-bytea.{$xt}");
	die($r_ph);
break;

case "40"://view
	if(!isset($ed->sg[2]) && !isset($ed->sg[3])){//add
		$ed->check([1]);
		$db=$ed->sg[1];
		$r_uv=[0=>'',1=>''];
		if($ed->post('uv1','!e') && $ed->post('uv2','!e')){
			$tb=$ed->sanitize($ed->post('uv1'));
			$exi=$ed->con->query("SELECT 1 FROM pg_views WHERE viewname='$tb'")->fetch();
			if($exi) $ed->redir("5/$db",['err'=>"This name exist"]);
			$vdef=$ed->post('uv2');
			$def=$ed->con->query($vdef);
			if(!$def) $ed->redir("5/$db",['err'=>"Wrong statement"]);
			$v_cre=$ed->con->query("CREATE VIEW $tb AS $vdef");
			if($v_cre) $ed->redir("5/$db",['ok'=>"Successfully created"]);
			else $ed->redir("5/$db",['err'=>"Create view failed"]);
		}
		echo $head.$ed->menu($db,'',2).$ed->form("40/$db");
		$lbl="Create";
	}else{//edit
		$ed->check([1,5]);
		$db=$ed->sg[1];$sp=$ed->sg[2];$ty=$ed->sg[3];
		if($ed->post('uv1','!e') && $ed->post('uv2','!e')){
			$tb=$ed->sanitize($ed->post('uv1'));
			if(is_numeric(substr($tb,0,1))) $ed->redir("5/$db",['err'=>"Not a valid name"]);
			$q_ch=$ed->con->query("SELECT 1 FROM pg_views WHERE viewname='$tb'")->fetch();
			if($tb!=$sp && $q_ch) $ed->redir("5/$db",['err'=>"Wrong name"]);
			$vdef=$ed->post('uv2');
			$def=$ed->con->query($vdef);
			if(!$def) $ed->redir("5/$db",['err'=>"Wrong statement"]);
			$ed->con->query("DROP VIEW IF EXISTS $sp CASCADE");
			$ed->con->query("CREATE VIEW $tb AS $vdef");
			$ed->redir("5/$db",['ok'=>"Successfully updated"]);
		}
		$r_uv=$ed->con->query("SELECT definition FROM pg_views WHERE viewname='$sp'")->fetch();
		$r_uv[1]=$sp;
		echo $head.$ed->menu($db,'',2).$ed->form("40/$db/$sp/$ty");
		$lbl="Edit";
	}
	echo "<table><tr><th colspan='2'>$lbl View</th></tr><tr><td>Name</td><td><input type='text' name='uv1' value='".$r_uv[1]."'/></td></tr><tr><td>Statement</td><td><textarea name='uv2'>".trim($r_uv[0])."</textarea></td></tr><tr><td colspan='2'><button type='submit'>Save</button></td></tr></table></form>";
break;

case "41"://trigger
	if(!isset($ed->sg[2]) && !isset($ed->sg[3])){//add
		$ed->check([1]);
		$db=$ed->sg[1];
		$r_tge=[0=>'',1=>'',2=>'',3=>'',4=>'',5=>''];
		if($ed->post('utg0','!e') && $ed->post('utg5','!e')){
		$utg0=$ed->sanitize($ed->post('utg0'));
		if(is_numeric(substr($utg1,0,1))) $ed->redir("41/$db",['err'=>"Not a valid name"]);
		$utg1=$ed->post('utg1');$utg2=$ed->post('utg2');$utg3=$ed->post('utg3');$utg4=$ed->post('utg4');$utg5=$ed->post('utg5');
		$q_tgcrt=$ed->con->query("CREATE TRIGGER $utg0 $utg2 $utg3 ON $utg1 FOR EACH $utg4 $utg5");
		if($q_tgcrt) $ed->redir("5/$db",['ok'=>"Successfully created"]);
		else $ed->redir("5/$db",['err'=>"Create failed"]);
		}
		echo $head.$ed->menu($db,'',2).$ed->form("41/$db");
		$t_lb="Create";
	}else{//edit
		$ed->check([1,5]);
		$db=$ed->sg[1];$sp=$ed->sg[2];$ty=$ed->sg[3];
		if($ed->post('utg1','!e') && $ed->post('utg5','!e')){
			$utg0=$ed->sanitize($ed->post('utg0'));$oldtb=$ed->post('oldtb');
			$utg1=$ed->post('utg1');$utg2=$ed->post('utg2');$utg3=$ed->post('utg3');$utg4=$ed->post('utg4');$utg5=$ed->post('utg5');
			if(is_numeric(substr($utg1,0,1))) $ed->redir("5/$db",['err'=>"Not a valid name"]);
			$ed->con->query("DROP TRIGGER IF EXISTS $sp ON $oldtb CASCADE");
			$q_tgcrt=$ed->con->query("CREATE TRIGGER $utg0 $utg2 $utg3 ON $utg1 FOR EACH $utg4 $utg5");
			if($q_tgcrt){
			$ed->redir("5/$db",['ok'=>"Successfully updated"]);
			}else{
			$ed->redir("41/$db/$sp/$ty",['err'=>"Update failed"]);
			}
		}
		$q_tg=$ed->con->query("SELECT tgname,relname,pg_get_triggerdef(t.oid) FROM pg_trigger t JOIN pg_class c ON t.tgrelid=c.oid WHERE tgname='$sp'");
		if($q_tg && $q_tg->num_row()>0){
			$r_tg=$q_tg->fetch();
			$r_tge[0]=$sp;
			$r_tge[1]=$r_tg[1];
			$tg_def=$r_tg[2];
			if(preg_match('/(BEFORE|AFTER|INSTEAD OF)\s+(INSERT|UPDATE|DELETE|TRUNCATE)\s+ON\s+(?:.*)\s+FOR\s+EACH\s+(ROW|STATEMENT)\s+(.+)/',$tg_def,$match)){
			$r_tge[2]=strtoupper($match[1]);
			$r_tge[3]=strtoupper($match[2]);
			$r_tge[4]=strtoupper($match[3]);
			$r_tge[5]=$match[4];
			}
		}
		echo $head.$ed->menu($db,'',2).$ed->form("41/$db/$sp/$ty");
		$t_lb="Edit";
	}
	$tgt=[];//list tables
	$q_tgt=$ed->con->query("SELECT * FROM information_schema.tables WHERE table_schema='public' AND table_type='BASE TABLE' AND table_catalog='$db'")->fetch(2);
	foreach($q_tgt as $r_tt) $tgt[]=$r_tt['table_name'];
	echo "<input type='hidden' name='oldtb' value='{$r_tge[1]}'/><table><tr><th colspan='2'>$t_lb Trigger</th></tr><tr><td>Name</td><td><input type='text' name='utg0' value='{$r_tge[0]}'/></td></tr><tr><td>Table</td><td><select name='utg1'>";
	foreach($tgt as $tt) echo "<option value='$tt'".($r_tge[1]==$tt? " selected":"").">$tt</option>";
	echo "</select></td></tr><tr><td>Time</td><td><select name='utg2'>";
	$tm=['BEFORE','AFTER','INSTEAD OF'];
	foreach($tm as $tn) echo "<option value='$tn'".($r_tge[2]==$tn?" selected":"").">$tn</option>";
	echo "</select></td></tr><tr><td>Event</td><td><select name='utg3'>";
	$evm=['INSERT','UPDATE','DELETE','TRUNCATE'];
	foreach($evm as $evn) echo "<option value='$evn'".($r_tge[3]==$evn?" selected":"").">$evn</option>";
	echo "</select></td></tr><tr><td>Each</td><td><select name='utg4'>";
	$f_each=['ROW','STATEMENT'];
	foreach($f_each as $each) echo "<option value='$each'".($r_tge[4]==$each?" selected":"").">$each</option>";
	echo "</select></td></tr><tr><td>Definition</td><td><textarea name='utg5'>".$r_tge[5]."</textarea></td></tr><tr><td colspan='2'><button type='submit'>Save</button></td></tr></table></form>";
break;

case "42"://routine
	$db=$ed->sg[1];
	$ed->check([1]);
	if($ed->post('save','i') && $ed->post('rname','!e') && $ed->post('rbody','!e')){
		$r_name=$ed->sanitize($ed->post('rname'));
		$r_type=$ed->post('rtype');
		$r_params=$ed->post('rparams');
		$r_returns=$ed->post('rreturns');
		$r_lang=$ed->post('rlang');
		$r_body=$ed->post('rbody');
		if(!empty($r_name) && !empty($ed->sg[2]) && $ed->sg[2]!=$r_name){
		$q_ty=$ed->con->query("SELECT oid::regprocedure FROM pg_proc WHERE proname='{$ed->sg[2]}'");
		$sp=$q_ty->fetch()[0];
		$ed->con->query("ALTER $r_type $sp RENAME TO $r_name");
		}
		if(empty($r_name)||empty($r_body)) $ed->redir("42/$db",['err'=>"Name and body fields are required."]);
		$q_create=$ed->con->query("CREATE OR REPLACE $r_type $r_name ($r_params) ".($r_type=="FUNCTION"?"RETURNS $r_returns ":"")."LANGUAGE $r_lang AS $$ $r_body $$");
		if($q_create) $ed->redir("5/$db",['ok'=>"Successfully saved"]);
		else $ed->redir("42/$db",['err'=>"Failed to save"]);
	}
	if(!isset($ed->sg[2]) && !isset($ed->sg[3])){//add
		$rname_val='';$rtype_val='';$rparams_val='';$rreturns_val='';$rlang_val='PLPGSQL';$rbody_val='';
		echo $head.$ed->menu($db,'',2).$ed->form("42/$db");
		$p_lb="Create";
	}else{
		$ed->check([1,5]);
		$sp=$ed->sg[2];
		$ty=$ed->sg[3];
		if(!empty($ty)){
			$q_rou=$ed->con->query("SELECT routine_name,routine_type,external_language,routine_definition FROM information_schema.routines r JOIN pg_proc p ON r.routine_name=p.proname WHERE r.routine_schema='public' AND r.routine_name='$sp' AND r.routine_type='".strtoupper($ty)."'");
			if($q_rou && $q_rou->num_row()>0){
			$r_rou=$q_rou->fetch(2)[0];
			$rname_val=$r_rou['routine_name'];
			$rtype_val=$r_rou['routine_type'];
			$rlang_val=$r_rou['external_language'];
			$rbody_val=trim($r_rou['routine_definition']);
			$q_type=$ed->con->query("SELECT pg_get_function_arguments(p.oid) AS params,pg_get_function_result(p.oid) AS returns FROM pg_proc p JOIN pg_namespace n ON p.pronamespace=n.oid WHERE p.proname='$sp' AND n.nspname='public'");
			$types=$q_type->fetch();
			$rparams_val=$types[0];
			$rreturns_val=$types[1];
			}
		}
		echo $head.$ed->menu($db,'',2).$ed->form("42/$db/$sp/$ty");
		$p_lb="Edit";
	}
	$routine_types=['FUNCTION','PROCEDURE'];
	$languages=['PLPGSQL','SQL','C','INTERNAL'];
	echo "<table><tr><th colspan='2'>$p_lb Routine</th></tr><tr><td>Name</td><td><input type='text' name='rname' value='$rname_val'/></td></tr><tr><td>Type</td><td><select name='rtype'>";
	foreach($routine_types as $rt_opt) echo "<option value='{$rt_opt}'".($rt_opt == $rtype_val ? " selected":"").">$rt_opt</option>";
	echo "</select></td></tr><tr><td>Parameters</td><td><input type='text' name='rparams' value='$rparams_val'/></td></tr><tr><td>Returns</td><td><input type='text' name='rreturns' value='$rreturns_val'/></td></tr><tr><td>Language</td><td><select name='rlang'>";
	foreach($languages as $lang_opt) echo "<option value='{$lang_opt}'".($lang_opt == $rlang_val ? " selected":"").">{$lang_opt}</option>";
	echo "</select></td></tr><tr><td>Body</td><td><textarea name='rbody'>$rbody_val</textarea></td></tr><tr><td colspan='2'><button type='submit' name='save'>Save</button></td></tr></table></form>";
break;

case "48"://execute routine
	$ed->check([1]);
	$db=$ed->sg[1];
	$sp=empty($ed->sg[2]) ? '':$ed->sg[2];
	$ty=empty($ed->sg[3]) ? '':$ed->sg[3];
	if(empty($sp)||empty($ty)){
		$ed->redir("5/$db",['err'=>"Routine not specified"]);
	}
	$q_params=$ed->con->query("SELECT proargnames,proargtypes FROM pg_proc WHERE proname='$sp' AND pronamespace=(SELECT oid FROM pg_namespace WHERE nspname='public')");
	$params=[];
	if($q_params && $q_params->num_row()>0){
		$proc_info=$q_params->fetch(2)[0];
		$arg_names=$proc_info['proargnames'] ? explode(',',trim($proc_info['proargnames'],'{}')):[];
		$arg_types_oids=$proc_info['proargtypes'] ? explode(' ',trim($proc_info['proargtypes'],'{}')):[];
		foreach($arg_types_oids as $idx => $oid){
			$type_name_query=$ed->con->query("SELECT typname FROM pg_type WHERE oid=$oid");
			$type_name=$type_name_query ? $type_name_query->fetch()[0]:'';
			$param_name=isset($arg_names[$idx]) ? $arg_names[$idx]:"arg".$idx;
			$params[]=['name'=>$param_name,'type'=>$type_name];
		}
	}
	if($ed->post('execute','i')){
		$args=[];
		foreach($params as $idx => $param){
			$input_val=$ed->post('param_'.$idx);
			if(in_array($param['type'],['int','integer','smallint','bigint','numeric','float','double precision'])){
				$args[]=$input_val;
			}elseif($param['type']=='boolean'){
				$args[]=($input_val=='1'?'TRUE':'FALSE');
			}else{
				$args[]=$input_val;
			}
		}
		$call_sql="SELECT $sp(".implode(',',$args).")";
		$q_exec=$ed->con->query($call_sql);
		if($q_exec){
			$result=$q_exec->fetch(1);
			echo $head.$ed->menu($db,'',1)."<table><tr><th colspan='2'>Execution Result</th></tr><tr><td>".json_encode($result)."</td></tr></table>";
		}else{
			$ed->redir("48/$db/$sp/$ty",['err'=>"Routine execution failed"]);
		}
	}else{
		echo $head.$ed->menu($db,'',2);
		echo $ed->form("48/$db/$sp/$ty")."<table><tr><th colspan='2'>Execute Routine</th></tr>";
		if(empty($params)){
			echo "<tr><td colspan='2'>This routine takes no parameters.</td></tr>";
		}else{
			foreach($params as $idx => $param){
				echo "<tr><td>{$param['name']} ({$param['type']})</td><td><input type='text' name='param_{$idx}'/></td></tr>";
			}
		}
		echo "<tr><td colspan='2'><button type='submit' name='execute'>Execute</button></td></tr></table></form>";
	}
break;

case "49"://drop sp
	$ed->check([1,5]);
	$sp=$ed->sg[2];
	$ty=$ed->sg[3];
	$tg='';
	if($ty=='trigger'){
	$q=$ed->con->query("SELECT relname FROM pg_trigger t JOIN pg_class c ON t.tgrelid=c.oid WHERE tgname='$sp'");
	$tg=' ON '.$q->fetch()[0];
	}elseif($ty=='function'){
	$q_ty=$ed->con->query("SELECT oid::regprocedure FROM pg_proc WHERE proname='$sp'");
	$sp=$q_ty->fetch()[0];
	}
	$q_drop=$ed->con->query("DROP $ty IF EXISTS $sp".$tg." CASCADE");
	if($q_drop) $ed->redir("5/".$ed->sg[1],['ok'=>"Successfully dropped"]);
break;

case "50"://login
	if($ed->post('lhost','!e') && $ed->post('username','!e') && $ed->post('password','i')){
	$_SESSION['user']=$ed->post('username');
	$_SESSION['host']=$ed->post('lhost');
	$_SESSION['token']=$ed->enco($ed->post('password'));
	$ed->redir();
	}
	session_unset();
	session_destroy();
	echo $head.$ed->menu('','',2).$ed->form("50")."<div class='dw'><h3>LOGIN</h3>
	<div>Host<br/><input type='text' id='host' name='lhost' value='localhost'/></div>
	<div>Username<br/><input type='text' name='username' value='postgres'/></div>
	<div>Password<br/><input type='password' name='password'/></div>
	<div><button type='submit'>Login</button></div></div></form>";
break;

case "51"://logout
	session_unset();
	session_destroy();
	$ed->redir();
break;

case "52"://users
	$ed->check();
	$q_usr=$ed->con->query("SELECT rolname,rolsuper,rolcreaterole,rolcreatedb FROM pg_roles WHERE rolcanlogin=true ORDER BY rolname")->fetch(1);
	echo $head.$ed->menu(1,'',2)."<table><tr><th>User</th><th>Superuser</th><th>Create Role</th><th>Create DB</th><th><a href='{$ed->path}53'>Add</a></th></tr>";
	if($q_usr){
	foreach($q_usr as $r_usr){
	$bg=($bg==1)?2:1;
	$rolname=$r_usr[0];
	$rolsuper=$r_usr[1]?'Yes':'No';
	$rolcreaterole=$r_usr[2]?'Yes':'No';
	$rolcreatedb=$r_usr[3]?'Yes':'No';
	echo "<tr class='r c$bg'><td>$rolname</td><td>$rolsuper</td><td>$rolcreaterole</td><td>$rolcreatedb</td><td><a href='{$ed->path}54/".base64_encode($rolname)."'>change</a><a class='del' href='{$ed->path}55/".base64_encode($rolname)."'>drop</a></td></tr>";
	}
	}
	echo "</table>";
break;

case "53"://add user
	$ed->check();
	echo $head.$ed->menu(1,'',2);
	if($ed->post('usr','!e') && $ed->post('pwd','i')){
		$usr=$ed->post('usr');
		$pwd=$ed->post('pwd');
		$super=($ed->post('super','i') ? '':'NO').'SUPERUSER';
		$createrole=($ed->post('createrole','i') ? '':'NO').'CREATEROLE';
		$createdb=($ed->post('createdb','i') ? '':'NO').'CREATEDB';
		$inherit=($ed->post('bypass','i') ? '':'NO').'INHERIT';
		$bypass=($ed->post('bypass','i') ? '':'NO').'BYPASSRLS';
		$createreplica=($ed->post('createreplica','i') ? '':'NO').'REPLICATION';
		$ed->con->query("CREATE DATABASE $usr");
		$q_crtu=$ed->con->query("CREATE ROLE $usr WITH LOGIN PASSWORD '$pwd' {$super} {$createrole} {$createdb} {$inherit} {$bypass} {$createreplica}");
		if($q_crtu) $ed->redir("52",['ok'=>"Successfully created"]);
		else $ed->redir("52",['err'=>"Create user failed"]);
	}else{
		echo $ed->form("53")."<table><tr><th colspan='2'>Create User</th></tr><tr><td>User</td><td><input type='text' name='usr'/></td></tr><tr><td>Password</td><td><input type='password' name='pwd'/></td></tr><tr><td>Superuser</td><td><input type='checkbox' name='super' value='1'/></td></tr><tr><td>Create Role</td><td><input type='checkbox' name='createrole' value='1'/></td></tr><tr><td>Create DB</td><td><input type='checkbox' name='createdb' value='1'/></td></tr><tr><td>Inherit</td><td><input type='checkbox' name='inherit' value='1'/></td></tr><tr><td>Bypass RLS</td><td><input type='checkbox' name='bypass' value='1'/></td></tr><tr><td>Create Replication</td><td><input type='checkbox' name='createreplica' value='1'/></td></tr><tr><td colspan='2'><button type='submit'>Create</button></td></tr></table></form>";
	}
break;

case "54"://change user
	$ed->check([6],['db'=>'']);
	$usr=base64_decode($ed->sg[1]);
	echo $head.$ed->menu(1,'',2);
	$r_usr=$ed->con->query("SELECT rolname,rolsuper,rolcreaterole,rolcreatedb,rolreplication,rolinherit,rolbypassrls FROM pg_roles WHERE rolname='$usr'");
	$r_usr=$r_usr->fetch();
	if(empty($r_usr)) $ed->redir("52",['err'=>"User not found."]);
	if($ed->post('pwd','!e')){
		$pwd=$ed->post('pwd');
		$super=($ed->post('super','i') ? '':'NO').'SUPERUSER';
		$createrole=($ed->post('createrole','i') ? '':'NO').'CREATEROLE';
		$createdb=($ed->post('createdb','i') ? '':'NO').'CREATEDB';
		$inherit=($ed->post('bypass','i') ? '':'NO').'INHERIT';
		$bypass=($ed->post('bypass','i') ? '':'NO').'BYPASSRLS';
		$createreplica=($ed->post('createreplica','i') ? '':'NO').'REPLICATION';
		$q_chgu=$ed->con->query("ALTER ROLE $usr WITH PASSWORD '$pwd' {$super} {$createrole} {$createdb} {$inherit} {$bypass} {$createreplica}");//"md5".md5($pwd.$usr)
		if($q_chgu) $ed->redir("52",['ok'=>"Successfully changed"]);
		else $ed->redir("52",['err'=>"Change user failed"]);
	}else{
		echo $ed->form("54/".base64_encode($usr))."<table><tr><th>Change User</th><th>$usr</th></tr><tr><td>Password</td><td><input type='password' name='pwd'/></td></tr><tr><td>Superuser</td><td><input type='checkbox' name='super' value='1'".($r_usr[1]?" checked":"")."/></td></tr><tr><td>Create Role</td><td><input type='checkbox' name='createrole' value='1'".($r_usr[2]?" checked":"")."/></td></tr><tr><td>Create DB</td><td><input type='checkbox' name='createdb' value='1'".($r_usr[3]?" checked":"")."/></td></tr><tr><td>Inherit</td><td><input type='checkbox' name='inherit' value='1'".($r_usr[5]?" checked":"")."/></td></tr><tr><td>Bypass RLS</td><td><input type='checkbox' name='bypass' value='1'".($r_usr[6]?" checked":"")."/></td></tr><tr><td>Create Replication</td><td><input type='checkbox' name='createreplica' value='1'".($r_usr[4]?" checked":"")."/></td></tr><tr><td colspan='2'><button type='submit'>Change</button></td></tr></table></form>";
	}
break;

case "55"://drop user
	$ed->check([6],['db'=>'']);
	$usr=base64_decode($ed->sg[1]);
	$ed->con->query("REASSIGN OWNED BY $usr TO CURRENT_USER");
	$ed->con->query("DROP OWNED BY $usr");
	$q_dro=$ed->con->query("DROP ROLE $usr");
	if($q_dro) $ed->redir("52",['ok'=>"Successfully deleted"]);
	else $ed->redir("52",['err'=>"Delete user failed"]);
break;

case "60"://info
	$ed->check([],['db'=>'']);
	echo $head.$ed->menu(1,'',2)."<table>";
	if(empty($ed->sg[1])){
		$ver=$ed->con->query("SHOW server_version")->fetch();
		echo "<tr><th colspan='2'>INFO</th></tr>";
		$q_var=['Extension'=>'pdo_pgsql','DB'=>$ver[0],'Php'=>PHP_VERSION,'Software'=>$_SERVER['SERVER_SOFTWARE']];
		foreach($q_var as $r_k=>$r_var){
		$bg=($bg==1)?2:1;
		echo "<tr class='r c$bg'><td>$r_k</td><td>$r_var</td></tr>";
		}
	}elseif($ed->sg[1]=='var'){
		echo "<tr><th>Variable</th><th>Value</th></tr>";
		$q_vars=$ed->con->query("SELECT name,setting,unit,short_desc FROM pg_settings ORDER BY name");
		if($q_vars){
		foreach($q_vars->fetch(1) as $r_var){
		$bg=($bg==1)?2:1;
		echo "<tr class='r c$bg'><td>{$r_var[0]}</td><td>{$r_var[1]} {$r_var[2]}</td></tr>";
		}
		}
	}elseif($ed->sg[1]=='status'){
		echo "<tr><th>Variable</th><th>Value</th></tr>";
		$q_sts=$ed->con->query("SELECT * FROM pg_stat_activity WHERE pid=pg_backend_pid()")->fetch(2);
		foreach($q_sts[0] as $k=>$v){
		$bg=($bg==1)?2:1;
		echo "<tr class='r c$bg'><td>$k</td><td>$v</td></tr>";
		}
	}elseif($ed->sg[1]=='process'){
		if(!empty($ed->sg[2]) && is_numeric($ed->sg[2])) $ed->con->query("SELECT pg_terminate_backend(".$ed->sg[2].")");
		$q_proc=$ed->con->query("SELECT pid,usename,datname,client_addr,application_name,state,query_start FROM pg_stat_activity WHERE state='active' ORDER BY query_start DESC");
		echo "<tr><th>PID</th><th>User</th><th>Database</th><th>Client</th><th>App Name</th><th>State</th><th>Query Start</th><th>Actions</th></tr>";
		foreach($q_proc->fetch(1) as $r_proc){
		$bg=($bg==1)?2:1;
		$pid=$r_proc[0];
		echo "<tr class='r c$bg'><td>$pid</td><td>{$r_proc[1]}</td><td>{$r_proc[2]}</td><td>{$r_proc[3]}</td><td>{$r_proc[4]}</td><td>{$r_proc[5]}</td><td>{$r_proc[6]}</td><td><a class='del' href='{$ed->path}61/process/$pid'>Kill</a></td></tr>";
		}
	}
	echo "</table>";
break;
}
unset($_POST,$_SESSION["ok"],$_SESSION["err"]);
?></div></div><div class="l1 ce"><a href="http://edmondsql.github.io">edmondsql</a></div>
<script>
const $=(s)=>document.querySelector(s);
const $$=(s)=>document.querySelectorAll(s);
const $n=(s)=>document.getElementsByName(s);
const $c=(s)=>document.createElement(s);
Element.prototype.show=function(){this.style.display='';}
Element.prototype.hide=function(){this.style.display='none';}

const host=$("#host");
if(host)host.focus();

let msg=$$(".msg");
$$(".del").forEach(d=>{
d.addEventListener('click',(e)=>{
e.preventDefault();
msg.forEach(m=>m.remove());
let hrf=e.target.getAttribute("href"),nMsg=$c("div"),nOk=$c("div"),nEr=$c("div");
nMsg.className='msg';
nOk.className='ok';nOk.innerText='Yes';
nEr.className='err';nEr.innerText='No';
nMsg.appendChild(nOk);nMsg.lastChild.onclick=()=>window.location=hrf;
nMsg.appendChild(nEr);nMsg.lastChild.onclick=()=>nMsg.remove();
document.body.appendChild(nMsg);
document.body.addEventListener('keyup',(e)=>{
e.preventDefault();
let key=e.which||e.keyCode||e.key||0;
if(key==32||key==89)window.location=hrf;
if(key==27||key==78)nMsg.remove();
});
});
});
msg.forEach(m=>{if(m.innerText!="")setTimeout(()=>{m.remove()},7000);m.addEventListener('dblclick',()=>m.remove())});

function selectall(cb,lb){
let i,multi=$('#'+lb);
if(cb.checked){for(i=0;i<multi.options.length;i++) multi.options[i].selected=true;
}else{multi.selectedIndex=-1;}
}
function toggle(cb,el){
let i,cbox=$n(el);
for(i=0;i<cbox.length;i++) cbox[i].checked=cb.checked;
}
function fmt(){
let j,opt=$n("fopt[]"),ff=$n("ffmt[]"),to=opt.length,ch="",ft=$n("ftype")[0];
for(j=0; ff[j]; ++j){if(ff[j].checked) ch=ff[j].value;}
if($('#tbs'))dbx('tbs');
if(ch=="sql"){
for(let k=0;k<to;k++) opt[k].parentElement.show();
}else if(ch=="xml"){
let k,n=4;
for(k=0;k<n;k++) opt[k].parentElement.show();
for(k=n;k<to;k++){opt[k].parentElement.hide();opt[k].checked=false}
}else{
for(let i=0;i<to;i++){opt[i].parentElement.hide();opt[i].checked=false}
}
}
function dbx(el){
let j,ch="",ft=$n("ftype")[0],ff=$n("ffmt[]"),db=$$("#"+el+" option:checked").length,arr=["json","csv1","csv2"];
for(j=0;ff[j];++j){if(ff[j].checked) ch=ff[j].value;}
if(ft[0].value!="plain"){
if((db<2 && arr.indexOf(ch)>-1)||(db>1 && arr.indexOf(ch)==-1)){
let op=$c("option");
op.value="plain";op.text="None";
ft.options.add(op,0);
ft.options[0].selected=true;
}
}
if(db>1 && ft[0].value=="plain" && arr.indexOf(ch)>-1)ft[0].remove();
}
</script>
</body></html>