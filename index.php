<?php
    // set_time_limit(1000);
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

    foreach($days as $i => $day) {
        foreach ($villes as $ville => $value) {
            if($value == 1) {
                $url = "http://clic.pro/r506/data-" . $ville . "-" . $days[$i] . ".csv";
                $csv = file_get_contents($url);

                $filename = tempnam(sys_get_temp_dir(), "csv");
                file_put_contents($filename, $csv);

                $handle = fopen($filename, "r");

                while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                    list($date, $heure) = explode(' ', $data[1]);
                    // echo "$ville, $data[0], $date, $heure, $data[2], $data[3], $data[5], $data[7], $data[8] <br>";
                    $check_sql = "SELECT COUNT(*) as count FROM etl.table WHERE reference = '$data[0]' AND ville = '$ville'";
                    $result = mysqli_query($CONNEXION, $check_sql);
                    $row = mysqli_fetch_assoc($result);

                    if ($row['count'] == 0) {
                        $sql = "INSERT INTO etl.table (ville, reference, date, heure, nom, prenom, article, prix, status) 
                            VALUES ('$ville', '$data[0]', '$date', '$heure', '$data[2]', '$data[3]', '$data[5]', '$data[7]', '$data[8]')";
                        if (mysqli_query($CONNEXION, $sql)) {
                            $succes1 = "Données ajouté avec succès";
                        } else {
                            echo "Erreur : " . mysqli_error($CONNEXION) . "<br>";
                        }
                    } else {
                        $update_sql = "UPDATE etl.table SET status = '$data[8]' WHERE reference = '$data[0]' AND ville = '$ville'";
                        if (mysqli_query($CONNEXION, $update_sql)) {
                            $succes2 = "Status mis à jour avec succès";
                        } else {
                            echo "Erreur : " . mysqli_error($CONNEXION) . "<br>";
                        }
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
                    // echo "$ville, $data[10], $data[0], $data[1], $data[5], $data[4], $data[2], $data[6], $data[9] <br>";
                    $check_sql = "SELECT COUNT(*) as count FROM etl.table WHERE reference = '$data[10]' AND ville = '$ville'";
                    $result = mysqli_query($CONNEXION, $check_sql);
                    $row = mysqli_fetch_assoc($result);

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

                    if ($row['count'] == 0) {
                        $sql = "INSERT INTO etl.table (ville, reference, date, heure, nom, prenom, article, prix, status) 
                            VALUES ('$ville', '$data[10]', '$data[0]', '$data[1]', '$data[5]', '$data[4]', '$data[2]', '$data[6]', '$data[9]')";
                        if (mysqli_query($CONNEXION, $sql)) {
                            $succes1 = "Données ajouté avec succès";
                        } else {
                            echo "Erreur : " . mysqli_error($CONNEXION) . "<br>";
                        }
                    } else {
                        $update_sql = "UPDATE etl.table SET status = '$data[9]' WHERE reference = '$data[10]' AND ville = '$ville'";
                        if (mysqli_query($CONNEXION, $update_sql)) {
                            $succes2 = "Status mis à jour avec succès";
                        } else {
                            echo "Erreur : " . mysqli_error($CONNEXION) . "<br>";
                        }
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
                        // echo "$ville, $data[0], $date, $heure, $data[2], $data[3], $data[5], $data[7], $data[8] <br>";
                        $check_sql = "SELECT COUNT(*) as count FROM etl.table WHERE reference = '$data[0]' AND ville = '$ville'";
                        $result = mysqli_query($CONNEXION, $check_sql);
                        $row = mysqli_fetch_assoc($result);

                        switch ($data[9]) {
                            case "Livré":
                                $data[9] = 'OK';
                                break;
                            case "Retourné":
                                $data[9] = 'RT';
                                break;
                            case "Annulé":
                                $data[9] = 'AN';
                                break;
                            case "En attente":
                                $data[9] = 'AT';
                                break;
                        }

                        if ($row['count'] == 0) {
                            $sql = "INSERT INTO etl.table (ville, reference, date, heure, nom, prenom, article, prix, status) 
                                VALUES ('$ville', '$data[2]', '$date', '$heure', '$data[6]', '$data[7]', '$data[4]', '$data[5]', '$data[8]')";
                            if (mysqli_query($CONNEXION, $sql)) {
                                $succes1 = "Données ajouté avec succès";
                            } else {
                                echo "Erreur : " . mysqli_error($CONNEXION) . "<br>";
                            }
                        } else {
                            $update_sql = "UPDATE etl.table SET status = '$data[8]' WHERE reference = '$data[2]' AND ville = '$ville'";
                            if (mysqli_query($CONNEXION, $update_sql)) {
                                $succes2 = "Status mis à jour avec succès";
                            } else {
                                echo "Erreur : " . mysqli_error($CONNEXION) . "<br>";
                            }
                        }
                    }

                    fclose($handle);
                    unlink($filename);
                }
            }
        }
    }
?>
