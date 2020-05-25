
$(function() {
    
    var loginErrorEvent;
    //$('select').formSelect();
    $("#enviar").on("click", function(e){
        //console.log("Hola mundo");
        e.preventDefault();
        var username = $("#username").val().trim();
        var pwd = $("#pwd").val().trim();
        //var simulacro = $("#selectSimulacro").val();
        // && simulacro != ""
        if(username != "" && pwd != "") {
            var data = { login: "", username: username, pwd: pwd };
            $.ajax({
                url: "./login_fns.php",
                method: "POST",
                data: data,
                dataType: "json",
                success: function(data) {
                    var status = data["status"];
                    if(status != 1) {
                        var message = data["message"];
                        $("#message").text(message);
                        $("form")[0].reset();

                        clearTimeout(loginErrorEvent);

                        loginErrorEvent = window.setTimeout(function() {
                            $("#message").text("");
                        }, 5000);
                    } else if(status == 1) {
                        //window.location.href = "http://www.preparados.gob.mx/macrosimulacro/cortes-info.php"; //For Deploy
                        window.location.href = "./dashboard.php";
                    }

                },
                error: function() {
    
                }
            });
        }
    });
});
