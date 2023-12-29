<?php
$from = 'alexandrecorrette@gmail.com';
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
$fieldError = 0;
$date;

if($_POST) {

    $honeypot = trim($_POST['name']) . trim($_POST['website']);
    
    if(empty($honeypot)) 
    {
        if($_SERVER['REQUEST_METHOD'] === "POST") {
            $fieldError = 0;
            if(strlen($_POST["message"]) < 1) {
                $fieldError += 1;
            } else {
                $text = substr(nl2br(strip_tags($_POST['message'])), 0, 16384);
                
            }
            if($_POST['first_Name'] && strlen($_POST['first_Name']) > 0 ) {
                $firstName = $_POST['first_Name'];
            }
            if($_POST['last_name'] && strlen($_POST['last_name']) > 0 ) {
                $lastName = $_POST['last_name'];
            }
            if($_POST['phone'] && strlen($_POST['phone']) > 0 ) {
                $phone = $_POST['phone'];
            }
            if($_POST['reason'] && strlen($_POST['reason']) > 0 ) {
                $reason = $_POST['reason'];
            }
            if($_POST['message'] && strlen($_POST['message']) > 0 ) {
                $text = $_POST['message'];
            
            }
        if($_POST['date'] && strlen($_POST['date']) > 0 ) {
            $date =  new DateTime($_POST['date']);
            
            
        } else {
            $fieldError += 3;
        }
        if (strlen($_POST["email"]) < 1) {
            $fielderror += 2;
        }
        elseif (!filter_var($_POST["email"], FILTER_VALIDATE_EMAIL)) {
            $fielderror += 4;
        }
        else {
            $email = $_POST["email"];
        }
    }
    
    // User input error
    if ( $fielderror > 0 ) {
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
            }
            // There was no user input error
            else {
                
                $subject =  'Chateau de Laubardemont - Nouveau contact ' . $firstName . " " . $lastName ;
                $message = 	"<h2>Chateau de Laubardemont - Formulaire de Contact</h2>" .
                "<p><strong>Message de : </strong>" . $firstName . " ". $lastName  . "</p><hr>" . 
                "<p> email: " . $email . "</p>" .
                "<p> téléphone: " . $phone . "</p>" .
                "<p> Type d'évènement: " . $reason . "</p>" .
                "<p> Date souhaitée: " . $date->format('d-m-Y') . "</p>" .
                "<p> Message: " . $text . "</p>";
                
                if (!mail($to, $subject, $message, $header))
                {
                    
                    $msg = '<div class="mail-error">Sorry, something went wrong. Please try again later.</div>';
                    
                } 
                else {
                    
            $msg = '<div class="mail-success"> Message envoyé avec succès nous vous contacterons rapidement / Message sent! Thanks for getting in contact.</div>';
        }
        
        echo $msg;
       
    }
}
else {
    $subject = 'tekki-tipps.de - BAD ROBOT - Contact Form';
    $message =  "<h2>tekki-tipps.de - BAD ROBOT - Contact Form</h2>";
    $text = substr(nl2br(strip_tags($_POST['message'])), 0, 16384);
    $message .= "<p>BAD ROBOT!</p>"
    . "<p>HTTP_USER_AGENT: " . $_SERVER["HTTP_USER_AGENT"] . "<br>"
    . "REMOTE_ADDR: " . $_SERVER["REMOTE_ADDR"] . "<br>"
    . "REMOTE_HOST: " . $_SERVER["REMOTE_HOST"] . "</p><hr>"
    . "<p>" . $text . "</p>";
    
}
mail($to, $subject, $message, $header);


}
?>