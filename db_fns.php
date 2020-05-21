<?php
  function dbConnect($user, $pass, $server) {
    $conn = oci_connect($user, $pass, $server, 'AL32UTF8');
    if (!$conn) {
        $e = oci_error();
        trigger_error(htmlentities($e['message'], ENT_QUOTES), E_USER_ERROR);
    }

    return $conn;
  }

  function dbClose($conn, $query) {
    oci_free_statement($query);
    oci_close($conn);
  }
?>
