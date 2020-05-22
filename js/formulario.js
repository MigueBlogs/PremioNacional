function validateForm(){
    if ($('#nombre').val().trim() === "" ){
        goToId('nombre'); return false; }
    if ($('#telefono').val().trim() === "" ){
        goToId('telefono'); return false; }
    if ($('#correo').val().trim() === "" ){
        goToId('correo'); return false; }
    if ($('#estado-select').val().trim() === "" ){
        goToId('estado-select'); return false; }
    if ($('#municipio-select').val().trim() === "" ){
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
    if ($('#telefono').val().trim() === "" ){ return; }
    if ($('#correo').val().trim() === "" ){ return; }
    if ($('#estado-select').val().trim() === "" ){ return; }
    if ($('#municipio-select').val().trim() === "" ){ return; }
    
    $('#btn-submit').removeClass('disabled').addClass('pulse');
}

$(document).ready(function() {    
    M.updateTextFields();
    $('.fixed-action-btn').floatingActionButton();
    $('input[data-length]').characterCounter();
    
    $('#input').on('input change paste', function(e){
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

    $('#estado-select').on('change', function(){
        if ($(this).val() !== ""){
            debugger
            let data = {idEstado: $(this).val()};
            $('#municipio-loading').show();
            $.post("premio_fns.php", data, function(result) {
                $('#municipio-div').show('fast');
                goToId('municipio-div');
                $('#municipio-select').empty().append('<option value="" disabled selected="">Elije el municipio</option>');
                $.each(result, function( index, value ) {
                    $('#municipio-select').append('<option value="'+value["id"]+'">'+value["municipio"]+'</option>');;
                });
                $('#municipio-select').formSelect();
                $('#municipio-loading').hide();
                $('#municipio-alert').remove();
            }, 'json');
            verificaInicio();
        }
    });

    $('#municipio-select').on('change', function(){
        verificaInicio();
    });

    $('select').formSelect();

    $('#submit-form').on('submit', function (e) {
        if (validateForm()){
            // this.submit();
        }
        else{
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
});


//initializing collapsible
$(document).ready(function(){
    $('.collapsible').collapsible();
    $("#lastOne").click(function(){
        $('#btn-continuar').removeClass('disabled').addClass('pulse');
    });
    $('#btn-continuar').on('click',function(){
        $("#instrucciones").hide('fast');
        $("#submit-form").show('fast');
    });
  });
