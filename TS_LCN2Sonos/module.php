<?
class TS_LCN2Sonos extends IPSModule
{
    
		public function Create()
		{
			//Never delete this line!
			parent::Create();
        //These lines are parsed on Symcon Startup or Instance creation
        //You cannot use variables here. Just static values.
        $this->RegisterPropertyInteger("Sonos_ID", 0 );
        $this->RegisterPropertyInteger("LCNDisplayId", 0);
        $this->RegisterPropertyInteger("Trigger", 0);
        $this->RegisterPropertyInteger("LCNDisplayLine1", 0);
        $this->RegisterPropertyInteger("Trigger_BMI", 0);
        $this->RegisterPropertyInteger("Rel_id", 0);
      
    }

//*********************************************************************************************************
    public function ApplyChanges()
    {
        //Never delete this line!
        parent::ApplyChanges();

        
        // Start create profiles
//         $this->RegisterVariableString("Sleeptimer", "Sleeptimer", "",100);
         
         $steuer_id =  $this->ReadPropertyInteger("Rel_id");
         $timer_id = $this->RegisterVariableInteger("Timer", "Timer", "Switch.SONOS",101);
         $trigger_id = $this->ReadPropertyInteger("Trigger_BMI");
         
        // Start add scripts 
        $timerScript='<?
$SonosId = IPS_GetProperty(IPS_GetParent($_IPS["SELF"]), "Sonos_ID");

if (@IPS_GetObjectIDByName("Sleeptimer", $SonosId)){
    $ip = IPS_GetProperty($SonosId, "IPAddress");
    if (Sys_Ping($ip, 1000) == true) {
        $s_steuer = GetValue(IPS_GetObjectIDByName("Timer", IPS_GetParent($_IPS["SELF"])));
        $s_bmi_aktiv = GetValue(IPS_GetObjectIDByName("Timer", IPS_GetParent($_IPS["SELF"])));
        include_once("../modules/SymconSonos/Sonos/sonosAccess.php");

      	if ($s_bmi_aktiv  == 1){
      		$sonos = new SonosAccess($ip); //Sonos ZP IPAdresse
      		$sonos->Play();
      		$sonos = new SonosAccess($ip); //Sonos ZP IPAdresse
      		$sonos->SetSleeptimer(0,6,0);
      	}
    }
}
?>';
  $timerScriptID = $this->RegisterScript("_timer", "_timer", $timerScript);
  IPS_SetHidden($timerScriptID,true);

  $sk_id=IPS_GetObjectIDByIdent('_timer', $this->InstanceID);
  if ( IPS_ScriptExists($sk_id)){
      IPS_SetScriptContent ( $sk_id, $timerScript);
  }


        $timerScriptaktion = '<?
$SonosId = IPS_GetProperty(IPS_GetParent($_IPS["SELF"]), "Sonos_ID");
if (@IPS_GetObjectIDByName("Sleeptimer", $SonosId)){
//Script zum WERTEZUWEISEN aus dem Webfrontend
  if($_IPS["SENDER"] == "WebFront"){
      SetValue($_IPS["VARIABLE"], $_IPS["VALUE"]);
  }
  $s_steuer = ($_IPS["VALUE"]);
  $ip = IPS_GetProperty($SonosId, "IPAddress");
  if (Sys_Ping($ip, 1000) == true) {
    include_once("../modules/SymconSonos/Sonos/sonosAccess.php");
    $sonos = new SonosAccess($ip); //Sonos ZP IPAdresse
    if ($s_steuer == 0) {
      $sonos->SetSleeptimer(0,0,0);
    }
    if ($s_steuer == 1){
    $sonos->SetSleeptimer(0,6,0);
    }
    $s_steuer = GetValue(IPS_GetObjectIDByName("Timer", IPS_GetParent($_IPS["SELF"]))   );
  }
}
?>';
  $timerScriptaktionID = $this->RegisterScript("_timer_aktion", "_timer_aktion", $timerScriptaktion);

              IPS_SetHidden($timerScriptaktionID,true);
              IPS_SetVariableCustomAction($timer_id,$timerScriptaktionID);
              
              $aktiv = true;
              $this->Registerevent1($trigger_id,$timerScriptID,$aktiv);
  $sk_id=IPS_GetObjectIDByIdent('_timer_aktion', $this->InstanceID);
  if ( IPS_ScriptExists($sk_id)){
      IPS_SetScriptContent ( $sk_id, $timerScriptaktion);
  }
              
// sleeptimer ende

//update_status
        $_update_status = '<?
$SonosId = IPS_GetProperty(IPS_GetParent($_IPS["SELF"]), "Sonos_ID");
$timer1ID= IPS_GetObjectIDByName("_updateStatus", $SonosId);
$timer2ID= IPS_GetObjectIDByName("_updateGrouping", $SonosId);
$StatusID= IPS_GetObjectIDByName("Status", $SonosId);
$nowPlayingID= IPS_GetObjectIDByName("nowPlaying", $SonosId);
$VolumeID= IPS_GetObjectIDByName("Volume", $SonosId);
$RadioID= IPS_GetObjectIDByName("Radio", $SonosId);
$triggerID = IPS_GetObjectIDByName("E_Trigger",(IPS_GetObjectIDByName("_timer",IPS_GetParent($_IPS["SELF"]))));

    $RelId = IPS_GetProperty(IPS_GetParent($_IPS["SELF"]), "Rel_id");
    $Rel= GetValueBoolean($RelId);
    if ($Rel == 0) {
     IPS_SetScriptTimer($timer1ID , 0);                  // ScriptTimer einschalten (auf 0 setzen)
     IPS_SetScriptTimer($timer2ID , 0);
     SetValueInteger($StatusID, 3);
     SetValueString($nowPlayingID, "");
     SetValueInteger($VolumeID, 0);
     SetValueInteger($RadioID, 0);
     IPS_SetEventActive($triggerID, false);  // deAktivert Ereignis
    }
    if ($Rel == 1){
     IPS_SetScriptTimer($timer1ID  , 5);                  // ScriptTimer einschalten (auf 5 Sekunde setzen)
     IPS_SetScriptTimer($timer2ID, 300);                  // ScriptTimer einschalten (auf 300 Sekunde setzen)
     IPS_SetEventActive($triggerID, true);  // deAktivert Ereignis
    }
?>';
  $_update_status_ID = $this->RegisterScript("_update_status", "_update_status", $_update_status);

             IPS_SetHidden($_update_status_ID,true);
             $this->Registerevent2($_update_status_ID,$steuer_id); 

  $sk_id=IPS_GetObjectIDByIdent('_update_status', $this->InstanceID);
  if ( IPS_ScriptExists($sk_id)){
      IPS_SetScriptContent ( $sk_id, $_update_status);
  }

//update_status
//Autostart
        $auto = '<?
$steuer_id = IPS_GetProperty(IPS_GetParent($_IPS["SELF"]), "Rel_id");
$SonosId = IPS_GetProperty(IPS_GetParent($_IPS["SELF"]), "Sonos_ID");
$radio=IPS_GetProperty($SonosId, "FavoriteStation");

switch ($_IPS["SENDER"])                                     // Ursache (Absender) des Triggers ermittlen
{
  case "Variable":                                       // status hat sich geÃ¤ndert
    $steuer = GetValueBoolean($steuer_id );
    if ($steuer)                                 // hat eingeschaltet
	{
     IPS_SetScriptTimer($_IPS["SELF"]  , 60);                  // ScriptTimer einschalten (auf 60 Sekunde setzen)
	} else {
		IPS_SetScriptTimer($_IPS["SELF"]  , 0);
	}
  break;
  case "TimerEvent":                                     // Timer hat getriggert
		SNS_SetRadio($SonosId ,$radio);
		SNS_Play($SonosId);
		IPS_SetScriptTimer($_IPS["SELF"]  , 0);
  break;

}
//		TSSNS_SetRadio($SonosId ,$radio);
//		TSSNS_Play($SonosId);
?>';
    $autoID = $this->RegisterScript("_autostart", "_autostart", $auto);

             IPS_SetHidden($autoID,true);
             IPS_SetScriptTimer($autoID, 0); 
             $this->Registerevent3($autoID,$steuer_id); 
  $sk_id=IPS_GetObjectIDByIdent('_autostart', $this->InstanceID);
  if ( IPS_ScriptExists($sk_id)){
      IPS_SetScriptContent ( $sk_id, $auto);
  }

//Autostart


        $_lcn_sonos = '<?
// Display ----------------------------------------------------------------------------------------
$DisplayId = IPS_GetProperty(IPS_GetParent($_IPS["SELF"]), "LCNDisplayId");
$SonosId = IPS_GetProperty(IPS_GetParent($_IPS["SELF"]), "Sonos_ID");
$LCNDisplayLine1 = IPS_GetProperty(IPS_GetParent($_IPS["SELF"]), "LCNDisplayLine1");

$DisplayZeile   = $LCNDisplayLine1;
$sourceID= IPS_GetObjectIDByName("nowPlaying", $SonosId);
//print_r($sourceID);
$nowPlaying     = GetValueString($sourceID);
//print_r($nowPlaying);
LCN_SendCommand($DisplayId, "GT", "DT" . $DisplayZeile . "1" . (substr($nowPlaying,  0, 12)));
LCN_SendCommand($DisplayId, "GT", "DT" . $DisplayZeile . "2" . (substr($nowPlaying, 12, 12)));
LCN_SendCommand($DisplayId, "GT", "DT" . $DisplayZeile . "3" . (substr($nowPlaying, 24, 12)));
LCN_SendCommand($DisplayId, "GT", "DT" . $DisplayZeile . "4" . (substr($nowPlaying, 36, 12)));
LCN_SendCommand($DisplayId, "GT", "DT" . $DisplayZeile . "5" . (substr($nowPlaying, 48, 12)));
// Display ----------------------------------------------------------------------------------------

?>';
    $_lcn_sonosID  = $this->RegisterScript("_lcn_sonos", "_lcn_sonos", $_lcn_sonos);

        IPS_SetHidden($_lcn_sonosID,true);
        $Trigger_id =$this->ReadPropertyInteger("Trigger");
        $this->Registerevent_trigger($_lcn_sonosID,$Trigger_id); 
  $sk_id=IPS_GetObjectIDByIdent('_lcn_sonos', $this->InstanceID);
  if ( IPS_ScriptExists($sk_id)){
      IPS_SetScriptContent ( $sk_id, $_lcn_sonos);
  }

        // End add scripts

    }

//*********************************************************************************************************
		private function Registerevent_trigger($TargetID,$sid_berechnung)
		{ 
      if(!isset($_IPS))
      global $_IPS;  
      $EreignisID = @IPS_GetEventIDByName("E_trigger",  $TargetID);
      if ($EreignisID == true){
        if (IPS_EventExists(IPS_GetEventIDByName ( "E_trigger", $TargetID)))
        {
           IPS_DeleteEvent(IPS_GetEventIDByName ( "E_trigger", $TargetID));
        }
      }       
      $eid = IPS_CreateEvent(0);                  //Ausgelöstes Ereignis
      IPS_SetName($eid, "E_trigger");
      IPS_SetEventTrigger($eid, 1, $sid_berechnung);        //Bei Änderung von Variable mit ID 15754
      IPS_SetParent($eid, $TargetID);         //Ereignis zuordnen
      IPS_SetEventActive($eid, true);             //Ereignis aktivieren
    }	

		private function Registerevent1($trigger_id,$TargetID, $aktiv)  //$trigger_id,$timerScriptID
		{ 
      if(!isset($_IPS))
      global $_IPS;  
      $EreignisID = @IPS_GetEventIDByName("E_Trigger",  $TargetID);
      if ($EreignisID == true){
        if (IPS_EventExists(IPS_GetEventIDByName ( "E_Trigger", $TargetID)))
        {
         IPS_DeleteEvent(IPS_GetEventIDByName ( "E_Trigger", $TargetID));
        }
      }
      $eid = IPS_CreateEvent(0);                  //Ausgelöstes Ereignis
      IPS_SetName($eid, "E_Trigger");
      IPS_SetEventTrigger($eid, 1, $trigger_id);        //Bei Änderung von Variable 
      IPS_SetParent($eid, $TargetID);         //Ereignis zuordnen
      IPS_SetEventActive($eid, true);             //Ereignis aktivieren
    }	
		private function Registerevent2($TargetID,$Ziel_id)
		{ 
      if(!isset($_IPS))
      global $_IPS;  
      $EreignisID = @IPS_GetEventIDByName("E_rel_true",  $TargetID);
      if ($EreignisID == true){
      if (IPS_EventExists(IPS_GetEventIDByName ( "E_rel_true", $TargetID)))
      {
       IPS_DeleteEvent(IPS_GetEventIDByName ( "E_rel_true", $TargetID));
      }
      }       
      $eid = IPS_CreateEvent(0);                  //Ausgelöstes Ereignis
      IPS_SetName($eid, "E_rel_true");
//      IPS_SetEventTrigger($eid, 1, $Ziel_id);        //Bei Änderung von Variable 
      IPS_SetEventTrigger($eid, 4, $Ziel_id);        //Bei bestimmten Wert
      IPS_SetEventTriggerValue($eid, true);       
      IPS_SetParent($eid, $TargetID);         //Ereignis zuordnen
      IPS_SetEventActive($eid, true);             //Ereignis aktivieren

      $EreignisID = @IPS_GetEventIDByName("E_rel_false",  $TargetID);
      if ($EreignisID == true){
      if (IPS_EventExists(IPS_GetEventIDByName ( "E_rel_false", $TargetID)))
      {
       IPS_DeleteEvent(IPS_GetEventIDByName ( "E_rel_false", $TargetID));
      }
      }       
      $eid = IPS_CreateEvent(0);                  //Ausgelöstes Ereignis
      IPS_SetName($eid, "E_rel_false");
//      IPS_SetEventTrigger($eid, 1, $Ziel_id);        //Bei Änderung von Variable 
      IPS_SetEventTrigger($eid, 4, $Ziel_id);        //Bei bestimmten Wert
      IPS_SetEventTriggerValue($eid, false);       
      IPS_SetParent($eid, $TargetID);         //Ereignis zuordnen
      IPS_SetEventActive($eid, true);             //Ereignis aktivieren
    }
		private function Registerevent3($TargetID,$Ziel_id)
		{ 
      if(!isset($_IPS))
      global $_IPS;  
      $EreignisID = @IPS_GetEventIDByName("E_rel",  $TargetID);
      if ($EreignisID == true){
      if (IPS_EventExists(IPS_GetEventIDByName ( "E_rel", $TargetID)))
      {
       IPS_DeleteEvent(IPS_GetEventIDByName ( "E_rel", $TargetID));
      }
      }       
      $eid = IPS_CreateEvent(0);                  //Ausgelöstes Ereignis
      IPS_SetName($eid, "E_rel");
      IPS_SetEventTrigger($eid, 1, $Ziel_id);        //Bei Änderung von Variable 
      IPS_SetParent($eid, $TargetID);         //Ereignis zuordnen
      IPS_SetEventActive($eid, true);             //Ereignis aktivieren

    }	

}
?>
