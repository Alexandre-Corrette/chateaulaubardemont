<!DOCTYPE html>
<html lang="{{ or site.Language.LanguageCode site.Language.Lang }}"
  dir="{{ or site.Language.LanguageDirection `ltr` }}">

<head>
  {{ partial "head.html" . }}
</head>

<body>
  {{ $image := .Resources.Get "marie-claire.png" }}
  <main>
    {{ if .IsHome }}
    <div class="container" style="background-image: url('{{ $image.RelPermalink }}');">
      {{ block "main" . }}
      {{ end }}
    </div>
    {{ else }}
    
    <header>
      {{ partial "header.html" . }}
    </header>
 
    <div class="container">
      {{ block "main" . }}
      {{ end }}
    </div>
    {{ end }}
  </main>
  <footer>
    {{ partial "footer.html" . }}
  </footer>
</body>

</html>