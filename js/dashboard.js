let registro_id, registro_nombre, registro_correo, registro_tr;

$(function() {
  M.updateTextFields();
  $('.dropdown-trigger').dropdown({
    hover:false,
    inDuration:300,
    outDuration:350,
    container:'main-container'
  });

  function validateForm(){
      let archivos = $('#archivo');
      if (parseInt(archivos.get(0).files.length) != 1){
          // goToId('archivo');
          return false;
      }
      return true;
  }

  function readArchivo() {   
      value = $('#archivo').get(0).files[0]   
      const allowedExtensions = /(\.zip|\.rar)$/i;
      if (!allowedExtensions.exec(value.name)){
          $('#archivo').val('');
          alert('Solo se aceptan archivos con extensión ZIP o RAR');
          return;
      }
      if (value.size > 10485760){  // 10MB
          $('#archivo').val('');
          alert('No puedes subir un archivo mayor a 10MB');
          return;
      }if(value.size < 10485760 && allowedExtensions.exec(value.name)){
        $('#btn-update-file').removeClass('disabled');
      }
  }

  function invisible(){
    $(".vista").hide();
  }

  function fechaCorte(){
    var dt = new Date();
    var fecha = {year: 'numeric', month: 'long', day: 'numeric'};
    var hora = {hour:'2-digit'};
    $(".fecha").html(dt.toLocaleString("es-MX", fecha)+', '+ dt.toLocaleString("es-MX",hora)+':00 h.');
  }

  invisible();
  fechaCorte();
  $("#VistaGeneral").show();


  $("#VGen").click(function(){
    invisible();
    $("#VistaGeneral").show('slow');
    fechaCorte();
  });

  $("#VAccesos").click(function(){
    invisible();
    $("#VistaAccesos").show('slow');
  });

  $('.tooltipped').tooltip();
  $('.modal').modal();
  $('#archivo').on('change', function() {
      let archivos = $('#archivo');
      if (parseInt(archivos.get(0).files.length) != 1 ){
          alert("Solo se puede subir un archivo (Zip o RAR).");
          return;
      }
      readArchivo();
  })

  $('button.borrar').on('click', function() {
    registro_id = null, registro_nombre = null, registro_correo = null, registro_tr = null;
    registro_tr = $(this).parent().parent();
    let tds = $(this).parent().siblings();
    
    registro_id = tds["0"].textContent.replace(/\D/, '');
    registro_nombre = tds["1"].textContent;
    registro_correo = tds["2"].textContent;
    $('#borrar-registro-id').text('#D'+registro_id);
    $('#borrar-registro-nombre').text(registro_nombre);
    $('#borrar-registro-correo').text(registro_correo);
    $('#modal-borrar').modal('open');
  })

  $('button.editar').on('click', function() {
    registro_id = null, registro_nombre = null, registro_correo = null, registro_tr = null;
    registro_tr = $(this).parent().parent();
    let tds = $(this).parent().siblings();
    
    registro_id = tds["0"].textContent.replace(/\D/, '');
    registro_nombre = tds["1"].textContent;
    registro_correo = tds["2"].textContent;
    $('#editar-registro-id').text('#'+registro_id);
    $('#editar-registro-nombre').text(registro_nombre);
    $('#editar-registro-correo').text(registro_correo);
    $('#id_update').attr('value',registro_id)
    $('#modal-editar').modal('open');
  })

  $('#submit-form').on('submit', function (e) {
      if (validateForm()){
          // this.submit();
          $('#wait-modal').modal('open');
      }
      else{
          $('#wait-modal').modal('close');
          $('#modal').modal('close');
          $('#error-modal p').text("Verifica tu archivo nuevamente.");
          $('#error-modal').modal('open');
          e.preventDefault();
      }
  });
  $('#btn-error-close').on('click', function(e){
      $('#div-error').hide('fast');
  });
  $('#btn-success-close').on('click', function(e){
      $('#div-success').hide('fast');
  });
  $('#borrar-registro-aceptar').on('click', function() {
    if (registro_id == null || registro_nombre == null || registro_correo == null){
      alert("Ha ocurrido un error al tratar de eliminar este registro.");
      return;
    }
    data = {
      id: registro_id, 
      nombre: registro_nombre, 
      correo: registro_correo, 
      seguro_borrar: "sí"};
    
    $.post( "dash_fns.php", data, function(result) {
      if ("status" in result && result["status"]){
        alert( "Se ha eliminado el registro exitosamente." );
        registro_tr.remove();
      }
      else {
        alert( "No se ha podido eliminar este registro." );
      }
    }, 'json')
      .fail(function() {
        alert( "No se ha podido eliminar este registro." );
      });
  })

  if ($('#vistageneral-tabla > tbody > tr').length == 0) {
    $('#vistageneral-tabla').hide();
    $('#des1').hide();
    
  }else{
    $('#prev').hide();
  }
  if ($('#vistageneral-tabla2 > tbody > tr').length == 0) {
    $('#vistageneral-tabla2').hide();
    $('#des2').hide();
  }else{
    $('#help').hide();
  }

  
});