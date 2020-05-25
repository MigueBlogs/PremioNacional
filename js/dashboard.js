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

});