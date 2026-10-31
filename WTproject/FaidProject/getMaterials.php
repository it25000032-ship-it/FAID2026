<?php
 require_once __DIR__ . "/db.php";
  header("Content-Type: application/json; charset=UTF-8");
  $sql = "SELECT * FROM materials";
  $result = $conn->query($sql);
  $materials = array();
  if ($result->num_rows > 0) {
      while($row = $result->fetch_assoc()) {
          $materials[] = $row;
      }
  }
  echo json_encode($materials);
?>