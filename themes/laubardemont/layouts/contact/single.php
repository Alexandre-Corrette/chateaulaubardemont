{{ define "main" }}
<div class="lg:container mx-auto">

{{ readFile "static/php/contact.php" | safeHTML }}

{{ partial "contact" . }}

</div>
{{ end }}