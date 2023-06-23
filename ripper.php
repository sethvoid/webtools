<?php
/**
 * Ripper ip address lookup
 *
 */

$ip = readline('Enter IP address: ');

$json = file_get_contents('https://rdap.db.ripe.net/ip/' . $ip);

if (!empty($json)) {
    $information = json_decode($json, true);

    if (isset($information['errorCode'])) {
        echo '===============================RESULTS==========================' . PHP_EOL;
        echo $information['title'] . PHP_EOL;
        foreach ($information['description'] as $errors) {
            echo $errors . PHP_EOL;
        }
        exit();
    }
    echo '===============================RESULTS==========================' . PHP_EOL;
    echo 'RANGE: ' .  $information['handle'] . PHP_EOL;
    echo 'status: ' . $information['status'][0] . PHP_EOL;

    foreach ($information['entities'] as  $entity) {
        echo $entity['handle'] . PHP_EOL;
        foreach ($entity['roles'] as $role) {
            echo  $role . PHP_EOL;
        }
        if (isset($entity['vcardArray'])) {
            echo '------------------Entities-----------------------' . PHP_EOL;
            foreach ($entity['vcardArray'] as $card) {
                if (is_array($card)) {
                    foreach ($card as $cardContent) {
                        if ($cardContent[0] == 'adr') {
                            if (isset($cardContent[1]['label'])) {
                                echo 'ADDRESS : ' . $cardContent[1]['label'] . PHP_EOL;
                            }
                        }

                        if ($cardContent[0] == 'email') {
                            if (isset($cardContent[3])) {
                                echo 'EMAIL: ' . $cardContent[3] . PHP_EOL;
                            }
                        }
                    }
                }
            }

            echo '-----------------------end entity----------------------------' . PHP_EOL;
        }
    }
    if (isset($information['remarks'][0]['description'][0])) {
        echo $information['remarks'][0]['description'][0] . PHP_EOL;
    }
    if (isset($information['events'])) {
        foreach ($information['events'] as $event) {
            echo $event['eventAction'] . ': ' . $event['eventDate'] . PHP_EOL;
        }
    }

    echo '===============================END=====================================' . PHP_EOL;
  }