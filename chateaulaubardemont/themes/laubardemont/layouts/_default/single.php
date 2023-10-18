{{ define "main" }}
 
{{ $title := "contact"}}
{{ $pageTitle := .Title }}

{{ if eq $pageTitle $title }}
  {{ partial "contact.html"}}
  <?php
  echo 'Your browser: <br>' . $_SERVER['HTTP_USER_AGENT'];
  echo '<br>Author: ' . '{{ .Params.author }}';
  ?>

{{ else }}
<h1>{{ .Title }}</h1>
  {{ $dateMachine := .Date | time.Format "2006-01-02T15:04:05-07:00" }}
  {{ $dateHuman := .Date | time.Format ":date_long" }}
  <time datetime="{{ $dateMachine }}">{{ $dateHuman }}</time>

  {{ .Content }}
  {{ partial "terms.html" (dict "taxonomy" "tags" "page" .) }}
  {{ end }}
{{ end }}
