<?php
set_time_limit(300);
require_once('connexion.php');

ini_set('memory_limit', '512M');

$villes = [
    "paris" => 1, 
    "marseille" => 1, 
    "lyon" => 2, 
    "toulouse" => 2,
    "nice" => 1, 
    "nantes" => 1, 
    "strasbourg" => 2, 
    "montpellier" => 1, 
    "bordeaux" => 1, 
    "lille" => 1, 
    "rennes" => 1, 
    "reims" => 1, 
    "toulon" => 2, 
    "angers" => 1, 
    "dijon" => 1, 
    "brest" => 1, 
    "nimes" => 1, 
    "tours" => 1, 
    "amiens" => 2, 
    "limoges" => 2, 
    "metz" => 1, 
    "perpignan" => 1, 
    "caen" => 2, 
    "mulhouse" => 1, 
    "grenoble" => 1,
    "chambery" => 3
];

$days = [
    "k2d8", "x6p4", "h3t5"
];

$batch_size = 1000;

if(isset($_POST['submit'])) {
    foreach($days as $i => $day) {
        foreach ($villes as $ville => $value) {
            $data_to_insert = [];
            $data_to_update = [];

            $existing_references = [];
            $check_sql = "SELECT reference FROM etl.table WHERE ville = '$ville'";
            $result = mysqli_query($CONNEXION, $check_sql);
            while ($row = mysqli_fetch_assoc($result)) {
                $existing_references[] = $row['reference'];
            }

            if($value == 1) {
                $url = "http://clic.pro/r506/data-" . $ville . "-" . $days[$i] . ".csv";
                $csv = file_get_contents($url);

                $filename = tempnam(sys_get_temp_dir(), "csv");
                file_put_contents($filename, $csv);

                $handle = fopen($filename, "r");

                while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                    list($date, $heure) = explode(' ', $data[1]);

                    if (!in_array($data[0], $existing_references)) {
                        $data_to_insert[] = "('$ville', '$data[0]', '$date', '$heure', '$data[2]', '$data[3]', '$data[5]', '$data[7]', '$data[8]')";
                    } else {
                        $data_to_update[] = "'$data[8]' WHERE reference = '$data[0]'";
                    }

                    if (count($data_to_insert) >= $batch_size) {
                        insertData($CONNEXION, $data_to_insert);
                        $data_to_insert = [];
                    }

                    if (count($data_to_update) >= $batch_size) {
                        updateData($CONNEXION, $data_to_update, $ville);
                        $data_to_update = [];
                    }
                }

                fclose($handle);
                unlink($filename);
            } else if($value == 2) {
                $url = "http://clic.pro/r506/data-" . $ville . "-" . $days[$i] . ".txt";
                $txt = file_get_contents($url);

                $filename = tempnam(sys_get_temp_dir(), "txt");
                file_put_contents($filename, $txt);

                $handle = fopen($filename, "r");

                while (($line = fgets($handle)) !== FALSE) {
                    $data = explode(";", $line);

                    switch ($data[9]) {
                        case 1:
                            $data[9] = 'OK';
                            break;
                        case 2:
                            $data[9] = 'RT';
                            break;
                        case 3:
                            $data[9] = 'AN';
                            break;
                        case 4:
                            $data[9] = 'AT';
                            break;
                    }

                    if (!in_array($data[10], $existing_references)) {
                        $data_to_insert[] = "('$ville', '$data[10]', '$data[0]', '$data[1]', '$data[5]', '$data[4]', '$data[2]', '$data[6]', '$data[9]')";
                    } else {
                        $data_to_update[] = "'$data[9]' WHERE reference = '$data[10]'";
                    }

                    if (count($data_to_insert) >= $batch_size) {
                        insertData($CONNEXION, $data_to_insert);
                        $data_to_insert = [];
                    }

                    if (count($data_to_update) >= $batch_size) {
                        updateData($CONNEXION, $data_to_update, $ville);
                        $data_to_update = [];
                    }
                }

                fclose($handle);
                unlink($filename);
            } else if ($value == 3) {
                if($ville == "chambery" && $i >= 2) {
                    $url = "http://clic.pro/r506/data-" . $ville . "-" . $days[$i] . ".csv";
                    $csv = file_get_contents($url);

                    $filename = tempnam(sys_get_temp_dir(), "csv");
                    file_put_contents($filename, $csv);

                    $handle = fopen($filename, "r");

                    while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                        list($date, $heure) = explode(' ', $data[0]);

                        switch ($data[8]) {
                            case "Livré":
                                $data[8] = 'OK';
                                break;
                            case "Retourné":
                                $data[8] = 'RT';
                                break;
                            case "Annulé":
                                $data[8] = 'AN';
                                break;
                            case "En attente":
                                $data[8] = 'AT';
                                break;
                        }

                        if (!in_array($data[2], $existing_references)) {
                            $data_to_insert[] = "('$ville', '$data[2]', '$date', '$heure', '$data[6]', '$data[7]', '$data[4]', '$data[5]', '$data[8]')";
                        } else {
                            $data_to_update[] = "'$data[8]' WHERE reference = '$data[2]'";
                        }

                        if (count($data_to_insert) >= $batch_size) {
                            insertData($CONNEXION, $data_to_insert);
                            $data_to_insert = [];
                        }

                        if (count($data_to_update) >= $batch_size) {
                            updateData($CONNEXION, $data_to_update, $ville);
                            $data_to_update = [];
                        }
                    }

                    fclose($handle);
                    unlink($filename);
                }
            }

            if (!empty($data_to_insert)) {
                insertData($CONNEXION, $data_to_insert);
            }

            if (!empty($data_to_update)) {
                updateData($CONNEXION, $data_to_update, $ville);
            }
        }
    }

    function insertData($conn, $data) {
        $insert_sql = "INSERT INTO etl.table (ville, reference, date, heure, nom, prenom, article, prix, status) VALUES " . implode(", ", $data);
        if (mysqli_query($conn, $insert_sql)) {
            echo "Données ajouté avec succès<br>";
        } else {
            echo "Erreur : " . mysqli_error($conn) . "<br>";
        }
    }

    function updateData($conn, $data, $ville) {
        $update_sql = "UPDATE etl.table SET status = " . implode(" ", $data) . "  AND ville = '$ville'";
        if (mysqli_query($conn, $update_sql)) {
            echo "Status mis à jour avec succès<br>";
        } else {
            echo "Erreur : " . mysqli_error($conn) . "<br>";
        }
    }
}
//Calcul du taux de retour produits

$nb_produits_rt = 0;

$sql = "SELECT COUNT(*) as count FROM etl.table WHERE status = 'RT'";
$result = mysqli_query($CONNEXION, $sql);
$row = mysqli_fetch_assoc($result);
$nb_produits_rt = $row['count'];

$sql = "SELECT COUNT(*) as count FROM etl.table";
$result = mysqli_query($CONNEXION, $sql);
$row = mysqli_fetch_assoc($result);
$nb_produits = $row['count'] - $nb_produits_rt;

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <title>Données magasin</title>
</head>
<body>
    <form action="" method="post">
        <input type="submit" name="submit" value="Mettre à jour les données">
    </form>
    <script>
        const data = {
            labels: [
                'Green',
                'Black'
            ],
            datasets: [{
                label: 'Taux de retours produits',
                data: [<?php echo $nb_produits; ?>, <?php echo $nb_produits_rt; ?>],
                backgroundColor: [
                    'rgb(75, 192, 192)',
                    'rgb(201, 203, 207)'
                ],
                hoverOffset: 4
            }]
        };
        const config = {
            type: 'pie',
            data: data,
        };
    </script>
    <canvas id="myChart"></canvas>
    <script>
        var myChart = new Chart(
            document.getElementById('myChart'),
            config
        );
    </script>
</body>
</html>
