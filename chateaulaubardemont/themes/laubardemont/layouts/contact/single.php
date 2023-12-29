{{ define "main" }}
<div class="container">


    <form novalidate method="post" action="/php/contact.php">
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
                    <input type="email" name="email" id="Email" required="required" placeholder="example@example.com" class="form-control">

                </div>


            </div>
            <div class="row">

                <div class="form-group col-xs-6" id="Phone__div">

                    <label for="Phone">Téléphone</label>
                    <input type="tel" name="phone" id="Phone" placeholder="06 07 00 00 00" class="form-control">

                </div>
            </div>
            <div class="row">
                <div class="form-group col-xs-6" id="Date__div">

                    <label for="date">Date souhaitée *</label>
                    <input type="date" name="date" id="date" placeholder="01/06/2023" class="form-control" required>

                </div>

            </div>
            <div class="row">

                <div class="form-group col-xs-12" id="Reason__div">


                    <label for="reason">Type d'évènement</label>
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
            <div class="row centered" style="text-align: center;">

                <input type="submit" value="Envoyer" class="button">

            </div>
        </fieldset>
    </form>
</div>
{{ end }}