<?php
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
        $timerScript='<?php
$SonosId = IPS_GetProperty(IPS_GetParent($_IPS["SELF"]), "Sonos_ID");

if (@IPS_GetObjectIDByName("Sleeptimer", $SonosId)){
    $ip = IPS_GetProperty($SonosId, "IPAddress");
    if (Sys_Ping($ip, 1000) == true) {
        $s_steuer = GetValue(IPS_GetObjectIDByName("Timer", IPS_GetParent($_IPS["SELF"])));
        $s_bmi_aktiv = GetValue(IPS_GetObjectIDByName("Timer", IPS_GetParent($_IPS["SELF"])));

      	if ($s_bmi_aktiv  == 1){
			SNS_Play($SonosId);
			SNS_SetSleepTimer($SonosId, 6);
      	}
    }
}
';
  $timerScriptID = $this->RegisterScript("_timer", "_timer", $timerScript);
  IPS_SetHidden($timerScriptID,true);

  $sk_id=IPS_GetObjectIDByIdent('_timer', $this->InstanceID);
  if ( IPS_ScriptExists($sk_id)){
      IPS_SetScriptContent ( $sk_id, $timerScript);
  }


        $timerScriptaktion = '<?php
$SonosId = IPS_GetProperty(IPS_GetParent($_IPS["SELF"]), "Sonos_ID");
if (@IPS_GetObjectIDByName("Sleeptimer", $SonosId)){
//Script zum WERTEZUWEISEN aus dem Webfrontend
  if($_IPS["SENDER"] == "WebFront"){
      SetValue($_IPS["VARIABLE"], $_IPS["VALUE"]);
  }
  $s_steuer = ($_IPS["VALUE"]);
  $ip = IPS_GetProperty($SonosId, "IPAddress");
  if (Sys_Ping($ip, 1000) == true) {
    if ($s_steuer == 0) {
	  SNS_SetSleepTimer($SonosId, 0);
    }
    if ($s_steuer == 1){
    	SNS_SetSleepTimer($SonosId, 6);
    }
    $s_steuer = GetValue(IPS_GetObjectIDByName("Timer", IPS_GetParent($_IPS["SELF"]))   );
  }
}
';
  $timerScriptaktionID = $this->RegisterScript("_timer_aktion", "_timer_aktion", $timerScriptaktion);

              IPS_SetHidden($timerScriptaktionID,true);
              IPS_SetVariableCustomAction($timer_id,$timerScriptaktionID);
              
              $aktiv = true;
 //BMI              
// var_dump($trigger_id)  ;
if ($trigger_id <> 0) {

              $this->Registerevent1($trigger_id,$timerScriptID,$aktiv);
}              
  $sk_id=IPS_GetObjectIDByIdent('_timer_aktion', $this->InstanceID);
  if ( IPS_ScriptExists($sk_id)){
      IPS_SetScriptContent ( $sk_id, $timerScriptaktion);
  }
            
// sleeptimer ende


//Autostart
        $auto = '<?php
$steuer_id = IPS_GetProperty(IPS_GetParent($_IPS["SELF"]), "Rel_id");
$SonosId = IPS_GetProperty(IPS_GetParent($_IPS["SELF"]), "Sonos_ID");
//$radio=IPS_GetProperty($SonosId, "FavoriteStation");
$radio="Radio Lippe";
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
';
    $autoID = $this->RegisterScript("_autostart", "_autostart", $auto);

             IPS_SetHidden($autoID,true);
//var_dump( $steuer_id)  ;
if ( $steuer_id <> 0) {
             IPS_SetScriptTimer($autoID, 0); 
             $this->Registerevent3($autoID,$steuer_id);
}              
  $sk_id=IPS_GetObjectIDByIdent('_autostart', $this->InstanceID);
  if ( IPS_ScriptExists($sk_id)){
      IPS_SetScriptContent ( $sk_id, $auto);
  }

//Autostart


        $_lcn_sonos = '<?php
// Display ----------------------------------------------------------------------------------------
$DisplayId = IPS_GetProperty(IPS_GetParent($_IPS["SELF"]), "LCNDisplayId");
$SonosId = IPS_GetProperty(IPS_GetParent($_IPS["SELF"]), "Sonos_ID");
$LCNDisplayLine1 = IPS_GetProperty(IPS_GetParent($_IPS["SELF"]), "LCNDisplayLine1");

$DisplayZeile   = $LCNDisplayLine1;
$sourceID= IPS_GetObjectIDByIdent("nowPlaying", $SonosId);
$nowPlaying     = GetValueString($sourceID);
//print_r($nowPlaying);
LCN_SendCommand($DisplayId, "GT", "DT" . $DisplayZeile . "1" . (substr($nowPlaying,  0, 12)));
LCN_SendCommand($DisplayId, "GT", "DT" . $DisplayZeile . "2" . (substr($nowPlaying, 12, 12)));
LCN_SendCommand($DisplayId, "GT", "DT" . $DisplayZeile . "3" . (substr($nowPlaying, 24, 12)));
LCN_SendCommand($DisplayId, "GT", "DT" . $DisplayZeile . "4" . (substr($nowPlaying, 36, 12)));
LCN_SendCommand($DisplayId, "GT", "DT" . $DisplayZeile . "5" . (substr($nowPlaying, 48, 12)));
// Display ----------------------------------------------------------------------------------------

';
    $_lcn_sonosID  = $this->RegisterScript("_lcn_sonos", "_lcn_sonos", $_lcn_sonos);

        IPS_SetHidden($_lcn_sonosID,true);
        $Trigger_id =$this->ReadPropertyInteger("Trigger");
if ($Trigger_id <> 0) {
        $this->Registerevent_trigger($_lcn_sonosID,$Trigger_id); 
}
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
      IPS_SetEventAction($eid, '{7938A5A2-0981-5FE0-BE6C-8AA610D654EB}', []);
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
      IPS_SetEventAction($eid, '{7938A5A2-0981-5FE0-BE6C-8AA610D654EB}', []);
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
      @IPS_SetEventTriggerValue($eid, true);       
      IPS_SetParent($eid, $TargetID);         //Ereignis zuordnen
      IPS_SetEventAction($eid, '{7938A5A2-0981-5FE0-BE6C-8AA610D654EB}', []);
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
      @IPS_SetEventTriggerValue($eid, false);       
      IPS_SetParent($eid, $TargetID);         //Ereignis zuordnen
      IPS_SetEventAction($eid, '{7938A5A2-0981-5FE0-BE6C-8AA610D654EB}', []);
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
      IPS_SetEventAction($eid, '{7938A5A2-0981-5FE0-BE6C-8AA610D654EB}', []);
      IPS_SetEventActive($eid, true);             //Ereignis aktivieren

    }	

}

