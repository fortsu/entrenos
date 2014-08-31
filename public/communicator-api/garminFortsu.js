function fortsuDateTime(dateTimeGC) { //11/16/2010 12:11:40
    var tmp = dateTimeGC.split(" ");
    var time = tmp[1]; // 12:11:40
    var tmp2 = tmp[0].split("/"); // 11/16/2010
    var result = tmp2[2] + "-" + tmp2[0] + "-" + tmp2[1] + " " + time;
    return result;
}

function exit_gcdiv() {
    // hiding popup div
    popup('garminconnect');
    // removing content from divs
    jQuery("#msg").empty();
    jQuery("#devices").empty();
    jQuery("#progress").empty();
    jQuery("#data").empty();
    jQuery("#import_progress").empty();
    // rolling up import menu
    displayDiv('arrow_new','new_options');
}

/**
 * Copyright © 2007 Garmin Ltd. or its subsidiaries.
 *
 * Licensed under the Apache License, Version 2.0 (the 'License')
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *    http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an 'AS IS' BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 * 
 * @fileoverview GarminDeviceControlDemo Demonstrates Garmin.DeviceControl.
 * 
 * @author Michael Bina michael.bina.at.garmin.com
 * @version 1.0
 * @Original URL: http://developer.garmin.com/web/communicator-api/examples/GarminDeviceControlDemo.js 
 * @Changes and modifications by FortSu 
 */

var control;
var readType;
var new_activities;
var completed=0;
var number_new=0;
var total_new=0;
//create a call-back listener class
var listener = Class.create();
listener.prototype = {
    initialize: function() { 
      this.activityListing = $("activityListing");
    },
    
    onFinishFindDevices: function(json) { 
        var devices = json.controller.getDevices();
		var num = json.controller.numDevices;
        //console.log("Found %d devices (devices array length: %d)", num, devices.length);
		if (num == 1) {
			var str = "Buscando actividades en dispositivo ";
			str += "'"+devices[0].getDisplayName()+"' ";
			str += "("+devices[0].getDescription() +")";
			$('devices').innerHTML = str;
            findTracks();
        } else if (num > 1) {
            var str = "Se han encontrado múltiples dispositivos. Por favor deje conectado solamente uno:\n\r";
            for (var i = 0; i < devices.length; i++) {
                str += "'"+devices[i].getDisplayName()+"' ("+devices[i].getDescription() +")\n\r";
            }
            alert(str);
            exit_gcdiv();
		} else {
            alert("No se ha encontrado ningún dispositivo");
            exit_gcdiv();
		}      
    },
    
    onProgressReadFromDevice: function(json) {
		var str = "Progreso: " + json.progress;
		$('progress').innerHTML = str;
	},
	
    onFinishReadFromDevice: function(json) {                    
  		if (json.success) {
  		    if (readType == Garmin.DeviceControl.FILE_TYPES.tcxDir) { //retrieving general data from Garmin FR
                this.factory = Garmin.TcxActivityFactory;
                if (this.factory != null) {
                    // storing all data into array of activity objects
                    this.activities = this.factory.parseDocument(json.controller.gpsData);
                    var total = "Encontradas "+ this.activities.length +" actividades";
                    $('data').innerHTML += total;
                    // looking for new ones 
                    this._checkNewActivities(this.activities);
                } else {
                    alert("Failed to import Garmin.TcxActivityFactory");
                    exit_gcdiv();
                }
            } else { //retrieving detailed data from each activity
                //removing tabs, new lines, etc.
                var xml_compact = json.controller.gpsDataString.replace(/[\n\r\t]/g,'');
                //getting rid of multiple white spaces
                var xml_trimmed = xml_compact.replace(/\s+/g,' ');
                var data = {'xmlstring': xml_trimmed};
                //console.log("Call %d/%d to importActivity, %d remaining", number_new, total_new, new_activities.length);
                importActivity(Object.toJSON(data));                
                // Cleanest way to deal with the js single-thread issue for now.
				// Cutting out to immediately move on to the next activity in the queue before listing.
				if (new_activities.length > 0) {
				    readType = Garmin.DeviceControl.FILE_TYPES.tcxDetail;
			        var activity_id = new_activities.shift();
                    control.readDetailFromDevice(readType, activity_id);
                    number_new += 1;
                    $('import_progress').innerHTML = "Leyendo " + number_new + "/" + total_new + " (" + parseInt(number_new*100/total_new) + "%)";
                }
            }
        } else {
            alert("Ocurrió un error leyendo del dispositivo")
            exit_gcdiv();
        }
  },
  
    _checkNewActivities: function(activities) {
	    var FRInfo = new Array(); // Array of activities described by json strings
	    for (var i = 0; i < activities.length; i++) {
		    var activity = activities[i];
		    var dateTimeGC = activity.getStartTime().format(Garmin.DateTimeFormat.FORMAT.timestamp, true, false);
		    var info = {'id': activity.getAttribute("activityName"), 'dateTime': fortsuDateTime(dateTimeGC)};
		    FRInfo.push(info);
	    }
	    sendData(Object.toJSON(FRInfo));
    },
}

function loadGarminConnect(server_name) {
    try {
        control = new Garmin.DeviceControl();
        control.register(new listener());
        var host = "http://" + server_name;
        var host_key = "";
        // http://developer.garmin.com/web-device/garmin-communicator-plugin/get-your-site-key/
        switch (server_name) {
            case "fortsu.com":
                host_key = "9173250440d34f4d502901d7c4a70f23";
                break;
            case "www.fortsu.com":
                host_key = "298e977a1ac983452751bbd9bcb9618f";
                break;
            case "dev.fortsu.com":
                host_key = "55d1564ee583902281d502c44a8114af";
                break;
            case "entrenos.fortsu.com":
                host_key = "859ee21c6b0e162f6039e020ca3759c5";
                break;
        }
        var unlocked = control.unlock( [host,host_key] );
  	    if (unlocked) {
            control.findDevices();
        } else {
            throw "No se pudo desbloquear el plugin de Garmin Connect";
        }
    } catch(e) {
        alert(e);
        exit_gcdiv();
    }
}

function findTracks() {
    try {
  		var numero = control.getDeviceNumber();
  	    control.setDeviceNumber(numero);
  	    readType = Garmin.DeviceControl.FILE_TYPES.tcxDir;
        control.readDataFromDevice(readType);
    } catch(e) {                                                              
        throw e; 
    }
}

function sendData(data) {
	if (window.XMLHttpRequest) {
		xmlhttp=new XMLHttpRequest();
	}
	
	xmlhttp.onreadystatechange=function() {
		if (xmlhttp.readyState==4 && xmlhttp.status==200) {
            try {
                if (xmlhttp.responseText){
		            new_activities = xmlhttp.responseText.evalJSON();  
			        if (new_activities.length > 0) {
			            total_new = new_activities.length;
			            $('data').innerHTML += " (" + total_new + " nuevas)";
			            readType = Garmin.DeviceControl.FILE_TYPES.tcxDetail;
			            var activity_id = new_activities.shift();
                        control.readDetailFromDevice(readType, activity_id);
                        number_new += 1;
                        $('import_progress').innerHTML = "Leyendo datos del dispositivo: " + number_new + "/" + total_new;
                    } else {
                        throw "Se ha producido un error al parsear las actividades procedentes del dispositivo";
                    }
                } else {
                    throw "No se han encontrado actividades nuevas";
                }
            } catch(e) {
                alert(e);
                exit_gcdiv();
            }
		}
	}
	
    var param = "data="+data;
	xmlhttp.open("POST","check_new_gc.php",true);
    xmlhttp.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    xmlhttp.send(param);
}

function importActivity(xml_data) {
	// append div to 'garminconnect' for each new activity -> 'act_<num_new_activity>'
    var current_act = "act_" + number_new;
    jQuery("#garminconnect").append("<div id='" + current_act + "'></div>");
    jQuery("#" + current_act + "").html("Importando actividad " + number_new + "/" + total_new + " ... <img src='images/28-1.gif'>");

    var jqXHR = jQuery.ajax({
        type: "POST",
        url: "gc_import.php",
        data: "num_act=" + current_act + "&data="+xml_data,
        dataType: "json",
        success: function(response) {
            //console.log("Received: " + JSON.stringify(response) + " | " + JSON.stringify(jqXHR));
            jQuery("#" + current_act + "").html("Actividad <b>" + response.start_time + "</b> importada <img src='images/check_ok_16.png'>");
        },
        error: function() {
            //console.log("Received: " + JSON.stringify(jqXHR));
            jQuery("#" + current_act + "").html("Se ha producido un error <img src='images/check_ko_24.png'>");
        },
        complete: function() {
            //console.log("Dispatched %d/%d activities, %d remaining", number_new, total_new, new_activities.length);
            completed += 1;
            if (completed == total_new) {
		        window.location.reload();
		    }
        }
    });
}
