<!DOCTYPE html>
<html>

<head>
  <title>Form Detection</title>
  <style>
    body {
      background-color: #f7f7f7;
      font-family: Arial, sans-serif;
      margin: 0;
    }

    h1 {
      color: #333;
      font-size: 36px;
      margin: 50px 0 30px;
      text-align: center;
    }

    form {
      background-color: #fff;
      border-radius: 4px;
      box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
      margin: 0 auto;
      max-width: 500px;
      padding: 30px;
    }

    label {
      display: block;
      font-size: 18px;
      margin-bottom: 10px;
    }

    input[type="text"] {
      border: 1px solid #ccc;
      border-radius: 4px;
      font-size: 16px;
      padding: 10px;
      width: 100%;
    }

    input[type="submit"] {
      background-color: #333;
      border: none;
      border-radius: 4px;
      color: #fff;
      cursor: pointer;
      font-size: 18px;
      padding: 10px 20px;
      margin-top: 20px;
    }

    input[type="submit"]:hover {
      background-color: #555;
    }

    h2 {
      color: #333;
      font-size: 24px;
      margin: 50px 0 30px;
      text-align: center;
    }

    p {
      color: #333;
      font-size: 16px;
      margin: 20px 0;
      text-align: center;
    }

    a {
      color: #333;
      font-weight: bold;
      text-decoration: none;
    }

    a:hover {
      text-decoration: underline;
    }

    table {
      border: 1px black;
      width: 100%;
    }

    th,
    td {
      text-align: left;
      padding: 8px;
    }

    th {
      background-color: #999;
      font-weight: bold;
    }

    tr:nth-child(even) {
      background-color: #f2f2f2;
    }

    tr:hover {
      background-color: #ddd;
    }
  </style>
</head>

<body>
  <h1>Form Detection</h1>
  <form method="POST" enctype="multipart/form-data">
    <label for="image">Upload an image:</label>
    <input type="file" name="image-url" id="image-url" accept="image/*" required>
    <br>
    <input type="submit" value="Analyze">
  </form>
  <br><br>
  <?php
  if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $sasToken = "sv=2022-11-02&ss=bfqt&srt=sco&sp=rwdlacupiytfx&se=2024-05-21T21:56:28Z&st=2024-05-21T13:56:28Z&spr=https,http&sig=hSTtsRL54jlw3aq637hsDXOh6Po8WMbFXTKMtHBkec8%3D";
    $storageAccount = "tema2storage";
    $containerName = "container";
    $blobName = $_FILES['image-url']['name'];
    $filetoUpload = $_FILES['image-url']['tmp_name'];
    $fileLen = filesize($filetoUpload);

    $destinationURL = "https://$storageAccount.blob.core.windows.net/$containerName/$blobName?$sasToken";
    $newURL = "https://$storageAccount.blob.core.windows.net/$containerName/$blobName";

    $currentDate = gmdate("D, d M Y H:i:s T", time());

    $headers = [
      'x-ms-blob-cache-control: max-age=3600',
      'x-ms-blob-type: BlockBlob',
      'x-ms-date: ' . $currentDate,
      'x-ms-version: 2019-07-07',
      'Content-Type: image/png',
      'Content-Length: ' . $fileLen
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $destinationURL);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, file_get_contents($filetoUpload));

    $result = curl_exec($ch);

    if (curl_errno($ch)) {
      echo 'Error:' . curl_error($ch);
    } else {
      echo $result;
    }

    curl_close($ch);

    $url = "https://aitema2.cognitiveservices.azure.com/formrecognizer/v2.1/prebuilt/receipt/analyze";
    $headers = array(
      "Content-Type: application/json",
      "Ocp-Apim-Subscription-Key: 14e9d4e4cfcf47dc835486a752ce134d"
    );

    $data = array(
      "url" => $destinationURL
    );

    $body = json_encode($data);

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $response = curl_exec($ch);
    if (curl_errno($ch)) {
      echo "Error: " . curl_error($ch);
    }
    curl_close($ch);

    $datas = json_decode($response, true);

    // Extragem campurile importante din răspuns
    $results = [];
    if (isset($datas['analyzeResult']['documentResults'][0]['fields'])) {
      foreach ($datas['analyzeResult']['documentResults'][0]['fields'] as $key => $field) {
        $results[$key] = $field['text'];
      }
    }

    $serverName = "tema2.database.windows.net";
    $username = "cox";
    $password = "123Pisica";
    $database = "database";

    try {
      $conn = new PDO("sqlsrv:Server=$serverName;Database=$database", $username, $password);
      $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch (PDOException $e) {
      die("Connection failed: " . $e->getMessage());
    }

    // Modificăm interogarea SQL pentru noua structură a tabelului
    $stmt = $conn->prepare("INSERT INTO FileUploads (Nume, Adresa, Timestamp, Rezultat_Procesare) VALUES (:name, :address, :timestamp, :processingResult)");

    echo '<h2>Form Detection Results:</h2>';
    $aux = 0;
    foreach ($results as $key => $value) {
      echo "<p>$key: $value</p>";
    }

    $name = $blobName;
    $address = $newURL;
    $timestamp = date('Y-m-d H:i:s', time());
    $processingResult = json_encode($results);

    $stmt->bindParam(':name', $name);
    $stmt->bindParam(':address', $address);
    $stmt->bindParam(':timestamp', $timestamp);
    $stmt->bindParam(':processingResult', $processingResult);

    try {
      $stmt->execute();
      $aux = 1;
    } catch (PDOException $e) {
      echo "Error executing SQL statement: " . $e->getMessage();
    }

    if ($aux == 0) {
      echo '<p>No data processed.</p>';
    }

    $query = "SELECT * FROM FileUploads";
    $result = $conn->query($query);

    echo '<h2>Form Detection History:</h2>';
    echo "<table>";
    echo "<tr><th>ID</th><th>Name</th><th>Address</th><th>Timestamp</th><th>Processing Result</th></tr>";
    while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
      echo "<tr>";
      echo "<td>" . $row['ID'] . "</td>";
      echo "<td>" . $row['Nume'] . "</td>";
      echo "<td><a href='" . $row['Adresa'] . "' target='_blank'>" . $row['Adresa'] . "</a></td>";
      echo "<td>" . $row['Timestamp'] . "</td>";
      echo "<td>" . $row['Rezultat_Procesare'] . "</td>";
      echo "</tr>";
    }
    echo "</table>";
  }
  ?>
</body>

</html>
