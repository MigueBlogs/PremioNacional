function readArchivo() {   
    value = $('#archivo').get(0).files[0]   
    const allowedExtensions = /(\.zip|\.rar)$/i;
    if (!allowedExtensions.exec(value.name)){
        $('#archivo').val('');
        alert('Solo se aceptan archivos con extensión zip o rar');
        return;
    }
    if (value.size > 10485760){  // 10MB
        $('#archivo').val('');
        alert('No puedes subir un archivo mayor a 10MB');
        return;
    }
}

function validateForm(){
    if ($('input[name=tipo]:checked').val() == null || $('input[name=tipo]:checked').val() == "") {
        goToId('div-tipo'); return false;}
    if ($('input[name=categoria]:checked').val() == null || $('input[name=categoria]:checked').val() == "") {
        goToId('div-categorias'); return false;}
    if ($('#nombre').val().trim() === "" ){
        goToId('nombre'); return false; }
    if ($('#telefono').val().trim().length > 0 && $('#telefono').val().trim().length != 10){
        goToId('telefono'); return false; }
    if ($('#correo').val().trim() === "" ){
        goToId('correo'); return false; }
    let archivos = $('#archivo');
    if (parseInt(archivos.get(0).files.length) != 1){
        goToId('archivo'); return false;
    }
    if ($('#estado-select').val().trim() === "" ){
        goToId('estado-select'); return false; }
    if ($('#estado-select').val().trim() !== ""  && $('#municipio-select').val() == null || $('#municipio-select').val() === "" ){
        goToId('municipio-select'); return false; }
    return true;
}
function goToId(elem_id) {
    const pos = $('#'+elem_id).position();
    window.scrollTo(pos.left, pos.top);
}

function verificaInicio() {
    $('#btn-submit').addClass('disabled').removeClass('pulse');

    if ($('#nombre').val().trim() === "" ){ return; }
    if ($('#telefono').val().trim().length > 0 && $('#telefono').val().trim().length != 10){ return; }
    if ($('#correo').val().trim() === "" ){ return; }
    let archivos = $('#archivo');
    if (parseInt(archivos.get(0).files.length) != 1){ return; }
    if ($('#estado-select').val() == null || $('#estado-select').val().trim() === "" ){ return; }
    else {
        if ($('#municipio-select').val() == null || $('#municipio-select').val() === "" ){ return; }
    }
    if ($('input[name=tipo]:checked').val() == null || $('input[name=tipo]:checked').val() == "") {return;}
    if ($('input[name=categoria]:checked').val() == null || $('input[name=categoria]:checked').val() == "") {return;}
    $('#btn-submit').removeClass('disabled').addClass('pulse');
}

$(document).ready(function() {    
    M.updateTextFields();
    $('.fixed-action-btn').floatingActionButton();
    $('input[data-length]').characterCounter();
    
    $('input[type=text], input[type=number], input[type=email], input[type=radio]').on('input change paste', function(e){
        verificaInicio();
    });
    $('#telefono').on('input change paste keydown keyup', function(){
        let temp = $(this).val().replace(/\D/, '');
        $(this).val(temp);
        $(this).text(temp);
        let len = $(this).val().length;

        if (len > 0 && len != 10) {
            $(this).removeClass("valid").addClass("invalid");
        }
        else if (len == 10) {
            $(this).removeClass("invalid").addClass("valid");
        }
        else {
            $(this).removeClass("valid").removeClass("invalid");
        }
        temp = $(this).val().replace(/\D/, '');
        $(this).val(temp);
        $(this).text(temp);
    });

    $('#archivo').on('change', function() {
        let archivos = $('#archivo');
        if (parseInt(archivos.get(0).files.length) != 1 ){
            alert("Solo se puede subir un archivo (Zip o RAR).");
            return;
        }
        readArchivo();
        verificaInicio();
    })

    $('select').on('change', function(){
        if ($(this).val().length > 0){
            $(this).siblings('input').removeClass('invalid').addClass('valid');
        }
        else {
            $(this).siblings('input').addClass('invalid').removeClass('valid');
        }
    });

    $('#estado-select').on('change', function(){
        if ($(this).val() !== ""){
            let data = {idEstado: $(this).val()};
            $('#municipio-loading').show();
            $.post("premio_fns.php", data, function(result) {
                $('#municipio-div').show('fast');
                goToId('municipio-div');
                $('#municipio-select').empty().append('<option value="" disabled selected="">Elige el municipio</option>');
                $.each(result, function( index, value ) {
                    $('#municipio-select').append('<option value="'+value["id"]+'">'+value["municipio"]+'</option>');;
                });
                $('#municipio-select').formSelect();
                $('#municipio-loading').hide();
                $('#municipio-alert').remove();
                verificaInicio();
            }, 'json');
        }
    });

    $('#municipio-select').on('change', function(){
        verificaInicio();
    });

    $('select').formSelect();

    $('#submit-form').on('submit', function (e) {
        if (validateForm()){
            // this.submit();
            $('#wait-modal').modal('open');
        }
        else{
            $('#wait-modal').modal('close');
            $('#modal').modal('close');
            $('#error-modal p').text("Completa y verifica los datos ingresados");
            $('#error-modal').modal('open');
            e.preventDefault();
        }
    });

    verificaInicio();
    $('.modal').modal();
    $('.tooltipped').tooltip();

    $('#btn-error-close').on('click', function(e){
        $('#div-error').hide('fast');
    });
    $('#btn-success-close').on('click', function(e){
        $('#div-success').hide('fast');
    });
    if ($('#estado-select').val() != null && $('#estado-select').val() !== "" ){ $('#estado-select').change(); }
});


//initializing collapsible
$(document).ready(function(){
    $('.collapsible').collapsible();
    $("#lastOne").click(function(){
        $('#btn-continuar').removeClass('disabled').addClass('pulse');
    });
    $('#btn-continuar').on('click',function(){
        
        if($('#btn-continuar').hasClass("disabled")){
            alert("Lee todas las instrucciones para iniciar la incripción.");
        }else{
            $("#instrucciones").hide('fast');
            $("#submit-form").show('fast');
        }
    });
  });
