let otroInmueble = false;
let colonia_missing = false;
let county;

function validateForm(){
    if (!primeraParteCompleta()) {
        $('#segunda-parte').addClass('hide');
        $('#primera-parte').show('fast');
        return false;
    }
    if (!segundaParteCompleta()) {
        $('#primera-parte').hide();
        $('#segunda-parte').removeClass('hide');
        return false;
    }
    return true;
}
function goToId(elem_id) {
    const pos = $('#'+elem_id).position();
    window.scrollTo(pos.left, pos.top);
}

function getCounty() {
    return county;
}

function clearSecondForm() {
    $("#Street").val('');
    $("#Neighborhood").val('');
    $("#Postal").val('');
    $("#County").val('');
    $("#StateLong").val('');
    $("#State").val('');
    $("#Latitude").val('');
    $("#Longitude").val('');
    //clearPunto();
}

function primeraParteCompleta() {
    if ($('#responsable').val().trim() === "" ){ $('#segunda-parte').addClass('hide'); $('#primera-parte').show('fast');
        goToId('responsable'); return false; }
    if ($('#tipoInmueble').val().length == 0 ){ $('#segunda-parte').addClass('hide'); $('#primera-parte').show('fast');
        goToId('tipoInmueble'); return false; }
    if ($('#niveles').val().length == 0){ $('#segunda-parte').addClass('hide'); $('#primera-parte').show('fast');
        goToId('niveles'); return false; }
    if ($('#hipotesis').val().length == 0 ){ $('#segunda-parte').addClass('hide'); $('#primera-parte').show('fast');
        goToId('hipotesis'); return false; }
    if ($('#dependencias').val().trim() === "" ){ $('#segunda-parte').addClass('hide'); $('#primera-parte').show('fast');
        goToId('dependencias'); return false; }
    if ($('#participantes').val().length == 0){ $('#segunda-parte').addClass('hide'); $('#primera-parte').show('fast');
        goToId('participantes'); return false; }
    if ($('#pob_flotante').val().length == 0){ $('#segunda-parte').addClass('hide'); $('#primera-parte').show('fast');
        goToId('pob_flotante'); return false; }
    if ($('input[name="propiedad"]').is(":checked") == false) { $('#segunda-parte').addClass('hide'); $('#primera-parte').show('fast');
        goToId('div-radio2'); return false;}
    if ($('input[name="institucion"]').is(":checked") == false) { $('#segunda-parte').addClass('hide'); $('#primera-parte').show('fast');
        goToId('div-radio'); return false;}
    if ($('#discapacidad').val().trim() === "" || $('#discapacidad').val() < 0 ){ $('#segunda-parte').addClass('hide'); $('#primera-parte').show('fast');
        goToId('discapacidad'); return false; }
    if ($('#correo').val().trim() === "" ){ $('#segunda-parte').addClass('hide'); $('#primera-parte').show('fast');
        goToId('correo'); return false; }
    if (otroInmueble && $('#otroTipoInmueble').val().trim() === "") { $('#segunda-parte').addClass('hide'); $('#primera-parte').show('fast');
        goToId('otroTipoInmueble'); return false;}
    return true;
}
function segundaParteCompleta() {
    if ($('#Latitude').val().trim() === "" ){ 
        goToId('map'); return false; }
    if ($('#Longitude').val().trim() === "" ){ 
        goToId('map'); return false; }
    return true;
}
function verificaInicio() {
    $('#btn-continuar').addClass('disabled').removeClass('pulse');

    if ($('#responsable').val().trim() === "" ){ return; }
    if ($('#tipoInmueble').val() == null || $('#tipoInmueble').val().length == 0 ){ return; }
    if ($('#niveles').val() == null || $('#niveles').val().length == 0){ return; }
    if ($('#hipotesis').val() == null || $('#hipotesis').val().length == 0 ){ return; }
    if ($('input[name="institucion"]').is(":checked") == false || $('input[name="institucion"]:checked').val() == ""){ return; }
    if ($('input[name="propiedad"]').is(":checked") == false || $('input[name="propiedad"]:checked').val() == ""){ return; }
    if ($('#dependencias').val() == null || $('#dependencias').val().length == 0 ){ return; }
    if ($('#participantes').val() == null || $('#participantes').val().length == 0){ return; }
    if ($('#discapacidad').val().trim() === "" || $('#discapacidad').val() < 0){ return; }
    if ($('#correo').val().trim() === "" ){ return; }
    if (otroInmueble && $('#otroTipoInmueble').val().trim() === ""){ return; }

    $('#btn-continuar').removeClass('disabled').addClass('pulse');
}
function verificaFinal() {
    $('#btn-submit').addClass('disabled').removeClass('pulse');
    // if ($('#Latitude').val().trim() === "" ){ return; }
    // if ($('#Longitude').val().trim() === "" ){ return; }
    // goToMap($('#Latitude').val().trim(), $('#Longitude').val().trim());
    $('#btn-submit').removeClass('disabled').addClass('pulse');
}

$(document).ready(function() {
    $("#mensaje").click(function(){
        alert("Más adelante podrás cambiar este dato en el portal de 'seguimiento', te pedimos continuar con el registro.");
      });
    
    M.updateTextFields();
    $('.fixed-action-btn').floatingActionButton();
    $('input[data-length]').characterCounter();
    
    $('#primera-parte input').on('change', function(e){
        verificaInicio();
    });

    $('#correo, #otroTipoInmueble').on('input', function(e){
        verificaInicio();
    });

    $('select').on('change', function(){
        if ($(this).val().length > 0){
            $(this).siblings('input').removeClass('invalid').addClass('valid');
        }
        else {
            $(this).siblings('input').addClass('invalid').removeClass('valid');
        }
    });

    $('#tipoInmueble').on('change', function(e){
        
        $('#otroTipoInmueble').removeAttr('required').parent().hide();
        otroInmueble = false;
        $('#checkbox-otro').val('false');
        $("#tipoInmueble option:selected").each(function() {
            if ($(this).val() == "Otro") {
                $('#otroTipoInmueble').attr('required', "").parent().show();
                $('#checkbox-otro').val('true');
                otroInmueble = true;
                M.updateTextFields();
                $('#otroTipoInmueble').characterCounter();
            }
        });
        verificaInicio();
    });

    $('#dependencias').on('change', function(e){
        
        if ($('#dependencias option:selected').val() == "Otra") {
            $('#otra-dependencia').show();
        }
        else {
            $('#otra-dependencia').hide();
        }
        verificaInicio();
    });

    $('#estado-select').on('change', function(){
        if ($(this).val() !== ""){
            debugger
            let data = {idEstado: $(this).val()};
            county = undefined;
            $('#mapa-div').hide('fast');
            $('#municipio-loading').show();
            $.post( "premio_fns.php", data, function(result) {
                //clearColonia();
                $('#municipio-div').show('fast');
                goToId('municipio-div');
                $('#colonias-div').hide();
                $('#municipio-select').empty().append('<option value="" disabled selected="">Elije el municipio</option>');
                $.each(result, function( index, value ) {
                    $('#municipio-select').append('<option value="'+value["id"]+'">'+value["municipio"]+'</option>');;
                });
                $('#municipio-select').formSelect();
                $('#municipio-loading').hide();
                colonia_missing = false;
                $('#municipio-alert').remove();
            }, 'json');
            clearSecondForm();
            verificaFinal();
        }
    });

    $('#municipio-select').on('change', function(){
        if ($(this).val() !== ""){
            let data = {idEstado: $('#estado-select').val(), idMunicipio: $(this).val()}
            $('#mapa-div').hide('fast');
            $('#colonia-loading').show();
            county = $(this).val().trim().toUpperCase();
            $.post( "macro_fns.php", data, function(result) {
                clearColonia();
                $('#colonias-div').show('fast');
                goToId('colonias-div');
                $('#colonia-select').empty().append('<option value="" disabled selected="">Elije la colonia</option>');
                let colonias = {};
                let nombres = [];
                let indices = {};
                $.each(result, function( index, value ) {
                    let id_mun = value["id"];
                    let nombre = value["nombre"];
                    if (nombre.slice(0, 7) == 'Barrio '){
                        nombre = nombre.slice(7);
                    }
                    nombres.push(nombre);
                    indices[nombre] = index;
                    colonias[index] = {'id': id_mun, 'cp': value['cp']};
                });                
                nombres.sort();
                $.each(nombres, function( index, nombre ) {
                    let tmp = colonias[indices[nombre]];
                    $('#colonia-select').append('<option value="'+tmp["id"]+'">'+nombre+' ('+tmp["cp"]+')</option>');
                });
                
                $('#colonia-select').formSelect();
                $('#colonia-loading').hide();
                colonia_missing = false;
            }, 'json');
            clearSecondForm();
            verificaFinal();
        }
    });

    $('#colonia-select').on('change', function(){
        let value = $(this).val();
        if (value != ""){
            if ($('#mapa-container').css('height') != "500px"){
                $('#mapa-div > div:first').append($('#mapa-container')).append('<p>Si no puedes ver el mapa, recarga la página (Control+Shift+R) y asegúrate de habilitar javascript en la página</p>');
                $('.mapContainer').css({'height': '500px', 'width': '100%'});
            }
            $('#mapa-div').show();
            goToId('mapa-div');
            gotoColonia(value, $('#municipio-select').val(), $('#estado-select').val());
            colonia_missing = false;
            clearSecondForm();
            verificaFinal();
        }
    });

    $('#btn-colonia-missing').on('click', function(){
        clearColonia();
        if ($('#mapa-container').css('height') != "500px"){
            $('#mapa-div > div:first').append($('#mapa-container')).append('<p>Si no puedes ver el mapa, recarga la página y asegúrate de habilitar javascript en la página</p>');
            $('.mapContainer').css({'height': '500px', 'width': '100%'});
        }
        $('#mapa-div').show();
        goToId('mapa-div');
        gotoMunicipio($('#municipio-select').val(), $('#estado-select').val());
        colonia_missing = true;
    });

    // $('#btn-continuar').on('click', function(e){
    //     if (primeraParteCompleta()) {
    //         $('#primera-parte').hide();
    //         $('#segunda-parte').removeClass('hide');
    //         goToId('estado-div');
    //         if ($('#estado-select').val() != ""){
    //             $('#estado-select').change();
    //         }
    //     }
    // });

    $('#btn-add-register').on('click', function(){
        $('#tabla-registros').hide();
        $('#btn-add-register').hide();
        $('#btn-table').show();
        $('#form-registro').show();
    });

    $('#btn-table').on('click', function(){
        $('#form-registro').hide();
        $('#tabla-registros').show();
        $('#btn-table').hide();
        $('#btn-add-register').show();
    });

    $('#btn-atras').on('click', function(e){
        $('#segunda-parte').addClass('hide');
        $('#primera-parte').show('fast');
    });

    $('select').formSelect();

    $('#submit-form').on('submit', function (e) {
        if (validateForm()){
            // this.submit();
        }
        else{
            e.preventDefault();
        }
    });

    $('input[type=radio][name=ejercicio]').on('change', function(){
        let data = {hipotesis: this.value};
        if (this.value == "E"){
            $('#hipotesis').empty().append('<option value="Sismo" selected>Sismo</option>');
            $('#hipotesis').formSelect();
        }
        else {
            $.post( "macro_fns.php", data, function(result) {
                    //console.log(result);
                    
                    $('#hipotesis').empty().append('<option value="" disabled selected>Elije una hipótesis</option>');
                    $.each(result, function( group, tipos ) {
                        $('#hipotesis').append('<optgroup label="'+group+'">');
                        $.each(tipos, function( index, tipo ) {
                            $('#hipotesis').append('<option value="'+tipo+'">'+tipo+'</option>');
                        });
                        //console.log(nombre);
                    });
                    $('#hipotesis').formSelect();
            }, 'json')
              .fail(function() {
                $('#error-modal p').text('Ha ocurrido un error de conexión, por favor verifique que está conectado a internet y vuelva a intentarlo');
                $('#error-modal').modal('open');
              });
        }
    });

    if($('input[type=radio][name=ejercicio]:checked').val()== "E"){
        $('#hipotesis').empty().append('<option value="Sismo" selected>Sismo</option>');
        $('#hipotesis').formSelect();
    }

    verificaInicio();
    $('.modal').modal();
    $('.tooltipped').tooltip();

    $('#btn-error-close').on('click', function(e){
        $('#div-error').hide('fast');
    });
});

//initializing collapsible
$(document).ready(function(){
    $('.collapsible').collapsible();
    $("#lastOne").click(function(){
        $('#btn-continuar').removeClass('disabled').addClass('pulse');
    });
  });