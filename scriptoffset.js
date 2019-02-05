/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
function setCookie (url, offset){
    	var ws=new Date();
        if (!offset && !url) {
                ws.setMinutes(ws.getMinutes()-10);
            } else {
                ws.setMinutes(10+ws.getMinutes());
            }
        document.cookie="scriptOffsetUrl="+url+";expires="+ws.toUTCString();
        document.cookie="scriptOffsetOffset="+offset+";expires="+ws.toUTCString();
    }
    
function deleteCookie() {
    
}    
    
function getCookie(name) {
        var cookie = " " + document.cookie;
        var search = " " + name + "=";
        var setStr = null;
        var offset = 0;
        var end = 0;
        if (cookie.length > 0) {
            offset = cookie.indexOf(search);
            if (offset != -1) {
                offset += search.length;
                end = cookie.indexOf(";", offset)
                if (end == -1) {
                    end = cookie.length;
                }
                setStr = unescape(cookie.substring(offset, end));
            }
        }
        return(setStr);
    }

function showProcess (url, sucsess, offset, action, agc) {
        $('#url, #refreshScript').hide();
        $('.progress').show();
        $('#runScript').text('Пауза!');
        $('.bar').text(url);
        $('.bar').css('width', sucsess * 100 + '%');
        setCookie(url, offset);
        
        updateProgress((sucsess + 0.01)* 100);
        //$('#log').show();
        

        //alert(document.getElementById("agc").innerhtml);
        //$('#agc').show();
            if(agc !== null) {
                if(agc.agcname !== null) {
                    if(agc.mcpzdn == null) agc.mcpzdn = 0;
                    if(agc.bdtsmt == null) agc.bdtsmt = 0;
                    if(agc.fhd == null) agc.fhd = 0;
                    if(agc.clvsrdst == null) agc.clvsrdst = 0;
                    
                    $('#agc tbody').html('<tr><td>'+offset+'</td><td>'+agc.inn+'</td><td>'+agc.agcname
					+'</td><td>'+agc.mcpzdn+'</td><td>'+agc.bdtsmt+'</td><td>'
					+agc.fhd+'</td><td>'+agc.clvsrdst+'</td></tr>');
                    /* $('#log').append('<tr>');
                    $('#log').append('<td>'+offset+'</td>');
                    $('#log').append('<td>'+agc.inn+'</td>');
                    $('#log').append('<td>'+agc.agcName+'</td>');
                    $('#log').append('</tr>'); */
                }
            }
        
        
        //if(agc !== null) {

        //}
        
        /*
         * pause click
         */
        $('#runScript').click(function(){
            //document.location.href=document.location.href;
            document.location.reload(true);
            
        });
        
        //if($('#runScript').text() == 'Пауза!')
            scriptOffset(url, offset, action);
    }

function scriptOffset (url, offset, action) {
        $.ajax({
            url: "http://busgov-master/bg_ajax.php",
            type: "POST",
            data: {
                "action":action
              , "url":url
              , "offset":offset
            },
            success: function(data){
                data = $.parseJSON(data);
                if(data.sucsess < 1) {
                    if(data.agc !== null) {
                        var agc =  data.agc;
                    } else {
                        var agc =  '';
                    }
                    
                    showProcess(url, data.sucsess, data.offset, action, data.agc);
                    } else {
                        setCookie();
                        $('.bar').css('width','100%');
                        $('.bar').text('OK');
                        $('#runScript').text('Еще');
                        alert('Отработаны все учреждения.');
                    }
            }
        });
    }

/*
 *    Create link to download xls file with all rows from db
 *    */    
function dwlTotalTable (action) {
    $.ajax({
            url: "http://busgov-master/bg_ajax.php",
            type: "POST",
            data: {
                "action":'download'
            },
            success: function(data){
                data = $.parseJSON(data);
                if(data.html !== null) {
                    $('#log1').html(data.html);
                    
                }
                else {
                    $('#log1').html('Нет данных для отображения.');
                }
            }
        });
}


/*
 *    Show all rows from database
 *    */    
function showTotalTable (action) {
    $.ajax({
            url: "http://busgov-master/bg_ajax.php",
            type: "POST",
            data: {
                "action":action
            },
            success: function(data){
                data = $.parseJSON(data);
                if(data.html !== null) {
                    $('#log').html(data.html);
                    
                }
                else {
                    $('#log').html('Нет данных для отображения.');
                }
            }
        });
}

/*
 *    Delete all rows from database
 *    */   
function clearDb (action) {
    $.ajax({
            url: "http://busgov-master/bg_ajax.php",
            type: "POST",
            data: {
                "action":'delete'
            },
            success: function(data){
                data = $.parseJSON(data);
                // if php returned data...
                if(data.html !== null) {
                    $('#log').append(data.html);
                    
                }
                
            }
        });
}

function updateProgress(newValue) {
    var progressBar = document.getElementById('ptest');
    progressBar.value = newValue;
    progressBar.getElementsByTagName('span')[0].textContent = newValue;
}
    
$(document).ready(function() {
    
    var url = getCookie("scriptOffsetUrl");
    var offset = getCookie("scriptOffsetOffset"); 
    var jsonUrl = "http://busgov-master/data/data.json";    
    var cnt = null;

    $.ajax(
    {
	url: jsonUrl,
	dataType: "json",
	async: false,
	success: function(data)
	{
		cnt = data.inns.length;
	}
    });
    
    updateProgress(0);
    
    /*
     *if pause button (#runScript) is clicked
     */
    if (url && url != 'undefined') {		
            $('#refreshScript').show();
            $('#runScript').text('Продолжить');
            $('#url').val(url);
            $('#offset').val(offset);
            
            $('#url').hide();
            $('.progress').show();
            $('.bar').show();
            $('.bar').text(url);
            $('.bar').css('width', (offset/cnt)* 100 + '%');
            updateProgress((offset/cnt)* 100);
            //confirm('Нажата пауза!');
    }
        
    
    
    $('#runScript').click(function() {
        
            var action = $('#runScript').data('action');
            var offset = $('#offset').val();
            var url = $('#url').val();
            
            if ($('#url').val() != getCookie("scriptOffsetUrl")) {
                $('#log').html('<table id="agc" class="agencies"><thead><tr>'+
                    '<th>Номер</th><th>Инн</th><th class="name">Наименование</th>'+
                    '<th>Муниц. задание</th><th>ФХД</th><th>Целев. средства</th>'+
                    '<th>Бюдж. смета</th></tr></thead><tbody></tbody></table>');
                setCookie();
                scriptOffset(url, 0, action);
            } else {
                $('#log').html('<table id="agc" class="agencies"><thead><tr>'+
                    '<th>Номер</th><th>Инн</th><th class="name">Наименование</th>'+
                    '<th>Муниц. задание</th><th>ФХД</th><th>Целев. средства</th>'+
                    '<th>Бюдж. смета</th></tr></thead><tbody></tbody></table>');
                scriptOffset(url, offset, action);
            }
            return false;
        });
        
    $('#refreshScript').click(function() {
            
            var action = $('#refreshScript').data('action');
            var url = $('#url').val();
            $('#runScript').text('Старт');
            $('#refreshScript').hide();
            
            //delete Cookies
            setCookie();
            //scriptOffset(url, 0, action);
            return false;
        });
        
    $('#download').click(function() {

         var action = $('#download').data('action');

        /*setCookie();
         scriptOffset(url, 0, action);*/
        dwlTotalTable(action);
         return false;
     });
     
     $('#showTotal').click(function() {

         var action = $('#showTotal').data('action');

        /*setCookie();
         scriptOffset(url, 0, action);*/
        showTotalTable(action);
         return false;
     });
     
    $('#clearDb').click(function() {

         var action = $('#clearDb').data('action');

        /*setCookie();
         scriptOffset(url, 0, action);*/
        if(confirm('Вы уверены, что хотите удалить ВСЕ данные?')) {
            clearDb(action);
        }
         return false;
     });
        
});