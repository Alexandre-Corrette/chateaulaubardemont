<?php

$from = 'formulaire-contact@chateau-laubardemont.com';
$to = 'contact@chateau-laubardemont.com';
$header = "MIME-Version: 1.0\r\n";
$header .= "Content-type: text/html; charset=utf-8\r\n";
$header .= "From: $from\r\n";
$msg = '';
$firstName = '';
$lastName = '';
$phone = '';
$reason = '';
$email = '';
$text = '';
$phone = '';
$answerFrom = 'contact@chateau-laubardemont.com';
$answerTo = '';
$fieldError = 0;
$date;
$sent;
$logo = "/logo-carre.png";

if (!empty($_POST)) {

    $honeypot = trim($_POST['name']) . trim($_POST['website']);

    if (empty($honeypot)) {

        $fieldError = 0;
        if (empty($_POST["Message"])) {
            $fieldError += 1;
        } else {
            $text = substr(nl2br(strip_tags($_POST['Message'])), 0, 16384);
        }
        if (!empty($_POST['first_Name'])) {
            $firstName = $_POST['first_Name'];
        }
        if (!empty($_POST['last_name'])) {
            $lastName = $_POST['last_name'];
        }
        if (!empty($_POST['phone'])) {
            $phone = $_POST['phone'];
        }
        if (!empty($_POST['reason'])) {
            $reason = $_POST['reason'];
        }

        if (!empty($_POST['date'])) {
            $date =  new DateTime($_POST['date']);
        } else {
            $fieldError += 3;
        }
        if (empty($_POST["email"])) {
            $fielderror += 2;
        } elseif (!filter_var($_POST["email"], FILTER_VALIDATE_EMAIL)) {
            $fielderror += 4;
        } else {
            $email = $_POST["email"];
        }


        // User input error
        if ($fielderror > 0) {
            $msg = "<div class='fielderror'>";
            switch ($fielderror) {
                case 1:

                    $msg .= "Please fill in the message field.";

                    break;
                case 2:

                    $msg .= "Please fill in the email address.";

                    break;
                case 3:

                    $msg .= "Veuillez préciser une date s'il vous plait";

                    break;
                case 4:

                    $msg .= "The email address is invalid.";

                    break;
                case 5:

                    $msg .= "Please fill in the message field." . "The email address is invalid.";
                    break;
            }
            $msg .= "</div>";
            echo $msg;
        } else {

            $subject =  'Chateau de Laubardemont ' . $firstName . " " . $lastName;
            $message =     "<h2>Chateau de Laubardemont - Formulaire de Contact</h2>" .
                "<p><strong>Message de : </strong>" . $firstName . " " . $lastName  . "</p><hr>" .
                "<p> email: " . $email . "</p>" .
                "<p> téléphone: " . $phone . "</p>" .
                "<p> Type d'évènement: " . $reason . "</p>" .
                "<p> Date souhaitée: " . $date->format('d-m-Y') . "</p>" .
                "<p> Message: " . $text . "</p>" .
                "<button></button>";
                $sent =  mail($to, $subject, $message, $header);
                
                if (!$sent) {

                    $msg = '<div class="mail-error">désolé, une erreur s\'est produite, merci de réessayer ultérieurement.</div>';
                    echo $msg;
                } else {
                    $url = '/';
                    $msg = '<div class="message-success" >Message envoyé avec succès nous vous contacterons rapidement / Message sent! We will contact you ASAP. </div>' . 
                    '<a href='. $url . '> retour à la page d\'accueil</a>';
                    $subjectAnswer =  'Chateau de Laubardemont - Message reçu ';
                    $answerMessage =     "Bonjour " . $firstName . " " . $lastName .
                        "<h2>Chateau de Laubardemont - Formulaire de Contact</h2>" .
                        "Merci de nous avoir contacté, nous allons traiter votre demande rapidement" .
                        "<p> votre email: " . $email . "</p>" .
                        "<p> votre téléphone: " . $phone . "</p>" .
                        "<p> votre évènement: " . $reason . "</p>" .
                        "<p> Date souhaitée: " . $date->format('d-m-Y') . "</p>" .
                        "<p> Votre Message: " . $text . "</p>";
                    $answerTo = $email;
                    $answerToContact = mail($answerTo, $subjectAnswer, $answerMessage, $header);
                    var_dump('answer',$answerToContact,$email);
                    echo $msg;
                }
        }
        
    } else {
        $subject = 'contact Chateau Laubardemont- BAD ROBOT - Contact Form';
        $message =  "<h2>contact chateau laubardemont- BAD ROBOT - Contact Form</h2>";
        $text = substr(nl2br(strip_tags($_POST['message'])), 0, 16384);
        $message .= "<p>BAD ROBOT!</p>"
            . "<p>HTTP_USER_AGENT: " . $_SERVER["HTTP_USER_AGENT"] . "<br>"
            . "REMOTE_ADDR: " . $_SERVER["REMOTE_ADDR"] . "<br>"
            . "REMOTE_HOST: " . $_SERVER["REMOTE_HOST"] . "</p><hr>"
            . "<p>" . $text . "</p>";
        mail($to, $subject, $message, $header);
    }
}
?>