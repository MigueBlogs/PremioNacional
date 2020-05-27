let registro_id, registro_nombre, registro_correo, registro_tr;

$(function() {
  $('.dropdown-trigger').dropdown({
    hover:false,
    inDuration:300,
    outDuration:350,
    container:'main-container'
  });

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

  $('button.borrar').on('click', function() {
    registro_id = null, registro_nombre = null, registro_correo = null, registro_tr = null;
    registro_tr = $(this).parent().parent();
    let tds = $(this).parent().siblings();
    
    registro_id = tds["0"].textContent;
    registro_nombre = tds["1"].textContent;
    registro_correo = tds["2"].textContent;
    $('#borrar-registro-id').text('#'+registro_id);
    $('#borrar-registro-nombre').text(registro_nombre);
    $('#borrar-registro-correo').text(registro_correo);
    $('#modal-borrar').modal('open');
  })

  $('#borrar-registro-aceptar').on('click', function() {
    if (registro_id == null || registro_nombre == null || registro_correo == null){
      alert("Ha ocurrido un error al tratar de eliminar este registro.");
      return;
    }
    data = {id: registro_id, nombre: registro_nombre, correo: registro_correo, seguro_borrar: "sí"};
    
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

});