<!DOCTYPE html>
<html lang="fr-fr"
  dir="ltr">

<head>
  <meta charset="utf-8">
<meta name="viewport" content="width=100vw, height=100vH, minimum-scale=1.0, initial-scale=1.0">
<title>contact | Chateau Laubardemont</title>

      <link rel="stylesheet" href="/css/main.min.0f863dc2566dc4363f5903b5d8d12adbd99624bb73c2b60554d493d31ff503a7.css" integrity="sha256-D4Y9wlZtxDY/WQO12NEq29mWJLtzwrYFVNST0x/1A6c=" crossorigin="anonymous">


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


    <form action="mailto:alexandrecorrette@gmail.com" method="GET">
        <fieldset>
            <center>
                <h2>Nous Contacter</h2>
            </center>
            <br>

            <div class="row">

                <div class="form-group col-xs-6" id="First_Name__div">

                    <label for="First_Name">Prénom</label>
                            <input type="text" name="First Name" id="First_Name" class="form-control">

                </div>
            </div>
            <div class="row">
                <div class="form-group col-xs-6" id="Last_Name__div">
                    <label for="Last_Name">Nom</label>
                    <input type="text" name="Last Name" id="Last_Name" class="form-control">
                </div>

            </div>
            <div class="row">

                <div class="form-group col-xs-6" id="Email__div">

                    <label title="required" for="Email">Email *</label>
                    <input type="email" name="Email" id="Email" required="required" placeholder="example@example.com"
                        class="form-control">

                </div>
                <div class="row">

                    <div class="form-group col-xs-6" id="Phone__div">

                        <label for="Phone">Téléphone</label>
                        <input type="tel" name="Phone" id="Phone" placeholder="06 07 00 00 00" class="form-control">

                    </div>
                </div>

            </div>
            <div class="row">
                <div class="form-group col-xs-6" id="Phone__div">

                    <label for="date">Date souhaitée</label>
                    <input type="date" name="date" id="date" placeholder="01/06/2023" class="form-control">

                </div>

            </div>
            <div class="row">

                <div class="form-group col-xs-12" id="Reason__div">


                    <label for="Reason">Type d'évènement'</label>
                    <select name="Reason" id="Reason" required="required" class="form-control">
                        <option value="">Choisissez</option>
                        <option value="Technical Support">Mariage</option>
                        <option value="Sales Contact">Anniversaire</option>
                        <option value="Billing Support">Séminaire</option>
                        <option value="Other">autre</option>
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
                    <input type="submit" value="Envoyer" class="btn btn-block btn-primary" data-disable-with="Submit">
                </div>
            </div>
        </fieldset>
    </form>
</div>
  <?php
  echo 'Your browser: <br>' . $_SERVER['HTTP_USER_AGENT'];
  echo '<br>Author: ' . 'Frank Kunert';
  ?>



    </div>
    
  </main>
  <footer>
    <p>Copyright 2023. All rights reserved.</p>

  </footer>
</body>

</html>