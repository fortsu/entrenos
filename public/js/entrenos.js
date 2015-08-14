
function showHideLayer(layer_id) {
    jQuery("#"+ layer_id).toggle();
}

function hideLayer(layer_id) {
    jQuery("#"+ layer_id).hide();
}

// Defines an object that contains user activities
var user_activities = {};

function edit_act_field(act_id, act_field, first_value, new_value) {
    var div_id = act_field + "_act_" + act_id;
    var old_value = "";
    // retrieve previous value from shared object if it exists
    if (act_id in user_activities) {
        if (typeof user_activities[act_id][act_field] != 'undefined') {
            old_value = user_activities[act_id][act_field];
        }
    } else {
        user_activities[act_id] = {};
        old_value = first_value;
    }
    var empty_display = "Click para editar";
    var ok_msg = "El campo se ha actualizado correctamente";
    switch (act_field) {
        case "title":
            ok_msg = "El título se ha actualizado correctamente";
            error_msg = "Se ha producido un error al actualizar el título de la actividad";
            empty_display = "Añadir título";
            break;
        case "distance":
            ok_msg = "La distancia se ha actualizado correctamente";
            error_msg = "Se ha producido un error al actualizar la distancia de la actividad";
            empty_display = "Añadir distancia";
            // User introduces distance in km (check if "." or ",") but stored in mm
            // make sure we have "." (javascript) and not "," (some locales)
            var new_value_tmp = new_value.replace(',','.');
            // just 3 decimal positions
            var new_value_float = parseFloat(new_value_tmp).toFixed(3);
            // translate from km into mm
            new_value = new_value_float*1000000;
            break;
        case "duration":
            ok_msg = "La duración se ha actualizado correctamente";
            error_msg = "Se ha producido un error al actualizar la duración de la actividad";
            empty_display = "Añadir duración";
            break;
        case "comments":
            ok_msg = "Los comentarios se han actualizado correctamente";
            error_msg = "Se ha producido un error al actualizar el comentario de la actividad";
            empty_display = "Añadir comentarios";
            break;
    }
    // No empty values allowed for distance and duration fields
    if ((act_field == "distance" || act_field == "duration") && (isNaN(parseFloat(new_value)) || !(new_value > 0))) {
        console.log("Field " + act_field + " can't be empty, restoring old value : " + old_value + " | new: " + new_value);
        if (old_value == "") {
            old_value = "<span style=\"opacity:0.3;\">" + empty_display + "</span>";
        }
        // Distance needs correct format
        if (act_field == "distance") {
            old_value = (parseFloat(old_value)/1000000).toFixed(3);
        }
        jQuery("#" + div_id).html(old_value);
    } else {
        if (new_value != old_value) {
            var jqXHR = jQuery.ajax({
                type: "POST",
                url: "/forms/formWorkout.php",
                dataType: "text",
                data: "action=update_field&act_id=" + act_id + "&act_field=" + act_field + "&new_value=" + new_value + "&old_value=" + old_value,
                success: function() {
                    // update shared object with latest value
                    user_activities[act_id][act_field] = new_value;
                    // preparing data to display
                    jQuery("#" + div_id).css({ opacity: 1 });
                    alert(ok_msg);
                    var ok_value = new_value;
                    if (ok_value == "") {
                       ok_value = "<span style=\"opacity:0.3;\">" + empty_display + "</span>";
                    }
                    // Special actions for distance field
                    if (act_field == "distance") {
                        // Distance needs correct format
                        ok_value = (parseFloat(ok_value)/1000000).toFixed(3);
                        // update speed and pace in DB and refresh displayed pace
                        update_avgs(act_id, act_field, new_value);
                    }
                    jQuery("#" + div_id).html(ok_value);
                },
                error: function() {
                    alert(error_msg);
                    //console.log("Received: " + JSON.stringify(jqXHR));
                    var error_comments = old_value;
                    if (error_comments == "") {
                        error_comments = "<span style=\"opacity:0.3;\">" + empty_display + "</span>";
                    }
                    jQuery("#" + div_id).html(error_comments);
                }
            });
        } else {
            console.log("Values are identical, nothing to change. Old: " + old_value + " | new: " + new_value);
            if (old_value == "") {
                old_value = "<span style=\"opacity:0.3;\">" + empty_display + "</span>";
            }
            if (act_field == "distance") {
                old_value = (parseFloat(old_value)/1000000).toFixed(3);
            }
            jQuery("#" + div_id).html(old_value);
        }
    }
}

/**
 * Update db values of speed and pace according to new value of field (distance || duration)
 * Refresh displayed value of the average field (initially intended for pace in activity page)
 * @param {Number} act_id
 * @param {Number} act_field
 * @param {Number} act_field_value
 * @return nothing
 */
function update_avgs(act_id, act_field, act_field_value) {
    var div_id = "pace_act_" + act_id;
    var jqXHR = jQuery.ajax({
        type: "POST",
        url: "/forms/formWorkout.php",
        data: "action=update_avgs&act_id=" + act_id + "&field=" + act_field + "&value=" + act_field_value,
        success: function() {
            var responseObj = JSON.parse(jqXHR.responseText);
            jQuery("#" + div_id).text(formatPace(responseObj.pace));
        },
        error: function() {
            jQuery("#" + div_id).css("border-color", "#fff");
        }
    });
}

//4.64907 -> 4:38 (truncate)
function formatPace(db_pace) {
    var tmp = db_pace.toString().split(".");
    var minutes = tmp[0];
    // Moving not integer part from decimal to sexagesimal -> (4.64907-4)*0.6 = 0.389442
    var minutes_int = parseInt(minutes);
    var tmp2 = parseFloat((db_pace - minutes_int)*6/10);
    var tmp3 = tmp2.toString().split(".");
    // We only want seconds (2 first digits), discarding ms
    if (tmp3.length < 2) {
        tmp3[1] = "00";
    }
    var seconds = tmp3[1].substr(0,2);
    var new_pace = minutes + ":" + seconds;
    return new_pace;
}

function change_sport(select_id, old_value) {
    // select id is sport_act_XYZ
    var tmp = select_id.split("_");
    var act_field = tmp[0] + "_id"; //actually we could completely hardcode it
    var act_id = tmp.pop();
    // Get selected value
    var sportSelect = document.getElementById(select_id);
    var new_value = sportSelect.options[sportSelect.selectedIndex].value;
    // Using JQuery:
    //var new_value = jQuery("#" + select_id + " :selected").text();

    var ok_msg = "El deporte se ha actualizado correctamente";
    var error_msg = "Se ha producido un error al actualizar el deporte de la actividad";

    var jqXHR = jQuery.ajax({
        type: "POST",
        url: "/forms/formWorkout.php",
        dataType: "text",
        data: "action=update_field&act_id=" + act_id + "&act_field=" + act_field + "&new_value=" + new_value + "&old_value=" + old_value,
        success: function() {
            // preparing data to display
            alert(ok_msg);
            // reload page from server (some figures change depending on sport value)
            //window.location.reload(true);
        },
        error: function() {
            //console.log("Received: " + JSON.stringify(jqXHR));
            alert(error_msg);
            // Setting select to old value
            sportSelect.value = old_value;
        }
    });
}

function edit_equip (equip_id, equip_field) {
    var div_id = "equip_" + equip_id + "_" + equip_field;
    var old_value = jQuery("#" + div_id).attr("title");
    var new_value = jQuery("#" + div_id).html();
    //console.log("Equip id: " + equip_id + " | field: " + equip_field + " | Old value: " + old_value + " | New value: " + new_value);
    if (new_value !== old_value) {
        var jqXHR = jQuery.ajax({
            type: "POST",
            url: "/forms/formEquip.php",
            dataType: "text",
            data: "action=update_equip&equip_id=" + equip_id + "&equip_field=" + equip_field + "&new_value=" + new_value,
            success: function() {
                //console.log("Received: " + JSON.stringify(jqXHR));
                jQuery("#" + div_id).text(jqXHR.responseText);
                jQuery("#" + div_id).attr("title", jqXHR.responseText);
            },
            error: function() {
                alert("Se ha producido un error. Inténtelo de nuevo más tarde");
                //console.log("Received: " + JSON.stringify(jqXHR));
                jQuery("#" + div_id).html(old_value);
                jQuery("#" + div_id).attr("title", old_value);
            }
        });
    }
}

function enable_equip (equip_id) {
    var equip_field = "active";
    var div_id = equip_field + "_" + equip_id;

    var old_value = 1;
    var new_value = 0;
    if (jQuery('#' + div_id).is(':checked')) {
        old_value = 0;
        new_value = 1;
    }

    //console.log("Equip id: " + equip_id + " | field: " + equip_field + " | Old value: " + old_value + " | New value: " + new_value);
    if (new_value !== old_value) {
        var jqXHR = jQuery.ajax({
            type: "POST",
            url: "/forms/formEquip.php",
            dataType: "text",
            data: "action=update_equip&equip_id=" + equip_id + "&equip_field=" + equip_field + "&new_value=" + new_value,
            success: function() {
                //console.log("Received: " + JSON.stringify(jqXHR));
                // Checkbox styling is not as simple as adding basic CSS stuff
                //jQuery("#" + div_id).css('background','green');
            },
            error: function() {
                alert("Se ha producido un error. Inténtelo de nuevo más tarde");
                //console.log("Received: " + JSON.stringify(jqXHR));
                //Checkbox styling is not as simple as adding basic CSS stuff
                //jQuery("#" + div_id).css('background','red');
                jQuery("#" + div_id).prop("checked", old_value);
            }
        });
    }
}

function adv_equip(){
    var link_text = jQuery("#more_equip_info").text();
    if (link_text == "más info >") {
        jQuery('.adv_equip_data').css({"display":"table-cell"});
        jQuery("#more_equip_info").text("< menos info");
    } else {
        jQuery('.adv_equip_data').css({"display":"none"});
        jQuery("#more_equip_info").text("más info >");
    }
}

function loadWorkout(entry, user_id, targetDiv, has_gpx, map) {
    if (typeof map == "undefined") {
        map = "osm";
        //map = "gmaps";
    }
	if (window.XMLHttpRequest) {
		xmlhttp = new XMLHttpRequest();
	}
	xmlhttp.onreadystatechange=function() {
        if (xmlhttp.readyState==4 && xmlhttp.status==200) {
            document.getElementById(targetDiv).innerHTML = xmlhttp.responseText;
            if (has_gpx) {
                var embedChart = new Image();
                // Altitude profile chart is created in infoDivDB.php
                embedChart.src = "/users/" +  user_id + "/reports/alt_profile_" + entry + ".png";
                var chartDiv = "elevation_chart";
                var div = document.getElementById(chartDiv);
                removeImg(chartDiv);
	            div.appendChild(embedChart);
            }
		}
	}

    switch (map) {
        case "osm":
            var build_map = "embed_map_osm.php";
            break;
        case "gmaps":
            var build_map = "embed_map.php";
            break;
        case "bing":
            var build_map = "embed_map_bing.php";
            break;
        default:
            var build_map = "embed_map_osm.php";
    }

    // Adding and loading dynamically embedded map to <head>
    if (has_gpx) {
        var embedMapSrc = "/" + build_map + "?file=users/" + user_id + "/data/" + entry;
        var embedMap = document.createElement('script')
        embedMap.setAttribute("type","text/javascript")
        embedMap.setAttribute("src", embedMapSrc)
        document.getElementsByTagName("head")[0].appendChild(embedMap)
    }

    var infoUrl = "/infoDivDB.php?id=" + entry + "&has_gpx=" + has_gpx;
	xmlhttp.open("GET", infoUrl, true);
	xmlhttp.send();
}

function deleteActivity(start_time) {
    var resp = confirm("¿Desea borrar la entrada " + start_time + "?");
    if (resp == true) {
        //alert("You pressed OK!");
        document.forms['delete_activity'].submit();
    }
    return false;
}

function launch_feedback(form_id, result_id, targetDiv) {
    popup(targetDiv);
    jQuery("#"+result_id).empty().hide();
    jQuery("#"+form_id).show();
}

/**
* Set original value to provide users a hint of what to type
* Mark element (apply css class) as error if left blank
* PD: placeholder not supported by all browsers yet
**/
function checkFormInputBlur(element) {
    if (element.value === '') {
        element.value = element.getAttribute('data-orig');
        element.className += " field-error";
    }
    element.style.opacity='0.7';
}
/**
* Enable field to let users type removing error highlight class
* PD: placeholder not supported by all browsers yet
**/
function checkFormInputFocus(element) {
    jQuery(element).removeClass('field-error');
    if (element.value == element.getAttribute('data-orig'))
        element.value= '';
    element.style.opacity='1';
}
/**
* Check that all mandatory fields in feedback form are present and valid
* Ideally loop through all writable input fields, short version just hardcoded for "subject" and "comments"
**/
function checkDataChanges(myform){
    var fields = ["subject", "comments"];
    var num_fields = fields.length;
    for (var i = 0; i < num_fields; i++) {
        var loop_element = myform.elements[fields[i]];
        // Javascript's trim is not supported on MSIE 8
        var loop_value = jQuery.trim(loop_element.value);
        if ((loop_value === "") || (loop_value == loop_element.getAttribute('data-orig'))) {
            loop_element.className += " field-error";
            loop_element.focus();
            return false;
        }
    }
    return true;
}

// TODO: handle request error!
function send_form(form_id, result_id, php_script) {
    jQuery("#"+result_id).show();
    jQuery("#"+form_id).hide();
    jQuery.post(php_script, jQuery('#'+ form_id).serialize(),
        function(data) {
            jQuery("#"+result_id).append(data.response);
        },
        'json'
    );
}

/**
* Check form data integrity before send it
**/
function sendFormCheck(myform, result_id, php_script) {
    if (checkDataChanges(myform)) {
        if (php_script.indexOf('http://') < 0) {
            php_script = "/" + php_script;
        }
        // getAttribute(XYZ) -> attribute of DOM element | element.id -> element property
        // http://stackoverflow.com/questions/10280250/getattribute-versus-element-object-properties
        send_form(myform.getAttribute("id"), result_id, php_script);
    }
}

function handleFiles(files, targetDiv) {
    // https://developer.mozilla.org/en/DOM/Input.multiple
    // https://developer.mozilla.org/en/DOM/FileList
    // https://developer.mozilla.org/en/DOM/File
    jQuery("#"+targetDiv).empty();
    for (var i = 0, len = files.length; i < len; i++) {
        //alert("Fichero #" + i + ": " + files[i].name + " | Tamaño: " + files[i].size + " | Tipo: " + files[i].type);
        var file_desc = files[i].name + " (" + Math.round(files[i].size/1024) + " kB)<br/>";
        jQuery("#"+targetDiv).append(file_desc);
    }
}

function showCal(year,month,targetDiv) {
	if (window.XMLHttpRequest) {
		xmlhttp=new XMLHttpRequest();
	}
	xmlhttp.onreadystatechange=function() {
        if (xmlhttp.readyState==4 && xmlhttp.status==200) {
            document.getElementById(targetDiv).innerHTML=xmlhttp.responseText;
		}
	}

    var infoUrl="info_calendar.php?year=" + year + "&month=" + month;
	xmlhttp.open("GET",infoUrl,true);
	xmlhttp.send();
}

/*
 * There is a form (myform) in search.php to collect all values that define a search
 * All related input fields are named "search_filter" so when updateSearch is called
 * we need to inspect which data comes attached to this field (NodeList) to build the search
 * Goal, tag and equipment data comes as type checkbox
 * Slider values (at least in html < 5) are recognized as text: "9;15"
 * Date filter comes in a select-one element
 */
function updateSearch(user_id, field) {
    if (window.XMLHttpRequest) {
		xmlhttp=new XMLHttpRequest();
	}
	xmlhttp.onreadystatechange=function() {
        if (xmlhttp.readyState==4 && xmlhttp.status==200) {
            document.getElementById("search_results").innerHTML=xmlhttp.responseText;
        }
    }
    var removeFromSearch = "";
    var refineSearch = "";
    for (i = 0; i < field.length; i++) {
        if (field[i].type == "text" || field[i].type == "select-one") {
            if (refineSearch === "") {
                refineSearch += field[i].value;
            } else {
                refineSearch += "|" + field[i].value;
            }
        } else if (!field[i].checked && field[i].type == "checkbox") {
            if (removeFromSearch === "") {
                removeFromSearch += field[i].value;
            } else {
                removeFromSearch += "|" + field[i].value;
            }
        }
    }
    var infoUrl="searchFilter.php?user_id=" + user_id + "&remove=" + removeFromSearch + "&refine=" + refineSearch;
	xmlhttp.open("GET",infoUrl,true);
	xmlhttp.send();
}

function updateSearch2(user_id, json_search_params) {
    var div_element = document.getElementById("search_results");
    if (window.XMLHttpRequest) {
		xmlhttp=new XMLHttpRequest();
	}
	xmlhttp.onreadystatechange=function() {
        if (xmlhttp.readyState==4 && xmlhttp.status==200) {
            //div_element.style.background = "transparent";
            div_element.innerHTML=xmlhttp.responseText;
        }
    }
    //div_element.style.background = "rgba(255,255,255,1.0) solid";
    var infoUrl="searchFilter.php?user_id=" + user_id + "&json_search_params=" + JSON.stringify(json_search_params);
	xmlhttp.open("GET",infoUrl,true);
	xmlhttp.send();
}

function changeContent(targetDiv, msg) {
    document.getElementById(targetDiv).innerHTML=msg;
}

function update_tags (select_element_id, act_id) {
    var target_div = "user_" + select_element_id; // user_equip
    //var select_value = jQuery("#" + select_element_id).val();
    var option_id = jQuery("#" + select_element_id).prop('selected', true).val(); // equip_5
    var model = jQuery("#" + select_element_id + " :selected").text();
    var tmp_array = option_id.split("_");
    var item_type = tmp_array[0];
    var item_id = tmp_array[1];
    //console.log("target_div: " + target_div + " | record_id: " + act_id + " | select_value: " + item_id + " | option_id: " + option_id + " | model:" + model + " | item_type: " + item_type);
    if (typeof item_id !== "undefined") {
        selectItem(target_div, model, item_id, act_id, item_type);
    }
}

function selectItem(targetDiv, model, item_id, record_id, item_type) {
    var option_id = item_type + "_" + item_id;
    if (document.getElementById(option_id).disabled == 0) {
        //updating database
        if (window.XMLHttpRequest) {
		    xmlhttp=new XMLHttpRequest();
	    }
	    xmlhttp.onreadystatechange=function() {
		    if (xmlhttp.readyState==4 && xmlhttp.status==200) {
			    var new_item_div = "<div id=\"div_" + item_type + "_" + item_id + "\" class=\"div_tag\">";
                new_item_div += model + " <a href='#' onclick='removeItem(" + item_id + "," + record_id + ",\"" + item_type + "\");return false'><img src=\"images/close-icon_16.png\" alt=\"Quitar\" title=\"Quitar\" align=\"absmiddle\"/></a></div>";
                jQuery("#"+targetDiv+"").append(new_item_div);
                document.getElementById(option_id).disabled = true;
		    }
	    }
        switch(item_type) {
            case "tag":
            case "goal":
                var infoUrl = "/forms/formAthlete.php?action=link_" + item_type + "&item_id=" + item_id + "&record_id=" + record_id;
                break;
            case "equip":
                var infoUrl = "/forms/usageEquipment.php?action=add&equip_id=" + item_id + "&record_id=" + record_id;
                break;
        }
	    xmlhttp.open("GET",infoUrl,true);
	    xmlhttp.send();
    } else {
        alert("El elemento " + document.getElementById(option_id).value + " no es seleccionable");
    }
}

function removeItem(item_id, record_id, item_type) {
    var option_id = item_type + "_" + item_id;
    //updating database
    if (window.XMLHttpRequest) {
		xmlhttp=new XMLHttpRequest();
	}
	xmlhttp.onreadystatechange=function() {
		if (xmlhttp.readyState==4 && xmlhttp.status==200) {
            jQuery("#div_"+ item_type +"_" + item_id).hide("slow");
            jQuery("#div_"+ item_type +"_" + item_id).detach();
            document.getElementById(option_id).disabled = false;
		}
	}
    switch(item_type) {
        case "tag":
        case "goal":
            var infoUrl = "/forms/formAthlete.php?action=unlink_" + item_type + "&item_id=" + item_id + "&record_id=" + record_id;
            break;
        case "equip":
            var infoUrl = "/forms/usageEquipment.php?action=remove&equip_id=" + item_id + "&record_id=" + record_id;
            break;
    }
	xmlhttp.open("GET",infoUrl,true);
	xmlhttp.send();
}

function post2FB (act_id, privacy, targetDiv) {
	if (window.XMLHttpRequest) {
		xmlhttp=new XMLHttpRequest();
	}
	xmlhttp.onreadystatechange=function() {
		if (xmlhttp.readyState==4 && xmlhttp.status==200) {
			document.getElementById(targetDiv).innerHTML=xmlhttp.responseText;
		}
	}

    var infoUrl="post2FB.php?act_id=" + act_id + "&privacy=" + privacy;
	xmlhttp.open("GET",infoUrl,true);
	xmlhttp.send();
}

function loadChart(action, week, month, year, targetDiv) {
    var embedChart = new Image();
    embedChart.src="buildChart.php?action=" + action + "&week=" + week + "&month=" + month + "&year=" + year;
    var div = document.getElementById(targetDiv);
    removeImg(targetDiv);
	div.appendChild(embedChart);
}

function removeImg(targetDiv) {
    var img2rem = document.getElementById(targetDiv).getElementsByTagName('img')[0];
    if (img2rem) {
        document.getElementById(targetDiv).removeChild(img2rem);
    }
}

// Added for popup div

function toggle(div_id) {
	jQuery("#"+ div_id).toggle();
}

function blanket_size(popUpDivVar) {
	if (typeof window.innerWidth != 'undefined') {
		viewportheight = window.innerHeight;
	} else {
		viewportheight = document.documentElement.clientHeight;
	}
	if ((viewportheight > document.body.parentNode.scrollHeight) && (viewportheight > document.body.parentNode.clientHeight)) {
		blanket_height = viewportheight;
	} else {
		if (document.body.parentNode.clientHeight > document.body.parentNode.scrollHeight) {
			blanket_height = document.body.parentNode.clientHeight;
		} else {
			blanket_height = document.body.parentNode.scrollHeight;
		}
	}
	var blanket = document.getElementById('blanket');
	blanket.style.height = blanket_height + 'px';
	var popUpDiv = document.getElementById(popUpDivVar);
	popUpDiv_height=blanket_height/2-jQuery('#' + popUpDivVar).height()/2;
	popUpDiv.style.top = popUpDiv_height + 'px';
}

function window_pos(popUpDivVar) {
	var popUpDiv = document.getElementById(popUpDivVar);
    window_width = popUpDiv.parentNode.offsetWidth/2 - jQuery('#' + popUpDivVar).width()/2;
	popUpDiv.style.left = window_width + 'px';
}

function popup(windowname) {
	blanket_size(windowname);
	window_pos(windowname);
	toggle('blanket');
	toggle(windowname);
    toggle('result');
}

function loadGC(server_name) {
    popup('garminconnect');
    loadGarminConnect(server_name);
}

function act_preview (act_id, targetDiv) { // ToDo: caching!
    var div_element = document.getElementById(targetDiv);
	if (window.XMLHttpRequest) {
		xmlhttp=new XMLHttpRequest();
	}
	xmlhttp.onreadystatechange=function() {
		if (xmlhttp.readyState==4 && xmlhttp.status==200) {
			div_element.innerHTML = xmlhttp.responseText;
            div_element.style.background = "rgba(0,0,0,0.7)"
		} else {
            div_element.innerHTML = "Cargando datos...";
        }
	}
    div_element.style.display = "block";
    div_element.style.background = "rgba(0,0,0,0.7) url('images/28-1.gif') center no-repeat"
    var infoUrl = "/forms/formWorkout.php?action=preview&act_id=" + act_id;
	xmlhttp.open("GET",infoUrl,true);
	xmlhttp.send();
}

function displayDiv (img_id, target_div) {
    jQuery("#" + target_div).toggle("blind", { direction: "vertical" });
    if (jQuery("#" + img_id).attr("src") == "images/down_arrow.png") {
        jQuery("#" + img_id).attr("src","images/right_arrow.png");
    } else {
        jQuery("#" + img_id).attr("src","images/down_arrow.png");
    }
}

function changeVisibility (act_id) {
    var str = jQuery("a#visible").text();
    if (str == "Actividad pública"){
        next_status = 0;
    } else {
        next_status = 1;
    }
    changeActVisibility(act_id, next_status);
}

function changeActVisibility(act_id, next_status) {
	if (window.XMLHttpRequest) {
		xmlhttp=new XMLHttpRequest();
	}
	xmlhttp.onreadystatechange=function() {
		if (xmlhttp.readyState==4 && xmlhttp.status==200) {
            var result = JSON.parse(xmlhttp.responseText);
            var result_msg = "";
            if (result.hasOwnProperty("success")) {
                result_msg = result.success;
			    alert(result_msg);
                if (next_status === 0){
                    jQuery('a#visible').text("Actividad privada");
                    jQuery("#lock").attr("src","/images/lock_16x16.png");
                    jQuery("#lock").attr("alt","Privado");
	                jQuery('a#visible').attr("title", "Permitir acceso público");
                    disable_share_links();
                } else {
                    jQuery('a#visible').text("Actividad pública");
                    jQuery("#lock").attr("src","/images/unlock_16x16.png");
                    jQuery("#lock").attr("alt","Público");
                    jQuery('a#visible').attr("title", "Restringir la privacidad");
                    enable_share_links();
                }
            } else {
                result_msg = result.error;
                alert(result_msg);
            }
		}
	}
    var infoUrl= "/forms/formWorkout.php?action=change_visibility&act_id=" + act_id + "&next_status="  + next_status;
	xmlhttp.open("GET",infoUrl,true);
	xmlhttp.send();
}

function enable_share_links () {
    // Provide full visibility to container div
    jQuery('.act-share').css('opacity', 1);
    // Remove onclick alert from links -> http://api.jquery.com/removeattr/
    jQuery(".link-share").prop("onclick", null);
    // Build proper links
    var share_targets = ["fb","tw","gp"];
    var arrayLength = share_targets.length;
    for (var i = 0; i < arrayLength; i++) {
        jQuery("#link_share_" + share_targets[i]).attr('href', jQuery("#link_share_" + share_targets[i]).attr('data-href'));
    }
    jQuery(".link-share").removeAttr("data-href");
}

function disable_share_links () {
    // Make visible share links are disabled
    jQuery('.act-share').css('opacity', 0.3);
    // Add javascript alert to warn user
    jQuery(".link-share").prop("onclick", "alert('Haz pública la actividad para poder compartirla')");
    // Hide real target links
    var share_targets = ["fb","tw","gp"];
    var arrayLength = share_targets.length;
    for (var i = 0; i < arrayLength; i++) {
        jQuery("#link_share_" + share_targets[i]).attr('data-href', jQuery("#link_share_" + share_targets[i]).attr('href'));
    }
    jQuery(".link-share").removeAttr("href");
}


function moveContent (sourceDiv, targetDiv, origContent, has_gpx) {
    if ( has_gpx === undefined ) {
        has_gpx = true;
    }
    if (has_gpx) {
        return_text = 'Mostrar mapa';
        return_title = 'Volver al mapa de la actividad';
    } else {
        return_text = 'Ocultar actividades similares';
        return_title = 'Sin datos para mostrar en mapa';
    }

    if (jQuery("#" + origContent).is(':visible')) {
        jQuery("#" + origContent).hide();
        jQuery("#" + targetDiv).empty();
        //copy content from hidden source div to target div
        jQuery("#" + sourceDiv).show();
        jQuery("#" + sourceDiv).clone().appendTo("#" + targetDiv);
        jQuery("#" + sourceDiv).hide();
        jQuery('a#similar_link').text(return_text);
        jQuery('a#similar_link').attr("title", return_title);
    } else {
        jQuery("#" + targetDiv).empty();
        jQuery("#" + origContent).show();
        jQuery('a#similar_link').text('Actividades similares');
        jQuery('a#similar_link').attr("title", "Actividades con distancia similar (\u00B1 5%)");
    }
}

function moveContent2 (sourceDiv, targetDiv) {
    if (jQuery("#" + targetDiv).is(':empty')) {
        //copy content from hidden source div to target div
        jQuery("#" + sourceDiv).show();
        jQuery("#" + sourceDiv).clone().appendTo("#" + targetDiv);
        jQuery("#" + sourceDiv).hide();
        jQuery('a#laps_link').text('Ocultar vueltas');
    } else {
        jQuery("#" + targetDiv).empty();
        jQuery('a#laps_link').text('Mostrar vueltas');
    }
}

function select_map(html_input) {
    // See https://developer.mozilla.org/en-US/docs/Web/API/HTMLInputElement#Properties
    var parent_form = html_input.form;
    var form_id = parent_form.name;
    var result_id = form_id + "_result";
    // form.action -> 'action' field!!
    var php_script = parent_form.getAttribute('action');
    //console.log("Form name: " + form_id + " | Result id: " + result_id + " | Action: " + php_script + " | Value: " + html_input.value);
    var close_link = "<a href='javascript:void(0);' onClick='jQuery(\"#" + result_id + "\").hide();' title='Cerrar'>[x]</a>";
    // Send form via AJAX
    jQuery.post(php_script, jQuery('#'+ form_id).serialize(),
        function(data) {
            var result = JSON.parse(data);
            var result_msg = "";
            // Remove all classes that may be there from past actions
            jQuery("#" + result_id).removeClass("oculto settings-feedback settings-success settings-error");
            // Remove style attribute added when hiding result div via click
            jQuery("#" + result_id).css('display', '');
            if (result.hasOwnProperty("success")) {
                result_msg = result.success;
                jQuery("#" + result_id).addClass("settings-feedback settings-success");
                // Update current value
                parent_form.maps_current.value = html_input.value;
            } else {
                result_msg = result.error;
                jQuery("#" + result_id).addClass("settings-feedback settings-error");
                // Rollback checked selection
                jQuery("#maps_choice_" + html_input.value).prop("checked", false);
                jQuery("#maps_choice_" + parent_form.maps_current.value).prop("checked", true);
            }
            jQuery("#" + result_id).empty().append(result_msg + " " + close_link);
        }
    );
}
