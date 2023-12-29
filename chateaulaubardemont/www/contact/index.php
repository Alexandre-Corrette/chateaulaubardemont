<!DOCTYPE html>
<html lang="fr-fr"
  dir="ltr">

<head>
  <meta charset="utf-8">
<meta name="viewport" content="width=100vw, height=100vH, minimum-scale=1.0, initial-scale=1.0">
<title>contact | Chateau Laubardemont</title>

      <link rel="stylesheet" href="/css/main.min.9e1257751491cfaf971d4e2fb02e76c0ccd3607ed6bd1766b52731f8847175fb.css" integrity="sha256-nhJXdRSRz6&#43;XHU4vsC52wMzTYH7WvRdmtScx&#43;IRxdfs=" crossorigin="anonymous">


      <script src="/js/main.23cd0c7d837263b9eaeb96ee2d9ccfa2969daa3fa00fa1c1fe8701a9b87251a1.js" integrity="sha256-I80MfYNyY7nq65buLZzPopadqj&#43;gD6HB/ocBqbhyUaE=" crossorigin="anonymous"></script>


</head>

<body>
  
  <main>
    
    
    <header>
      
<div class="logo-container">
    <img src="/images/logo-carre.png" class="nav-logo">
</div>


<nav>
    <a href="/contact">Nous contacter</a>
</nav>


    </header>
 
    <div class="container">
      
 




  <div class="form-container">


    <form novalidate method="POST">
        <fieldset>
            <center>
                <h2>Nous Contacter</h2>
            </center>
            <br>

            <div class="row">

                <div class="form-group col-xs-6" id="First_Name__div">

                    <label for="First_Name">Prénom</label>
                            <input type="text" name="first_Name" id="First_Name" class="form-control">

                </div>
            </div>
            <div class="name">
                <label for="name" class="form-label">Name</label>
                <span class="required-field">*</span>
                <input type="text" class="form-control" id="name" name="name" placeholder="Your Name" required>
              </div>
          
              <div class="website">
                <label for="website" class="form-label">Website</label>
                <span class="required-field">*</span>
                <input type="text" class="form-control" id="website" name="website" placeholder="Your Website URL" required>
              </div>
          
            <div class="row">
                <div class="form-group col-xs-6" id="Last_Name__div">
                    <label for="Last_Name">Nom</label>
                    <input type="text" name="last_name" id="Last_Name" class="form-control">
                </div>

            </div>
            <div class="row">

                <div class="form-group col-xs-6" id="Email__div">

                    <label title="required" for="Email">Email *</label>
                    <input type="email" name="email" id="Email" required="required" placeholder="example@example.com"
                        class="form-control">

                </div>
                <div class="row">

                    <div class="form-group col-xs-6" id="Phone__div">

                        <label for="Phone">Téléphone</label>
                        <input type="tel" name="phone" id="Phone" placeholder="06 07 00 00 00" class="form-control">

                    </div>
                </div>

            </div>
            <div class="row">
                <div class="form-group col-xs-6" id="Phone__div">

                    <label for="date">Date souhaitée *</label>
                    <input type="date" name="date" id="date" placeholder="01/06/2023" class="form-control"  required>

                </div>

            </div>
            <div class="row">

                <div class="form-group col-xs-12" id="Reason__div">


                    <label for="reason">Type d'évènement'</label>
                    <select name="reason" id="reason" required="required" class="form-control">
                        <option value="">Choisissez</option>
                        <option value="Mariage">Mariage</option>
                        <option value="Anniversaire">Anniversaire</option>
                        <option value="Professionel">Séminaire</option>
                        <option value="Autre">autre</option>
                    </select>
                </div>

            </div>
            <div class="row">

                <div class="form-group col-xs-12" id="Message__div">
                    <label title="required" for="Message">Message *</label>
                    <textarea name="Message" id="Message" required="required" class="form-control"></textarea>

                </div>

            </div>
            <br>
            <div class="row">
                <div class="col-xs-12">
                    <input type="submit" value="Envoyer" class="btn btn-primary" data-disable-with="Submit">
                </div>
            </div>
        </fieldset>
    </form>
</div>
  <?php

$from = 'alexandrecorrette@gmail.com';
$to = 'contact@chateaulaubardemont.com';
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
$date = new DateTime();


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
        if($_POST['date'] && (DateTime::createFromFormat('m/d/Y', $_POST['date']) !== false)) {
            $date = $_POST['date'];
            var_dump($date);
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
        
          $subject =  'Chateau de Laubardemont - nouveau contact' . $firstName . $lastName ;
          $message = 	"<h2>Chateau dse Laubardemont.com - Formulaire de Contact</h2>" .
                        "<p><strong>From: </strong>" . $firstName . $lastName  . "</p><hr>" . 
                        "<p>" . $email . "</p>";
                        "<p>" . $phone . "</p>";
                        "<p>" . $reason . "</p>";
                        "<p>" . $date . "</p>";
                        "<p>" . $message . "</p>";
        
        if (!mail($to, $subject, $message, $header))
        {
         
            $msg = '<div class="mail-error">Sorry, something went wrong. Please try again later.</div>';
          
        } 
        else {
          
            $msg = '<div class="mail-success">Message sent! Thanks for getting in contact.</div>';
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


?>



    </div>
    
  </main>
  <footer>
    <p>Copyright 2023. InOktober - All rights reserved.</p>

  </footer>
</body>

</html>